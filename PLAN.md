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
    на основе карт из `AliasMapProvider`. Подключается как `['class' => …]` в `frontendUrlManager.rules`.
  - `Bootstrap` (L2) — инвалидация тега `route_aliases` на AR-событиях `RouteAlias`.
  - Бэкенд: `controllers\backend\DefaultController` (CRUD) + формы + вьюхи; каскад модуль→путь→slug
    реализован встроенными JSON-данными + минимальным inline-скриптом (без доп. ассетов).
- Пилот-провайдер: `Besnovatyj\Page\Module implements AliasTargetProvider` (цели `Page/page/view`,
  `Page/page/group` + списки slug). Аналог пилота Blog для канала URL-правил.

## Порядок правил

Вклад модуля в `frontendUrlManager.rules` мёржится ПЕРЕД корневыми правилами (`RecursiveMerge`),
catch-all ядра остаётся последним. Правило возвращает `false` на не-алиасных путях, поэтому не затеняет
другие правила; для алиасных выигрывает у catch-all.

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

## Не в этой итерации (phase 2)

- Явные 301-редиректы «длинный канонический → короткий» (нормализация), поле `is_permanent`.
- Вложенные/множественные параметры цели сложнее одного slug.
- Импорт/экспорт правил, аудит.

# Моя (Besnovatyj) заметка:

1) Модуль "app/vendor/besnovatyj/yii2-cms-clear-manager" предоставляет функционал сбора данных по очистке доступной у других модулей.
Как пример "app/vendor/besnovatyj/yii2-cms-blog/src/config/config.php:18" и "\Besnovatyj\Blog\controllers\backend\ClearController"
Неплохо бы и здесь реализовать подобное, чтобы можно было сбрасывать кеш из основного модуля очистки при желании 
2) Профиоировать приложение до и после включения модуля
