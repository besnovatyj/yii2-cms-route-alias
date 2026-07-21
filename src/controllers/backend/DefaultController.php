<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\controllers\backend;

use Besnovatyj\Kernel\controller\ControllerTrait;
use Besnovatyj\RouteAlias\forms\backend\RouteAliasForm;
use Besnovatyj\RouteAlias\forms\backend\search\RouteAliasSearch;
use Besnovatyj\RouteAlias\repositories\RouteAliasRepository;
use Besnovatyj\RouteAlias\services\AliasTargetRegistry;
use Besnovatyj\RouteAlias\services\manage\RouteAliasManageService;
use Throwable;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Бэкенд-контроллер управления короткими URL-алиасами.
 */
class DefaultController extends Controller
{
    use ControllerTrait;

    public function __construct(
        $id,
        $module,
        private readonly RouteAliasManageService $service,
        private readonly RouteAliasRepository $repository,
        private readonly AliasTargetRegistry $registry,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ]);
    }

    public function actionIndex(): string
    {
        $searchModel = new RouteAliasSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'alias' => $this->repository->get($id),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new RouteAliasForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $alias = $this->service->create($form);
                Yii::$app->session->setFlash('success', 'Алиас создан.');
                return $this->redirect(['view', 'id' => $alias->id]);
            } catch (\Exception $e) {
                $this->handleDomainException($e, 'Ошибка при создании алиаса.');
            }
        }
        $this->flashFormErrors($form);

        return $this->render('create', [
            'model' => $form,
            'catalog' => $this->registry->catalog(),
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $alias = $this->repository->get($id);
        $form = new RouteAliasForm($alias);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->edit($id, $form);
                Yii::$app->session->setFlash('success', 'Алиас обновлён.');
                return $this->redirect(['view', 'id' => $id]);
            } catch (\Exception $e) {
                $this->handleDomainException($e, 'Ошибка при сохранении алиаса.');
            }
        }
        $this->flashFormErrors($form);

        return $this->render('update', [
            'model' => $form,
            'alias' => $alias,
            'catalog' => $this->registry->catalog(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function actionDelete(int $id): Response
    {
        try {
            $this->service->remove($id);
            Yii::$app->session->setFlash('success', 'Алиас удалён.');
        } catch (\Exception $e) {
            Yii::$app->errorHandler->logException($e);
            Yii::$app->session->setFlash('error', YII_DEBUG ? VarDumper::dumpAsString($e->getMessage()) : 'Ошибка при удалении.');
        }
        return $this->redirect(['index']);
    }

    private function flashFormErrors(RouteAliasForm $form): void
    {
        if ($form->hasErrors()) {
            Yii::$app->session->addFlash('error', $form->getErrorSummary(true));
        }
    }
}
