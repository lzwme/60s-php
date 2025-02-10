<?php
require_once 'utils.php';

function fetchBing($type = 'json')
{
    $api       = 'https://cn.bing.com';
    $cachefile = 'bing_' . date('Y-m-d') . '.json';

    $data = cacheGet($cachefile);

    if (! $data) {
        $today      = date('Y-m-d', time()); // PHP 默认就是 'Y-m-d' 格式
        $rawContent = reqGet($api, 'curl');

        if (! $rawContent) {
            $rawContent = reqGet('https://www.bing.com', 'curl');
            $reg        = '/<link rel="preload" href="([^"]+)" as="image" id="preloadBg"/';

            if (preg_match($reg, $rawContent, $matches)) {
                $data = [
                    'date'      => $today,
                    'image_url' => 'https://www.bing.com' . $matches[1],
                ];
            } else {
                $data = [];
            }
        } else if (preg_match('/var _model =([^;]+);;var/', $rawContent, $matches)) {
            $rawJson = $matches[1];
            $images  = json_decode($rawJson, true)['MediaContents'] ?? [];

            if (! empty($images)) {
                $imageContent = $images[0]['ImageContent'] ?? [];
                $image        = $imageContent['Image'] ?? [];

                $data = [
                    'date'        => $today,
                    'headline'    => $imageContent['Headline'] ?? '',
                    'title'       => $imageContent['Title'] ?? '',
                    'description' => $imageContent['Description'] ?? '',
                    'image_url'   => 'https://cn.bing.com' . $image['Wallpaper'] ?? '',
                    'main_text'   => $imageContent['QuickFact']['MainText'] ?? '',
                    'copyright'   => $imageContent['Copyright'] ?? '',
                ];

                cacheSet($cachefile, $data);
            } else {
                $data = [];
            }
        }
    }

    if ($type === 'image') {
        if ($data['image_url']) {
            header("Location: " . $data['image_url']);
        }

        die();
    }

    if ($type === 'text') {
        return $data['image_url'] ?? '';
    } else {
        return responseWithBaseRes($data);
    }
}
