<?php
require_once 'utils.php';

function fetchDouyin($encode = 'json', $c = 'word')
{
    $api = 'https://aweme-lq.snssdk.com/aweme/v1/hot/search/list/?aid=1128&version_code=880';

    $data = cacheGet($api);
    if (!$data) {
        $response = file_get_contents($api);
        $data = json_decode($response, true);
        $data = $data['data'];

        cacheSet($api, $data, 300);
    }

    if (!$data) {
        return [
            'status' => 500,
            'message' => '解析 JSON 失败',
            'data' => null,
        ];
    }

    $list = [];
    if ($c == 'word' && is_array($data['word_list'])) {
        $list = $data['word_list'];
    }

    if ($c == 'trending' && is_array($data['trending_list'])) {
        $list = $data['trending_list'];
    }

    if ($encode === 'json') {
        $active_time = $data['active_time'] ?? '';
        $formattedList = array_map(function ($e) use ($active_time) {
            $e['title'] = $e['word'] ?? '';
            $e['url'] = $e['word_cover']['url'] ?? ('https://www.douyin.com/search/' . urlencode($e['title']) . '?type=general');
            $e['active_time'] = $active_time;
            $e['cover'] = $e['word_cover']['url_list'][0] ?? '';

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
