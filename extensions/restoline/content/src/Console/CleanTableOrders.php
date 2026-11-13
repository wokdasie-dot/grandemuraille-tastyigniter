<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Models\TableOrder;

class CleanTableOrders extends Command
{
    protected $signature = 'restoline:clean-table-orders
        {--days=90 : Nombre de jours de conservation}
        {--dry-run : Afficher le nombre sans supprimer}';

    protected $description = 'Supprime les anciennes commandes QR a table pour garder la base legere.';

    public function handle(): int
    {
        $days = max(7, (int)$this->option('days'));
        $limit = now()->subDays($days);

        $query = TableOrder::query()
            ->where(function($query) use ($limit): void {
                $query->where('submitted_at', '<', $limit)
                    ->orWhere(function($query) use ($limit): void {
                        $query->whereNull('submitted_at')
                            ->where('created_at', '<', $limit);
                    });
            });

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info(sprintf('%d commande(s) QR plus ancienne(s) que %d jours seraient supprimees.', $count, $days));

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->orderBy('order_id')->chunkById(100, function($orders) use (&$deleted): void {
            foreach ($orders as $order) {
                $order->delete();
                $deleted++;
            }
        }, 'order_id');

        $this->info(sprintf('%d commande(s) QR supprimee(s). Conservation : %d jours.', $deleted, $days));

        return self::SUCCESS;
    }
}
