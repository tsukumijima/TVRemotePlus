<?php

	// バージョン
	$version = file_get_contents(dirname(__FILE__).'/data/version.txt');

	// 設定読み込み
	require_once (dirname(__FILE__).'/config.php');

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

	// NVEncC の名前とパス
	$nvencc_exe = 'NVEncC64.exe';
	$nvencc_path =  $base_dir.'bin/NVEncC/'.$nvencc_exe;

	// TSTask の名前とパス
	$tstask_exe = 'TSTask.exe';
	$tstask_path = $base_dir.'bin/TSTask/'.$tstask_exe;
	$tstaskcentre_exe = 'TSTaskCentre.exe';
	$tstaskcentre_path = $base_dir.'bin/TSTask/'.$tstask_exe;


	// ***** 詳細設定 *****
	// いじる必要はありません
	// 変更すると一部動作しなくなるものも含まれています

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


	// ***** 内部処理 *****

	// BonDriverのチャンネルを取得
	list($BonDriver_dll, $ch, $ch_T, $ch_S, $ch_CS, $sid, $sid_T, $sid_S, $sid_CS, $tsid, $tsid_T, $tsid_S, $tsid_CS) = initBonChannel($BonDriver_dir);
	
	// 各種モジュール

	// Windows用非同期コマンド実行関数
	function win_exec($cmd){
		$fp = popen($cmd.' > nul', "r");
		pclose($fp);
	}

	// BOM除去
	function removeBOM($text){
		// 悪しきBOM
		$bom = pack('H*', 'EFBBBF');

		// 置換して返す
		$newtext = preg_replace("/^$bom/", '', $text);
		return $newtext;
	}

	// ch2を整形して連想配列化する関数
	function ch2Convert($ch2_file, $csflg = false){

		// ch2を取得
		$ch2_rawdata = removeBOM(file_get_contents($ch2_file));

		// 文字コード判定
		if (!empty(mb_detect_encoding($ch2_rawdata, 'SJIS, SJIS-WIN, EUC-JP, UTF-8'))){
			$charset = mb_detect_encoding($ch2_rawdata, 'SJIS, SJIS-WIN, EUC-JP, UTF-8');
		} else { // 何故かUTF-16だけ上手く検知されないバグが…
			$charset = 'UTF-16LE';
		}

		// ch2の文字コードをUTF-8に
		$ch2_data = mb_convert_encoding($ch2_rawdata, 'UTF-8', $charset);

		// 置換
		$ch2_data = str_replace("\r\n", "\n", $ch2_data); // CR+LFからLFに変換
		$ch2_data = str_replace("; TVTest チャンネル設定ファイル\n", "", $ch2_data);
		$ch2_data = preg_replace("/; 名称,チューニング空間.*/", "", $ch2_data);
		$ch2_data = str_replace(',,', ',1,', $ch2_data); // サービスタイプ欄がない場合に1として換算しておく
		if (!$csflg){
			$ch2_data = preg_replace("/;#SPACE\(1\,CS110\).*$/s", "", $ch2_data); //CSチャンネルは削除
			$ch2_data = preg_replace("/;#SPACE.*/", "", $ch2_data); //余計なコメントを削除
		} else {
			$ch2_data = preg_replace("/;#SPACE\(0\,BS\).*;#SPACE\(1\,CS110\)/s", "", $ch2_data); //BSチャンネルは削除
			$ch2_data = preg_replace("/;#SPACE.*/", "", $ch2_data); //余計なコメントを削除
		}
		$ch2_data = str_replace("\n\n", "", $ch2_data); // 空行削除
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
	// 若干時間がかかるため、index.php の読み込み時のみに実行する
	function basicAuth($basicauth, $basicauth_user, $basicauth_password){

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

	// ch2からチャンネルリストとサービスIDリストを取得する関数
	function initBonChannel($BonDriver_dir){

		// BonDriver_dirからBonDriverを検索
		foreach (glob($BonDriver_dir."[bB]on[dD]river_*.dll") as $i => $file) {
			$BonDriver_dll[$i] = str_replace($BonDriver_dir, '', $file);
		}
		if (!isset($BonDriver_dll)) $BonDriver_dll = array();

		// 地デジのch2があれば
		if (isset(glob($BonDriver_dir."[bB]on[dD]river_*_[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_*-[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_PT*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_PX*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[tT]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[tT]*.ch2")[0])){

			// BonDriver_DirからBonDriverのチャンネル設定ファイルを検索
			if (isset(glob($BonDriver_dir."[bB]on[dD]river_*_[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_*_[tT]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_*-[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_*-[tT]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_PT*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_PT*[tT]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_PX*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_PX*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[tT]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[tT]*.ch2")[0])){
				$BonDriver_ch2_file_T = glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[tT]*.ch2")[0];

			} else {
				$BonDriver_ch2_file_T = array();
			}

			$BonDriver_ch2_T = ch2Convert($BonDriver_ch2_file_T);

			// 地デジ(T)用チャンネルをセット
			foreach ($BonDriver_ch2_T as $key => $value) {
				// サービス状態が1の物のみセットする
				// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
				if ($value[4] != 2 and $value[8] == 1 and !isset($ch_T[strval($value[3])])){
					// 全角は半角に直す
					// チャンネル名
					$ch_T[strval($value[3])] = mb_convert_kana($value[0], 'asv');
					// サービスID(SID)
					$sid_T[strval($value[3])] = mb_convert_kana($value[5], 'asv');
					// トランスポートストリームID(TSID)
					$tsid_T[strval($value[3])] = mb_convert_kana($value[7], 'asv');
				}
			}
		} else {
			$ch_T = array();
			$sid_T = array();
			$tsid_T = array();
		}

		// BSCSのch2があれば
		if (isset(glob($BonDriver_dir."[bB]on[dD]river_*_[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_*-[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_PT*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_PX*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[sS]*.ch2")[0])
			 or isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[sS]*.ch2")[0])){

			// BonDriver_DirからBonDriverのチャンネル設定ファイルを検索
			if (isset(glob($BonDriver_dir."[bB]on[dD]river_*_[sS]*.ch2")[0])){
			$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_*_[sS]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_*-[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_*-[sS]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_PT*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_PT*[sS]*.ch2")[0];

			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_PX*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_PX*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_Spinel_PT*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_Spinel_PX*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_Proxy_PT*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_Proxy_PX*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PT*[sS]*.ch2")[0];
			
			} else if (isset(glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[sS]*.ch2")[0])){
				$BonDriver_ch2_file_S = glob($BonDriver_dir."[bB]on[dD]river_ProxySplitter_PX*[sS]*.ch2")[0];

			} else {
				$BonDriver_ch2_file_S = array();
			}

			$BonDriver_ch2_S = ch2Convert($BonDriver_ch2_file_S);
			$BonDriver_ch2_CS = ch2Convert($BonDriver_ch2_file_S, true);

			// BS用チャンネルをセット
			foreach ($BonDriver_ch2_S as $key => $value) {
				// サービス状態が1の物のみセットする
				// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
				if ($value[4] != 2 and $value[8] == 1 and !isset($ch_S[strval($value[5])])){
					// 正規表現と人力で無理やり弾く（処理の変更でいらなくなった）
					// and !preg_match("/1[4-8]2/", $value[5]) and !preg_match("/1[4-8]3/", $value[5]) and $value[5] != 102 and $value[5] != 104){
					// 全角は半角に直す
					// チャンネル名
					$ch_S[strval($value[5])] = mb_convert_kana($value[0], 'asv');
					// サービスID(SID)
					$sid_S[strval($value[5])] = mb_convert_kana($value[5], 'asv');
					// トランスポートストリームID(TSID)
					$tsid_S[strval($value[5])] = mb_convert_kana($value[7], 'asv');
				}
			}

			// CS用チャンネルをセット
			foreach ($BonDriver_ch2_CS as $key => $value) {
				// サービス状態が1の物のみセットする
				// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
				if ($value[4] != 2 and $value[8] == 1 and !isset($ch_CS[strval($value[5])])){
					// 全角は半角に直す
					// BS-TBSとQVCバッティング問題
					if (intval($value[5]) !== 161){
						// チャンネル名
						$ch_CS[strval($value[5])] = mb_convert_kana($value[0], 'asv');
						// サービスID(SID)
						$sid_CS[strval($value[5])] = mb_convert_kana($value[5], 'asv');
						// トランスポートストリームID(TSID)
						$tsid_CS[strval($value[5])] = mb_convert_kana($value[7], 'asv');
					// QVCのみ
					} else {
						// チャンネル名
						$ch_CS['161cs'] = mb_convert_kana($value[0], 'asv');
						// サービスID(SID)
						$sid_CS['161cs'] = mb_convert_kana($value[5], 'asv');
						// トランスポートストリームID(TSID)
						$tsid_CS['161cs'] = mb_convert_kana($value[7], 'asv');
					}
				}
			}

		} else {
			$ch_S = array();
			$ch_CS = array();
			$sid_S = array();
			$sid_CS = array();
			$tsid_S = array();
			$tsid_CS = array();
		}

		// 合体させる
		$ch = $ch_T + $ch_S + $ch_CS;
		$sid = $sid_T + $sid_S + $sid_CS;
		$tsid = $tsid_T + $tsid_S + $tsid_CS;

		return array($BonDriver_dll, $ch, $ch_T, $ch_S, $ch_CS, $sid, $sid_T, $sid_S, $sid_CS, $tsid, $tsid_T, $tsid_S, $tsid_CS);
	}


