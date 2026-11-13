<!doctype html>
<html lang="fr-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $this->page->title ?? "La Grande Muraille de Chine - Click & Collect" }}</title>
    <meta name="description" content="Commande demo Restoline pour La Grande Muraille de Chine a Hornu pres de Mons : wok, teppanyaki, nouilles, riz et specialites asiatiques en Click & Collect.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    @themeStyles
</head>
<body>
    @themePartial('site.header')

    <main class="rb-main">
        @themePage
    </main>

    @themePartial('site.footer')

    <div class="rb-mobile-cart">
        <span>Panier demo</span>
        <strong>Voir la commande</strong>
    </div>

    <script src="assets/js/app.js" defer></script>
    @themeScripts
</body>
</html>
