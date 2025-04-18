<?php
/**
 * 获取客户端IP地址
 * 示例：
 *  http://x.lzw.me/iapi/ip?json=1
 *  http://x.lzw.me/iapi/ip?json=1&from=ipshu&ip=121.11.121.1
 */

include_once __DIR__ . '/../utils.php';

class IPQuery
{
    public static function handle($encoding, $ip = '')
    {
        if ($encoding === 'text') {
            return get_real_ip();
        }

        if (! $ip) {
            $ip = tryGetReqParam('ip') ?? get_real_ip();
        }

        $valid = filter_var($ip, FILTER_VALIDATE_IP);

        $info = [
            'ip'   => $ip,
            'img'  => $valid ? 'https://zh-hans.ipshu.com/picture/' . $ip . '.png' : '',
            'data' => self::getIpInfo($ip, tryGetReqParam('from', 'ip2region')),
            'code' => $valid ? 200 : 400,
        ];

        return responseWithBaseRes($info);
    }
    public static function getIpInfo($ip, $from = 'ip2region')
    {
        if (! $ip) {
            return null;
        }

        // 如果是 http 开头的域名 url 格式，获取其 ip
        if (filter_var($ip, FILTER_VALIDATE_URL)) {
            $ip = gethostbyname(parse_url($ip, PHP_URL_HOST));
        }

        $data = [
            'country'  => '',
            'province' => '',
            'city'     => '',
            'timezone' => '',
            'isp'      => '',
        ];

        switch ($from) {
            case 'ipshu':{
                    $info = cacheGet($ip);
                    if ($info) {
                        return $info;
                    }

                    ['res' => $html] = httpCurl("https://zh-hans.ipshu.com/ipv4/$ip");

                    preg_match('/<tr><td>城市纬度.+ <\/td><td> (\d+\.\d+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['lat'] = (float) $match[1];
                    }

                    preg_match('/<tr><td>城市经度.+ <\/td><td> (\d+\.\d+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['lng'] = (float) $match[1];
                    }

                    preg_match('/<tr><td>城市名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['city'] = trim($match[1]);
                    }

                    preg_match('/<tr><td>地区名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['province'] = trim($match[1]);
                    }

                    preg_match('/<tr><td>地区名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['province'] = trim($match[1]);
                    }

                    preg_match('/<tr><td>国家或地区名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['country'] = trim(strip_tags($match[1]));
                    }

                    preg_match('/<tr><td>国家或地区代码.+ <\/td><td> (.+)<\/a>.+<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['countryCode'] = trim(strip_tags($match[1]));
                    }

                    // === 更多信息中提取

                    preg_match('/<tr><td>互联网服务提供商.+ <\/td><td>(.+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['isp'] = trim(strip_tags($match[1]));
                    }

                    preg_match('/<tr><td>时区.+ <\/td><td>(.+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['timezone'] = trim(strip_tags($match[1]));
                    }

                    preg_match('/<tr><td>货币代码.+ <\/td><td>(.+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['currency'] = trim(strip_tags($match[1]));
                    }

                    preg_match('/<tr><td>邮编.+ <\/td><td>(.+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['zip'] = trim(strip_tags($match[1]));
                    }

                    preg_match('/<tr><td>地区代码.+ <\/td><td>(.+)<\/td>/', $html, $match);
                    if (count($match)) {
                        $data['regionCode'] = trim(strip_tags($match[1]));
                    }

                    if (isset($data['lat'])) {
                        cacheSet($ip, $data, 3600 * 24 * 7);
                    }

                    break;
                }
            case 'ipapi':{
                    $key             = getenv('IPAPI_KEY');
                    $url             = "https://pro.ip-api.com/json/{$ip}?fields=66842623&key={$key}";
                    ['res' => $data] = httpCurl($url, 'GET', null, ['referer: https://members.ip-api.com/', 'origin: https://members.ip-api.com/']);
                    $data            = $data ? json_decode($data, true) : null;
                    break;
                }
            case 'qqwry':{
                    require_once __DIR__ . '/qqwry/qqwry.php';
                    $data = qqwry($ip, 'json');
                    if (isset($data[$ip])) {
                        $data = $data[$ip];
                    }

                    break;
                }
            case 'ip2region':
            default:
                require_once __DIR__ . '/ip2region/XdbSearcher.php';
                $xdbPath = __DIR__ . '/ip2region/ip2region.xdb';

                if (! file_exists($xdbPath)) {
                    $xdbPath = __DIR__ . '/../../.cache/ip2region.xdb';

                    if (! file_exists($xdbPath)) {
                        // $xdbApi  = 'https://fastly.jsdelivr.net/gh//lionsoul2014/ip2region@master/data/ip2region.xdb';
                        $xdbApi = 'https://fastly.jsdelivr.net/gh/zoujingli/Ip2Region@master/data/ip2region.xdb';
                        $data   = file_get_contents('https://ip2region.net/data/ip2region.xdb');
                        file_put_contents($xdbPath, $data);
                    }
                }

                $searcher = XdbSearcher::newWithFileOnly($xdbPath);
                $region   = $searcher->search($ip);
                $r        = explode('|', $region);

                $data['region']   = $region;
                $data['country']  = $r[0];
                $data['city_id']  = $r[1];
                $data['province'] = $r[2];
                $data['city']     = $r[3];
                $data['isp']      = $r[4];
                break;
        }

        return $data;
    }
}
