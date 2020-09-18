<?php

/**
 *      Script with common functions
 */

/**
 * Function to store transaction info in tmp-file (like a database-replacement)
 * 
 * @param String $user_id PC User ID
 * @param String $transaction_id PC Transaction ID
 * @param String $status Status to store, can be 'created', 'confirmed', 'declined'
 */

function store_transaction_info($user_id, $transaction_id, $status) {
    $storage_file = '/tmp/pc_sample_storage.json';

    if (file_exists($storage_file)) {
        $storage = json_decode(file_get_contents($storage_file), true);
    } else {
        $storage = array();
    }

    // store transaction status
    $storage[$transaction_id] = $status;
    
    // store relation between last transaction for defined user
    if (null != $user_id) {
        $storage[$user_id] = $transaction_id;
    }

    file_put_contents($storage_file, json_encode($storage));
}

/**
 * Function get transaction info from tmp-file (like a database-replacement)
 * 
 * @param String $user_id PC User ID
 * @param String $transaction_id PC Transaction ID
 *
 * @return String Status, can be 'created', 'confirmed', 'declined'
 */
function get_stored_transaction_info($user_id, $transaction_id) {
    $storage_file = '/tmp/pc_sample_storage.json';

    if (file_exists($storage_file)) {
        $storage = json_decode(file_get_contents($storage_file), true);
    } else {
        return null;
    }

    if (null != $user_id) {
        $transaction_id = $storage[$user_id];
    }

    if (null == $transaction_id) {
        return null;
    }

    return $storage[$transaction_id];
}


/**
 * Function to make regular call to PC
 * see https://repo.payconfirm.org/server/doc/v5/rest-api/#introduction
 * 
 * @param String $url URL to make a call, should be exact URL of required method
 * @param String $request JSON with the request, can be null
 * @param String $expected_answer Expected answer type. For example, for create user it will be `user_created`
 * @param String &$result Variable to return an answer in JSON format
 * @param String &$error_description Variable to return error description if happened
 * @param Int &$error_code Variable to return error code if happened
 * 
 * @return Boolean true if request was success, false if not
 */
function pc_request($url, $request, $expected_answer, &$result, &$error_description, &$error_code = 0)
{
    if (($url == null) || ($url == '')) {
        return false;
    }

    // init curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($request == null) {
        // make GET-request
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    } else {
        // make POST-request
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($request))
        );
    }

    // execute request
    $output = curl_exec($ch);
    $error_description = curl_error($ch);
    curl_close($ch);

    // check result
    if (false === $output) {
        return false;
    }

    //decode json to an array
    $output_json = json_decode($output, true);

    // check answer from PC
    $pc_answer = $output_json['answer'];
    if ($pc_answer['result']['error_code'] != 0) {
        $error_description = $pc_answer['result']['error_message'];
        $error_code = $pc_answer['result']['error_code'];
        return false;
    }

    // if we should NOT return an answer - finish
    if (null == $expected_answer) {
        return true;
    }

    // get expected object from PC answer
    $result = null;
    if (isset($pc_answer[$expected_answer])) {
        $result = $pc_answer[$expected_answer];
    }

    if (null == $result) {
        $error_description = "Non expected answer from PC Server";
        return false;
    }

    return true;
}

?>