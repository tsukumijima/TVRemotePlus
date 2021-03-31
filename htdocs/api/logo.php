<?php

	// EMWUI の局ロゴ取得 API のラッパー
	// リバースプロキシ環境でも局ロゴを表示するため

	// モジュール読み込み
	require_once ('../../modules/require.php');

	// クエリ
	$logo_onid = isset($_REQUEST['onid']) ? filter_var($_REQUEST['onid'], FILTER_VALIDATE_INT) : false;
	$logo_sid = isset($_REQUEST['sid']) ? filter_var($_REQUEST['sid'], FILTER_VALIDATE_INT) : false;

	// onid と sid がついている場合のみ
	if ($logo_onid !== false and $logo_sid !== false) {
		
		// ブラウザにキャッシュしてもらえるようにヘッダーを設定
		// 参考: https://qiita.com/yuuuking/items/4f11ccfc822f4c198ab0
		header('Cache-Control: public, max-age=2592000');  // 30日間
		header('Content-Type: image/bmp');

		if (!empty($EDCB_http_url)) {

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

		} else {
			// 空文字を出力する
			// 画像が空だと background-image は描画されない
			echo '';
		}

		exit();

	} else {

		// エラー画像
		header('Content-Type: image/jpeg');
		readfile('../files/thumb_default.jpg');
		
		exit();
	}
