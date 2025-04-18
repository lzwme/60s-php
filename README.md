# 「60s看世界」API 集合 PHP 版

[在线浏览 https://lzw.me/x/60s](https://lzw.me/x/60s)

> 「60s看世界」免费快速的 API 集合，60s 带你看世界、哔哩/微博/抖音/知乎/头条热搜、汇率换算、Bing 壁纸等
>
> 参考 `vikiboss/60s` 实现的 PHP 版本。主要是直接在已有 PHP 服务下上传为一个子目录即可使用，实现服务简单部署。

## 🪵 API 目录

- 日更
  - 🌍 60s 读懂世界 [type=60s]
  - 🏞️ Bing 每日壁纸 [type=bing]
  - 🪙 汇率查询（支持 160+ 货币） [type=ex-rates]
  - 📰 历史上的今天 [type=history]
- 热榜
  - 📺 哔哩哔哩实时热搜榜 [type=bili]
  - 👀 猫眼票房排行榜 [type=maoyan]
  - 🦊 微博实时热搜榜 [type=weibo]
  - ❓ 知乎实时热搜 [type=zhihu]
  - 🎵 抖音实时热搜 [type=douyin]
  - 📰 头条实时热搜 [type=toutiao]
- 实用工具
  - 📡 IP 查询、获取访客公网 IP 地址 [type=ip[&ip=xxx.xxx.xxx.xxx]]
  - 📊 天气查询 [type=weather&cityCode=101010100]
- 娱乐
  - 🎤 唱呀：随机唱歌音频 [type=changya]
  - 💬 随机一言 [type=yiyan]
  - 💬 随机KFC v50 段子 [type=v50]
  - ✨ 随机运势 [type=luck]
  - 🤣 随机搞笑段子 [type=duanzi]
  - 🤭 随机发病文学 [type=fabing]
  - 📖 随机答案之书 [type=answer]

## 🎨 返回格式

除特殊说明外，所有 API 均支持返回以下格式：

- `json`（默认） 适用于 API 调用
- `text` 文本形式。适用于直接展示

通过 URL 的 `e`/`encode`/`encoding` 参数进行指定。

比如：

- [https://60s.lzw.me/?e=text](https://60s.lzw.me/?e=text)
- [https://60s.lzw.me/?e=json](https://60s.lzw.me/?e=json)

## 🧭 使用说明

**1. 🌍 【知乎每日早报】每天 60s 读懂世界**

- [https://60s.lzw.me/?type=60s](https://60s.lzw.me/?type=60s)
- V1 旧版本格式：[https://60s.lzw.me/?type=60s&v1=1](https://60s.lzw.me/?type=60s&v1=1)

**2. 🏞️ Bing 每日壁纸**

- [https://60s.lzw.me/?type=bing](https://60s.lzw.me/?type=bing)（默认 JSON 数据）
- [https://60s.lzw.me/?type=bing&e=text](https://60s.lzw.me/?type=bing&e=text) （仅返回图片直链）
- [https://60s.lzw.me/?type=bing&e=image](https://60s.lzw.me/?type=bing&e=image) （重定向到原图直链）
- 每天 16 点更新，支持 `text`/`json`/`image` 三种返回形式。

**3. 🪙 汇率查询（每天更新，支持 160+ 货币）**

- [https://60s.lzw.me/?type=ex-rates&c=USD](https://60s.lzw.me/?type=ex-rates&c=USD)

- 参数说明：使用参数 `c` 指定[货币代码](https://coinyep.com/zh/currencies)，不指定默认为 CNY，货币代码可在[这里](https://coinyep.com/zh/currencies)查询。

**4. 📺 哔哩哔哩实时热搜榜**

- [https://60s.lzw.me/?type=bili](https://60s.lzw.me/?type=bili)

**5. 🦊 微博实时热搜榜**

- [https://60s.lzw.me/?type=weibo](https://60s.lzw.me/?type=weibo)

**6. ❓ 知乎实时热搜榜**

- [https://60s.lzw.me/?type=zhihu](https://60s.lzw.me/?type=zhihu)

**7. 📰 头条实时热搜榜**

- [https://60s.lzw.me/?type=toutiao](https://60s.lzw.me/?type=toutiao)

**8. 🎵 抖音实时热搜榜**

- [https://60s.lzw.me/?type=douyin](https://60s.lzw.me/?type=douyin)

**9. 历史上的今天【百科】**

- [https://60s.lzw.me/?type=history](https://60s.lzw.me/?type=history)

**10. 获取访客公网 IP 地址**
- [https://60s.lzw.me/?type=ip](https://60s.lzw.me/?type=ip)

**11. 唱呀：随机唱歌音频**
- [https://60s.lzw.me/?type=changya](https://60s.lzw.me/?type=changya)

**12. 猫眼票房排行榜**
- [https://60s.lzw.me/?type=maoyan](https://60s.lzw.me/?type=maoyan)

**13. 天气查询**
- [https://60s.lzw.me/?type=weather&cityCode=101010100](https://60s.lzw.me/?type=weather&cityCode=101010100)

## 安装部署

### 直接部署

首先，下载 `60s` 目录至本地：

```bash
wget https:///ghfast.top/github.com/lzwme/60s-php/archive/refs/heads/main.zip
unzip main.zip && mv 60s-php-main 60s
ls 60s
```

然后基于 PHP 部署一个基本的 Web 服务，将 Web 服务根目录指向 `60s` 目录即可。nginx 配置请参考：[./nginx-60s.conf](nginx-60s.conf)

### Docker 部署

首先下载 `60s` 目录至本地目录，如 `/home/www/60s`。

**基于 docker 命令：**

```bash
cd /home/www/60s

# 拉取 php7.4 镜像
docker pull shinsenter/phpfpm-nginx:php7.4-alpine

# 启动
docker run -d \
  --name 60s \
  -v "$(pwd)/:/var/www/html" \
  -v "$(pwd)/nginx-60s.conf:/etc/nginx/sites-enabled/00-default.conf" \
  -p 8060:80 \
  shinsenter/phpfpm-nginx:php7.4-alpine
```

**基于 `docker-compose`：**

进入 `60s` 目录，然后执行如下命令即可：

```bash
cd /home/www/60s
docker-compose up -d
```

最后访问 `http://localhost:8060/reader` 即可。

### 黑名单设置

若发现来自于某些 IP 的访问频率过高，可将其加入黑名单，防止其频繁访问。
根目录下新建文件 `blacklist.txt`，并填入 IP 即可；也支持通过环境变量 BLACKLIST 设置。

## 相关

- API 参考： [「60s」免费快速的 API 集合 【deno 版本】](https://github.com/vikiboss/60s)
- reader 参考：[https://github.com/flow2000/news](https://github.com/flow2000/news)
