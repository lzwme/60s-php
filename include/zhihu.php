<?php
require_once 'utils.php';

function fetchZhihu($encode = 'json')
{
    $api = 'https://www.zhihu.com/api/v4/search/top_search';

    $response = file_get_contents($api);
    $data = json_decode($response, true);
    $list = $data['top_search']['words'] ?? [];

    if ($encode === 'json') {
        $formattedList = array_map(function ($e) {
            $e['title'] = $e['query'] ?? '';
            $e['url'] = $e['url'] ?? ('https://www.zhihu.com/search?q=' . urlencode($e['title']));

            return $e;
        }, $list);

        return responseWithBaseRes($formattedList);
    } else {
        $text = '';
        foreach ($list as $index => $item) {
            $text .= ($index + 1) . '. ' . $item['query'] . "\n";
        }
        return $text;
    }
}

// try {
//     print_r(fetchZhihu());
// } catch (Exception $e) {
//     echo 'Error: ' . $e->getMessage();
// }
