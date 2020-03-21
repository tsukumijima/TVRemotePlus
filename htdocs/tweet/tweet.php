<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');

	// セッション保存ディレクトリ
	session_save_path($base_dir.'data/twitter_session');

	// Twitter認証用セッション名
	// 視聴数カウントにもセッションを使っていてIDが重複すると面倒な事になるので設定
	session_name('twitter_session');

	// セッション有効期限
	ini_set('session.gc_maxlifetime', 7776000); // 3ヶ月
	ini_set('session.cookie_lifetime', 7776000); // 3ヶ月

	// セッション開始
	session_start();

	// TwitterOAuthの読み込み
	require_once ('../../modules/TwitterOAuth/autoload.php');
	use Abraham\TwitterOAuth\TwitterOAuth;

	if (isset($_SESSION['oauth_token']) and isset($_SESSION['oauth_token_secret'])){ //OAuthトークンがセッションにあるなら

		// エラー捕捉
		try {

			// APIにアクセスするためのアクセストークンを用いて$connectionを作成
			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET,
			$_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

			// CookieからJSONを取得
			$cookie = json_decode($_COOKIE['twitter'], true);

			// 現在のタイムスタンプ
			$now_tweettime = time();

			// 以前ハッシュタグ付きツイートをしたときのタイムスタンプ
			if (isset($cookie['tweet_latest']) and !empty($cookie['tweet_latest'])){
				$previous_tweettime = $cookie['tweet_latest'];
			} else {
				$previous_tweettime = 0;
			}

			// ハッシュタグ処理
			if ($_POST['hashtag'] !== ''){ // 空でないなら

				$hashtag = '';
				$hashtag_text = explode(' ', str_replace('　', ' ', $_POST['hashtag'])); //スペースで分割

				// ハッシュタグの数だけ
				foreach ($hashtag_text as $i => $value) {
					if (strpos($hashtag_text[$i], '#') === false){ // # が付いてなかったら
						$hashtag_text[$i] = '#'.$hashtag_text[$i]; // それぞれ付けておく
					}
					$hashtag = $hashtag.$hashtag_text[$i].' ';
				}

				// 現在のタイムスタンプと前のタイムスタンプが指定した秒数空いてるならハッシュタグを付ける
				// シャドウバン対策です、60秒以内(？)にハッシュタグつけて連投すると Search Ban されるらしい
				// echo 'ハッシュタグ付きツイートの差: '.($now_tweettime - $previous_tweettime).'秒 ';
				if (($now_tweettime - $previous_tweettime) > $tweet_time){

					// 間隔が空いててハッシュタグあるならハッシュタグもつける
					$tweet_text = $hashtag."\n".$_POST['tweet'];
					
					// ハッシュタグついてるのでタイムスタンプをCookieに記録する
					$cookie['tweet_latest'] = time();
					setcookie('twitter', json_encode($cookie, JSON_UNESCAPED_UNICODE), time() + 7776000, '/');

				} else { 
					// 指定した秒数空いてないのでハッシュタグを無効化
					$tweet_text = str_replace('#', '# ', $hashtag)."\n".$_POST['tweet'];
				}

			} else {
				// ハッシュタグないならツイートだけつける
				$tweet_text = $_POST['tweet'];
			}

			// 画像とツイートが添付されている場合のみ
			if (isset($_POST['tweet']) and !isset($_POST['picture'])){

				// アップロードしたはずの画像が存在するなら
				if (is_uploaded_file($_FILES['picture']['tmp_name'])){

					if (empty($tweet_upload)){
						$tweet_upload = $base_dir.'data/upload/';
					}

					// アップロード処理
					$picture = $tweet_upload.'/Capture_'.date('Ymd-His').'.jpg'; // アップロードするパス
					
					// アップロードディレクトリに保存
					if (move_uploaded_file($_FILES['picture']['tmp_name'], $picture)){
						// Twitterに画像をアップロード
						$media = $connection->upload('media/upload', ['media' => $picture]);
					} else {
						echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
						exit(1);
					}
					
					$tweet_type = '画像付きツイート';

					// 投稿後に画像を削除するなら
					if ($tweet_delete == 'true'){
						// ファイルを削除
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

			} else if (isset($_POST['tweet'])){ //画像はないけどツイートはある
				$tweet_type = 'ツイート';

    			// ツイートの内容を設定
				$tweet = array(
					'status' => $tweet_text,
				);

			} else { // 何故か両方ない場合
				echo '<span class="tweet-failed">本文が送信されませんでした：投稿に失敗しました…</span>';
				exit(1);
			}

			// ツイートする
			// $resultにはbool型で実行結果が出力される
			$result = $connection->post('statuses/update', $tweet);
		
		} catch(Exception $e) {
			echo '<span class="tweet-failed">投稿中にエラーが発生しました：投稿に失敗しました…</span>';
			exit(1);
		}

		// 情報取得
		$info = $connection->get('account/verify_credentials');

		if($result and !isset($result->errors) and !isset($info->errors)){
			echo $tweet_type.'：投稿に成功しました。';
		} else if (isset($result->errors)){
			switch ($result->errors[0]->code) {
				case 32:
					echo '<span class="tweet-failed">認証に失敗しました：投稿に失敗しました…</span>';
					break;
				case 135:
					echo '<span class="tweet-failed">認証に失敗しました：投稿に失敗しました…</span>';
					break;
				case 89:
					echo '<span class="tweet-failed">トークンが期限切れです(再ログインしてください)：投稿に失敗しました…</span>';
					break;
				case 185:
					echo '<span class="tweet-failed">ツイート数の上限に達しています：投稿に失敗しました…</span>';
					break;
				case 187:
					echo '<span class="tweet-failed">ツイートが重複しています：投稿に失敗しました…</span>';
					break;
				case 231:
					echo '<span class="tweet-failed">ログインを確認してください(再ログインしてください)：投稿に失敗しました…</span>';
					break;
				case 261:
					echo '<span class="tweet-failed">TwitterAPI アプリが凍結されています：投稿に失敗しました…</span>';
					break;
				case 261:
					echo '<span class="tweet-failed">アカウントが一時的にロックされています：投稿に失敗しました…</span>';
					break;
				default:
					echo '<span class="tweet-failed">投稿に失敗しました… (code: '.$result->errors[0]->code.')　<a id="tweet-login" href="/tweet/auth.php">再ログイン</a></span>';
					break;
			}
		} else {
			echo '<span class="tweet-failed">投稿に失敗しました…　<a id="tweet-login" href="/tweet/auth.php">再ログイン</a></span>';
		}

	} else { //セッションがない場合
		echo '<a id="tweet-login" href="/tweet/auth.php">ツイートするには Twitter でログインして下さい</a>';
	}
