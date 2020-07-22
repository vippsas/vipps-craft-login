<?php


namespace vippsas\login\models;

use vippsas\login\VippsLogin;

class Session
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private $access_token;

    /**
     * @var integer
     */
    private $token_duration;

    /**
     * @var string
     */
    private $id_token;

    /**
     * @var array
     */
    private $scopes;

    /**
     * @var string
     */
    private $token_type;

    /**
     * @var integer
     */
    private $expires_at;

    /**
     * @var array
     */
    private $data;

    // Public Methods
    // =========================================================================

    /**
     * Session constructor.
     * @param mixed $response
     */
    public function __construct($response)
    {
        $this->access_token = $response->access_token;
        $this->token_duration = $response->expires_in;
        $this->id_token = $response->id_token;
        $this->scopes = explode(' ', $response->scope);
        $this->token_type = $response->token_type;
        $this->expires_at = time()+$response->expires_in;
        $this->getDataFromVipps();
    }

    /**
     * Check if the token is expired
     * @return bool
     */
    public function isExpired()
    {
        return $this->getExpiresIn() > 0;
    }

    /**
     * Returns the number of seconds until
     * the token expires
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expires_at - time();
    }

    /**
     * Returns an array of adresses
     * @return array|null
     */
    public function getAddresses()
    {
        return $this->getFieldFromData('address');
    }

    /**
     * Returns the user email
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getFieldFromData('email');
    }

    /**
     * Returns true if the email is verified
     * false if its not, and null if the information is missing
     * @return bool|null
     */
    public function isEmailVerified()
    {
        return $this->getFieldFromData('email_verified');
    }

    /**
     * Returns the given name
     * @return string|null
     */
    public function getGivenName()
    {
        return $this->getFieldFromData('given_name');
    }

    /**
     * Returns the family name
     * @return string|null
     */
    public function getFamilyName()
    {
        return $this->getFieldFromData('family_name');
    }

    /**
     * Returns the full name
     * @return string|null
     */
    public function getName()
    {
        return $this->getFieldFromData('name');
    }

    /**
     * Returns the Phone Number
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->getFieldFromData('phone_number');
    }

    /**
     * Returns the SID
     * @return string|null
     */
    public function getSid()
    {
        return $this->getFieldFromData('sid');
    }

    /**
     * Returns the Sub
     * @return string|null
     */
    public function getSub()
    {
        return $this->getFieldFromData('sub');
    }

    /**
     * Get the National Identification Number
     * @return string|null
     */
    public function getNin()
    {
        return $this->getFieldFromData('nin');
    }

    /**
     * Returns the BirthDate as a DateTime object
     * @return \DateTime|null
     */
    public function getBirthdate()
    {
        $birthdate = $this->getFieldFromData('birthdate');
        if($birthdate) return \DateTime::createFromFormat('Y-m-d', $this->getFieldFromData('birthdate'));
        return $birthdate;
    }

    /**
     * @deprecated Just a test function
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    // Protected Methods
    // =========================================================================



    // Private Methods
    // =========================================================================

    /**
     * Update object with data from Vipps
     * @return bool
     */
    private function getDataFromVipps() : bool
    {
        if(!$this->data)
        {
            try {
                /** @var $response \Psr\Http\Message\ResponseInterface */
                $response = VippsLogin::getInstance()->vippsLogin->getUserInfo($this->access_token);
                $this->data = json_decode($response->getBody()->getContents());
                return true;
            } catch (\Exception $e) {
                throw $e;
                return false;
            }
        }
    }

    /**
     * Retrieve a field from the data object
     * @param $field
     * @return mixed|null
     */
    private function getFieldFromData($field)
    {
        if(!$this->data) $this->getDataFromVipps();
        if(!isset($this->data->$field)) return null;
        return $this->data->$field;
    }
}