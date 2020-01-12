<?php

	// モジュール読み込み
	require_once ('../../require.php');
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
		ob_start('mb_output_handler');

		$json = array(
			'api' => 'listupdate',
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

	// リストリセット
	if (isset($_GET['list_reset'])){

		// jsonを削除
		@unlink($infofile);

		$json = array(
			'api' => 'listupdate',
			'status' => 'list_reset',
		);

		// レスポンス
		$response = json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		echo $response;

		exit;
	}

	// 再生履歴を削除
	if (isset($_GET['history_reset'])){

		// jsonを削除
		@unlink($historyfile);

		$json = array(
			'api' => 'listupdate',
			'status' => 'history_reset',
		);

		// レスポンス
		$response = json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		echo $response;

		exit;
	}

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

	// ファイルを四階層まで検索する
	// MP4・MKVファイルも検索する
	$search = array_merge(glob($TSfile_dir.'/*{.ts,.mts,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*{.ts,.mts,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*/*{.ts,.mts,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*/*/*{.ts,.mts,.m2ts,.mp4,.mkv}', GLOB_BRACE));

	foreach ($search as $key => $value) {

		$TSfile['data'][$key]['file'] = str_replace($TSfile_dir, '', $value); // ルートフォルダからのパスを含めたファイル名
		$TSfile['data'][$key]['pathinfo'] = pathinfo($value); // 拡張子なしファイル名など
		unset($TSfile['data'][$key]['pathinfo']['dirname']); // セキュリティの問題でdirnameは削除
		$TSfile['data'][$key]['title'] = convertSymbol(str_replace('　', ' ', $TSfile['data'][$key]['pathinfo']['filename'])); // 拡張子なしファイル名を暫定でタイトルに
		$TSfile['data'][$key]['title_raw'] = str_replace('　', ' ', $TSfile['data'][$key]['pathinfo']['filename']); // HTML抜き
		$TSfile['data'][$key]['update'] = filemtime($value); // ファイルの更新日時(Unix時間)
		$md5 = md5($value); // ファイル名のmd5

		// サムネイルが存在するなら
		if (file_exists($base_dir.'htdocs/files/thumb/'.$md5.'.jpg')){

			$TSfile['data'][$key]['thumb_state'] = 'generated'; // サムネイル生成フラグ
			$TSfile['data'][$key]['thumb'] = $md5.'.jpg'; // サムネイル画像のパス(拡張子なしファイル名のmd5)

		// 以前サムネイル生成に失敗したなら
		// サムネイルに失敗した＝不正なTSファイルなので、毎回生成させると時間を食う
		} else if (isset($TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['thumb_state']) and 
				   $TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['thumb_state'] == 'failed'){

			$TSfile['data'][$key]['thumb_state'] = 'failed'; // サムネイル生成フラグ
			$TSfile['data'][$key]['thumb'] = 'thumb_default.jpg'; // サムネイル画像のパス

		} else { 

			// ないならデフォルトに
			$TSfile['data'][$key]['thumb'] = 'thumb_default.jpg'; // サムネイル画像のパス

			// ffmpegでサムネイルを生成
			$cmd = $ffmpeg_path.' -y -ss 72 -i "'.$value.'" -vframes 1 -f image2 -s 480x270 "'.$base_dir.'htdocs/files/thumb/'.$md5.'.jpg" 2>&1';
			exec($cmd, $opt_, $return);

			// 生成成功
			if ($return === 0){

				// サムネイル生成フラグ
				$TSfile['data'][$key]['thumb_state'] = 'generated';

			// 生成失敗
			} else {

				// サムネイル生成フラグ
				$TSfile['data'][$key]['thumb_state'] = 'failed';

			}

			// サムネイル画像のパス(拡張子なしファイル名のmd5)
			// 仮になかった場合デフォルト画像が表示される
			$TSfile['data'][$key]['thumb'] = $md5.'.jpg';
		}

		// 番組情報が取得できているなら
		if (isset($TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['info_state']) and 
			$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['info_state'] == 'generated'){

			// 拡張子の情報を一時的に保管
			$extension = $TSfile['data'][$key]['pathinfo']['extension'];
			
			// 前に取得した情報を読み込む
			// MP4・MKVからは番組情報を取得できないので、同じファイル名のTSがあればその番組情報を使う
			$TSfile['data'][$key] = $TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']];

			// ファイルパスがTSのものに上書きされてしまうのでここで戻しておく
			$TSfile['data'][$key]['file'] = str_replace($TSfile_dir, '', $value);

			// 拡張子も.tsとして上書きされてしまうのでこれも戻しておく（ついでに小文字化）
			$TSfile['data'][$key]['pathinfo']['extension'] = strtolower($extension);
			
			// 番組情報取得フラグ
			$TSfile['data'][$key]['info_state'] = 'generated';

		// 番組情報を取得していないなら
		// MP4・MKVには番組情報は含まれていないので除外
		} else if ($TSfile['data'][$key]['pathinfo']['extension'] != 'mp4' and $TSfile['data'][$key]['pathinfo']['extension'] != 'mkv'){

			// rplsinfoでファイル情報を取得
			$cmd = $rplsinfo_path.' -C -dtpcbieg -l 10 "'.$value.'" 2>&1';
			exec($cmd, $opt, $return);

			// 取得成功
			if ($return == 0){

				$opt = mb_convert_encoding(implode("\n", $opt), 'UTF-8', 'SJIS'); // 実行結果の配列を連結して一旦文字列に
				// 正規表現でエラーメッセージを置換する
				$opt = preg_replace("/番組情報元ファイル.*?は有効なTS, rplsファイルではありません./", '', $opt);
				$opt = preg_replace("/番組情報元ファイル.*?から有効な番組情報を検出できませんでした./", '', $opt);
				$opt = preg_replace("/番組情報元ファイル.*?を開くのに失敗しました./", '', $opt);
			
				$fileinfo = str_getcsv($opt); // Parseして配列にする

				// 出力
				$TSfile['data'][$key]['title'] = convertSymbol(str_replace('　', ' ', mb_convert_kana($fileinfo[4], 'asv', 'UTF-8'))); // 取得した番組名の方が正確なので修正
				$TSfile['data'][$key]['title_raw'] = str_replace('　', ' ', mb_convert_kana($fileinfo[4], 'asv', 'UTF-8')); // 取得した番組名の方が正確なので修正
				$TSfile['data'][$key]['date'] = $fileinfo[0]; // 録画日付
				$TSfile['data'][$key]['info_state'] = 'generated'; // 番組情報取得フラグ
				$TSfile['data'][$key]['info'] = str_replace('　', ' ', mb_convert_kana($fileinfo[5], 'asv', 'UTF-8')); // 番組情報
				$TSfile['data'][$key]['channel'] = str_replace('　', ' ', mb_convert_kana($fileinfo[3], 'asv', 'UTF-8')); //チャンネル名
				$TSfile['data'][$key]['start'] = substr($fileinfo[1], 0, strlen($fileinfo[1])-3); // 番組の開始時刻
				$TSfile['data'][$key]['end'] = end_calc($fileinfo[1], duration_calc($fileinfo[2])); // 番組の終了時刻
				$TSfile['data'][$key]['duration'] = duration_calc($fileinfo[2]); // ファイルの時間を算出
				$TSfile['data'][$key]['start_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']); // 開始時刻のタイムスタンプ
				$TSfile['data'][$key]['end_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']) + (duration_calc($fileinfo[2]) * 60); // 終了時刻のタイムスタンプ

				// 結果を保存する
				$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = $TSfile['data'][$key];
				
			// 取得失敗
			} else {

				// 出力
				$TSfile['data'][$key]['date'] = date('Y/m/d', $TSfile['data'][$key]['update']); // 更新日時から推測
				$TSfile['data'][$key]['info_state'] = 'failed'; // 番組情報取得フラグ
				$TSfile['data'][$key]['info'] = '取得できませんでした';
				$TSfile['data'][$key]['channel'] = '取得失敗';
				$TSfile['data'][$key]['start'] = date('H:i', $TSfile['data'][$key]['update'] - (30 * 60));
				$TSfile['data'][$key]['end'] = date('H:i', $TSfile['data'][$key]['update']);
				$TSfile['data'][$key]['duration'] = '30?';
				$TSfile['data'][$key]['start_timestamp'] = $TSfile['data'][$key]['update'] - (30 * 60); // 分からないので取りあえず30分引いとく
				$TSfile['data'][$key]['end_timestamp'] = $TSfile['data'][$key]['update'];

				// 結果を保存する
				$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = $TSfile['data'][$key];

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
