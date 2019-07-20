<?php

// ******** 設定 ********

//タイムゾーンを日本に
date_default_timezone_set('Asia/Tokyo');

// べースディレクトリ(フォルダ)
$base_dir = str_replace('\\', '/', dirname(__FILE__)).'/';

// HTTPSアクセスかどうか
if (empty($_SERVER["HTTPS"])){
	$scheme = 'http://';
} else {
	$scheme = 'https://';
}

// ↓↓↓↓↓ ここから編集箇所 ↓↓↓↓↓


// ***** デフォルト設定 *****

// デフォルトとはなっていますが、変更必須な設定もあります、必ず目を通してください
// また、TSTask に配置する BonDriver は必ず 64bit 用のものにしてください

// フォルダのパスを記述するときは「\」(バックスラッシュ)ではなく必ず「/」(スラッシュ)を利用してください
// 動作環境である php は元々 Unix 系環境ベースのため、バックスラッシュだと動作がおかしくなる場合があります

// また、数値以外は必ず「'」(シングルクォーテーション)で囲んでください
// 囲まない場合・左右どちらかの「'」が欠けている場合は動作がおかしくなる場合があります
// また、「`」(バッククオート)は別物です、ご注意ください

// デフォルトの動画の画質
$quality_default = '810p'; // 1080p・810p・720p・540p・360p・240P から選択

// デフォルトのエンコーダー
// ffmpeg が通常のエンコーダー(ソフトウェアエンコード)、
// QSVEnc がハードウェアエンコーダーです
// ( QSVEnc の方が CPU を消費しない・エンコードが早いのでオススメですが、Intel 製の一部の CPU しか使えません)
$encoder_default = 'QSVEnc'; // ffmpeg・QSVEnc から選択

// デフォルトで字幕データをストリームに含めるか(含める… true 含めない… false )
// 字幕データを配信するストリームに含めると、字幕をプレイヤー側で表示出来るようになります
// ただし、字幕を含めると ffmpeg の場合はエラーを吐いてストリームが開始出来ない場合があったり、
// 字幕の無い番組の場合、一部のセグメントのエンコードが遅れ、ストリームがカクつく場合があります
// 字幕自体は個々にプレイヤー側で表示/非表示を切り替え可能です
// また、ファイル再生時は常に字幕データをストリームに含んだ状態で配信されます(リアルタイム性が必要ではないため)
$subtitle_default = 'false';

// BonDriver は (TVRemotePlusをインストールしたフォルダ)/bin/TSTask/BonDriver/ に配置してください
// デフォルトの BonDriver (地デジ用・変更必須)
// 例：$BonDriver_default_T = 'BonDriver_Spinel_PX_Q3PE4_T0.dll';
$BonDriver_default_T = '';

// デフォルトの BonDriver (BS用・変更必須)
// 例：$BonDriver_default_S = 'BonDriver_Spinel_PX_Q3PE4_S0.dll';
$BonDriver_default_S = '';

// 録画した TS ファイルのあるディレクトリ(フォルダ・変更必須)
// ファイル再生の際に利用します
// 例：$TSfile_dir = 'E:/TV-Record/';
$TSfile_dir = '';

// EDCB の HTTP サーバ( EMWUI )の API がある URL を指定します(番組表取得で利用します・変更必須)
// http://(EDCB(EMWUI)の動いてるPCのローカルIP):5510/api/ のように指定します
// 例：$EDCB_http_url = 'http://192.168.1.11:5510/api/';
$EDCB_http_url = '';

// 再生履歴を何件まで保持するか
// デフォルトは10件です
$history_keep = 10;

// ストリーム開始設定後の画面を表示せずに再生画面へリダイレクトするか
// (リダイレクトする… true リダイレクトしない… false )
$setting_redirect = 'true';


// ***** ニコニコ実況関連設定 *****
// ニコニコのメールアドレスとパスワードは、ニコニコ実況への投稿・過去ログの取得に必須です

// ニコニコにログインする際のメールアドレス(変更必須)
// 例：$nicologin_mail = 'example@gmail.com';
$nicologin_mail = '';

// ニコニコにログインする際のパスワード(変更必須)
// 例：$nicologin_password = '12345678';
$nicologin_password = '';


// ***** Twitter 関連設定 *****

