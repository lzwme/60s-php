<?php
require_once __DIR__ . '/utils.php';

function fetchDouyin($encode = 'json', $c = 'word')
{
    $api = 'https://aweme-lq.snssdk.com/aweme/v1/hot/search/list/?aid=1128&version_code=880';

    $data = cacheGet($api);
    if (! is_array($data)) {
        $content = @file_get_contents($api);
        $decoded = $content === false ? null : json_decode($content, true);
        $data    = $decoded['data'] ?? null;
        $data    = is_array($data) ? $data : null;

        if ($data !== null) {
            cacheSet($api, $data, 300);
        }
    }

    if (! is_array($data)) {
        return responseWithBaseRes([], '解析 JSON 失败', 500);
    }

    $list = [];
    if ($c === 'word' && is_array($data['word_list'] ?? null)) {
        $list = $data['word_list'];
    }

    if ($c === 'trending' && is_array($data['trending_list'] ?? null)) {
        $list = $data['trending_list'];
    }

    if ($encode === 'json') {
        $activeTime    = $data['active_time'] ?? '';
        $formattedList = array_map(function ($e) use ($activeTime) {
            $title              = $e['word'] ?? '';
            $e['title']         = $title;
            $e['url']           = $e['word_cover']['url'] ?? ('https://www.douyin.com/search/' . urlencode($title) . '?type=general');
            $e['active_time']   = $activeTime;
            $e['cover']         = $e['word_cover']['url_list'][0] ?? '';

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
