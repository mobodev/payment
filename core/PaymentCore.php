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
class PaymentCore { 
    function __construct(){

    }

    /**
     * Method responsible for requesting session ID from TigoPesa API.
     *
     * @param $base_url: Base URL depending on the environment (Sandbox, Live)
     * @param $api_key: Merchant API KEY
     * @param $secret_key: Merchant API SECRET KEY
     * @return mixed: session ID
     */
    function getSessionId($base_url,$api_key,$secret_key){
        $headers=array();
        $request_url=$base_url."oauth/generate/accesstoken?grant_type=client_credentials";
        $headers["Content-Type"]="application/x-www-form-urlencoded";
        $response=$this->executePostRequest($headers,null,$request_url,$api_key,$secret_key);
        return $this->getSpecificDataFromResult($response,"accessToken");
    }


    /**
     * Method which is responsible for getting secure payment URL from TigoPesa API
     *
     * @param $base_url: Base URL depending on the environment (Sandbox, Live)
     * @param $session_id: Session ID granted by TigoPesa API
     * @param $request_body: Data to be sent along with the request
     * @param $api_key: Merchant API KEY
     * @param $secret_key: Merchant API SECRET KEY
     * @return mixed: Json response from TigoPesa API
     */
    function getSecurePaymentURL($base_url, $session_id, $request_body, $api_key, $secret_key){
        $request_url=$base_url."tigo/payment-auth/authorize";
        $headers=array(
            'Content-Type: application/json',
            'accessToken:'.$session_id
        );
        $request_body=json_encode($request_body);
        $response=$this->executePostRequest($headers,$request_body,$request_url,$api_key,$secret_key);
        return $response;
    }


    /**
     * Method responsible for executing all CURL POST request to the TigoPesa API
     *
     * @param $request_header : Request headers
     * @param $request_body : Request body parameters
     * @param $request_url: TigoPesa request URL
     * @param $api_key: API key
     * @param $secret_key: API secret
     * @return mixed: Json response from TigoPesa API
     */
    function executePostRequest($request_header, $request_body, $request_url, $api_key, $secret_key){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
        if($request_body==null){
            $request_body="client_id=".$api_key."&client_secret=".$secret_key;
        }
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request_body);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
        $this->createLogFile($api_key,$request_header,$request_body,$request_url,$result);
        return $result;
    }

    /**
     * Method responsible for executing all CURL GET request to the TigoPesa API.
     * @param $session_id: Session ID granted by TigoPesa API
     * @param $request_url: TigoPesa request URL
     * @return mixed: Json response from TigoPesa API
     */
    function executeGetRequest($session_id,$request_url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $headers = array();
        $headers[] = "accessToken: ".$session_id;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
        return $result;
    }


    /**
     * Method responsible for creating log from TigoAPI request information.
     *
     * @param $file_name: Name of the file to be created
     * @param $request_header : Request header
     * @param $request_body : Request body
     * @param $request_url : URL that will be handling that request
     * @param $execution_response : Response of the request sent to that request
     */
    function createLogFile($file_name, $request_header, $request_body, $request_url, $execution_response){
        $ipAddress=$_SERVER['SERVER_ADDR'];
        $file = fopen($file_name.".json", "w") or die("Unable to open file!");
        if(sizeof($request_header)>1){
            $data_array = implode(",", $request_header);
        }else{
            $data_array =$request_header;
        }
        $content="IP Address: ".$ipAddress."\n";
        $content=$content."URL: ".$request_url."\n";
        $content=$content."Headers: ".$data_array."\n\n";
        $content = $content."\nBody: ".$request_body."\n\n";
        $content = $content."\nResponse: ".$execution_response."\n";
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Method responsible for getting specific data from json response.
     *
     * @param $result: Json response
     * @param $data_key: Key of the data to be retrieved
     * @return mixed
     */
    public function getSpecificDataFromResult($result,$data_key){
        $result=json_decode($result,true);
        return $result[''.$data_key.''];
    }

    /**
     * Method responsible for creating partial payment file on your server
     * @param $payment_uuid M-Pesa UUID
     * @param $partial_data :partial data in a json format
     * @return bool TRUE if created otherwise false
     */
    public function logPartialPayment($payment_uuid,$partial_data){
        $filePath="../logs/".$payment_uuid.".json";
        $generatedFile = fopen($filePath, "w") or die("Unable to open file!");
        fwrite($generatedFile, $partial_data."\n");
        fclose($generatedFile);
        return $this->isFileAvailable($filePath);
    }

    /**
     * Method responsible for retrieving partial payment details from a file
     * @param $payment_uuid : M-Pesa UUID
     * @return mixed json data
     */
    public function getLoggedPartialPayment($payment_uuid){
        $filePath="../logs/".$payment_uuid.".json";
        if ($this->isFileAvailable($filePath)) {
            $response=file_get_contents($filePath);
        } else {
            $fileData=array();
            $fileData["payment"]["status"]="N/A";
            $fileData["payment"]["refId"]="N/A";
            $fileData["payment"]["refUID"]="N/A";
            $fileData["payment"]["received"]="Was not logged";
            $response=json_encode($fileData);
        }
        return json_decode($response);
    }

    function isFileAvailable($path){
        return file_exists($path);
    }
}