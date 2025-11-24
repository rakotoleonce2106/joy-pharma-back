<?php

namespace App\Controllers;

/**
 * Class GatewayController
 *
 * Controller class to handle all incoming and outgoing routes
 * The route mappings are configured in routes.php
 *
 * @package App\Controllers
 */
class GatewayController
{
    private $merchant;
    protected $app;
    protected $view;
    protected $gatewayService;
    protected $secure3DService;
    private $logger;
    private static $API_OPERATIONS;
    private static $DEFAULT_VIEW = 'DEFAULT_VIEW';

    /**
     * Retrieve session.js url required for Hosted Session Transactions and render the default transaction page
     * Builds the session.js url required for all transactions that use a Hosted Session and redirects to the Transaction page
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function retrieveHostedSession($request, $response, $args)
    {
        return $this->renderView($request, $response, self::$DEFAULT_VIEW, ["sessionjsurl" => $this->merchant->GetSessionjsurl(), "currency" => $this->merchant->GetCurrency() ])->withStatus(200);
    }

    /**
     * Retrieve the Hosted Checkout Session details to be used for Hosted Checkout transactions and render the transaction page
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function retrieveHostedCheckoutSession($request, $response, $args)
    {
        //Get the Session ID needed for the Hosted Checkout process
        if ($args && $args['successIndicator']) {
            $jsonResponse['successIndicator'] = $args['successIndicator'];
            $jsonResponse['sessionId'] = $args['sessionId'];
            $jsonResponse['sessionVersion'] = $args['sessionVersion'];
        } else {
            $args = ["apiOperation" => "CREATE_CHECKOUT_SESSION", "orderId" => bin2hex(openssl_random_pseudo_bytes(8)), "currency" => $this->merchant->GetCurrency()];
            $jsonResponse = $this->gatewayService->getCheckOutSession($request, $response, $args);
        }
        $jsonResponse['orderId'] = $args['orderId'];
        $jsonResponse['merchantId'] = $this->merchant->GetMerchantId();
        $jsonResponse['checkoutJSUrl'] = $this->merchant->GetCheckoutJSUrl();

        return $this->renderView($request, $response, "hostedCheckout", $jsonResponse)->withStatus(200);
    }

    /**
     * Render the Hosted Checkout Receipt page
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function retrieveHostedReceipt($request, $response, $args)
    {

        $parsedResponse = $this->gatewayService->getHostedTransaction($request, $response, $this->buildRetrieveArgs($args));

        return $this->renderView($request, $response, "hostedCheckoutReceipt", $parsedResponse)->withStatus(200);
    }


    /**
     * Render the Receipt page for Browser Transactions (PAYPAL , UNIONPAY)
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function retrieveBrowserReceipt($request, $response, $args)
    {
        $parsedResponse = $this->gatewayService->getBrowserTransaction($request, $response, $this->buildRetrieveArgs($args));

        return $this->renderView($request, $response, "hostedCheckoutReceipt", $parsedResponse)->withStatus(200);

    }

    /**
     * Render the default Transaction pages
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function retrieveView($request, $response, $args)
    {
        $args['currency'] = $this->merchant->GetCurrency();
        return $this->renderView($request, $response, self::$DEFAULT_VIEW, $args)->withStatus(200);
    }

    /**
     * Render the default Receipt page
     * @param $request
     * @param $response
     * @param $args
     */
    public function retrieveReceipt($request, $response, $args)
    {
        $this->gatewayService->retrieveTransactionResponse($request, $response, $args);

    }

    /**
     * Submits the Transaction data to the Gateway via the GatewayService in JSON Format
     * @param $request
     * @param $response
     * @param $args
     */
    public function postTransaction($request, $response, $args)
    {
        $this->gatewayService->postTransaction($request, $response, $args);
    }

    /**
     * Submits the Transaction data to the Gateway via the GatewayService in JSON Format
     * @param $request
     * @param $response
     * @param $args
     */
    public function check3DEnrollment($request, $response, $args)
    {
        $this->secure3DService->check3DEnrollment($request, $response, $args);
    }

    /**
     * Submits the Transaction data to the Gateway via the GatewayService in JSON Format
     * @param $request
     * @param $response
     * @param $args
     */
    public function post3DTransaction($request, $response, $args)
    {
        $this->secure3DService->process3DSecure($request, $response, $args);
    }


    /**
     * Submits the NVP Transaction data to the Gateway via the GatewayService
     * @param $request
     * @param $response
     * @param $args
     */
    public function postNVPTransaction($request, $response, $args)
    {
        $this->gatewayService->postNVPTransaction($request, $response, $args);
    }

    /**
     * Submits Browser Transaction data to the Gateway via the GatewayService
     * @param $request
     * @param $response
     * @param $args
     */
    public function postBrowserTransaction($request, $response, $args)
    {
        $this->gatewayService->postBrowserTransaction($request, $response, $args);
    }


    public function __construct($app)
    {
        $this->merchant = $app['Merchant'];
        $this->gatewayService = $app['GatewayService'];
        $this->secure3DService = $app['Secure3DService'];
        $this->app = $app;
        $this->view = $app['renderer'];
        $this->logger = $app['logger'];

        self::$API_OPERATIONS = $app['apiOperations'];
    }

    /**
     * Render the default transaction
     * @param $request
     * @param $response
     * @param $path
     * @param $args
     * @return mixed
     */
    private function renderView($request, $response, $path, $args)
    {
        $urlPath = ltrim($request->getUri()->getPath(), '/');

        $path = ($path === self::$DEFAULT_VIEW || $path === NULL) ? ($urlPath ? $urlPath : 'pay') : $path;

        if ($urlPath === 'secure3d') {
            $args['redirectUrlEndpoint'] = 'process3ds';
        }
        return $this->view->render($response, $path . ".phtml", $args);
    }

    private function buildRetrieveArgs($args)
    {
        return ["apiOperation" => 'RETRIEVE_ORDER', "orderId" => $args['orderId'], "transactionId" => $args['transactionId']];
    }
}

?>