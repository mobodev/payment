<?php


/*
 * Copyright (c) 2018 Karim Kalunga
 *
 * Licensed under The MIT License,
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://opensource.org/licenses/MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 */

/**
 * <p>
 *     Here is where all your end-points for data exchanges are defined
 * </p>
 *
 * @author Karim Kalunga(mobodev)
 *         karimkalunga@endlesstec.com
 *
 */
require_once '../core/PaymentCore.php';
require_once "../core/PaymentCoreConstants.php";
require '.././libs/Slim/Slim.php';
require_once "../libs/stripe/init.php";

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->contentType('application/json');

/**
 * Request responsible for card payment processing through stripe payment Gateway
 * apiKey= Stripe merchant live key
 * description= Payment description
 * token= Stripe payment token
 * amount= payment amount
 * currency= Default transaction currency
 * email= customer's email
 */
$app->post('/card',function() use ($app){
    $response=array();

    $fields=array('apiKey','description','token','amount','currency','email');
    verifyRequiredParams($fields);
    $apiKey=$app->request->post('apiKey');
    $description=$app->request->post('description');
    $token=$app->request->post('token');
    $amount=$app->request->post('amount');
    $currency=$app->request->post('currency');
    $email=$app->request->post('email');
    $amount=$amount."00";
    \Stripe\Stripe::setApiKey($apiKey);

    $customer = \Stripe\Customer::create(array(
            "source" => $token,
            "email"=>$email,
            "description" =>$description)
    );
    $card_charge =\Stripe\Charge::create(array(
        'amount' => $amount,
        'currency' => $currency,
        'customer' => $customer->id));
    $responseData=str_replace('Stripe\Charge JSON:','',$card_charge);
    $data = json_decode($responseData);
    $response[STRIPE_RESPONSE_STATUS]=$data->outcome->network_status;
    $response[STRIPE_RESPONSE_REASON]=$data->outcome->reason;
    $response[STRIPE_RESPONSE_RISK_LEVEL]=$data->outcome->risk_level;
    $response[RESPONSE_MESSAGE]=$data->outcome->seller_message;
    $response[STRIPE_RESPONSE_PAYMENT_TYPE]=$data->outcome->type;
    $response[STRIPE_RESPONSE_ISPAID]=$data->paid;
    $response[STRIPE_RESPONSE_PAYMENT_ID]=$data->id;
    $response[STRIPE_RESPONSE_CARD]=$data->source->brand;
    $response[STRIPE_RESPONSE_AMOUNT_DEDUCTED]=$data->amount;
    $response[STRIPE_RESPONSE_PAYMENT_TIMESTAMP]=$data->created;
    $response[STRIPE_RESPONSE_COUNTRY]=$data->country;
    echoResponse(200,$response);

});


/**
 * Request responsible for initializing payment process from Tigo Pesa side
 */
$app->post('/tigopesa', function () use ($app) {
    $fields=array('environment','phone','countryCode','country','amount','currency',
        'tax','fee','apiKey','apiSecret','accountName','accountMsisdn','accountPin',
        'language','token','firstName','lastName','email');
    verifyRequiredParams($fields);
    $request_body=array();
    $paymentGatewaysAPI=new PaymentCore();

    $environment=$app->request->post('environment');
    $base_url=$environment== "SANDBOX" ? TIGOPESA_SANDBOX_ENVIRONMENT :TIGOPESA_LIVE_ENVIRONMENT;
    /*Customer details*/
    $customerPhoneNumber=$app->request->post('phone');
    $customerFirstName=$app->request->post('firstName');
    $customerLastName=$app->request->post('lastName');
    $customerEmailAddress=$app->request->post('email');
    $customerCountry=$app->request->post('country');
    $customerCountryCode=$app->request->post('countryCode');

    /*Payment details*/
    $amountToPay=$app->request->post('amount');
    $currencyCode=$app->request->post('currency');
    $transactionTax=$app->request->post('tax');
    $transactionFee=$app->request->post('fee');
    $transaction_ref_code=$app->request->post('token');

    /*Merchant details*/
    $api_key=$app->request->post('apiKey');
    $api_secrete=$app->request->post('apiSecret');
    $account_name=$app->request->post('accountName');
    $account_msisdn=$app->request->post('accountMsisdn');
    $account_pin=$app->request->post('accountPin');
    $redirection_url="default";
    $callback_url="default";

    /*Other details*/
    $language=$app->request->post('language');

    //Get session ID from TiGo Pesa API
    $session_id=$paymentGatewaysAPI->getSessionId($base_url,$api_key,$api_secrete);

    //Create transaction request
    $request_body['MasterMerchant']=array(
        'account' => $account_msisdn,
        'pin' => $account_pin,
        'id' => $account_name
    );

    /*Create request body*/
    $request_body['Subscriber']=array(
        'account' => $customerPhoneNumber,
        'countryCode' => $customerCountryCode,
        'country'=>$customerCountry,
        'firstName'=>$customerFirstName,
        'lastName'=>$customerLastName,
        'emailId'=>$customerEmailAddress
    );

    $request_body['redirectUri']=$redirection_url;
    $request_body['callbackUri']=$callback_url;
    $request_body['language']=$language;

    $request_body['originPayment']=array(
        'amount' => $amountToPay,
        'currencyCode' => $currencyCode,
        'tax'=>$transactionTax,
        'fee'=>$transactionFee
    );

    $request_body['LocalPayment']=array(
        'amount' => $amountToPay,
        'currencyCode' => $currencyCode
    );
    $request_body['transactionRefId']=$transaction_ref_code;

    /*Get payment response*/
   $paymentResponse=$paymentGatewaysAPI->getSecurePaymentURL($base_url,$session_id,$request_body,$api_key,$api_secrete);
   echo $paymentResponse;
});


