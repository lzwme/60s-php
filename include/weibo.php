<?php
require_once __DIR__ . '/utils.php';

function fetchWeibo($encode = 'json')
{
    $api = 'https://weibo.com/ajax/side/hotSearch';

    $filteredList = cacheGet($api);

    if (! is_array($filteredList)) {
        $content = @file_get_contents($api);
        $data    = $content === false ? null : json_decode($content, true);

        $list         = is_array($data['data']['realtime'] ?? null) ? $data['data']['realtime'] : [];
        $filteredList = array_values(array_filter($list, function ($e) {
            return is_array($e) && empty($e['is_ad']);
        }));

        if ($filteredList !== []) {
            cacheSet($api, $filteredList, 120);
        }
    }

    $filteredList = is_array($filteredList) ? $filteredList : [];

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $title      = $e['word'] ?? '';
            $e['title'] = $title;
            // 原实现使用 ||，当 word_scheme 为空字符串时会得到 "" 而不是 title
            $e['url']   = 'https://s.weibo.com/weibo?q=' . urlencode($e['word_scheme'] ?: $title);

            return $e;
        }, $filteredList);

        return responseWithBaseRes($formattedList);
    }

    $rawRes = [];
    foreach ($filteredList as $i => $e) {
        $rawRes[] = ($i + 1) . '. ' . ($e['word'] ?? '');
    }

    return implode("\n", $rawRes);
}
