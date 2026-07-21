<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\readModels;

use Besnovatyj\RouteAlias\entities\RouteAlias;

/**
 * Read-репозиторий алиасов — источник данных для карт маршрутизации.
 */
class RouteAliasReadRepository
{
    /**
     * Все активные алиасы. Набор небольшой (короткие URL — штучные), поэтому грузится целиком
     * и кэшируется вызывающей стороной ({@see \Besnovatyj\RouteAlias\services\AliasMapProvider}).
     *
     * @return RouteAlias[]
     */
    public function activeAll(): array
    {
        return RouteAlias::find()
            ->where(['status' => RouteAlias::STATUS_ACTIVE])
            ->all();
    }
}
