<?php
require_once __DIR__ . '/utils.php';

class Maoyan
{
    public static function handle($encoding)
    {
        $info = self::fetchHTMLData();
        $list = $info['list'];
        $tips = $info['tips'];

        $rawValues = array_column($list, 'rawValue');
        array_multisort($rawValues, SORT_DESC, $list);

        $ret = [
            'list'           => [],
            'tip'            => $tips,
            'update_time'    => date('Y-m-d H:i:s'),
            'update_time_at' => time(),
        ];

        foreach ($list as $idx => $e) {
            $ret['list'][] = [
                'rank'            => $idx + 1,
                'maoyan_id'       => $e['movieId'],
                'movie_name'      => $e['movieName'],
                'release_year'    => $e['releaseTime'],
                'box_office'      => $e['rawValue'],
                'box_office_desc' => self::formatBoxOffice($e['rawValue']),
            ];
        }

        if ($encoding == 'text') {
            $msg = `全球电影票房总榜（猫眼）\n\n`;

            foreach ($ret['list'] as $e) {
                $msg .= sprintf('%d. %s (%s) - %s', $e['rank'], $e['movie_name'], $e['release_year'], $e['box_office_desc']) . "\n";
            }

            return $msg;
        } else {
            return responseWithBaseRes($ret);
        }
    }
    public static function fetchHTMLData()
    {
        $url     = 'https://piaofang.maoyan.com/i/globalBox/historyRank';
        $headers = [
            'referer: https://piaofang.maoyan.com/',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
        ];
        ['res' => $html] = httpCurl($url, 'GET', null, $headers);
        $json            = preg_match('/var props = (\{.*?\});/', $html, $matches) ? $matches[1] : '{}';
        $data            = json_decode($json, true)['data'] ?? [];

        return [
            'title' => $data['title'] ?? '全球票房影史榜',
            'uid'   => preg_match('/name="csrf"\s+content="([^"]+)"/', $html, $matches) ? $matches[1] : '',
            'uuid'  => preg_match('/name="deviceId"\s+content="([^"]+)"/', $html, $matches) ? $matches[1] : '',
            'list'  => $data['detail']['list'] ?? [],
            'tips'  => $data['detail']['tips'] ?? '',
        ];

    }
    public static function formatBoxOffice($boxOffice)
    {
        $amount      = floatval($boxOffice);
        $UNIT_WAN    = 10 ** 4;
        $UNIT_YI     = 10 ** 8;
        $UNIT_WAN_YI = 10 ** 12;

        $formatNumber = function ($num) {
            return number_format($num, 2, '.', '');
        };

        if ($amount < $UNIT_WAN) {
            return $formatNumber($amount) . '元';
        } else if ($amount < $UNIT_YI) {
            return $formatNumber($amount / $UNIT_WAN) . '万元';
        } else if ($amount < $UNIT_WAN_YI) {
            return $formatNumber($amount / $UNIT_YI) . '亿元';
        } else {
            return $formatNumber($amount / $UNIT_WAN_YI) . '万亿元';
        }
    }
}
