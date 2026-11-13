<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Restoline\Content\Models\TableAccessCode;
use Restoline\Content\Models\TableOrder;
use Restoline\Content\Models\TableOrderItem;
use Restoline\Content\Models\TableOrderingSetting;
use Restoline\Content\Services\TableAccessCodeService;

class TableOrderingController extends Controller
{
    public function index(Request $request)
    {
        $settings = TableOrderingSetting::current();
        if ($request->boolean('changer_table')) {
            $request->session()->forget([
                'restoline_table_ordering.validated',
                'restoline_table_ordering.table',
                'restoline_table_ordering.table_id',
                'restoline_table_ordering.code_id',
                'restoline_table_ordering.date',
            ]);
        }

        $session = (array)$request->session()->get('restoline_table_ordering', []);
        $accessSession = $this->validAccessSession($request);
        $orderToken = (string)$request->session()->get('restoline_table_ordering.current_order_token', '');
        if ($orderToken === '') {
            $orderToken = (string)Str::uuid();
            $request->session()->put('restoline_table_ordering.current_order_token', $orderToken);
        }

        return view('restoline.content::table-ordering.index', [
            'table' => $request->query('table', $accessSession['table'] ?? $session['table'] ?? ''),
            'orderToken' => $orderToken,
            'validated' => $accessSession !== null,
            'categories' => $this->menuCategories(),
            'lastOrder' => $request->session()->get('restoline_table_ordering.last_order'),
            'clientOrders' => $this->clientOrders($request),
            'orderingOpen' => $settings->isOpenNow(),
            'orderingSchedule' => $settings->scheduleLabel(),
            'orderingClosedMessage' => $settings->closed_message,
        ]);
    }

    public function statusPage(Request $request, string $reference)
    {
        $order = TableOrder::with('items')->where('reference', $reference)->firstOrFail();
        $this->rememberClientOrder($request, $order->reference);

        return view('restoline.content::table-ordering.status', [
            'order' => $order,
            'statusUrl' => route('restoline.table-ordering.status-json', ['reference' => $order->reference]),
        ]);
    }

    public function statusJson(Request $request, string $reference): JsonResponse
    {
        $order = TableOrder::with('items')->where('reference', $reference)->firstOrFail();

        return response()->json($this->clientOrderPayload($order));
    }

    public function verify(Request $request, TableAccessCodeService $service)
    {
        $data = $request->validate([
            'table' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'max:16'],
        ]);

        $record = $service->validateAccess($data['table'], $data['code']);

        if (!$record) {
            return back()->withInput()->withErrors([
                'code' => 'Table ou code incorrect. Demandez le code du jour au restaurant.',
            ]);
        }

