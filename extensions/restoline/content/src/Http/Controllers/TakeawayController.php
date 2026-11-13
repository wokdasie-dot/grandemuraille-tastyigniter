<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Cart\Classes\CartManager;
use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\Local\Facades\Location as LocationFacade;
use Igniter\Local\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class TakeawayController extends Controller
{
    public function index(): View
    {
        $location = $this->activateCollection();
        $cart = app(CartManager::class)->cartInstance($location->getKey());
        $categories = Category::query()
            ->where('name', 'like', 'Emporter | %')
            ->whereIsEnabled()
            ->orderBy('priority')
            ->with(['menus' => function ($query): void {
                $query->whereIsEnabled()
                    ->where('order_restriction', 'like', '%"collection"%')
                    ->with(['menu_options.menu_option_values.option_value'])
                    ->orderBy('menu_priority');
            }])
            ->get()
            ->filter(fn(Category $category) => $category->menus->isNotEmpty());

        return view('restoline.content::takeaway.index', [
            'categories' => $categories,
            'cartItems' => $cart->content(),
            'cartTotal' => $cart->total(),
            'collectionOpen' => LocationFacade::checkOrderTime(null, 'collection'),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $location = $this->activateCollection();
        $data = $request->validate([
            'menu_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'menu_options' => ['nullable', 'array'],
            'menu_options.*.option_values' => ['nullable', 'array'],
        ]);
        $menu = Menu::query()
            ->whereIsEnabled()
            ->where('order_restriction', 'like', '%"collection"%')
            ->whereHas('categories', fn($query) => $query->where('name', 'like', 'Emporter | %'))
            ->findOrFail($data['menu_id']);

        try {
            $cartManager = app(CartManager::class);
            $cartManager->cartInstance($location->getKey());
            $cartManager->addCartItem($menu->getKey(), [
                'quantity' => $data['quantity'] ?? 1,
                'menu_options' => $data['menu_options'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->route('restoline.takeaway.index')
                ->with('takeaway_error', 'La commande ne peut pas etre ajoutee pour le moment. Verifiez les horaires de retrait.');
        }

        return redirect()->route('restoline.takeaway.index')->with('takeaway_success', 'Plat ajoute au panier.');
    }

    public function updateCart(Request $request, string $rowId): RedirectResponse
    {
        $location = $this->activateCollection();
        $data = $request->validate([
            'action' => ['required', 'in:plus,minus,remove'],
        ]);

        try {
            $cartManager = app(CartManager::class);
            $cartManager->cartInstance($location->getKey());

            if ($data['action'] === 'remove') {
                $cartManager->removeCartItem($rowId);
                $message = 'Plat retire du panier.';
            } else {
                $cartManager->updateCartItemQty($rowId, $data['action']);
                $message = $data['action'] === 'plus'
                    ? 'Quantite augmentee.'
                    : 'Quantite diminuee.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->route('restoline.takeaway.index')
                ->with('takeaway_error', 'Le panier ne peut pas etre modifie pour le moment.');
        }

        return redirect()->route('restoline.takeaway.index')->with('takeaway_success', $message);
    }

    private function activateCollection(): Location
    {
        $location = Location::getDefault() ?: Location::query()->whereIsEnabled()->firstOrFail();
        LocationFacade::setCurrent($location);
        LocationFacade::updateOrderType(Location::COLLECTION);
        LocationFacade::asapScheduleTimeslot();

        return $location;
    }
}
