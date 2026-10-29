<?php
require_once __DIR__ . '/utils.php';

class ArrayRandom
{
    public static function handle($type, $encoding, $arg1 = null)
    {
        $json = self::getContent($type);

        // getContent 失败时返回 null，而 array_rand([]) 在 PHP 8 会抛出 ValueError
        if (! is_array($json) || $json === []) {
            if ($type === 'v50' || $type === 'kfc') {
                return responseWithBaseRes([], 'v50 段子数据获取失败', 502);
            }

            return responseWithBaseRes([], "未知或暂无可用的 API 类型：{$type}", 401);
        }

        $item = $json[array_rand($json)];

        if ($type === 'fabing') {
            if (! $arg1) {
                $arg1 = tryGetReqParam('name', '主人');
            }

            if (is_string($item)) {
                $item = str_replace('[name]', (string) $arg1, $item);
            }
        } else if ($type === 'luck') {
            $tipList = $item['content'] ?? [];
            if (! is_array($tipList) || $tipList === []) {
                return responseWithBaseRes([], '运势数据异常', 500);
            }

            $tipIndex = array_rand($tipList);
            $tip      = $tipList[$tipIndex];

            if ($encoding == 'text') {
                return ($item['good-luck'] ?? '') . ': ' . $tip;
            }

            $item = [
                'luck_desc'      => $item['good-luck'] ?? '',
                'luck_rank'      => $item['rank'] ?? null,
                'luck_tip'       => $tip,
                'luck_tip_index' => $tipIndex,
            ];
        }

        if ($encoding == 'text') {
            if (is_string($item)) {
                return $item;
            }

            if (is_array($item)) {
                foreach (['answer', 'content', 'text'] as $key) {
                    if (isset($item[$key])) {
                        return is_string($item[$key])
                            ? $item[$key]
                            : (string) json_encode($item[$key], JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            return '';
        }

        return responseWithBaseRes($item);
    }

    public static function getContent($type)
    {
        $json = [];

        if ($type === 'v50' || $type === 'kfc') {
            $api      = 'https://cdn.jsdelivr.net/gh/vikiboss/v50@main/static/v50.json';
            $cacheKey = 'v50.json';
            $json     = cacheGet($cacheKey);

            if (! is_array($json) || $json === []) {
                // 部分环境无法直连 jsdelivr，失败时不缓存，下次请求会重试
                $content = reqGet($api);
                $json    = is_string($content) && $content !== '' ? json_decode($content, true) : null;

                if (is_array($json) && $json !== []) {
                    cacheSet($cacheKey, $json, 3600 * 24);
                }
            }
        } else {
            if ($type === 'yiyan') {
                $type = 'hitokoto';
            }

            // 防目录穿越：$type 来自请求参数
            $type     = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $type);
            $jsonFile = __DIR__ . "/../jsonData/{$type}.json";

            if ($type === '' || ! is_file($jsonFile)) {
                return null;
            }

            $content = is_file($jsonFile) ? @file_get_contents($jsonFile) : false;
            $json    = is_string($content) ? json_decode($content, true) : null;
        }

        return is_array($json) ? $json : null;
    }
}
