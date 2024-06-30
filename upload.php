<?php

require_once __DIR__ . "/upload-conf.php";

function get_list_data(string $url) {
    global $use_ntlm_auth, $ntlm_user, $ntlm_password, $pre_resuest_url, $pre_resuest_postdata;
    // echo PHP_EOL . "<br>" . "list url " . $url;

    function_exists('curl_init') ? 0 : die('ERROR: LIBRARY CURL IS NOT CONNECTED');
    $ch = curl_init();
    $ch = $ch ? $ch : die('ERROR: Cannot init the curl connection');

    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.75";

    $options = [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_VERBOSE => true,
        CURLOPT_COOKIESESSION => true
    ];
    if ($use_ntlm_auth) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_NTLM;
        $options[CURLOPT_USERPWD] = sprintf('%s:%s', $ntlm_user, $ntlm_password);
    }
    if (preg_match("/^https:/i", $url)) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }

    $options[CURLOPT_URL] = $pre_resuest_url;
    $options[CURLOPT_HTTPGET] = false;
    $options[CURLOPT_POST] = true;
    $options[CURLOPT_POSTFIELDS] = $pre_resuest_postdata;
    curl_setopt_array($ch, $options);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
    curl_exec($ch);
    $options[CURLOPT_URL] = $url;
    $options[CURLOPT_HTTPGET] = true;
    $options[CURLOPT_POST] = false;
    $options[CURLOPT_POSTFIELDS] = false;
    $header[] = "X-Requested-With: XMLHttpRequest";
    curl_setopt_array($ch, $options);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

    $json_response = curl_exec($ch);

    curl_close($ch);

    $response = json_decode($json_response, true);
    if (!$response) die('ERROR: Cannot get list from staffmap');
    if (!isset($response['data'])) die('ERROR: Cannot get field data when getting list from staffmap');
    $response = $response['data'];
    if (!is_array($response)) die('ERROR: data from staffmap is not array');

    return $response;
}

function fieldname_id_map(array $list_data): array {
    $map = [];
    foreach ($list_data as $row) {
        $key = $row[1];
        if (array_key_exists($key, $map)) continue;
        $map[$key] = $row[0];
    }
    return $map;
}

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
        if ($i > 0) $res .= '&';
        $i += 1;
        $res .= $key . '=' . $value;
    }
    return $res;
}

function save_staffmap_entity($entity, $data) {
    $id = $data["id"] ?? "";
    unset($data["id"]);
    $url_params = array_to_url($data);
    $method = $id ? "PUT" : "POST";
    $response = send_request($entity, $id, $method, $url_params);
    if (!isset($response[0])) throw new Exception("Empty response");
    if (isset($response[0]["error"])) throw new Exception($response[0]["error"]);
}

function remove_bad_sings($value) {
    $value = str_replace('"', '', $value);
    $value = str_replace("'", '', $value);
    $value = str_replace('/', urlencode("/"), $value);
    $value = str_replace('\\', urlencode("\\"), $value);
    return $value;
}

function is_not_empty($value): bool {
    return !empty($value);
}

function get_id(string $id_by, array $map, array $data): string {
    $value = $data[$id_by] ?? null;
    if (!$value) throw new \Exception("No field \"$id_by\"");
    $id = $map[$value] ?? null;
    if (!$id) throw new \Exception("Entity with \"$id_by\" = \"$value\"");
    return $id;
}

$desks = $desk_id_by ? fieldname_id_map(get_list_data($desk_list_url)) : [];
$employees = $employee_id_by ? fieldname_id_map(get_list_data($employee_list_url)) : [];

$json = file_get_contents($filename);
$data = json_decode($json, true);

$len = count($data);
foreach ($data as $i => $row) {
    try {
        $row = array_slice($row, 0, count($fieldnames));
        $row = array_map("remove_bad_sings", $row);
        $data = array_combine($fieldnames, $row);
        $data = array_filter($data, "is_not_empty", ARRAY_FILTER_USE_KEY);

        if ($desk_id_by) $data["desk_id"] = get_id($desk_id_by, $desks, $data);
        if ($employee_id) $data["employee_id"] = get_id($employee_id_by, $employees, $data);

        save_staffmap_entity($entity, $data);
        echo PHP_EOL . "Saved " . ($i + 1) . " of " . $len;
    } catch (\Throwable $e) {
        echo PHP_EOL . "Failed to save " . ($i + 1) . " of " . $len;
        echo PHP_EOL . $e->getMessage() . PHP_EOL;
    }
}
