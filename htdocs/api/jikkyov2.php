<?php

	require_once('../../modules/require.php');

	/**
	 * ニコ生（ニコニコ全体）にログインし、Cookie を指定のパスに保存する
	 *
	 * @param string $cookie Cookie を保存するパス
	 * @return void
	 */
	function loginNicolive(string $cookie) {

		global $nicologin_mail, $nicologin_password;

		// ログイン先
		$url = 'https://account.nicovideo.jp/api/v1/login';

		// 送信するデータ
		$data = array(
			'mail' => $nicologin_mail, // メールアドレス
			'password' => $nicologin_password, // パスワード
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない

		// Cookie をファイルに保存する（重要）
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);

		// 空実行する
		// curl_exec() の返り値はアカウント画面の HTML なので取る価値はない
		curl_exec($curl);
		curl_close($curl);
	}


	/**
	 * ニコ生の視聴セッション構築用 WebSocket URL を取得する
	 *
	 * @param string $nicolive_id ニコ生の ID (ex: lv329283198)
	 * @param string $cookie Cookie を保存するパス
	 * @return void
	 */
	function getNicoliveSession(string $nicolive_id, string $cookie) {

		// ベース URL
		$nicolive_baseurl = 'https://live2.nicovideo.jp/watch/';

		// HTML を取得
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $nicolive_baseurl.$nicolive_id);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie); // Cookie を送信する
		$nicolive_html = curl_exec($curl);  // リクエストを実行
		curl_close($curl);
		
		// json をスクレイピング
		preg_match('/<script id="embedded-data" data-props="(.*?)"><\/script>/s', $nicolive_html, $result);
		$nicolive_json = json_decode(htmlspecialchars_decode($result[1]), true);

		return $nicolive_json['site']['relive']['webSocketUrl'];
	}


	// 視聴セッション構築用の WebSocket URL を取得
	// 'lv329283198' は仮、実際にはスクレイピングか API で放送 ID を取得することになる
	$websocket_url = getNicoliveSession('lv329283198', $cookiefile);

	// 出力用 JSON
	$output = [
		'api' => 'jikkyo',
		'watchsession_url' => $websocket_url,
	];

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($output, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
