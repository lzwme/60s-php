<?php
require_once __DIR__ . '/utils.php';

// $ip 黑名单
// 从 文件或环境变量获取（每行一个，或用逗号/分号/空白分隔）
$blacklistFile = __DIR__ . '/../blacklist.txt';
$blacklist     = is_readable($blacklistFile)
    ? (string) file_get_contents($blacklistFile)
    : (string) getenv('BLACKLIST');

function checkBlacklist($ip)
{
    global $blacklist;

    if (! $ip || $blacklist === '') {
        return;
    }

    // 原实现用 strpos 做子串匹配，既会误伤（1.2.3.4 命中 1.2.3.45），
    // 又因为函数内没有 global 声明，$blacklist 根本取不到值。
    $entries = preg_split('/[\s,;]+/', $blacklist, -1, PREG_SPLIT_NO_EMPTY);

    if (in_array($ip, $entries, true)) {
        // 返回 403
        http_response_code(403);
        exit("由于滥用等原因，你的 IP({$ip}) 地址已被禁止");
    }
}

checkBlacklist(get_real_ip());
