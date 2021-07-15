<?php

/**
 *      Script with common functions
 */

/**
 * Function to store Alias in tmp-file (like a database-replacement)
 * see pers/activation_code scripts
 * 
 * @param String $alias Alias object
 */
 function store_alias($alias) {
    global $storage_file;

    if (file_exists($storage_file)) {
        $storage = json_decode(file_get_contents($storage_file), true);
    } else {
        $storage = array();
    }

    // store alias
    if (!isset($storage['aliases'])) {
        $storage['aliases'] = array();
    }

    if (is_array($alias)) {
        $storage['aliases'][array_keys($alias)[0]] = $alias[array_keys($alias)[0]];
    } else {
        $storage['aliases'][] = $alias;
    }


    file_put_contents($storage_file, json_encode($storage));
 }

 /**
 * Function to get Alias from tmp-file (like a database-replacement)
 * see pers/activation_code scripts
 * 
 * After Alias was returned - it will be removed to prevent duble-usage
 * 
 * @param String $alias Alias string
 * 
 * @return Object Alias object
 */
 function get_alias($alias) {
    global $storage_file;

    if (file_exists($storage_file)) {
        $storage = json_decode(file_get_contents($storage_file), true);
    } else {
        return null;
    }

    // check if there are stored aliases
    if (!isset($storage['aliases'])) {
        return null;
    }

    // check if there is $alias in storage
    if (!isset($storage['aliases'][$alias])) {
        return null;
    }  

    $result = array($alias => $storage['aliases'][$alias]);

    // if there is not persistent_alias, then remove this alias to prevent double-usage
    if (!$persistent_alias)
        unset($storage['aliases'][$alias]);

    // re-store aliases
    file_put_contents($storage_file, json_encode($storage));

    return $result;
 }

/**
 * Function to store transaction info in tmp-file (like a database-replacement)
 * 
 * @param String $user_id PC User ID
 * @param String $transaction_id PC Transaction ID
 * @param String $status Status to store, can be 'created', 'confirmed', 'declined'
 */

function store_transaction_info($user_id, $transaction_id, $status) {
    global $storage_file;

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
    global $storage_file;

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

/**
 * Function to generate random ASCII string
 * 
 * @param Int $length required length
 * @param String $keyspace Set of chars
 * 
 * @return String Random string value
 */
function random_str(
    int $length = 64,
    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
): string {
    if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
    }
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

?>