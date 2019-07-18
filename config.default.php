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

// バージョン
$version = '1.0.0-rc1';


// ******** 内部処理 ********

// iniファイルに書き込む関数
function save_ini_file($file, $ini){
	$fp = fopen($file, 'w');
	foreach ($ini as $key => $value){
		// iniファイルに書き込み
		fputs($fp, $key.'='.$value."\n");
	}
	fclose($fp);
}

// Windows用非同期コマンド実行関数
function win_exec($cmd){
	$fp = popen($cmd.' > nul', "r");
	pclose($fp);
}

// ch2を整形して連想配列化する関数
function ch2convert($ch2_file){

	// ch2を取得
	$ch2_data = mb_convert_encoding(file_get_contents($ch2_file), 'UTF-8', 'SJIS, UTF-16, UTF-8');

	// 置換
	$ch2_data = str_replace("\r\n", "\n", $ch2_data); // CR+LFからLFに変換
	$ch2_data = str_replace("\n\n", "", $ch2_data); // 空行削除
	$ch2_data = str_replace("; TVTest チャンネル設定ファイル\n", "", $ch2_data);
	$ch2_data = str_replace("; 名称,チューニング空間,チャンネル,リモコン番号,サービスタイプ,サービスID,ネットワークID,TSID,状態\n", "", $ch2_data);
	$ch2_data = str_replace("; TVTest チャンネル設定ファイル\n", "", $ch2_data);
	$ch2_data = str_replace(";#SPACE(0,UHF)\n", "", $ch2_data);
	$ch2_data = str_replace(";#SPACE(0,BS)\n", "", $ch2_data);
	//$ch2_data = str_replace(";#SPACE(1,CS110)\n", "", $ch2_data);
	$ch2_data = preg_replace("/;#SPACE\(1\,CS110\).*$/s", "", $ch2_data); //CSチャンネルは削除
	$ch2_data = rtrim($ch2_data);

	// 改行で分割
	$ch2 = explode("\n", $ch2_data);

	// さらにコンマで分割
	foreach ($ch2 as $key => $value) {
		$ch2[$key] = explode(",", $ch2[$key]);
	}

	return $ch2;
}

// [新]とかをHTML化
function convertSymbol($string){
	$string = str_replace('[', '<span class="mark">', $string);
	$string = str_replace(']', '</span>', $string);
	return $string;
}

// basic認証関連
// 若干時間がかかるため index.php の読み込み時のみに実行する
function basicauth($basicauth, $basicauth_user, $basicauth_password){

	global $base_dir, $htaccess, $htpasswd;

	// basic認証有効
	if ($basicauth == 'true'){

		// .htpasswd ファイル作成
		$htpasswd_conf = $basicauth_user.':'.password_hash($basicauth_password, PASSWORD_BCRYPT);
		file_put_contents($htpasswd, $htpasswd_conf);

		// .htaccess 書き換え
		$htaccess_conf = file_get_contents($htaccess);
		
		// 文言がない場合は追加する
		if (!preg_match("/AuthType Basic.*/", $htaccess_conf)){

			// .htpasswd の絶対パスを修正
			$htaccess_conf = $htaccess_conf."\n".
				'AuthType Basic'."\n".
				'AuthName "Input your ID and Password."'."\n".
				'AuthUserFile '.$base_dir.'htdocs/.htpasswd'."\n".
				'require valid-user'."\n";
				
			file_put_contents($htaccess, $htaccess_conf);
		}

	} else {

		// .htpasswd 削除
		if (file_exists($htpasswd)) unlink($htpasswd);

		// .htaccess 文言削除
		$htaccess_conf = file_get_contents($htaccess);
		if (preg_match("/AuthType Basic.*/", $htaccess_conf)){
			$htaccess_conf = preg_replace("/AuthType Basic.*/s", '', $htaccess_conf);
			file_put_contents($htaccess, $htaccess_conf);
		}

	}
}


// BonDriver_DirからBonDriverを検索
foreach (glob($BonDriver_dir."BonDriver_*.dll") as $i => $file) {
	$BonDriver_dll[$i] = str_replace($BonDriver_dir, '', $file);
}

// 地デジのch2があれば
if (isset(glob($BonDriver_dir."BonDriver_*T0*.ch2")[0])){

	// BonDriver_DirからBonDriverのチャンネル設定ファイルを検索
	$BonDriver_ch2_file_T = glob($BonDriver_dir."BonDriver_*T0*.ch2")[0];
	$BonDriver_ch2_T = ch2convert($BonDriver_ch2_file_T);

	// 地デジ(T)用チャンネルをセット
	foreach ($BonDriver_ch2_T as $key => $value) {
		// サービス状態が1の物のみセットする
		// あとサブチャンネルはセットしない
		if ($value[4] == 1 and !isset($ch_T[strval($value[3])])){
			// 全角は半角に直す
			// チャンネル名
			$ch_T[strval($value[3])] = mb_convert_kana($value[0], 'asv');
			// サービスID(SID)
			$sid_T[strval($value[3])] = mb_convert_kana($value[5], 'asv');
		}
	}
} else {
	$ch_T = array();
	$sid_T = array();
}

// BSCSのch2があれば
if (isset(glob($BonDriver_dir."BonDriver_*S0*.ch2")[0])){

	// BonDriver_DirからBonDriverのチャンネル設定ファイルを検索
	$BonDriver_ch2_file_S = glob($BonDriver_dir."BonDriver_*S0*.ch2")[0];
	$BonDriver_ch2_S = ch2convert($BonDriver_ch2_file_S);

	// BSCS(S)用チャンネルをセット
	foreach ($BonDriver_ch2_S as $key => $value) {
		// サービス状態が1の物のみセットする
		// あとサブチャンネルはセットしない
		if ($value[4] == 1 and !isset($ch_S[strval($value[5])])
			// 正規表現と人力で無理やり弾く
			and !preg_match("/1[4-8]2/", $value[5]) and !preg_match("/1[4-8]3/", $value[5]) and $value[5] != 102 and $value[5] != 104){
			// 全角は半角に直す
			// チャンネル名
			$ch_S[strval($value[5])] = mb_convert_kana($value[0], 'asv');
			// サービスID(SID)
			$sid_S[strval($value[5])] = mb_convert_kana($value[5], 'asv');
		}
	}
} else {
	$ch_S = array();
	$sid_S = array();
}

// 合体させる
$ch = $ch_T + $ch_S;
$sid = $sid_T + $sid_S;

