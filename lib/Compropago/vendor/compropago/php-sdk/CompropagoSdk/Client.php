<?php
/**
 * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
 */

namespace CompropagoSdk;


class Client
{
    const VERSION="4.0.0.0";
    const API_LIVE_URI='http://api.compropago.com/v1/';
    const API_SANDBOX_URI='http://api.compropago.com/v1/';

    public $publickey;
    public $privatekey;
    public $live;
    public $deployUri;
    public $api;

    /**
     * Client constructor.
     * @param $publickey
     * @param $privatekey
     * @param $live
     */
    public function __construct($publickey, $privatekey, $live)
    {
        $this->publickey = $publickey;
        $this->privatekey = $privatekey;
        $this->live = $live;

        $this->deployUri = ($live === true) ? self::API_LIVE_URI : self::API_SANDBOX_URI;

        $this->api = new Service($this);
    }

    /**
     * Return the User for API
     * @return mixed
     */
    public function getUser()
    {
        return $this->privatekey;
    }

    /**
     * Return the Password for API
     * @return mixed
     */
    public function getPass()
    {
        return $this->publickey;
    }
}