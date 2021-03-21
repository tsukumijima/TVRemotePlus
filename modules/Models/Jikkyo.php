<?php

require_once ('classloader.php');

class Jikkyo {


    // ログイン情報を保存する Cookie ファイル
    private $cookie_file;

    // jikkyo_channels.json ファイル
    private $jikkyo_channels_file;

    // jikkyo_ikioi.json ファイル
    private $jikkyo_ikioi_file;

    // ゲストかどうか
    private bool $is_guest;

    // ニコニコのメールアドレス
    private string $nicologin_mail;
    
    // ニコニコのパスワード
    private string $nicologin_password;    

    // 変換テーブル
    // ch は公式チャンネル・co はコミュニティ
    private array $table = [
        'jk1' => 'ch2646436', // NHK総合
        'jk2' => 'ch2646437', // NHK Eテレ
        'jk4' => 'ch2646438', // 日本テレビ
        'jk5' => 'ch2646439', // テレビ朝日
        'jk6' => 'ch2646440', // TBSテレビ
        'jk7' => 'ch2646441', // テレビ東京
        'jk8' => 'ch2646442', // フジテレビ
        'jk9' => 'ch2646485', // TOKYO MX
        'jk10' => 'co5253063', // テレ玉
        'jk11' => 'co5215296', // tvk
        'jk101' => 'co5214081', // NHK BS1 
        'jk103' => 'co5175227', // NHK BSプレミアム
        'jk141' => 'co5175341', // BS日テレ
        'jk151' => 'co5175345', // BS朝日
        'jk161' => 'co5176119', // BS-TBS
        'jk171' => 'co5176122', // BSテレ東
        'jk181' => 'co5176125', // BSフジ
        'jk191' => 'co5251972', // WOWOW PRIME
        'jk192' => 'co5251976', // WOWOW LIVE
        'jk193' => 'co5251983', // WOWOW CINEMA
        'jk211' => 'ch2646846', // BS11
        'jk222' => 'co5193029', // BS12
        'jk236' => 'co5296297', // BSアニマックス
        'jk333' => 'co5245469', // AT-x
    ];


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
        $this->jikkyo_channels_file = $jikkyo_channels_file;
        $this->jikkyo_ikioi_file = $jikkyo_ikioi_file;
        
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
        curl_setopt($curl, CURLOPT_USERAGENT, Utils::getUserAgent()); // ユーザーエージェントを送信
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file); // Cookie をファイルに保存する（重要）

        // 空実行する
        // curl_exec() の返り値はアカウント画面の HTML なので取る価値はない
        curl_exec($curl);
        curl_close($curl);
    }


    /**
     * チャンネル名から実況 ID を取得する
     * そのチャンネルにニコニコ実況のチャンネルが存在しない場合は null を返す
     *
     * @param string $channel_name チャンネル名（放送局名）
     * @return ?string そのチャンネルの実況 ID
     */
    public function getNicoJikkyoID(string $channel_name): ?string {

        // jikkyo_channels.json を読み込み
        $channel_table = json_decode(file_get_contents($this->jikkyo_channels_file), true);

        // 配列を回す
        foreach ($channel_table as $channel_record) {

            // 抽出したチャンネル名
            $channel_field = $channel_record['Channel'];
            
            // 正規表現用の文字をエスケープ
            $channel_field_escape = str_replace('/', '\/', preg_quote($channel_field));

            // 正規表現パターン
            mb_regex_encoding('UTF-8');
            $match = '/^'.str_replace('NHK総合', 'NHK総合[0-9]?', str_replace('NHKEテレ', 'NHKEテレ[0-9]?', $channel_field_escape)).'[0-9]?[0-9]?[0-9]?$/u';

            // チャンネル名がいずれかのパターンに一致したら
            if ($channel_name === $channel_field or preg_match($match, $channel_name)) {

                // 実況 ID を返す
                if (intval($channel_record['JikkyoID']) > 0) {
                    return 'jk'.$channel_record['JikkyoID'];
                } else {
                    return null;
                }
            }
        }

        // チャンネル名が一致しなかった
        return null;
    }


    /**
     * 実況ID（例: jk1）から、ニコニコチャンネルID（例: ch2646436）を取得する
     * API に渡す ID は jk1 のようなチャンネルのスクリーンネームだと取得できないらしい
     * 存在しない実況 ID の場合は null を返す
     *
     * @param string $nicojikkyo_id 実況ID
     * @return ?string ニコニコチャンネルID or null
     */
    public function getNicoChannelID(string $nicojikkyo_id): ?string {

        if (isset($this->table[$nicojikkyo_id])) {
            return $this->table[$nicojikkyo_id];
        } else {
            return null;
        }
    }


    /**
     * ニコニコチャンネルの ID から、現在放送中のニコ生の放送 ID を取得する
     * 現在放送中の番組が存在しない場合は null を返す
     * 実装方法の変更により現在未使用
     *
     * @param string $nicochannel_id ニコニコチャンネルID
     * @return ?string ニコ生の放送ID or null
     */
    /*
    public function getNicoLiveID(string $nicochannel_id): ?string {

        // ベース URL
        $api_baseurl = 'https://public.api.nicovideo.jp/v1/channel/channelapp/content/lives.json?sort=startedAt&page=1&channelId=';

        // API レスポンスを取得
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_baseurl.str_replace('ch', '', $nicochannel_id));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
        curl_setopt($curl, CURLOPT_USERAGENT, Utils::getUserAgent()); // ユーザーエージェントを送信
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
                return 'lv'.$item['id'];
            }
        }

        // アイテムごとに回したけど現在放送中の番組がなかった
        return null;
    }
    */


    /**
     * ニコ生の視聴セッション情報を取得する
     *
     * @param string $nicolive_id ニコ生の放送 ID として利用できる文字列 (ex: lv329283198・ch2646436)
     * @return ?array 視聴セッション情報が含まれる連想配列 or null
     */
    public function getNicoliveSession(string $nicolive_id): ?array {

        /**
         * 二回使うのでクロージャにした
         *
         * @param string $nicolive_id ニコ生の放送 ID (ex: lv329283198・ch2646436)
         * @param string $cookie_file Cookie のあるファイル
         * @return array 処理結果
         */
        $getSession = function(string $nicolive_id, string $cookie_file): array {

            // ベース URL
            $nicolive_baseurl = 'https://live2.nicovideo.jp/watch/';

            // HTML を取得
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $nicolive_baseurl.$nicolive_id);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
            curl_setopt($curl, CURLOPT_USERAGENT, Utils::getUserAgent()); // ユーザーエージェントを送信
            if (file_exists($cookie_file)) curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); // Cookie を送信する（ファイルがあれば）
            $nicolive_html = curl_exec($curl);  // リクエストを実行
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);  // ステータスコードを取得
            curl_close($curl);
    
            // ステータスコードを判定
            switch ($status_code) {
                // 200：OK
                case 200:
                    break;
                // 404：Not found
                case 404:
                    return ['error' => '指定された放送 ID は存在しません。(HTTP Error 404)'];
                // 500：Internal Server Error
                case 500:
                    return ['error' => '現在、ニコニコ実況で障害が発生しています。(HTTP Error 500)'];
                // 503：Service Unavailable
                case 503:
                    return ['error' => '現在、ニコニコ実況はメンテナンス中です。(HTTP Error 503)'];
                // それ以外のステータスコード
                default:
                    return ['error' => "現在、ニコニコ実況でエラーが発生しています。(HTTP Error {$status_code})"];
            }
            
            // json をスクレイピング
            preg_match('/<script id="embedded-data" data-props="(.*?)"><\/script>/s', $nicolive_html, $result);

            // $result が存在しない
            if (!isset($result[1])) {
                return ['error' => 'ニコニコ実況の番組情報の取得に失敗しました。'];
            }

            // ニコ生の番組情報諸々が入った連想配列
            $nicolive_json = json_decode(htmlspecialchars_decode($result[1]), true);

            return $nicolive_json;
        };

        // 情報を取得
        $nicolive_json = $getSession($nicolive_id, $this->cookie_file);


        // HTTP エラー
        if (isset($nicolive_json['error'])) {
            return $nicolive_json;
        }

        // 現在放送中 (ON_AIR) でないなら null を返す
        if ($nicolive_json['program']['status'] !== 'ON_AIR') {
            return null;
        }

        // ログイン利用で実際にログインされている、またはゲスト利用
        if ($nicolive_json['user']['isLoggedIn'] === true or $this->is_guest) {

            // 今のところ処理なし
        
        // ログイン利用だが実際にはログインされていない（セッション切れなど）
        } else {

            // ログイン処理を実行し、Cookie を保存する
            $this->login();

            // 再度情報を取得
            $nicolive_json = $getSession($nicolive_id, $this->cookie_file);
        }

        // タイトル
        $title = $nicolive_json['program']['title'];

        // 開始時間
        $begintime = $nicolive_json['program']['beginTime'];

        // 終了時間
        $endtime = $nicolive_json['program']['endTime'];

        // 放送 ID を上書き
        $nicolive_id = $nicolive_json['program']['nicoliveProgramId'];

        // ユーザー ID
        $user_id = (isset($nicolive_json['user']['id']) ? $nicolive_json['user']['id'] : null);

        // ユーザータイプ（ non・standard・premium のいずれか）
        $user_type = $nicolive_json['user']['accountType'];

        // ログインしているかどうか
        $is_login = $nicolive_json['user']['isLoggedIn'];

        // 視聴セッション構築用の WebSocket の URL
        $watchsession_url = $nicolive_json['site']['relive']['webSocketUrl'];


        // 連想配列を返す
        return [
            'title' => $title,
            'begintime' => $begintime,
            'endtime' => $endtime,
            'live_id' => $nicolive_id,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'is_login' => $is_login,
            'watchsession_url' => $watchsession_url,
        ];
    }

    
    /**
     * ニコニコ実況の過去ログを取得する
     * 過去ログが取得できたら DPlayer 互換フォーマットの過去ログを、取得できなければエラーメッセージを返す
     *
     * @param string $nicojikkyo_id ニコニコ実況の ID
     * @param integer $start_timestamp 取得を開始する時刻のタイムスタンプ
     * @param integer $end_timestamp 取得を終了する時刻のタイムスタンプ
     * @return array [過去ログ（DPlayer 互換）or エラーメッセージ, 過去ログ API の URL]
     */
    public function getNicoJikkyoKakolog(string $nicojikkyo_id, int $start_timestamp, int $end_timestamp) :array {

        /**
         * ニコニコの色指定を 16 進数カラーコードに置換する
         * @param string $color ニコニコの色指定
         * @return ?string 16 進数カラーコード
         */
        $getCommentColor = function($color): ?string {
            $color_table = [
                'red' => '#E54256',
                'pink' => '#FF8080',
                'orange' => '#FFC000',
                'yellow' => '#FFE133',
                'green' => '#64DD17',
                'cyan' => '#39CCFF',
                'blue' => '#0000FF',
                'purple' => '#D500F9',
                'black' => '#000000',
                'white' => '#FFFFFF',
                'white2' => '#CCCC99',
                'niconicowhite' => '#CCCC99',
                'red2' => '#CC0033',
                'truered' => '#CC0033',
                'pink2' => '#FF33CC',
                'orange2' => '#FF6600',
                'passionorange' => '#FF6600',
                'yellow2' => '#999900',
                'madyellow' => '#999900',
                'green2' => '#00CC66',
                'elementalgreen' => '#00CC66',
                'cyan2' => '#00CCCC',
                'blue2' => '#3399FF',
                'marineblue' => '#3399FF',
                'purple2' => '#6633CC',
                'nobleviolet' => '#6633CC',
                'black2' => '#666666',
            ];
            if (isset($color_table[$color])) {
                return $color_table[$color];
            } else {
                return null;
            }
        };
    
        /**
         * ニコニコの位置指定を DPlayer の位置指定に置換する
         * @param string $position ニコニコの位置指定
         * @return ?string DPlayer の位置指定
         */
        $getCommentPosition = function($position): ?string {
            switch ($position) {
                case 'ue':
                    return 'top';
                case 'naka':
                    return 'right';
                case 'shita':
                    return 'bottom';
                default:
                    return null;
            }
        };


        // 過去ログ API の URL
        $kakologapi_sprintf= 'https://jikkyo.tsukumijima.net/api/kakolog/%s?starttime=%d&endtime=%d&format=json';
        $kakologapi_url = sprintf($kakologapi_sprintf, $nicojikkyo_id, $start_timestamp, $end_timestamp);

        // API から過去ログを取得
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $kakologapi_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
        curl_setopt($curl, CURLOPT_USERAGENT, Utils::getUserAgent()); // ユーザーエージェントを送信
        $kakolog_json = curl_exec($curl);  // リクエストを実行
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);  // ステータスコードを取得
        curl_close($curl);

        // ステータスコードを判定
        switch ($status_code) {
            // 500：Internal Server Error
            case 500:
                return ['過去ログ API でサーバーエラーが発生しました。過去ログ API に不具合がある可能性があります。(HTTP Error 500)', $kakologapi_url];
            // 503：Service Unavailable
            case 503:
                return ['現在、過去ログ API は一時的に利用できなくなっています。(HTTP Error 503)', $kakologapi_url];
        }

        // json をデコード
        $kakologs = json_decode($kakolog_json, true);

        // エラーが入ってたら処理中断、エラーメッセージと過去ログ API の URL を返す
        if (isset($kakologs['error'])) {
            return [$kakologs['error'], $kakologapi_url];
        }

        // 過去ログのコメントが空の場合は処理中断
        if (empty($kakologs['packet'])) {
            return ['この番組の過去ログは存在しないか、現在取得中です。', $kakologapi_url];
        }

        // 変換後のコメント
        $danmaku = [];

        // コメントごとに回す
        foreach ($kakologs['packet'] as $kakolog_) {

            $kakolog = $kakolog_['chat'];

            // content が存在しないコメントを除外
            if (!isset($kakolog['content'])) {
                continue;
            }

            // 削除されているコメントを除外
            if (isset($kakolog['deleted'])) {
                continue;
            }

            // 運営コメントは今のところ全て弾く（今後変えるかも）
            if (preg_match('/\/[a-z]+ /', $kakolog['content'])) {
                continue;
            }

            // 色・位置
            $color = '#FFFFFF';  // 色のデフォルト
            $position = 'right';  // 位置のデフォルト
            if (isset($kakolog['mail']) and !empty($kakolog['mail'])) {
                // コマンドをスペースで区切って配列にしたもの
                $command = explode(' ', str_replace('184', '', $kakolog['mail']));
                foreach ($command as $item) {  // コマンドごとに
                    if ($getCommentColor($item) !== null) {
                        $color = $getCommentColor($item);
                    }
                    if ($getCommentPosition($item) !== null) {
                        $position = $getCommentPosition($item);
                    }
                }
            }

            // ユーザーID
            if (isset($kakolog['user_id'])) {
                $user_id = $kakolog['user_id'];
            } else {
                $user_id = '';
            }
            
            // コメント時間（秒単位）を算出
            $time = floatval(($kakolog['date'] - $start_timestamp).'.'.($kakolog['date_usec'] ?? '0'));

            // コメント時間を遅延
            $time += intval(isSettingsItem('comment_file_delay'));

            // 万が一コメント時間がマイナスになった場合は 0 に設定
            if ($time < 0) $time = 0;

            // 配列の末尾に追加
            $danmaku[] = [
                'time' => $time,                // コメント時間（秒単位）
                'type' => $position,            // コメントの場所
                'color' => $color,              // コメントの色を
                'author' => $user_id,           // ユーザーID
                'text' => $kakolog['content'],  // コメント本文
            ];
        }

        // 変換した過去ログと過去ログ API の URL を返す
        return [$danmaku, $kakologapi_url];
    }


    /**
     * 実況勢いをちくわちゃんランキング（ちくラン）から取得
     * サーバーに負荷をかけないように、実行結果はファイルにキャッシュし 1 分以上経ったら更新する
     *
     * @param string $nicojikkyo_id 実況ID
     * @return string 実況勢い
     */
    public function getNicoJikkyoIkioi(string $nicojikkyo_id): string {

        // jikkyo_ikioi.json を更新
        $update = function($table, $jikkyo_ikioi_file): void {

            $chikuran_url = 'http://www.chikuwachan.com/live/index.cgi?search=%E3%83%8B%E3%82%B3%E3%83%8B%E3%82%B3%E5%AE%9F%E6%B3%81';

            // ちくランの検索結果から実況勢いを取得
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $chikuran_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // これがないと HTTPS で接続できない
            curl_setopt($curl, CURLOPT_USERAGENT, Utils::getUserAgent()); // ユーザーエージェントを送信
            $response = curl_exec($curl);  // リクエストを実行
            curl_close($curl);

            // HTML を解析
            $document = new Document($response);

            // 配列を用意
            $jikkyo_ikioi = [];

            // 実況チャンネルごとに勢いを取得
            foreach ($table as $nicojikkyo_id => $nicochannel_id) {

                // 実況勢いを取得
                $jikkyo_ikioi_elem = $document->querySelector("div#comm_{$nicochannel_id} div.counts div.active");

                // 実況勢いを配列に追加
                if ($jikkyo_ikioi_elem !== null) {
                    $jikkyo_ikioi[$nicojikkyo_id] = strval($jikkyo_ikioi_elem->textContent);
                } else {
                    // 実況勢いはないけど、.box_active は存在する
                    if ($document->querySelector("div#comm_{$nicochannel_id} div.counts div.box_active") !== null) {
                        $jikkyo_ikioi[$nicojikkyo_id] = '0';  // 常に 0 に設定
                    } else {
                        $jikkyo_ikioi[$nicojikkyo_id] = '-';  // その実況チャンネルの勢いが取得できなかった
                    }
                }
            }
            
            // jikkyo_ikioi.json に保存
            file_put_contents($jikkyo_ikioi_file, json_encode($jikkyo_ikioi, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        };

        // jikkyo_ikioi.json が存在しない or 更新されてから 1 分以上経っている
        clearstatcache();  // キャッシュを削除（重要）
        if ((file_exists($this->jikkyo_ikioi_file) === false) or
            (time() - filemtime($this->jikkyo_ikioi_file) >= 60)) {

            // jikkyo_ikioi.json を更新
            $update($this->table, $this->jikkyo_ikioi_file);
        }

        // jikkyo_ikioi.json から実況勢いを取得
        $jikkyo_ikioi = json_decode(file_get_contents($this->jikkyo_ikioi_file), true);

        // 指定された実況チャンネルのものを返す
        if (isset($jikkyo_ikioi[$nicojikkyo_id])) {
            $jikkyo_ikioi_number = $jikkyo_ikioi[$nicojikkyo_id];
        } else {
            $jikkyo_ikioi_number = '-';
        }

        return $jikkyo_ikioi_number;
    }
}
