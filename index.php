<?php
// error_reporting(E_ALL);
// ini_set("display_errors", '1');
ini_set('date.timezone', 'Asia/Shanghai');
require_once './include/utils.php';
require_once './include/blacklist.php';

// 分类
$type = tryGetReqParam(['t', 'type', 'cate'], '60s');
// 格式
$encode  = tryGetReqParam(['e', 'encode'], 'json');
$nocache = isset($_REQUEST['nocache']);

$result = '';

switch ($type) {
    case 'zhihu':
        require './include/zhihu.php';
        $result = fetchZhihu($encode);
        break;
    case 'bili':
        require './include/bili.php';
        $result = fetchBili($encode);
        break;
    case 'weibo':
        require './include/weibo.php';
        $result = fetchWeibo($encode);
        break;
    case 'toutiao':
        require './include/toutiao.php';
        $result = fetchToutiao($encode);
        break;
    case 'douyin':
        require './include/douyin.php';
        $result = fetchDouyin($encode, isset($_GET['trending']) ? 'trending' : 'word');
        break;
    case 'bing':
        require './include/bing.php';
        $result = fetchBing($encode);
        break;
    case 'history':
    case 'events':
        require './include/eventsOnHistory.php';
        $result = fetchEventsOnHistory($encode, $_GET['day'] ?? '');
        break;
    case 'ex-rates':
    case 'rate':
        require './include/ext-rates.php';
        $result = fetchRatesByCurrency($encode, $_GET['c'] ?? 'CNY');
        break;
    case '60s':
        require './include/60s.php';
        $result = fetch60s($encode, $_GET['offset'] ?? 0, isset($_GET['v1']), $nocache);
        break;
    case 'ip':
      require './include/ip/index.php';
        $result = IPQuery::handle($encode);
        break;
    case 'maoyan':
        require './include/maoyan.php';
        $result = Maoyan::handle($encode);
        break;
    case 'changya':
        require './include/changya.php';
        $result = Changya::handle($encode);
        break;
    case 'weather':
        require './include/weather.php';
        $result = Weather::handle($encode);
        break;
    case 'answer':
    case 'duanzi':
    case 'fabing':
    case 'hitokoto':
    case 'yiyan':
    case 'v50':
    case 'kfc':
        require './include/ArrayRandom.php';
        $result = ArrayRandom::handle($type, $encode);
        break;
    default:
        $result = "unkown type: {$type}";
        if ($encode === 'json') {
            $result = responseWithBaseRes('', $result, 400);
        }
}

$ctype = $encode === 'text' ? 'text/plain' : 'application/json';
header("Content-Type: {$ctype}; charset=utf-8");

if (! $nocache) {
    $seconds_to_cache = 60; // 1 分钟缓存
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT");
    header("Cache-Control: max-age=$seconds_to_cache");
    header("Cache-Control: public");
    header("Pragma: cache");
}

if (isset($_GET['cors'])) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
}

die(is_string($result) ? $result : json_encode($result));
