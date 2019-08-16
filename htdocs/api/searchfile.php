<?php

	// モジュール読み込み
	require_once ('../../module.php');

	// かなり長くなることがあるので実行時間制限をオフに
	ignore_user_abort(true);
	set_time_limit(0);

	// jsonからデコードして代入
	if (file_exists($infofile)){
		$TSfile = json_decode(file_get_contents($infofile), true);
	} else {
		$TSfile = array();
	}

	// ブッチ要求が来たら適当に返す
	if (isset($_GET['flush'])){

		// Apache環境変数に deflate(gzip) 無効をセット
		apache_setenv('no-gzip', '1');

		// レスポンスをバッファに貯める
		ob_start();

		$json = array(
			'apiname' => 'searchfile',
			'status' => 'flush',
		);

		// レスポンス
		$response = json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		echo $response;

		// ヘッダ
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Length: '.ob_get_length());
		header('Connection: close'); // ブッチする
		
		// 溜めてあった出力を解放しフラッシュする
		ob_end_flush();
		ob_flush();
		flush();

	}

	// リセット要求が来たら適当にリセット
	if (isset($_GET['reset'])){

		// jsonを削除
		@unlink($infofile);
		@unlink($historyfile);

		$json = array(
			'apiname' => 'searchfile',
			'status' => 'reset',
		);

		// レスポンス
		$response = json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		echo $response;

		exit;
	}

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	// 終了時間を計算する関数
	function end_calc($start, $duration){

		// それぞれ切り出す
		$start_hour = mb_substr($start, 0, 2);
		$start_min = mb_substr($start, 3, 2);

		// 十の位が0なら消す
		if (mb_substr($start_hour, 0, 1) == 0) $start_hour = mb_substr($start_hour, 1, 1);
		if (mb_substr($start_min, 0, 1) == 0) $start_min = mb_substr($start_min, 1, 1);

		// 数値に変換
		$start_hour = intval($start_hour);
		$start_min = intval($start_min);
	
		if ($start_hour !== 0){
			// 繰り上がり
			$start = ($start_hour * 60) + $start_min;
		} else {
			// 繰り上がらない
			$start = $start_min;
		}

		// 開始時間と長さを足して終了時間(分)を出す
		$end_ = $start + $duration;
		// 割って繰り上がりさせる
		$end_hour = floor($end_ / 60);
		$end_min = floor($end_ % 60);

		// 0埋めする
		$end = sprintf('%02d', $end_hour).':'.sprintf('%02d', $end_min);
	
		return $end;
	}

	// 時間を計算する関数
	function duration_calc($duration){

		// それぞれ切り出す
		$duration_hour = mb_substr($duration, 0, 2);
		$duration_min = mb_substr($duration, 3, 2);

		// 十の位が0なら消す
		if (mb_substr($duration_hour, 0, 1) == 0) $duration_hour = mb_substr($duration_hour, 1, 1);
		if (mb_substr($duration_min, 0, 1) == 0) $duration_min = mb_substr($duration_min, 1, 1);

		// 数値に変換
		$duration_hour = intval($duration_hour);
		$duration_min = intval($duration_min);

		if ($duration_hour !== 0){
			// 繰り上がり
			$duration = ($duration_hour * 60) + $duration_min;
		} else {
			// 繰り上がらない
			$duration = $duration_min;
		}

		return $duration;
	}

	// ファイルを検索
	$search = array_merge(glob($TSfile_dir.'*.ts'), glob($TSfile_dir.'*\*.ts'));

	foreach ($search as $key => $value) {
		$TSfile['data'][$key]['file'] = $value; // パス含めたファイル名
		$TSfile['data'][$key]['title'] = convertSymbol(str_replace('　', ' ', basename($value, '.ts'))); // 拡張子なしファイル名を暫定でタイトルに
		$TSfile['data'][$key]['update'] = filemtime($value); // ファイルの更新日時(Unix時間)
		$md5 = md5($TSfile['data'][$key]['file']); // ファイル名のmd5

		// サムネイルが存在するなら
		if (file_exists($base_dir.'htdocs/files/thumb/'.$md5.'.jpg')){
			$TSfile['data'][$key]['thumb'] = $md5.'.jpg'; // サムネイル画像のパス(拡張子なしファイル名のmd5)

		} else { // ないならデフォルトに
			$TSfile['data'][$key]['thumb'] = 'thumb_default.jpg';
			// ffmpegでサムネイルを生成
			$cmd = $ffmpeg_path.' -ss 70 -i "'.$value.'" -vframes 1 -f image2 -s 480x270 "'.$base_dir.'htdocs/files/thumb/'.$md5.'.jpg"';
			exec($cmd, $opt, $return);

			// サムネイル生成出来たと判断し代入
			// なかったらデフォルト画像が表示される
			$TSfile['data'][$key]['thumb'] = $md5.'.jpg';
		}

		// 番組情報が存在するなら
		if (file_exists($base_dir.'htdocs/files/info/'.$md5.'.json')){
			// 保存してあるjsonから読み出す
			$fileinfo = json_decode(file_get_contents($base_dir.'htdocs/files/info/'.$md5.'.json'));

			// 出力
			$TSfile['data'][$key]['title'] = convertSymbol(str_replace('　', ' ', $fileinfo[4])); // 取得した番組名の方が正確なので修正
			$TSfile['data'][$key]['data'] = $md5.'.json'; // ファイル情報jsonのパス
			$TSfile['data'][$key]['date'] = $fileinfo[0]; // 録画日付
			$TSfile['data'][$key]['info'] = str_replace('　', ' ', mb_convert_kana($fileinfo[5], 'asv')); // 番組情報
			$TSfile['data'][$key]['channel'] = str_replace('　', ' ', mb_convert_kana($fileinfo[3], 'asv')); //チャンネル名
			$TSfile['data'][$key]['start'] = substr($fileinfo[1], 0, strlen($fileinfo[1])-3); // 番組の開始時刻
			$TSfile['data'][$key]['end'] = end_calc($fileinfo[1], duration_calc($fileinfo[2])); // 番組の終了時刻
			$TSfile['data'][$key]['duration'] = duration_calc($fileinfo[2]); // ファイルの時間を算出
			$TSfile['data'][$key]['start_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']); // 開始時刻のタイムスタンプ
			$TSfile['data'][$key]['end_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']) + (duration_calc($fileinfo[2]) * 60); // 終了時刻のタイムスタンプ

		} else { // ないならNULL
			// 出力
			$TSfile['data'][$key]['data'] = null;
			$TSfile['data'][$key]['date'] = date('Y/m/d', $TSfile['data'][$key]['update']); // 更新日時から推測
			$TSfile['data'][$key]['info'] = '取得できませんでした';
			$TSfile['data'][$key]['channel'] = '取得失敗';
			$TSfile['data'][$key]['start'] = '--:--';
			$TSfile['data'][$key]['end'] = '--:--';
			$TSfile['data'][$key]['duration'] = '--';
			$TSfile['data'][$key]['start_timestamp'] = $TSfile['data'][$key]['update'];
			$TSfile['data'][$key]['end_timestamp'] = $TSfile['data'][$key]['update'] + (30 * 60); // 分からないので取りあえず30分足しとく

			// rplsinfoでファイル情報を取得
			$cmd = $rplsinfo_path.' -C -dtpcbieg -l 10 "'.$value.'"';
			exec($cmd, $opt, $return);

			if ($return == 0){ // 成功したら
				$opt = mb_convert_encoding(implode("\n", $opt), 'UTF-8', 'SJIS'); // 実行結果の配列を連結して一旦文字列に
				// 正規表現でエラーメッセージを置換する
				$opt = preg_replace("/番組情報元ファイル.*?は有効なTS, rplsファイルではありません./", '', $opt);
				$opt = preg_replace("/番組情報元ファイル.*?から有効な番組情報を検出できませんでした./", '', $opt);
				$opt = preg_replace("/番組情報元ファイル.*?を開くのに失敗しました./", '', $opt);
			
				$fileinfo = str_getcsv($opt); // Parseして配列にする
				// ファイルを出力して情報を保存
				file_put_contents($base_dir.'htdocs/files/info/'.$md5.'.json', json_encode($fileinfo, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

				// 出力
				$TSfile['data'][$key]['title'] = convertSymbol(str_replace('　', ' ', $fileinfo[4])); // 取得した番組名の方が正確なので修正
				$TSfile['data'][$key]['data'] = $md5.'.json'; // ファイル情報jsonのパス
				$TSfile['data'][$key]['date'] = $fileinfo[0]; // 録画日付
				$TSfile['data'][$key]['info'] = str_replace('　', ' ', mb_convert_kana($fileinfo[5], 'asv')); // 番組情報
				$TSfile['data'][$key]['channel'] = str_replace('　', ' ', mb_convert_kana($fileinfo[3], 'asv')); //チャンネル名
				$TSfile['data'][$key]['start'] = substr($fileinfo[1], 0, strlen($fileinfo[1])-3); // 番組の開始時刻
				$TSfile['data'][$key]['end'] = end_calc($fileinfo[1], duration_calc($fileinfo[2])); // 番組の終了時刻
				$TSfile['data'][$key]['duration'] = duration_calc($fileinfo[2]); // ファイルの時間を算出
				$TSfile['data'][$key]['start_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']); // 開始時刻のタイムスタンプ
				$TSfile['data'][$key]['end_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']) + (duration_calc($fileinfo[2]) * 60); // 終了時刻のタイムスタンプ
			}
		}
	}

	// JSONにエンコード
	$json = json_encode($TSfile, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

	// ファイルに保存
	file_put_contents($infofile, $json);

	// flush モードでないなら出力
	if (!isset($_GET['flush'])){
		header('content-type: application/json; charset=utf-8');
		echo $json;
	}
