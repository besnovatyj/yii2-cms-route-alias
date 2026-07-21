<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

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
                [
                    'location' => 'left-sidebar',
                    'group' => 'Routing',
                    'groupIcon' => 'bi bi-signpost-2',
                    'priority' => 100,
                    'groupPriority' => 80,
                ],
            ],
        ],
    ],
];
