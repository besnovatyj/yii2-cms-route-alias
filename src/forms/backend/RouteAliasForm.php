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
 */
class RouteAliasForm extends BaseForm
{
    public ?int $id = null;
    public string $module = '';
    public string $route = '';
    public ?string $slug = '';
    public string $path = '';
    public int $status = RouteAlias::STATUS_ACTIVE;

    public function __construct(?RouteAlias $alias = null, $config = [])
    {
        if ($alias !== null) {
            $this->id = $alias->id;
            $this->route = $alias->route;
            $this->path = $alias->path;
            $this->status = (int)$alias->status;
            $params = $alias->getParams();
            $this->slug = $params !== [] ? (string)reset($params) : '';
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['route', 'path'], 'required'],
            [['route', 'path', 'slug', 'module'], 'string', 'max' => 255],
            ['status', 'in', 'range' => [RouteAlias::STATUS_INACTIVE, RouteAlias::STATUS_ACTIVE]],
            ['path', 'match', 'pattern' => '#^[a-z0-9]+(?:[-/][a-z0-9]+)*$#',
                'message' => 'Путь: строчные латинские буквы/цифры, разделители «-» и «/», без ведущего/хвостового слэша.'],
            ['route', 'validateRoute'],
            ['slug', 'validateSlug'],
            ['path', 'validatePathReserved'],
            ['path', 'validatePathUnique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'module' => 'Модуль',
            'route'  => 'Базовый путь (цель)',
            'slug'   => 'Slug',
            'path'   => 'Короткий URL',
            'status' => 'Активен',
        ];
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
        /** @var RouteAliasRepository $repo */
        $repo = Yii::$container->get(RouteAliasRepository::class);
        if ($repo->existsByPath($this->path, $this->id)) {
            $this->addError($attribute, 'Такой короткий URL уже занят.');
        }
    }

    private function registry(): AliasTargetRegistry
    {
        return Yii::$container->get(AliasTargetRegistry::class);
    }
}
