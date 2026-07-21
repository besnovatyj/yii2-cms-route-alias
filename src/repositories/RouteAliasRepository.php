<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\repositories;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use RuntimeException;

/**
 * CRUD-репозиторий алиасов (бэкенд).
 */
class RouteAliasRepository
{
    /**
     * @throws NotFoundException
     */
    public function get(int $id): RouteAlias
    {
        if (($alias = RouteAlias::findOne($id)) !== null) {
            return $alias;
        }
        throw new NotFoundException('Алиас не найден.');
    }

    /**
     * Есть ли алиас с таким путём (для проверки уникальности; можно исключить свой id).
     */
    public function existsByPath(string $path, ?int $exceptId = null): bool
    {
        $query = RouteAlias::find()->where(['path' => RouteAlias::normalizePath($path)]);
        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }
        return $query->exists();
    }

    public function save(RouteAlias $alias): void
    {
        if (!$alias->save()) {
            throw new RuntimeException('Не удалось сохранить алиас.');
        }
    }

    public function remove(RouteAlias $alias): void
    {
        if (!$alias->delete()) {
            throw new RuntimeException('Не удалось удалить алиас.');
        }
    }
}
