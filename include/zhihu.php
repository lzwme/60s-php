<?php
require_once __DIR__ . '/utils.php';

function fetchZhihu($encode = 'json')
{
    $api    = 'https://www.zhihu.com/api/v3/feed/topstory/hot-lists/total?limit=1000';
    $cookie = getenv('ZHIHU_COOKIE') ?: '';

    ['res' => $response] = httpCurl($api, 'GET', null, ["cookie: {$cookie}"]);

    $data = is_string($response) ? json_decode($response, true) : null;
    $raw  = is_array($data['data'] ?? null) ? $data['data'] : [];

    $list = array_map(function ($e) {
        $target  = is_array($e['target'] ?? null) ? $e['target'] : [];
        $created = (int) ($target['created'] ?? 0);

        return [
            'title'          => $target['title'] ?? '',
            'detail'         => $target['excerpt'] ?? '',
            'cover'          => $e['children'][0]['thumbnail'] ?? '',
            'hot_value_desc' => $e['detail_text'] ?? '',
            'answer_cnt'     => $target['answer_count'] ?? 0,
            'follower_cnt'   => $target['follower_count'] ?? 0,
            'comment_cnt'    => $target['comment_count'] ?? 0,
            'created_at'     => $created * 1000,
            'created'        => $created > 0 ? date('Y-m-d H:i:s', $created) : '',
            'url'            => $target['url'] ?? '',
            'link'           => $target['url'] ?? '',
        ];
    }, $raw);

    if ($encode === 'json') {
        return responseWithBaseRes($list);
    }

    $text = '';
    foreach ($list as $idx => $item) {
        $text .= ($idx + 1) . '. ' . $item['title'] . ' ' . $item['hot_value_desc'] . "\n";
    }

    return $text;
}
