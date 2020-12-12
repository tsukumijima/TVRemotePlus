<?php

require_once ('classloader.php');

class Jikkyo {


	// ログイン情報を保存する Cookie ファイル
	private $cookie_file;

	// channel_table.json ファイル
	private $channel_table_file;

	// ゲストかどうか
	private bool $is_guest;

	// ニコニコのメールアドレス
	private string $nicologin_mail;
	
	// ニコニコのパスワード
	private string $nicologin_password;


	/**
	 * コンストラクタ
	 *
	 * @param string $nicologin_mail ニコニコのメールアドレス
	 * @param string $nicologin_password ニコニコのパスワード
	 * @return void
	 */
	public function __construct(string $nicologin_mail, string $nicologin_password) {
		
		// require.php 内の変数をインスタンス変数に設定
		require ('require.php');

		$this->cookie_file = $cookiefile;
		$this->channel_table_file = $channel_table_file;
		
		// メールアドレス・パスワードが空ならゲスト利用と判定
		$this->is_guest = (empty($nicologin_mail) or empty($nicologin_password));

		// メールアドレス・パスワードをセット
		$this->nicologin_mail = $nicologin_mail;
		$this->nicologin_password = $nicologin_password;
	}
	

	/**
	 * ニコニコにログインし、Cookie を保存する
	 * 毎回ログインしていると非効率でかつログアウトが頻繁に発生してしまうため、
	 * セッションが切れるまで一度取得した Cookie を使い回す
	 *
	 * @return void
	 */
	private function login(): void {

		// メールアドレスまたはパスワードが空だったらログインしない
		if ($this->is_guest) {
			return;
		}

		// ログイン先
		$url = 'https://account.nicovideo.jp/api/v1/login';

		// 送信するデータ
		$data = array(
			'mail' => $this->nicologin_mail, // メールアドレス
			'password' => $this->nicologin_password, // パスワード
		);

		// curl を初期化
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file); // Cookie をファイルに保存する（重要）

