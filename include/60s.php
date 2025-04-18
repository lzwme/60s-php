<?php

require_once __DIR__ . '/utils.php';

function fetch60s($encode = 'json', $offset = 0, $isV1 = false, $force = false)
{
    return fetch60sFromWechat($encode, $offset, $isV1, $force);

    $api       = 'https://www.zhihu.com/api/v4/columns/c_1715391799055720448/items?limit=8';
    $today     = date('Y-m-d', time() - (int) $offset * 24 * 3600);
    $cachefile = '60s_' . $today . '.json';

    $finalData = cacheGet($cachefile);
    $fromCache = isset($finalData) && is_array($finalData['result']);
    $newData   = '';

    if (! $finalData || $force) {
        // $response = file_get_contents($api);
        // $cookie = '__zse_ck=001_J3v0qhj4h2dUNxcD8h=3oNv3dmOO+amBsmT=8u+YeTZgTaxMvR526BNVNWDAmJ+Qrh7oUmL71X+5yYlm5J5qR8a45M6u/uZbU9fb5FijqaKcVlbsrvPkfkiXE6AqcIgK';
        $cookie = getenv('ZHIHU_COOKIE');
        if (! $cookie) {
            $cookie = '__zse_ck=003_bnW9isRsE7j=GUdObPd7xeW3EOegV2wwAOwU=9x1xxsLiFCd+RTiESdH9SkbwcnH/hJvOlSgQyJPsnq/pzKC5kw4GjnYepAtr1Wm1uyb50xb; z_c0=2|1:0|10:1727679662|4:z_c0|92:Mi4xUnB2ZlZnQUFBQUFBQUpJMVRZRlFHU1lBQUFCZ0FsVk5ycHJuWndBTGZYaXNkZm9uUHV4TVUwRVJkS3cxMF9rZXpR|153bfe8d42e07be9b26d6067a7923aba29efbeb1a76b09a623e0d8a9d8e74540';
        }

        ['res' => $response] = httpCurl($api, 'GET', null, ["cookie: $cookie"]);
        $data                = json_decode($response, true);

        if ($data && is_array($data['data'])) {
            $reg = '/<p\s+data-pid=[^<>]+>([^<>]+)<\/p>/';

            foreach ($data['data'] as $item) {
                $updated   = $item['updated'] ?? $item['created'] ?? 0;
                $day       = date('Y-m-d', (int) $updated + 8 * 3600);
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
                                $item                           = array_merge($item, $c);
                                $item['content_need_truncated'] = false;
                            }
                        }
                    }

                    if ($item['content_need_truncated'] === true) {
                        continue;
                    }
                }

                $content     = $item['content'] ?? '';
                $url         = $item['url'] ?? '';
                $title_image = $item['title_image'] ?? '';

                preg_match('/(\d{4}年.+星期.+农历[^<]+)</', $content, $matches);
                $date = $matches[1] ?? '';

                $contents = preg_match_all($reg, $content, $matches);
                $result   = array_map(function ($e) {
                    return preg_replace('/<[^<>]+>/', '', $e);
                }, $matches[1]);

                if (! empty($result)) {
                    $fData = [
                        'url'         => $url,
                        'result'      => $result,
                        'title_image' => $title_image,
                        'date'        => $date,
                        'updated'     => $updated * 1000,
                    ];

                    cacheSet($cachefile, $fData);

                    if ($today === $day) {
                        $finalData = $fData;
                    }
                    if (! $newData) {
                        $newData = $fData;
                    }
                }
            }
        }
    }

    if (! $finalData) {
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
                'news'       => $news,
                'tip'        => $tip,
                'date'       => $finalData['date'],
                'updated'    => date('Y-m-d H:i:s', $finalData['updated']),
                'updated_at' => $finalData['updated'] ?? 0,
                'url'        => $finalData['url'] ?? '',
                'cover'      => $finalData['title_image'] ?? '',
                'fromCache'  => $fromCache,
            ]);
        } else {
            return implode("\n", array_merge($news, [$tip]));
        }
    }
}

function fetch60sFromWechat($encode = 'json', $offset = 0, $isV1 = false, $force = false)
{
    $today     = date('Y-m-d', time() + 8 * 3600 - (int) $offset * 24 * 3600);
    $cachefile = '60s_wechat_' . $today . '.json';

    $finalData = cacheGet($cachefile, []);
    $fromCache = isset($finalData) && isset($finalData['news']);

    if (! $finalData || $force) {
        $ghproxy = getenv('GH_PROXY_URL');
        if (false == $ghproxy) {
            $ghproxy = 'https://ghfast.top/';
        }

        $api  = "{$ghproxy}https://raw.githubusercontent.com/vikiboss/60s-static-host/refs/heads/main/static/60s/$today.json";
        $data = @json_decode(file_get_contents($api), true);
        // var_dump($data);
        // die();

        if ($data && isset($data['news'])) {
            cacheSet($cachefile, $data);
            $finalData = $data;
        }
    }

    if ($isV1) {
        if ($encode === 'json') {
            return responseWithBaseRes($finalData['news'] ?? []);
        } else {
            return implode("\n", $finalData['news'] ?? []);
        }
    } else {
        if ($encode === 'json') {
            return responseWithBaseRes(array_merge($finalData, [
                'url'       => $finalData['link'] ?? '',
                'fromCache' => $fromCache,
            ]));
        } else {
            $text = "每天 60s 看世界（{$finalData['date']}）\n\n";

            foreach ($finalData['news'] as $key => $line) {
                $text .= ($key + 1) . ". $line\n";
            }

            $text .= "\n【微语】" . $finalData['tip'] ?? '';

            return $text;
        }
    }
}
