<?php

	// 設定読み込み
	require_once ('../../config.php');

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);
	
	ini_set('display_errors', 0);

	// ニコニコ実況IDをチャンネル名から取得する関数
	function getJKchannel($channel){
		global $base_dir;

		// ch_sid.txtを改行ごとに区切って配列にする
		$ch_sid = explode("\n", file_get_contents($base_dir.'data/ch_sid.txt'));

		// 配列を回す
		foreach ($ch_sid as $key => $value) {

			// Tabで区切る
			$ch_sid[$key] = explode('	', $value);

			// 抽出したチャンネル名
			$jkch = mb_convert_kana($ch_sid[$key][4], 'asv');

			// 正規表現パターン
			mb_regex_encoding("UTF-8");
			$match = "{".$jkch."[0-9]}u";
			$match2 = "{".preg_quote(mb_substr($jkch, 0, 5))."[0-9]".preg_quote(mb_substr($jkch, 5, 3))."}u"; // NHK総合用パターン
			$match3 = "{".preg_quote(mb_substr($jkch, 0, 6))."[0-9]".preg_quote(mb_substr($jkch, 6, 3))."}u"; // NHKEテレ用パターン

			// チャンネル名が一致したら
			if ($channel == $jkch or preg_match($match, $channel) or preg_match($match2, $channel) or preg_match($match3, $channel)){
			//if ($channel == $jkch){
				// 実況IDを返す
				return $ch_sid[$key][0];
			}
		}
	}

	// 番組情報を取得する関数
	function getEpgguide($ch_, $sid){
		global $ini,$ch,$EDCB_http_url,$jkchannels;
		
		// 番組表API読み込み
		$epg = simplexml_load_file($EDCB_http_url.'EnumEventInfo?onair=&sid='.$sid);

		// チャンネル名
		if (isset($epg->items->eventinfo[0]->service_name)){
			$channel = mb_convert_kana(strval($epg->items->eventinfo[0]->service_name), 'asv');
		} else {
			$channel = 'チャンネル名を取得できませんでした';
		}

		// 現在の番組
		if (isset($epg->items->eventinfo[0]->startTime)){
			$starttime = $epg->items->eventinfo[0]->startDate.' '.$epg->items->eventinfo[0]->startTime;
		} else {
			$starttime = date('Y/m/d').'00:00:00';
		}
		if (isset($epg->items->eventinfo[0]->duration)){
			$duration = $epg->items->eventinfo[0]->duration;
		} else {
			$duration = '0000';
		}
		if (isset($epg->items->eventinfo[0]->event_name)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$program_name = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[0]->event_name), 'asv')); 
		} else {
			$program_name = '番組情報を取得できませんでした';
		}
		if (isset($epg->items->eventinfo[0]->event_text)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$program_info = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[0]->event_text), 'asv'));
		} else {
			$program_info = '番組情報を取得できませんでした';
		}

		// 次の番組
		if (isset($epg->items->eventinfo[1]->startTime)){
			$next_starttime = $epg->items->eventinfo[0]->startDate.' '.$epg->items->eventinfo[1]->startTime;
		} else {
			$next_starttime = date('Y/m/d').'00:00:00';
		}
		if (isset($epg->items->eventinfo[1]->duration)){
			$next_duration = $epg->items->eventinfo[1]->duration;
		} else {
			$next_duration = '0000';
		}
		if (isset($epg->items->eventinfo[1]->event_name)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$next_program_name = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[1]->event_name), 'asv')); 
		} else {
			$next_program_name = '番組情報を取得できませんでした';
		}
		if (isset($epg->items->eventinfo[1]->event_text)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$next_program_info = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[1]->event_text), 'asv'));
		} else {
			$next_program_info = '番組情報を取得できませんでした';
		}

		// 開始/終了時間の解析
		$starttimestamp = strtotime($starttime); //タイムスタンプに変換
		$next_starttimestamp = strtotime($next_starttime); //タイムスタンプに変換
		$endtimestamp = $starttimestamp + $duration; // 秒数を足す
		$next_endtimestamp = $next_starttimestamp + $duration; // 秒数を足す
		$starttime = date("H:i", $starttimestamp);
		$next_starttime = date("H:i", $next_starttimestamp);
		$endtime = date("H:i", $endtimestamp);
		$next_endtime = date("H:i", $next_endtimestamp);

		// 実況IDを取得する
		$jkch = getJKchannel($ch[$ch_]);
		
		// 実況勢いを先に取得しておいたデータから見つけて代入
		foreach ($jkchannels->channel as $i => $value) {
			if (strval($value->id) == $jkch){ // 地デジのチャンネル番号が一致したら
				$ikioi = intval($value->thread->force); // 勢いを代入
			}
		}
		// 地デジで取得できなかったら
		if (!isset($ikioi)){
			foreach ($jkchannels->bs_channel as $i => $value) {
				if (strval($value->id) == $jkch){ // BSのチャンネル番号が一致したら
					$ikioi = intval($value->thread->force); // 勢いを代入
				}
			}
		}
		// BSでも取得できなかったら空にしておく
		if (!isset($ikioi)){
			$ikioi = ' - ';
		}

		return array(
			'ch' => $ch_,
			'channel' => $channel,
			'ikioi'=> $ikioi,
			'timestamp' => $starttimestamp, 
			'duration' => $endtimestamp - $starttimestamp, 
			'starttime' => $starttime, 
			'to' => '～', 
			'endtime' => $endtime, 
			'program_name' => convertSymbol($program_name),
			'program_info' => convertSymbol($program_info),
			'next_starttime' => $next_starttime, 
			'next_endtime' => $next_endtime, 
			'next_program_name' => convertSymbol($next_program_name),
			'next_program_info' => convertSymbol($next_program_info),
		);
	}

	// 実況勢いを取得してパースしておく
	$jkchannels = simplexml_load_file('http://jk.nicovideo.jp/api/v2_app/getchannels/');

	// ついでにストリーム状態を判定する
	if ($ini["state"] == "ONAir" or $ini["state"] == "File"){
		$standby = file_get_contents($standby_m3u8);
		$stream = file_get_contents($segment_folder.'stream.m3u8');
		if ($standby == $stream){
			$status = 'standby';
		} else {
			$status = 'onair';
		}
	} else {
		$status = 'offline';
	}

	$epgguide['info'] = array(
		'state' => $ini['state'],
		'status' => $status,
	);

	// ONAir状態なら
	if ($ini["state"] == "ONAir"){

		// 番組情報を取得
		$epgguide['play'] = getEpgguide($ini['channel'], $sid[$ini['channel']]);

		// チャンネル名が取得出来なかったら代入
		if ($epgguide['play']['channel'] == 'チャンネル名を取得できませんでした'){
			$epgguide['play']['channel'] = $ch[$ini['channel']];
		}

	} else {

		$epgguide['play'] = array(
			'ch' => 0,
			'channel' => '',
			'timestamp' => '', 
			'duration' => '', 
			'starttime' => '', 
			'to' => '', 
			'endtime' => '', 
			'program_name' => '配信休止中…',
			'program_info' => '',
			'next_starttime' => '', 
			'next_endtime' => '', 
			'next_program_name' => '',
			'next_program_info' => '',
		);

	}

	foreach ($sid as $key => $value) {
		// 番組情報を取得
		$epgguide['onair'][strval($key)] = getEpgguide($key, $value);
	}

	if (!isset($epgguide['onair'])) $epgguide['onair'] = array();
	
	$epgguide['apiname'] = 'epgguide';

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($epgguide, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
