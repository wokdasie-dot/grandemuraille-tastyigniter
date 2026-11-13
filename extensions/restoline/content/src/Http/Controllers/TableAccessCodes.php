<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Restoline\Content\Models\TableAccessCode;
use Restoline\Content\Models\TableOrderingSetting;
use Restoline\Content\Services\TableAccessCodeService;

class TableAccessCodes extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => TableAccessCode::class,
            'title' => 'Codes QR des tables',
            'emptyMessage' => 'Aucun code généré pour les tables.',
            'defaultSort' => ['valid_date', 'DESC'],
            'configFile' => 'tableaccesscode',
        ],
    ];

    public array $formConfig = [
        'name' => 'Code de table',
        'model' => TableAccessCode::class,
        'create' => [
            'title' => 'Créer un code de table',
            'redirect' => 'restoline/content/table_access_codes/edit/{code_id}',
            'redirectClose' => 'restoline/content/table_access_codes',
        ],
        'edit' => [
            'title' => 'Modifier le code de table',
            'redirect' => 'restoline/content/table_access_codes/edit/{code_id}',
            'redirectClose' => 'restoline/content/table_access_codes',
        ],
        'preview' => [
            'title' => 'Aperçu du code de table',
            'back' => 'restoline/content/table_access_codes',
        ],
        'delete' => [
            'redirect' => 'restoline/content/table_access_codes',
        ],
        'configFile' => 'tableaccesscode',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.TableOrdering';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-table-ordering');
    }


    public function listExtendQuery(Builder $query): void
    {
        $this->deleteOldCodes();
        $query->whereDate('valid_date', now()->toDateString());
    }

    protected function deleteOldCodes(): void
    {
        TableAccessCode::query()
            ->whereDate('valid_date', '<', now()->toDateString())
            ->delete();
    }

    public function onGenerateToday(): void
    {
        $this->deleteOldCodes();
        $count = app(TableAccessCodeService::class)->generateForDate(now(), false);
        flash()->success(sprintf('%d codes du jour générés. Les codes existants sont conservés.', $count))->now();
    }

    public function onRegenerateToday(): void
    {
        $this->deleteOldCodes();
        $count = app(TableAccessCodeService::class)->generateForDate(now(), true);
        flash()->success(sprintf('%d codes du jour régénérés.', $count))->now();
    }

    public function onRegenerateCode(): void
    {
        $record = TableAccessCode::findOrFail((int)post('code_id'));
        app(TableAccessCodeService::class)->regenerate($record);
        flash()->success(sprintf('Nouveau code pour la table %s : %s', $record->table_name, $record->code))->now();
    }

    public function printToday()
    {
        $this->deleteOldCodes();
        app(TableAccessCodeService::class)->generateForDate(now(), false);

        $codes = TableAccessCode::query()
            ->whereDate('valid_date', now()->toDateString())
            ->where('status', true)
            ->get()
            ->sortBy(fn(TableAccessCode $code): string => $this->naturalTableSortKey((string)$code->table_name))
            ->values();

        return $this->makeView('print_today', [
            'codes' => $codes,
            'validDateLabel' => now()->format('d/m/Y'),
            'qrUrl' => url('/commander'),
            'qrImageUrl' => url('/storage/media/uploads/qr-codes/wokdasie-qr-commande-table.svg'),
            'codesUrl' => admin_url('restoline/content/table_access_codes'),
        ]);
    }

    public function print_today()
    {
        return $this->printToday();
    }

    protected function naturalTableSortKey(string $tableName): string
    {
        if (preg_match('/^(\d+)([A-Z]*)$/i', trim($tableName), $matches)) {
            return str_pad($matches[1], 4, '0', STR_PAD_LEFT).strtoupper($matches[2]);
        }

        return strtoupper($tableName);
    }

    public function stocks()
    {
        $this->pageTitle = 'Stocks des plats QR';

        $settings = TableOrderingSetting::current();
        $categoryIds = $settings->qrCategoryIds();

        $rows = collect();

        if ($categoryIds !== []) {
            $rows = DB::table('menus as m')
                ->join('menu_categories as mc', 'mc.menu_id', '=', 'm.menu_id')
                ->join('categories as c', 'c.category_id', '=', 'mc.category_id')
                ->leftJoin('stocks as s', function($join) {
                    $join->on('s.stockable_id', '=', 'm.menu_id')
                        ->where('s.stockable_type', '=', 'menus')
                        ->where('s.location_id', '=', 1);
                })
                ->whereIn('c.category_id', $categoryIds)
                ->where('m.menu_status', 1)
                ->select([
                    'm.menu_id',
                    'm.menu_name',
                    'm.menu_price',
                    'c.name as category_name',
                    'c.priority as category_priority',
                    'm.menu_priority',
                    's.quantity',
                    's.is_tracked',
                    's.low_stock_threshold',
                    's.out_of_stock_type',
                    's.out_of_stock_until',
                ])
                ->orderBy('c.priority')
                ->orderBy('c.name')
                ->orderBy('m.menu_priority')
                ->orderBy('m.menu_name')
                ->get()
                ->unique('menu_id')
                ->map(function($row) {
                    $row->stock_status = $this->stockStatusLabel($row);
                    $row->stock_badge_class = $this->stockBadgeClass($row);
                    $row->edit_url = admin_url('menus/edit/'.$row->menu_id);

                    return $row;
                })
                ->values();
        }

        return $this->makeView('stocks', [
            'rows' => $rows,
            'categoryCount' => count($categoryIds),
            'settingsUrl' => admin_url('restoline/content/table_ordering_settings'),
            'codesUrl' => admin_url('restoline/content/table_access_codes'),
        ]);
    }

    protected function stockStatusLabel(object $row): string
    {
        if ((int)($row->is_tracked ?? 0) !== 1) {
            return 'Stock non suivi';
        }

        if ($this->hasOutOfStockOverride($row)) {
            return 'Indisponible';
        }

        $quantity = (int)($row->quantity ?? 0);
        if ($quantity <= 0) {
            return 'Rupture';
        }

        $threshold = $row->low_stock_threshold !== null ? (int)$row->low_stock_threshold : null;
        if ($threshold !== null && $threshold > 0 && $quantity <= $threshold) {
            return 'Stock bas : '.$quantity;
        }

        return 'Disponible : '.$quantity;
    }

    protected function stockBadgeClass(object $row): string
    {
        if ((int)($row->is_tracked ?? 0) !== 1) {
            return 'badge-secondary';
        }

        if ($this->hasOutOfStockOverride($row) || (int)($row->quantity ?? 0) <= 0) {
            return 'badge-danger';
        }

        $threshold = $row->low_stock_threshold !== null ? (int)$row->low_stock_threshold : null;
        if ($threshold !== null && $threshold > 0 && (int)$row->quantity <= $threshold) {
            return 'badge-warning';
        }

        return 'badge-success';
    }

    protected function hasOutOfStockOverride(object $row): bool
    {
        if (($row->out_of_stock_type ?? null) === 'indefinitely') {
            return true;
        }

        return filled($row->out_of_stock_until) && now()->lessThan((string)$row->out_of_stock_until);
    }
}
