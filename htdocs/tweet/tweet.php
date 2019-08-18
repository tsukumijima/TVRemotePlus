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

	if (isset($_SESSION['oauth_token']) and isset($_SESSION['oauth_token_secret'])){ //OAuthトークンがセッションにあるなら

		// エラー捕捉
		try {

			// APIにアクセスするためのアクセストークンを用いて$connectionを作成
			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET,
			$_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

			// 前にハッシュつけたツイートのタイムスタンプを取得
			$now_tweet = time();

			// ファイルがあるなら
			if (file_exists($tweet_time_file)){
				$previous_tweet = file_get_contents($tweet_time_file);
			} else {
				$previous_tweet = 0;
			}

			// ハッシュタグ処理
			if ($_POST['hashtag'] !== ''){ // 空でないなら
				$hashtag = '';
				$hashtag_text = explode(' ', str_replace('　', ' ', $_POST['hashtag'])); //スペースで分割
				// ハッシュタグの数だけ
				foreach ($hashtag_text as $i => $value) {
					if (strpos($hashtag_text[$i], '#') === false){ // ハッシュタグ付いてなかったら
						$hashtag_text[$i] = '#'.$hashtag_text[$i]; // それぞれ付けておく
					}
					$hashtag = $hashtag.$hashtag_text[$i].' ';
				}

				// 現在のタイムスタンプと前のタイムスタンプが指定した秒数空いてるならハッシュタグを付ける
				// シャドウバン対策です、60秒以内(？)にハッシュタグつけて連投するとShadowBanされるみたいです
				// echo 'ハッシュタグ付きツイートの差: '.($now_tweet - $previous_tweet).'秒 ';
				if (($now_tweet - $previous_tweet) > $tweet_time){

					// 間隔が空いててハッシュタグあるならハッシュタグもつける
					$tweet_text = $hashtag."\n".$_POST['tweet'];
					
					// ハッシュタグついてるのでタイムスタンプをファイルに記録する
					file_put_contents($tweet_time_file, time());

				} else { 
					// 指定した秒数空いてないのでハッシュタグを無効化
					$tweet_text = str_replace('#', '# ', $hashtag)."\n".$_POST['tweet'];
				}

			} else {
				// ハッシュタグないならツイートだけつける
				$tweet_text = $_POST['tweet'];
			}

			if (isset($_POST['tweet']) and !isset($_POST['picture'])){ //画像とTweetが添付されてるなら

				if(is_uploaded_file($_FILES['picture']['tmp_name'])){ //うｐしたはずの画像が存在するなら

					// アップロード処理
					$picture = $tweet_upload.'/Capture_'.date('Ymd-his').'.jpg'; //うｐするファイルパス
					
					if (move_uploaded_file($_FILES['picture']['tmp_name'], $picture)){
						// 一旦アップロードディレクトリに保存してTwitterに画像をアップロード
						$media = $connection->upload('media/upload', ['media' => $picture]);
					} else {
						echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
						exit(1);
					}
					
					$tweet_type = '画像付きツイート';

					// 投稿後に画像を削除するなら
					if ($tweet_delete == 'true'){
						// 削除
						unlink($picture);
					}

					// ツイートの内容を設定
					$tweet = array(
						'status' => $tweet_text,
						'media_ids' => implode(',', [$media->media_id_string])
					);

				} else {
					echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
					exit(1);
				}

			} else if (isset($_POST['tweet'])){ //画像はないけどTweetはある
				$tweet_type = 'ツイート';

    			// ツイートの内容を設定
				$tweet = array(
					'status' => $tweet_text,
				);

			} else { // 何故か両方ない場合
				echo '<span class="tweet-failed">本文が送信されませんでした：投稿に失敗しました…</span>';
				exit(1);
			}

			// ツイートする、$resultにはbool型で実行結果が出力される
			$result = $connection->post('statuses/update', $tweet);
		
		} catch(Exception $e) {
			echo '<span class="tweet-failed">投稿中にエラーが発生しました：投稿に失敗しました…</span>';
			exit(1);
		}

		// 情報取得
		$info = $connection->get("account/verify_credentials");

		if($result and !isset($result->errors) and !isset($info->errors)){
			echo $tweet_type.'：投稿に成功しました。';
		} else if (isset($result->errors) and ($result->errors[0]->code == 32 or $result->errors[0]->code == 135)){
			echo '<span class="tweet-failed">認証に失敗しました：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 89){
			echo '<span class="tweet-failed">トークンが期限切れです(再ログインしてください)：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 185){
			echo '<span class="tweet-failed">ツイート数の上限に達しています：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 187){
			echo '<span class="tweet-failed">ツイートが重複しています：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 231){
			echo '<span class="tweet-failed">ログインを確認して下さい(再ログインしてください)：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 261){
			echo '<span class="tweet-failed">TwitterAPIアプリが凍結されています：投稿に失敗しました…</span>';
		} else if (isset($result->errors) and $result->errors[0]->code == 326){
			echo '<span class="tweet-failed">アカウントが一時的にロックされています：投稿に失敗しました…</span>';
		} else {
			echo '<span class="tweet-failed">投稿に失敗しました…　<a id="tweet-login" href="'.$BASEURL.'tweet/auth.php">再ログイン</a></span>';
		}

	} else { //セッションがない場合
		echo '<a id="tweet-login" href="'.$BASEURL.'tweet/auth.php">ツイートするにはTwitterでログインして下さい</a>';
	}

