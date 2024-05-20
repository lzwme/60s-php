<?php
require_once 'utils.php';

function fetchToutiao($encode = 'json')
{
    $api = 'https://is-lq.snssdk.com/api/suggest_words/?business_id=10016';
    $response = file_get_contents($api);

    $data = json_decode($response, true);
    $list = $data['data'][0]['words'] ?? [];

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['word'] ?? '';
            $e['url'] = 'https://www.toutiao.com/trending/' . $e['id'];

            return $e;
        }, $list);

        return responseWithBaseRes($formattedList);
    } else {
        $rawRes = implode("\n", array_map(function ($e, $i) {
            return ($i + 1) . '. ' . ($e['word'] ?? '');
        }, $list, array_keys($list)));
        return $rawRes;
    }
}
