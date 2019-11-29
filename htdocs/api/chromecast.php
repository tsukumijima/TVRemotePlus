<?php

	// モジュール読み込み
	require_once ('../../module.php');
	
	// 設定ファイルのパス
	$castfile = $base_dir.'cast/cast.json';
	$scanfile = $base_dir.'cast/scan.json';

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);
	$cast = json_decode(file_get_contents($castfile), true);

	$json = array(
		'api' => 'chromecast'
	);

	// コマンド確認
	if (isset($_GET['cmd'])){
		$cast['cmd'] = $_GET['cmd'];

		// スタートならChromeCast起動
		if ($cast['cmd'] == 'start' and isset($_GET['ip']) and isset($_GET['port'])){

			if ($ini['state'] == 'File' and $ini['fileext'] != 'ts' and $ini['encoder'] == 'Progressive'){
				$streamurl = 'http://'.$_SERVER['SERVER_NAME'].':'.$http_port.'/api/stream';
				$streamtype = 'video/mp4';
			} else {
				$streamurl = 'http://'.$_SERVER['SERVER_NAME'].':'.$http_port.'/stream/stream.m3u8';
				$streamtype = 'application/vnd.apple.mpegurl';
			}

			$cmd = 'pushd '.str_replace('/', '\\', $base_dir).'bin\Apache\bin\ && start "Chromecast Connect" /min  ..\..\php\php.exe '.$base_dir.'cast/cast.php '.$streamurl.' '.$streamtype.' '.$_GET['ip'].' '.$_GET['port'];
			// echo $cmd."\n";
			win_exec($cmd);
			$cast['cast'] = true;
			$cast['status'] = 'load';
		}

		// ストップ
		if ($cast['cmd'] == 'stop'){
			$cast['cast'] = false;
			$cast['status'] = 'stop';
		}

		// スキャンモード
		if ($cast['cmd'] == 'scan'){

			// Bonjour を再起動しておく (Apache を管理者権限で立ち上げておく必要があります)
			exec('net stop "Bonjour Service" & net start "Bonjour Service"', $opt1, $return1);
			if ($return1 == 0) $bonjour = true;
			else $bonjour = false;

			sleep(1);

			// コマンド実行
			$cmd = 'pushd '.str_replace('/', '\\', $base_dir).'bin\Apache\bin\ && ..\..\php\php.exe '.$base_dir.'cast/cast.php scan';
			// echo $cmd."\n";
			exec($cmd, $opt2, $return2);

			// 結果をデコードして代入
			$scan = json_decode(implode("\n", $opt2), true);

			// 取得できた
			if ($return2 == 0 and !empty($scan)){

				$scanflg = true;

				// スキャン結果からChromeCastだけ抽出
				foreach ($scan as $key => $value) {
					if (preg_match("/googlecast/", $key) and !preg_match("/Google-Home/", $key)){
						
						$scandata[$key] = $value;
						if (preg_match("/Chromecast/", $key)){
							$scandata[$key]['type'] = 'Chromecast';
						} else if (preg_match("/Android-TV/", $key)){
							$scandata[$key]['type'] = 'Android TV';
						} else if (preg_match("/Google-Nest/", $key)){
							$scandata[$key]['type'] = 'Google Nest Hub';
						} else {
							$scandata[$key]['type'] = 'Other device';
						}
					}
				}

				// 取得できなかった時用に結果を保存しておく
				file_put_contents($scanfile, json_encode($scandata, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			}

		}

	} else {
		$cast['cmd'] = '';
	}

	// 取得できなかった・しなかった
	if (!isset($scanflg)){

		$scanflg = false;

		// 保存されてあれば読み込む
		if (file_exists($scanfile)){
			$scandata = json_decode(file_get_contents($scanfile), true);
		} else {
			$scandata = array();
		}
	}

	// 引数確認
	if (isset($_GET['arg'])){
		$cast['arg'] = $_GET['arg'];
	} else {
		$cast['arg'] = '';
	}

	// JSON
	$json['cmd'] = $cast['cmd'];
	if ($cast['cmd'] == 'start' and isset($_GET['host']) and isset($_GET['ip']) and isset($_GET['port'])){
		$json['ip'] = $_GET['ip'];
		$json['port'] = $_GET['port'];
	}
	$json['arg'] = $cast['arg'];
	if ($cast['cmd'] == 'scan'){
		$json['bonjour'] = $bonjour;
	}
	if (isset($cast['cast'])){
		$json['cast'] = $cast['cast'];
	} else {
		$json['cast'] = false;
	}
	if (isset($cast['status'])){
		$json['status'] = $cast['status'];
	} else {
		$json['status'] = 'stop';
	}
	$json['scan'] = $scanflg;
	$json['scandata'] = $scandata;

	// スタート時のみ Play になるまで待つ
	$i = 0;

	if ($cast['cmd'] == 'start' and isset($_GET['ip']) and isset($_GET['port'])){
		while (true){
			
			// 0.5秒ごとに読み込み
			usleep(500000);
			$cast_ = json_decode(file_get_contents($castfile), true);
			// 再生が開始されたらbreak
			if ($cast_['status'] == 'play'){
				$cast['status'] = 'play';
				$json['status'] = 'play';
				break;
			}

			// 30秒待っても起動しない場合は終了してbreak
			if ($i > 60){
				$cast['cmd'] = 'stop';
				$cast['cast'] = false;
				$cast['status'] = 'failed';
				$json['status'] = 'failed';
				break;
			}
			$i++;
		}
	}

	// 出力
	header('content-type: application/json; charset=utf-8');
	file_put_contents($castfile, json_encode($cast, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
	echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

