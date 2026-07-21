<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\entities;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\Json;

/**
 * Короткий URL-алиас маршрута.
 *
 * Отображает литеральный путь (`path`, глобально уникальный) на внутренний фронтовый роут (`route`)
 * с параметрами (`params_json`, обычно `{"slug": "..."}`). Точное сопоставление, никаких регекспов.
 *
 * @property int         $id
 * @property string      $path
 * @property string      $route
 * @property string|null $params_json
 * @property int         $status
 * @property string      $created_at
 * @property string      $updated_at
 */
class RouteAlias extends ActiveRecord
{
    public const int STATUS_INACTIVE = 0;
    public const int STATUS_ACTIVE = 1;

    /**
     * @param array<string,mixed> $params
     */
    public static function create(string $path, string $route, array $params, int $status = self::STATUS_ACTIVE): self
    {
        $alias = new static();
        $alias->path = self::normalizePath($path);
        $alias->route = self::normalizeRoute($route);
        $alias->setParams($params);
        $alias->status = $status;
        return $alias;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function edit(string $path, string $route, array $params, int $status): void
    {
        $this->path = self::normalizePath($path);
        $this->route = self::normalizeRoute($route);
        $this->setParams($params);
        $this->status = $status;
    }

    public function isActive(): bool
    {
        return (int)$this->status === self::STATUS_ACTIVE;
    }

    /**
     * Параметры роута как массив.
     *
     * @return array<string,mixed>
     */
    public function getParams(): array
    {
        if ($this->params_json === null || $this->params_json === '') {
            return [];
        }
        $decoded = Json::decode($this->params_json);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params_json = $params === [] ? null : Json::encode($params);
    }

    /**
     * Нормализует путь: без ведущего/хвостового слэша, нижний регистр.
     */
    public static function normalizePath(string $path): string
    {
        return mb_strtolower(trim($path, "/ \t\n\r"));
    }

    /**
     * Нормализует роут к каноничному виду UrlManager: без ведущего слэша.
     */
    public static function normalizeRoute(string $route): string
    {
        return ltrim(trim($route), '/');
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%route_alias_aliases%}}';
    }
}
