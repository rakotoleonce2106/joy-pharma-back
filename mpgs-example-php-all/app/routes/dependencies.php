<?php
//DIC configuration
$container = $app->getContainer();
//view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};
//monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};
//service
$container['GatewayService'] = function ($app) {
    return new \App\Services\GatewayService($app);
};
$container['MasterPassService'] = function ($app) {
    return new \App\Services\MasterPassService($app);
};
$container['Secure3DService'] = function ($app) {
    return new \App\Services\Secure3DService($app);
};
//controller
$container['GatewayController'] = function ($app) {
    return new \App\Controllers\GatewayController($app);
};
$container['WebHooksController'] = function ($app) {
    return new \App\Controllers\WebHooksController($app);
};
$container['MasterpassController'] = function ($app) {
    return new \App\Controllers\MasterpassController($app);
};
//merchant
$container['Merchant'] = function ($c) {
    $settings = $c->get('settings')['configArray'];
    return new \App\Models\Merchant($settings);
};
//handler
$container['TransactionHandler'] = function ($app) {
    return new \App\Handlers\TransactionHandler($app);
};
//parsers
$container['RequestParser'] = function ($app) {
    return new \App\Parsers\RequestParser($app);
};
$container['ResponseParser'] = function ($app) {
    return new \App\Parsers\ResponseParser($app);
};

//Map routes to apiOperation - required for all Gateway Transactions
$container['apiOperations'] = function ($c) {
    $apiOperations = [
        'authorize' => 'AUTHORIZE',
        'capture' => 'CAPTURE',
        'confirm' => 'CONFIRM_BROWSER_PAYMENT',
        'initiate' => 'INITIATE_BROWSER_PAYMENT',
        'pay' => 'PAY',
        'refund' => 'REFUND',
        'retrieve' => 'RETRIEVE_TRANSACTION',
        'paypal' => 'INITIATE_BROWSER_PAYMENT',
        'unionpay' => 'INITIATE_BROWSER_PAYMENT',
        'verify' => 'VERIFY',
        'void' => 'VOID',
        'update' => 'UPDATE_AUTHORIZATION',
        'secure3d' => 'CHECK_3DS_ENROLLMENT'];

    return $apiOperations;
};

$container['WEBHOOKS_DIR'] = function ($c) {
    return $_SERVER['DOCUMENT_ROOT'] . '/notifications/';
};

$container['WEBHOOKS_SECRET'] = getenv('WEBHOOKS_SECRET');



