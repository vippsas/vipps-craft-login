<?php


namespace vippsas\login\events;


use craft\elements\User;
use vippsas\login\models\Session;
use yii\base\Event;

class BaseVippsEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param User $user
     * @return self
     */
    public function setUser(User $user) : self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param User $user
     * @return self
     */
    public function setSession(Session $session) : self
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}