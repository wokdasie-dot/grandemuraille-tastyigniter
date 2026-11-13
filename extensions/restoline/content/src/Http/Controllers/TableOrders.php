<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Illuminate\Database\Eloquent\Builder;
use Restoline\Content\Models\TableOrder;

class TableOrders extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => TableOrder::class,
            'title' => 'Commandes QR à table',
            'emptyMessage' => 'Aucune commande QR reçue.',
            // Les tickets déjà imprimés sont classés par heure d'impression.
            // Pour une commande encore en attente, submitted_at sert de repli.
            'defaultSort' => ['printed_at', 'DESC'],
            'showSorting' => false,
            'configFile' => 'tableorder',
        ],
    ];

    public array $formConfig = [
        'name' => 'Commande QR',
        'model' => TableOrder::class,
        'preview' => [
            'title' => 'Détail commande QR',
            'back' => 'restoline/content/table_orders',
        ],
        'edit' => [
            'title' => 'Modifier commande QR',
            'redirect' => 'restoline/content/table_orders/edit/{order_id}',
            'redirectClose' => 'restoline/content/table_orders',
        ],
        'configFile' => 'tableorder',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.TableOrdering';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-table-ordering');
    }

    public function listExtendQuery(Builder $query): void
    {
        $query->reorder()
            ->orderByRaw('COALESCE(printed_at, submitted_at) DESC')
            ->orderByDesc('order_id');
    }

    public function onMarkPrinted(): void
    {
        $record = TableOrder::findOrFail((int)post('order_id'));
        $record->print_status = 'printed';
        $record->printed_at = now();
        $record->save();
        flash()->success('Commande marquée comme imprimée.')->now();
    }
}
