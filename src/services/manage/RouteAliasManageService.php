<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\services\manage;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\forms\backend\RouteAliasForm;
use Besnovatyj\RouteAlias\repositories\RouteAliasRepository;
use Besnovatyj\RouteAlias\services\AliasTargetRegistry;

/**
 * Сервис управления алиасами (create/edit/remove).
 *
 * Собирает параметры роута из выбранного slug и имени slug-параметра цели ({@see AliasTargetRegistry}):
 * форма оперирует «модуль → путь → slug», а хранится роут + `params` (обычно `{"slug": "..."}`).
 */
final class RouteAliasManageService
{
    private RouteAliasRepository $repository;
    private AliasTargetRegistry $registry;

    public function __construct(RouteAliasRepository $repository, AliasTargetRegistry $registry)
    {
        $this->repository = $repository;
        $this->registry = $registry;
    }

    public function create(RouteAliasForm $form): RouteAlias
    {
        $alias = RouteAlias::create(
            $form->path,
            $form->route,
            $this->buildParams($form),
            (int)$form->status,
        );
        $this->repository->save($alias);
        return $alias;
    }

    public function edit(int $id, RouteAliasForm $form): void
    {
        $alias = $this->repository->get($id);
        $alias->edit(
            $form->path,
            $form->route,
            $this->buildParams($form),
            (int)$form->status,
        );
        $this->repository->save($alias);
    }

    public function remove(int $id): void
    {
        $alias = $this->repository->get($id);
        $this->repository->remove($alias);
    }

    /**
     * @return array<string,string>
     */
    private function buildParams(RouteAliasForm $form): array
    {
        if ($form->slug === null || $form->slug === '') {
            return [];
        }
        $target = $this->registry->findTarget($form->route);
        $slugParam = $target?->slugParam ?? 'slug';
        return [$slugParam => $form->slug];
    }
}
