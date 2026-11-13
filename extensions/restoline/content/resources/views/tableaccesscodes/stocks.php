<div class="row-fluid">
    <div class="span12">
        <div class="toolbar-widget">
            <div class="toolbar">
                <a class="btn btn-light" href="<?= e($codesUrl); ?>">Retour aux codes QR</a>
                <a class="btn btn-light" href="<?= e($settingsUrl); ?>">Choisir les catégories QR</a>
            </div>
        </div>

        <div class="callout callout-info">
            Cette page affiche uniquement les plats visibles dans la commande QR. Pour limiter un plat du mois, ouvrez le plat puis activez le suivi du stock.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Stocks des plats QR
    </div>

    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
            <tr>
                <th>Plat</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows->isEmpty()): ?>
                <tr>
                    <td colspan="5" class="text-muted">
                        Aucun plat QR trouvé. Vérifiez les catégories sélectionnées dans les horaires QR.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row->menu_name); ?></td>
                    <td><?= e($row->category_name); ?></td>
                    <td><?= e(number_format((float)$row->menu_price, 2, ',', ' ')); ?> €</td>
                    <td>
                        <span class="badge <?= e($row->stock_badge_class); ?>">
                            <?= e($row->stock_status); ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <a class="btn btn-light btn-sm" href="<?= e($row->edit_url); ?>">Modifier le plat</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
