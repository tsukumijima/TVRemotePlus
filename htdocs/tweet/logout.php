<?php

	// 設定読み込み
	require_once ('../../config.php');

	// セッション保存ディレクトリ
	session_save_path($base_dir.'data/twitter_session');

	// Twitter認証用セッション名
	// 視聴数カウントにもセッションを使っていてIDが重複すると面倒な事になるので設定
	session_name('twitter_session');

	// セッション有効期限
	ini_set('session.gc_maxlifetime', 604800); //一週間
	ini_set('session.cookie_lifetime', 604800); //一週間

	// セッション開始
	session_start();

	// セッション変数を全て解除する
	$_SESSION = array();

	// セッションを切断するにはセッションCookieも削除する
	// Note: セッション情報だけでなくセッションを破壊する
	if (isset($_COOKIE[session_name()])) {
		// Cookieにセッション名の値が存在したら
		setcookie(session_name(), '', time()-42000, '/'); //Cookieを削除
	}

	// 最終的に、セッションを破壊する
	session_destroy();

	// 出力
	echo '<span class="tweet-failed">ログアウトしました。　<a id="tweet-login" href="'.$BASEURL.'tweet/auth.php">再ログイン</a></span>';

