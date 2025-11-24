<?php


namespace App\Controllers;

/**
 * Class MasterpassController
 *
 * Controller class to handle all incoming and outgoing routes for Masterpass transactions
 * The route mappings are configured in routes.php
 * @package App\Controllers
 */
class MasterpassController
{
    private $merchant;
    protected $app;
    protected $view;
    protected $masterpassService;
    private $logger;
    private static $API_OPERATIONS;
    private static $DEFAULT_VIEW = 'DEFAULT_VIEW';

    public function __construct($app)
    {
        $this->merchant = $app['Merchant'];
        $this->masterpassService = $app['MasterPassService'];
        $this->app = $app;
        $this->view = $app['renderer'];
        $this->logger = $app['logger'];

        self::$API_OPERATIONS = $app['apiOperations'];
    }

    /**
     * Submits the Masterpass form
     * @param $request
     * @param $response
     * @param $args
     */
    public function postMasterPass($request, $response, $args)
    {
        $this->masterpassService->postMasterPass($request, $response, $args);
    }


    /**
     * Parse Masterpass response and display receipt
     * @param $request
     * @param $response
     * @param $args
     */
    public function getMasterpassReceipt($request, $response, $args)
    {
        $this->masterpassService->getMasterpassReceipt($request, $response, $args);
    }
}