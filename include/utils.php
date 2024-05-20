<?php

function randomId($size)
{
    $alphabet = '1234567890abcdefghijklmnopqrstuvwxyz';
    $str = '';
    for ($i = 0; $i < $size; $i++) {
        $str .= $alphabet[rand(0, strlen($alphabet) - 1)];
    }
    return $str;
}

function responseWithBaseRes($obj, $message = '', $status = 200, $toText = true)
{
    $defaultTips = '数据来自官方，实时更新, 代码开源地址: https://github.com/lzwme/blog-examples/blob/main/examples/60s';

    $res = [
        'status' => $status,
        'message' => $message || $defaultTips,
        'data' => $obj ?: [],
    ];
    return $toText ? json_encode($res, 2) : $res;
}

function transferText($str, $mode)
{
    if ($mode === 'a2u') {
        $callback = function ($matches) {
            return chr((int) $matches[1]);
        };
        return preg_replace_callback('/&#(\d+);/', $callback, $str);
    } else {
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $result .= '&#' . ord($str[$i]) . ';';
        }
        return $result;
    }
}

function reqGet($url, $type = 'get')
{
    if ($type === 'curl') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 以字符串返回
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0");

        if (preg_match('/^https:\/\//i', $url)) {
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    } else {
        $stream_opts = ["ssl" => ["verify_peer" => false, "verify_peer_name" => false]];
        return file_get_contents($url, false, stream_context_create($stream_opts));
    }
}

$CACHE_DIR = './.cache/';

function cacheSet($key, $data, $maxAge = 0)
{
    global $CACHE_DIR;
    if (!file_exists($CACHE_DIR)) {
        mkdir($CACHE_DIR);
    }

    $filepath = $CACHE_DIR . (substr(strrchr($key, '.'), 1) == 'json' ? $key : md5($key));

    file_put_contents($filepath, json_encode([
        'data' => $data,
        't' => $maxAge ? time() + $maxAge : 0,
    ]));
}

function cacheGet($key)
{
    global $CACHE_DIR;
    $filepath = $CACHE_DIR . (substr(strrchr($key, '.'), 1) == 'json' ? $key : md5($key));

    if (file_exists($filepath)) {
        $result = json_decode(file_get_contents($filepath), true);

        if ($result && 0 == $result['t'] || time() < $result['t']) {
            return $result['data'];
        }

        unlink($filepath);
    }

    return;
}
