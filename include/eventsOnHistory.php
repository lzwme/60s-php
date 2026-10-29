<?php

require_once __DIR__ . '/utils.php';

function fetchEventsOnHistory($encode = 'json', $day = '')
{
    $day = preg_replace('/\D/', '', (string) $day);
    if (strlen($day) !== 4) {
        $day = date('md');
    }

    $month     = substr($day, 0, 2);
    $api       = "https://baike.baidu.com/cms/home/eventsOnHistory/{$month}.json";
    $cachefile = "eventsOnHistory_{$month}.json";
    $data      = cacheGet($cachefile);

    if (! is_array($data)) {
        $content = @file_get_contents($api);
        $data    = $content === false ? null : json_decode($content, true);

        if (is_array($data)) {
            cacheSet($cachefile, $data, 3600 * 24);
        }
    }

    $list = is_array($data) && is_array($data[$month][$day] ?? null) ? $data[$month][$day] : [];

    foreach ($list as $i => $value) {
        if (is_array($value)) {
            $list[$i]['title'] = strip_tags((string) ($value['title'] ?? ''));
        }
    }

    if ($encode === 'json') {
        return responseWithBaseRes($list);
    }

    $rawRes = [];
    foreach ($list as $i => $e) {
        $rawRes[] = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '. [' . ($e['year'] ?? '') . '] ' . ($e['title'] ?? '');
    }

    return implode("\n", $rawRes);
}
