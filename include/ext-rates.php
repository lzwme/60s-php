<?php
require_once __DIR__ . '/utils.php';

function fetchRatesByCurrency($type = 'json', $currency = 'CNY')
{
    // $currency 来自请求参数，会拼进 URL 与缓存文件名，需严格校验
    $currency = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $currency));
    if (strlen($currency) !== 3) {
        return responseWithBaseRes([], '无效的货币代码', 400);
    }

    $base      = 'https://open.er-api.com/v6/latest/';
    $cachefile = "rates_{$currency}-" . date('Y-m-d') . '.json';

    $data = cacheGet($cachefile);

    if (! is_array($data) || $data === []) {
        $response = reqGet($base . $currency);
        $decoded  = is_string($response) ? json_decode($response, true) : null;
        $rates    = is_array($decoded['rates'] ?? null) ? $decoded['rates'] : [];

        if ($rates === []) {
            return responseWithBaseRes([], '汇率数据获取失败', 502);
        }

        $data = $rates;
        cacheSet($cachefile, $data);
    }

    if ($type === 'json') {
        return responseWithBaseRes($data);
    }

    $result = '';
    foreach ($data as $key => $value) {
        $result .= $key . ': ' . $value . "\n";
    }

    return $result;
}
