# 「60s看世界」API 集合 PHP 版

[在线浏览 https://lzw.me/x/60s](https://lzw.me/x/60s)

> 「60s看世界」免费快速的 API 集合，60s 带你看世界、哔哩/微博/抖音/知乎/头条热搜、汇率换算、Bing 壁纸等

> 60s 数据目前来源于这个[知乎专栏](https://www.zhihu.com/column/c_1715391799055720448)，原专栏在[这里](https://www.zhihu.com/column/c_1261258401923026944)，但是原专栏已不在知乎更新，这个新的专栏貌似是搬运的，希望它能坚持更新下去吧 🙏。

## 🪵 API 目录

1. 🌍 60s 读懂世界
1. 🏞️ Bing 每日壁纸
1. 🪙 汇率查询（支持 160+ 货币）
1. 📺 哔哩哔哩实时热搜榜
1. 🦊 微博实时热搜榜
1. ❓ 知乎实时热搜
1. 📰 头条实时热搜
1. 🎵 抖音实时热搜
1. 📰 历史上的今天

## 🎨 返回格式

除特殊说明外，所有 API 均支持返回以下格式：

- `json`（默认）
- `text`

通过 URL 的 `e`/`encode`/`encoding` 参数进行指定。

比如：

- [https://lzw.me/x/iapi/60s/?e=text](https://lzw.me/x/iapi/60s/?e=text)
- [https://lzw.me/x/iapi/60s/?e=json](https://lzw.me/x/iapi/60s/?e=json)

## 🧭 使用说明

**1. 🌍 【知乎每日早报】每天 60s 读懂世界**

- [https://lzw.me/x/iapi/60s/?type=60s](https://lzw.me/x/iapi/60s/?type=60s)
- V1 旧版本格式：[https://lzw.me/x/iapi/60s/?type=60s&v1=1](https://lzw.me/x/iapi/60s/?type=60s&v1=1)

**2. 🏞️ Bing 每日壁纸**

- [https://lzw.me/x/iapi/60s/?type=bing](https://lzw.me/x/iapi/60s/?type=bing)（默认 JSON 数据）
- [https://lzw.me/x/iapi/60s/?type=bing&e=text](https://lzw.me/x/iapi/60s/?type=bing&e=text) （仅返回图片直链）
- [https://lzw.me/x/iapi/60s/?type=bing&e=image](https://lzw.me/x/iapi/60s/?type=bing&e=image) （重定向到原图直链）
- 每天 16 点更新，支持 `text`/`json`/`image` 三种返回形式。

**3. 🪙 汇率查询（每天更新，支持 160+ 货币）**

- [https://lzw.me/x/iapi/60s/?type=ex-rates&c=USD](https://lzw.me/x/iapi/60s/?type=ex-rates&c=USD)

- 参数说明：使用参数 `c` 指定[货币代码](https://coinyep.com/zh/currencies)，不指定默认为 CNY，货币代码可在[这里](https://coinyep.com/zh/currencies)查询。

**4. 📺 哔哩哔哩实时热搜榜**

- [https://lzw.me/x/iapi/60s/?type=bili](https://lzw.me/x/iapi/60s/?type=bili)

**5. 🦊 微博实时热搜榜**

- [https://lzw.me/x/iapi/60s/?type=weibo](https://lzw.me/x/iapi/60s/?type=weibo)

**6. ❓ 知乎实时热搜榜**

- [https://lzw.me/x/iapi/60s/?type=zhihu](https://lzw.me/x/iapi/60s/?type=zhihu)

**7. 📰 头条实时热搜榜**

- [https://lzw.me/x/iapi/60s/?type=toutiao](https://lzw.me/x/iapi/60s/?type=toutiao)

**8. 🎵 抖音实时热搜榜**

- [https://lzw.me/x/iapi/60s/?type=douyin](https://lzw.me/x/iapi/60s/?type=douyin)

**9. 历史上的今天【百科】**

- [https://lzw.me/x/iapi/60s/?type=history](https://lzw.me/x/iapi/60s/?type=history)

## 安装部署

### 直接部署

首先，下载 60s 目录至本地：

```bash
wget https://mirror.ghproxy.com/github.com/lzwme/60s-php/archive/refs/heads/main.zip
unzip main.zip
ls 60s-php-main
```

然后基于 PHP 部署一个基本的 Web 服务，将 Web 服务根目录指向 `60s-php-main` 目录即可。nginx 配置请参考：[./nginx-60s.conf](nginx-60s.conf)

### Docker 部署

首先拉取镜像：

首先下载 `60s` 目录至本地目录，如 `/home/www/60s`。然后基于 `docker-compose` 部署，进入 `60s` 目录，然后执行如下命令即可：

```bash
docker-compose up -d
```

最后访问 http://localhost:8060/reader 即可。

## 相关

- [「60s」免费快速的 API 集合 【deno 版本】](https://github.com/vikiboss/60s)
- [https://github.com/flow2000/news](https://github.com/flow2000/news)
