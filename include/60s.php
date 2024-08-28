<?php

require_once 'utils.php';

function fetch60s($encode = 'json', $offset = 0, $isV1 = false)
{
    $api = 'https://www.zhihu.com/api/v4/columns/c_1715391799055720448/items?limit=8';
    $today = date('Y-m-d', time() + 8 * 3600 - (int) $offset * 24 * 3600);
    $cachefile = '60s_' . $today . '.json';

    $finalData = cacheGet($cachefile);
    $fromCache = isset($finalData) && is_array($finalData['result']);
    $newData = '';

    if (!$finalData) {
        // $response = file_get_contents($api);
        // $cookie = '__zse_ck=001_J3v0qhj4h2dUNxcD8h=3oNv3dmOO+amBsmT=8u+YeTZgTaxMvR526BNVNWDAmJ+Qrh7oUmL71X+5yYlm5J5qR8a45M6u/uZbU9fb5FijqaKcVlbsrvPkfkiXE6AqcIgK';
        $cookie = getenv('ZHIHU_COOKIE');
        if (!$cookie) $cookie = '__zse_ck=001_J3v0qhj4h2dUNxcD8h=3oNv3dmOO+amBsmT=8u+YeTZgTaxMvR526BNVNWDAmJ+Qrh7oUmL71X+5yYlm5J5qR8a45M6u/uZbU9fb5FijqaKcVlbsrvPkfkiXE6AqcIgK; z_c0=2|1:0|10:1724806971|4:z_c0|80:MS4xdWtxX0F3QUFBQUFtQUFBQVlBSlZUVHZGdTJlTDhIRVZkV2ptSy1rdEw2eWdtd29iRkFfbWRRPT0=|7b058dcac62d2af47ed8d5338f474f753cd8e55beb0d316f3874d3a154133873';

        ['res' => $response] = httpCurl($api, 'GET', null, ["cookie: $cookie"]);
        $data = json_decode($response, true);

        if ($data && is_array($data['data'])) {
            $reg = '/<p\s+data-pid=[^<>]+>([^<>]+)<\/p>/';

            foreach ($data['data'] as $item) {
                $updated = $item['updated'] ?? $item['created'] ?? 0;
                $day = date('Y-m-d', (int) $updated + 8 * 3600);
                $cachefile = '60s_' . $day . '.json';

                if ($item['content_need_truncated'] === true) {
                    // 截断的内容，且已存在缓存，则忽略此条数据
                    if (file_exists($cachefile)) {
                        continue;
                    }

                    // 从页面获取完整的 content
                    ['res' => $response] = httpCurl($item['url'], 'GET', null, ["cookie: $cookie", "Referer: {$item['url']}"]);

                    if ($response) {
                        preg_match('/<script id="js-initialData" type="text\/json">(\{.+\})<\/script>/', $response, $matches);
                        $initData = $matches[1] ?? '';
                        if ($initData) {
                            $initData = json_decode($initData, true);

                            $c = $initData['initialState']['entities']['articles'][$item['id']];
                            if (isset($c)) {
                                $item = array_merge($item, $c);
                                $item['content_need_truncated'] = false;
                            }
                        }
                    }

                    if ($item['content_need_truncated'] === true) {
                        continue;
                    }
                }

                $content = $item['content'] ?? '';
                $url = $item['url'] ?? '';
                $title_image = $item['title_image'] ?? '';

                preg_match('/(\d{4}年.+星期.+农历[^<]+)</', $content, $matches);
                $date = $matches[1] ?? '';

                $contents = preg_match_all($reg, $content, $matches);
                $result = array_map(function ($e) {
                    return preg_replace('/<[^<>]+>/', '', $e);
                }, $matches[1]);

                if (!empty($result)) {
                    $fData = [
                        'url' => $url,
                        'result' => $result,
                        'title_image' => $title_image,
                        'date' => $date,
                        'updated' => $updated * 1000,
                    ];

                    cacheSet($cachefile, $fData);

                    if ($today === $day) {
                        $finalData = $fData;
                    }
                    if (!$newData) {
                        $newData = $fData;
                    }
                }
            }
        }
    }

    if (!$finalData) {
        $finalData = $newData ?? [];
    }

    if ($isV1) {
        if ($encode === 'json') {
            return responseWithBaseRes($finalData['result'] ?? []);
        } else {
            return implode("\n", $finalData['result'] ?? []);
        }
    } else {
        $news = array_map(function ($e) {
            return preg_replace('/^(\d+)、\s*/', '$1. ', $e);
        }, $finalData['result'] ?? []);
        $tip = array_pop($news);

        if ($encode === 'json') {
            return responseWithBaseRes([
                'news' => $news,
                'tip' => $tip,
                'date' => $finalData['date'],
                'updated' => $finalData['updated'] ?? 0,
                'url' => $finalData['url'] ?? '',
                'cover' => $finalData['title_image'] ?? '',
                'fromCache' => $fromCache,
            ]);
        } else {
            return implode("\n", array_merge($news, [$tip]));
        }
    }
}
