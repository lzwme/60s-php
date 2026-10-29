<?php
// 作为 API 端点，错误信息不应混入响应体（会破坏 JSON）；
// 本地调试时把 display_errors 临时改成 '1' 即可。
error_reporting(E_ALL);
ini_set('display_errors', '0');
// date.timezone 是 PHP_INI_ALL，但 date_default_timezone_set() 才是标准 API
date_default_timezone_set('Asia/Shanghai');

require_once __DIR__ . '/include/utils.php';
require_once __DIR__ . '/include/blacklist.php';

// 分类
$type = tryGetReqParam(['t', 'type', 'cate'], '60s');
// 格式
$encode  = tryGetReqParam(['e', 'encode', 'encoding', 'format'], 'json');
$nocache = tryGetReqFlag('nocache');

$result = '';

// 这里刻意使用 try/catch：线上未开启 display_errors 时，未捕获的 Throwable 只会返回空白页，
// 前端将无法区分「无数据」和「服务出错」。
try {
    switch ($type) {
        case 'zhihu':
            require_once __DIR__ . '/include/zhihu.php';
            $result = fetchZhihu($encode);
            break;
        case 'bili':
            require_once __DIR__ . '/include/bili.php';
            $result = fetchBili($encode);
            break;
        case 'weibo':
            require_once __DIR__ . '/include/weibo.php';
            $result = fetchWeibo($encode);
            break;
        case 'toutiao':
            require_once __DIR__ . '/include/toutiao.php';
            $result = fetchToutiao($encode);
            break;
        case 'douyin':
            require_once __DIR__ . '/include/douyin.php';
            $result = fetchDouyin($encode, tryGetReqFlag('trending') ? 'trending' : 'word');
            break;
        case 'bing':
            require_once __DIR__ . '/include/bing.php';
            $result = fetchBing($encode);
            break;
        case 'history':
        case 'events':
            require_once __DIR__ . '/include/eventsOnHistory.php';
            $result = fetchEventsOnHistory($encode, tryGetReqParam('day', ''));
            break;
        case 'ex-rates':
        case 'rate':
            require_once __DIR__ . '/include/ext-rates.php';
            $result = fetchRatesByCurrency($encode, tryGetReqParam('c', 'CNY'));
            break;
        case '60s':
            require_once __DIR__ . '/include/60s.php';
            $result = fetch60s($encode, (int) tryGetReqParam('offset', 0), tryGetReqFlag('v1'), $nocache);
            break;
        case 'ip':
            require_once __DIR__ . '/include/ip/index.php';
            $result = IPQuery::handle($encode);
            break;
        case 'maoyan':
            require_once __DIR__ . '/include/maoyan.php';
            $result = Maoyan::handle($encode);
            break;
        case 'changya':
            require_once __DIR__ . '/include/changya.php';
            $result = Changya::handle($encode);
            break;
        case 'weather':
            require_once __DIR__ . '/include/weather.php';
            $city_code = tryGetReqParam(['city_code', 'cityCode', 'city']);
            $result    = Weather::handle($encode, $city_code, (int) tryGetReqParam(['mini'], 0));
            break;
        case 'answer':
        case 'duanzi':
        case 'fabing':
        case 'hitokoto':
        case 'yiyan':
        case 'v50':
        case 'kfc':
        case 'luck':
            require_once __DIR__ . '/include/ArrayRandom.php';
            $result = ArrayRandom::handle($type, $encode);
            break;
        default:
            http_response_code(400);
            $result = "unknown type: {$type}";
            if ($encode === 'json') {
                $result = responseWithBaseRes([], $result, 400);
            }
    }
} catch (Throwable $e) {
    error_log('[60s] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    $result = $encode === 'json'
        ? responseWithBaseRes([], '服务内部错误，请稍后重试', 500)
        : '服务内部错误，请稍后重试';
}

$ctype = $encode === 'text' ? 'text/plain' : 'application/json';
header("Content-Type: {$ctype}; charset=utf-8");

if (! $nocache) {
    $seconds_to_cache = 60; // 1 分钟缓存
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT");
    // 两个 Cache-Control 头会相互覆盖，合并成一个
    header("Cache-Control: public, max-age=$seconds_to_cache");
    header("Pragma: cache");
}

if (tryGetReqFlag('cors')) {
    // 注意：Access-Control-Allow-Credentials: true 不能与 Access-Control-Allow-Origin: * 同时使用，浏览器会拒绝，
    // 因此这里不发送 Allow-Credentials
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

die(is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
