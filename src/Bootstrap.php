<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\services\AliasMapProvider;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;

/**
 * Bootstrap модуля алиасов: сброс кэша карт маршрутизации при изменении алиасов.
 *
 * {@see AliasMapProvider} держит прямую/обратную карты в кэше с тегом `route_aliases`. Правки алиасов
 * идут из бэкенда обычным AR `->save()/->delete()`, поэтому ловим AR-события `RouteAlias` и сбрасываем
 * тег целиком («крупный помол» — набор мал, точечная инвалидация не нужна).
 *
 * Bootstrap глобальный (L2, гейт modman): выполняется во всех приложениях; кэш `apcu` общий, поэтому
 * фронт немедленно получает свежие карты после правки из админки.
 */
final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $invalidate = static function (): void {
            if (Yii::$app->has('cache') && Yii::$app->cache !== null) {
                TagDependency::invalidate(Yii::$app->cache, [AliasMapProvider::CACHE_TAG]);
            }
        };

        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_INSERT, $invalidate);
        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_UPDATE, $invalidate);
        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_DELETE, $invalidate);
    }
}
