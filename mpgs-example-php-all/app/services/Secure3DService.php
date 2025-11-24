<?php


namespace App\Services;

/**
 * Class Secure3DService
 * @package App\Services
 */
class Secure3DService extends GatewayService
{

    /**
     * Check if the Card scheme is enrolled for 3DS
     * @param $request
     * @param $response
     * @param $args
     */
    public function check3DEnrollment($request, $response, $args)
    {
        //Generate a random secureId
        $args['secureId'] = 'secure3d-' . rand();

        //Parse Request
        $transactionParameters = $this->requestParser->parseTransactionParams($request, $args);
        $requestBody = $transactionParameters['requestBody'];
        $jsonRequest = $this->requestParser->formRequestBody($requestBody);

        //Save variables in Session - Needed in the next step to process 3D Authorize after enrollment check
        $this->saveSessionValues($requestBody, $args);

        $jsonResponse = $this->transactionHandler->sendTransaction($jsonRequest, $transactionParameters['gatewayUrl'], 'PUT', $transactionParameters['requestBody']["protocol"]);

        $this->parseEnrollmentResponse($jsonResponse, $response);
    }

    /**
     * Processing 3DSecure involves:
     * Processing the ACS Result from the Enrollment Check
     * Run Authorize
     * @param $request
     * @param $response
     * @param $args
     */
    public function process3DSecure($request, $response, $args)
    {
        //Process ACS Result
        $this->processACSResult($request);

        //Pay or AUTHORIZE
        $this->processPayment($args, $response);
    }

    public function __construct($app)
    {
        parent::__construct($app);
    }

    private function saveSessionValues($requestBody, $args)
    {
        $_SESSION['secureId'] = $args['secureId'];
        $_SESSION['sessionId'] = $requestBody["sessionId"];
        $_SESSION['orderId'] = $requestBody['orderId'];
        $_SESSION['amount'] = $requestBody['amount'];
        $_SESSION['transactionId'] = $requestBody['transactionId'];
        $_SESSION['currency'] = $requestBody['currency'];
    }

    private function parseEnrollmentResponse($jsonResponse, $response)
    {
        $redirectUrl = function () {
            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            return $protocol . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '/process3ds';
        };

        $parsedResponse = json_decode($jsonResponse, true);
        $enrollment_status = $parsedResponse['3DSecure']["summaryStatus"];

        if ($enrollment_status === 'CARD_ENROLLED') {
            //Response from Enrollment Check needed for Processing 3D Authorize
            $acsUrl = $parsedResponse['3DSecure']['authenticationRedirect']['customized']['acsUrl'];
            $PaReq = $parsedResponse['3DSecure']['authenticationRedirect']['customized']['paReq'];
            $responseUrl = $redirectUrl();
            $mdValue = '$mdValue';

            $this->renderReceipt($response, "secureIdPayerAuthenticationForm.phtml", ["acsUrl" => $acsUrl, "PaReq" => $PaReq, "responseUrl" => $responseUrl, "mdValue" => $mdValue]);
        } else {
            $this->renderReceipt($response, "error.phtml", ["responseStatus" => "DECLINED", "cause" => $parsedResponse["error"]["cause"], "explanation" => $parsedResponse["error"]["explanation"], "orderDescription" => $enrollment_status, "orderId" => $_SESSION['orderId'], "orderAmount" => $_SESSION['amount'], "currency" => $_SESSION['currency']]);
        }
    }

    private function processACSResult($request)
    {
        $requestBody = $request->getParsedBody();
        $apiOperation = 'PROCESS_ACS_RESULT';
        $requestBody['apiOperation'] = $apiOperation;
        $args['secureId'] = $_SESSION['secureId'];
        $args['sessionId'] = $_SESSION['sessionId'];

        $gatewayUrl = $this->requestParser->getGatewayUrl($requestBody, $args);

        $requestData = [apiOperation => $apiOperation, '3DSecure' => [
            "paRes" => $requestBody["PaRes"],
        ]];
        $requestData = json_encode($requestData, JSON_PRETTY_PRINT);

        $this->transactionHandler->sendTransaction($requestData, $gatewayUrl, 'POST');
    }


    private function processPayment($args, $response)
    {
        $requestBody = [orderId => $_SESSION['orderId'], transactionId => $_SESSION['transactionId']];
        $gatewayUrl = $this->requestParser->getGatewayUrl($requestBody, []);
        $secureId = $args['secureId'];
        //Get PAYMENT_OPTIONS_INQUIRY to retrieve the transactionMode for the Merchant, use this to determine the apiOperation for the transaction
        $apiOperation = $this->getTransactionMode();

        $requestForward = ['3DSecureId' => $secureId, apiOperation => $apiOperation, order => [amount => $_SESSION['amount'], currency => $_SESSION['currency']], session => [id => $_SESSION['sessionId']]];

        $jsonResponse = $this->transactionHandler->sendTransaction(json_encode($requestForward), $gatewayUrl, 'PUT');

        $this->renderReceipt($response, self::$DEFAULT_RECEIPT, ["responseData" => json_encode(json_decode($jsonResponse), JSON_PRETTY_PRINT), "requestData" => json_encode($requestForward, JSON_PRETTY_PRINT), "requestUrl" => $gatewayUrl, "apiOperation" => 'AUTHORIZE']);
    }

    /**
     * Get the TransactionMode for the current Merchant
     * @return string
     */
    private function getTransactionMode()
    {
        $gatewayUrl = $this->requestParser->getGatewayUrl(['apiOperation' => 'PAYMENT_OPTIONS_INQUIRY']);
        $jsonResponse = $this->transactionHandler->getTransactionResponse($gatewayUrl);
        $responseArray = json_decode($jsonResponse, true);
        $transactionMode = $responseArray['transactionMode'];
        if ($transactionMode === 'PURCHASE')
            return 'PAY';
        else
            return 'AUTHORIZE';

    }

}