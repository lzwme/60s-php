<?php

class Changya
{
    public static function handle($encoding)
    {
        $info = self::fetch();
        switch ($encoding) {
            case 'text':
                return $info['audio']['url'];
            case 'audio':
                header('Location: ' . $info['audio']['url']);
                die();
            case 'json':
                return $info;
        }
    }
    public static function fetch()
    {
        $seedIdList = [
            '7o62vihNpccBDyDPv',
            '7onVzoWsr2m12T3Jn',
            '9o73vLjV8gBVXsaTK',
            'bop2h0lm9HqkTZk5l',
            'foh3CeIN82Vq2V1g8',
            'fop3CRU0CDDRVZyRv',
            'Io82fiEPMZXca3L2h',
            'jov3lixipX1myTZJ8',
            'Ko-3CFC4SwwgPVkad',
            'Koj3bFjg9iTHHFVwp',
            'OoC38kEclV2PHPw08',
            'QoM37qUJxRJg5ZR5U',
            'roz2SJYV8oy0sNHPl',
            'SoO36F74v12acJg5z',
            'SoQJ9cKu61FJ1Vwc7',
            'Soz3xRz1f230H3ws6',
            'toGZlBfZbukck2sHb',
            'WoENz0IiQVX1PLJs7',
        ];
        $randomId = array_rand($seedIdList);
        $url      = "https://m.api.singduck.cn/user-piece/{$randomId}";
        $data     = file_get_contents($url);
        if (! $data) {
            throw new Error('fetch data error');
        }

        $start = '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">';
        $end   = '</script>';

        $spos     = strpos($data, $start) + strlen($start);
        $epos     = strpos($data, $end, $spos);
        $pageData = json_decode(substr($data, $spos, $epos - $spos), true);

        $rkey = array_rand($pageData['props']['pageProps']['pieces']);
        $item = $pageData['props']['pageProps']['pieces'][$rkey];

        $url = $item['originAudioUrl'] ?? $item['audioUrl'] ?? $item['recordUrl'] ?? '';
        if ($url) {
            $url = urldecode($url);
        }

        return [
            'user'  => [
                'nickname'   => $item['nickname'],
                'gender'     => $item['gender'],
                'avatar_url' => $item['user']['avatarUrl'],
            ],
            'song'  => [
                'name'   => $item['songName'],
                'singer' => $item['artist'],
                'lyrics' => array_map('trim', explode("\n", $item['lyric'])),
            ],
            'audio' => [
                'url'        => $url,
                'duration'   => $item['audioDuration'],
                'like_count' => $item['likeCount'],
                'link'       => "https://m.api.singduck.cn/user-piece/{$item['ugcId']}",
                'publish'    => $item['publishTime'],
                'publish_at' => $item['publishTime'],
            ],
        ];
    }
}
