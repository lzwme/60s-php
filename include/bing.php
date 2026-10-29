<?php
require_once __DIR__ . '/utils.php';

function fetchBing($type = 'json')
{
    $api       = 'https://cn.bing.com';
    $cachefile = 'bing_' . date('Y-m-d') . '.json';

    $data = cacheGet($cachefile);

    if (! is_array($data) || $data === []) {
        $today      = date('Y-m-d');
        $rawContent = reqGet($api, 'curl');

        if (! $rawContent) {
            $rawContent = reqGet('https://www.bing.com', 'curl');
            $reg        = '/<link rel="preload" href="([^"]+)" as="image" id="preloadBg"/';

            if ($rawContent && preg_match($reg, $rawContent, $matches)) {
                $data = [
                    'date'      => $today,
                    'image_url' => 'https://www.bing.com' . $matches[1],
                ];
                cacheSet($cachefile, $data);
            } else {
                $data = [];
            }
        } else if (preg_match('/var _model =([^;]+);;var/', $rawContent, $matches)) {
            $rawJson = $matches[1];
            $decoded = json_decode($rawJson, true);
            $images  = is_array($decoded['MediaContents'] ?? null) ? $decoded['MediaContents'] : [];

            if (! empty($images)) {
                $imageContent = $images[0]['ImageContent'] ?? [];
                $image        = $imageContent['Image'] ?? [];

                $data = [
                    'date'        => $today,
                    'headline'    => $imageContent['Headline'] ?? '',
                    'title'       => $imageContent['Title'] ?? '',
                    'description' => $imageContent['Description'] ?? '',
                    // 原写法 'https://cn.bing.com' . $image['Wallpaper'] ?? '' 受运算符优先级影响，
                    // 实际等价于 ('https://cn.bing.com' . $image['Wallpaper']) ?? ''，缺少字段时会先告警
                    'image_url'   => 'https://cn.bing.com' . ($image['Wallpaper'] ?? ''),
                    'main_text'   => $imageContent['QuickFact']['MainText'] ?? '',
                    'copyright'   => $imageContent['Copyright'] ?? '',
                ];

                cacheSet($cachefile, $data);
            } else {
                $data = [];
            }
        } else {
            $data = [];
        }
    }

    $data = is_array($data) ? $data : [];

    if ($type === 'image') {
        if (! empty($data['image_url'])) {
            header("Location: " . $data['image_url']);
        } else {
            http_response_code(404);
        }

        exit;
    }

    if ($type === 'text') {
        return $data['image_url'] ?? '';
    }

    return responseWithBaseRes($data);
}