/**
 * Request as callback executed on M-Pesa servers
 * uid=M-Pesa payment UID
 * code = Payment status code
 * transactionRefId= M-Pesa transaction reference ID
 */
$app->post("/callback",function() use ($app){
    $data = json_decode(file_get_contents('php://input'), true);
    $ref_uuid = $data['uid'];
    $status_code = $data['code'];
    $transRefId = $data['transactionRefId'];
    $transRefId = $transRefId!=null ? $transRefId: null;
    if($data!=null){
        if(array_key_exists('uid',$data) && array_key_exists('code',$data) && array_key_exists('requestId',$data)){
            $paymentAPI = new PaymentCore();
            if($ref_uuid!=null){
                $partialPay=$paymentAPI->getLoggedPartialPayment($ref_uuid);
                $partialPayment=array();
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_STATUS_CODE]=$status_code;
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_REFERENCE_ID]=$transRefId;
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_REFERENCE_UID]=$ref_uuid;
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_LOG_TIMESTAMP]=$partialPay->payment->logged_timestamp;
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_CALLBACK_STATUS]="Received";
                $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_CALLBACK_TIMESTAMP]=strtotime(date("Y-m-d H:i:s"));
                $paymentAPI->logPartialPayment($ref_uuid,json_encode($partialPayment));
            }
        }else{
            $response=array();
            $response[RESPONSE_ERROR_STATUS]=true;
            $response[RESPONSE_MESSAGE]="Bad request sent for this call back, check your json keys";
            echoResponse(202,$response);
        }
    }else{
        $response=array();
        $response[RESPONSE_ERROR_STATUS]=true;
        $response[RESPONSE_MESSAGE]="Bad request sent for this call back, check your json keys";
        echoResponse(202,$response);
    }
});



/**
 * Request partial payment file retrial, in case you want to check call back status
 * @path uuid=payment UUID
 */

$app->get("/:uuid/partial",function($uuid) use ($app){
    $paymentAPI = new PaymentCore();
    $result = $paymentAPI->getLoggedPartialPayment($uuid);
    $partialPayment[PAYMENT_STATUS_CODE]=$result->payment->status;
    $partialPayment[PAYMENT_REFERENCE_ID]=$result->payment->refId;
    $partialPayment[PAYMENT_REFERENCE_UID]=$result->payment->refUID;
    $partialPayment[PAYMENT_LOG_TIMESTAMP]=$result->payment->logged_timestamp;
    $partialPayment[PAYMENT_CALLBACK_STATUS]=$result->payment->callback;
    $partialPayment[PAYMENT_CALLBACK_TIMESTAMP]=$result->payment->callback_timestamp;
    echoResponse(200,$partialPayment);
});


/**
 * Request to log partial payment from client side into a file
 * uuid= payment UUID
 * code= payment status code (100= processing, 200=OK, 105=Failed)
 * refID= app generated reference ID
 */
$app->post("/partial",function() use ($app){
    verifyRequiredParams(array('uuid','code','refId'));
    $response = array();
    $ref_uuid = $app->request->post('uuid');
    $statusCode = $app->request->post('code');
    $ref_trans_id = $app->request->post('refId');

    $partialPayment=array();
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_STATUS_CODE]=$statusCode;
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_REFERENCE_ID]=$ref_trans_id;
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_REFERENCE_UID]=$ref_uuid;
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_LOG_TIMESTAMP]=strtotime(date("Y-m-d H:i:s"));
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_CALLBACK_STATUS]=PAYMENT_DEFAULT_VALUE;
    $partialPayment[PAYMENT_JSON_OBJECT][PAYMENT_CALLBACK_TIMESTAMP]=PAYMENT_DEFAULT_VALUE;
    $paymentAPI = new PaymentCore();
    $result=$paymentAPI->logPartialPayment($ref_uuid,json_encode($partialPayment));
    if($result){
        $response[RESPONSE_ERROR_STATUS] = false;
        $response[RESPONSE_MESSAGE] = $ref_uuid;
        echoResponse(200, $response);
    }else{
        $response[RESPONSE_ERROR_STATUS] = true;
        $response[RESPONSE_MESSAGE] = "Oops, failed to add partial payment";
        echoResponse(201,$response);
    }
});



/**
 * Default method to be executed for the invalid direct get request
 */
$app->get("/",function () use ($app){
    $response=array();
    $response[RESPONSE_ERROR_STATUS]=true;
    $response[RESPONSE_MESSAGE]="Un authorized access.";
    echoResponse(403,$response);
});


/**
 * Method responsible for displaying execution results
 *
 * @param $status_code: HTTP status code
 * @param $response: Result to be echoed
 */
function echoResponse($status_code, $response){
    $app = \Slim\Slim::getInstance();
    $app->status($status_code);
    echo json_encode($response);
}


/**
 * method responsible for verifying all mandatory params to be passed during API calls.
 * @param $required_fields
 * @throws \Slim\Exception\Stop
 */
function verifyRequiredParams($required_fields){
    $error = false;
    $error_fields = "";
    $request_params = $_REQUEST;

    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response[RESPONSE_ERROR_STATUS] = true;
        $response[RESPONSE_MESSAGE] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

$app->run();

