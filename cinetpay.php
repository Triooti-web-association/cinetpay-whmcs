<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function cinetpay_MetaData()
{
    return array(
        'DisplayName' => 'CinetPay',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function cinetpay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'CinetPay  FLOOZ / TMONEY / CARTE VISA',
        ),
        // a text field type allows for single line text input
        'apikey' => array(
            'FriendlyName' => "Entrer l'API KEY",
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => "Entrer votre APIKEY <br>: L'APikEY du fournisseur <b>$callBackUrl</b> dans votre champs callback dans le menu Profil du CinetPay",
        ),
        'site_id' => array(
            'FriendlyName' => "Entrer le site ID",
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => "Entrer votre ID de site de <br>Copier le lien de votre site:  <b>$callBackUrl</b> dans votre champs callback dans le menu Profil du CinetPay",
        ),
        'channels' => array(
            'FriendlyName' => 'Quel transaction à prendre en compte ?',
            'Type' => 'dropdown',
            'Options' => array(
                'ALL' => 'Tous',
                'MOBILE_MONEY' => 'Mobile Money',
                'CREDIT_CARD' => 'Carte de credit',
                'WALLET' => 'Wallet blockchain',
            ),
            'Description' => 'Choississez la transaction à tenir en compte',
        ),
    	'fees' => array(
            'FriendlyName' => 'Frais de retrait',
            'Type' => 'yesno',
            'Description' => 'Ajouter un frais de retrait supplémentaire ?',
        ),
       
    );
}


 function _toIntN($str)
    {
        return (int)preg_replace("/([^0-9\\.])/i", "", $str);
    }

    

function paymentFeesN($amount)
{
	$fee = 150;
    if ($amount >= 200 && $amount <= 5000) {
 		$fee = 150;
 	} elseif ($amount > 5000 && $amount <= 20000) {
    	$fee = 450;
 	} elseif ($amount > 20000 && $amount <= 50000) {
 		$fee = 900;
 	}elseif ($amount > 50000 && $amount <= 100000) {
 		$fee = 1800;
 	}elseif ($amount > 100000 && $amount <= 200000) {
 		$fee = 3600;
 	}elseif ($amount > 200000 && $amount <= 500000) {
 		$fee = 4500;
 	}elseif ($amount > 500000 && $amount <= 850000) {
 		$fee = 5000;
 	}elseif ($amount > 850000 && $amount <= 1000000) {
 		$fee = 7000;
 	}elseif ($amount > 1000000 && $amount <= 1500000) {
 		$fee = 9000;
 	}elseif ($amount > 1500000 && $amount <= 2000000) {
 		$fee = 11000;
 	}
	return $fee;
}

function cinetpay_link($params)
{
    // Gateway Configuration Parameters
    $apikey = $params['apikey'];
    $siteId = $params['site_id'];
    $channels = $params['channels'];
    $fees =$params['fees'];
    // Invoice Parameters
    $transaction_id = $params['invoiceid'].'-MDG'.uniqid();
    $currencyCode = $params['currency'];
    // Client Parameters
    $customer_name = $params['clientdetails']['firstname'];
    $customer_surname = $params['clientdetails']['lastname'];    
    // System Parameters
  
    $returnUrl = $params['returnurl'];
    $description = $fees ? $params["description"].' + '.paymentFeesN($params['amount']).'F de retrait' : $params["description"] ;
    $amount =$fees ? _toIntN($params['amount'])+paymentFeesN($params['amount']) : _toIntN($params['amount']) ;
    $callBackUrl = 'https://'.$_SERVER['HTTP_HOST'].'/modules/gateways/callback/cinetpay.php';


	$postfields = [
        'amount' => $amount,
        'apikey' => $apikey,
        'site_id' =>$siteId,
    	'currency' => $currencyCode,
        'transaction_id' => $transaction_id,
        'description' =>$description,
    	'return_url' => $returnUrl,
        'notify_url' => $callBackUrl,
        'channels' => $channels,
        'customer_name' =>$customer_name,
    	'customer_surname' => $customer_surname,
    	'alternative_currency' => $currencyCode,
        'customer_email' => $params['clientdetails']['email'],
        'customer_phone_number' => $params['clientdetails']['phonenumber'],
        'customer_address' => $params['clientdetails']['address1'],
        'customer_city' => $params['clientdetails']['city'],
        'customer_country' => $params['clientdetails']['country'],
        'customer_state' => $params['clientdetails']['state'],
        'customer_zip_code' => $params['clientdetails']['postcode']
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-checkout.cinetpay.com/v2/payment');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $transactionObjet = json_decode($response);    
    $link = $transactionObjet->data->payment_url;
	
	// print_r($transactionObjet->data->payment_url);
	// die();


    return '
    <style>
    	#neonShadow{
        	margin-top: 35px;
  			border:none;
  			border-radius:50px;
  			transition:0.3s;
  			animation: glow 1s infinite ;
  			transition:0.5s;
		}
 		@keyframes glow{
  			0%{box-shadow: 5px 5px 20px rgb(93, 52, 168),-5px -5px 20px rgb(93, 52, 168);}
  			50%{box-shadow: 5px 5px 20px rgb(81, 224, 210),-5px -5px 20px rgb(81, 224, 210)}
  			100%{box-shadow: 5px 5px 20px rgb(93, 52, 168),-5px -5px 20px rgb(93, 52, 168)}
		}
	</style>
    <a  id="neonShadow" type="submit"  style="" href="'.$link.'"  class="btn btn-success btn-block button">PAYER MAINTENANT</a>
    ';
}

