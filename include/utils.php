<?php

function randomId($size)
{
    $size     = max(0, (int) $size);
    $alphabet = '1234567890abcdefghijklmnopqrstuvwxyz';
    $str      = '';
    $max      = strlen($alphabet) - 1;
    for ($i = 0; $i < $size; $i++) {
        $str .= $alphabet[rand(0, $max)];
    }
    return $str;
}

function responseWithBaseRes($obj, $message = '', $status = 200, $toText = true)
{
    $defaultTips = '数据来自官方，实时更新, 代码开源地址: https://github.com/lzwme/60s-php';

    $res = [
        'status'  => (int) $status,
        // 这里必须用 ?: 而不是 ??，否则传入空字符串时不会回退到默认提示
        'message' => $message ?: $defaultTips,
        'data'    => $obj ?: [],
    ];

    if (! $toText) {
        return $res;
    }

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
    $json  = json_encode($res, $flags);

    if ($json === false) {
        // 极端数据（递归引用、INF/NAN 等）下的兜底，保证始终返回合法 JSON
        $res['data'] = [];
        $json        = json_encode($res, $flags);
    }

    return $json === false ? '{"status":500,"message":"json encode error","data":[]}' : $json;
}

function transferText($str, $mode = '')
{
    $str = (string) $str;

    if ($mode === 'a2u') {
        return preg_replace_callback('/&#(\d+);/', function ($matches) {
            // chr() 在 PHP 8.5 起会对超出 0-255 的值给出废弃提示，这里显式取模
            return chr(((int) $matches[1]) % 256);
        }, $str);
    }

    $result = '';
    $len    = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $result .= '&#' . ord($str[$i]) . ';';
    }

    return $result;
}

/**
 * 是否启用 SSL 证书校验（默认开启）。
 * 个别老服务器缺少 CA 根证书时可通过环境变量 CURL_SSL_VERIFY=0 临时关闭。
 */
function sslVerifyEnabled()
{
    static $enabled = null;
    if ($enabled === null) {
        $env     = strtolower(trim((string) getenv('CURL_SSL_VERIFY')));
        $enabled = ! in_array($env, ['0', 'false', 'no', 'off'], true);
    }

    return $enabled;
}

function reqGet($url, $type = 'get')
{
    if ($type === 'curl') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 以字符串返回
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if (preg_match('/^https:\/\//i', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, sslVerifyEnabled());
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, sslVerifyEnabled() ? 2 : 0);
        }

        $result = curl_exec($ch);
        // PHP 8.5 起 curl_close() 已被废弃；PHP >= 8.0 的 CurlHandle 离开作用域会自动释放
        return $result;
    }

    $opts = [
        'http' => ['timeout' => 20, 'follow_location' => 1],
        'ssl'  => [
            'verify_peer'      => sslVerifyEnabled(),
            'verify_peer_name' => sslVerifyEnabled(),
        ],
    ];

    return @file_get_contents($url, false, stream_context_create($opts));
}

$CACHE_DIR = __DIR__ . '/../.cache/';

/**
 * 解析缓存文件路径。
 * xxx.json 形式直接作为文件名（需过滤路径字符），其余 key 使用 md5，避免路径穿越。
 */
function cacheFilePath($key)
{
    global $CACHE_DIR;
    $key = (string) $key;

    $suffix = strrchr($key, '.');
    if ($suffix !== false && strtolower(substr($suffix, 1)) === 'json') {
        $name = ltrim(preg_replace('/[^A-Za-z0-9_.\-]/', '_', $key), '.');
        if ($name !== '') {
            return $CACHE_DIR . $name;
        }
    }

    return $CACHE_DIR . md5($key);
}

