<?php
require_once __DIR__ . '/utils.php';

// $ip 黑名单
$blacklistFile = 'blacklist.txt';
// 从 文件或环境变量获取
$blacklist = file_exists($blacklistFile) ? file_get_contents($blacklistFile) : getenv('BLACKLIST');

function checkBlacklist($ip) {
  if (!empty($blacklist) && strpos($blacklist, $ip) !== false) {
    // 返回 403
    http_response_code(403);
    exit("由于滥用等原因，你的 IP({$ip}) 地址已被禁止");
  }
}

checkBlacklist(get_real_ip());
