<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\services;

use Yii;
use yii\caching\TagDependency;

/**
 * Интеграция с модулем очистки (ClearManager): сброс кэша карт маршрутизации алиасов.
 *
 * У модуля нет файлового кеша — «очищаемый ресурс» это кэш прямой/обратной карт алиасов
 * ({@see AliasMapProvider}, тег {@see AliasMapProvider::CACHE_TAG}). Сброс форсирует пересборку карт
 * из БД при следующем обращении. Формат методов повторяет {@see \Besnovatyj\Blog\services\BlogCacheClearService}
 * (строка для `getData`, bool для `clearData`).
 */
final class AliasCacheClearService
{
    private AliasMapProvider $maps;

    public function __construct(AliasMapProvider $maps)
    {
        $this->maps = $maps;
    }

    /**
     * Человекочитаемое состояние кэша: сколько активных алиасов сейчас в картах маршрутизации.
     */
    public function getData(): string
    {
        $count = count($this->maps->maps()['forward']);
        return $count . ' ' . $this->pluralAliases($count) . ' (кэш карт маршрутизации)';
    }

    /**
     * Сбрасывает кэш карт алиасов. Пустой/несконфигурированный кэш — не ошибка.
     */
    public function clearData(): bool
    {
        if (Yii::$app->has('cache') && Yii::$app->cache !== null) {
            TagDependency::invalidate(Yii::$app->cache, [AliasMapProvider::CACHE_TAG]);
        }
        return true;
    }

    private function pluralAliases(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'активный алиас';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 'активных алиаса';
        }
        return 'активных алиасов';
    }
}
