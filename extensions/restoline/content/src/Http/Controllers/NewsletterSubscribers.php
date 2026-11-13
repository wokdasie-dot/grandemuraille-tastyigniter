<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\NewsletterSubscriber;

class NewsletterSubscribers extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => NewsletterSubscriber::class,
            'title' => 'Inscrits newsletter',
            'emptyMessage' => 'Aucun inscrit.',
            'defaultSort' => ['created_at', 'DESC'],
            'configFile' => 'newslettersubscriber',
        ],
    ];

    public array $formConfig = [
        'name' => 'Inscrit newsletter',
        'model' => NewsletterSubscriber::class,
        'create' => [
            'title' => 'Créer un inscrit',
            'redirect' => 'restoline/content/newsletter_subscribers/edit/{subscriber_id}',
            'redirectClose' => 'restoline/content/newsletter_subscribers',
        ],
        'edit' => [
            'title' => 'Modifier un inscrit',
            'redirect' => 'restoline/content/newsletter_subscribers/edit/{subscriber_id}',
            'redirectClose' => 'restoline/content/newsletter_subscribers',
        ],
        'delete' => [
            'redirect' => 'restoline/content/newsletter_subscribers',
        ],
        'configFile' => 'newslettersubscriber',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }
}
