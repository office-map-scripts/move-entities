<?php

require_once __DIR__ . "/upload-conf.php";

function send_request($target, $id = null, $custom_request = null, $url_params = null) { //$target is one of "Booking", "Desk", "Employee", etc
    global $project_url, $apiKey, $use_ntlm_auth, $ntlm_user, $ntlm_password;

    $id = ($id) ? "/" . $id : $id = "";
    if ($custom_request == 'POST') $url = "{$project_url}/api/{$target}{$id}?apikey={$apiKey}";
    else {
        $url_params = ($url_params) ? "&" . $url_params : "";
        $url = "{$project_url}/api/{$target}{$id}?apikey={$apiKey}{$url_params}";
    }
    $url = str_replace(' ', '%20', $url);
    // echo '<br>' . PHP_EOL . 'URL: ' . $url;

    function_exists('curl_init') ? 0 : die('ERROR: LIBRARY CURL IS NOT CONNECTED');
    $ch = curl_init();
    $ch = $ch ? $ch : die('ERROR: Cannot init the curl connection');

    $options = [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
    ];
    if ($use_ntlm_auth) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_NTLM;
        $options[CURLOPT_USERPWD] = sprintf('%s:%s', $ntlm_user, $ntlm_password);
        // echo '<br>' . PHP_EOL . 'User: ' . $ntlm_user . ' Password: ' . $ntlm_user;
    }
    if (preg_match("/^https:/i", $url)) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    if ($custom_request) {
        $options[CURLOPT_HTTPGET] = false;
        if ($custom_request == 'PUT') {
            $options[CURLOPT_PUT] = true;
        } else if ($custom_request == 'POST') {
            $options[CURLOPT_POST] = true;
            $url_params = str_replace(' ', '%20', $url_params);
            $options[CURLOPT_POSTFIELDS] = $url_params;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $custom_request;
        }
    }

    curl_setopt_array($ch, $options);

    $json_response = curl_exec($ch);
    $response = json_decode($json_response, true);
    // echo '<br>' . PHP_EOL . 'Response: ' . $json_response;
    curl_close($ch);

    return $response;
}

function array_to_url($arr) {
    $res = '';
    $i = 0;
    foreach ($arr as $key => $value) {
        if ($value) {
            if ($i > 0) $res .= '&';
            $i += 1;
            $res .= $key . '=' . $value;
        }
    }
    return $res;
}

function update_entity_in_staffmap($entity, $data) {
    $url_params = array_to_url($data);
    $id = $data["id"] ?? "";
    unset($data["id"]);
    $response = send_request($entity, $id, 'PUT', $url_params);
    if (!isset($response[0])) throw new Exception("Empty response");
    if (isset($response[0]["error"])) throw new Exception($response[0]["error"]);
    return $id;
}

function remove_bad_sings($value) {
    $value = str_replace('"', '', $value);
    $value = str_replace("'", '', $value);
    $value = str_replace('/', urlencode("/"), $value);
    $value = str_replace('\\', urlencode("\\"), $value);
    return $value;
}


$json = file_get_contents($filename);
$data = json_decode($json, true);

$len = count($data);
foreach ($data as $i => $row) {
    $value = remove_bad_sings($row[1]);
    try {
        $result = update_entity_in_staffmap($entity, ["id" => $row[0], $fieldname => $value]);
        echo PHP_EOL . "Updated " . $i . " of " . $len . " id: " . $result;
    } catch (\Throwable $e) {
        echo PHP_EOL . "Failed to update " . $i . " of " . $len;
        echo PHP_EOL . $e->getMessage() . PHP_EOL;
    }
}
