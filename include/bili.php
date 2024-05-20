<?php
require_once 'utils.php';

function fetchBili($encode = 'json')
{
    $api = 'https://app.bilibili.com/x/v2/search/trending/ranking';
    $list = cacheGet($api);

    if (!$list) {
        $response = file_get_contents($api);
        $data = json_decode($response, true)['data'];

        $list = isset($data['list']) ? array_filter($data['list'], function ($e) {
            return isset($e['is_commercial']) && $e['is_commercial'] === '0';
        }) : [];

        if (isset($data['list'])) {
            cacheSet($api, $list, 600);
        }
    }

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['show_name'] ?? '';
            $e['url'] = $e['url'] ?? ('https://search.bilibili.com/all?keyword=' . urlencode($e['title']));

            return $e;
        }, $list);

        return responseWithBaseRes($formattedList);
    } else {
        $rawRes = array_map(function ($e, $i) {
            return ($i + 1) . '. ' . (isset($e['show_name']) ? $e['show_name'] : '');
        }, $list, array_keys($list));

        return implode("\n", $rawRes);
    }
}
