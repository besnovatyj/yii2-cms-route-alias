<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\listeners\CanonicalAliasRedirect;
use Besnovatyj\RouteAlias\services\AliasMapProvider;
use Besnovatyj\RouteAlias\urls\RouteAliasUrlRule;
use Yii;
use yii\base\ActionEvent;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\web\UrlManager;

/**
 * Bootstrap модуля алиасов.
 *
 * 1. Сброс кэша карт маршрутизации при изменении алиасов. {@see AliasMapProvider} держит прямую/обратную
 *    карты в кэше с тегом `route_aliases`. Правки алиасов идут из бэкенда обычным AR `->save()/->delete()`,
 *    поэтому ловим AR-события `RouteAlias` и сбрасываем тег целиком («крупный помол» — набор мал,
 *    точечная инвалидация не нужна).
 * 2. Правило {@see RouteAliasUrlRule} ставится ПЕРВЫМ в `frontendUrlManager` (см. {@see self::prependAliasRule()}).
 * 3. Во фронтенде — 301 на короткий URL с остальных адресов той же страницы ({@see CanonicalAliasRedirect}).
 *
 * Bootstrap глобальный (L2, гейт modman): выполняется во всех приложениях; кэш `apcu` общий, поэтому
 * фронт немедленно получает свежие карты после правки из админки. Выключение модуля убирает Bootstrap
 * целиком — правило и редирект исчезают, маршрутизация возвращается к обычной.
 */
final class Bootstrap implements BootstrapInterface
{
    /** Компонент менеджера URL фронтенда (common/config/components.php). */
    private const string URL_MANAGER_ID = 'frontendUrlManager';

    public function bootstrap($app): void
    {
        $this->prependAliasRule($app);

        if ($app->id === 'app-frontend') {
            $app->on(Application::EVENT_BEFORE_ACTION, static function (ActionEvent $event): void {
                Yii::createObject(CanonicalAliasRedirect::class)->handle($event);
            });
        }

        $invalidate = static function (): void {
            if (Yii::$app->has('cache') && Yii::$app->cache !== null) {
                TagDependency::invalidate(Yii::$app->cache, [AliasMapProvider::CACHE_TAG]);
            }
        };

        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_INSERT, $invalidate);
        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_UPDATE, $invalidate);
        Event::on(RouteAlias::class, ActiveRecord::EVENT_AFTER_DELETE, $invalidate);
    }

    /**
     * Ставит {@see RouteAliasUrlRule} в начало правил `frontendUrlManager`.
     *
     * Почему не через `rules` в config/common.php: modman сортирует пакеты группы по имени
     * (MergePlanCompiler), и правила модулей-провайдеров вмёрживаются раньше (`yii2-cms-page` <
     * `yii2-cms-route-alias`). `UrlManager::createUrl()` берёт первое сработавшее правило, поэтому
     * `page/<slug>` перехватывал генерацию, и ссылки/canonical/sitemap получали длинный адрес вместо алиаса.
     *
     * Компонент не создаётся заранее: его определение оборачивается в замыкание, которое при первом
     * обращении строит менеджер штатно и добавляет правило через `UrlManager::addRules($rules, false)`.
     * В бэкенде и консоли, где менеджер фронта нужен редко, накладных расходов нет. Если компонент уже
     * создан (кто-то обратился к нему раньше этого Bootstrap) — правило добавляется сразу.
     */
    private function prependAliasRule(Application $app): void
    {
        if (!$app->has(self::URL_MANAGER_ID)) {
            return;
        }

        $rules = [['class' => RouteAliasUrlRule::class]];

        if ($app->has(self::URL_MANAGER_ID, true)) {
            $app->get(self::URL_MANAGER_ID)->addRules($rules, false);
            return;
        }

        $definition = $app->getComponents()[self::URL_MANAGER_ID];
        $app->set(self::URL_MANAGER_ID, static function () use ($definition, $rules): UrlManager {
            /** @var UrlManager $manager */
            $manager = Yii::createObject($definition);
            $manager->addRules($rules, false);
            return $manager;
        });
    }
}
