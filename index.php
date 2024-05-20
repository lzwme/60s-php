<?php
// 分类
$cate = $_GET['t'] ?? $_GET['type'] ?? $_GET['cate'] ?? '60s';
// 格式
$encode = $_GET['e'] ?? $_GET['encode'] ?? 'json';

$result = '';

switch ($cate) {
    case 'zhihu':
        require 'include/zhihu.php';
        $result = fetchZhihu($encode);
        break;
    case 'bili':
        require 'include/bili.php';
        $result = fetchBili($encode);
        break;
    case 'weibo':
        require 'include/weibo.php';
        $result = fetchWeibo($encode);
        break;
    case 'toutiao':
        require 'include/toutiao.php';
        $result = fetchToutiao($encode);
        break;
    case 'douyin':
        require 'include/douyin.php';
        $result = fetchDouyin($encode, isset($_GET['trending']) ? 'trending' : 'word');
        break;
    case 'bing':
        require 'include/bing.php';
        $result = fetchBing($encode);
        break;
    case 'history':
    case 'events':
        require 'include/eventsOnHistory.php';
        $result = fetchEventsOnHistory($encode, $_GET['day'] ?? '');
        break;
    case 'ex-rates':
    case 'rate':
        require 'include/ext-rates.php';
        $result = fetchRatesByCurrency($encode, $_GET['c'] ?? 'CNY');
        break;
    case '60s':
    default:
        require 'include/60s.php';
        $result = fetch60s($encode, $_GET['offset'] ?? 0, isset($_GET['v1']));
        break;
}

header('Content-Type: application/json');

if (!isset($_REQUEST['nocache'])) {
    $seconds_to_cache = 600; // 10 分钟缓存
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT");
    header("Cache-Control: max-age=$seconds_to_cache");
    header("Cache-Control: public");
    header("Pragma: cache");
}

if (isset($_GET['cors'])) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
}

die(is_string($result) ? $result : json_encode($result));
