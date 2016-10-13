<?php

namespace CryptoCoinJar\CryptoCore\Coins;
use GuzzleHttp\Client;

class ZCash {
    
    //ports - mainnet: 8232 or testnet: 18232
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;
    private $verifySSL;
    private $useTestNet;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;
    
    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    function __construct($username, $password, $host = 'localhost', $port = 8232, $url = null) {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;

        if (!$this->useTestNet) {
            $this->port = $port;
        } else {
            $this->port = 18232;
        }

        $this->url           = $url;

        // Set some defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
        $this->verifySSL = false;
    }


    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null) {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    /**
     * @param $useTestNet
     * toggle the use of the testnet
     */
    function setTestNet($useTestNet) {
        $this->useTestNet = $useTestNet;
    }

    function __call($method, $params)
    {
        $this->status = null;
        $this->error = null;
        $this->raw_response = null;
        $this->response = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = array(
            'method' => $method,
            'params' => $params,
            'id' => $this->id
        );

        //create new instance of GuzzleHttp Client
        $client = new Client([

            // Base URI is used with relative requests
            'base_uri' => "{$this->proto}://{$this->host}:{$this->port}/",

            // You can set any number of default request options.
            'timeout'  => 2.0

        ]);


        //todo: HTTPS implementation
        $this->raw_response = $client->post("{$this->url}", [ 'auth' => [$this->username, $this->password], 'json' => $request]);

        //get the status
        $this->status = $this->raw_response->getStatusCode();

        //decode the response
        $this->response = json_decode($this->raw_response, TRUE);



        if ($this->response['error']) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
           // If bitcoind didn't return a nice error message, return the reason phrase
            return $this->raw_response->getReasonPhrase();
        }

        if ($this->error) {
            return FALSE;
        }

        return $this->response['result'];

    }

}
