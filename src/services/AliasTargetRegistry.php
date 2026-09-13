<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\services;

use Besnovatyj\Contracts\routing\AliasTarget;
use Besnovatyj\Contracts\routing\AliasTargetProvider;
use Besnovatyj\Kernel\module\ModuleFinder;
use Besnovatyj\RouteAlias\entities\RouteAlias;

/**
 * Находит модули, объявляющие алиасуемые цели ({@see AliasTargetProvider}), и агрегирует их для админки.
 *
 * Discovery — через перебор зарегистрированных модулей приложения по контракту ({@see ModuleFinder}):
 * инстанцируются только модули-провайдеры. Работает только на бэкенде (экран создания/редактирования
 * алиаса), не на горячем пути маршрутизации. Связанность нулевая: провайдеры не знают об этом модуле.
 */
final class AliasTargetRegistry
{
    /** @var array<string,AliasTargetProvider>|null */
    private ?array $providers = null;

    /**
     * Провайдеры целей, ключ — id модуля.
     *
     * @return array<string,AliasTargetProvider>
     */
    public function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        return $this->providers = ModuleFinder::implementing(AliasTargetProvider::class);
    }

    /**
     * Данные для каскада админки: модуль → цели → доступные slug.
     *
     * @return array<string,array{label:string,targets:list<array{route:string,label:string,slugParam:string}>,slugs:array<string,array<string,string>>}>
     */
    public function catalog(): array
    {
        $catalog = [];
        foreach ($this->providers() as $moduleId => $provider) {
            $targets = [];
            $slugs = [];
            foreach ($provider->aliasTargets() as $target) {
                $route = RouteAlias::normalizeRoute($target->route);
                $targets[] = ['route' => $route, 'label' => $target->label, 'slugParam' => $target->slugParam];
                $slugs[$route] = $provider->aliasSlugs($target->route);
            }
            if ($targets !== []) {
                $catalog[$moduleId] = ['label' => $moduleId, 'targets' => $targets, 'slugs' => $slugs];
            }
        }
        return $catalog;
    }

    /**
     * Найти цель по (нормализованному) роуту среди всех провайдеров — чтобы узнать её `slugParam`.
     */
    public function findTarget(string $route): ?AliasTarget
    {
        $route = RouteAlias::normalizeRoute($route);
        foreach ($this->providers() as $provider) {
            foreach ($provider->aliasTargets() as $target) {
                if (RouteAlias::normalizeRoute($target->route) === $route) {
                    return $target;
                }
            }
        }
        return null;
    }

    /**
     * Список известных (объявленных провайдерами) роутов-целей в нормализованном виде.
     *
     * @return list<string>
     */
    public function knownRoutes(): array
    {
        $routes = [];
        foreach ($this->providers() as $provider) {
            foreach ($provider->aliasTargets() as $target) {
                $routes[] = RouteAlias::normalizeRoute($target->route);
            }
        }
        return array_values(array_unique($routes));
    }
}
