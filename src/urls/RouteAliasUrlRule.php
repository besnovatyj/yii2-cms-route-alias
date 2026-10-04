<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\urls;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\services\AliasMapProvider;
use yii\base\BaseObject;
use yii\web\UrlRuleInterface;

/**
 * Двунаправленное правило коротких URL-алиасов на основе ТОЧНОГО сопоставления.
 *
 * Никаких пользовательских регекспов: и разбор, и генерация — array-лукапы по картам из
 * {@see AliasMapProvider} (кэш APCu). Ставится ПЕРВЫМ в `frontendUrlManager` из
 * {@see \Besnovatyj\RouteAlias\Bootstrap} (а не через `rules` конфига): иначе правила модулей-провайдеров
 * (`page/<slug>`), вмёрженные раньше по алфавиту пакетов, перехватывают генерацию URL, и алиас
 * не появляется ни в ссылках, ни в canonical. На не-алиасных путях/роутах возвращает `false` и НЕ
 * затеняет другие правила. При выключении модуля правило исчезает — маршрутизация возвращается к обычной.
 *
 * Пустой путь (`''`) — алиас главной страницы: разбирается для `/`, генерируется в `/`.
 *
 * DI: контейнер автовайрит {@see AliasMapProvider} в конструктор (правило создаётся через
 * `Yii::createObject`). Требует `frontendUrlManager.cache = false` — объект-правило с сервисом не
 * сериализуется в кэш правил менеджера (в проекте уже так, см. common/config/components.php).
 */
final class RouteAliasUrlRule extends BaseObject implements UrlRuleInterface
{
    private AliasMapProvider $maps;

    public function __construct(AliasMapProvider $maps, $config = [])
    {
        parent::__construct($config);
        $this->maps = $maps;
    }

    /**
     * Разбор запроса: короткий путь → внутренний роут с параметрами.
     *
     * Пустой путь (`/`) ищется в карте как обычный ключ `''` — алиас главной, если он заведён.
     *
     * @return array{0:string,1:array}|false
     */
    public function parseRequest($manager, $request): array|false
    {
        $path = RouteAlias::normalizePath($request->pathInfo);

        $forward = $this->maps->maps()['forward'];
        if (!isset($forward[$path])) {
            return false;
        }

        return [$forward[$path]['route'], $forward[$path]['params']];
    }

    /**
     * Генерация URL: внутренний роут с параметрами → короткий путь (если для него заведён алиас).
     *
     * Совпадение по подмножеству: параметры алиаса должны присутствовать в запрошенных с теми же
     * значениями; выбирается наиболее специфичный алиас (с наибольшим числом совпавших параметров),
     * оставшиеся параметры уходят в query-строку. Нет алиаса → `false` (обычная генерация URL).
     */
    public function createUrl($manager, $route, $params): string|false
    {
        $route = RouteAlias::normalizeRoute((string)$route);
        $reverse = $this->maps->maps()['reverse'];
        if (!isset($reverse[$route])) {
            return false;
        }

        $best = null;
        $bestSize = -1;
        foreach ($reverse[$route] as $candidate) {
            if ($this->isSubset($candidate['params'], $params) && count($candidate['params']) > $bestSize) {
                $best = $candidate;
                $bestSize = count($candidate['params']);
            }
        }

        if ($best === null) {
            return false;
        }

        $leftover = $this->diffParams($params, $best['params']);
        $url = $best['path'];
        if ($leftover !== [] && ($query = http_build_query($leftover)) !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Все пары ключ→значение из $subset присутствуют в $params с равными (строково) значениями.
     *
     * Нескалярное значение (`?slug[]=x` из query-строки при канонизирующем редиректе) — не совпадение,
     * а не «Array to string conversion».
     *
     * @param array<string,mixed> $subset
     * @param array<string,mixed> $params
     */
    private function isSubset(array $subset, array $params): bool
    {
        foreach ($subset as $key => $value) {
            if (
                !array_key_exists($key, $params)
                || !is_scalar($params[$key])
                || (string)$params[$key] !== (string)$value
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Параметры $params без ключей, покрытых алиасом ($covered).
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $covered
     * @return array<string,mixed>
     */
    private function diffParams(array $params, array $covered): array
    {
        return array_diff_key($params, $covered);
    }
}
