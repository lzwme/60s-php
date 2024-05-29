const host = '../index.php?cors=1';

const config = {
  api: {
    bing: `${host}&type=bing`,
    t60s: `${host}&type=60s`,
  },
  type: {
    t60s: {
      title: '每日早报',
    },
    bili: {
      title: 'B站热榜',
    },
    weibo: {
      title: '微博热搜',
    },
    toutiao: {
      title: '头条热搜',
    },
    douyin: {
      title: '抖音热搜',
    },
    history: {
      title: '历史上的今天',
    },
  },
};

const el = {
  bing: document.getElementById('bing'),
  weiyu: document.querySelector('#weiyu'),
  newsTitle: document.getElementById('news_title'),
  news: document.getElementById('news'),
  btn: document.querySelector('.switch_btn'),
  btnSpan: document.querySelector('.switch_btn_txt'),
  btnBefore: document.querySelector('.before_btn'),
  btnAfter: document.querySelector('.after_btn'),
};

const R = {
  offset: 0,
  currentType: 't60s',
  loadBingImg() {
    axios
      .get(config.api.bing)
      .then(response => (el.bing.src = response.data.data.image_url))
      .catch(function (error) {
        failNotify(`获取壁纸数据失败\uD83D\uDE1E，请点击跳转至问题反馈`, 1);
        NProgress.done();
        console.log(error);
      });
  },
  loadYiyan() {
    axios
      .get('https://x.lzw.me/?c=k')
      .then(response => (el.weiyu.innerHTML = response.data['hitokoto']))
      .catch(function (error) {
        h5Utils.toast(`获取一言失败 \uD83D\uDE1E`, { icon: 'warning' });
        console.log(error);
      });
  },
  getNextType() {
    const typeList = Object.keys(config.type);
    const nextIndex = (typeList.indexOf(R.currentType) + 1) % typeList.length;
    return typeList[nextIndex];
  },
  async initEvents() {
    el.weiyu.addEventListener('click', () => R.loadYiyan(), false);
    el.bing.addEventListener(
      'click',
      () => {
        window.open(document.getElementById('bing').src.split('_1920x1080.jpg')[0] + '_UHD.jpg');
      },
      false
    );

    el.btn.addEventListener(
      'click',
      () => {
        R.currentType = R.getNextType();
        if (R.currentType === 't60s') {
          R.offset = 0;
          el.btnBefore.style.display = '';
          el.btnAfter.style.display = '';
        } else {
          el.btnBefore.style.display = 'none';
          el.btnAfter.style.display = 'none';
        }

        history.replaceState('', '', `?type=${R.currentType}`);
        R.loadNews();
      },
      false
    );
    el.btnAfter.addEventListener(
      'click',
      () => {
        if (R.offset === 0) return h5Utils.toast('当前已经是最新的了');

        R.offset -= 1;
        R.loadNews();
      },
      false
    );
    el.btnBefore.addEventListener(
      'click',
      () => {
        if (R.offset === 5) return h5Utils.toast('之后没有了', { icon: 'warning' });

        R.offset += 1;
        R.loadNews();
      },
      false
    );
  },
  async loadNews() {
    const currentConfig = config.type[R.currentType];

    try {
      const nextType = R.getNextType();
      el.btnSpan.innerHTML = `切换至【${config.type[nextType].title}】`;

      let { data } = await axios.get(`${config.api[R.currentType] || `${host}&type=${R.currentType}`}&offset=${R.offset}`);
      const newsHtmlList = [];

      if (data.data) data = data.data;
      console.log(data);

      switch (R.currentType) {
        case 't60s': {
          if (Array.isArray(data.news)) {
            data.news.forEach(item => {
              newsHtmlList.push(`<li>${String(item).replace(/^\d+(.|、) /, '')}</li>`);
            });
          }
          break;
        }
        case 'weibo': {
          data.forEach(item => {
            const title = item.word || item.note || item.word_scheme;
            const url = `https://s.weibo.com/weibo?q=${encodeURIComponent(item.word_scheme || title)}`;
            newsHtmlList.push(`<li><a href="${url}" target="_blank">${title}</a></li>`);
          });
          break;
        }
        case 'bili': {
          data.forEach(item => {
            if (!item.url) item.url = `https://search.bili.com/all?keyword=${encodeURIComponent(item.keyword || item.show_name)}`;
            newsHtmlList.push(`<li><a href="${item.url}" target="_blank">${item.show_name || item.keyword}</a></li>`);
          });
          break;
        }
        case 'history': {
          data.forEach(item => {
            newsHtmlList.push(`<li><a href="${item.link}" target="_blank">[${item.year}年] ${item.title}</a></li>`);
          });
          break;
        }
        default: {
          if (Array.isArray(data)) {
            data.forEach(item => {
              const title = item.title || item.keyword || item.word;
              const url = item.url;
              // console.log(item)
              if (title) {
                if (url) newsHtmlList.push(`<li><a href="${url}" target="_blank">${title}</a></li>`);
                else newsHtmlList.push(`<li>${title}</li>`);
              }
            });
          }
        }
      }

      // news
      el.news.innerHTML = newsHtmlList.join('');
      // date 时间
      document.getElementById('date').innerHTML =
        currentConfig.title + ` [ ${data.date || new Date(data.updated || Date.now()).toLocaleString()} ]`;
      // 一言
      if (String(data.tip).includes('【微语】')) {
        document.getElementById('weiyu').innerHTML = data.tip.replace('【微语】', '');
      } else {
        R.loadYiyan();
      }
    } catch (error) {
      failNotify(`获取【${currentConfig.title}】数据失败\uD83D\uDE1E，请点击 OK 跳转至问题反馈！【${error.message}】`, 1);
      console.log(error);
    }

    NProgress.done();
  },
  async init() {
    const re = /type=([a-z0-9]+)/.exec(location.href);
    if (re && re[1] in config.type) R.currentType = re[1];

    R.initEvents();
    NProgress.start();
    R.loadYiyan();
    R.loadBingImg();
    R.loadNews();
  },
};

R.init();

async function failNotify(msg, onOk) {
  const { isConfirmed } = await h5Utils.alert({ text: msg, icon: 'error', showConfirmButton: true });
  if (isConfirmed) {
    if (typeof onOk === 'function') onOk();
    else if (onOk == 1) window.open('https://github.com/lzwme/60s-php/issues/new');
  }
}
