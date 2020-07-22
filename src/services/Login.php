<?php


namespace vippsas\login\services;

use Craft;
use GuzzleHttp\Client;
use vippsas\login\components\Button;
use vippsas\login\models\Session;
use vippsas\login\VippsLogin;
use yii\base\Component;
use vippsas\login\models\Settings;
use yii\base\InvalidConfigException;

class Login extends Component
{
    // Constants
    // =========================================================================

    /**
     * Base URL for the production API
     * @var string
     */
    const PROD_URL = 'https://api.vipps.no';

    /**
     * Base URL for the test API
     * @var string
     */
    const TEST_URL = 'https://apitest.vipps.no';

    // Properties
    // =========================================================================

    /**
     * Settings object
     * @var Settings
     */
    private $settings;

    /**
     * Guzzle Client object
     * @var Client
     */
    private $client;

    /**
     * The Vipps Session if the User is logged in
     * @var Session
     */
    private $session_object;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->settings = VippsLogin::getInstance()->getSettings();

        parent::init();
    }

    /**
     * Get the LogInButton object
     * @return Button
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function loginButton() : Button
    {
        return new Button($this->getLoginUrl());
    }

    /**
     * Get the LogInButton object
     * @return Button
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function continueButton() : Button
    {
        return (new Button($this->getContinueUrl()))->continue();
    }

    /**
     * Returns the auth URL
     * @return string
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getLoginUrl() : string
    {
        $parameters = [
            'client_id' => $this->getClientId(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->settings->getLoginScopes()),
            'state' => \Craft::$app->security->generateRandomString(30),
            'redirect_uri' => $this->settings->getRedirectUri(),
        ];
        $path = $this->getOpenIDConfiguration('authorization_endpoint', $this->getBaseUrl().'/access-management-1.0/access/oauth2/auth');

        return $path.'?'.http_build_query($parameters);
    }

    /**
     * Returns the auth URL
     * @return string
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getContinueUrl() : string
    {
        $parameters = [
            'client_id' => $this->getClientId(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->settings->getContinueScopes()),
            'state' => \Craft::$app->security->generateRandomString(30),
            'redirect_uri' => $this->settings->getRedirectUri('continue'),
        ];
        $path = $this->getOpenIDConfiguration('authorization_endpoint', $this->getBaseUrl().'/access-management-1.0/access/oauth2/auth');

        return $path.'?'.http_build_query($parameters);
    }

    /**
     * Request a new login token from vipps based on a code
     * @param $code
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidConfigException
     */
    public function getNewLoginToken($code)
    {
        $path = $this->getOpenIDConfiguration('token_endpoint', $this->getBaseUrl().'/access-management-1.0/access/oauth2/token');

        return $this->getClient()->post($path, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->getClientId().':'.$this->getClientSecret())
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->settings->getRedirectUri(),
                'client_id' => $this->getClientId()
            ]
        ]);
    }

    /**
     * Request a new continue token from vipps based on a code
     * @param $code
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidConfigException
     */
    public function getNewContinueToken($code)
    {
        $path = $this->getOpenIDConfiguration('token_endpoint', $this->getBaseUrl().'/access-management-1.0/access/oauth2/token');

        return $this->getClient()->post($path, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->getClientId().':'.$this->getClientSecret())
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->settings->getRedirectUri('continue'),
                'client_id' => $this->getClientId()
            ]
        ]);
    }

    /**
     * Request userinfo from Vipps
     * @param $token
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidConfigException
     */
    public function getUserInfo($token)
    {
        $path = $this->getOpenIDConfiguration('userinfo_endpoint', $this->getBaseUrl().'/vipps-userinfo-api/userinfo');

        return $this->getClient()->get($path, [
            'headers' => [
                'Authorization' => 'Bearer '.$token
            ]
        ]);
    }

    /**
     * Returns the Vipps Session if the user is logged in.
     * Returns null if there is no session for the current user.
     * @return Session|null
     */
    public function session()
    {
        if(!$this->session_object)
        {
            $content = \Craft::$app->session->get('vipps_login');

            if(!$content)
            {
                return null;
            }

            $this->session_object = unserialize($content);

            // If the session is expired, delete it
            if($this->session_object->getExpiresIn() < 0)
            {
                Craft::$app->session->remove('vipps_login');
                return null;
            }
        }
        return $this->session_object;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Get the OpenID Configuration from the
     * .well-known/openid-configuration endpoint
     * @param string|null $path
     * @param string|null $default
     * @return mixed|string
     * @throws InvalidConfigException
     */
    protected function getOpenIDConfiguration(string $path = null, string $default = null)
    {
        $config = Craft::$app->cache->get('vipps-login-openid-configuration');

        if(!$config)
        {
            $config = $this->fetchOpenIDConfiguration()->getBody()->getContents();
            Craft::$app->cache->set('vipps-login-openid-configuration', $config, 3600);
        }

        $array = json_decode($config, true);

        if($path !== null && !is_string($path)) throw new InvalidConfigException("OpenID Configuration path must be null or string");
        elseif($path === null) return $array;
        else {
            if(isset($array[$path])) return $array[$path];
            elseif($default !== null) return $default;
            else throw new InvalidConfigException("OpenID Configuration is not found and default value is not provided.");
        }
    }



    // Private Methods
    // =========================================================================

    private function fetchOpenIDConfiguration()
    {
        return $this->getClient()->get($this->getBaseUrl().'/access-management-1.0/access/.well-known/openid-configuration');
    }

    /**
     * Get the base API URL based on environment
     * @return string
     */
    private function getBaseUrl() : string
    {
        return $this->settings->inTest() ? self::TEST_URL : self::PROD_URL;
    }

    /**
     * Returns the Client ID for the current environment
     * @return string
     */
    private function getClientId() : string
    {
        return $this->settings->inTest() ? $this->settings->test_client_id : $this->settings->prod_client_id;
    }

    /**
     * Returns the Client Secret for the current environment
     * @return string
     */
    private function getClientSecret() : string
    {
        return $this->settings->inTest() ? $this->settings->test_client_secret : $this->settings->prod_client_secret;
    }

    /**
     * Get the GuzzleHTTP Client object
     * @return Client
     */
    private function getClient()
    {
        if(!$this->client || $this->client instanceof Client) $this->client = new Client();
        return $this->client;
    }
}