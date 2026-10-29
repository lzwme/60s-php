<?php

require_once __DIR__ . '/utils.php';

class Weather
{
    public static function handle($encoding, $city_code, $mini = false)
    {
        // 注意：city_code 传入空字符串时 ?? 不会回退，需用 ?:
        $city_code = preg_match('/^\d{6,9}$/', (string) $city_code) ? $city_code : '';
        $data      = self::getWeather($city_code ?: self::getCityCode() ?: '101010100');

        if (! $data || ! isset($data['cityInfo'])) {
            return responseWithBaseRes($data, '未获取到城市信息', 404);
        }

        if ($encoding === 'text') {
            $forecast = is_array($data['data']['forecast'] ?? null) ? $data['data']['forecast'] : [];
            $tody     = is_array($forecast[0] ?? null) ? $forecast[0] : [];

            $msg = [
                '城市：' . ($data['cityInfo']['city'] ?? ''),
                '日期：' . ($tody['ymd'] ?? '') . ' ' . ($tody['week'] ?? ''),
                '天气：' . ($tody['type'] ?? ''),
                '温度：' . ($tody['high'] ?? '') . ' ' . ($tody['low'] ?? ''),
                '湿度：' . ($data['data']['shidu'] ?? ''),
                '空气质量：' . ($data['data']['quality'] ?? ''),
                'PM2.5：' . ($data['data']['pm25'] ?? ''),
                'PM10：' . ($data['data']['pm10'] ?? ''),
                '风力风向：' . ($tody['fx'] ?? '') . ' ' . ($tody['fl'] ?? ''),
                '感冒指数：' . ($data['data']['ganmao'] ?? ''),
                '[💌]温馨提示：' . ($tody['notice'] ?? ''),
                '更新时间：' . ($data['time'] ?? '') . "\n",
            ];

            foreach ($forecast as $day) {
                $ymd    = (string) ($day['ymd'] ?? '');
                $low    = (string) ($day['low'] ?? '');
                $high   = (string) ($day['high'] ?? '');
                $week   = (string) ($day['week'] ?? '');
                $type   = (string) ($day['type'] ?? '');
                $notice = (string) ($day['notice'] ?? '');

                if ($mini) {
                    $ymd    = substr(str_replace('-', '', $ymd), 4);
                    $low    = str_replace('低温', '', $low);
                    $high   = str_replace('高温', '', $high);
                    $week   = str_replace('星期', '', $week);
                    $msg[]  = "{$ymd} {$week} {$low} {$high} {$type}";
                } else {
                    $msg[] = "{$ymd} {$week} {$low} {$high} {$type} {$notice}";
                }
            }

            return implode("\n", $msg);
        } else {
            return responseWithBaseRes($data, $city_code ? '' : "未获取到城市码，默认返回北京天气");
        }
    }
    public static function getWeather($city_code)
    {
        // ${var} 插值写法自 PHP 8.2 起废弃，PHP 8.5 仍会告警，改为 {$var}
        $api     = "http://t.weather.itboy.net/api/weather/city/{$city_code}";
        $content = @file_get_contents($api);
        if ($content === false) {
            return null;
        }

        return json_decode($content, true);
    }
    public static function getCityCode()
    {
        $ip = get_real_ip();

        $cityApi  = 'https://fastly.jsdelivr.net/gh/Oreomeow/checkinpanel@master/city.json';
        $cacheKey = 'weather_city_code.json';
        $cityInfo = cacheGet($cacheKey);

        if (! $cityInfo) {
            $content = @file_get_contents($cityApi);
            if ($content !== false) {
                $cityInfo = json_decode($content, true);
            }
            if (is_array($cityInfo) && $cityInfo) {
                cacheSet($cacheKey, $cityInfo, 3600 * 24 * 7);
            }
        }

        require_once __DIR__ . '/ip/index.php';
        $info = IPQuery::getIpInfo($ip);
        if (! is_array($info)) {
            return null;
        }

        $city = str_replace('市', '', (string) ($info['city'] ?? ''));

        if ($city !== '' && is_array($cityInfo) && isset($cityInfo[$city])) {
            return $cityInfo[$city];
        }

        return null;
    }
}
