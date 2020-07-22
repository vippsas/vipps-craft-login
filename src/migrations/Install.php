<?php

namespace vippsas\login\migrations;

use craft\db\Migration;
use vippsas\login\VippsLogin;
use craft\db\Table;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(
            VippsLogin::SUB_DATABASE_TABLE,
            [
                'user_id' => $this->integer()->notNull(),
                'vipps_sub' => $this->uid()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->addPrimaryKey(
            'pk_vipps_user',
            VippsLogin::SUB_DATABASE_TABLE,
            'vipps_sub'
        );
        $this->addForeignKey(
            'fk_vipps_is_connected_to_user',
            VippsLogin::SUB_DATABASE_TABLE,
            'user_id',
            Table::USERS,
            'id',
            'CASCADE',
            'CASCADE'
        );

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(VippsLogin::SUB_DATABASE_TABLE);
        return true;
    }
}
