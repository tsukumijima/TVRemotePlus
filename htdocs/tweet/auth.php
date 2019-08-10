<?php

	// モジュール読み込み
	require_once ('../../module.php');

	ini_set('display_errors', 0);

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

	// twitterOAuthの読み込み
	require "twitteroauth/autoload.php";
	use Abraham\TwitterOAuth\TwitterOAuth;

	// コンシューマーキーが空ならエラー吐く
	if (empty($CONSUMER_KEY) or empty($CONSUMER_SECRET)){
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		echo '<b>エラー：TwitterAPI のコンシューマーキー、またはコンシューマーシークレットキーが設定されていないため、アプリ連携ができません。</b><br>';
		echo 'Twitter投稿機能を利用する場合は、予め TwitterAPI 開発者アカウントを取得した上で config.php を編集し、<br>';
		echo 'TwitterAPI のコンシューマーキー・コンシューマーシークレットキーを設定し、もう一度アプリ連携し直してください。<br>';
		echo '<a href="/">ホームに戻る</a><br>';
		exit(1);
	}

	// config.phpで入力した値を用いてTwitterに接続
	$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);

	// エラー捕捉
	try {
		// 認証URLを取得するためのリクエストトークンの生成
		$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $OAUTH_CALLBACK));

	} catch(Exception $e) {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		if (preg_match('/Callback URL not approved for this client application.*/', $e)){
			echo '<b>エラー：TVRemotePlusのコールバックURLがTwitterAPI側に承認されていない、または一致しないため、アプリ連携ができません。</b><br>';
			echo 'TwitterAPIのアプリ設定にて、Callback URLs の項目にコールバックURL ('.$OAUTH_CALLBACK.') を追加し、もう一度アプリ連携し直してください。<br>';
			echo 'また、config.php にて $OAUTH_CALLBACK の値を変更していてTwitterAPI側のコールバックURLと一致しない場合にもこのエラーが発生します。<br>';
			echo '<a href="/">ホームに戻る</a><br>';
			
		} else {
			echo '<b>エラー：認証中に不明なエラーが発生したため、アプリ連携ができません。</b><br>';
			echo 'もう一度アプリ連携し直してください。<br>';
			echo '<a href="/">ホームに戻る</a><br>';
		}
		exit(1);
	}

	// 認証後にアクセストークンを取得するために、セッション関数にトークンを保存することでコールバック後にアクセス出来るようにする
	$_SESSION['oauth_token'] = $request_token["oauth_token"];
	$_SESSION['oauth_token_secret'] = $request_token["oauth_token_secret"];

	// 認証URLの取得
	$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));

	// 認証ページにリダイレクト
	header('Location: '.$url);
	exit;

