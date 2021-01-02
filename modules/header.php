<?php

	// モジュール読み込み
	require_once (dirname(__FILE__).'/require.php');
	require_once (dirname(__FILE__).'/module.php');
  
	// ストリーム番号を取得
	$stream = getStreamNumber($_SERVER['REQUEST_URI']);

	// iniファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	$backtrace = debug_backtrace();

?>

<!DOCTYPE html>
<html lang="ja">

<head>

<?php	if (strpos($backtrace[0]['file'], 'watch.php') !== false){ ?>
  <title>録画番組 - <?php echo $site_title; ?></title>
<?php	} else if (strpos($backtrace[0]['file'], 'settings.php') !== false){ ?>
  <title>設定 - <?php echo $site_title; ?></title>
<?php	} else { ?>
  <title><?php echo $site_title; ?></title>
<?php	} // 括弧終了 ?>
  <meta charset="UTF-8">
  <meta name="theme-color" content="#191919">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

  <!-- Style -->
  <link rel="manifest" href="/manifest.json">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" type="text/css" href="https://use.fontawesome.com/releases/v5.13.0/css/all.css">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap">
  <link rel="stylesheet" type="text/css" href="/files/toastr.min.css">
  <link rel="stylesheet" type="text/css" href="/files/balloon.min.css">
  <link rel="stylesheet" type="text/css" href="/files/style.css">
<?php
	if (strpos($backtrace[0]['file'], 'index.php') !== false){ // index.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/swiper.min.css">'."\n";
	}
	if (strpos($backtrace[0]['file'], 'watch.php') !== false){ // watch.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/watch.css">'."\n";
	}
	if (strpos($backtrace[0]['file'], 'settings.php') !== false){ // settings.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/settings.css">'."\n";
	}
?>

  <!-- Script -->
  <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
  <script type="text/javascript" src="/files/pwacompat.min.js" async></script>
  <script type="text/javascript" src="/files/jquery.min.js"></script>
  <script type="text/javascript" src="/files/DPlayer.min.js"></script>
  <script type="text/javascript" src="/files/hls.min.js"></script>
  <script type="text/javascript" src="/files/toastr.min.js"></script>
  <script type="text/javascript" src="/files/js.cookie.min.js"></script>
  <script type="text/javascript" src="/files/velocity.min.js"></script>
  <script type="text/javascript" src="/files/moment.min.js"></script>
  <script type="text/javascript" src="/files/css_browser_selector.js"></script>
  <script type="text/javascript" src="/files/common.js?<?php echo $version; ?>"></script>
<?php
	if (strpos($backtrace[0]['file'], 'index.php') !== false){ // index.phpのみ
		echo '  <script type="text/javascript" src="/files/clusterize.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/swiper.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/index.js?'.$version.'"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/script.js?'.$version.'"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/jikkyo.js?'.$version.'"></script>'."\n";
	}
	if (strpos($backtrace[0]['file'], 'watch.php') !== false){ // watch.phpのみ
		echo '  <script type="text/javascript" src="/files/watch.js?'.$version.'"></script>'."\n";
	} else if (strpos($backtrace[0]["file"], 'settings.php') !== false){ // settings.phpのみ
		echo '  <script type="text/javascript" src="/files/settings.js?'.$version.'"></script>'."\n";
	} else if ($ini[$stream]['state'] == 'ONAir'){
	} else if ($ini[$stream]['state'] == 'File'){
	}
?>

  <script>

    // 個人設定のデフォルト値
    settings = {
        twitter_show: true,
        comment_show: true,
        dark_theme: false,
        subchannel_show: false,
        list_view: false,
        logo_show: true,
        comment_size: 35,
        comment_delay: 5,
        comment_file_delay: 0,
        comment_list_performance: 'normal',
        list_view_number: 30,
        onclick_stream: false,
        player_floating: true,
        ljicrop_magnify: 100,
        ljicrop_coordinateX: 0,
        ljicrop_coordinateY: 0,
        ljicrop_type: 'upperright',
    };
    if (Cookies.get('settings') === undefined){
        var json = JSON.stringify(settings);
        Cookies.set('settings', json, { expires: 365 });
    } else {
        settings = JSON.parse(Cookies.get('settings'));
    }
    if (settings['dark_theme']){
        document.documentElement.classList.add('dark');
    }

    window.addEventListener('load', function() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register("/serviceworker.js");
        }
    });

