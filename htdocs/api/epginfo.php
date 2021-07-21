<?php

	// カレントディレクトリを modules/ 以下に変更（こうしないと読み込んでくれない）
	chdir('../../modules/');

	// モジュール読み込み
	require_once ('classloader.php');
	require_once ('require.php');
	require_once ('module.php');

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
		= initBonChannel($BonDriver_dir);

	// 前回応答のハッシュを取得
	$hash = (string)filter_var($_REQUEST['hash'] ?? null);

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents_lock_sh($inifile), true);

	// コンテキストを読み込む
	libxml_set_streams_context($ssl_context);

	// 番組情報を取得する関数
	function getEpgInfo($ch, $chnum, $sid, $onid, $tsid){
		
		global $EDCB_http_url, $nicologin_mail, $nicologin_password;
		
		// ------------- 実況勢い -------------

		// モデルを初期化
		$instance = new Jikkyo($nicologin_mail, $nicologin_password);
		
		// 実況 ID を取得
		$nicojikkyo_id = $instance->getNicoJikkyoID($ch[$chnum]);
    
		// 実況 ID が存在する
		if ($nicojikkyo_id !== null) {

			// 実況勢いを取得
			$ikioi = $instance->getNicoJikkyoIkioi($nicojikkyo_id);

		} else {

			// 実況勢いを取得できなかった
			$ikioi = '-';
		}
		
		// -----------------------------------

		if (!empty($EDCB_http_url)){
		
			// 番組表 API
			@$epg = simplexml_load_file($EDCB_http_url.'api/EnumEventInfo?onair=&onid='.$onid.'&sid='.$sid.'&tsid='.$tsid);

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

	// 番組情報を取得
	foreach ($sid as $key => $value) {
		$epginfo['onair'][strval($key)] = getEpgInfo($ch, $key, $value, $onid[strval($key)], $tsid[strval($key)]);
	}

	if (!isset($epginfo['onair'])) $epginfo['onair'] = array();

	// ストリーム状態とストリームの番組情報を取得する
	foreach ($ini as $key => $value) {

		$key = strval($key);

		if ($ini[$key]['state'] == 'ONAir' or $ini[$key]['state'] == 'File'){
			$standby = file_get_contents($silent == 'true' ? $standby_silent_m3u8 : $standby_m3u8);
			$stream_m3u8 = @file_get_contents($segment_folder.'stream'.$key.'.m3u8');
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

	$response = json_encode(['ffffffff', $epginfo], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	$hash_new = '_'.md5($response);
	if ($hash_new !== $hash) {
		$response = substr_replace($response, $hash_new, strpos($response, 'ffffffff'), 8);
	} else {
		// 前回応答と同じなので省略
		$response = json_encode([$hash_new], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo $response;
