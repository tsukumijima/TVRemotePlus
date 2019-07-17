<?php

// 設定読み込み
require_once (dirname(__FILE__)."/config.php");

// iniファイル読み込み
$ini = json_decode(file_get_contents($inifile), true);

$backtrace = debug_backtrace();

?>

<!DOCTYPE html>
<html lang="ja">

<head>
<?php if (strpos($backtrace[0]["file"], 'watch.php') !== false){ ?>
  <title>ファイル再生 - <?php echo $site_title; ?></title>
<?php } else if (strpos($backtrace[0]["file"], 'setting.php') !== false){ ?>
  <title>ストリーム開始 - <?php echo $site_title; ?></title>
  <?php } else { ?>
  <title><?php echo $site_title; ?></title>
<?php } // 括弧終了 ?>
  <meta charset="UTF-8">
  <meta name="theme-color" content="#191919">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <!-- Style -->
  <link rel="manifest" href="manifest.json">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="stylesheet" type="text/css" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="stylesheet" type="text/css" href="files/DPlayer.min.css">
  <link rel="stylesheet" type="text/css" href="files/style.css">
  <?php
  if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ
    echo '<link rel="stylesheet" type="text/css" href="files/watch.css">';
  } // 括弧終了
  ?>
  <!-- Script -->
  <script async type="text/javascript"  src="files/pwacompat.min.js"></script>
  <script type="text/javascript" src="files/jquery.min.js"></script>
  <script type="text/javascript" src="files/DPlayer.min.js"></script>
  <script type="text/javascript" src="files/hls.min.js"></script>
  <?php
  if (strpos($backtrace[0]["file"], 'index.php') !== false){ // index.phpのみ
    echo '<script type="text/javascript" src="files/resize.js"></script>';
  }
  if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ
    echo '<script type="text/javascript" src="files/watch.js"></script>';
  } else if ($ini['state'] == 'ONAir'){
    echo '<script type="text/javascript" src="files/script.js"></script>';
    echo '<script type="text/javascript" src="files/index.js"></script>';
  } else if ($ini['state'] == 'Offline'){
    echo '<script type="text/javascript" src="files/script.js"></script>';
  } else if ($ini['state'] == 'File'){
    echo '<script type="text/javascript" src="files/script.js"></script>';
    echo '<script type="text/javascript" src="files/file.js"></script>';
  } // 括弧終了
  ?>

  <script>
    window.addEventListener('load', function() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register("/serviceWorker.js");
        }
    });
  </script>

</head>

<body>
  <nav id="top">
    <div id="nav-drawer">
      <div id="nav-open">
        <span><i class="material-icons">menu</i></span>
      </div>
    </div>
    <div id="logo">
      <a href="./"><img src="<?php echo $icon_file; ?>"></a>
    </div>
    <div id="menubutton">
      <i class="material-icons">more_vert</i>
    </div>
  </nav>
  <?php
  if (strpos($backtrace[0]["file"], 'watch.php') !== false){ // watch.phpのみ
  ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
      <div class="menu-link">
        <i class="fas fa-redo-alt"></i>
        <span id="searchfile" class="menu-link-href">リスト更新</span>
      </div>
    </div>
  </nav>
  <?php
  } // 括弧終了
  ?>

  <nav id="nav-content">
    <div class="nav-logo">
      <img src="<?php echo $icon_file; ?>">
    </div>
    <a class="nav-link" href="./">
      <i class="fas fa-home"></i>
      <span class="nav-link-href">ホーム</span>
    </a>
    <a class="nav-link" href="./setting.php">
      <i class="far fa-play-circle"></i>
      <span class="nav-link-href">ストリーム開始</span>
    </a>
    <form method="post" name="quickstop" action="./setting.php">
      <input type="hidden" name="state" value="Offline">
      <a class="nav-link" href="javascript:quickstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">ストリーム終了</span>
      </a>
    </form>
    <a class="nav-link" href="./watch.php">
      <i class="fas fa-search"></i>
      <span class="nav-link-href">ファイルを再生</span>
    </a>
    <a class="nav-link" href="./tweet/auth.php">
      <i class="fab fa-twitter"></i>
      <span class="nav-link-href">Twitter ログイン</span>
    </a>
    <a class="nav-link">
      <i class="fas fa-cog"></i>
      <span class="nav-link-href">設定</span>
    </a>
    <a class="nav-link" href="https://github.com/nambuplace/TVRemotePlus" target="_blank">
      <i class="fas fa-history"></i>
      <span class="nav-link-href">
        v<?php echo $version; ?>

      </span>
    </a>

  </nav>
  <div id="nav-close"></div>
