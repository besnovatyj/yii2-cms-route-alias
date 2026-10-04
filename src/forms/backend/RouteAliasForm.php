<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\forms\backend;

use Besnovatyj\Forms\BaseForm;
use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\repositories\RouteAliasRepository;
use Besnovatyj\RouteAlias\services\AliasTargetRegistry;
use Yii;

/**
 * Форма создания/редактирования алиаса.
 *
 * Оперирует связкой «модуль → путь (роут) → slug»; хранится роут + params. Поле `module` — вспомогательное
 * (для каскада в UI), не сохраняется. Валидаторы, которым нужны сервисы, резолвят их из DI-контейнера,
 * чтобы форму можно было создавать обычным `new` (совместимо с CRUD-контроллером).
 *
 * Флажок `isHome` — алиас главной страницы: хранится как пустой `path` (UNIQUE в БД гарантирует, что
 * главная одна). Поле `path` при нём не заполняется и не валидируется на формат.
 */
class RouteAliasForm extends BaseForm
{
    public ?int $id = null;
    public string $module = '';
    public string $route = '';
    public ?string $slug = '';
    public string $path = '';
    public bool $isHome = false;
    public int $status = RouteAlias::STATUS_ACTIVE;

    public function __construct(?RouteAlias $alias = null, $config = [])
    {
        if ($alias !== null) {
            $this->id = $alias->id;
            $this->route = $alias->route;
            $this->path = $alias->path;
            $this->isHome = $alias->path === '';
            $this->status = (int)$alias->status;
            $params = $alias->getParams();
            $this->slug = $params !== [] ? (string)reset($params) : '';
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['route', 'required'],
            ['path', 'required', 'when' => fn (): bool => !$this->isHome,
                'whenClient' => 'function () { var h = document.querySelector(\'#route-alias-form [data-role="home"]\'); return !(h && h.checked); }'],
            [['route', 'path', 'slug', 'module'], 'string', 'max' => 255],
            ['isHome', 'boolean'],
            ['status', 'in', 'range' => [RouteAlias::STATUS_INACTIVE, RouteAlias::STATUS_ACTIVE]],
            ['path', 'match', 'pattern' => '#^[a-z0-9]+(?:[-/][a-z0-9]+)*$#',
                'message' => 'Путь: строчные латинские буквы/цифры, разделители «-» и «/», без ведущего/хвостового слэша.'],
            ['route', 'validateRoute'],
            ['slug', 'validateSlug'],
            ['path', 'validatePathReserved'],
            // skipOnEmpty=false: пустой путь главной тоже проверяется на занятость (иначе — IntegrityException по UNIQUE).
            ['path', 'validatePathUnique', 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'module' => 'Модуль',
            'route'  => 'Базовый путь (цель)',
            'slug'   => 'Slug',
            'path'   => 'Короткий URL',
            'isHome' => 'Главная страница',
            'status' => 'Активен',
        ];
    }

    /**
     * Алиас главной всегда хранится с пустым путём, что бы ни пришло в поле `path`.
     */
    public function beforeValidate(): bool
    {
        if ($this->isHome) {
            $this->path = '';
        }
        return parent::beforeValidate();
    }

    /**
     * Роут должен быть объявлен модулем-провайдером ({@see AliasTargetProvider}).
     */
    public function validateRoute(string $attribute): void
    {
        $known = $this->registry()->knownRoutes();
        if (!in_array(RouteAlias::normalizeRoute($this->route), $known, true)) {
            $this->addError($attribute, 'Неизвестная цель. Выберите модуль и его базовый путь из списка.');
        }
    }

    /**
     * Если провайдер отдаёт список slug для цели — выбранный slug должен в нём быть.
     */
    public function validateSlug(string $attribute): void
    {
        $target = $this->registry()->findTarget($this->route);
        if ($target === null) {
            return; // роут уже отвергнут validateRoute
        }
        if ($this->slug === null || $this->slug === '') {
            $this->addError($attribute, 'Выберите slug цели.');
            return;
        }
        $provider = null;
        foreach ($this->registry()->providers() as $candidate) {
            foreach ($candidate->aliasTargets() as $t) {
                if (RouteAlias::normalizeRoute($t->route) === RouteAlias::normalizeRoute($this->route)) {
                    $provider = $candidate;
                    break 2;
                }
            }
        }
        $available = $provider?->aliasSlugs($this->route) ?? [];
        if ($available !== [] && !array_key_exists($this->slug, $available)) {
            $this->addError($attribute, 'Такого slug нет среди доступных для выбранной цели.');
        }
    }

    /**
     * Путь не должен затенять зарезервированные/системные префиксы (список — в params модуля).
     */
    public function validatePathReserved(string $attribute): void
    {
        $reserved = (array)(Yii::$app->getModule('RouteAlias')->params['reservedPrefixes'] ?? []);
        $firstSegment = explode('/', RouteAlias::normalizePath($this->path))[0] ?? '';
        if ($firstSegment !== '' && in_array($firstSegment, array_map('mb_strtolower', $reserved), true)) {
            $this->addError($attribute, 'Этот префикс зарезервирован и не может использоваться как короткий URL.');
        }
    }

    /**
     * Глобальная уникальность короткого пути (дублирует UNIQUE в БД — для дружелюбной ошибки).
     */
    public function validatePathUnique(string $attribute): void
    {
        if ($this->path === '' && !$this->isHome) {
            return; // пустой путь без флажка уже отвергнут required
        }
        /** @var RouteAliasRepository $repo */
        $repo = Yii::$container->get(RouteAliasRepository::class);
        if ($repo->existsByPath($this->path, $this->id)) {
            if ($this->isHome) {
                $this->addError('isHome', 'Главная страница уже назначена другому алиасу.');
            } else {
                $this->addError($attribute, 'Такой короткий URL уже занят.');
            }
        }
    }

    private function registry(): AliasTargetRegistry
    {
        return Yii::$container->get(AliasTargetRegistry::class);
    }
}
