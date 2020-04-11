<?php

	// Shift-JISに
	ini_set('default_charset', 'sjis-win');
	
	// べースフォルダ
	$base_dir = rtrim(str_replace('\\','/',dirname(__FILE__)), '/');

	// バージョン
	$version = file_get_contents(dirname(__FILE__).'/data/version.txt');

	// Shift-JISのダメ文字対策（主にWin7向け・SJIS滅びろ）
	function sj_str($text) {
		$str_arr = array('―\','ソ\','Ы\','Ⅸ\','噂\','浬\','欺\','圭\','構\','蚕\','十\','申\','曾\','箪\','貼\','能\','表\','暴\','予\',
						'禄\','兔\','喀\','媾\','彌\','拿\','杤\','歃\','濬\','畚\','秉\','綵\','臀\','藹\','觸\','軆\','鐔\','饅\','鷭\', "");
		$text = str_replace("\\\\", "\\", $text);
		for ($i = 0; $str_arr[$i] != ""; $i++) {
			$text = str_replace($str_arr[$i] . "\\", mb_substr($str_arr[$i], 0, 1), $text); // 先に\がついていたら消して
			$text = str_replace($str_arr[$i], $str_arr[$i] . "\\", $text); // \つける
		}
		return $text;
	}
	
	// ' // ←エディタの表示がおかしくなる事への対策

	// フォルダコピー関数
	function dir_copy($dir_name, $new_dir){

		if (!is_dir($dir_name)) {
			copy(sj_str($dir_name), sj_str($new_dir));
			return true;
		}
		if (!is_dir($new_dir)) {
			mkdir($new_dir);
		}

		if (is_dir($dir_name)) {
			if ($dh = opendir($dir_name)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (is_dir($dir_name . "/" . $file)) {
						dir_copy($dir_name . "/" . $file, $new_dir . "/" . $file);
					} else {
						copy(sj_str($dir_name . "/" . $file), sj_str($new_dir . "/" . $file));
					}
				}
				closedir($dh);
			}
		}
		return true;
	}

	// フォルダがない場合にのみディレクトリを作成する
	function if_mkdir($mkdir){
		global $serverroot;
		if (!file_exists($serverroot.$mkdir)){
			mkdir($serverroot.$mkdir);
			echo '    フォルダ '.$serverroot.$mkdir.' を作成しました。'."\n";
			echo "\n";
		}
	}

	// コピー
	function if_copy($copy, $flg = false){
		global $base_dir, $serverroot;
		if (!file_exists($serverroot.$copy) or $flg == true){
			dir_copy($base_dir.$copy, $serverroot.$copy);
			echo '    '.$base_dir.$copy.' を'."\n";
			echo '    '.$serverroot.$copy.' にコピーしました。'."\n";
			echo "\n";
		}
	}

	// 出力
	echo "\n";
	echo '  -------------------------------------------------------------------'."\n";
	echo '                    TVRemotePlus '.$version.' インストーラー'."\n";
	echo '  -------------------------------------------------------------------'."\n";
	echo "\n";
	echo '    TVRemotePlus のセットアップを行うインストーラーです。'."\n";
	echo '    途中でキャンセルする場合はそのままウインドウを閉じてください。'."\n";
	echo "\n";
	echo '  -------------------------------------------------------------------'."\n";

	echo "\n";
	echo '    1. TVRemotePlus をインストールするフォルダを指定します。'."\n";
	echo "\n";
	echo '      フォルダをドラッグ&ドロップするか、フォルダパスを入力してください。'."\n";
	echo '      なお、Users・Program Files 以下と、日本語(全角)が含まれるパス、'."\n";
	echo '      半角スペースを含むパスは正常に動作しなくなる原因となるため、避けてください。'."\n";
	echo "\n";
	echo '      インストールするフォルダ：';
	// TVRemotePlusをインストールするフォルダ
	$serverroot = trim(fgets(STDIN));
	$serverroot = str_replace('"', '', $serverroot);
	echo "\n";
	// 空だったら
	if (empty($serverroot)){
		while(empty($serverroot)){
			echo '     入力欄が空です。もう一度入力してください。'."\n";
			echo "\n";
			echo '     インストールするフォルダ：';
			$serverroot = trim(fgets(STDIN));
			$serverroot = str_replace('"', '', $serverroot);
			echo "\n";
		}
	}
	// 置換
	$serverroot = str_replace('\\', '/', $serverroot);
	$serverroot = rtrim($serverroot, '/');

	// フォルダが存在する場合アップデート
	if (file_exists($serverroot) and file_exists($serverroot.'/config.php')){
		echo '      既に指定されたフォルダにインストールされていると判定しました。'."\n";
		echo '      アップデートモードでインストールします。'."\n";
		echo '      このままアップデートモードでインストールするには 1 を、'."\n";
		echo '      全て新規インストールする場合は 2 を入力してください。'."\n";
		echo '      Enter キーで次に進む場合、自動でアップデートモードを選択します。'."\n";
		echo "\n";
		echo '      インストールモード：';
		$update_flg = trim(fgets(STDIN));
		// 判定
		if ($update_flg == 2) $update = false;
		else $update = true;
		echo "\n";
	} else {	
		$update = false;
	}


	// 新規インストールの場合はIPとポートを訊く
	if ($update === false){
		echo '    2. TVRemotePlus をインストールする PC の、ローカル IP アドレスを入力してください。'."\n";
		echo "\n";
		echo '      ローカル IP アドレスは、通常 192.168.x.xx のような形式の家の中用の IP アドレスです。'."\n";
		echo '      インストーラーで検知したローカル IP アドレスは '.getHostByName(getHostName()).' です。'."\n";
		echo '      判定が間違っている場合もあります (VPN 等を使っていて複数の仮想デバイスがある場合など)'."\n";
		echo '      その場合、メインで利用しているローカル IP アドレスを ipconfig で調べ、入力してください。'."\n";
		echo '      よくわからない場合は、Enter キーを押し、次に進んでください。'."\n";
		echo "\n";
		echo '      ローカル IP アドレス：';
		// TVRemotePlusを稼働させるPC(サーバー)のローカルLAN内IP
		$serverip = trim(fgets(STDIN));
		// 空だったら
		if (empty($serverip)){
			$serverip = getHostByName(getHostName());
		}
		echo "\n";

		echo '    3. 必要な場合、TVRemotePlus が利用するポートを設定して下さい。'."\n";
		echo "\n";
		echo '      通常は、ブラウザの URL 欄から http://'.$serverip.':8000 でアクセスできます。'."\n";
		echo '      この 8000 の番号を変えたい場合は、ポート番号を入力してください。'."\n";
		echo '      HTTPS 接続時はポート番号が ここで設定した番号 + 100 になります。'."\n";
		echo '      よくわからない場合は、Enter キーを押し、次に進んでください。'."\n";
		echo "\n";
		echo '      利用ポート番号：';
		// TVRemotePlusを稼働させるポート
		$http_port = trim(fgets(STDIN));
		// 空だったら
		if (empty($http_port)){
			$http_port = 8000;
		}
		$https_port = $http_port + 100; // SSL用ポート
		echo "\n";

		echo '    4. TVTest の BonDriver は 32bit ですか？ 64bit ですか？'."\n";
		echo "\n";
		echo '      32bit の場合は 1 、64bit の場合は 2 と入力してください。'."\n";
		echo '      この項目で 32bit 版・64bit 版どちらの TSTask を使うかが決まります。'."\n";
		echo '      インストール終了後、お使いの TVTest の BonDriver と ch2 ファイルを、'."\n";
		echo '      '.$serverroot.'/bin/TSTask/BonDriver/ にコピーしてください。'."\n";
		echo '      Enter キーで次に進む場合、自動で 32bit の TSTask を選択します。'."\n";
		echo "\n";
		echo '      TVTest の BonDriver：';
		// TVTestのBonDriver
		$bondriver = trim(fgets(STDIN));
		// 判定
		if ($bondriver != 2) $bondriver = 1;
		echo "\n";

		echo '    5. 録画ファイルのあるフォルダを指定します。'."\n";
		echo "\n";
		echo '      フォルダをドラッグ&ドロップするか、フォルダパスを入力してください。'."\n";
		echo '      なお、特殊なパス (UNCパス等) の場合、正常に動作しない可能性があります。'."\n";
		echo "\n";
		echo '      録画ファイルのあるフォルダ：';
		// 録画ファイルのあるフォルダ
		$TSfile_dir = trim(fgets(STDIN));
		$TSfile_dir = str_replace('"', '', $TSfile_dir);
		echo "\n";
		// 空だったら
		if (empty($TSfile_dir)){
			while(empty($TSfile_dir)){
				echo '      入力欄が空です。もう一度入力してください。'."\n";
				echo "\n";
				echo '      録画ファイルのあるフォルダ：';
				$TSfile_dir = trim(fgets(STDIN));
				$TSfile_dir = str_replace('"', '', $TSfile_dir);
				echo "\n";
			}
		}
		// フォルダがなかったら
		if (!file_exists($TSfile_dir)){
			while(!file_exists($TSfile_dir)){
				echo '      フォルダが存在しません。もう一度入力してください。'."\n";
				echo "\n";
				echo '      録画ファイルのあるフォルダ：';
				$TSfile_dir = trim(fgets(STDIN));
				$TSfile_dir = str_replace('"', '', $TSfile_dir);
				echo "\n";
			}
		}
		// 置換
		$TSfile_dir = str_replace('\\', '/', $TSfile_dir);
		$TSfile_dir = rtrim($TSfile_dir, '/').'/';
	}

	echo '  -------------------------------------------------------------------'."\n";
	echo "\n";
	echo '    インストールを開始します。'."\n";
	echo "\n";
	echo '  -------------------------------------------------------------------'."\n";
	echo "\n";
	echo '    TVRemotePlus をインストールしています…'."\n";
	echo "\n";

	sleep(1); // 1秒

	// フォルダを作る
	if_mkdir('/');
	if_copy ('/config.default.php', true);
	if_copy ('/createcert.bat', true);
	if_copy ('/LICENSE.txt', true);
	if_copy ('/README.md', true);
	if_copy ('/stream.bat', true);
	if_copy ('/bin', true);
	if_copy ('/data', true);
	if_copy ('/docs', true);
	if_copy ('/htdocs', true);
	if_copy ('/logs', true);
	if_copy ('/modules', true);

	// 設定ファイル
	$tvrp_conf_file = $serverroot.'/config.php';
	$tvrp_default_file = $serverroot.'/config.default.php';

	// 新規インストールのみの処理
	if ($update === false){

		// Apache の設定ファイル
		$httpd_conf_file = $serverroot.'/bin/Apache/conf/httpd.conf';
		$httpd_default_file = $serverroot.'/bin/Apache/conf/httpd.default.conf';
		// PHP の設定ファイル
		$php_ini_file = $serverroot.'/bin/PHP/php.ini';
		$php_default_file = $serverroot.'/bin/PHP/php.default.ini';

		// config.default.php を config.php にコピー
		copy($tvrp_default_file, $tvrp_conf_file);
		// httpd.default.conf を httpd.conf にコピー
		copy($httpd_default_file, $httpd_conf_file);
		// php.default.ini を php.ini にコピー
		copy($php_default_file, $php_ini_file);
		
		// TSTask のコピー
		if ($bondriver == 2){
			copy($serverroot.'/bin/TSTask/64bit/BonDriver_TSTask.dll', $serverroot.'/bin/TSTask/BonDriver_TSTask.dll');
			copy($serverroot.'/bin/TSTask/64bit/TSTask.exe', $serverroot.'/bin/TSTask/TSTask-tvrp.exe');
			copy($serverroot.'/bin/TSTask/64bit/TSTaskCentre.exe', $serverroot.'/bin/TSTask/TSTaskCentre-tvrp.exe');
		} else {
			copy($serverroot.'/bin/TSTask/32bit/BonDriver_TSTask.dll', $serverroot.'/bin/TSTask/BonDriver_TSTask.dll');
			copy($serverroot.'/bin/TSTask/32bit/TSTask.exe', $serverroot.'/bin/TSTask/TSTask-tvrp.exe');
			copy($serverroot.'/bin/TSTask/32bit/TSTaskCentre.exe', $serverroot.'/bin/TSTask/TSTaskCentre-tvrp.exe');
		}

		// 状態設定ファイルを初期化
		$jsonfile = $serverroot.'/data/settings.json';
		$json['1']['state'] = 'Offline';
		$json['1']['channel'] = '0';
		if (!file_exists($jsonfile)) file_put_contents($jsonfile, json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

		// TVRemotePlus の設定ファイル
		$tvrp_conf = file_get_contents($tvrp_conf_file);
		// 置換
		$tvrp_conf = preg_replace('/^\$TSfile_dir =.*/m', '$TSfile_dir = \''.mb_convert_encoding($TSfile_dir, 'UTF-8', 'SJIS, SJIS-WIN').'\';', $tvrp_conf);
		// 書き込み
		file_put_contents($tvrp_conf_file, $tvrp_conf);

		// Apache の設定ファイル
		$httpd_conf = file_get_contents($httpd_conf_file);
		// 置換
		$httpd_conf = preg_replace("/Define SRVROOT.*/", 'Define SRVROOT "'.$serverroot.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define SRVIP.*/", 'Define SRVIP "'.$serverip.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define HTTP_PORT.*/", 'Define HTTP_PORT "'.$http_port.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define HTTPS_PORT.*/", 'Define HTTPS_PORT "'.$https_port.'"', $httpd_conf);
		// 書き込み
		file_put_contents($httpd_conf_file, $httpd_conf);

		// PHP の設定ファイル
		$php_ini = file_get_contents($php_ini_file);
		// 置換
		$php_ini = preg_replace('/^extension_dir =.*/m', 'extension_dir = "'.mb_convert_encoding($serverroot.'/bin/PHP/ext/', 'UTF-8', 'SJIS, SJIS-WIN').'"', $php_ini);
		// 書き込み
		file_put_contents($php_ini_file, $php_ini);

		// HTTPS 接続用オレオレ証明書の作成
		echo '    HTTPS 接続用の自己署名証明書を作成します。'."\n";
		echo "\n";
		echo '  -------------------------------------------------------------------'."\n";
		echo "\n";

		$cmd = 'pushd "'.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\" && '.
			   '.\openssl.exe req -new -newkey rsa:2048 -nodes -config ..\conf\openssl.cnf -keyout ..\conf\server.key -out ..\conf\server.crt'.
			   ' -x509 -days 3650 -sha256 -subj "/C=JP/ST=Tokyo/O=TVRemotePlus/CN='.$serverip.'" -addext "subjectAltName = IP:127.0.0.1,IP:'.$serverip.'"';

		exec($cmd, $opt1, $return1);
		copy($serverroot.'/bin/Apache/conf/server.crt', $serverroot.'/htdocs/files/TVRemotePlus.crt');
		echo "\n";
		echo '  -------------------------------------------------------------------'."\n";
		echo "\n";
		if ($return1 == 0){
			echo '    自己署名証明書を正常に作成しました。'."\n";
		} else {
			echo '    自己署名証明書の作成に失敗しました…'."\n\n";
			echo '    自己署名証明書が正常に作成されていない場合、Apache の起動に失敗します。'."\n";
			echo '    インストール先にコピーされている createcert.bat を実行して自己署名証明書を作成するか、'."\n";
			echo '    再インストールし、'.$serverroot.'/bin/Apache/conf/ に server.crt と server.key'."\n";
			echo '    が作成されていることを確認してから TVRemotePlus を起動してください。'."\n";
		}

		// ショートカット作成
		// 既にショートカットがある場合は上書きしないようショートカット名を変える
		if (file_exists(getenv('USERPROFILE').'\Desktop\TVRemotePlus - launch.lnk')){
			$shortcut_file = '\Desktop\TVRemotePlus - launch (1).lnk';
			$shortcut_count = 1;
			while (file_exists(getenv('USERPROFILE').$shortcut_file)){
				if (file_exists(getenv('USERPROFILE').$shortcut_file)){
					$shortcut_count++;
					$shortcut_file = '\Desktop\TVRemotePlus - launch ('.$shortcut_count.').lnk';
				}
			}
		} else {
			$shortcut_file = '\Desktop\TVRemotePlus - launch.lnk';
		}
		$powershell = '$shell = New-Object -ComObject WScript.Shell; '.
					  '$lnk = $shell.CreateShortcut(\"$Home'.$shortcut_file.'\"); '.
					  '$lnk.TargetPath = \"'.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\httpd.exe\"; '.
					  '$lnk.WorkingDirectory = \"'.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\"'.
					  '$lnk.WindowStyle = 7; '.
					  '$lnk.Save()';
		exec('powershell -Command "'.$powershell.'"', $opt2, $return2);
		echo "\n";
		if ($return2 == 0) echo '    ショートカットを作成しました。'."\n";
		else echo '    ショートカットの作成に失敗しました…'."\n";
		
		echo "\n";

	// アップデート処理
	} else {

		// 古い設定ファイルを読み込む
		require_once ($tvrp_conf_file);

		// config.default.php を config.php にコピー
		copy($tvrp_default_file, $tvrp_conf_file);

		// 設定を配列に格納
		@$config['quality_default'] = $quality_default;
		@$config['encoder_default'] = $encoder_default;
		@$config['BonDriver_default_T'] = $BonDriver_default_T;
		@$config['BonDriver_default_S'] = $BonDriver_default_S;
		@$config['stream_current_live'] = $stream_current_live;
		@$config['stream_current_file'] = $stream_current_file;
		@$config['subtitle_default'] = $subtitle_default;
		@$config['subtitle_file_default'] = $subtitle_file_default;
		@$config['TSfile_dir'] = $TSfile_dir;
		@$config['TSinfo_dir'] = $TSinfo_dir;
		@$config['EDCB_http_url'] = $EDCB_http_url;
		@$config['reverse_proxy_url'] = $reverse_proxy_url;
		@$config['setting_hide'] = $setting_hide;
		@$config['silent'] = $silent;
		@$config['history_keep'] = $history_keep;
		@$config['update_confirm'] = $update_confirm;
		@$config['nicologin_mail'] = $nicologin_mail;
		@$config['nicologin_password'] = $nicologin_password;
		@$config['tweet_time'] = $tweet_time;
		@$config['tweet_upload'] = $tweet_upload;
		@$config['tweet_delete'] = $tweet_delete;
		@$config['CONSUMER_KEY'] = $CONSUMER_KEY;
		@$config['CONSUMER_SECRET'] = $CONSUMER_SECRET;
		@$config['basicauth'] = $basicauth;
		@$config['basicauth_user'] = $basicauth_user;
		@$config['basicauth_password'] = $basicauth_password;
		@$config['setting_redirect'] = $setting_redirect;
		@$config['encoder_log'] = $encoder_log;
		@$config['encoder_window'] = $encoder_window;
		@$config['TSTask_shutdown'] = $TSTask_shutdown;
		@$config['udp_port'] = $udp_port;
		@$config['hlslive_time'] = $hlslive_time;
		@$config['hlsfile_time'] = $hlsfile_time;
		@$config['hlslive_list'] = $hlslive_list;


		// 新しくコピーした設定ファイルに以前の設定をインポートする
		$tvrp_conf = file_get_contents($tvrp_conf_file);

		foreach ($config as $key => $value) {

			// 空でなければ
			if (!empty($value)){

				// シングルクォーテーションを取る（セキュリティ対策）
				$value = str_replace('\'', '', $value);

				// 数値化できるものは数値に変換しておく
				if (is_numeric($value) and mb_substr($value, 0, 1) != '0'){
					$set = intval($value);
				} else {
					$set = '\''.strval($value).'\'';
				}

				// バックスラッシュ(\)を見つけたらスラッシュに変換
				if (strpos($set, '\\') !== false){
					$set = str_replace('\\', '/', $set);
				}
				
				// config.php を書き換え
				$tvrp_conf = preg_replace("/^\\$$key =.*;/m", '$'.$key.' = '.$set.';', $tvrp_conf); // 置換
				
			}
		}

		file_put_contents($tvrp_conf_file, $tvrp_conf); // 書き込み
	}

	echo '  -------------------------------------------------------------------'."\n";
	echo "\n";
	echo '    インストールを完了しました。'."\n";
	echo "\n";
	sleep(1); // 1秒

	// 新規インストールのみの処理
	if ($update === false){
		echo '    セットアップはまだ終わっていません。'."\n\n";
		echo '    BonDriver と TVTest のチャンネル設定ファイル (.ch2) は '."\n";
		echo '    '.$serverroot .'/bin/TSTask/BonDriver/ に忘れずに入れてください。'."\n\n";
		echo '    終わったら、デスクトップのショートカットから TVRemotePlus を起動し、'."\n";
		echo '    ブラウザから http://'.$serverip.':'.$http_port.'/ へアクセスします。'."\n";
		echo '    その後、≡ サイドメニュー → 設定 → 環境設定 から必要な箇所を設定してください。'."\n\n";
		echo '    PWA 機能を使用する場合は、設定ページからダウンロードできる自己署名証明書を'."\n";
		echo '    あらかじめ端末にインストールした上で、 https://'.$serverip.':'.$https_port.'/ にアクセスしてください。'."\n";
		echo "\n";
	}

	echo '    終了するには何かキーを押してください。'."\n";
	echo "\n";
	echo '  -------------------------------------------------------------------'."\n";
	echo "\n";
	trim(fgets(STDIN));
