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

function httpCurl($url, $method = 'GET', $postfields = null, $headers = null, $debug = false)
{
    $method = strtoupper($method);
    $ci = curl_init();

    /* Curl settings */
    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);

    //curl_setopt($ci, CURLOPT_HEADER, true); // 启用时会将头文件的信息作为数据流输出
    // 是否不需要响应的正文, 在只需要响应头的情况下可以不要正文
    //curl_setopt($curl, CURLOPT_NOBODY, true);

    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2); // 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    // curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr);

    //设置请求头
    if (!empty($headers)) {
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    }

    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }

    $ssl = preg_match('/^https:\/\//i', $url) ? true : false;
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    }

    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = $requestinfo['http_code'];

    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    // return $response;
    return array('code' => $http_code, 'res' => $response, 'req' => $requestinfo);
}
