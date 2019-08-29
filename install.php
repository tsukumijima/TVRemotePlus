<?php

	// Shift-JISに
	ini_set('default_charset', 'sjis-win');
	
	// べースフォルダ
	$base_dir = rtrim(str_replace('\\','/',dirname(__FILE__)), '/');

	// Shift-JIS 文字化け対策(主にWin7向け)
	// Shift-JIS は早く滅びろ
	// エディタで表示が変になるが気にしてはいけない
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
			echo '  フォルダ '.$serverroot.$mkdir.' を作成しました。'."\n";
			echo "\n";
		}
	}

	// コピー
	function if_copy($copy, $flg = false){
		global $base_dir, $serverroot;
		if (!file_exists($serverroot.$copy) or $flg == true){
			dir_copy($base_dir.$copy, $serverroot.$copy);
			echo '  '.$base_dir.$copy.' を'."\n";
			echo '  '.$serverroot.$copy.' にコピーしました。'."\n";
			echo "\n";
		}
	}

	// 出力
	echo "\n";
	echo ' ========================================================'."\n";
	echo '                 TVRemotePlus　インストーラー'."\n";
	echo ' ========================================================'."\n";
	echo "\n";
	echo '  TVRemotePlus のセットアップを行うインストーラーです。'."\n";
	echo '  途中でキャンセルする場合は Ctrl + C を押してください。'."\n";
	echo "\n";

	echo '  1. TVRemotePlus をインストールするフォルダを指定します。'."\n";
	echo "\n";
	echo '     フォルダをドラッグ&ドロップするか、ファイルパスを入力してください。'."\n";
	echo '     なお、Users・Program Files 以下と、日本語(全角)が含まれるパスは、'."\n";
	echo '     動作しなくなる原因となるため、避けてください。'."\n";
	echo "\n";
	echo '     インストールするフォルダ：';
	// TVRemotePlusのインストールフォルダ
	$serverroot = trim(fgets(STDIN));
	echo "\n";
	// 空だったら
	if (empty($serverroot)){
		while(empty($serverroot)){
			echo '     入力欄が空です。もう一度入力してください。'."\n";
			echo "\n";
			echo '     インストールするフォルダ：';
			$serverroot = trim(fgets(STDIN));
			echo "\n";
		}
	}
	// 置換
	$serverroot = str_replace('\\', '/', $serverroot);
	$serverroot = rtrim($serverroot, '/');

	// フォルダが存在する場合アップデート
	if (file_exists($serverroot) and file_exists($serverroot.'/config.php')){
		echo '     既に指定されたフォルダにインストールされていると判定しました。'."\n";
		echo '     アップデートモードでインストールします。'."\n";
		echo '     このままアップデートモードでインストールするには 1 を、'."\n";
		echo '     全て新規インストールする場合は 2 を入力してください。'."\n";
		echo "\n";
		echo '     インストール：';
		$update_flg = trim(fgets(STDIN));
		// 判定
		if ($update_flg == 1) $update = true;
		else $update = false;
		echo "\n";
	} else {	
		$update = false;
	}


	// 新規インストールの場合はIPとポートを訊く
	if ($update === false){
		echo '  2. TVRemotePlusをインストールするPCの、ローカルIPアドレスを入力してください。'."\n";
		echo "\n";
		echo '     ローカルIPアドレスは、通常 192.168.x.xx のような形式の家の中用の IP アドレスです。'."\n";
		echo '     インストーラーで検知したローカル IP アドレスは '.getHostByName(getHostName()).' です。'."\n";
		echo '     判定が間違っている場合もあります( VPN 等で複数の仮想デバイスがある場合など)。'."\n";
		echo '     その場合、メインで利用しているローカル IP アドレスを ipconfig で調べ、入力してください。'."\n";
		echo '     よくわからない場合は、Enter キーを押し、次に進んでください。'."\n";
		echo "\n";
		echo '     ローカルIPアドレス：';
		// TVRemotePlusを稼働させるPC(サーバー)のローカルLAN内IP
		$serverip = trim(fgets(STDIN));
		// 空だったら
		if (empty($serverip)){
			$serverip = getHostByName(getHostName());
		}
		echo "\n";

		echo '  3. 必要な場合、TVRemotePlusが利用するポートを設定して下さい。'."\n";
		echo "\n";
		echo '     通常は、ブラウザの URL 欄から http://'.$serverip.':8000 でアクセスできます。'."\n";
		echo '     この 8000 の番号を変えたい場合は、ポート番号を入力してください。'."\n";
		echo '     HTTPS 接続時はポート番号が ここで設定した番号 + 100 になります。'."\n";
		echo '     よくわからない場合は、Enter キーを押し、次に進んでください。'."\n";
		echo "\n";
		echo '     利用ポート番号：';
		// TVRemotePlusを稼働させるポート
		$port = trim(fgets(STDIN));
		// 空だったら
		if (empty($port)){
			$port = 8000;
		}
		$ssl_port = $port + 100; // SSL用ポート
		echo "\n";

		echo '  4. TVTest の BonDriver は 32bit ですか？ 64bit ですか？'."\n";
		echo "\n";
		echo '     32bit の場合は 1 、64bit の場合は 2 と入力してください。'."\n";
		echo '     この項目で 32bit 版・64bit版どちらの TSTask を使うかが決まります。'."\n";
		echo '     インストール終了後、お使いの TVTest の BonDriver と ch2 ファイルを、'."\n";
		echo '     '.$serverroot.'/bin/TSTask/BonDriver/ にコピーしてください。'."\n";
		echo '     Enter キーで次に進む場合、自動で 32bit を選択します。'."\n";
		echo "\n";
		echo '     TVTest の BonDriver：';
		// TVTestのBonDriver
		$bondriver = trim(fgets(STDIN));
		// 判定
		if ($bondriver != 2) $bondriver = 1;
		echo "\n";

		echo '  5. 録画ファイルのあるフォルダを指定します。'."\n";
		echo "\n";
		echo '     フォルダをドラッグ&ドロップするか、ファイルパスを入力してください。'."\n";
		echo '     なお、日本語(全角文字)が含まれるパスとネットワークドライブ上のフォルダは、'."\n";
		echo '     動作しなくなる原因となるため、避けてください。'."\n";
		echo "\n";
		echo '     録画ファイルのあるフォルダ：';
		// 録画ファイルのあるフォルダ
		$TSfile_dir = trim(fgets(STDIN));
		echo "\n";
		// 空だったら
		if (empty($TSfile_dir)){
			while(empty($TSfile_dir)){
				echo '     入力欄が空です。もう一度入力してください。'."\n";
				echo "\n";
				echo '     録画ファイルのあるフォルダ：';
				$TSfile_dir = trim(fgets(STDIN));
				echo "\n";
			}
		}
		// 置換
		$TSfile_dir = str_replace('\\', '/', $TSfile_dir);
		$TSfile_dir = rtrim($TSfile_dir, '/').'/';
	}

	echo '  インストールを開始します。'."\n";
	echo "\n\n";
	sleep(1);

	// フォルダを作る
	if_mkdir('/');
	if_copy ('/config.default.php', true);
	if_copy ('/createcert.bat', true);
	if_copy ('/header.php', true);
	if_copy ('/LICENSE.txt', true);
	if_copy ('/module.php', true);
	if_copy ('/README.md', true);
	if_copy ('/require.php', true);
	if_copy ('/stream.bat', true);
	if_copy ('/stream.php', true);
	if_copy ('/bin', true);
	if_copy ('/cast', true);
	if_copy ('/data', true);
	if_copy ('/docs', true);
	if_copy ('/htdocs', true);

	// 新規インストールのみの処理
	if ($update === false){

		// 設定ファイル
		$tvrp_conf_file = $serverroot.'/config.php';
		$httpd_conf_file = $serverroot.'/bin/Apache/conf/httpd.conf';
		$httpd_default_file = $serverroot.'/bin/Apache/conf/httpd.default.conf';
		$openssl_conf_file = $serverroot.'/bin/Apache/conf/openssl.cnf';
		$openssl_default_file = $serverroot.'/bin/Apache/conf/openssl.default.cnf';
		$opensslext_conf_file = $serverroot.'/bin/Apache/conf/openssl.ext';
		$opensslext_default_file = $serverroot.'/bin/Apache/conf/openssl.default.ext';

		// config.default.php を config.php にコピー
		if (!file_exists($serverroot.'/config.php')) copy($serverroot.'/config.default.php', $serverroot.'/config.php');
		// httpd.default.conf を httpd.conf にコピー
		if (!file_exists($httpd_conf_file)) copy($httpd_default_file, $httpd_conf_file);
		// openssl.default.cnf を openssl.cnf にコピー
		if (!file_exists($openssl_conf_file)) copy($openssl_default_file, $openssl_conf_file);
		// openssl.default.ext を openssl.ext にコピー
		if (!file_exists($opensslext_conf_file)) copy($opensslext_default_file, $opensslext_conf_file);
		
		// TSTask のコピー
		if ($bondriver == 2){
			copy($serverroot.'/bin/TSTask/64bit/BonDriver_TSTask.dll', $serverroot.'/bin/TSTask/BonDriver_TSTask.dll');
			copy($serverroot.'/bin/TSTask/64bit/TSTask.exe', $serverroot.'/bin/TSTask/TSTask.exe');
			copy($serverroot.'/bin/TSTask/64bit/TSTaskCentre.exe', $serverroot.'/bin/TSTask/TSTaskCentre.exe');
		} else {
			copy($serverroot.'/bin/TSTask/32bit/BonDriver_TSTask.dll', $serverroot.'/bin/TSTask/BonDriver_TSTask.dll');
			copy($serverroot.'/bin/TSTask/32bit/TSTask.exe', $serverroot.'/bin/TSTask/TSTask.exe');
			copy($serverroot.'/bin/TSTask/32bit/TSTaskCentre.exe', $serverroot.'/bin/TSTask/TSTaskCentre.exe');
		}

		// 状態設定ファイルを初期化
		$jsonfile = $serverroot.'/data/setting.json';
		$json = array('state' => 'Offline');
		if (!file_exists($jsonfile)) file_put_contents($jsonfile, json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

		// TVRemotePlusの設定ファイル
		$tvrp_conf = file_get_contents($tvrp_conf_file);
		$tvrp_conf = preg_replace('/^\$TSfile_dir =.*/m', '$TSfile_dir = \''.$TSfile_dir.'\';', $tvrp_conf); // 置換
		$tvrp_conf = preg_replace('/^\$http_port =.*/m', '$http_port = '.$port.';', $tvrp_conf); // 置換
		file_put_contents($tvrp_conf_file, $tvrp_conf); // 書き込み

		// Apacheの設定ファイル
		$httpd_conf = file_get_contents($httpd_conf_file);
		// 置換
		$httpd_conf = preg_replace("/Define SRVROOT.*/", 'Define SRVROOT "'.$serverroot.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define SRVIP.*/", 'Define SRVIP "'.$serverip.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define PORT.*/", 'Define PORT "'.$port.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define SSL_PORT.*/", 'Define SSL_PORT "'.$ssl_port.'"', $httpd_conf);
		file_put_contents($httpd_conf_file, $httpd_conf);// 書き込み

		// OpenSSLの設定ファイル
		$openssl_conf = file_get_contents($openssl_conf_file);
		$openssl_conf = preg_replace("/commonName_default		= .*/", 'commonName_default		= '.$serverip.'', $openssl_conf); // 置換
		file_put_contents($openssl_conf_file, $openssl_conf); // 書き込み

		// OpenSSLの拡張設定ファイル
		$opensslext_conf = file_get_contents($opensslext_conf_file);
		$opensslext_conf = preg_replace("/subjectAltName = .*/", 'subjectAltName = IP:'.$serverip.'', $opensslext_conf); // 置換		
		file_put_contents($opensslext_conf_file, $opensslext_conf); // 書き込み


		// HTTPS接続用オレオレ証明書の作成
		echo '  HTTPS 接続用の自己署名証明書を作成します。'."\n";
		echo '  途中、入力を求められる箇所がありますが、全て Enter で飛ばしてください。'."\n";
		echo '  途中でパスワードとかも聞かれますが、飛ばして構いません。'."\n";
		echo '  続行するには何かキーを押してください。'."\n";
		trim(fgets(STDIN));
		echo "\n";

		$cmd =  'cd '.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\ && '.
				'.\openssl.exe genrsa -out ..\conf\server.key 2048 && '.
				'.\openssl.exe req -new -key ..\conf\server.key -out ..\conf\server.csr -config ..\conf\openssl.cnf && '.
				'.\openssl.exe x509 -req -in ..\conf\server.csr -out ..\conf\server.crt -days 3650 -signkey ..\conf\server.key -extfile ..\conf\openssl.ext';

		exec($cmd, $opt1, $return1);
		copy($serverroot.'/bin/Apache/conf/server.crt', $serverroot.'/htdocs/server.crt');
		echo "\n";
		echo "\n";
		if ($return1 == 0) echo '  HTTPS接続用の自己署名証明書を作成しました。'."\n";
		else echo '  HTTPS接続用の自己署名証明書の作成に失敗しました…'."\n";

		// ショートカット作成
		$powershell = '$shell = New-Object -ComObject WScript.Shell; '.
					'$lnk = $shell.CreateShortcut(\"$Home\Desktop\TVRemotePlus - launch.lnk\"); '.
					'$lnk.TargetPath = \"'.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\httpd.exe\"; '.
					'$lnk.WindowStyle = 7; '.
					'$lnk.Save()';
		exec('powershell -Command "'.$powershell.'"', $opt2, $return2);
		echo "\n";
		if ($return2 == 0) echo '  ショートカットを作成しました。'."\n";
		else echo '  ショートカットの作成に失敗しました…'."\n";

	}

	echo "\n";
	echo "\n";
	echo '  インストールが完了しました。'."\n";
	echo "\n";
	sleep(1);

	// 新規インストールのみの処理
	if ($update === false){
		echo '  セットアップはまだ終わっていません。'."\n\n";
		echo '  BonDriver と.ch2 ファイルは '.$serverroot .'/bin/TSTask/BonDriver/ に忘れずに入れてください。'."\n\n";
		echo '  終わったら、デスクトップのショートカットから TVRemotePlus を起動し、その後'."\n";
		echo '  ブラウザから http://'.$serverip.':'.$port.'/ にアクセスします。'."\n";
		echo '  その後、≡ サイドメニュー → 設定 → 環境設定 から必要な箇所を設定してください。'."\n\n";
		echo '  PWA 機能を使用する場合は、予め端末に設定ページからダウンロードできる自己署名証明書を'."\n";
		echo '  インストールした上で、 https://'.$serverip.':'.$ssl_port.'/ にアクセスしてください。'."\n";
		sleep(1);
	}

	echo "\n";
	echo '  終了するには何かキーを押してください。'."\n\n";
	trim(fgets(STDIN));

