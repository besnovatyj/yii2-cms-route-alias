<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\services;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\readModels\RouteAliasReadRepository;
use Yii;
use yii\caching\TagDependency;

/**
 * Строит и кэширует карты сопоставления алиасов для {@see \Besnovatyj\RouteAlias\urls\RouteAliasUrlRule}.
 *
 * Набор алиасов небольшой, поэтому грузится целиком одним элементом кэша (APCu) с тегом
 * {@see self::CACHE_TAG}; инвалидация — в {@see \Besnovatyj\RouteAlias\Bootstrap} на AR-событиях.
 * На горячем пути парсинга/генерации URL — только array-лукапы, запросов к БД нет (пока кэш валиден).
 *
 * Формат карт:
 *  - forward: `path => ['route' => 'Page/page/view', 'params' => ['slug' => 'about']]`
 *  - reverse: `route => [ ['params' => [...], 'path' => '...'], ... ]` (несколько алиасов на роут)
 */
final class AliasMapProvider
{
    public const string CACHE_KEY = 'route_alias.maps';
    public const string CACHE_TAG = 'route_aliases';

    private RouteAliasReadRepository $repository;

    public function __construct(RouteAliasReadRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array{forward: array<string,array{route:string,params:array}>, reverse: array<string,list<array{params:array,path:string}>>}
     */
    public function maps(): array
    {
        // Без кэша (напр. кэш не сконфигурирован) строим напрямую — корректность важнее скорости.
        if (!Yii::$app->has('cache') || Yii::$app->cache === null) {
            return $this->build();
        }

        return Yii::$app->cache->getOrSet(
            self::CACHE_KEY,
            fn (): array => $this->build(),
            null,
            new TagDependency(['tags' => [self::CACHE_TAG]]),
        );
    }

    /**
     * @return array{forward: array, reverse: array}
     */
    private function build(): array
    {
        $forward = [];
        $reverse = [];

        foreach ($this->repository->activeAll() as $alias) {
            /** @var RouteAlias $alias */
            $params = $alias->getParams();
            $forward[$alias->path] = ['route' => $alias->route, 'params' => $params];
            $reverse[$alias->route][] = ['params' => $params, 'path' => $alias->path];
        }

        return ['forward' => $forward, 'reverse' => $reverse];
    }
}
