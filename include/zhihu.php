<?php
require_once 'utils.php';

function fetchZhihu($encode = 'json')
{
    $api = 'https://www.zhihu.com/api/v3/feed/topstory/hot-lists/total?limit=1000';
    $cookie = getenv('ZHIHU_COOKIE') ?: '';

    ['res' => $response] = httpCurl($api, 'GET', null, ["cookie: $cookie"]);

    $data = json_decode($response, true);

    $list = array_map(function ($e) {
      return [
         'title' => $e['target']['title'],
         'detail' => $e['target']['excerpt'],
         'cover' => $e['children'][0]['thumbnail'] ?? '',
         'hot_value_desc' => $e['detail_text'],
         'answer_cnt' => $e['target']['answer_count'],
         'follower_cnt' => $e['target']['follower_count'],
         'comment_cnt' => $e['target']['comment_count'],
         'created_at' => $e['target']['created'] * 1000,
         'created' => date('Y-m-d H:i:s', $e['target']['created']),
         'url' => $e['target']['url'],
         'link' => $e['target']['url'],
      ];
    }, $data['data'] ?? []);

    if ($encode === 'json') {
        return responseWithBaseRes($list);
    } else {
        $text = '';
        foreach ($list as $idx => $item) {
            $text .= ($idx + 1) . '. ' . $item['title'] . ' ' . $item['hot_value_desc'] . "\n";
        }
        return $text;
    }
}

// try {
//     print_r(fetchZhihu());
// } catch (Exception $e) {
//     echo 'Error: ' . $e->getMessage();
// }
