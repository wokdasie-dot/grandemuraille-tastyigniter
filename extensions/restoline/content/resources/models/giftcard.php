<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => ['prompt' => 'Rechercher un code, un bénéficiaire ou un acheteur', 'mode' => 'all'],
    'scopes' => ['status' => ['label' => 'Actif', 'type' => 'switch', 'conditions' => 'status = :filtered']],
];

$config['list']['toolbar'] = ['buttons' => [
    'create' => ['label' => 'Créer un bon cadeau', 'class' => 'btn btn-primary', 'href' => admin_url('restoline/content/gift_cards/create')],
    'redeem' => ['label' => 'Utiliser un bon cadeau', 'class' => 'btn btn-default', 'href' => admin_url('restoline/content/gift_cards/redeem')],
]];

$config['list']['columns'] = [
    'edit' => ['type' => 'button', 'iconCssClass' => 'fa fa-pencil', 'attributes' => ['class' => 'btn btn-edit', 'href' => admin_url('restoline/content/gift_cards/edit/{gift_card_id}')]],
    'code' => ['label' => 'Code', 'type' => 'text', 'searchable' => true],
    'recipient_name' => ['label' => 'Bénéficiaire', 'type' => 'text', 'searchable' => true],
    'formatted_balance' => ['label' => 'Solde', 'type' => 'text'],
    'status_label' => ['label' => 'État', 'type' => 'text'],
    'expires_at' => ['label' => 'Expiration', 'type' => 'date'],
    'emailed_at' => ['label' => 'Email envoyé', 'type' => 'datetime'],
    'print' => ['type' => 'button', 'iconCssClass' => 'fa fa-print', 'attributes' => ['class' => 'btn btn-light', 'href' => admin_url('restoline/content/gift_cards/print?gift_card_id={gift_card_id}'), 'target' => '_blank']],
    'email' => ['type' => 'button', 'iconCssClass' => 'fa fa-envelope', 'attributes' => ['class' => 'btn btn-light', 'data-request' => 'onSendGiftCardEmail', 'data-request-data' => 'gift_card_id: {gift_card_id}', 'data-request-confirm' => 'Envoyer le bon cadeau par email ?']],
];

$config['form']['toolbar'] = ['buttons' => [
    'save' => ['label' => 'Enregistrer', 'context' => ['create', 'edit'], 'partial' => 'form/toolbar_save_button', 'class' => 'btn btn-primary', 'data-request' => 'onSave'],
]];

$config['form']['fields'] = [
    'initial_amount' => ['label' => 'Montant initial', 'type' => 'currency', 'span' => 'left'],
    'expires_at' => ['label' => 'Date d’expiration', 'type' => 'datepicker', 'mode' => 'date', 'span' => 'right'],
    'recipient_name' => ['label' => 'Nom du bénéficiaire', 'type' => 'text', 'span' => 'left'],
    'recipient_email' => ['label' => 'Email du bénéficiaire', 'type' => 'text', 'span' => 'right', 'comment' => 'Le bon est envoyé automatiquement après sa création.'],
    'purchaser_name' => ['label' => 'Nom de l’acheteur', 'type' => 'text', 'span' => 'left'],
    'status' => ['label' => 'Actif', 'type' => 'switch', 'default' => true, 'span' => 'right'],
    'note' => ['label' => 'Note interne', 'type' => 'textarea'],
];

$config['form']['tabs']['fields'] = [
    'code' => ['label' => 'Code du bon', 'type' => 'text', 'tab' => 'Validation', 'disabled' => true],
    'formatted_balance' => ['label' => 'Solde actuel', 'type' => 'text', 'tab' => 'Validation', 'disabled' => true],
    'qr_token' => ['label' => 'Jeton QR', 'type' => 'text', 'tab' => 'Validation', 'disabled' => true],
];

return $config;
