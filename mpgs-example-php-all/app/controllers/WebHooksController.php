<?php

namespace App\Controllers;
/**
 * Class WebHooksController
 *
 * Controller class to handle all incoming and outgoing routes for processing Webhooks
 * The route mappings are configured in routes.php
 * @package App\Controllers
 */
class WebHooksController
{

    protected $app;
    private $logger;
    private $WEBHOOKS_DIR;
    private $WEBHOOKS_SECRET;

    public function __construct($app)
    {
        $this->app = $app;
        $this->logger = $app['logger'];
        $this->WEBHOOKS_DIR = $app['WEBHOOKS_DIR'];
        $this->WEBHOOKS_SECRET = $app['WEBHOOKS_SECRET'];
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     */
    public function retrieveWebHookNotifications($request, $response, $args)
    {
        //Open the Notifications directory
        $notificationDirectory = opendir($this->WEBHOOKS_DIR);
        $notificationList = [];

        //Build an array from all the file notifications
        while ($file = readdir($notificationDirectory)) {
            $notification = file_get_contents($this->WEBHOOKS_DIR . $file);
            $notificationString = json_decode($notification);

            if ($notification != '' && strlen($notification) > 0) {
                array_push($notificationList, $notificationString);
            }
        }

        //Close Notifications directory
        closedir($notificationDirectory);

        //Return notifications in JSON format
        return json_encode($notificationList, true);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     */
    public function processWebHookNotifications($request, $response, $args)
    {
        $this->logger->warning("processWebHookNotifications ");
        //Create the notification directory if it doesn't exist yet
        if (!file_exists($this->WEBHOOKS_DIR)) {
            mkdir($this->WEBHOOKS_DIR, 0777, true);
        }
        //Parse Notification request and grab the required fields
        $requestBody = $request->getParsedBody();
        $responseArray = [orderId => $requestBody['order']['id'], transactionId => $requestBody['transaction']['id'], orderStatus => $requestBody['result'], amount => $requestBody['order']['amount'], timestamp => $requestBody['order']['creationTime']];

        //Build, open and write the notification to a file
        $webhookFile = 'notification' . microtime(true) . '.json';
        $fp = fopen($this->WEBHOOKS_DIR . $webhookFile, "wb");
        file_put_contents($this->WEBHOOKS_DIR . $webhookFile, json_encode($responseArray));

        fclose($fp);

    }

    /**
     * @return bool
     */
    public
    function clearDirectory()
    {
        $dirname = opendir($this->WEBHOOKS_DIR);

        $dir_handle = opendir($this->WEBHOOKS_DIR);
        if (!$dir_handle)
            return false;
        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file)) {
                    var_dump($file);
                    unlink($dirname . "/" . $file);
                }
            }
        }
        closedir($dir_handle);

        return true;
    }
}