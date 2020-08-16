<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');
	require_once ('../../modules/module.php');

	// かなり長くなることがあるので実行時間制限をオフに
	ignore_user_abort(true);
	set_time_limit(0);

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
	if (file_exists($commentfile)){
		$comment_ini = json_decode(file_get_contents($commentfile), true);
	} else {
		$comment_ini = array();
	}

	// ニコニコ実況のgetflv API
	$basegetflv = 'http://jk.nicovideo.jp/api/v2/getflv?v=';

	// ニコニコにログインしてその状態でアクセスする関数
	// 第二引数をtrueにするとログイン処理します
	function nicologin($url, $loginflg){

		global $cookiefile, $nicologin_mail, $nicologin_password;

		if (!file_exists($cookiefile) or $loginflg == true){ //ログインしてない or ログインを求められたならログインする

			// ログイン先
			$loginurl = 'https://account.nicovideo.jp/api/v1/login';

			// 送信するデータ
			$data = array(
				'mail' => $nicologin_mail, // メールアドレス
				'password' => $nicologin_password, // パスワード
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $loginurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false); // これがないとhttpsで接続できない

			// COOKIEをファイル保存する
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);

			$res = curl_exec($ch);

			curl_close($ch);
		}

		// Cookie保持状態でアクセスする			
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				
		// COOKIEのファイルからデータを送信する
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
				
		$res = curl_exec($ch);
				
		curl_close($ch);

		return $res;
	}

	// 実況スレッドを解析する関数
	function getJKthread($jkthread_xml){

		// とりあえずthreadは全部格納する
		// 0で成功、それ以外は失敗
		if(isset($jkthread_xml->thread['resultcode'])) $jkthread_info['resultcode'] = intval($jkthread_xml->thread['resultcode']);
		// スレッドID
		if(isset($jkthread_xml->thread['thread'])) $jkthread_info['thread'] = intval($jkthread_xml->thread['thread']);
		// スレッド開始からのコメント数
		if(isset($jkthread_xml->thread['last_res'])) $jkthread_info['last_res'] = intval($jkthread_xml->thread['last_res']);
		// コメント投稿するため？のチケット
		if(isset($jkthread_xml->thread['ticket'])) $jkthread_info['ticket'] = strval($jkthread_xml->thread['ticket']);
		// 取得した現在のサーバー時間(UNIX時間)
		if(isset($jkthread_xml->thread['server_time'])) $jkthread_info['server_time'] = intval($jkthread_xml->thread['server_time']);

		// XMLを解析してAPIに格納
		if (isset($jkthread_xml->chat[0])){ //コメントが存在するなら
		
			//ここを配列の数だけ繰り返す
			for ($i = 0; $i < count($jkthread_xml->chat); $i++){
				// chatを格納
				// スレッドID
				if (isset($jkthread_xml->chat[$i]['thread'])) $jkthread[$i]['thread'] = intval($jkthread_xml->chat[$i]['thread']);
				// スレッド開始からの連番コメント番号
				if (isset($jkthread_xml->chat[$i]['no'])) $jkthread[$i]['no'] = intval($jkthread_xml->chat[$i]['no']);
				// コメントのスレッド開始からの位置 1vpos＝10ミリ秒 100vposで１秒
				if (isset($jkthread_xml->chat[$i]['vpos'])) $jkthread[$i]['vpos'] = intval($jkthread_xml->chat[$i]['vpos']);
				// コメント投稿時間のUNIX時間
				if (isset($jkthread_xml->chat[$i]['date'])) $jkthread[$i]['date'] = intval($jkthread_xml->chat[$i]['date']);
				// コメント投稿時間の1秒以下の時間
				if (isset($jkthread_xml->chat[$i]['date_usec'])) $jkthread[$i]['date_usec'] = intval($jkthread_xml->chat[$i]['date_usec']);
				// コメントのコマンド ない時もある
				if (isset($jkthread_xml->chat[$i]['mail'])) $jkthread[$i]['mail'] = strval($jkthread_xml->chat[$i]['mail']);
				// ユーザーID
				if (isset($jkthread_xml->chat[$i]['user_id'])) $jkthread[$i]['user_id'] = strval($jkthread_xml->chat[$i]['user_id']);
				// 投稿者がプレミアム会員であれば1(一般会員はない)
				if (isset($jkthread_xml->chat[$i]['premium'])) $jkthread[$i]['premium'] = intval($jkthread_xml->chat[$i]['premium']);
				// 匿名コメントなら1
				if (isset($jkthread_xml->chat[$i]['anonymity'])) $jkthread[$i]['anonymity'] = intval($jkthread_xml->chat[$i]['anonymity']);
				// コメント本文
				if (isset($jkthread_xml->chat[$i])) $jkthread[$i]['content'] = strval($jkthread_xml->chat[$i]);
			}

		} else {
			$jkthread = array('');
		}

		return @array($jkthread, $jkthread_info);
	}

	// 位置を解析して数値にする関数
	function getPosition($option){

		// 上
		if (strpos($option, 'ue') !== false){
			$position = 1;
		// 下
		} else if (strpos($option, 'shita') !== false){
			$position = 2;
		// スクロール
		} else {
			$position = 0;
		}

		return $position;
	}

	// 色を16進数カラーコードにする関数
	function getColor($option){

		if (strpos($option, 'red') !== false){
			$color = '#E54256';
		} else if (strpos($option, 'pink') !== false){
			$color = '#FF8080';
		} else if (strpos($option, 'orange') !== false){
			$color = '#FFC000';
		} else if (strpos($option, 'yellow') !== false){
			$color = '#FFE133';
		} else if (strpos($option, 'green') !== false){
			$color = '#64DD17';
		} else if (strpos($option, 'cyan') !== false){
			$color = '#39CCFF';
		} else if (strpos($option, 'blue') !== false){
			$color = '#0000FF';
		} else if (strpos($option, 'purple') !== false){
			$color = '#D500F9';
		} else if (strpos($option, 'black') !== false){
			$color = '#000000';
		} else if (strpos($option, 'white2') !== false){
			$color = '#CCCC99';
		} else if (strpos($option, 'niconicowhite') !== false){
			$color = '#CCCC99';
		} else if (strpos($option, 'red2') !== false){
			$color = '#CC0033';
		} else if (strpos($option, 'truered') !== false){
			$color = '#CC0033';
		} else if (strpos($option, 'pink2') !== false){
			$color = '#FF33CC';
		} else if (strpos($option, 'orange2') !== false){
			$color = '#FF6600';
		} else if (strpos($option, 'passionorange') !== false){
			$color = '#FF6600';
		} else if (strpos($option, 'yellow2') !== false){
			$color = '#999900';
		} else if (strpos($option, 'madyellow') !== false){
			$color = '#999900';
		} else if (strpos($option, 'green2') !== false){
			$color = '#00CC66';
		} else if (strpos($option, 'elementalgreen') !== false){
			$color = '#00CC66';
		} else if (strpos($option, 'cyan2') !== false){
			$color = '#00CCCC';
		} else if (strpos($option, 'blue2') !== false){
			$color = '#3399FF';
		} else if (strpos($option, 'marineblue') !== false){
			$color = '#3399FF';
		} else if (strpos($option, 'purple2') !== false){
			$color = '#6633CC';
		} else if (strpos($option, 'nobleviolet') !== false){
			$color = '#6633CC';
		} else if (strpos($option, 'black2') !== false){
			$color = '#666666';
		} else {
			$color = '#FFFFFF';
		}

		return $color;
	}

	// コメントの取得
	if ($_SERVER['REQUEST_METHOD'] == 'GET' and $ini[$stream]['state'] == 'ONAir' and
	    intval($ini[$stream]['channel']) !== 0 and !isset($_GET['id'])){ // パラメータ確認 (jk0もはじく)

		// 実況IDを取得
		if (isset($ch[$ini[$stream]['channel']])){
			$channel = getJKchannel($ch[$ini[$stream]['channel']]);
		} else if ($ch[intval($ini[$stream]['channel']).'_1']){
			$channel = getJKchannel($ch[intval($ini[$stream]['channel']).'_1']);
		} else {
			$channel = -2;
		}
		$getflv = nicologin($basegetflv.'jk'.$channel, false); // getflvを叩く

		// 取得した結果(クエリ)をParseして配列に格納する
		parse_str($getflv, $getflv_param);

		// 実況勢いを取得する
		@$jkchannels = simplexml_load_file('http://jk.nicovideo.jp/api/v2_app/getchannels/');

		if ($jkchannels and !empty($channel)){

			// 実況勢いを先に取得しておいたデータから見つけて代入
			foreach ($jkchannels->channel as $i => $value) {
				if (strval($value->id) == $channel){ // 地デジのチャンネル番号が一致したら
					$ikioi = intval($value->thread->force); // 勢いを代入
				}
			}
			// 地デジで取得できなかったら
			if (!isset($ikioi)){
				foreach ($jkchannels->bs_channel as $i => $value) {
					if (strval($value->id) == $channel){ // BSのチャンネル番号が一致したら
						$ikioi = intval($value->thread->force); // 勢いを代入
					}
				}
			}
			// BSでも取得できなかったら空にしておく
			if (!isset($ikioi)){
				$ikioi = '';
			}

			// チャンネルが存在する場合のみ
			if (isset($channel) and $channel !== -1 and $channel !== -2){

				if (!isset($_GET['res'])){ //resパラメータがないなら

					// コメントサーバのresを取得するURL
					$jkthread_res_URL = 'http://'.$getflv_param['ms'].':'.$getflv_param['http_port'].'/api/thread?version=20061206&thread='.$getflv_param['thread_id'];

					$jkthread_xml = simplexml_load_file($jkthread_res_URL);

					// APIを解析
					list($jkthread_res, $jkthread_res_info) = getJKthread($jkthread_xml);

					if (isset($jkthread_res_info['last_res'])){ //last_resがあれば代入
						$last_res = $jkthread_res_info['last_res'];
					} else { //なかったら0にしとく
						$last_res = 0;
					}

				} else { //resパラメータがあるなら
					$last_res = intval($_GET['res']); //そのまま代入
				}

				$last_res_request = $last_res + 1; //リクエストする時にlast_resがそのままだとコメが重複するので+1する
				
				// コメントサーバから実況を取得するURL
				$jkthread_url = 'http://'.$getflv_param['ms'].':'.$getflv_param['http_port'].
								'/api/thread?version=20061206&thread='.$getflv_param['thread_id'].'&res_from='.$last_res_request;

				// XMLでAPIを叩く
				$jkthread_xml = simplexml_load_file($jkthread_url);

				// APIを解析
				list($jkthread, $jkthread_info) = getJKthread($jkthread_xml);

				// jkthreadから取得したres
				if (isset($jkthread_info['last_res'])){
					$res = intval($jkthread_info['last_res']);
				} else {
					$res = 0;
				}

				// まずresのパラメータが存在していて
				// かつresが空でなくて
				// かつ受け取ったresがAPIのresと同じだったら(新しいコメがなかったら)
				if (isset($_GET['res']) and !$_GET['res'] == '' and $res != $last_res){

					// jkthread をDPlayer用 danmaku 形式に変換する
					for ($i = 0; $i < count($jkthread); $i++) { 

						if (isset($jkthread[$i]) and !empty($jkthread[$i])) {

							// 自分のコメントを表示しない
							// commentとコメント内容が同じ & フラグが立っていない場合
							if (isset($comment_ini['comment'])){ // 空になってる場合があるので分岐
								// 自分のコメントだったら
								if (($jkthread[$i]['content'] == $comment_ini['comment']) and ($comment_ini['comment_readed'] == false)){
									$jkthread[$i]['content'] = ''; // 空にする
									$comment_ini['comment_readed'] = true; //フラグをtrueに
								}
							} else { // iniが空だったら再設定しておく
								$comment_ini['comment'] = ' ';
								$comment_ini['comment_readed'] = false;
							}

							// iniファイル書き込み
							file_put_contents($commentfile, json_encode($comment_ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

							// オプションを解析する
							if (isset($jkthread[$i]['mail'])){ //空でないなら
								$option = str_replace('184', '', $jkthread[$i]['mail']);
							} else {
								$option = '';
							}

							// 位置を取得
							$position = getPosition($option);
							
							// 色を取得
							$color = getColor($option);

							$danmaku[$i] = array(
								'0.'.strval($jkthread[$i]['vpos']), // 秒数(ミリ秒足す)
								$position, // 場所(上・スクロール・下)をインポート
								$color, // 色をインポート
								$jkthread[$i]['user_id'], // ユーザーIDをインポート
								$jkthread[$i]['content'], // コメントをインポート
								$jkthread[$i]['no'], // コメ番をインポート
							);

						} else {
							$danmaku[$i] = null; // コメントを取得できなかった
						}
					}

				} else { //そうでなかったらdanmakuをnullにする
					$danmaku[0] = null;
				}

				// 出力JSON
				$json = array(
					'api' => 'jikkyo',
					'type' => 'load',
					'ikioi' => $ikioi,
					'channel' => 'jk'.$channel,
					'res' => $last_res,
					'last_res' => $res,
					//'jkthread' => $jkthread,
					//'jkthread_url'=> $jkthread_url,
					'code' => 0,
					'version' => 3,
					'data' => $danmaku,
				);

			} else {

				// 出力JSON
				$json = array(
					'api' => 'jikkyo',
					'type' => 'load',
					'ikioi' => $ikioi,
					'channel' => 'jk'.$channel,
					'res' => 0,
					'last_res' => 0,
					'code' => 0,
					'version' => 3,
					'data' => null,
				);
			}

		// エラー発生時
		} else {

			// HTTPステータスコードを判定
			$context = stream_context_create(array(
				'http' => array('ignore_errors' => true)
			));
			$response = file_get_contents('http://jk.nicovideo.jp/api/v2_app/getchannels/', false, $context);

			if (isset($http_response_header[0])){
				if (strpos($http_response_header[0], '500')){
					$error = 'ニコニコ実況に問題が発生しています';
				} else if (strpos($http_response_header[0], '502')){
					$error = 'ニコニコ実況が落ちている可能性があります';
				} else if (strpos($http_response_header[0], '503')){
					$error = 'メンテナンス中です';
				} else if (empty($channel)){
					$error = '-';
				} else {
					$error = 'ニコニコ実況に問題が発生しています';
				}
			} else {
				$error = '不明なエラーが発生しています';
			}

			// 出力JSON
			$json = array(
				'api' => 'jikkyo',
				'type' => 'load',
				'ikioi' => $error,
				'channel' => 'jk'.$channel,
				'error' => $error,
				'code' => 0,
				'version' => 3
			);
		}

	// コメントの送信
	} else if ($_SERVER['REQUEST_METHOD'] == 'POST' and $ini[$stream]['state'] == 'ONAir'){ //POSTでアクセスがあった&生放送なら

		// コメント(JSON形式)をPOSTで受信
		$comment = json_decode(file_get_contents('php://input'), true);

		// コメントが前のコメントと同じでない & ニコニコのログイン情報がセットされてるなら
		if ($comment['text'] !== $comment_ini['comment'] and !empty($nicologin_mail) and !empty($nicologin_password)){

			// commentをアップデート
			$comment_ini['comment'] = $comment['text'];
			$comment_ini['comment_readed'] = false; //フラグをfalseに

			// iniファイル書き込み
			file_put_contents($commentfile, json_encode($comment_ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			// 実況IDを取得
			if (isset($ch[$ini[$stream]['channel']])){
				$channel = getJKchannel($ch[$ini[$stream]['channel']]);
			} else if ($ch[intval($ini[$stream]['channel']).'_1']){
				$channel = getJKchannel($ch[intval($ini[$stream]['channel']).'_1']);
			} else {
				$channel = -2;
			}
			$getflv = nicologin($basegetflv.'jk'.$channel, false); // getflvを叩く

			// 取得した結果(クエリ)をParseして配列に格納する
			parse_str($getflv, $getflv_param);

			// 認証されていない(Cookieの期限切れなど)場合
			if (!isset($getflv_param['user_id'])){
				// ログイン認証させてもう一度getflvを叩く
				$getflv = nicologin($basegetflv.'jk'.$channel, true);		
				// もう一度取得した結果(クエリ)をParseして配列に格納する
				parse_str($getflv, $getflv_param);
				// もう一度取得したらログインできた
				if (isset($getflv_param['user_id'])){
					$login = true;
				} else {
					$login = false;
				}
			} else if (isset($getflv_param['user_id'])){
				$login = true;
			}

			// ログインが確実に出来ている場合のみ
			if ($login){

				// コメントサーバの情報を取得するURL
				$jkthread_url = 'http://'.$getflv_param['ms'].':'.$getflv_param['http_port'].'/api/thread?version=20061206&thread='.$getflv_param['thread_id'];
			
				// XMLでAPIを叩く
				$jkthread_xml = simplexml_load_file($jkthread_url);

				// APIを解析
				list($jkthread, $jkthread_info) = getJKthread($jkthread_xml);

				// vpos = 現在の時間 - 放送開始時間
				$vpos = time() - $jkthread_info['thread'];

				// postkeyを取得
				$postkey_URL = 'http://jk.nicovideo.jp/api/getpostkey?thread='.$jkthread_info['thread'];
				$postkey = str_replace('postkey=', '', nicologin($postkey_URL, false)); //ログインクッキーを渡す

				// パラメータ解析
				// 色を解析
				// 何故か謎の数字になるため取りあえずで全て決め打ち
				if (strpos($comment['color'], strval(16777215)) !== false){ // 白(#fff)
					$color = '';
				} else if (strpos($comment['color'], strval(15024726)) !== false){ // 赤(#e54256)
					$color = 'red';
				} else if (strpos($comment['color'], strval(16769331)) !== false){ // 黄色(#ffe133)
					$color = 'yellow';
				} else if (strpos($comment['color'], strval(6610199)) !== false){ // 緑(#64DD17)
					$color = 'green';
				} else if (strpos($comment['color'], strval(3788031)) !== false){ // 水色(#39ffff)
					$color = 'cyan';
				} else if (strpos($comment['color'], strval(13959417)) !== false){ // 紫(#D500F9)
					$color = 'purple';
				}

				// 位置を解析
				if ($comment['type'] == 0){ //スクロール
					$position = '';
				} else if ($comment['type'] == 1){ //上
					$position = 'ue';
				} else if ($comment['type'] == 2){ //下
					$position = 'shita';
				}

				// オプションを作る
				$option = $color.' '.$position.' 184';

				// SocketでSendするXML
				$send = '<chat thread="'.$jkthread_info['thread'].'" ticket="'.$jkthread_info['ticket'].'" vpos="'.$vpos.'" postkey="'.$postkey.'" mail="'.$option.'" user_id="'.$getflv_param['user_id'].'" premium="0" staff="0">'.$comment['text'].'</chat>'."\0"; //NULL文字を入れる

				// TCP Socketで接続する
				// Socketを作成する
				$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
				// 接続
				$result = socket_connect($socket, $getflv_param['ms'], $getflv_param['ms_port']);

				// 送信
				socket_write($socket, $send, strlen($send));

				// 受信
				$out = socket_read($socket, 2048, PHP_BINARY_READ);
				//echo mb_convert_encoding(socket_strerror(socket_last_error($socket)), 'UTF-8', 'sjis-win'); // エラー把握

				// Socketを閉じる
				socket_close($socket);

				// コード
				$code = 0;

			} else { // ログイン失敗

				$color = '';
				$code = 200;
			}

		} else { // エラー処理

			$color = '';
			$code = 100;
		}

		// 出力JSON
		$json = array(
			'api' => 'jikkyo',
			'type' => 'send',
			'channel' => 'jk'.$ini[$stream]['channel'],
			'color' => $color,
			'text' => $comment['text'],
			'code' => $code,
			'version' => 3
		);

	// 過去ログの取得
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET' and $ini[$stream]['state'] == 'File'){ //ファイル再生なら

		// タイムスタンプ類
		$start_timestamp = $ini[$stream]['start_timestamp'];
		$end_timestamp = $ini[$stream]['end_timestamp'];

		// 実況IDを取得
		$channel = getJKchannel($ini[$stream]['filechannel']);

		// チャンネルが-1・-2以外 & ニコニコのログイン情報がセットされてるなら
		// -1・-2はそのチャンネルがニコニコ実況にない事を意味する
		if (isset($channel) and $channel !== -1 and $channel !== -2 and !empty($nicologin_mail) and !empty($nicologin_password)){

			// getflvを叩く
			$getflv = nicologin($basegetflv.'jk'.$channel.'&start_time='.$start_timestamp.'&end_time='.$end_timestamp, false);

			// 取得した結果(クエリ)をParseして配列に格納する
			parse_str($getflv, $getflv_param);

			// 認証されていない(Cookieの期限切れなど)場合
			if (!isset($getflv_param['user_id'])){
				// ログイン認証させてもう一度getflvを叩く
				$getflv = nicologin($basegetflv.'jk'.$channel, true);		
				// もう一度取得した結果(クエリ)をParseして配列に格納する
				parse_str($getflv, $getflv_param);
				// もう一度取得したらログインできた
				if (isset($getflv_param['user_id'])){
					$login = true;
				} else {
					$login = false;
				}
			} else if (isset($getflv_param['user_id'])){
				$login = true;
			}

			// ログインが確実に出来ている場合のみ
			if ($login){

				// Waybackkeyを取得
				$waybackkey_URL = 'http://jk.nicovideo.jp/api/v2/getwaybackkey?thread='.$getflv_param['thread_id'];
				$waybackkey = str_replace('waybackkey=', '', nicologin($waybackkey_URL, false)); //ログインクッキーを渡す

				// ニコニコの謎仕様で過去ログは取得を終える時間から巻き戻して1000件分しか取得できないため、
				// whileで繰り返して$whenが放送開始時刻の前になるまで取得する

				// 取得終了時間　ここから巻き戻して1000件ずつ取得
				$when = $end_timestamp;
				// 定義
				$jkthread = array();
				$jkthread_new = array();

				// 放送開始時刻より$whenの方が時間が前になるまで実行
				while($start_timestamp < $when){

					// コメントサーバから実況を取得するURL
					$jkthread_url = 'http://'.$getflv_param['ms'].':'.$getflv_param['http_port'].
								'/api/thread?version=20061206&thread='.$getflv_param['thread_id'].'&user_id='.$getflv_param['user_id'].'&waybackkey='.$waybackkey.
								'&res_from=-1000&when='.$when;

					// XMLでAPIを叩く
					$jkthread_xml = @simplexml_load_file($jkthread_url); // 何故かたまにエラー吐くので抑制

					// APIを解析
					list($jkthread_new, $jkthread_info) = getJKthread($jkthread_xml);

					// コメントがあれば
					if (!isset($jkthread_new[0]['date'])) break;
					$when = $jkthread_new[0]['date'];

					// 順次足す
					$jkthread = array_merge($jkthread_new, $jkthread);
				}

				// コメントは取得出来たもののこのままの状態だと
				// 放送開始時刻よりも前のコメントも混じってしまっているため見つけ次第消す
				foreach ($jkthread as $key => $value){

					if (!isset($jkthread_new[0]['date'])) break;

					// 放送開始時刻よりタイムスタンプが小さいなら
					if ($jkthread[$key]['date'] < $start_timestamp){
						// 配列を削除
						unset($jkthread[$key]);
					}
				}

				// 配列のインデックスを詰める
				$jkthread = array_values($jkthread);

				// オプションを解析してDPlayer用に変換
				foreach ($jkthread as $i => $value){

					if (isset($jkthread[$i]['mail'])){ //空でないなら
						$option = str_replace('184', '', $jkthread[$i]['mail']);
					} else {
						$option = '';
					}
		
					// 位置を取得
					$position = getPosition($option);
					
					// 色を取得
					$color = getColor($option);

					// ユーザーIDがあれば代入
					if (isset($jkthread[$i]['user_id'])){
						$user_id = $jkthread[$i]['user_id'];
					} else {
						$user_id = 'none';
					}

					// vposからミリ秒部分だけ取り出す
					$vpos = floatval('0.'.substr($jkthread[$i]['vpos'], -3, 3));
					// 秒を計算
					$sec = ($jkthread[$i]['date'] - $start_timestamp) + $vpos + intval(isSettingsItem('comment_file_delay')); // 遅延分を足す
					// 万が一マイナスになった場合は0にする
					if ($sec < 0) $sec = 0;
		
					$danmaku[$i] = array(
						//   コメント投稿時間から放送開始時間を引く   vposを浮動小数点型に変換して四捨五入してさっきのに足す
						round($sec, 3), // 秒数(ミリ秒足す)
						$position, // 場所(上・スクロール・下)をインポート
						$color, // 色をインポート
						$user_id, //ユーザーIDをインポート
						$jkthread[$i]['content'], //コメントをインポート
					);
				}

				$code = 0;

				// コメントが空ならNULLにする
				if (!isset($danmaku)){
					$danmaku = null;
				}

			} else { // ログイン失敗

				// エラーコード
				$code = 200;
				$danmaku = null;
			}

		// -1が出た場合
		} else if ($channel === -1){

			// -1 の場合はエラーにしない
			$code = 0;
			$danmaku = null;

		// その他のエラー
		} else {

			// エラーコード
			$code = 100;
			$danmaku = null;
		}

		// 出力JSON
		$json = array(
			'api' => 'jikkyo',
			'type' => 'file',
			'ch' => 'jk'.$channel,
			'code' => $code,
			'version' => 3,
			'data' => $danmaku
		);

	// オフラインかファイル再生で投稿できないときに蹴る
	} else if ($_SERVER['REQUEST_METHOD'] == 'POST'){

		// 出力JSON
		$json = array(
			'api' => 'jikkyo',
			'type' => 'error',
			'code' => 300,
			'version' => 3
		);

	} else if (isset($_GET['id'])){ // ?id=TVRemotePlus

		// 出力JSON
		$json = array(
			'api' => 'jikkyo',
			'type' => 'connect',
			'code' => 0,
			'version' => 3
		);

	} else { // それ以外のエラー

		// 出力JSON
		$json = array(
			'api' => 'jikkyo',
			'type' => 'error',
			'code' => 0,
			'version' => 3
		);

	}

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
