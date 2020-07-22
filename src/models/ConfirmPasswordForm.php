<?php


namespace vippsas\login\models;


use craft\base\Model;

class ConfirmPasswordForm extends Model
{
    public $password;

    public function rules()
    {
        return [
            ['password', 'string']
        ];
    }
}