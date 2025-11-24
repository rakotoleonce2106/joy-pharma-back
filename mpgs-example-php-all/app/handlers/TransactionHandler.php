<?php

namespace App\Handlers;

/**
 * Class TransactionHandler
 * Responsible for handling the CURL operations to/from the gateway
 *
 * @package App\Handlers
 */
class TransactionHandler
{
    private $merchant;
    protected $app;
    private static $NVP_CONTENT_TYPE = array("Content-Type: application/x-www-form-urlencoded;charset=UTF-8");
    private static $JSON_CONTENT_TYPE = array("Content-Type: Application/json;charset=UTF-8");


    public function __construct($app)
    {
        $this->merchant = $app['Merchant'];
        $this->app = $app;
    }

    /**
     * Handles CURL PUT/POST transaction to the Gateway for REST and NVP
     *
     * @param $requestData
     * @param $gatewayUrl
     * @param $transactionType
     * @param $protocol
     * @return mixed|string
     */
    public function sendTransaction($requestData, $gatewayUrl, $transactionType, $protocol = 'REST')
    {
        $curlObj = $this->initCurlObj($gatewayUrl);

        $http_response_header = ($protocol === 'NVP') ? self::$NVP_CONTENT_TYPE : self::$JSON_CONTENT_TYPE;
        curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, $transactionType);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, array("Content-Length: " . strlen($requestData)));
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, $http_response_header);

        $jsonResponse = curl_exec($curlObj);

        if (curl_error($curlObj))
            $jsonResponse = "CURL Error: " . curl_errno($curlObj) . " - " . curl_error($curlObj) . "-" . curl_getinfo($curlObj);

        curl_close($curlObj);

        //pretty print if json response
        if ($protocol === 'NVP') return $jsonResponse;
        else
            return $this->prettify($jsonResponse);
    }

    /**
     * Handles the CURL GET transaction
     *
     * @param $gatewayUrl
     * @return mixed|string
     */
    public function getTransactionResponse($gatewayUrl)
    {
        $curlObj = $this->initCurlObj($gatewayUrl);

        curl_setopt($curlObj, CURLOPT_HTTPGET, 1);
        curl_setopt($curlObj, CURLOPT_URL, $gatewayUrl);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);

        $jsonResponse = curl_exec($curlObj);

        if (curl_error($curlObj))
            $jsonResponse = "CURL Error: " . curl_errno($curlObj) . " - " . curl_error($curlObj) . "-" . curl_getinfo($curlObj);

        curl_close($curlObj);

        //pretty print json response
        return $this->prettify($jsonResponse);
    }

    private function prettify($jsonResponse)
    {
        return json_encode(json_decode($jsonResponse), JSON_PRETTY_PRINT);
    }

    private function initCurlObj($gatewayUrl)
    {
        $curlObj = curl_init();

        curl_setopt($curlObj, CURLOPT_URL, $gatewayUrl);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);

        if ($this->merchant->isCertificateAuth()) {
            curl_setopt($curlObj, CURLOPT_SSLCERT, $this->merchant->GetCertificatePath());
            curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, $this->merchant->GetCertificateVerifyHost());
            curl_setopt($this->curlObj, CURLOPT_SSL_VERIFYPEER, $this->merchant->isCertificateVerifyPeer());
        } else {
            curl_setopt($curlObj, CURLOPT_USERPWD, $this->merchant->GetApiUsername() . ":" . $this->merchant->GetPassword());
        }

        return $curlObj;
    }
}

?>