<?php

use Slim\Http\Request;
use Slim\Http\Response;

//Transactions using JSON format without Hosted Sesssion
$routes = [
    '/capture',
    '/refund',
    '/void',
    '/initiate',
    '/confirm',
    '/update',
    '/retrieve',
    '/verify',
    '/paypal',
    '/unionpay',
    '/masterpass',
    '/webhook'
];
//Transactions that use Hosted Session
$hostedRoutes = [
    '/authorize',
    '/pay',
    '/nvpPay',
    '/secure3d'
];
//Transactions that use NVP protocol
$nvpRoutes = [
    '/nvpPay'
];

//Default View - Landing page
$app->get('/', 'GatewayController:retrieveHostedSession')->setName('/pay');

//Handle Get routes to display the Transaction pages
foreach ($routes as $route) {
    $app->get($route, 'GatewayController:retrieveView');
}

//Handle Hosted Session Transaction pages
foreach ($hostedRoutes as $route) {
    $app->get($route, 'GatewayController:retrieveHostedSession')->setName($route);
}

//Get the Receipt for Browser Payment Receipts
$app->get('/browserPaymentReceipt/order/{orderId}/transaction/{transactionId}', 'GatewayController:retrieveBrowserReceipt');

//Display the Hosted Checkout transaction page
$app->get('/hostedCheckout', 'GatewayController:retrieveHostedCheckoutSession');

//Display the Receipt for Hosted Checkout transaction
$app->get('/hostedCheckout/{orderId}/{result}', 'GatewayController:retrieveHostedReceipt');

$app->get('/hostedCheckout/{orderId}/{successIndicator}/{sessionId}', 'GatewayController:retrieveHostedCheckoutSession');


//Retrieve WebHook notifcations
$app->get('/webhookNotifications', 'WebHooksController:retrieveWebHookNotifications');

//Retrieve WebHook notifcations
$app->post('/process-webhook', 'WebHooksController:processWebHookNotifications');

//Clear WebHook notifcations
$app->get('/clear-webhook', 'WebHooksController:clearDirectory');

$app->put('/hostedReceipt', 'GatewayController:postTransaction');

//Handle Retrieve Transaction
$app->post('/retrieve', 'GatewayController:retrieveReceipt');

foreach ($nvpRoutes as $route) {
    $app->put($route, 'GatewayController:postNVPTransaction');
}

//Handle generic post transactions and display the receipt page
$app->map(['put', 'post'], '/receipt', 'GatewayController:postTransaction');

//Display the 3DEnrollement Check form
$app->put('/secureIdPayerAuthenticationForm', 'GatewayController:check3DEnrollment');

//Process 3DSecure post transaction and display the receipt page
$app->post('/process3ds', 'GatewayController:post3DTransaction');

//Process Browser Transactions- PAYPAL , UNIONPAY
$app->post('/processBrowserPayment', 'GatewayController:postBrowserTransaction');

//Process Masterpass
$app->post('/masterpass', 'MasterpassController:postMasterPass');

//Process Masterpass
$app->get('/masterpassResponse', 'MasterpassController:getMasterpassReceipt');