        $this->mergeOrderingSession($request, [
            'validated' => true,
            'table' => $record->table_name,
            'table_id' => $record->dining_table_id,
            'code_id' => $record->getKey(),
            'date' => $record->valid_date->toDateString(),
            'validated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('restoline.table-ordering.index')->with('success', sprintf(
            'Accès validé pour la table %s.',
            $record->table_name,
        ));
    }

    public function submit(Request $request, TableAccessCodeService $service)
    {
        $settings = TableOrderingSetting::current();
        if (!$settings->isOpenNow()) {
            return back()->withInput()->withErrors([
                'items' => $settings->closed_message ?: 'La commande à table est fermée pour le moment.',
            ]);
        }

        $data = $request->validate([
            'table' => ['required', 'string', 'max:32'],
            'code' => ['nullable', 'string', 'max:16'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
            'order_token' => ['required', 'string', 'max:80'],
        ]);

        $accessCode = null;
        $session = $this->validAccessSession($request);
        if (!$session || strtoupper(trim((string)$session['table'])) !== strtoupper(trim((string)$data['table']))) {
            $rateKey = $this->accessRateKey($request, $data['table']);
            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return back()->withInput()->withErrors([
                    'code' => 'Trop d’essais incorrects. Demandez de l’aide au serveur.',
                ]);
            }

            $accessCode = $service->validateAccess($data['table'], (string)($data['code'] ?? ''));
            if (!$accessCode) {
                RateLimiter::hit($rateKey, 600);
                return back()->withInput()->withErrors([
                    'code' => 'Table ou code incorrect. Vérifiez le code du jour donné par le restaurant.',
                ]);
            }
            RateLimiter::clear($rateKey);

            $session = [
                'validated' => true,
                'table' => $accessCode->table_name,
                'table_id' => $accessCode->dining_table_id,
                'code_id' => $accessCode->getKey(),
                'date' => $accessCode->valid_date->toDateString(),
                'validated_at' => now()->toIso8601String(),
            ];
            $this->mergeOrderingSession($request, $session);
        }

        $orderToken = trim((string)$data['order_token']);
        $existingOrder = TableOrder::where('client_token', $orderToken)->first();
        if ($existingOrder) {
            return redirect()->route('restoline.table-ordering.status', ['reference' => $existingOrder->reference]);
        }

        $quantities = collect($data['items'] ?? [])
            ->mapWithKeys(fn($quantity, $menuId) => [(int)$menuId => (int)$quantity])
            ->filter(fn($quantity) => $quantity > 0);

        if ($quantities->isEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Sélectionnez au moins un plat avant d’envoyer la commande.',
            ]);
        }

        $menus = $this->availableMenuItems()
            ->whereIn('m.menu_id', $quantities->keys()->all())
            ->get()
            ->keyBy('menu_id');

        if ($menus->count() !== $quantities->count()) {
            return back()->withInput()->withErrors([
                'items' => 'Un article sélectionné n’est plus disponible. Merci de rafraîchir la page.',
            ]);
        }

        try {
            $order = DB::transaction(function() use ($session, $data, $quantities, $menus, $orderToken): TableOrder {
                $trackedStocks = $this->lockAndValidateTrackedStocks($quantities, $menus);

                $order = TableOrder::create([
                    'reference' => $this->makeOrderReference(),
                    'client_token' => $orderToken,
                    'dining_table_id' => $session['table_id'] ?? null,
                    'access_code_id' => $session['code_id'] ?? null,
                    'table_name' => (string)$session['table'],
                    'valid_date' => $session['date'] ?? now()->toDateString(),
                    'status' => 'new',
                    'print_status' => 'pending',
                    'note' => trim((string)($data['note'] ?? '')) ?: null,
                    'subtotal' => 0,
                    'submitted_at' => now(),
                ]);

                $subtotal = 0.0;
                foreach ($quantities as $menuId => $quantity) {
                    $menu = $menus->get($menuId);
                    $unitPrice = (float)$menu->menu_price;
                    $lineTotal = $unitPrice * $quantity;
                    $subtotal += $lineTotal;

                    TableOrderItem::create([
                        'order_id' => $order->getKey(),
                        'menu_id' => $menu->menu_id,
                        'menu_name' => $menu->menu_name,
                        'menu_description' => trim(strip_tags(html_entity_decode((string)$menu->menu_description, ENT_QUOTES, 'UTF-8'))) ?: null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }

                $order->subtotal = $subtotal;
                $order->save();

                $this->decrementTrackedStocks($trackedStocks, $quantities);

                return $order;
            });
        } catch (\RuntimeException $exception) {
            return back()->withInput()->withErrors([
                'items' => $exception->getMessage(),
            ]);
        }

        $itemCount = TableOrderItem::where('order_id', $order->getKey())->sum('quantity');
        $request->session()->put('restoline_table_ordering.last_order', [
            'reference' => $order->reference,
            'table' => $order->table_name,
            'count' => $itemCount,
        ]);
        $this->rememberClientOrder($request, $order->reference);
        $request->session()->put('restoline_table_ordering.current_order_token', (string)Str::uuid());

        return redirect()->route('restoline.table-ordering.status', ['reference' => $order->reference]);
    }

    public function printerPending(Request $request): JsonResponse
    {
        if (!$this->printerTokenIsValid($request)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $orders = TableOrder::with('items')
            ->where('status', 'new')
            ->where(function($query): void {
                $query->where('print_status', 'pending')
                    ->orWhere(function($query): void {
                        $query->where('print_status', 'printing')
                            ->where('updated_at', '<', now()->subMinutes(10));
                    });
            })
            ->orderBy('submitted_at')
            ->limit(20)
            ->get();

        $orders->each(function(TableOrder $order): void {
            $order->print_status = 'printing';
            $order->save();
        });

        $orders = $orders
            ->map(fn(TableOrder $order) => $this->printerPayload($order))
            ->values();

        return response()->json([
            'restaurant' => '',
            'print_mode' => 'category_tickets',
            'generated_at' => now()->toIso8601String(),
            'orders' => $orders,
        ]);
    }

    public function printerMarkPrinted(Request $request, string $reference): JsonResponse
    {
        if (!$this->printerTokenIsValid($request)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $order = TableOrder::where('reference', $reference)->firstOrFail();
        $order->print_status = 'printed';
        $order->printed_at = now();
        $order->save();

        return response()->json(['ok' => true, 'reference' => $order->reference]);
    }

    public function printerMarkFailed(Request $request, string $reference): JsonResponse
    {
        if (!$this->printerTokenIsValid($request)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $order = TableOrder::where('reference', $reference)->firstOrFail();
        $order->print_status = 'failed';
        $order->save();

        return response()->json(['ok' => true, 'reference' => $order->reference]);
    }

    protected function menuCategories()
    {
        $rows = $this->availableMenuItems()
            ->select([
                'c.category_id',
                'c.name as category_name',
                'c.description as category_description',
                'm.menu_id',
                'm.menu_name',
                'm.menu_description',
                'm.menu_price',
                'm.menu_priority',
            ])
            ->orderBy('c.priority')
            ->orderBy('c.name')
            ->orderBy('m.menu_priority')
            ->orderBy('m.menu_name')
            ->get();

        return $rows->groupBy('category_id')->map(function($items) {
            $first = $items->first();

            $items = $items->map(function($item) {
                $item->image_url = $this->menuImageUrl((string)$item->menu_name);
                return $item;
            });

            return (object)[
                'id' => $first->category_id,
                'name' => $first->category_name,
                'description' => trim(strip_tags(html_entity_decode((string)$first->category_description, ENT_QUOTES, 'UTF-8'))),
                'hero_image_url' => $this->categoryImageUrl((string)$first->category_name),
                'items' => $items,
            ];
        })->values();
    }

    protected function availableMenuItems()
    {
        $categoryIds = TableOrderingSetting::current()->qrCategoryIds();

        return DB::table('categories as c')
            ->join('menu_categories as mc', 'mc.category_id', '=', 'c.category_id')
            ->join('menus as m', 'm.menu_id', '=', 'mc.menu_id')
            ->leftJoin('stocks as s', function($join): void {
                $join->on('s.stockable_id', '=', 'm.menu_id')
                    ->where('s.stockable_type', '=', 'menus')
                    ->where('s.location_id', '=', 1);
            })
            ->join('locationables as l', function($join): void {
                $join->on('l.locationable_id', '=', 'm.menu_id')
                    ->where('l.locationable_type', '=', 'menus')
                    ->where('l.location_id', '=', 1);
            })
            ->where('c.status', 1)
            ->where('m.menu_status', 1)
            ->where(function($query): void {
                $query->whereNull('s.id')
                    ->orWhere('s.is_tracked', 0)
                    ->orWhere(function($query): void {
                        $query->where('s.is_tracked', 1)
                            ->where('s.quantity', '>', 0)
                            ->where(function($query): void {
                                $query->whereNull('s.out_of_stock_type')
                                    ->orWhere(function($query): void {
                                        $query->where('s.out_of_stock_type', '!=', 'indefinitely')
                                            ->where(function($query): void {
                                                $query->whereNull('s.out_of_stock_until')
                                                    ->orWhere('s.out_of_stock_until', '<=', now());
                                            });
                                    });
                            });
                    });
            })
            ->whereIn('c.category_id', $categoryIds);
    }

    protected function menuImageUrl(string $menuName): ?string
    {
        $normalizedName = mb_strtolower($menuName);

        if (str_contains($normalizedName, 'wan-tan') || str_contains($normalizedName, 'wantan')) {
            return $this->qrMenuImageUrl('wokdasie-menu-potage-wantan.jpg');
        }

        if (str_contains($normalizedName, 'calamars')) {
            return $this->qrMenuImageUrl('wokdasie-menu-beignet-calamars.jpg');
        }

        if (str_contains($normalizedName, 'galette coréenne') || str_contains($normalizedName, 'galette coreenne') || str_contains($normalizedName, 'kimchi')) {
            return $this->qrMenuImageUrl('wokdasie-menu-galette-kimchi.jpg');
        }

        if (str_contains($normalizedName, 'brioche lapin')) {
            return $this->qrMenuImageUrl('wokdasie-menu-brioche-lapin.jpg');
        }

        if (!preg_match('/^([A-Z]\d{2})\./', $menuName, $matches)) {
            return null;
        }

        $code = strtoupper($matches[1]);

        if ($code === 'K22') {
            $slug = str_contains(mb_strtolower($menuName), 'végétarien') ? 'k22-veg' : 'k22-surimi';
        } elseif (str_starts_with($code, 'P')) {
            return $this->qrMenuImageUrl('wokdasie-menu-poke-optimized.jpg');
        } else {
            $slug = strtolower($code);
        }

        return $this->qrMenuImageUrl('wokdasie-menu-'.$slug.'.png');
    }

    protected function categoryImageUrl(string $categoryName): ?string
    {
        return match ($categoryName) {
            'Sushi minute' => $this->qrMenuImageUrl('wokdasie-menu-sushi.png'),
            'Poké bowl minute' => $this->qrMenuImageUrl('wokdasie-menu-poke-optimized.jpg'),
            default => null,
        };
    }

    private function qrMenuImageUrl(string $filename): string
    {
        $relativePath = 'storage/media/uploads/qr-menu/'.$filename;
        $path = public_path($relativePath);
        $version = is_file($path) ? filemtime($path) : time();

        return url('/'.$relativePath).'?v='.$version;
    }

    private function makeOrderReference(): string
    {
        do {
            $reference = 'QR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (TableOrder::where('reference', $reference)->exists());

        return $reference;
    }

    private function printerTokenIsValid(Request $request): bool
    {
        $expected = (string)env('RESTOLINE_PRINTER_GATEWAY_TOKEN', '');
        $given = (string)$request->query('token', $request->bearerToken() ?? '');

        return $expected !== '' && hash_equals($expected, $given);
    }

    private function printerPayload(TableOrder $order): array
    {
        $printRequestedAt = now();
        $items = $this->printerItemsWithCategories($order);

        return [
            'reference' => $order->reference,
            'table' => $order->table_name,
            'submitted_at' => $order->submitted_at?->toIso8601String(),
            'print_requested_at' => $printRequestedAt->toIso8601String(),
            'print_time' => $printRequestedAt->format('H:i'),
            'note' => (string)$order->note,
            'items' => $items,
            'tickets' => $items
                ->groupBy('category')
                ->map(function($ticketItems, string $category) use ($order, $printRequestedAt): array {
                    return [
                        'category' => $category,
                        'table' => (string)$order->table_name,
                        'print_requested_at' => $printRequestedAt->toIso8601String(),
                        'print_time' => $printRequestedAt->format('H:i'),
                        'note' => (string)$order->note,
                        'items' => $ticketItems->values(),
                    ];
                })
                ->values(),
        ];
    }

    private function lockAndValidateTrackedStocks($quantities, $menus)
    {
        $stocks = DB::table('stocks')
            ->where('location_id', 1)
            ->where('stockable_type', 'menus')
            ->whereIn('stockable_id', $quantities->keys()->all())
            ->where('is_tracked', 1)
            ->lockForUpdate()
            ->get()
            ->keyBy('stockable_id');

        foreach ($quantities as $menuId => $quantity) {
            $stock = $stocks->get($menuId);
            if (!$stock) {
                continue;
            }

            $menuName = (string)($menus->get($menuId)->menu_name ?? 'Ce plat');
            if ($this->stockHasOutOfStockOverride($stock) || (int)$stock->quantity <= 0) {
                throw new \RuntimeException($menuName.' n’est plus disponible pour le moment.');
            }

            if ((int)$stock->quantity < (int)$quantity) {
                throw new \RuntimeException(sprintf(
                    '%s : il reste seulement %d portion(s) disponible(s).',
                    $menuName,
                    max(0, (int)$stock->quantity),
                ));
            }
        }

        return $stocks;
    }

    private function decrementTrackedStocks($stocks, $quantities): void
    {
        foreach ($stocks as $menuId => $stock) {
            $quantity = (int)($quantities->get($menuId) ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            DB::table('stocks')
                ->where('id', $stock->id)
                ->update([
                    'quantity' => max(0, (int)$stock->quantity - $quantity),
                    'updated_at' => now(),
                ]);

            DB::table('stock_history')->insert([
                'stock_id' => $stock->id,
                'user_id' => null,
                'order_id' => null,
                'state' => 'sold',
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function stockHasOutOfStockOverride($stock): bool
    {
        if ($stock->out_of_stock_type === null) {
            return false;
        }

        if ($stock->out_of_stock_type === 'indefinitely') {
            return true;
        }

        return $stock->out_of_stock_until && strtotime((string)$stock->out_of_stock_until) > time();
    }

    private function printerItemsWithCategories(TableOrder $order)
    {
        $menuIds = $order->items
            ->pluck('menu_id')
            ->filter()
            ->unique()
            ->values();

        $categories = collect();
        if ($menuIds->isNotEmpty()) {
            $categories = DB::table('menu_categories as mc')
                ->join('categories as c', 'c.category_id', '=', 'mc.category_id')
                ->whereIn('mc.menu_id', $menuIds->all())
                ->orderBy('c.priority')
                ->orderBy('c.name')
                ->select('mc.menu_id', 'c.name')
                ->get()
                ->groupBy('menu_id')
                ->map(fn($rows) => (string)$rows->first()->name);
        }

        return $order->items->map(function($item) use ($categories): array {
            $category = (string)($categories->get($item->menu_id) ?: 'Autres');
            $menuName = (string)$item->menu_name;

            return [
                'category' => $category,
                'quantity' => (int)$item->quantity,
                'name' => $this->kitchenItemName($menuName, $category),
                'description' => (string)$item->menu_description,
            ];
        })->values();
    }

    private function kitchenItemName(string $menuName, string $category): string
    {
        $prefix = $this->kitchenCategoryPrefix($menuName, $category);
        if ($prefix === '' || str_starts_with($menuName, $prefix.' - ')) {
            return $menuName;
        }

        return $prefix.' - '.$menuName;
    }

    private function kitchenCategoryPrefix(string $menuName, string $category): string
    {
        if (preg_match('/^K\d{2}\./', $menuName)) {
            return 'Sushi';
        }

        if (preg_match('/^M\d{2}\./', $menuName)) {
            return 'California/Maki';
        }

        if (preg_match('/^N\d{2}\./', $menuName)) {
            return 'Suggestion';
        }

        return match ($category) {
            'Sushi minute' => 'Sushi',
            'California / Maki - 6 pieces' => 'California/Maki',
            'Suggestions du mois' => 'Suggestion',
            default => '',
        };
    }

    private function clientOrderPayload(TableOrder $order): array
    {
        $status = (string)$order->print_status;
        $submittedAt = $order->submitted_at ?: $order->created_at;
        $waitingSeconds = $submittedAt ? (int)$submittedAt->diffInSeconds(now()) : 0;
        $needsHelp = in_array($status, ['failed'], true)
            || (in_array($status, ['pending', 'printing'], true) && $waitingSeconds >= 90);

        return [
            'reference' => $order->reference,
            'table' => $order->table_name,
            'print_status' => $status,
            'status_label' => $order->print_status_label,
            'needs_help' => $needsHelp,
            'waiting_seconds' => $waitingSeconds,
            'printed_at' => $order->printed_at?->format('H:i'),
            'items' => $order->items->map(fn($item) => [
                'quantity' => (int)$item->quantity,
                'name' => (string)$item->menu_name,
            ])->values(),
        ];
    }

    private function validAccessSession(Request $request): ?array
    {
        $session = (array)$request->session()->get('restoline_table_ordering', []);
        if (($session['validated'] ?? false) !== true || empty($session['code_id']) || empty($session['date'])) {
            return null;
        }

        if ((string)$session['date'] !== now()->toDateString()) {
            return null;
        }

        $code = TableAccessCode::query()
            ->whereKey($session['code_id'])
            ->whereDate('valid_date', now()->toDateString())
            ->where('status', true)
            ->first();

        if (!$code) {
            return null;
        }

        return [
            'validated' => true,
            'table' => (string)$code->table_name,
            'table_id' => $code->dining_table_id,
            'code_id' => $code->getKey(),
            'date' => $code->valid_date->toDateString(),
            'validated_at' => $session['validated_at'] ?? now()->toIso8601String(),
        ];
    }

    private function mergeOrderingSession(Request $request, array $values): void
    {
        $session = (array)$request->session()->get('restoline_table_ordering', []);
        $request->session()->put('restoline_table_ordering', array_merge($session, $values));
    }

    private function rememberClientOrder(Request $request, string $reference): void
    {
        $session = (array)$request->session()->get('restoline_table_ordering', []);
        $references = collect($session['order_refs'] ?? [])
            ->push($reference)
            ->filter()
            ->unique()
            ->take(-12)
            ->values()
            ->all();

        $this->mergeOrderingSession($request, ['order_refs' => $references]);
    }

    private function clientOrders(Request $request)
    {
        $session = (array)$request->session()->get('restoline_table_ordering', []);
        $references = collect($session['order_refs'] ?? [])
            ->push($session['last_order']['reference'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($references === []) {
            return collect();
        }

        return TableOrder::with('items')
            ->whereIn('reference', $references)
            ->whereDate('valid_date', now()->toDateString())
            ->orderByDesc('submitted_at')
            ->get();
    }

    private function accessRateKey(Request $request, string $table): string
    {
        return 'restoline:table-ordering:access:'
            .sha1($request->ip().'|'.strtoupper(trim($table)));
    }
}