<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ ?>
<?php		if ($ini[$stream]['state'] == 'File' and $ini[$stream]['fileext'] != 'ts' and $ini[$stream]['encoder'] == 'Progressive'){ ?>
    stream = '<?php echo $stream; ?>';
    streamurl = 'http://<?php echo $_SERVER['SERVER_NAME'].':'.$http_port; ?>/api/stream/<?php echo $stream; ?>';
    streamtype = 'video/mp4';

<?php		} else { ?>
    stream = '<?php echo $stream; ?>';
    streamurl = 'http://<?php echo $_SERVER['SERVER_NAME'].':'.$http_port; ?>/stream/stream<?php echo $stream; ?>.m3u8';
    streamtype = 'application/vnd.apple.mpegurl';

<?php		} //括弧終了 ?>
<?php	} // 括弧終了 ?>
  </script>

</head>

<body class="scrollbar">

  <nav id="top">
    <div id="nav-open">
      <i class="material-icons">menu</i>
    </div>
    <a id="logo" href="/">
      <img src="<?php echo $icon_file; ?>">
    </a>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false or strpos($backtrace[0]["file"], 'watch.php') !== false){ // index.php・watch.phpのみ ?>
    <div id="menu-button">
      <i class="material-icons">more_vert</i>
    </div>
<?php	} else { ?>
    <div id="menu-fakebutton"></div>
<?php	} // 括弧終了 ?>
  </nav>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
<?php	if (isSettingsItem('subchannel_show', true)){ ?>
      <div id="subchannel-hide" class="menu-link" aria-label="メインチャンネルのみ番組表に表示します" data-balloon-pos="up">
        <i class="fas fa-broadcast-tower"></i>
        <span class="menu-link-href">サブチャンネルを隠す</span>
      </div>
<?php	} else { ?>
      <div id="subchannel-show" class="menu-link" aria-label="サブチャンネルを番組表に表示します" data-balloon-pos="up">
        <i class="fas fa-broadcast-tower"></i>
        <span class="menu-link-href">サブチャンネルを表示</span>
      </div>
<?php	} // 括弧終了 ?>
      <div id="ljicrop" class="menu-link" aria-label="Ｌ字画面のクロップの設定を表示します" data-balloon-pos="up">
        <i class="fas fa-tv"></i>
        <span class="menu-link-href">Ｌ字画面のクロップ</span>
      </div>
      <div id="hotkey" class="menu-link" aria-label="キーボードショートカットの一覧を表示します" data-balloon-pos="up">
        <i class="fas fa-keyboard"></i>
        <span class="menu-link-href">ショートカット一覧</span>
      </div>
      <google-cast-launcher style="display: none;" aria-label="Chromecast などを使ってテレビで再生できます" data-balloon-pos="up"></google-cast-launcher>
      <div id="cast-toggle" class="menu-link" aria-label="Chromecast などを使ってテレビで再生できます" data-balloon-pos="up">
        <svg style="width: 21px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="chromecast" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-chromecast fa-w-16">
          <path fill="currentColor" d="M447.83 64H64a42.72 42.72 0 0 0-42.72 42.72v63.92H64v-63.92h383.83v298.56H298.64V448H448a42.72 42.72 0 0 0 42.72-42.72V106.72A42.72 42.72 0 0 0 448 64zM21.28 383.58v63.92h63.91a63.91 63.91 0 0 0-63.91-63.92zm0-85.28V341a106.63 106.63 0 0 1 106.64 106.66v.34h42.72a149.19 149.19 0 0 0-149-149.36h-.33zm0-85.27v42.72c106-.1 192 85.75 192.08 191.75v.5h42.72c-.46-129.46-105.34-234.27-234.8-234.64z" class="">
          </path>
        </svg>
        <span class="menu-link-href">キャストを開始</span>
      </div>
    </div>
  </nav>
