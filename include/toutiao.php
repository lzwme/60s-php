<?php
require_once __DIR__ . '/utils.php';

function fetchToutiao($encode = 'json')
{
    $api      = 'https://is-lq.snssdk.com/api/suggest_words/?business_id=10016';
    $content  = @file_get_contents($api);
    $data     = $content === false ? null : json_decode($content, true);
    $list     = is_array($data['data'][0]['words'] ?? null) ? $data['data'][0]['words'] : [];

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['word'] ?? '';
            $e['url']   = 'https://www.toutiao.com/trending/' . ($e['id'] ?? '');

            return $e;
        }, $list);

        return responseWithBaseRes($formattedList);
    }

    $rawRes = [];
    foreach ($list as $i => $e) {
        $rawRes[] = ($i + 1) . '. ' . ($e['word'] ?? '');
    }

    return implode("\n", $rawRes);
}
