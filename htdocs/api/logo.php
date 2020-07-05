<?php

	// EMWUI の局ロゴ取得 API のラッパー
	// リバースプロキシ環境でも局ロゴを表示するため

	// モジュール読み込み
	require_once ('../../modules/require.php');

	// onid と sid がついている場合のみ
	if (isset($_REQUEST['onid']) and isset($_REQUEST['sid'])) {
		
		// ヘッダー
		header('Content-Type: image/bmp');

		// クエリ
		$logo_onid = $_REQUEST['onid'];
		$logo_sid = $_REQUEST['sid'];

		// 局ロゴの URL
		$logo_url = $EDCB_http_url.'api/logo?onid='.$logo_onid.'&sid='.$logo_sid;
		$logo_url_fallback = $EDCB_http_url.'EMWUI/logo.lua?onid='.$logo_onid.'&sid='.$logo_sid;

		// 局ロゴを取得
		$logo = @file_get_contents($logo_url, false, $ssl_context);

		// 新 API が 404 だったらフォールバック
		if (isset($http_response_header[0]) and strpos($http_response_header[0], '200') === false) {
			$logo = @file_get_contents($logo_url_fallback, false, $ssl_context);
		}

		// 局ロゴを出力
		echo $logo;
		
		exit();

	} else {

		// エラー画像
		header('Content-Type: image/jpeg');
		readfile('../files/thumb_default.jpg');
		
		exit();

	}
