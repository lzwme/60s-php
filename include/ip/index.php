<?php
/**
 * 获取客户端IP地址
 * 示例：
 *  http://x.lzw.me/iapi/ip?json=1
 *  http://x.lzw.me/iapi/ip?json=1&from=ipshu&ip=121.11.121.1
 */

require_once __DIR__ . '/../utils.php';

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
            $ip = gethostbyname((string) parse_url($ip, PHP_URL_HOST));
        }

        // 非合法 IP（域名解析失败时会返回原域名）直接返回空结果，
        // 否则 ip2region 的 ip2long() 会失败，导致 shift 浮点数抛 TypeError
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [
                'country'  => '',
                'province' => '',
                'city'     => '',
                'timezone' => '',
                'isp'      => '',
                'region'   => '',
            ];
        }

        $data = [
            'country'  => '',
            'province' => '',
            'city'     => '',
            'timezone' => '',
            'isp'      => '',
        ];

        switch ($from) {
            case 'ipshu': {
                $cached = cacheGet($ip);
                if ($cached) {
                    return $cached;
                }

                ['res' => $html] = httpCurl("https://zh-hans.ipshu.com/ipv4/{$ip}");
                $html            = (string) $html;

                $fields = [
                    '城市纬度'       => ['lat', '/<tr><td>城市纬度.+ <\/td><td> (\d+\.\d+)<\/td>/'],
                    '城市经度'       => ['lng', '/<tr><td>城市经度.+ <\/td><td> (\d+\.\d+)<\/td>/'],
                    '城市名称'       => ['city', '/<tr><td>城市名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/'],
                    '地区名称'       => ['province', '/<tr><td>地区名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/'],
                    '国家或地区名称' => ['country', '/<tr><td>国家或地区名称.+ <\/td><td> (.+)\(第三方验证.+<\/td>/'],
                    '国家或地区代码' => ['countryCode', '/<tr><td>国家或地区代码.+ <\/td><td> (.+)<\/a>.+<\/td>/'],
                    '互联网服务提供商' => ['isp', '/<tr><td>互联网服务提供商.+ <\/td><td>(.+)<\/td>/'],
                    '时区'           => ['timezone', '/<tr><td>时区.+ <\/td><td>(.+)<\/td>/'],
                    '货币代码'       => ['currency', '/<tr><td>货币代码.+ <\/td><td>(.+)<\/td>/'],
                    '邮编'           => ['zip', '/<tr><td>邮编.+ <\/td><td>(.+)<\/td>/'],
                    '地区代码'       => ['regionCode', '/<tr><td>地区代码.+ <\/td><td>(.+)<\/td>/'],
                ];

                foreach ($fields as [$key, $regex]) {
                    if (preg_match($regex, $html, $match)) {
                        $value = trim(strip_tags($match[1]));
                        $data[$key] = in_array($key, ['lat', 'lng'], true) ? (float) $value : $value;
                    }
                }

                if (isset($data['lat'])) {
                    cacheSet($ip, $data, 3600 * 24 * 7);
                }

                break;
            }
            case 'ipapi': {
                $key             = getenv('IPAPI_KEY') ?: '';
                $url             = "https://pro.ip-api.com/json/{$ip}?fields=66842623&key={$key}";
                ['res' => $res]  = httpCurl($url, 'GET', null, ['referer: https://members.ip-api.com/', 'origin: https://members.ip-api.com/']);
                $data            = $res ? json_decode($res, true) : null;
                break;
            }
            case 'qqwry': {
                // qqwry 依赖 bin 数据文件，仓库中未包含；缺失时回退到 ip2region
                $qqwryFile = __DIR__ . '/qqwry/qqwry.php';
                if (! is_file($qqwryFile)) {
                    return self::getIpInfo($ip, 'ip2region');
                }

                require_once $qqwryFile;
                $data = qqwry($ip, 'json');
                if (isset($data[$ip])) {
                    $data = $data[$ip];
                }

                break;
            }
            case 'ip2region':
            default: {
                require_once __DIR__ . '/ip2region/XdbSearcher.php';
                $xdbPath = __DIR__ . '/ip2region/ip2region.xdb';

                if (! is_file($xdbPath)) {
                    $xdbPath = __DIR__ . '/../../.cache/ip2region.xdb';

                    if (! is_file($xdbPath)) {
                        $content = @file_get_contents('https://ip2region.net/data/ip2region.xdb');
                        if (! $content) {
                            return [
                                'country'  => '',
                                'province' => '',
                                'city'     => '',
                                'timezone' => '',
                                'isp'      => '',
                                'region'   => '',
                            ];
                        }
                        if (! is_dir(dirname($xdbPath))) {
                            @mkdir(dirname($xdbPath), 0775, true);
                        }
                        file_put_contents($xdbPath, $content);
                    }
                }

                try {
                    $searcher = XdbSearcher::newWithFileOnly($xdbPath);
                    $region   = $searcher->search($ip);
                    $searcher->close();
                } catch (Throwable $e) {
                    // 数据文件缺失或损坏时不中断接口，返回空结果
                    error_log('[60s] ip2region: ' . $e->getMessage());
                    $region = null;
                }

                $r = explode('|', (string) $region);
                $r = array_pad($r, 5, '');

                $data['region']   = (string) $region;
                $data['country']  = $r[0];
                $data['city_id']  = $r[1];
                $data['province'] = $r[2];
                $data['city']     = $r[3];
                $data['isp']      = $r[4];
                break;
            }
        }

        return $data;
    }
}
