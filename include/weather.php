<?php

require_once __DIR__ . '/utils.php';

class Weather
{
    public static function handle($encoding, $city_code, $mini = false)
    {
        $data = self::getWeather($city_code ?? self::getCityCode() ?? '101010100');

        if (! $data || ! isset($data['cityInfo'])) {
            return responseWithBaseRes($data, '未获取到城市信息', 404);
        }

        if ($encoding === 'text') {
            $tody = $data['data']['forecast'][0];
            $msg  = [
                "城市：{$data['cityInfo']['city']}",
                "日期：{$tody['ymd']} {$tody['week']}",
                "天气：{$tody['type']}",
                "温度：{$tody['high']} {$tody['low']}",
                "湿度：{$data['data']['shidu']}",
                "空气质量：{$data['data']['quality']}",
                "PM2.5：{$data['data']['pm25']}",
                "PM10：{$data['data']['pm10']}",
                "风力风向：{$tody['fx']} {$tody['fl']}",
                "感冒指数：{$data['data']['ganmao']}",
                "[💌]温馨提示：{$tody['notice']}",
                "更新时间：{$data['time']}\n",
            ];

            foreach ($data['data']['forecast'] as $day) {
                if ($mini) {
                    $day['ymd']  = substr(str_replace('-', '', $day['ymd']), 4);
                    $day['low']  = str_replace('低温', '', $day['low']);
                    $day['high'] = str_replace('高温', '', $day['high']);
                    $day['week'] = str_replace('星期', '', $day['week']);
                    $msg[]       = "{$day['ymd']} {$day['week']} {$day['low']} {$day['high']} {$day['type']}";
                } else {
                    $msg[] = "{$day['ymd']} {$day['week']} {$day['low']} {$day['high']} {$day['type']} {$day['notice']}";
                }
            }

            return implode("\n", $msg);
        } else {
            return responseWithBaseRes($data, $city_code ? '' : "未获取到城市码，默认返回北京天气");
        }
    }
    public static function getWeather($city_code)
    {
        $api     = "http://t.weather.itboy.net/api/weather/city/${city_code}";
        $weather = json_decode(file_get_contents($api), true);
        return $weather;
    }
    public static function getCityCode()
    {
        $ip = get_real_ip();

        $cityApi  = 'https://fastly.jsdelivr.net/gh/Oreomeow/checkinpanel@master/city.json';
        $cacheKey = 'weather_city_code.json';
        $cityInfo = cacheGet($cacheKey);

        if (! $cityInfo) {
            $cityInfo = json_decode(file_get_contents($cityApi), true);
            if ($cityInfo) {
                cacheSet($cacheKey, $cityInfo, 3600 * 24 * 7);
            }
        }

        require_once __DIR__ . '/ip/index.php';
        $info = IPQuery::getIpInfo($ip);
        $city = str_replace('市', '', $info['city']);

        if (isset($cityInfo[$city])) {
            return $cityInfo[$city];
        }
    }
}
