<?php

require_once 'utils.php';

function fetchEventsOnHistory($encode = 'json', $day = '')
{
    if (!$day || strlen($day) !== 4) {
        $day = date_format(date_create(), 'md');
    }

    $month = substr($day, 0, 2);
    $api = "https://baike.baidu.com/cms/home/eventsOnHistory/$month.json";
    $cachefile = "eventsOnHistory_$month.json";
    $data = cacheGet($cachefile);

    if (!$data) {
        $response = file_get_contents($api);
        $data = json_decode($response, true);

        if ($data) {
            cacheSet($cachefile, $data, 3600 * 24);
        }
    }

    $list = $data[$month][$day];

    if (is_array($list)) {
        foreach ($list as $i => $value) {
            $value['title'] = strip_tags($value['title']);
            $list[$i] = $value;
        }
    }

    if ($encode === 'json') {
        return responseWithBaseRes($list);
    } else {
        $rawRes = array_map(function ($e, $i) {
            return str_pad(($i + 1), 2, '0', STR_PAD_LEFT) . '. [' . $e['year'] . '] ' . $e['title'];;
        }, $list, array_keys($list));

        return implode("\n", $rawRes);
    }
}
