<?php


namespace vippsas\login\records;

use craft\db\ActiveRecord;
use vippsas\login\VippsLogin;

/**
 * @property string $vipps_sub
 * @property string $user_id
 */

class User extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'vipps_sub',
            'user_id'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return VippsLogin::SUB_DATABASE_TABLE;
    }

    /**
     * Get the connected user
     * @return \craft\elements\User|null
     */
    public function getUser()
    {
        return \craft\elements\User::findOne(['id' => $this->user_id]);
    }
}