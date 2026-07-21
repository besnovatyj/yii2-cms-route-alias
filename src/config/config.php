<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

return [
    'id' => 'RouteAlias',
    'params' => [
        'iconClass' => 'bi bi-signpost-split',

        // Префиксы (первый сегмент пути), которые НЕЛЬЗЯ занимать коротким алиасом, чтобы не затенять
        // системные/модульные маршруты. Расширяется под конкретный проект. Проверяется в RouteAliasForm.
        'reservedPrefixes' => [
            'admin', 'backend', 'assets', 'static', 'csp-report',
        ],

        // Интеграция с модулем очистки (ClearManager): сброс кэша карт маршрутизации алиасов.
        // Читается EndpointCollectorService, если ClearManager установлен; иначе параметры инертны
        // (жёсткой зависимости нет). См. controllers/backend/ClearController.
        'endpoints' => [
            'clear' => [
                'maps' => [
                    'rowTitle' => 'Кэш карт маршрутизации URL-алиасов',
                    'getData' => '/RouteAlias/backend/clear/get-data',
                    'clear' => '/RouteAlias/backend/clear/clear-data',
                ],
            ],
        ],
    ],
];
