<?php

namespace App\Services;

/**
 * Class GatewayService
 * Service class
 * @package App\Services
 */
class GatewayService
{
    protected $merchant;
    protected $app;
    protected $view;
    protected $apiOperations;
    protected $transactionHandler;
    public $requestParser;
    protected $responseParser;
    static $DEFAULT_RECEIPT = 'receipt.phtml';

    /**
     * Post JSON transaction to the Gateway , retrieve the response and  render the receipt page
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postTransaction($request, $response, $args)
    {
        $transactionParameters = $this->requestParser->parseTransactionParams($request, $args);
        $jsonRequest = $this->requestParser->formRequestBody($transactionParameters['requestBody']);
        $jsonResponse = $this->transactionHandler->sendTransaction($jsonRequest, $transactionParameters['gatewayUrl'], 'PUT', $transactionParameters['requestBody']["protocol"]);
        $this->renderReceipt($response, self::$DEFAULT_RECEIPT, ["responseData" => $jsonResponse, "requestData" => $jsonRequest, "requestUrl" => $transactionParameters['gatewayUrl'], "apiOperation" => $transactionParameters['apiOperation']]);
    }

    /**
     * Post NVP transaction to the Gateway, retrieve the response and render the receipt page
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postNVPTransaction($request, $response, $args)
    {
        $transactionParameters = $this->requestParser->parseTransactionParams($request, $args);
        $requestString = $this->requestParser->buildNVPPayRequest($transactionParameters['requestBody']);
        $jsonResponse = $this->transactionHandler->sendTransaction($requestString, $transactionParameters['gatewayUrl'], 'POST', 'NVP');

        $this->renderReceipt($response, self::$DEFAULT_RECEIPT, ["responseData" => $jsonResponse, "requestData" => $requestString, "requestUrl" => $transactionParameters['gatewayUrl'], "apiOperation" => $transactionParameters['apiOperation']]);
    }


    /**
     * POST Browser Transactions - PAYPAL and UNION PAY
     *
     * @param $request
     * @param $response
     * @param $args
     */
    public function postBrowserTransaction($request, $response, $args)
    {
        $transactionParameters = $this->requestParser->parseTransactionParams($request, $args);
        $requestBody = $transactionParameters['requestBody'];
        $returnUrl = $this->requestParser->getBrowserPaymentReturnUrl($requestBody);
        $sourceOfFunds = $requestBody['sourceOfFunds']['type'];

        $requestData = [
            apiOperation => $requestBody['apiOperation'],
            order => [
                "amount" => $requestBody['transaction']['amount'],
                "currency" => $requestBody['transaction']['currency']
            ],
            sourceOfFunds => [
                type => $sourceOfFunds
            ]
        ];

        $browserPayment = [operation => "PAY", returnUrl => $returnUrl];

        if ($sourceOfFunds === 'PAYPAL') {
            $browserPayment[paypal] = [paymentConfirmation => "CONFIRM_AT_PROVIDER"];
        }

        $requestData[browserPayment] = $browserPayment;

        $jsonRequest = json_encode($requestData);

        $jsonResponse = $this->transactionHandler->sendTransaction($jsonRequest, $transactionParameters['gatewayUrl'], 'PUT');


        $parsedResponse = json_decode($jsonResponse, true);

        $redirectUrl = "Location: " . $parsedResponse['browserPayment']['redirectUrl'];

        //Redirect to the Browser Payment redirectURL and exit
        header($redirectUrl);

        exit;
    }

    public function getHostedTransaction($request, $response, $args)
    {
        $jsonResponse = $this->getTransactionResponse($request, $args);
        $response = $this->responseParser->parseHostedPaymentResponse($jsonResponse);
        return $response;
    }

    public function getBrowserTransaction($request, $response, $args)
    {
        $jsonResponse = $this->getTransactionResponse($request, $args);
        $response = $this->responseParser->parseBrowserPaymentResponse($jsonResponse);
        return $response;
    }

    public function retrieveTransactionResponse($request, $response, $args)
    {
        $requestBody = $request->getParsedBody();
        $gatewayUrl = $this->requestParser->getGatewayUrl($requestBody, $args);
        $jsonResponse = $this->transactionHandler->getTransactionResponse($gatewayUrl);

        $this->renderReceipt($response, self::$DEFAULT_RECEIPT, ["responseData" => $jsonResponse, "requestData" => json_encode($requestBody, JSON_PRETTY_PRINT), "requestUrl" => $gatewayUrl, "apiOperation" => $requestBody['apiOperation']]);
    }


    public function getCheckOutSession($request, $response, $args)
    {

        $formRequestBody = function () use ($args) {
            $requestData = ['apiOperation' => $args['apiOperation'], 'order' => [
                "id" => $args["orderId"],
                "currency" => $args["currency"]
            ]];
            $jsonRequest = json_encode($requestData);

            return $jsonRequest;
        };

        $jsonRequest = $formRequestBody($args);

        $jsonResponse = $this->transactionHandler->sendTransaction($jsonRequest, $this->merchant->GetCheckoutSessionUrl(), 'POST', 'REST');

        $parsedResponse = json_decode($jsonResponse, true);

        $response = [sessionId => $parsedResponse['session']['id'], sessionVersion => $parsedResponse['session']['version'], successIndicator => $parsedResponse['successIndicator']];

        return $response;
    }

    public function __construct($app)
    {
        $this->merchant = $app['Merchant'];
        $this->app = $app;
        $this->view = $app['renderer'];
        $this->apiOperations = $app['apiOperations'];
        $this->transactionHandler = $app['TransactionHandler'];
        $this->requestParser = $app['RequestParser'];
        $this->responseParser = $app['ResponseParser'];

    }

    protected function renderReceipt($response, $receipt, $args)
    {
        $response = $this->view->render($response, $receipt, $args);

        return $response->withStatus(200);
    }

    private function getTransactionResponse($request, $args)
    {
        $requestBody = $request->getParsedBody();
        $gatewayUrl = $this->requestParser->getGatewayUrl($requestBody, $args);
        $jsonResponse = $this->transactionHandler->getTransactionResponse($gatewayUrl);
        return json_decode($jsonResponse, true);
    }

}

?>