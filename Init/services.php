<?php

declare(strict_types=1);

use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Modules\Sviat\OrderManagerControl\Backend\Controllers\OrderManagerControlAdmin;
use Okay\Modules\Sviat\OrderManagerControl\Extenders\BackendExtender;
use Okay\Modules\Sviat\OrderManagerControl\Helpers\OrderManagerControlHelper;

return [
    OrderManagerControlHelper::class => [
        'class' => OrderManagerControlHelper::class,
        'arguments' => [
            new SR(EntityFactory::class),
        ],
    ],
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(OrderManagerControlHelper::class),
            new SR(Design::class),
        ],
    ],
    OrderManagerControlAdmin::class => [
        'class' => OrderManagerControlAdmin::class,
    ],
];
