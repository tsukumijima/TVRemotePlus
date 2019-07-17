<?php

	// Shift-JISに
	ini_set('default_charset', 'sjis-win');
	
	// べースフォルダ
	$base_dir = str_replace('\\','/',dirname(__FILE__));

	function dir_copy($dir_name, $new_dir){
		if (!is_dir($dir_name)) {
			copy($dir_name, $new_dir);
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
						copy($dir_name . "/" . $file, $new_dir . "/" . $file);
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
	echo '  TVRemotePlusのセットアップを行うインストーラーです。'."\n";
	echo '  途中でキャンセルする場合は Ctrl + C を押してください。'."\n";
	echo "\n";

	echo '  1. TVRemotePlusをインストールするフォルダを指定します。'."\n";
	echo "\n";
	echo '     フォルダをドラッグ&ドロップするか、ファイルパスを入力してください。'."\n";
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
		$update = true;
		echo '     既に指定されたフォルダにインストールされていると判定しました。'."\n";
		echo '     アップデートモードでインストールします。'."\n";
		echo "\n";

	// 新規インストールの場合はIPとポートを訊く
	} else {
		$update = false;
		echo '  2. TVRemotePlusをインストールするPCの、ローカルIPアドレスを入力してください。'."\n";
		echo "\n";
		echo '     ローカルIPアドレスは、通常 192.168.x.xx のような形式の家の中用のIPアドレスです。'."\n";
		echo '     インストーラーで検知したローカルIPアドレスは '.getHostByName(getHostName()).' です。'."\n";
		echo '     判定が間違っている場合もあります(VPN等で複数の仮想デバイスがある場合など)。'."\n";
		echo '     その場合、メインで利用しているローカルIPアドレスを ipconfig で調べ、入力してください。'."\n";
		echo '     よくわからない場合は、Enterキーを押し、次に進んでください。'."\n";
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
		echo '     通常は、ブラウザのURL欄から http://'.$serverip.':8000 でアクセスできます。'."\n";
		echo '     この 8000 の番号を変えたい場合は、ポート番号を入力してください。'."\n";
		echo '     HTTPS接続時はポート番号が ここで設定した番号 + 100 になります。'."\n";
		echo '     よくわからない場合は、Enterキーを押し、次に進んでください。'."\n";
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
	}

	echo '  インストールを開始します。'."\n";
	echo "\n\n";
	sleep(1);

	// フォルダを作る
	if_mkdir('/');
	if_copy ('/config.default.php');
	if_copy ('/header.php', true);
	if_copy ('/LICENSE.txt', true);
	if_copy ('/README.md', true);
	if_copy ('/stream.php', true);
	if_copy ('/Twitter_Develop.md', true);
	if_copy ('/bin/', true);
	if_copy ('/data/', true);
	if_copy ('/htdocs/', true);

	// 新規インストールのみの処理
	if ($update === false){

		// 設定ファイル
		$httpd_conf_file = $serverroot.'/bin/Apache/conf/httpd.conf';
		$httpd_default_file = $serverroot.'/bin/Apache/conf/httpd.default.conf';
		$openssl_conf_file = $serverroot.'/bin/Apache/conf/openssl.cnf';
		$openssl_default_file = $serverroot.'/bin/Apache/conf/openssl.default.cnf';
		$opensslext_conf_file = $serverroot.'/bin/Apache/conf/openssl.ext';
		$opensslext_default_file = $serverroot.'/bin/Apache/conf/openssl.default.ext';

		// config.default.php を config.php にコピー
		if (file_exists(!$serverroot.'/config.php')) copy($serverroot.'/config.default.php', $serverroot.'/config.php');
		// httpd.default.conf を httpd.conf にコピー
		if (file_exists(!$httpd_conf_file)) copy($httpd_default_file, $httpd_conf_file);
		// openssl.default.cnf を openssl.cnf にコピー
		if (file_exists(!$openssl_conf_file)) copy($openssl_default_file, $openssl_conf_file);
		// openssl.default.ext を openssl.ext にコピー
		if (file_exists(!$opensslext_conf_file)) copy($opensslext_default_file, $opensslext_conf_file);

		// 状態設定ファイルを初期化
		$jsonfile = $serverroot.'/data/setting.json';
		$json = array('state' => 'Offline');
		if (!file_exists($jsonfile)) file_put_contents($jsonfile, json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

		// Apacheの設定ファイル
		$httpd_conf = file_get_contents($httpd_conf_file);

		// 置換
		$httpd_conf = preg_replace("/Define SRVROOT.*/", 'Define SRVROOT "'.$serverroot.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define SRVIP.*/", 'Define SRVIP "'.$serverip.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define PORT.*/", 'Define PORT "'.$port.'"', $httpd_conf);
		$httpd_conf = preg_replace("/Define SSL_PORT.*/", 'Define SSL_PORT "'.$ssl_port.'"', $httpd_conf);

		// 書き込み
		file_put_contents($httpd_conf_file, $httpd_conf);

		// OpenSSLの設定ファイル
		$openssl_conf = file_get_contents($openssl_conf_file);
		
		// 置換
		$openssl_conf = preg_replace("/commonName_default		= .*/", 'commonName_default		= '.$serverip.'', $openssl_conf);

		// 書き込み
		file_put_contents($openssl_conf_file, $openssl_conf);

		// OpenSSLの拡張設定ファイル
		$opensslext_conf = file_get_contents($opensslext_conf_file);

		// 置換
		$opensslext_conf = preg_replace("/subjectAltName = .*/", 'subjectAltName = IP:'.$serverip.'', $opensslext_conf);

		// 書き込み
		file_put_contents($opensslext_conf_file, $opensslext_conf);

		// HTTPS接続用オレオレ証明書の作成
		echo '  HTTPS接続用の自己署名証明書を作成します。'."\n";
		echo '  途中、入力を求められる箇所がありますが、全てEnterで飛ばしてください。'."\n";
		echo '  続行するには何かキーを押してください。'."\n";
		trim(fgets(STDIN));
		echo "\n";

		$cmd =  'cd '.str_replace('/', '\\', $serverroot).'\\bin\\Apache\\bin\\ && '.
				'openssl.exe genrsa -out ..\conf\server.key 2048 && '.
				'openssl.exe req -new -key ..\conf\server.key -out ..\conf\server.csr -config ..\conf\openssl.cnf && '.
				'openssl.exe x509 -req -in ..\conf\server.csr -out ..\conf\server.crt -signkey ..\conf\server.key -extfile ..\conf\openssl.ext';
		echo $cmd."\n";
		exec($cmd);
		copy($serverroot.'/bin/Apache/conf/server.crt', $serverroot.'/htdocs/server.crt');
		echo "\n";
		echo '  HTTPS接続用の自己署名証明書を作成しました。'."\n";

		// ショートカット作成
		$powershell = '$shell = New-Object -ComObject WScript.Shell; '.
					'$lnk = $shell.CreateShortcut(\"$Home\Desktop\TVRemotePlus.lnk\"); '.
					'$lnk.TargetPath = \"'.str_replace('/', '\\', $serverroot).'\bin\Apache\bin\httpd.exe\"; '.
					'$lnk.Save()';
		exec('powershell -Command "'.$powershell.'"');
		echo '  ショートカットを作成しました。'."\n";

	}

	echo "\n";
	echo '  インストールが完了しました。'."\n";
	sleep(1);

	echo '  セットアップはまだ終わっていません。'."\n\n";
	echo '  config.php (設定ファイル)を UTF-8・LF で開けるテキストエディタにて開き、'."\n";
	echo '  変更が必要な箇所を設定し、忘れずに保存してください。'."\n";
	echo '  また、BonDriverは bin/TSTask/BonDriver/ フォルダに忘れずに入れてください。'."\n\n";
	echo '  全て終わったら、デスクトップのショートカットから TVRemotePlus を起動し、その後'."\n";
	echo '  ブラウザから http://'.$serverip.':'.$port.'/ にアクセスし、異常がなければ完了です。'."\n\n";
	echo '  終了するには何かキーを押してください。'."\n\n";
	trim(fgets(STDIN));

