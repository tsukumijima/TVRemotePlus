<?php

	// モジュール読み込み
	require_once (dirname(__FILE__).'/config.php');

	// iniファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	$backtrace = debug_backtrace();

?>

<!DOCTYPE html>
<html lang="ja">

<head>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ ?>
  <title>ファイル再生 - <?php echo $site_title; ?></title>
<?php	} else if (strpos($backtrace[0]["file"], 'setting.php') !== false){ ?>
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
  <link rel="stylesheet" type="text/css" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="stylesheet" type="text/css" href="/files/DPlayer.min.css">
  <link rel="stylesheet" type="text/css" href="/files/toastr.min.css">
  <link rel="stylesheet" type="text/css" href="/files/balloon.min.css">
  <link rel="stylesheet" type="text/css" href="/files/style.css">
<?php
	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/swiper.min.css">'."\n";
	}
	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/watch.css">'."\n";
	}
	if (strpos($backtrace[0]["file"], 'setting.php') !== false){ // setting.phpのみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/setting.css">'."\n";
	}
?>
  <!-- Script -->
  <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
  <script type="text/javascript" src="/files/pwacompat.min.js" async></script>
  <script type="text/javascript" src="/files/jquery.min.js"></script>
  <script type="text/javascript" src="/files/DPlayer.min.js"></script>
  <script type="text/javascript" src="/files/hls.min.js"></script>
  <script type="text/javascript" src="/files/toastr.min.js"></script>
  <script type="text/javascript" src="/files/push.min.js"></script>
  <script type="text/javascript" src="/files/js.cookie.min.js"></script>
  <script type="text/javascript" src="/files/velocity.min.js"></script>
  <script type="text/javascript" src="/files/common.js"></script>
<?php
	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ
		echo '  <script type="text/javascript" src="/files/swiper.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/index.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/script.js"></script>'."\n";
	}
	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ
		echo '  <script type="text/javascript" src="/files/watch.js"></script>'."\n";
	} else if (strpos($backtrace[0]["file"], 'setting.php') !== false){ // setting.phpのみ
		echo '  <script type="text/javascript" src="/files/setting.js"></script>'."\n";
	} else if ($ini['state'] == 'ONAir'){
		echo '  <script type="text/javascript" src="/files/onair.js"></script>'."\n";
	} else if ($ini['state'] == 'File'){
		echo '  <script type="text/javascript" src="/files/file.js"></script>'."\n";
	}
?>

  <script>
    settings = {twitter_show:true, comment_show:true, comment_size:35, onclick_stream:false,};
    if (Cookies.get('settings') != undefined){
      settings = JSON.parse(Cookies.get('settings'));
    }

    window.addEventListener('load', function() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register("/serviceworker.js");
        }
    });
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ ?>

    streamurl = 'http://<?php echo $_SERVER['SERVER_NAME'].':'.$http_port; ?>/stream/stream.m3u8';

<?php	} // 括弧終了 ?>
  </script>

</head>

<body>

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
    <google-cast-launcher style="display: none;" aria-label="Chromecastを使ってテレビで再生できます" data-balloon-pos="up"></google-cast-launcher>
      <div id="cast-toggle" class="menu-link" aria-label="Chromecastを使ってテレビで再生できます" data-balloon-pos="up">
        <svg style="width: 21px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="chromecast" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-chromecast fa-w-16">
          <path fill="currentColor" d="M447.83 64H64a42.72 42.72 0 0 0-42.72 42.72v63.92H64v-63.92h383.83v298.56H298.64V448H448a42.72 42.72 0 0 0 42.72-42.72V106.72A42.72 42.72 0 0 0 448 64zM21.28 383.58v63.92h63.91a63.91 63.91 0 0 0-63.91-63.92zm0-85.28V341a106.63 106.63 0 0 1 106.64 106.66v.34h42.72a149.19 149.19 0 0 0-149-149.36h-.33zm0-85.27v42.72c106-.1 192 85.75 192.08 191.75v.5h42.72c-.46-129.46-105.34-234.27-234.8-234.64z" class="">
          </path>
        </svg>
        <span class="menu-link-href">キャストを開始</span>
      </div>
      <div id="cast-scan" class="menu-link" aria-label="キャストするChromecastをスキャンします" data-balloon-pos="up">
        <i class="fas fa-sync-alt"></i>
        <span class="menu-link-href">デバイスをスキャン</span>
      </div>
    </div>
  </nav>
<?php	} // 括弧終了 ?>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
      <div id="list-update" class="menu-link">
        <i class="fas fa-redo-alt"></i>
        <span class="menu-link-href">リストを更新</span>
      </div>
      <div id="list-reset" class="menu-link">
        <i class="fas fa-trash-restore-alt"></i>
        <span class="menu-link-href">リストをリセット</span>
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
    <form method="post" name="quickstop" action="/setting/">
      <input type="hidden" name="state" value="Offline">
      <a class="nav-link" href="javascript:quickstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">ストリーム終了</span>
      </a>
    </form>
    <a class="nav-link" href="/watch/">
      <i class="fas fa-search"></i>
      <span class="nav-link-href">ファイルを再生</span>
    </a>
    <a class="nav-link" href="/tweet/auth.php">
      <i class="fab fa-twitter"></i>
      <span class="nav-link-href">Twitter ログイン</span>
    </a>
    <a class="nav-link" href="/setting/">
      <i class="fas fa-cog"></i>
      <span class="nav-link-href">設定</span>
    </a>
<?php
	if ($update_confirm == 'true'){
		$update = file_get_contents('https://raw.githubusercontent.com/tsukumijima/TVRemotePlus/master/data/version.txt');
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
  <div id="nav-close"></div>
  <div id="menu-close"></div>
  
  <section id="main">
