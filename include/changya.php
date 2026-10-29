<?php
require_once __DIR__ . '/utils.php';

class Changya
{
    public static function handle($encoding)
    {
        $info = self::fetch();

        if (! is_array($info) || empty($info['audio']['url'])) {
            return responseWithBaseRes([], '暂未获取到音频数据', 502);
        }

        switch ($encoding) {
            case 'text':
                return $info['audio']['url'];
            case 'audio':
                header('Location: ' . $info['audio']['url']);
                exit;
            default:
                // 保持原有 JSON 结构（顶层直接是 user/song/audio）
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

        // 注意：array_rand() 返回的是数组下标而非元素本身
        $seedId = $seedIdList[array_rand($seedIdList)];
        $url    = "https://m.api.singduck.cn/user-piece/{$seedId}";
        $data   = @file_get_contents($url);
        if (! $data) {
            return null;
        }

        $start = '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">';
        $end   = '</script>';

        $spos = strpos($data, $start);
        if ($spos === false) {
            return null;
        }
        $spos += strlen($start);

        $epos = strpos($data, $end, $spos);
        if ($epos === false) {
            return null;
        }

        $pageData = json_decode(substr($data, $spos, $epos - $spos), true);
        $pieces   = $pageData['props']['pageProps']['pieces'] ?? null;
        if (! is_array($pieces) || $pieces === []) {
            return null;
        }

        $item = $pieces[array_rand($pieces)];
        if (! is_array($item)) {
            return null;
        }

        $url = $item['originAudioUrl'] ?? $item['audioUrl'] ?? $item['recordUrl'] ?? '';
        if ($url) {
            $url = urldecode($url);
        }

        return [
            'user'  => [
                'nickname'   => $item['nickname'] ?? '',
                'gender'     => $item['gender'] ?? '',
                'avatar_url' => $item['user']['avatarUrl'] ?? '',
            ],
            'song'  => [
                'name'   => $item['songName'] ?? '',
                'singer' => $item['artist'] ?? '',
                'lyrics' => array_map('trim', explode("\n", (string) ($item['lyric'] ?? ''))),
            ],
            'audio' => [
                'url'        => $url,
                'duration'   => $item['audioDuration'] ?? 0,
                'like_count' => $item['likeCount'] ?? 0,
                'link'       => "https://m.api.singduck.cn/user-piece/" . ($item['ugcId'] ?? ''),
                'publish'    => $item['publishTime'] ?? '',
                'publish_at' => $item['publishTime'] ?? '',
            ],
        ];
    }
}