		// 空実行する
		// curl_exec() の返り値はアカウント画面の HTML なので取る価値はない
		curl_exec($curl);
		curl_close($curl);
	}


	/**
	 * チャンネル名から実況 ID を取得する
	 * そのチャンネルにニコニコ実況のチャンネルが存在しない場合は -1 、
	 * どのチャンネルにも一致しなかった場合は -2 を返す
	 *
	 * @param string $channel_name チャンネル名（放送局名）
	 * @return integer そのチャンネルの実況 ID
	 */
	public function getNicoJikkyoID(string $channel_name): int {

		// channel_table.json を読み込み
		$channel_table = json_decode(file_get_contents($this->channel_table_file), true);

		// 配列を回す
		foreach ($channel_table as $channel_record) {

			// 抽出したチャンネル名
			$channel_field = $channel_record['Channel'];

			// 正規表現パターン
			// preg_quote() は正規表現用の文字をエスケープする用
			mb_regex_encoding('UTF-8');
			$match = '{'.str_replace('NHK総合', 'NHK総合[0-9]?', str_replace('NHKEテレ', 'NHKEテレ[0-9]?', $channel_field)).'[0-9]?}u';

			// チャンネル名がいずれかのパターンに一致したら
			if ($channel_name === $channel_field or preg_match($match, $channel_name)) {

				// 実況 ID を返す
				return intval($channel_record['JikkyoID']);
			}
		}

		// チャンネル名が一致しなかった
		return -2;
	}


	/**
	 * 実況ID（例: (jk)1）から、ニコニコチャンネルID（例: (ch)2646436）を取得する
	 * API は jk1 のようなチャンネルのスクリーンネームだと取得できないらしい
	 * 存在しない実況 ID の場合は null を返す
	 *
	 * @param integer $nicojikkyo_id 実況ID
	 * @return mixed ニコニコチャンネルID or null
	 */
	public function getNicoChannelID(int $nicojikkyo_id) {

		// 変換テーブル
		$table = [
			'jk1' => 2646436,
			'jk2' => 2646437,
			'jk4' => 2646438,
			'jk5' => 2646439,
			'jk6' => 2646440,
			'jk7' => 2646441,
			'jk8' => 2646442,
			'jk9' => 2646485,
			'jk211' => 2646846,
		];

		if (isset($table['jk'.$nicojikkyo_id])) {
			// return $table['jk'.$nicojikkyo_id];
			return 1072;  // 開始までは暫定的にチャンネル ID をウェザーニューズに設定
		} else {
			return null;
		}
	}


	/**
	 * ニコニコチャンネルの ID から、現在放送中のニコ生の放送 ID を取得する
	 * 現在放送中の番組が存在しない場合は null を返す
	 *
	 * @param integer $nicochannel_id ニコニコチャンネルID
	 * @return mixed ニコ生の放送ID or null
	 */
	public function getNicoLiveID(int $nicochannel_id) {

		// ベース URL
		$api_baseurl = 'https://public.api.nicovideo.jp/v1/channel/channelapp/content/lives.json?sort=startedAt&page=1&channelId=';

		// API レスポンスを取得
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $api_baseurl.$nicochannel_id);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
		$response = json_decode(curl_exec($curl), true);  // リクエストを実行
		curl_close($curl);

		if (!isset($response) or empty($response) or $response['meta']['status'] !== 200) {
			return null;  // レスポンスの取得に失敗した
		}

		// アイテムごとに回す
		foreach ($response['data']['items'] as $item) {
			
			// アイテムの category が current（放送中）であれば
			if ($item['category'] === 'current') {

				// ニコ生の放送 ID を返す
				return $item['id'];
			}
		}

		// アイテムごとに回したけど現在放送中の番組がなかった
		return null;
	}


	/**
	 * ニコ生の視聴セッション情報を取得する
	 *
	 * @param integer $nicolive_id ニコ生の放送ID (ex: (lv)329283198)
	 * @return array 視聴セッション情報が含まれる連想配列
	 */
	public function getNicoliveSession(int $nicolive_id): array {

		/**
		 * 二回使うので関数内関数にした
		 *
		 * @param integer $nicolive_id ニコ生の放送 ID (ex: (lv)329283198)
		 * @param string $cookie_file Cookie のあるファイル
		 * @return array 処理結果
		 */
		function getSession(int $nicolive_id, string $cookie_file): array {

			// ベース URL
			$nicolive_baseurl = 'https://live2.nicovideo.jp/watch/';

			// HTML を取得
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $nicolive_baseurl.'lv'.$nicolive_id);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
			if (file_exists($cookie_file)) curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); // Cookie を送信する（ファイルがあれば）
			$nicolive_html = curl_exec($curl);  // リクエストを実行
			curl_close($curl);
			
			// json をスクレイピング
			preg_match('/<script id="embedded-data" data-props="(.*?)"><\/script>/s', $nicolive_html, $result);
			$nicolive_json = json_decode(htmlspecialchars_decode($result[1]), true);

			return $nicolive_json;
		}

		// 情報を取得
		$nicolive_json = getSession($nicolive_id, $this->cookie_file);


		// ログイン利用で実際にログインされている、またはゲスト利用
		if ($nicolive_json['user']['isLoggedIn'] === true or $this->is_guest) {

			// 今のところ処理なし
		
		// ログイン利用だが実際にはログインされていない（セッション切れなど）
		} else {

			// ログイン処理を実行し、Cookie を保存する
			$this->login();

			// 再度情報を取得
			$nicolive_json = getSession($nicolive_id, $this->cookie_file);
		}

		// タイトル
		$title = $nicolive_json['program']['title'];

		// 開始時間
		$begintime = $nicolive_json['program']['beginTime'];

		// 終了時間
		$endtime = $nicolive_json['program']['endTime'];

		// ユーザー ID
		$user_id = (isset($nicolive_json['user']['id']) ? $nicolive_json['user']['id'] : '');

		// ユーザータイプ（ non・standard・premium のいずれか）
		$user_type = $nicolive_json['user']['accountType'];

		// ログインしているかどうか
		$is_login = $nicolive_json['user']['isLoggedIn'];

		// 視聴セッション構築用の WebSocket の URL
		$websocket_url = $nicolive_json['site']['relive']['webSocketUrl'];


		// 連想配列を返す
		return [
			'title' => $title,
			'begintime' => $begintime,
			'endtime' => $endtime,
			'live_id' => 'lv'.$nicolive_id,
			'user_id' => $user_id,
			'user_type' => $user_type,
			'is_login' => $is_login,
			'websocket_url' => $websocket_url,
		];
	}
}
