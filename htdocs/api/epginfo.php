<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');
	require_once ('../../modules/module.php');

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

	// コンテキストを読み込む
	libxml_set_streams_context($ssl_context);

	// 番組情報を取得する関数
	function getEpgInfo($ch, $jkchannels, $chnum, $sid, $onid, $tsid){
		
		global $EDCB_http_url;

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

		if (!empty($EDCB_http_url)){
		
			// 番組表API読み込み
			$epg = simplexml_load_file($EDCB_http_url.'api/EnumEventInfo?onair=&onid='.$onid.'&sid='.$sid.'&tsid='.$tsid);

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
				$next_starttime = $epg->items->eventinfo[1]->startDate.' '.$epg->items->eventinfo[1]->startTime;
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
			$endtimestamp = $starttimestamp + $duration; // 秒数を足す
			$starttime = date("H:i", $starttimestamp);
			$endtime = date("H:i", $endtimestamp);

			// 次の番組の開始/終了時間の解析
			$next_starttimestamp = strtotime($next_starttime); //タイムスタンプに変換
			$next_endtimestamp = $next_starttimestamp + $next_duration; // 秒数を足す
			$next_starttime = date("H:i", $next_starttimestamp);
			$next_endtime = date("H:i", $next_endtimestamp);

			return array(
				'ch' => intval($chnum),
				'ch_str' => strval($chnum),
				'tsid' => intval($tsid),
				'channel' => $channel,
				'ikioi'=> $ikioi,
				'timestamp' => $starttimestamp, 
				'duration' => $endtimestamp - $starttimestamp, 
				'starttime' => $starttime, 
				'to' => '～', 
				'endtime' => $endtime, 
				'program_name' => decorateMark($program_name),
				'program_info' => decorateMark($program_info),
				'next_starttime' => $next_starttime, 
				'next_endtime' => $next_endtime, 
				'next_program_name' => decorateMark($next_program_name),
				'next_program_info' => decorateMark($next_program_info),
			);

		} else {

			return array(
				'ch' => intval($chnum),
				'ch_str' => strval($chnum),
				'tsid' => intval($tsid),
				'channel' => 'チャンネル名を取得できませんでした',
				'ikioi'=> $ikioi,
				'duration' => '', 
				'starttime' => '00:00', 
				'to' => '～', 
				'endtime' => '00:00', 
				'program_name' => '番組情報を取得できませんでした',
				'program_info' => '番組情報を表示するには、EDCB Material WebUI の API がある URL が設定されている必要があります。<br>'.
								  '左上の ≡ サイドメニュー → 設定 → 環境設定 から設定できます。',
				'next_starttime' => '00:00', 
				'next_endtime' => '00:00', 
				'next_program_name' => '番組情報を取得できませんでした',
				'next_program_info' => '',
			);
		}
	}

	$epginfo['api'] = 'epginfo';

	// 実況勢いを取得してパースしておく
	@$jkchannels = simplexml_load_file('http://jk.nicovideo.jp/api/v2_app/getchannels/');
	if (!$jkchannels){
		$jkchannels = array();
	}

	// 番組情報を取得
	foreach ($sid as $key => $value) {
		$epginfo['onair'][strval($key)] = getEpgInfo($ch, $jkchannels, $key, $value, $onid[strval($key)], $tsid[strval($key)]);
	}

	if (!isset($epginfo['onair'])) $epginfo['onair'] = array();

	// ストリーム状態とストリームの番組情報を取得する
	foreach ($ini as $key => $value) {

		$key = strval($key);

		if ($ini[$key]['state'] == 'ONAir' or $ini[$key]['state'] == 'File'){
			$standby = file_get_contents($standby_m3u8);
			$stream_m3u8 = file_get_contents($segment_folder.'stream'.$key.'.m3u8');
			if ($standby == $stream_m3u8){
				$status = 'standby';
			} else {
				$status = 'onair';
			}
		} else {
			$status = 'offline';
		}
	
		if ($ini[$key]['state'] === null) $ini[$key]['state'] = 'Offline';

		// ONAir状態なら
		if ($ini[$key]['state'] == 'ONAir'){

			// 番組情報を取得
			if (isset($epginfo['onair'][$ini[$key]['channel']])){
				$epginfo['stream'][$key] = $epginfo['onair'][$ini[$key]['channel']];
			// サブチャンネルをオフにした後にサブチャンネルのストリームが残っている場合用
			} else {
				$epginfo['stream'][$key] = array(
					'state' => $ini[$key]['state'],
					'status' => $status,
					'ch' => intval($ini[$key]['channel']),
					'ch_str' => strval($ini[$key]['channel']),
					'tsid' => '',
					'channel' => 'チャンネル名を取得できませんでした',
					'timestamp' => '', 
					'duration' => '', 
					'starttime' => '', 
					'to' => '', 
					'endtime' => '', 
					'program_name' => '番組情報を取得できませんでした',
					'program_info' => 'サブチャンネルの番組情報を表示するには、サブチャンネルが番組表に表示されている必要があります。<br>'.
					                  '右上の︙メニュー →［サブチャンネルを表示］から表示を切り替えられます。',
					'next_starttime' => '', 
					'next_endtime' => '', 
					'next_program_name' => '番組情報を取得できませんでした',
					'next_program_info' => '',
				);
			}

			// ステータス
			$epginfo['stream'][$key]['state'] = $ini[$key]['state'];
			$epginfo['stream'][$key]['status'] = $status;

			// チャンネル名が取得出来なかったら代入
			if (isset($ch[$ini[$key]['channel']]) and $epginfo['stream'][$key]['channel'] == 'チャンネル名を取得できませんでした'){
				$epginfo['stream'][$key]['channel'] = $ch[$ini[$key]['channel']];
			}

		// ファイル再生
		} else if ($ini[$key]['state'] == 'File'){

			$epginfo['stream'][$key] = array(
				'state' => $ini[$key]['state'],
				'status' => $status,
				'ch' => 0,
				'tsid' => 0,
				'channel' => $ini[$key]['filechannel'],
				'time' => $ini[$key]['filetime'],
				'start_timestamp' => $ini[$key]['start_timestamp'], 
				'end_timestamp' => $ini[$key]['end_timestamp'], 
				'program_name' => $ini[$key]['filetitle'],
				'program_info' => $ini[$key]['fileinfo'],
			);

		// オフライン
		} else {

			$epginfo['stream'][$key] = array(
				'state' => $ini[$key]['state'],
				'status' => $status,
				'ch' => 0,
				'ch_str' => '0',
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
	}

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($epginfo, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
