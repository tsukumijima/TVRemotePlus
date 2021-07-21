<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');
	require_once ('../../modules/module.php');

	// ストリーム番号を取得
	$stream = getStreamNumber($_SERVER['REQUEST_URI']);

	// セッションのファイル数を返す関数
	function getActiveCount($path) {
		// ファイル数検索
		$files = glob($path.'/sess_*');
		if ($files === false) {
			return 0;
		}
		$n = 0;
		foreach ($files as $value) {
			$modified = @filemtime($value);
			if ($modified !== false && time() - $modified >= 12) {
				@unlink($value);
			} else {
				$n++;
			}
		}
		// ファイル数を返す
		return $n;
	}

	// 視聴数をおおまかに把握するのが目的なので、本来のセッションよりも簡素な仕組みを使う
	$sess = (string)filter_var($_REQUEST['sess'] ?? null);
	if (!preg_match('/\A_[0-9a-f]{16}\z/', $sess)) {
		$sess = '_'.bin2hex(openssl_random_pseudo_bytes(8));
	}
	if (@file_exists($base_dir.'data/session/sess'.$sess)) {
		// タイムスタンプを更新
		@touch($base_dir.'data/session/sess'.$sess);
	} elseif (getActiveCount($base_dir.'data/session') < 100) {
		// ファイル数が制限未満なら作成
		@touch($base_dir.'data/session/sess'.$sess);
	}

	// 前回応答のハッシュを取得
	$hash = (string)filter_var($_REQUEST['hash'] ?? null);
	$hold = isset($_REQUEST['hold']) && filter_var($_REQUEST['hold'], FILTER_VALIDATE_INT) === 1;

	$standby = null;
	$elapsed_sec = 0;
	for (;;) {

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents_lock_sh($inifile), true);

	// 現在の視聴数を取得する
	$watching = getActiveCount($base_dir.'data/session');

	// ついでにストリーム状態を判定する
	if (isset($ini[$stream]) and ($ini[$stream]['state'] == 'ONAir' or $ini[$stream]['state'] == 'File')){

		if (!((!isset($ini[$stream]['fileext']) or ($ini[$stream]['fileext'] != 'mp4' or $ini[$stream]['fileext'] != 'mkv')) and
		       $ini[$stream]['state'] == 'File' and $ini[$stream]['encoder'] == 'Progressive')){

			// 比較元のm3u8
			if (!isset($standby)) {
				$standby = file_get_contents($silent == 'true' ? $standby_silent_m3u8 : $standby_m3u8);
			}
			// 比較先のm3u8
			$stream_m3u8 = @file_get_contents($segment_folder.'stream'.$stream.'.m3u8');
			// 比較先のm3u8の更新日時
			$modified = @filemtime($segment_folder.'stream'.$stream.'.m3u8');

			// m3u8が30秒経っても更新されない
			if ($standby == $stream_m3u8 and time() - $modified > 30){
				$status = 'failed';
			// 再生始まったけど更新が止まってしまった
			} else if ($ini[$stream]['state'] == 'ONAir' and time() - $modified > 20){
				$status = 'restart';
			// m3u8が更新されていない
			} else if ($standby == $stream_m3u8){
				$status = 'standby';
			// m3u8が更新されている
			} else {
				$status = 'onair';
			}

			$streamtype = 'normal';

		} else {

			$status = 'onair';
			$streamtype = 'progressive';

		}

	} else {
		$status = 'offline';
		$streamtype = 'normal';
	}
	
	if (!isset($ini[$stream]) or $ini[$stream]['state'] === null) $ini[$stream]['state'] = 'Offline';

	$json = array(
		'api' => 'status',
		'state' => $ini[$stream]['state'],
		'status' => $status,
		'watching' => $watching,
		'streamtype' => $streamtype,
		'sess' => $sess,
	);

	$response = json_encode(['ffffffff', $json], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	$hash_new = '_'.md5($response);
	if ($hash_new !== $hash) {
		$response = substr_replace($response, $hash_new, strpos($response, 'ffffffff'), 8);
		break;
	} elseif (!$hold || $elapsed_sec >= 8) {
		// 前回応答と同じなので省略
		$response = json_encode([$hash_new], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		break;
	}
	clearstatcache();
	sleep(1);
	$elapsed_sec++;

	} // for

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo $response;