<?php	} // 括弧終了 ?>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
<?php	if (isSettingsItem('list_view', true)){ ?>
      <div id="normal-view" class="menu-link" aria-label="録画一覧を通常通り表示します" data-balloon-pos="up">
        <i class="fas fa-th-list"></i>
        <span class="menu-link-href">通常表示に切り替え</span>
      </div>
<?php	} else { ?>
      <div id="list-view" class="menu-link" aria-label="録画番組を細いリストで表示します" data-balloon-pos="up">
        <i class="fas fa-list"></i>
        <span class="menu-link-href">リスト表示に切り替え</span>
      </div>
<?php	} // 括弧終了 ?>
      <div id="list-update" class="menu-link">
        <i class="fas fa-redo-alt"></i>
        <span class="menu-link-href">リストを更新</span>
      </div>
      <div id="list-reset" class="menu-link">
        <i class="fas fa-trash-restore-alt"></i>
        <span class="menu-link-href">リストをリセット</span>
      </div>
      <div id="history-reset" class="menu-link">
        <i class="fas fa-trash-alt"></i>
        <span class="menu-link-href">再生履歴をリセット</span>
      </div>
    </div>
  </nav>
<?php	} // 括弧終了 ?>

  <nav id="nav-content">
    <div class="nav-logo">
      <img src="<?php echo $icon_file; ?>">
    </div>
    <a class="nav-link" href="/">
      <i class="fas fa-home"></i>
      <span class="nav-link-href">ホーム</span>
    </a>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ ?>
    <form method="post" name="quickstop" action="/settings/">
      <input type="hidden" name="state" value="Offline">
      <input type="hidden" name="stream" value="<?php echo $stream; ?>">
      <a class="nav-link" href="javascript:quickstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">このストリームを終了</span>
      </a>
    </form>
<?php	} // 括弧終了 ?>
    <form method="post" name="allstop" action="/settings/">
      <input type="hidden" name="state" value="Offline">
      <input type="hidden" name="stream" value="<?php echo $stream; ?>">
      <input type="hidden" name="allstop" value="true">
      <a class="nav-link" href="javascript:allstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">全てのストリームを終了</span>
      </a>
    </form>
    <a class="nav-link" href="/watch/">
      <i class="fas fa-video"></i>
      <span class="nav-link-href">録画番組を再生</span>
    </a>
    <a class="nav-link" href="/tweet/auth">
      <i class="fab fa-twitter"></i>
      <span class="nav-link-href">Twitter ログイン</span>
    </a>
    <a class="nav-link" href="/settings/">
      <i class="fas fa-cog"></i>
      <span class="nav-link-href">設定</span>
    </a>
<?php
	if ($update_confirm == 'true'){
		$update_context = stream_context_create( array('http' => array('timeout' => 5)) );
		$update = file_get_contents('https://raw.githubusercontent.com/tsukumijima/TVRemotePlus/master/data/version.txt?_='.time(), false, $update_context);
		// 取得したバージョンと現在のバージョンが違う場合のみ
		if ($update != $version){
			echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank" '.
						'aria-label="アップデートがあります (version '.str_replace('v', '', $update).')" data-balloon-pos="up">'."\n";
			echo '      <i class="fas fa-history" style="color: #e8004a;"></i>'."\n";
		} else {
			echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank">'."\n";
			echo '      <i class="fas fa-history"></i>'."\n";
		}
	} else {
		echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank">'."\n";
		echo '      <i class="fas fa-history"></i>'."\n";
	}
?>
      <span class="nav-link-href">
        version <?php echo str_replace('v', '', $version); ?>

      </span>
    </a>
  </nav>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ ?>
  <div id="cover" class="open"></div>
<?php	} else { ?>
  <div id="cover"></div>
<?php	} // 括弧終了 ?>
  <div id="nav-close"></div>
  <div id="menu-close"></div>
  
  <section id="main">
