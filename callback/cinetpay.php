<?php

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$cinetpay = basename(__FILE__, '.php');

$gatewayParams = getGatewayVariables($cinetpay);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}


$transaction_id = $_POST["cpm_trans_id"];
$site_id = $_POST["cpm_site_id"];


$api_key = $gatewayParams['apikey'];
$site_key = $gatewayParams['site_id'];

$verif_site= $site_id == $site_key;

$invoiceId = explode("-MDG", $transaction_id)[0];


$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);


checkCbTransID($transaction_id);

if ($verif_site) {

    $postfields = [
        'apikey' => $api_key,
        'transaction_id' => $transaction_id,
        'site_id' =>$site_key
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-checkout.cinetpay.com/v2/payment/check');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $transactionInformation = json_decode($response);    
    $status = $transactionInformation->message;
	
    logTransaction($gatewayParams['name'], $response, $status);

    if($status=="SUCCES"){
    addInvoicePayment(
        $invoiceId,
        $transaction_id,
        0,
        0,
        $cinetpay
    );
    }
}
