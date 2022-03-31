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

	if (isset($_SESSION['oauth_token']) and isset($_SESSION['oauth_token_secret'])){ //OAuthトークンがセッションにあるなら

		// エラー捕捉
		try {

			// APIにアクセスするためのアクセストークンを用いて$connectionを作成
			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET,
			$_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

			// CookieからJSONを取得
			$cookie = json_decode($_COOKIE['tvrp_twitter_settings'], true);

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
				$hashtag_text_raw = str_replace('　', ' ', $_POST['hashtag']);  // 全角スペースを半角スペースに置換
				$hashtag_text_raw = preg_replace('/\s(?=\s)/', '', $hashtag_text_raw);  // 複数のスペースを一つへ
				$hashtag_text_raw = trim($hashtag_text_raw);  // 左右のスペースを除去
				$hashtag_text = explode(' ', $hashtag_text_raw);  // スペースで分割

				// ハッシュタグの数だけ
				foreach ($hashtag_text as $i => $value) {
					$hashtag_text[$i] = trim($hashtag_text[$i]);  // 左右のスペースを除去
					if (empty($hashtag_text[$i])) {
						continue;  // 空文字は無視
					}
					if (strpos($hashtag_text[$i], '#') === false){  // # が付いてなかったら
						$hashtag_text[$i] = '#'.$hashtag_text[$i];  // それぞれ付けておく
					}
					$hashtag = $hashtag.$hashtag_text[$i].' ';
				}

				// 現在のタイムスタンプと前のタイムスタンプが指定した秒数空いてるならハッシュタグを付ける
				// シャドウバン対策です、60秒以内(？)にハッシュタグつけて連投すると Search Ban されるらしい
				// echo 'ハッシュタグ付きツイートの差: '.($now_tweettime - $previous_tweettime).'秒 ';
				if (($now_tweettime - $previous_tweettime) > $tweet_time){

					// 間隔が空いててハッシュタグあるならハッシュタグもつける
					$tweet_text = $_POST['tweet'].' '.$hashtag;

					// ハッシュタグついてるのでタイムスタンプをCookieに記録する
					$cookie['tweet_latest'] = time();
					setcookie('tvrp_twitter_settings', json_encode($cookie, JSON_UNESCAPED_UNICODE), time() + 7776000, '/');

				} else {
					// 指定した秒数空いてないのでハッシュタグを無効化
					$tweet_text = $_POST['tweet'].' '.str_replace('#', '# ', $hashtag);
				}

			} else {
				// ハッシュタグないならツイートだけつける
				$tweet_text = $_POST['tweet'];
			}

			// 画像とツイートが添付されている場合のみ
			if (isset($_POST['tweet']) and isset($_FILES['picture1'])){

				// アップロードしたはずの画像が存在するなら
				if (is_uploaded_file($_FILES['picture1']['tmp_name'])){

					// アップロード先のフォルダ
					if (empty($tweet_upload)){
						$tweet_upload = $base_dir.'data/upload/';
					}

					// メディアID
					$media_ids = [];

					// 1枚ごとに実行
					foreach ($_FILES as $index => $file) {

						// picture5 (存在しない回)
						if ($index === 'picture5') {
							break; // ループを抜ける
						}

						// アップロードするパス
						$picture = $tweet_upload.'/Capture_'.date('Ymd-His').'_'.str_replace('picture', '', $index).'.jpg';

						// 画像をアップロード先のフォルダに移動
						if (move_uploaded_file($file['tmp_name'], $picture)){

							// Twitterに画像をアップロード
							$media = $connection->upload('media/upload', ['media' => $picture]);

							// メディアIDを取得
							if (isset($media->media_id_string)) {

								$media_ids[] = $media->media_id_string;

							} else {
								echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
								exit(1);
							}

						} else {
							echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
							exit(1);
						}

					}

					$tweet_type = '画像付きツイート';

					// 投稿後に画像を削除するなら
					if ($tweet_delete == 'true'){
						// ファイルを削除
						unlink($picture);
					}

					// ツイートの内容を設定
					$tweet = [
						'status' => $tweet_text,
						'media_ids' => implode(',', $media_ids)
					];

				} else {
					echo '<span class="tweet-failed">画像の投稿に失敗しました：投稿に失敗しました…</span>';
					exit(1);
				}

			} else if (isset($_POST['tweet']) and !empty($_POST['tweet'])){ // 画像はないけどツイートはある
				$tweet_type = 'ツイート';

    			// ツイートの内容を設定
				$tweet = [
					'status' => $tweet_text
				];

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
			// エラーメッセージ
			switch ($result->errors[0]->code) {
				case 32:
					echo '<span class="tweet-failed">認証に失敗しました：投稿に失敗しました…</span>';
					break;
				case 63:
					echo '<span class="tweet-failed">アカウントが停止されています：投稿に失敗しました…</span>';
					break;
				case 64:
					echo '<span class="tweet-failed">アカウントが停止されています：投稿に失敗しました…</span>';
					break;
				case 88:
					echo '<span class="tweet-failed">レート制限を超えました：投稿に失敗しました…</span>';
					break;
				case 89:
					echo '<span class="tweet-failed">トークンが期限切れです (再ログインしてください)：投稿に失敗しました…</span>';
					break;
				case 99:
					echo '<span class="tweet-failed">OAuth 資格情報を確認できません (再ログインしてください)：投稿に失敗しました…</span>';
					break;
				case 131:
					echo '<span class="tweet-failed">サーバーエラー (500) が発生しています：投稿に失敗しました…</span>';
					break;
				case 135:
					echo '<span class="tweet-failed">認証に失敗しました：投稿に失敗しました…</span>';
					break;
				case 185:
					echo '<span class="tweet-failed">ツイート数の上限に達しています：投稿に失敗しました…</span>';
					break;
				case 186:
					echo '<span class="tweet-failed">ツイートが長過ぎます：投稿に失敗しました…</span>';
					break;
				case 187:
					echo '<span class="tweet-failed">ツイートが重複しています：投稿に失敗しました…</span>';
					break;
				case 226:
					echo '<span class="tweet-failed">このアクションを完了できません：投稿に失敗しました…</span>';
					break;
				case 231:
					echo '<span class="tweet-failed">ログインを確認してください (再ログインしてください)：投稿に失敗しました…</span>';
					break;
				case 261:
					echo '<span class="tweet-failed">Twitter API アプリが凍結されています：投稿に失敗しました…</span>';
					break;
				case 261:
					echo '<span class="tweet-failed">アカウントが一時的にロックされています：投稿に失敗しました…</span>';
					break;
				case 326:
					echo '<span class="tweet-failed">アカウントが一時的にロックされています：投稿に失敗しました…</span>';
					break;
				case 415:
					echo '<span class="tweet-failed">コールバック URL が承認されていません：投稿に失敗しました…</span>';
					break;
				case 416:
					echo '<span class="tweet-failed">Twitter API アプリが無効化されています：投稿に失敗しました…</span>';
					break;
				default:
					echo '<span class="tweet-failed">投稿に失敗しました… (code: '.$result->errors[0]->code.')　<a id="tweet-login" href="/tweet/auth.php"><i class="fas fa-sign-in-alt"></i>再ログイン</a></span>';
					break;
			}
		} else {
			echo '<span class="tweet-failed">投稿に失敗しました…　<a id="tweet-login" href="/tweet/auth.php"><i class="fas fa-sign-in-alt"></i>再ログイン</a></span>';
		}

	} else { //セッションがない場合
		echo '<a id="tweet-login" href="/tweet/auth.php"><i class="fas fa-sign-in-alt"></i>ツイートするには Twitter でログインしてください</a>';
	}
