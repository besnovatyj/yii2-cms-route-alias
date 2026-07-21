<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\controllers\backend;

use Besnovatyj\RouteAlias\services\AliasCacheClearService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Контроллер интеграции модуля алиасов с модулем очистки (ClearManager).
 *
 * Эндпойнты объявлены в config/config.php (`params.endpoints.clear.maps`) — их читает
 * {@see \Besnovatyj\ClearManager\services\EndpointCollectorService}; жёсткой зависимости от ClearManager
 * нет (интеграция по конвенции параметров). Формат ответа и обработка ошибок повторяют
 * {@see \Besnovatyj\ClearManager\controllers\backend\DataController} и {@see \Besnovatyj\Blog\controllers\backend\ClearController}:
 *  - все экшены — только POST и только AJAX, ответ в JSON;
 *  - ожидаемые сбои бросаются {@see ServerErrorHttpException}, прочие исключения всплывают к ErrorHandler.
 */
class ClearController extends Controller
{
    private AliasCacheClearService $service;

    public function __construct($id, $module, AliasCacheClearService $service, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['POST']],
            ],
        ];
    }

    /**
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        // Формат ставим до parent::beforeAction — чтобы ошибки фильтров (verb) тоже ушли как JSON.
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Yii::$app->getRequest()->getIsAjax()) {
            throw new BadRequestHttpException('Ожидается AJAX-запрос.');
        }
        return true;
    }

    /**
     * Состояние кэша карт маршрутизации алиасов.
     */
    public function actionGetData(): array
    {
        return ['status' => 'success', 'data' => $this->service->getData()];
    }

    /**
     * Сбрасывает кэш карт маршрутизации алиасов.
     *
     * @throws ServerErrorHttpException
     */
    public function actionClearData(): array
    {
        if (!$this->service->clearData()) {
            throw new ServerErrorHttpException('Не удалось сбросить кэш карт маршрутизации алиасов.');
        }
        return ['status' => 'success', 'message' => 'Кэш карт маршрутизации алиасов сброшен'];
    }
}
