<?php
require_once 'utils.php';

function fetchWeibo($encode = 'json')
{
    $api = 'https://weibo.com/ajax/side/hotSearch';

    $filteredList = cacheGet($api);

    if (!$filteredList) {
        $response = file_get_contents($api);
        $data = json_decode($response, true);

        $list = ($data['data'] ?? [])['realtime'] ?? [];
        $filteredList = array_filter($list, function ($e) {
            return !isset($e['is_ad']) || !$e['is_ad'];
        });

        cacheSet($api, $filteredList, 120);
    }

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['word'] ?? '';
            $e['url'] = 'https://s.weibo.com/weibo?q=' . urlencode($e['word_scheme'] || $e['title']);

            return $e;
        }, $filteredList);

        return responseWithBaseRes(array_values($formattedList));
    } else {
        $rawRes = array_map(function ($e, $i) {
            return ($i + 1) . '. ' . ($e['word'] ?? '');
        }, $filteredList, array_keys($filteredList));

        return implode("\n", $rawRes);
    }

}

// print_r(fetchWeibo('json'));
