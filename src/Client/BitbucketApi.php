<?php

namespace Bitbucket\Client;

use Bitbucket\Exceptions\BitbucketApiReturn401Exception;
use \Bitbucket\Exceptions\BitbucketApiReturnErrorException;
use \Bitbucket\Exceptions\BitbucketApiReturnUnknownException;

class BitbucketApi
{

    protected $clientId;
    protected $secret;
    protected $token;
    protected $refresh;
    protected $expiresIn;

    /**
     * BitbucketApi constructor.
     * @param string $clientId
     * @param string $secret
     * @param null|string $token
     * @param null|string $refresh
     * @param null|int $expiresIn
     * @throws \Exception
     */
    public function __construct($clientId, $secret, $token = null, $refresh = null, $expiresIn = null)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;

        if ($token === null ||
            $refresh === null ||
            $expiresIn === null) {

            try {
                $this->setToken();
            }
            //Rethrow
            catch (\Exception $exception) {
                throw $exception;
            }
        }
        //already have tokens, set class properties
        else {
            $this->token = $token;
            $this->refresh = $refresh;
            $this->expiresIn = $expiresIn;
        }
    }


    /**
     * Attempts to retrieve a new token using the specified client id and secret.
     * @throws \Exception
     */
    protected function setToken()
    {
        try {
            $response = $this->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
                ['grant_type' => 'client_credentials'], false);

            $responseDecode = json_decode($response['body'], true);

            $this->token = $responseDecode['access_token'];
            $this->refresh = $responseDecode['refresh_token'];
            $this->expiresIn = $responseDecode['expires_in'];

        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Attempts to retrieve new token using the current refresh token.
     * @throws \Exception
     */
    public function getTokenFromRefresh()
    {
        try {
            $response = $this->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
                ['grant_type=refresh_token', 'refresh_token=' . $this->refresh], false);

            $responseDecode = json_decode($response['body'], true);

            $this->token = $responseDecode['access_token'];
            $this->refresh = $responseDecode['refresh_token'];
            $this->expiresIn = $responseDecode['expires_in'];

        } catch (\Exception $exception) {
            throw $exception;
        }

    }

    /**
     * Send curl post request
     * @param string $url
     * @param array $postData
     * @param bool $useToken
     * @return array|bool|string
     * @throws \Exception
     */
    public function sendCurlPost($url, $postData = [], $useToken = true)
    {
        $curlOpt = [
            CURLOPT_POST => true
        ];

        //Set variable CURLOPT's
        $curlOpt[CURLOPT_USERPWD] = (!$useToken? $this->clientId . ':' . $this->secret : null);
        $curlOpt[CURLOPT_POSTFIELDS] = $postData? $postData : null;
        //Set barer token if available
        $headers = ($useToken? ['Authorization: Bearer ' . $this->token]:[]);


        try {
            return $this->sendCurl($url, $curlOpt, $headers);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Send a curl get request
     * @param string $url
     * @param bool $useToken
     * @return array
     * @throws \Exception
     */
    public function sendCurlGet($url, $useToken = true)
    {
        $curlOpt = [];
        $curlOpt[CURLOPT_USERPWD] = (!$useToken? $this->clientId . ':' . $this->secret : null);
        $headers = ($useToken !== null? ['Authorization: Bearer ' . $this->token]:[]);

        try {
            return $this->sendCurl($url, $curlOpt, $headers);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Sends a curl request to the specified endpoint, CURLOPT_RETURNTRANSFER always set to true.
     * @param string $url
     * @param array $curlOpts
     * @param array $headers
     * @return array|bool|string
     * @throws \Exception
     * @throws BitbucketApiReturn401Exception
     * @throws BitbucketApiReturnErrorException
     * @throws BitbucketApiReturnUnknownException
     */
    protected function sendCurl($url, $curlOpts = [], $headers = [])
    {
        $baseCurlOPts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        $baseCurlOPts[CURLOPT_HTTPHEADER] = $headers;

        //array_merge does not preserve keys
        $curlOpts = $curlOpts + $baseCurlOPts;

        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception('Curl error when connecting to: '
                . $curlOpts[CURLOPT_URL] . '. With error:' . curl_error($curl));
        }

        $response = [
            'http_status_code' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
            'body' => $response
        ];

        //Done with curl connection, close
        curl_close($curl);

        if ($response['http_status_code'] == 200) {
            return $response;
        }
        else if ($response['http_status_code'] == 401) {
            throw new BitbucketApiReturn401Exception();
        }
        else if ($response['http_status_code'] >= 400) {
            throw new BitbucketApiReturnErrorException($response['http_status_code']);
        }
        //Catch all
        else {
            throw new BitbucketApiReturnUnknownException($response['http_status_code']);
        }
    }
}