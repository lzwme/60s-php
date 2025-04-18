<?php
require_once __DIR__ . '/utils.php';

class ArrayRandom
{
    public static function handle($type, $encoding, $arg1 = null)
    {
        $json = self::getContent($type);
        $idx  = array_rand($json);
        $item = $json[$idx];

        if ($type === 'fabing') {
            if (! $arg1) {
                $arg1 = tryGetReqParam(['name'], '主人');
            }

            $item = str_replace('[name]', $arg1, $item);
        } else if ($type === 'luck') {
            $tipIdex = array_rand($item['content']);
            $tip     = $item['content'][$tipIdex];

            if ($encoding == 'text') {
                return "{$item['good-luck']}: {$tip}";
            }

            $item = [
                'luck_desc'      => $item['good-luck'],
                'luck_rank'      => item['rank'],
                'luck_tip'       => $tip,
                'luck_tip_index' => $tipIdex,
            ];
        }

        if ($encoding == 'text') {
            if (is_string($item)) {
                return $item;
            }

            foreach (['answer', 'content', 'text'] as $key) {
                if (isset($item[$key])) {
                    return $item[$key];
                }
            }
        } else {
            return responseWithBaseRes($item);
        }
    }
    public static function getContent($type)
    {
        $json = [];

        if ($type === 'v50' || $type === 'kfc') {
            $api  = 'https://cdn.jsdelivr.net/gh/vikiboss/v50@main/static/v50.json';
            $cacheKey = 'v50.json';
            $json = cacheGet($cacheKey);

            if (! $json) {
                $json = json_decode(file_get_contents($api), true);
                if (is_array($json)) {
                    cacheSet($cacheKey, $json, 3600 * 24);
                }

            }
        } else {
            if ($type === 'yiyan') {
                $type = 'hitokoto';
            }

            $jsonFile = __DIR__ . "/../jsonData/{$type}.json";
            if (! file_exists($jsonFile)) {
                return responseWithBaseRes('', "未知的 API 类型：{$type}", 401);
            }

            $json = json_decode(file_get_contents($jsonFile), true);
        }

        return $json;
    }
}
