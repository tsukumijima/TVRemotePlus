<?php

	// 設定読み込み
	require_once ('../../config.php');

	// セッションのファイル数を返す関数
	function getActiveCount() {
		$path = session_save_path();
		// 何もファイルなかったら0を返す
		if (empty($path)) {
			return 0;
		}
		// ファイル数検索
		$files = glob($path.'/sess_*');
		if ($files === false) {
			return 0;
		}
		// ファイル数を返す
		return count($files);
	}

	// セッションの有効期限
	// 秒数は定期的にアクセスする秒数より短い必要がある
	ini_set('session.gc_maxlifetime', 4);
	ini_set('session.cookie_lifetime', 4);

	// セッションを確実に破棄する
	ini_set('session.gc_probability', 1);  // 分子(デフォルト:1)
	ini_set('session.gc_divisor', 1);  // 分母(デフォルト:100)

	// セッション保存ディレクトリ
	session_save_path($base_dir.'data/session/');

	// 視聴数カウント用セッション名
	// Twitter認証用にもセッションを使っていてIDが重複すると面倒な事になるので設定
	session_name('watchnow_session');

	// セッション管理開始
	session_start();

	// 現在の視聴数を取得する
	$watchnow = getActiveCount();

	$json = array(
		'apiname' => 'watchnow',
		'watchnow' => $watchnow,
	);

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
