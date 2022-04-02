<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');

	// セッション保存ディレクトリ
	session_save_path($base_dir.'data/twitter_session');

	// Twitter認証用セッション名
	// 視聴数カウントにもセッションを使っていてIDが重複すると面倒な事になるので設定
	session_name('tvrp_twitter_session');

	// セッション有効期限
	ini_set('session.gc_maxlifetime', 7776000); // 3ヶ月
	ini_set('session.cookie_lifetime', 7776000); // 3ヶ月

	// セッション開始
	session_start();

	// TwitterOAuth の読み込み
	require_once ('../../modules/classloader.php');
	use Abraham\TwitterOAuth\TwitterOAuth;

	if (!isset($_GET['denied'])){ // deniedでないなら

		// auth.phpでセッション関数に代入したトークンを用いて$connectionを作成
		$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

		// APIのアクセスに必要なトークンを取得
		$access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $_REQUEST['oauth_verifier']]);

		// セッション関数に入れておいたコールバック用トークンを差し替える
		$_SESSION['oauth_token'] = $access_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $access_token['oauth_token_secret'];

		// 取得したトークンでもう一度接続
		$_connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

		// 情報取得
		$info = $_connection->get("account/verify_credentials");

		// アカウント情報をCookieに登録しておく
		$cookie = json_encode([
			'account_name' => $info->name,
			'account_id' => $info->screen_name,
			'account_icon' => str_replace('_normal', '', $info->profile_image_url_https),
		], JSON_UNESCAPED_UNICODE);
		setcookie('tvrp_twitter_settings', $cookie, time() + 7776000, '/');

		// トップページにリダイレクト
		if (isset($_GET['stream']) and !empty($_GET['stream'])) {  // ストリーム番号があるか
			header('Location: '.$site_url.filter_var($_GET['stream'], FILTER_VALIDATE_INT).'/');
		} else {
			header('Location: '.$site_url);
		}

		exit;

	} else {

		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		echo '<b>エラー：Twitterアカウントへのアプリ連携が拒否されたため、アプリ連携ができません。</b><br>';
		echo 'Twitter投稿機能を利用する場合は、「連携アプリを認証」を押して、アプリ連携し直してください。<br>';
		echo '<a href="/">ホームに戻る</a><br>';
		exit(1);
	}
