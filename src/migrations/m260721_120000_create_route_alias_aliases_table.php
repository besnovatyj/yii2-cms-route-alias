<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use yii\base\NotSupportedException;

/** 'm<YYMMDD_HHMMSS>_<n>' */
class m260721_120000_create_route_alias_aliases_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%route_alias_aliases%}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'path' => $this->string(255)->notNull()
                ->comment('Короткий литеральный путь URL (без ведущего/хвостового слэша)'),
            'route' => $this->string(255)->notNull()
                ->comment('Внутренний роут-цель (без ведущего слэша), напр. Page/page/view'),
            'params_json' => $this->text()->null()->defaultValue(null)
                ->comment('JSON параметров роута (напр. {"slug":"about"})'),
            'status' => $this->smallInteger(1)->notNull()->defaultValue(1)
                ->comment('0=выключен, 1=включён'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('NOW()')
                ->comment('Дата создания'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('NOW()')->append('ON UPDATE NOW()')
                ->comment('Дата обновления'),
        ], $this->tableOptions);
        $this->addCommentOnTable(static::TABLE_NAME, 'Короткие URL-алиасы маршрутов фронтенда');

        // UNIQUE на path — глобальная уникальность короткого адреса: два модуля с одинаковым slug
        // не смогут занять один и тот же короткий URL (защита от коллизий на уровне БД).
        $this->createIndexes(static::TABLE_NAME, 'path', false, true);
        $this->createIndexes(static::TABLE_NAME, 'route', false, false);
        $this->createIndexes(static::TABLE_NAME, 'status', false, false);

        parent::safeUp();
    }

    public function safeDown(): void
    {
        parent::safeDown();
    }
}
