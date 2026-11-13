<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Codes QR tables du <?= e($validDateLabel); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
        }

        .actions {
            display: flex;
            gap: 10px;
            padding: 14px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border: 1px solid #bbb;
            border-radius: 6px;
            color: #111;
            text-decoration: none;
            background: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            border-color: #17694f;
            color: #fff;
            background: #17694f;
        }

        .sheet {
            width: 204mm;
            min-height: auto;
            margin: 12px auto;
            padding: 2mm;
            background: #fff;
            box-shadow: 0 6px 30px rgba(0, 0, 0, .12);
            overflow: hidden;
        }

        .header {
            display: none;
        }

        .print-date {
            margin: 0 0 1mm;
            font-size: 10pt;
            line-height: 1;
            color: #333;
            font-weight: 700;
        }

        h1 {
            margin: 0;
            font-size: 10pt;
            line-height: 1.05;
        }

        .subtitle {
            font-size: 6pt;
            color: #333;
        }

        .qr-box {
            text-align: center;
            font-size: 0;
            line-height: 1.2;
        }

        .qr-box img {
            display: block;
            width: 14mm;
            height: 14mm;
            margin: 0 auto;
        }

        .codes-table {
            width: 100%;
            margin-top: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .codes-table th,
        .codes-table td {
            height: 6.1mm;
            padding: 0 1mm;
            border: .5px solid #777;
            text-align: center;
            vertical-align: middle;
            break-inside: avoid;
            overflow: hidden;
        }

        .codes-table th {
            height: 5.5mm;
            font-size: 10pt;
            font-weight: 700;
            background: #f3f3f3;
        }

        .table-cell {
            width: 24%;
            font-size: 12pt;
            font-weight: 700;
            line-height: 1.05;
        }

        .code-cell {
            width: 26%;
            font-size: 19pt;
            line-height: 1;
            font-weight: 400;
            letter-spacing: .02em;
        }

        .empty {
            margin-top: 20mm;
            text-align: center;
            font-size: 16pt;
            color: #555;
        }

        .footer {
            display: none;
        }

        @media print {
            html,
            body {
                width: 204mm;
                height: 291mm;
                overflow: hidden;
            }

            body {
                background: #fff;
            }

            .actions {
                display: none;
            }

            .sheet {
                width: 204mm;
                max-height: 291mm;
                margin: 0;
                padding: 0;
                box-shadow: none;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer la page A4</button>
        <a class="btn" href="<?= e($codesUrl); ?>">Retour aux codes QR</a>
    </div>

    <main class="sheet">
        <div class="print-date">Date : <?= e($validDateLabel); ?></div>

        <header class="header">
            <div>
                <h1>Codes QR tables - <?= e($validDateLabel); ?></h1>
                <div class="subtitle">
                    QR commande a table
                </div>
            </div>
            <div class="qr-box">
                <img src="<?= e($qrImageUrl); ?>" alt="QR code commande a table">
            </div>
        </header>

        <?php if ($codes->isEmpty()): ?>
            <div class="empty">Aucun code actif pour aujourd'hui.</div>
        <?php else: ?>
            <?php
                $codesList = $codes->values();
                $leftCount = (int)ceil($codesList->count() / 2);
                $leftCodes = $codesList->slice(0, $leftCount)->values();
                $rightCodes = $codesList->slice($leftCount)->values();
            ?>
            <table class="codes-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Code</th>
                        <th>Table</th>
                        <th>Code</th>
                    </tr>
                </thead>
                <tbody>
                <?php for ($index = 0; $index < $leftCount; $index++): ?>
                    <?php
                        $leftCode = $leftCodes->get($index);
                        $rightCode = $rightCodes->get($index);
                    ?>
                    <tr>
                        <td class="table-cell"><?= $leftCode ? e($leftCode->table_name) : ''; ?></td>
                        <td class="code-cell"><?= $leftCode ? e($leftCode->code) : ''; ?></td>
                        <td class="table-cell"><?= $rightCode ? e($rightCode->table_name) : ''; ?></td>
                        <td class="code-cell"><?= $rightCode ? e($rightCode->code) : ''; ?></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <footer class="footer">
            Fiche interne restaurant.
        </footer>
    </main>
</body>
</html>
