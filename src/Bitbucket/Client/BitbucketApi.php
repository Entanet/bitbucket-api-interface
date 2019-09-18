<?php

namespace Bitbucket\Client;

use \Bitbucket\Exceptions\BitbucketApiReturnErrorException;
use \Bitbucket\Exceptions\BitbucketApiReturnUnknownException;

class BitbucketApi
{

    protected $clientId;
    protected $secret;
    protected $token;
    protected $refresh;
    protected $expiresIn;

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
    }

    protected function setToken()
    {
        $response = $this->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
            ['grant_type' => 'client_credentials'], false);

        //catch all error
        if ($response['http_status_code'] >= 400) {
            throw new BitbucketApiReturnErrorException($response['http_status_code']);
        }
        else if ($response['http_status_code'] != 200) {
            throw new BitbucketApiReturnUnknownException($response['http_status_code']);
        }
        else {
            $responseDecode = json_decode($response['body'], true);

            $this->token = $responseDecode['access_token'];
            $this->refresh = $responseDecode['refresh_token'];
            $this->expiresIn = $responseDecode['expires_in'];
        }
    }

    public function getTokenFromRefresh()
    {
        $response = $this->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
            ['grant_type=refresh_token', 'refresh_token='.$this->refresh], false);

        if ($response['http_status_code'] == 200) {
            $responseDecode = json_decode($response['body'], true);

            $this->token = $responseDecode['access_token'];
            $this->refresh = $responseDecode['refresh_token'];
            $this->expiresIn = $responseDecode['expires_in'];
        }
        else if ($response['http_status_code'] >= 400) {
            throw new BitbucketApiReturnErrorException($response['http_status_code']);
        }
        else {
            throw new BitbucketApiReturnUnknownException($response['http_status_code']);
        }
    }

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


        return $this->sendCurl($url, $curlOpt, $headers);
    }

    public function sendCurlGet($url, $useToken = true)
    {
        $curlOpt = [];
        $curlOpt[CURLOPT_USERPWD] = (!$useToken? $this->clientId . ':' . $this->secret : null);
        $headers = ($useToken !== null? ['Authorization: Bearer ' . $this->token]:[]);

        return $this->sendCurl($url, $curlOpt, $headers);
    }

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

        curl_close($curl);
        return $response;
    }
}