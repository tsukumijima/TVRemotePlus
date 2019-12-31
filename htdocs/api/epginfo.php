<?php

	// モジュール読み込み
	require_once ('../../require.php');
	require_once ('../../module.php');

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
		= initBonChannel($BonDriver_dir);

    // ストリーム番号を取得
	$stream = getStreamNumber($_SERVER['REQUEST_URI']);

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	// 番組情報を取得する関数
	function getEpgInfo($ch, $jkchannels, $chnum, $sid, $onid, $tsid){
		global  $EDCB_http_url;
		
		// 番組表API読み込み
		$epg = simplexml_load_file($EDCB_http_url.'/EnumEventInfo?onair=&onid='.$onid.'&sid='.$sid.'&tsid='.$tsid);

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
			if (isset($epg->items->eventinfo[0]->service_name)){
				$program_name = '放送休止';
			} else {
				$program_name = '番組情報を取得できませんでした';
			}
		}
		if (isset($epg->items->eventinfo[0]->event_text)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$program_info = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[0]->event_text), 'asv'));
		} else {
			if (isset($epg->items->eventinfo[0]->service_name)){
				$program_info = '放送休止';
			} else {
				$program_info = '番組情報を取得できませんでした';
			}
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
			if (isset($epg->items->eventinfo[1]->service_name)){
				$next_program_name = '放送休止';
			} else {
				$next_program_name = '番組情報を取得できませんでした';
			}
		}
		if (isset($epg->items->eventinfo[1]->event_text)){
			//文字列に変換してさらに半角に変換して改行をbrにする
			$next_program_info = str_replace("\n", "<br>\n", mb_convert_kana(strval($epg->items->eventinfo[1]->event_text), 'asv'));
		} else {
			if (isset($epg->items->eventinfo[1]->service_name)){
				$next_program_info = '放送休止';
			} else {
				$next_program_info = '番組情報を取得できませんでした';
			}
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
		$jkch = getJKchannel($ch[$chnum]);
		
		if (!empty($jkchannels)){
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
		}
		// BSでも取得できなかったら空にしておく
		if (!isset($ikioi)){
			$ikioi = ' - ';
		}

		return array(
			'ch' => intval($chnum),
			'tsid' => intval($tsid),
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
	@$jkchannels = simplexml_load_file('http://jk.nicovideo.jp/api/v2_app/getchannels/');
	if (!$jkchannels){
		$jkchannels = array();
	}

	// ついでにストリーム状態を判定する
	if ($ini['state'] == 'ONAir' or $ini['state'] == 'File'){
		$standby = file_get_contents($standby_m3u8);
		$stream = file_get_contents($segment_folder.'stream'.$stream.'.m3u8');
		if ($standby == $stream){
			$status = 'standby';
		} else {
			$status = 'onair';
		}
	} else {
		$status = 'offline';
	}

	$epginfo['info'] = array(
		'state' => $ini['state'],
		'status' => $status,
	);

	// ONAir状態なら
	if ($ini["state"] == "ONAir"){

		// 番組情報を取得
		$epginfo['play'] = getEpgInfo($ch, $jkchannels, $ini['channel'], $sid[$ini['channel']], $onid[$ini['channel']], $tsid[$ini['channel']]);

		// チャンネル名が取得出来なかったら代入
		if ($epginfo['play']['channel'] == 'チャンネル名を取得できませんでした'){
			$epginfo['play']['channel'] = $ch[$ini['channel']];
		}

	} else {

		$epginfo['play'] = array(
			'ch' => 0,
			'tsid' => 0,
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
		$epginfo['onair'][strval($key)] = getEpgInfo($ch, $jkchannels, $key, $value, $onid[strval($key)], $tsid[strval($key)]);
	}

	if (!isset($epginfo['onair'])) $epginfo['onair'] = array();
	
	$epginfo['api'] = 'epginfo';

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($epginfo, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
