<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\forms\backend\search;

use Besnovatyj\RouteAlias\entities\RouteAlias;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Поиск/фильтрация алиасов в админке.
 */
class RouteAliasSearch extends Model
{
    public $id;
    public $path;
    public $route;
    public $status;

    public function rules(): array
    {
        return [
            [['id', 'status'], 'integer'],
            [['path', 'route'], 'string'],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = RouteAlias::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => [
                'pageSize' => 100,
                'pageSizeLimit' => [15, 100],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id, 'status' => $this->status]);
        $query->andFilterWhere(['like', 'path', $this->path]);
        $query->andFilterWhere(['like', 'route', $this->route]);

        return $dataProvider;
    }
}
