<?php
require_once 'utils.php';

function fetchRatesByCurrency($type = 'json', $currency = 'CNY') {
    $api = 'https://open.er-api.com/v6/latest/';
    $cachefile = 'rates_' . strtoupper($currency) . '-' . date('Y-m-d') . '.json';

    $data = cacheGet($cachefile);

    if (!$data) {
        $response = reqGet($api . $currency);

        $data = json_decode($response, true)['rates'];
        cacheSet($cachefile, $data);
    }

    if ($type === 'json') {
        return responseWithBaseRes($data);
    } else {
        $result = '';
        foreach ($data as $key => $value) {
            $result .= $key . ': ' . $value . "\n";
        }
        return $result;
    }
}