// ハッシュタグ付きツイートを連投した際に何秒以内ならハッシュタグを消してシャドウバンを回避するか
// Twitter の規制が厳しいため、60秒以内(？)にハッシュタグつけて連投するとシャドウバン(Search Ban)されるみたいです
// その対策用です、鬱陶しいのであれば0にすればオフになります
$tweet_time = 60;

// 画像付きツイートを投稿した際に一度アップロードした画像を削除するかどうか
// 削除するなら true 、削除しない( htdocs/tweet/upload/ フォルダに保存する)なら false です
$tweet_delete = 'false';

// ベースとなるURL($_SERVER["HTTP_HOST"] はIPアドレスを自動で判定する・基本はデフォルトのままでOK)
$BASEURL = $scheme.$_SERVER["HTTP_HOST"].'/';


// ***** Twitter API関連 *****
// TVRemotePlus からツイートを投稿するのに必須です
// 別途、TwitterAPI の開発者アカウントを取得する必要があります

// コンシューマーキー(変更必須)
// 例：$CONSUMER_KEY =  'XXXXXXXXXXXXXXXXXXXXXXXXX';
$CONSUMER_KEY =  '';
// コンシューマーシークレットキー(変更必須)
// 例：$CONSUMER_SECRET = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$CONSUMER_SECRET = '';

// コールバックURLを指定(変更する必要はありません)
$OAUTH_CALLBACK = $BASEURL.'tweet/callback.php';


// ***** basic 認証設定 *****
// basic 認証を利用する場合に設定してください

// basic 認証を利用するかどうか(利用する… true 利用しない… false )
$basicauth = 'false';

// basic 認証の際のユーザー名(変更必須)
// 例：$basicauth_user = 'user';
$basicauth_user = '';

// basic 認証の際のパスワード(変更必須)
// 例：$basicauth_password = '12345678';
$basicauth_password = '';


// ↑↑↑↑↑ 編集箇所ここまで ↑↑↑↑↑


// ***** 各種exeファイルのパス設定 *****
// いじる必要はありません

// rplsinfo の名前とパス
$rplsinfo_exe =  'rplsinfo.exe';
$rplsinfo_path =  $base_dir.'bin/'.$rplsinfo_exe;

// ffmpeg の名前とパス
$ffmpeg_exe =  'ffmpeg.exe';
$ffmpeg_path = $base_dir.'bin/'.$ffmpeg_exe;

// QSVEncC の名前とパス
$qsvencc_exe = 'QSVEncC64.exe';
$qsvencc_path =  $base_dir.'bin/QSVEncC/'.$qsvencc_exe;

// TSTask の名前とパス
$tstask_exe = 'TSTask.exe';
$tstask_path = $base_dir.'bin/TSTask/'.$tstask_exe;
$tstaskcentre_exe = 'TSTaskCentre.exe';
$tstaskcentre_path = $base_dir.'bin/TSTask/'.$tstask_exe;


// ***** その他・詳細設定 *****
// 基本はいじる必要はありません
// 変更すると一部動作しなくなるものも含まれています

// サイト名
$site_title = 'TVRemotePlus';

// UDP送信時の開始ポート番号
$udp_port = 1234;

// アイコンのパス
// htdocs からのパス
$icon_file = 'files/TVRemotePlus.svg';

// BonDriver のあるディレクトリ(フォルダ)
// デフォルトは TSTaskのあるフォルダ/BonDriver/ フォルダです
$BonDriver_dir = $base_dir.'bin/TSTask/BonDriver/';

// セグメントを一時的に保管するフォルダのパス
// 変更すると作動しなくなります
// HDD に変更したい場合、Windows のシンボリックリンク機能を利用して下さい
$segment_folder = $base_dir.'htdocs/stream/';

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

// スタンバイ時の m3u8 のパス
$standby_m3u8 = $base_dir.'data/standby.m3u8';

// .htaccess のパス
$htaccess = $base_dir.'htdocs/.htaccess';

// .htpasswd のパス
$htpasswd = $base_dir.'htdocs/.htpasswd';


// ******** 内部処理 ********

// モジュール読み込み
require_once (dirname(__FILE__).'/module.php');

// BonDriverのチャンネルを取得
list($ch, $sid) = initBonChannel($BonDriver_dir);
