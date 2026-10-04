# План модуля `besnovatyj/yii2-cms-route-alias`

## Назначение

Управляемые из админки короткие («красивые») URL фронтенда, отвязанные от домена и без
пользовательских регекспов. Админ задаёт **литеральный путь** и выбирает **цель** (модуль → базовый
роут → slug) из списков, которые модули объявляют сами. Одно класс-правило `UrlRuleInterface`
работает в обе стороны на основе **точного сопоставления** по таблице в БД, загружаемой целиком в кэш.

## Ключевые инварианты (согласовано с заказчиком)

1. **Опциональность.** Модуль — НЕ критичная зависимость. Ядро и минимальное приложение работают без
   БД и без этого модуля. Никакой другой модуль не зависит от него жёстко.
2. **Прозрачный fallback.** Во вьюхах всегда обычный `Url::to(['/Module/controller/action', ...])`.
   Включён модуль → в адресной строке короткие алиасы (там, где заведены). Выключен → всё возвращается
   к обычной маршрутизации, ничего не падает (правило исчезает из `frontendUrlManager` через modman).
3. **Никаких админских регекспов.** Сопоставление — строковое равенство пути. Единственный фиксированный
   regex — в коде правила, ревьюится один раз.
4. **Коллизии закрыты БД.** `UNIQUE` на `path` — два модуля с одинаковым slug не займут один короткий URL.
5. **Производительность.** Весь (небольшой) набор алиасов грузится одним элементом кэша (APCu), инвалидация
   по тегу `route_aliases` при CRUD. На горячем пути парсинга/генерации — array-лукапы, ноль запросов к БД.
   (`Connection::$enableSchemaCache` тут ни при чём — он кэширует метаданные схемы, не строки.)

## Архитектура

- Контракт в `yii2-cms-contracts` (нейтральная зона, все модули уже зависят от него):
  - `Besnovatyj\Contracts\routing\AliasTarget` — readonly DTO цели (роут, подпись, имя slug-параметра).
  - `Besnovatyj\Contracts\routing\AliasTargetProvider` — модуль объявляет алиасуемые цели и доступные slug.
- Модуль `RouteAlias`:
  - `entities\RouteAlias` (AR) — таблица `{{%route_alias_aliases%}}`.
  - `readModels\RouteAliasReadRepository` — активные алиасы для карт.
  - `repositories\RouteAliasRepository` — CRUD.
  - `services\AliasMapProvider` — строит и кэширует прямую/обратную карты (тег `route_aliases`).
  - `services\AliasTargetRegistry` — находит модули-провайдеры (`instanceof AliasTargetProvider`).
  - `services\manage\RouteAliasManageService` — create/edit/remove (форма → сущность, slug → params).
  - `urls\RouteAliasUrlRule` (`UrlRuleInterface`) — parse (path→route+params) и create (route+params→path),
    на основе карт из `AliasMapProvider`. Ставится первым в `frontendUrlManager` из `Bootstrap`
    (см. «Порядок правил»). Пустой путь `''` — алиас главной (`/`).
  - `Bootstrap` (L2) — инвалидация тега `route_aliases` на AR-событиях `RouteAlias`; правило первым
    в `frontendUrlManager`; во фронтенде — подписка `listeners\CanonicalAliasRedirect`.
  - `listeners\CanonicalAliasRedirect` — 301 на алиас, если страница открыта по другому адресу.
  - Бэкенд: `controllers\backend\DefaultController` (CRUD) + формы + вьюхи; каскад модуль→путь→slug
    реализован встроенными JSON-данными + минимальным inline-скриптом (без доп. ассетов).
  - Интеграция с ClearManager (опциональная, по конвенции `params.endpoints.clear`):
    `services\AliasCacheClearService` + `controllers\backend\ClearController` — сброс кэша карт
    маршрутизации (тег `route_aliases`) из основного модуля очистки. Жёсткой зависимости нет.
- Пилот-провайдер: `Besnovatyj\Page\Module implements AliasTargetProvider` (цели `Page/page/view`,
  `Page/page/group` + списки slug). Аналог пилота Blog для канала URL-правил.

## Порядок правил

Правило должно стоять ПЕРВЫМ: `UrlManager::createUrl()` берёт первое сработавшее правило. Вклад через
`frontendUrlManager.rules` не годится — modman сортирует пакеты группы по имени (`MergePlanCompiler`),
и `page/<slug>` из `yii2-cms-page` оказывался раньше: разбор `/price` работал, а ссылки, canonical и
sitemap получали `/page/price` (дубль адреса). Поэтому `Bootstrap` оборачивает определение
`frontendUrlManager` в ленивое замыкание и добавляет правило штатным `addRules($rules, false)`.
Правило возвращает `false` на не-алиасных путях/роутах, поэтому не затеняет другие правила.

## Дубли адресов и канонизация

Страница с алиасом остаётся доступной и по другим адресам: правило модуля (`/page/home`), разбор по
умолчанию (`/Page/page/view?slug=home`), другой регистр/хвостовой слэш (`/PRICE`, `/price/`).
- canonical/`og:url` (`Url::canonical()`), sitemap, меню, ссылки — генерация через менеджер, получают алиас;
- `CanonicalAliasRedirect` (фронтенд, GET/HEAD, `Application::EVENT_BEFORE_ACTION`) отвечает 301 на алиас,
  если путь запроса не совпадает с путём алиаса; оставшиеся query-параметры переносятся.

## Шаги реализации

- [x] 0. Изучить конвенции (Module/Bootstrap/common/config, миграции, формы, CRUD, UrlRule-пример).
- [x] 1. Каркас: дерево каталогов, LICENSE, PLAN.md.
- [x] 2. Контракты: `AliasTarget`, `AliasTargetProvider` в `yii2-cms-contracts`.
- [x] 3. composer.json модуля.
- [x] 4. Миграция таблицы `route_alias_aliases` (UNIQUE path, индексы route/status).
- [x] 5. Сущность `RouteAlias` (create/edit, params ↔ params_json).
- [x] 6. Репозитории: read (активные) + write (CRUD) + NotFoundException.
- [x] 7. `AliasMapProvider` (кэш-карты) и `AliasTargetRegistry` (discovery провайдеров).
- [x] 8. `RouteAliasUrlRule` (обе стороны, subset-match параметров, leftover → query).
- [x] 9. `Bootstrap` (инвалидация тега).
- [x] 10. Формы: `RouteAliasForm`, `search\RouteAliasSearch`; `manage\RouteAliasManageService`.
- [x] 11. Бэкенд-контроллер CRUD + вьюхи (index/create/update/view/_form) с каскадом.
- [x] 12. Конфиги модуля: `Module.php`, `config/{config,common,dependencies,options,adminMenu}.php`.
- [x] 13. Пилот: `Page\Module` реализует `AliasTargetProvider` (+ метод списка slug в read-repo).
- [x] 14. Самопроверка: php -l синтаксис, сверка неймспейсов/зависимостей, README-примечание по установке.
- [x] 15. Интеграция с ClearManager: `params.endpoints.clear` + `ClearController` + `AliasCacheClearService`.
- [x] 16. Главная страница (пустой путь, флажок `isHome` в форме); правило первым из `Bootstrap`;
  301 «длинный → короткий» (`CanonicalAliasRedirect`).

## Не в этой итерации (phase 2)

- Поле `is_permanent` (выбор 301/302 на алиас) — сейчас редирект всегда 301.
- Вложенные/множественные параметры цели сложнее одного slug.
- Импорт/экспорт правил, аудит.
