<?php

namespace App\Services;

/**
 * Class MasterPassService
 * @package App\Services
 */
class MasterPassService extends GatewayService
{
    /**
     * @param $request
     * @param $response
     * @param $args
     */
    public function postMasterPass($request, $response, $args)
    {
        $requestBody = $request->getParsedBody();

        //Get sessionId
        $sessionId = $this->getSessionId($request, $response, $args);

        //UpdateWallet
        $gatewayUrl = $this->requestParser->getGatewayUrl(NULL, [sessionId => $sessionId]);
        $updateWalletRequest = $this->updateWalletRequest($requestBody, $gatewayUrl);

        //OpenWallet
        $responseData = $this->openWallet($requestBody, $gatewayUrl, $updateWalletRequest);

        if ($responseData["result"] == 'ERROR') {
            $this->renderReceipt($response, "error.phtml", ["responseStatus" => "DECLINED", "cause" => $responseData["error"]["cause"], "explanation" => $responseData["error"]["explanation"], "orderId" => $_SESSION['orderId'], "orderAmount" => $_SESSION['amount'], "currency" => $_SESSION['currency']]);
        } else {
            $this->renderReceipt($response, "masterpassButton.phtml", ["wallet" => $responseData['wallet']['masterpass']]);
        }

    }

    /**
     * @param $request
     * @param $response
     * @param $args
     */
    public function getMasterpassReceipt($request, $response, $args)
    {
        //Update Session from Masterpass Wallet response
        $updateWalletResponse = $this->updateSessionFromWallet($request);

        if ($updateWalletResponse["result"] == 'ERROR') {
            $this->renderReceipt($response, "error.phtml", ["responseStatus" => "DECLINED", "cause" => $updateWalletResponse["error"]["cause"], "explanation" => $updateWalletResponse["error"]["explanation"], "orderId" => $_SESSION['orderId'], "orderAmount" => $_SESSION['amount'], "currency" => $_SESSION['currency']]);
        } else {
            //Run PAY transaction
            $jsonResponse = $this->pay($response);
        }

    }

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * @param $requestBody
     * @return array
     */
    private function buildUpdateWalletRequest($requestBody)
    {
        $updateWalletRequest = [order => [amount => $requestBody['amount'], currency => $requestBody['currency']]];
        return $updateWalletRequest;
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    private function getSessionId($request, $response, $args)
    {
        $sessionResponse = parent::getCheckOutSession($request, $response, $args);
        $sessionId = $sessionResponse['sessionId'];

        return $sessionId;
    }

    /**
     * @param $requestBody
     * @param $gatewayUrl
     * @return array
     */
    private function updateWalletRequest($requestBody, $gatewayUrl)
    {
        $updateWalletRequest = $this->buildUpdateWalletRequest($requestBody);
        $updateWalletRequest['order']['walletProvider'] = $requestBody['walletProvider'];
        $updateWalletRequest[wallet] = [masterpass => [originUrl => isset($_SERVER['HTTPS']) ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . '/masterpassResponse']];
        $updateResponse = $this->transactionHandler->sendTransaction(json_encode($updateWalletRequest), $gatewayUrl, 'PUT', 'REST');

        return $updateWalletRequest;
    }

    /**
     * @param $requestBody
     * @param $gatewayUrl
     * @param $updateWalletRequest
     * @return mixed
     */
    private function openWallet($requestBody, $gatewayUrl, $updateWalletRequest)
    {
        $openWalletResponse = $this->transactionHandler->sendTransaction(json_encode($updateWalletRequest), $gatewayUrl, 'POST', 'REST');

        $responseData = json_decode($openWalletResponse, true);
        $_SESSION['sessionId'] = $responseData['session']['id'];
        $_SESSION['amount'] = $requestBody['amount'];
        $_SESSION['orderId'] = $requestBody['orderId'];
        $_SESSION['transactionId'] = $requestBody['transactionId'];

        return $responseData;
    }

    /**
     * @param $request
     * @return mixed
     */
    private function updateSessionFromWallet($request)
    {
        $sessionId = $_SESSION['sessionId'];
        $queryParams = $request->getQueryParams();
        $oauthToken = $queryParams['oauth_token'];
        $oauthVerifier = $queryParams['oauth_verifier'];
        $checkoutUrl = $queryParams['checkout_resource_url'];

        $requestBody = [apiOperation => 'UPDATE_SESSION_FROM_WALLET', order => [walletProvider => 'MASTERPASS_ONLINE'], wallet => [masterpass => [oauthToken => $oauthToken, oauthVerifier => $oauthVerifier, checkoutUrl => $checkoutUrl]]];

        $gatewayUrl = $this->requestParser->getGatewayUrl(NULL, [sessionId => $sessionId]);

        $masterpassResponse = $this->transactionHandler->sendTransaction(json_encode($requestBody), $gatewayUrl, 'POST', 'REST');

        $masterpassResponseArray = json_decode($masterpassResponse, true);

        return $masterpassResponseArray;
    }

    /**
     * @return mixed
     */
    private function pay($response)
    {
        $gatewayUrl = $this->requestParser->getGatewayUrl(NULL, [orderId => $_SESSION['orderId'], transactionId => $_SESSION['transactionId']]);
        $requestData = [apiOperation => 'PAY', session => [id => $_SESSION['sessionId']]];
        $jsonRequest = $this->requestParser->formRequestBody($requestData);
        $jsonResponse = $this->transactionHandler->sendTransaction($jsonRequest, $gatewayUrl, 'PUT', 'REST');

        $this->renderReceipt($response, "masterpassReceipt.phtml", ["responseData" => $jsonResponse, "requestData" => $jsonRequest, "requestUrl" => $gatewayUrl, "apiOperation" => "Pay with Masterpass"]);
    }
}

?>
