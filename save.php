<?php
require_once __DIR__ . "/save-conf.php";

function get_list_url() {
    global $list_url;
    return $list_url;
}

function get_list_data() {
    global $use_ntlm_auth, $ntlm_user, $ntlm_password, $pre_resuest_url, $pre_resuest_postdata;

    $url = get_list_url();
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

function save_to_csv($filename, $data, $csv_titles = []) {
    global $csv_delimiter;

    $file = fopen($filename, 'w');

    if (count($csv_titles)) fputcsv($file, $csv_titles, $csv_delimiter);

    foreach ($data as $row) {
        fputcsv($file, $row, $csv_delimiter);
    }

    fclose($file);
}


$entities = get_list_data();
$json = json_encode($entities);
file_put_contents($filename, $json);