function cacheSet($key, $data, $maxAge = 0)
{
    global $CACHE_DIR;

    if (! is_dir($CACHE_DIR) && ! @mkdir($CACHE_DIR, 0775, true) && ! is_dir($CACHE_DIR)) {
        return false;
    }

    $payload = json_encode(
        ['data' => $data, 't' => $maxAge ? time() + (int) $maxAge : 0],
        JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($payload === false) {
        return false;
    }

    // 先写临时文件再原子替换，避免并发请求读到写了一半的缓存
    $filepath = cacheFilePath($key);
    $tmp      = $filepath . '.' . uniqid('', true) . '.tmp';

    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
        return false;
    }

    if (! @rename($tmp, $filepath)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

function cacheGet($key, $defaults = null)
{
    $filepath = cacheFilePath($key);

    if (! is_file($filepath)) {
        return $defaults;
    }

    $raw = @file_get_contents($filepath);
    if ($raw === false || $raw === '') {
        return $defaults;
    }

    $result = json_decode($raw, true);
    if (! is_array($result) || ! array_key_exists('data', $result)) {
        @unlink($filepath);
        return $defaults;
    }

    $expiresAt = (int) ($result['t'] ?? 0);
    if ($expiresAt === 0 || time() < $expiresAt) {
        return $result['data'];
    }

    @unlink($filepath);
    return $defaults;
}

function httpCurl($url, $method = 'GET', $postfields = null, $headers = null, $debug = false)
{
    $method = strtoupper((string) $method);
    $ci     = curl_init();

    /* Curl settings */
    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 10); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 15); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2); // 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的

    // 注意：CURLINFO_HEADER_OUT 是 curl_getinfo() 的常量，不是合法 option，设置为 CURLOPT_HEADER 才有意义

    //设置请求头
    if (! empty($headers) && is_array($headers)) {
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    }

    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (! empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }

    $ssl = (bool) preg_match('/^https:\/\//i', $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, sslVerifyEnabled()); // https 请求校验证书
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, sslVerifyEnabled() ? 2 : 0);
    }

    $response    = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code   = (int) ($requestinfo['http_code'] ?? 0);

    if ($response === false) {
        $requestinfo['error'] = curl_error($ci);
        $requestinfo['errno'] = curl_errno($ci);
    }

    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }

    // PHP 8.5 起 curl_close() 已被废弃，句柄由 GC 自动释放
    return array('code' => $http_code, 'res' => $response, 'req' => $requestinfo);
}

function tryGetReqParam($keys, $default = null)
{
    $keysList = is_string($keys) ? [$keys] : (array) $keys;

    foreach ($keysList as $key) {
        if (! is_string($key) || $key === '' || ! isset($_REQUEST[$key])) {
            continue;
        }

        $value = $_REQUEST[$key];

        // 只接受标量；PHP 8 起 htmlspecialchars(array) 会直接抛 TypeError
        if (! is_scalar($value)) {
            continue;
        }

        // 不使用 htmlspecialchars：参数会被放入 JSON、URL、正则、缓存名等场景，
        // HTML 转义不可逆（会把 & 变成 &amp; 等），仅在输出 HTML 时才需要
        return trim(strip_tags((string) $value));
    }

    return $default;
}

/**
 * 获取布尔型请求参数（如 v1、trending、cors、nocache）。
 * 裸参数（?v1）视为 true；v1=0 / v1=false 视为 false。
 */
function tryGetReqFlag($key)
{
    if (! array_key_exists($key, $_REQUEST) || ! is_scalar($_REQUEST[$key])) {
        return false;
    }

    $value = $_REQUEST[$key];
    if ($value === '' || $value === null) {
        return true;
    }

    return (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

/**
 * 判断是否为内网/保留地址。
 */
function isPrivateIp($ip)
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

function get_real_ip()
{
    static $realip = null;
    if ($realip !== null) {
        return $realip;
    }

    $serverValue = function ($key) {
        return isset($_SERVER[$key]) && is_string($_SERVER[$key]) ? trim($_SERVER[$key]) : '';
    };

    $realip = '';

    if (isset($_SERVER)) {
        $realip = $serverValue('REMOTE_ADDR');

        $forwarded = $serverValue('HTTP_X_FORWARDED_FOR');
        if ($forwarded !== '') {
            foreach (explode(',', $forwarded) as $xip) {
                $xip = trim($xip);
                if (filter_var($xip, FILTER_VALIDATE_IP) && ! isPrivateIp($xip)) {
                    $realip = $xip;
                    break;
                }
            }
        } elseif (($clientIp = $serverValue('HTTP_CLIENT_IP')) !== '' && filter_var($clientIp, FILTER_VALIDATE_IP)) {
            $realip = $clientIp;
        } elseif (($cfIp = $serverValue('HTTP_CF_CONNECTING_IP')) !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            $realip = $cfIp;
        } elseif (($realIp = $serverValue('HTTP_X_REAL_IP')) !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
            $realip = $realIp;
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = (string) getenv("REMOTE_ADDR");
        }
    }

    return $realip = str_replace("::ffff:", "", $realip);
}
