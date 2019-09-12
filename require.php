<?php 

// TVRemotePlus 内共通で使用する変数を定義しています
// 全て基本的にいじる必要はありません
// 変更すると一部動作しなくなるものも含まれています

// ***** 各種exeファイルのパス設定 *****

// rplsinfo の名前とパス
$rplsinfo_exe =  'rplsinfo-tvrp.exe';
$rplsinfo_path =  $base_dir.'bin/'.$rplsinfo_exe;

// ffmpeg の名前とパス
$ffmpeg_exe =  'ffmpeg-tvrp.exe';
$ffmpeg_path = $base_dir.'bin/'.$ffmpeg_exe;

// QSVEncC の名前とパス
$qsvencc_exe = 'QSVEncC64-tvrp.exe';
$qsvencc_path =  $base_dir.'bin/QSVEncC/'.$qsvencc_exe;

// NVEncC の名前とパス
$nvencc_exe = 'NVEncC64-tvrp.exe';
$nvencc_path =  $base_dir.'bin/NVEncC/'.$nvencc_exe;

// TSTask の名前とパス
$tstask_exe = 'TSTask-tvrp.exe';
$tstask_path = $base_dir.'bin/TSTask/'.$tstask_exe;
$tstaskcentre_exe = 'TSTaskCentre-tvrp.exe';
$tstaskcentre_path = $base_dir.'bin/TSTask/'.$tstaskcentre_exe;


// ***** その他の設定 *****

// バージョン
$version = file_get_contents(dirname(__FILE__).'/data/version.txt');

// サイト名
$site_title = 'TVRemotePlus';

// アイコンのパス
// htdocs からのパス
$icon_file = '/files/TVRemotePlus.svg';

// BonDriver のあるディレクトリ(フォルダ)
// デフォルトは TSTask のあるフォルダ/BonDriver/ フォルダです
$BonDriver_dir = $base_dir.'bin/TSTask/BonDriver/';

// セグメントを一時的に保管するフォルダのパス
// 変更すると作動しなくなります
// HDD など別のドライブに変更したい場合は、Windows のシンボリックリンク機能を利用して下さい
$segment_folder = $base_dir.'htdocs/stream/';

// config.php のパス
$tvrp_conf_file = $base_dir.'config.php';

// ファイル情報保存ファイルのパス
$infofile = $base_dir.'htdocs/files/fileinfo.json';

// 再生履歴保存ファイルのパス
$historyfile = $base_dir.'htdocs/files/history.json';

// 設定ファイルのパス
$inifile = $base_dir.'data/setting.json';

// コメント設定ファイルのパス
$commentfile = $base_dir.'data/comment.json';

// ニコニコのログイン Cookie 保存ファイルのパス
$cookiefile = $base_dir.'data/nico.cookie';

// ツイートのタイムスタンプ記録ファイルのパス
$tweet_time_file = $base_dir.'data/tweet_time.dat';

// オフライン時の m3u8 のパス
$offline_m3u8 = $base_dir.'data/offline.m3u8';
$offline_silent_m3u8 = $base_dir.'data/offline_silent.m3u8';

// スタンバイ時の m3u8 のパス
$standby_m3u8 = $base_dir.'data/standby.m3u8';
$standby_silent_m3u8 = $base_dir.'data/standby_silent.m3u8';

// .htaccess のパス
$htaccess = $base_dir.'htdocs/.htaccess';

// .htpasswd のパス
$htpasswd = $base_dir.'htdocs/.htpasswd';

