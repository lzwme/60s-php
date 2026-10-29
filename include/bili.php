<?php
require_once __DIR__ . '/utils.php';

function fetchBili($encode = 'json')
{
    $api  = 'https://app.bilibili.com/x/v2/search/trending/ranking';
    $list = cacheGet($api);

    if (! is_array($list)) {
        $content = @file_get_contents($api);
        $data    = $content === false ? null : json_decode($content, true);

        $rawList = $data['data']['list'] ?? null;
        $rawList = is_array($rawList) ? $rawList : [];

        $list = array_values(array_filter($rawList, function ($e) {
            return is_array($e) && isset($e['is_commercial']) && $e['is_commercial'] === '0';
        }));

        if ($list !== []) {
            cacheSet($api, $list, 600);
        }
    }

    $list = is_array($list) ? $list : [];

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['show_name'] ?? '';
            $e['url']   = $e['url'] ?? ('https://search.bilibili.com/all?keyword=' . urlencode($e['title']));

            return $e;
        }, $list);

        return responseWithBaseRes($formattedList);
    }

    $rawRes = [];
    foreach ($list as $i => $e) {
        $rawRes[] = ($i + 1) . '. ' . ($e['show_name'] ?? '');
    }

    return implode("\n", $rawRes);
}
