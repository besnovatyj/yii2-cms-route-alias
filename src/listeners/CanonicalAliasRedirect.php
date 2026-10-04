<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\listeners;

use Besnovatyj\RouteAlias\urls\RouteAliasUrlRule;
use Yii;
use yii\base\ActionEvent;
use yii\web\Request;

/**
 * 301-редирект на короткий URL, если страница с алиасом открыта по другому адресу.
 *
 * Правило алиасов отвечает только за свой путь; остальные адреса той же страницы продолжают
 * разбираться — правило модуля (`/page/home`), разбор по умолчанию (`/Page/page/view?slug=home`),
 * другой регистр или хвостовой слэш (`/PRICE`, `/price/`). Без редиректа это дубли контента.
 *
 * Работает на `Application::EVENT_BEFORE_ACTION` только во фронтенде (подписка — в
 * {@see \Besnovatyj\RouteAlias\Bootstrap}). К этому моменту параметры роута уже влиты в query-параметры
 * запроса ({@see Request::resolve()}), поэтому «роут + параметры» действия известны без биндинга.
 * Канонический путь берётся у того же {@see RouteAliasUrlRule} — единый источник; для роутов без алиаса
 * правило возвращает `false` и редиректа нет. Стоимость — один array-лукап по картам из кэша.
 */
final class CanonicalAliasRedirect
{
    private RouteAliasUrlRule $rule;

    public function __construct(RouteAliasUrlRule $rule)
    {
        $this->rule = $rule;
    }

    /**
     * Перенаправляет GET/HEAD-запрос на алиас, если текущий путь с ним не совпадает.
     */
    public function handle(ActionEvent $event): void
    {
        $request = Yii::$app->getRequest();
        if (!$request instanceof Request || !($request->getIsGet() || $request->getIsHead())) {
            return;
        }

        $manager = Yii::$app->getUrlManager();
        $url = $this->rule->createUrl($manager, $event->action->getUniqueId(), $request->getQueryParams());
        if ($url === false) {
            return;
        }

        // Сравниваем только путь: оставшиеся query-параметры (utm и т.п.) алиас переносит как есть.
        $aliasPath = explode('?', $url, 2)[0];
        if ($request->getPathInfo() === $aliasPath) {
            return;
        }

        Yii::$app->getResponse()->redirect($manager->getBaseUrl() . '/' . $url, 301);
        $event->isValid = false;
    }
}
