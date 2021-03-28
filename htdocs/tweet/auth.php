<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');
	require_once ('../../modules/module.php');

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

	// TwitterOAuthの読み込み
	require_once ('../../modules/TwitterOAuth/autoload.php');
	use Abraham\TwitterOAuth\TwitterOAuth;

	// コンシューマーキーが空の場合
	if (empty($CONSUMER_KEY) or empty($CONSUMER_SECRET)){
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		echo '<b>エラー：TwitterAPI の Consumer Key 、または Consumer Secret が設定されていないため、アプリ連携ができません。</b><br>';
		echo 'Twitter 投稿機能を利用する場合は、予め TwitterAPI 開発者アカウントを取得した上で <a href="/settings/">環境設定</a> から<br>';
		echo 'TwitterAPI の Consumer Key・Consumer Secret を設定し、もう一度アプリ連携し直してください。<br>';
		echo '<a href="/">ホームに戻る</a><br>';
		exit(1);
	}

	// リバースプロキシからのアクセスでリバースプロキシのURLが指定されていない場合
	if ($OAUTH_CALLBACK === false){
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		echo '<b>エラー：リバースプロキシからアクセスする場合の URL が指定されていないため、アプリ連携ができません。</b><br>';
		echo 'リバースプロキシから Twitter 投稿機能を利用する場合は、予め TwitterAPI 開発者アカウントを取得した上で <a href="/settings/">環境設定</a> から<br>';
		echo 'リバースプロキシからアクセスする場合の URL を設定し、もう一度アプリ連携し直してください。<br>';
		echo '<a href="/">ホームに戻る</a><br>';
		exit(1);
	}

	// リファラからストリーム番号を取得
	$stream = getStreamNumber($_SERVER['HTTP_REFERER']);

	// config.phpで入力した値を用いてTwitterに接続
	$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);

	// エラー捕捉
	try {
		// 認証URLを取得するためのリクエストトークンの生成
		$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $OAUTH_CALLBACK.'?stream='.$stream));

	} catch(Exception $e) {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">';
		if (preg_match('/Callback URL not approved for this client application.*/', $e)){
			echo '<b>エラー：TVRemotePlus の Callback URL が TwitterAPI 側に承認されていない、または一致しないため、アプリ連携ができません。</b><br>';
			echo 'TwitterAPI のアプリ設定にて、Callback URLs の項目に Callback URL ('.$OAUTH_CALLBACK.') を追加し、もう一度アプリ連携し直してください。<br>';
			echo '<a href="/">ホームに戻る</a><br>';
			
		} else if (preg_match('/Could not authenticate you.*/', $e)){
			echo '<b>エラー：TwitterAPI の認証に失敗したため、アプリ連携ができません。</b><br>';
			echo '設定した Consumer Key・Consumer Secret が間違っている可能性があります。<br>';
			echo '<a href="/settings/">環境設定</a> から Consumer Key・Consumer Secret が正しいかどうか確認し、もう一度アプリ連携し直してください。<br>';
			echo '<a href="/">ホームに戻る</a><br>';
			
		} else {
			echo '<b>エラー：認証中に不明なエラーが発生したため、アプリ連携ができません。</b><br>';
			echo '<b>'.$e.'</b><br>';
			echo '<a href="/settings/">環境設定</a> から設定が正しいかどうか確認し、もう一度アプリ連携し直してください。<br>';
			echo '<a href="/">ホームに戻る</a><br>';
		}
		exit(1);
	}

	// 認証後にアクセストークンを取得するために、セッション関数にトークンを保存することでコールバック後にアクセス出来るようにする
	$_SESSION['oauth_token'] = $request_token['oauth_token'];
	$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

	// 認証URLの取得
	$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));

	// 認証ページにリダイレクト
	header('Location: '.$url);
	exit;

