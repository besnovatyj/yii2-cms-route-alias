<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Contracts\adminMenu\AdminMenuLocation;
use Besnovatyj\Contracts\adminMenu\AdminMenuPlacement;

return [
    [
        'label' => 'URL Aliases',
        'iconClass' => 'bi bi-signpost-split me-1',
        'url' => ['/RouteAlias/backend/default/index'],
        'active' => static function () {
            return str_contains(Yii::$app->request->url, '/RouteAlias/backend/default');
        },
        '_meta' => [
            'placements' => [
                new AdminMenuPlacement(
                    location: AdminMenuLocation::RightSidebar,
                    group: 'Service',
                    groupIcon: 'bi bi-sliders',
                    groupPriority: 100,
                    priority: 100,
                ),
            ],
        ],
    ],
];
