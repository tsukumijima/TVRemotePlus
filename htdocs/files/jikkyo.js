
// ---------------------------------------------------------------------
// ニコ生チャンネルにリニューアルされる新ニコニコ実況対応用のコード


class Scroll {

    /**
     * コンストラクタ
     * @param {HTMLElement} comment_draw_box コメントリスト
     * @param {HTMLElement} comment_scroll コメントスクロールボタン
     * @param {Function} scroll_amount スクロール量
     * @param {Boolean} is_file ファイル再生かどうか
     */
    constructor(comment_draw_box, comment_scroll, scroll_amount, is_file = false) {

        // 引数をセット
        this.comment_draw_box = comment_draw_box;
        this.comment_scroll = comment_scroll;
        this.getScrollAmount = scroll_amount
        this.is_file = is_file

        // 自動スクロールモードか
        this.is_autoscroll_mode = true;

        // 自動スクロール中か
        this.is_autoscroll_now = false;

        // setTimeout の ID
        this.is_autoscroll_now_timer;
    }

    /**
     * コメントをスクロールする
     * @param {Boolean} animation アニメーションするかどうか
     */
    scroll(animation = false) {

        // スクロールする余地が存在する
        if (!(Math.ceil(this.comment_draw_box.scrollHeight) === Math.ceil(this.comment_draw_box.scrollTop + this.comment_draw_box.clientHeight)) || this.is_file) {

            // 既に自動スクロール中
            if (this.is_autoscroll_now === true) {
                // タイマーをクリア
                clearTimeout(this.is_autoscroll_now_timer);
                // イベントを削除
                this.comment_draw_box.onscroll = null;
            }

            // ボタンを非表示
            this.comment_scroll.classList.remove('show');

            // スクロール中フラグをオン
            this.is_autoscroll_now = true;

            // スクロール
            this.comment_draw_box.scrollTo({
                top: this.getScrollAmount(),
                left: 0,
                behavior: (animation ? 'smooth': 'auto'),  // アニメーション
            });

            // スクロールを停止して 100ms 後に終了とする
            this.comment_draw_box.onscroll = (event) => {
                clearTimeout(this.is_autoscroll_now_timer);
                this.is_autoscroll_now_timer = setTimeout(() => {
                    // スクロール中フラグをオフ
                    this.is_autoscroll_now = false;
                    // イベントを削除
                    this.comment_draw_box.onscroll = null;
                }, 100);
            };
        }
    }

    /**
     * 手動スクロールでかつ下まで完全にスクロールされているかどうか
     * 参考: https://developer.mozilla.org/ja/docs/Web/API/Element/scrollHeight
     * @return {Boolean} 手動スクロールでかつ下まで完全にスクロールされているなら true
     */
    isManualBottomScroll() {
        // 自動スクロール状態でない & ファイル再生状態でない
        if (this.is_autoscroll_mode === false && this.is_file === false) {
            const height = Math.ceil(this.comment_draw_box.scrollHeight); // ボックス全体の高さ
            const scroll = Math.ceil(this.comment_draw_box.scrollTop + this.comment_draw_box.clientHeight);  // スクロールで見えている部分の下辺
            const diff = Math.abs(height - scroll); // 絶対値を取得
            // 差が 8 以内なら（イコールだとたまにずれる時に判定漏れが起きる）
            if (diff <= 8) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * コメントリストが手動スクロールされたときのイベント
     */
    manualScrollEvent() {

        let timeout;
        this.comment_draw_box.addEventListener('scroll', (event) => {

            // setTimeout() がセットされていたら無視
            if (timeout) return;
            timeout = setTimeout(() => {
                timeout = 0;

                // 自動スクロール中でない
                if (this.is_autoscroll_now === false) {

                    // 手動スクロールでかつ下まで完全にスクロールされている場合は自動スクロールに戻す
                    if (this.isManualBottomScroll()) {

                        // 自動スクロール中
                        this.is_autoscroll_mode = true;

                        // ボタンを非表示
                        this.comment_scroll.classList.remove('show');

                    } else {

                        // 手動スクロール中
                        this.is_autoscroll_mode = false;

                        // ボタンを表示
                        this.comment_scroll.classList.add('show');
                    }
                }

            }, 100);  // 100ms ごと
        });
    }

    /**
     * コメントスクロールボタンがクリックされた時のイベント
     */
    clickScrollButtonEvent() {

        this.comment_scroll.addEventListener('click', (event) => {

            // ボタンを非表示
            this.comment_scroll.classList.remove('show');

            // スクロール
            this.scroll(true);

            // 700ms 後に自動スクロールに戻す
            setTimeout(() => {
                this.is_autoscroll_mode = true;
            }, 700);
        });
    }

    /**
     * ウインドウがリサイズされたときのイベント
     */
    windowResizeEvent() {

        window.addEventListener('resize', (event) => {

            // 500ms 後に実行
            setTimeout(() => {

                // ボタンを非表示
                this.comment_scroll.classList.remove('show');

                // スクロール
                this.scroll();

                // 自動スクロールに戻す
                this.is_autoscroll_mode = true;

            }, 500);
        });
    }
}


/**
 * 一旦この関数に集約
 * @param {string} stream_state ストリームの状態 (ONAir / File / Offline) 
 */
function newNicoJKAPIBackend(stream_state) {

    switch (stream_state) {

        // ライブ配信
        case 'ONAir':
            return newNicoJKAPIBackendONAir();

        // ファイル再生
        case 'File':
            return newNicoJKAPIBackendFile();

        // オフライン
        default:
            return newNicoJKAPIBackendOffline();
    }

}


/**
 * ライブ配信向け
 */
function newNicoJKAPIBackendONAir() {

    // 視聴セッション WebSocket
    let watchsession;

    // コメントセッション WebSocket
    let commentsession;

    // コメントセッション WebSocket に接続できたかどうか
    let commentsession_connectable;

    // コメントセッション WebSocket への接続情報など
    let commentsession_info;
  
    // 各要素
    // コメントボックス
    let comment_draw_box = null;

    // コメントボックスの実際の描画領域
    let comment_draw_box_real = null;

    // コメントスクロールボタン
    let comment_scroll = null;

    // コメント（ライブ配信）
    let comment_live = null;

    // コメント数
    let comment_counter = null;

    // DOM 構築を待ってから要素を取得
    window.addEventListener('DOMContentLoaded', (event) => {
        if (settings['comment_show']) {  // コメントリストが表示されている場合のみ
            comment_draw_box = document.getElementById('comment-draw-box');
            comment_draw_box_real = comment_draw_box.getElementsByTagName('tbody')[0];
            comment_scroll = document.getElementById('comment-scroll');
        }
    });

    /**
     * 視聴セッションサーバーに接続し、コメントサーバーへの接続情報を取得する
     * 接続できたらコメントサーバーへの接続情報の入ったオブジェクトを、
     * 接続できなかったらエラーメッセージを返す
     * @return {array} [接続成功: true・接続失敗: false, コメントサーバーへの接続情報の入ったオブジェクト or エラーメッセージ]
     */
    async function getCommentServerInfo() {

        // 視聴セッション情報を取得
        // await で Ajax が完了するまで待つ
        let watchsession_info;

        try {
            watchsession_info = await $.ajax({
                url: '/api/jikkyo/' + stream,
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token')},
                dataType: 'json',
                cache: false,
            });
        } catch (error) {
            return [false, `視聴セッション情報の取得に失敗しました。(${error.statusText})`];
        }

        // 視聴セッション情報を取得できなかった
        if (watchsession_info.result === 'error') {
            return [false, watchsession_info.message];  // 接続失敗
        }

        // 視聴セッション WebSocket を開く
        watchsession = new WebSocket(watchsession_info.session.watchsession_url);

        // 視聴セッション WebSocket を開いたとき
        watchsession.addEventListener('open', (event) => {

            // 視聴セッションをリクエスト
            watchsession.send(JSON.stringify({
                'type': 'startWatching',
                'data': {
                    'stream': {
                        'quality': 'super_high',
                        'protocol': 'hls',
                        'latency': 'low',
                        'chasePlay': false,
                    },
                    'room': {
                        'protocol': 'webSocket',
                        'commentable': true,
                    },
                    'reconnect': false,
                },
            }));
        });

        // コメントサーバーへの接続情報を取得
        const commentsession_info = await new Promise((resolve, reject) => {

            // 視聴セッション WebSocket でメッセージを受信したとき
            watchsession.addEventListener('message', (event) => {

                // 受信したメッセージ
                const message = JSON.parse(event.data);

                switch (message.type) {

                    // ping-pong
                    case 'ping':

                        // pong を返してセッションを維持する
                        // 送り返さなかった場合勝手にセッションが閉じられる
                        watchsession.send(JSON.stringify({
                            'type': 'pong',
                        }));

                    break;

                    // 座席情報
                    case 'seat':

                        // keepIntervalSec の秒数ごとに keepSeat を送信して座席を維持する
                        let keepseat = setInterval(() => {
                            // セッションがまだ開いていれば
                            if (watchsession.readyState === 1) {
                                // 座席を維持
                                watchsession.send(JSON.stringify({
                                    'type': 'keepSeat',
                                }));
                            // setInterval を解除
                            } else {
                                clearInterval(keepseat);
                            }
                        }, message.data.keepIntervalSec * 1000);

                    break;

                    // 統計情報
                    case 'statistics':

                        // 総コメント数を取得
                        // スレッド開始からの合計なので、毎分・毎時間のコメント数ではない
                        let comment_count = message.data.comments;

                        // コメント数を表示
                        if (comment_counter === null) {
                            comment_counter = document.getElementById('comment-counter');
                        }
                        comment_counter.textContent = `コメント数: ${comment_count}`;

                    break;

                    // 部屋情報（実際には統合されていて、全てアリーナ扱いになっている）
                    case 'room':

                        // コメントサーバーへの接続情報の入ったオブジェクトを返す
                        // デバッグ用で実際には使わないものもある
                        resolve({
                            // 視聴セッション情報
                            'title': watchsession_info.session.title,
                            'begintime': watchsession_info.session.begintime,
                            'endtime': watchsession_info.session.endtime,
                            'live_id': watchsession_info.session.live_id,
                            'user_id': watchsession_info.session.user_id,
                            'user_type': watchsession_info.session.user_type,
                            'is_login': watchsession_info.session.is_login,
                            'watchsession_url': watchsession_info.session.watchsession_url,
                            // コメントサーバーへの接続情報
                            'thread_id': message.data.threadId,
                            'postkey': (message.data.yourPostKey ? message.data.yourPostKey : null),
                            'commentsession_url': message.data.messageServer.uri,
                        });

                    break;

                    // エラー情報
                    case 'error':

                        let error;

                        // エラー情報
                        switch (message.data.code) {

                            case 'CONNECT_ERROR':
                                error = 'コメントサーバーに接続できません。';
                            break;
                            case 'CONTENT_NOT_READY':
                                error = 'ニコニコ実況が配信できない状態です。';
                            break;
                            case 'NO_THREAD_AVAILABLE':
                                error = 'コメントスレッドを取得できません。';
                            break;
                            case 'NO_ROOM_AVAILABLE':
                                error = 'コメント部屋を取得できません。';
                            break;
                            case 'NO_PERMISSION':
                                error = 'API にアクセスする権限がありません。';
                            break;
                            case 'NOT_ON_AIR':
                                error = 'ニコニコ実況が放送中ではありません。';
                            break;
                            case 'BROADCAST_NOT_FOUND':
                                error = 'ニコニコ実況の配信情報を取得できません。';
                            break;
                            case 'INTERNAL_SERVERERROR':
                                error = 'ニコニコ実況でサーバーエラーが発生しています。';
                            break;
                            default:
                                error = `ニコニコ実況でエラーが発生しています。(${message.data.code})`;
                            break;
                        }

                        // エラー情報を表示
                        console.log(`error occurred. code: ${message.data.code}`);
                        if (dp.danmaku.showing) {
                            dp.notice(error);
                        }

                    break;

                    // 視聴セッションが閉じられた（4時のリセットなど）
                    case 'disconnect':

                        let disconnect;

                        // イベントを削除
                        if (watchsession) watchsession.onclose = null;

                        // 接続切断の理由
                        switch (message.data.reason) {

                            case 'TAKEOVER':
                                disconnect = 'ニコニコ実況の番組から追い出されました。';
                            break;
                            case 'NO_PERMISSION':
                                disconnect = 'ニコニコ実況の番組の座席を取得できませんでした。';
                            break;
                            case 'END_PROGRAM':
                                disconnect = 'ニコニコ実況がリセットされたか、コミュニティの番組が終了しました。';
                            break;
                            case 'PING_TIMEOUT':
                                disconnect = 'コメントサーバーとの接続生存確認に失敗しました。';
                            break;
                            case 'TOO_MANY_CONNECTIONS':
                                disconnect = 'ニコニコ実況の同一ユーザからの接続数上限を越えています。';
                            break;
                            case 'TOO_MANY_WATCHINGS':
                                disconnect = 'ニコニコ実況の同一ユーザからの視聴番組数上限を越えています。';
                            break;
                            case 'CROWDED':
                                disconnect = 'ニコニコ実況の番組が満席です。';
                            break;
                            case 'MAINTENANCE_IN':
                                disconnect = 'ニコニコ実況はメンテナンス中です。';
                            break;
                            case 'SERVICE_TEMPORARILY_UNAVAILABLE':
                                disconnect = 'ニコニコ実況で一時的にサーバーエラーが発生しています。';
                            break;
                            default:
                                disconnect = `ニコニコ実況との接続が切断されました。(${message.data.reason})`;
                            break;
                        }

                        // 接続切断の理由を表示
                        console.log(`disconnected. reason: ${message.data.reason}`);
                        if (dp.danmaku.showing) {
                            dp.notice(disconnect);
                        }

                        // 5 秒ほど待ってから再接続
                        setTimeout(() => {

                            //　再接続表示
                            if (dp.danmaku.showing) {
                                dp.notice('ニコニコ実況に再接続しています…');
                            }

                            // プレイヤー側のコメント機能をリロード
                            restart();

                        }, 5000);

                    break;
                }
            });

            // 視聴セッションの接続が閉じられたとき（ネットワークが切断された場合など）
            // イベントを無効化できるように敢えて onclose で実装する
            watchsession.onclose = (event) => {

                // 接続切断の理由を表示
                console.log(`disconnected. code: ${event.code}`);
                if (dp.danmaku.showing) {
                    dp.notice(`ニコニコ実況との接続が切断されました。(code: ${event.code})`);
                }

                // 10 秒ほど待ってから再接続
                // ニコ生側から切断された場合と異なりネットワークが切断された可能性が高いので、間を多めに取る
                setTimeout(() => {

                    //　再接続表示
                    if (dp.danmaku.showing) {
                        dp.notice('ニコニコ実況に再接続しています…');
                    }

                    // プレイヤー側のコメント機能をリロード
                    restart()

                }, 10000);
            };

        });

        return [true, commentsession_info];
    }

    /**
     * コメントを受信・描画する
     * @param {object} options DPlayer から渡されるコールバック等が入ったオブジェクト
     */
    function receiveComment(options) {

        // Scroll クラスのインスタンス
        let scroll_instance = new Scroll(comment_draw_box, comment_scroll, () => {return comment_draw_box.scrollHeight});

        // コメントサーバーの WebSocket
        commentsession = new WebSocket(commentsession_info.commentsession_url, 'msg.nicovideo.jp#json');

        // WebSocket を開いたとき
        commentsession.addEventListener('open', (event) => {

            // コメントの送信をリクエスト
            commentsession.send(JSON.stringify([
                { 'ping': {'content': 'rs:0'} },
                { 'ping': {'content': 'ps:0'} },
                { 'ping': {'content': 'pf:0'} },
                { 'ping': {'content': 'rf:0'} },
                {
                    'thread':{
                        'thread': commentsession_info.thread_id,
                        'threadkey': commentsession_info.postkey,
                        'version': '20061206',
                        'user_id': commentsession_info.user_id,
                        'res_from': 0,
                        'with_global': 1,
                        'scores': 1,
                        'nicoru': 0,
                    }
                },
            ]));
        });

        // WebSocket でメッセージを受信したとき
        // コメントを描画する
        commentsession.addEventListener('message', async (event) => {

            // コメント送信リクエストの結果
            if (JSON.parse(event.data).thread !== undefined) {

                // スレッド情報
                const thread = JSON.parse(event.data).thread;

                // リクエスト成功
                if (thread.resultcode === 0) {

                    // 接続成功のコールバックを DPlayer に通知
                    console.log(commentsession_info);
                    options.success([{}]);  // 空のコメントを入れておく

                // リクエスト失敗
                } else {

                    // 接続失敗のコールバックを DPlayer に通知
                    const message = 'コメントサーバーに接続できませんでした。';
                    console.error('Error: ' + message);
                    if (dp.danmaku.showing) {
                        options.error(message);  // エラーメッセージを送信
                    } else {
                        options.success([{}]);  // 成功したことにして通知を抑制
                    }
                }
            }

            // コメントを取得
            const comment = JSON.parse(event.data).chat;

            // コメントがない or 広告用など特殊な場合は弾く
            if (comment === undefined ||
                comment.content === undefined ||
                comment.content.match(/\/[a-z]+ /)) {
                return;
            }

            // 自分のコメントも表示しない
            if (comment.yourpost && comment.yourpost === 1) {
                return;
            }

            // コメントがコメントフィルターに設定されたキーワードと一致したら描画しない
            const comment_filter = JSON.parse(localStorage.getItem('tvrp-comment-filter') || '[]');
            for (const keyword of comment_filter) {
                // キーワードが空でないか（空の場合、全てのコメントをフィルタリングしてしまう）
                if (keyword !== '') {
                    // コメント内にキーワードが部分一致で含まれている
                    if (comment.content.includes(keyword)) {
                        // console.log(`コメントをフィルタリングしました(keyword: ${keyword}): ${comment.content}`);
                        return;
                    }
                }
            }

            // 色・位置
            let color = '#FFFFFF';  // 色のデフォルト
            let position = 'right';  // 位置のデフォルト
            if (comment.mail !== undefined && comment.mail !== null) {
                // コマンドをスペースで区切って配列にしたもの
                const command = comment.mail.replace('184', '').split(' ');
                for (const item of command) {  // コマンドごとに
                    if (getCommentColor(item) !== null) {
                        color = getCommentColor(item);
                    }
                    if (getCommentPosition(item) !== null) {
                        position = getCommentPosition(item);
                    }
                }
            }

            // 描画用の配列に変換
            const time = moment.unix(comment.date).format('HH:mm:ss');
            const danmaku = {
                text: comment.content,
                color: color,
                type: position,
            }

            // HLS 配信に伴う遅延（指定された秒数）分待ってから描画
            await new Promise(r => setTimeout(r, settings.comment_delay * 1000));

            // コメントリストが表示されている場合のみ
            if (settings['comment_show']) {

                // 768px 以上のみ
                if (document.body.clientWidth > 768){

                    // コメントリストに表示する
                    comment_draw_box_real.insertAdjacentHTML('beforeend',`
                        <tr class="comment-live">
                            <td class="time" align="center">${time}</td>
                            <td class="comment">${danmaku.text}</td>
                        </tr>`
                    );

                    // スクロールする（自動スクロールが有効な場合のみ）
                    if (scroll_instance.is_autoscroll_mode) {
                        scroll_instance.scroll();
                    }

                    // 初回のみ .comment-live のエレメントを取得
                    if (comment_live === null) {
                        comment_live = document.getElementsByClassName('comment-live');
                    }

                    // コメント数が 100 個を超えたら古いコメントを削除
                    if (comment_live.length > 100){
                        comment_live[0].remove();
                    }
                }
            }

            // コメント描画 (再生時のみ)
            if (!dp.video.paused){
                dp.danmaku.draw(danmaku);
            }
        });

        // コメントリストが表示されている場合のみ
        if (settings['comment_show']) {

            // コメントリストが手動スクロールされたときのイベントを設定
            scroll_instance.manualScrollEvent();

            // コメントスクロールボタンがクリックされたときのイベントを設定
            scroll_instance.clickScrollButtonEvent();

            // ウインドウがリサイズされたときのイベントを設定
            scroll_instance.windowResizeEvent();
        }
    }

    /**
     * コメントを送信する
     * @param {object} options DPlayer から渡されるコールバック等が入ったオブジェクト
     */
    function sendComment(options) {

        // 色
        const color_table = {
            '16777215': 'white',
            '15024726': 'red',
            '16769331': 'yellow',
            '6610199': 'green',
            '3788031': 'cyan',
            '13959417': 'purple',
        };

        // 位置
        const position_table = {
            '0': 'naka',
            '1': 'ue',
            '2': 'shita',
        };

        // vpos を計算 (10ミリ秒単位)
        const vpos = Math.floor(new Date().getTime() / 10) - (commentsession_info.begintime * 100);

        // コメントを送信
        watchsession.send(JSON.stringify({
            'type': 'postComment',
            'data': {
                'text': options.data.text,  // コメント本文
                'color': color_table[options.data.color.toString()],  // コメントの色
                'position': position_table[options.data.type.toString()],  // コメントの位置
                'vpos': vpos,  // 開始時間からの累計秒（10ミリ秒単位）
                'isAnonymous': true,  // 匿名コメント (184)
            }
        }));

        // コメント送信のレスポンス
        // onmessage なのはピンポイントでイベントを無効化できるため
        watchsession.onmessage = (event) => {

            // 受信したメッセージ
            const message = JSON.parse(event.data);

            switch (message.type) {

                // postCommentResult
                // これが送られてくる → コメント送信に成功している
                case 'postCommentResult':

                    // コメント成功のコールバックを DPlayer に通知
                    options.success();

                    // イベントを解除
                    watchsession.onmessage = null;

                break;

                // error
                // コメント送信直後に error が送られてきた → コメント送信に失敗している
                case 'error':

                    // エラーメッセージ
                    let error;
                    switch (message.data.code) {

                        case 'INVALID_MESSAGE':
                            error = 'コメント内容が無効です。';
                        break;
                        case 'COMMENT_POST_NOT_ALLOWED':
                            error = 'コメントするにはニコニコにログインしてください。';
                        break;
                        case 'COMMENT_LOCKED':
                            error = 'コメントがロックされています。';
                        break;
                        default:
                            error = `コメントの送信に失敗しました。(${message.data.code})`;
                        break;
                    }

                    // コメント失敗のコールバックを DPlayer に通知
                    options.error(error);

                    // イベントを解除
                    watchsession.onmessage = null;

                break;
            }
        }
    }

    // ストリームの更新イベントを受け取ったときのイベント
    function restart() {

        // WebSocket が開かれていれば閉じる
        if (watchsession) watchsession.onclose = null;
        if (watchsession) watchsession.close();
        if (commentsession) commentsession.close();

        // プレイヤー側のコメント機能をリロード
        dp.danmaku.dan = [];
        dp.danmaku.clear();
        dp.danmaku.load();

        // イベント自身を削除
        document.getElementById('status').removeEventListener('update', restart);
    }

    /**
     * ニコニコの色指定を 16 進数カラーコードに置換する
     * @param {string} color ニコニコの色指定
     * @return {string} 16 進数カラーコード
     */
    function getCommentColor(color) {
        const color_table = {
            'red': '#E54256',
            'pink': '#FF8080',
            'orange': '#FFC000',
            'yellow': '#FFE133',
            'green': '#64DD17',
            'cyan': '#39CCFF',
            'blue': '#0000FF',
            'purple': '#D500F9',
            'black': '#000000',
            'white': '#FFFFFF',
            'white2': '#CCCC99',
            'niconicowhite': '#CCCC99',
            'red2': '#CC0033',
            'truered': '#CC0033',
            'pink2': '#FF33CC',
            'orange2': '#FF6600',
            'passionorange': '#FF6600',
            'yellow2': '#999900',
            'madyellow': '#999900',
            'green2': '#00CC66',
            'elementalgreen': '#00CC66',
            'cyan2': '#00CCCC',
            'blue2': '#3399FF',
            'marineblue': '#3399FF',
            'purple2': '#6633CC',
            'nobleviolet': '#6633CC',
            'black2': '#666666',
        };
        if (color_table[color] !== undefined) {
            return color_table[color];
        } else {
            return null;
        }
    }

    /**
     * ニコニコの位置指定を DPlayer の位置指定に置換する
     * @param {string} position ニコニコの位置指定
     * @return {string} DPlayer の位置指定
     */
    function getCommentPosition(position) {
        switch (position) {
            case 'ue':
                return 'top';
            case 'naka':
                return 'right';
            case 'shita':
                return 'bottom';
            default:
                return null;
        }
    }

    // ページを閉じる/移動する前に WebSocket を閉じる
    // しなくても勝手に閉じられる気はするけど一応
    window.addEventListener('beforeunload', () => {
        if (watchsession) watchsession.onclose = null;
        if (watchsession) watchsession.close();
        if (commentsession) commentsession.close();
    });

    return {

        // コメント受信時
        // 正確には最初のプレイヤー読み込み時のみ発火
        read: (options) => {

            // コメントサーバーへの接続情報を取得してから
            getCommentServerInfo().then(([commentsession_connectable_, commentsession_info_]) => {

                // 他からも見れるように上のスコープに配置
                commentsession_connectable = commentsession_connectable_;
                commentsession_info = commentsession_info_;

                // 再起動イベントを追加
                document.getElementById('status').addEventListener('update', restart);

                // 視聴セッションを取得できていれば
                if (commentsession_connectable) {

                    // コメントを受信・描画する
                    receiveComment(options);

                // 視聴セッションを取得できなかった
                } else {

                    // 接続失敗のコールバックを DPlayer に通知
                    console.error('Error: ' + commentsession_info);
                    if (dp.danmaku.showing) {
                        options.error(commentsession_info);  // エラーメッセージを送信
                    } else {
                        options.success([{}]);  // 成功したことにして通知を抑制
                    }
                }
            });
        },

        // コメント送信時
        send: (options) => {

            // 視聴セッションを取得できていれば
            if (commentsession_connectable) {

                // コメントを送信する
                sendComment(options);

            // 視聴セッションを取得できなかった
            } else {

                // コメント失敗のコールバックを DPlayer に通知
                options.error(commentsession_info);
            }
        }
    }
}


/**
 * ファイル再生向け
 */
function newNicoJKAPIBackendFile() {
  
    // 各要素
    // コメントボックス
    let comment_draw_box = null;

    // コメントボックスの実際の描画領域
    let comment_draw_box_real = null;

    // コメントスクロールボタン
    let comment_scroll = null;

    // コメント（ファイル再生）
    let comment_file = null;

    // コメント数
    let comment_counter = null;

    // 進捗バー
    let progressbar = null;

    /**
     * 最も近い配列の要素のインデックスを取得する
     * 参考: https://qiita.com/shuuuuun/items/f0031d710ca50b21177a
     * @param {array} array 調べたい配列
     * @param {number} search 調べたい値
     */
    function getClosestArrayElementIndex(array, search) {
        let diff = [];
        let index = 0;
        if (array.length !== 0){
            array.forEach((val, i) => {
                if (val <= search) {  // 調べたい値より大きい値は弾く
                    diff[i] = Math.abs(search - val);
                    index = (diff[index] < diff[i]) ? index : i;
                }
            });
            return index;
        } else {
            return 0;
        }
    }

    // DOM 構築を待ってから実行
    window.addEventListener('DOMContentLoaded', (event) => {

        // コメントリストが表示されている場合のみ
        if (settings['comment_show']) {

            // 各要素を取得
            comment_draw_box = document.getElementById('comment-draw-box');
            comment_draw_box_real = comment_draw_box.getElementsByTagName('tbody')[0];
            comment_scroll = document.getElementById('comment-scroll');
            comment_counter = document.getElementById('comment-counter');
            progressbar = document.getElementById('progress');

            // コメントリストヘッダーの時間の幅を調整
            document.getElementById('comment-time').style.width = '62px';

            // コメント時間が入る配列
            // 現在の再生時間に一番近いコメントを探すのに使う
            let comment_time = [];

            // コメントの読み込みが完了したときのイベント
            dp.on('danmaku_load_end', (event) => {

                let html = [];

                // コメント数を表示
                comment_counter.textContent = `コメント数: ${dp.danmaku.dan.length}`;

                // コメントごとに実行
                for (const danmaku of dp.danmaku.dan) {

                    // コメントが空ならスルー
                    if (danmaku.text === '') {
                        continue;
                    }

                    // 再生時間を配列に追加
                    comment_time.push(danmaku.time);

                    // 分と秒を計算
                    let second = Math.floor(danmaku.time % 60);
                    let minutes = Math.floor(danmaku.time / 60);
                    if (second < 10) second = '0' + second;
                    if (minutes < 10) minutes = '0' + minutes;
                    let time = minutes + ':' + second;

                    // 末尾に追加
                    html.push(
                        `<tr class="comment-file" onclick="dp.video.dispatchEvent(new CustomEvent('commentclick', {detail: ${danmaku.time}}))">
                            <td class="time" align="center">${time}</td>
                            <td class="comment">${danmaku.text}</td>
                        </tr>`
                    );
                }

                // 標準モード
                if (settings['comment_list_performance'] !== 'light') {

                    // コメントをコメントリストに一気に挿入
                    comment_draw_box_real.innerHTML = html.join('');

                    // 初回のみ .comment-file のエレメントを取得
                    if (comment_file === null) {
                        comment_file = document.getElementsByClassName('comment-file');
                    }

                    /**
                     * スクロール量を取得
                     */
                    function getScrollTop() {

                        // 現在の再生時間に一番近い再生時間のコメントのインデックスを取得（ +1 はおまじない）
                        let comment_current_index = getClosestArrayElementIndex(comment_time, dp.video.currentTime) + 1;

                        // 現在の再生時間に一番近い再生時間のコメントの要素を取得
                        let comment_current = comment_file[comment_current_index];

                        // スクロール量を返す
                        // 5 (px) はパディング
                        if (comment_current !== undefined) {
                            return comment_current.offsetTop - comment_draw_box.clientHeight + 5;
                        } else {
                            return 0;
                        }
                    }

                // 軽量モード
                } else {

                    // 識別用のクラスを付与
                    document.querySelector('#comment-box').classList.add('comment-lightmode');

                    // Clusterize.js で高速スクロール
                    let clusterize = new Clusterize({
                        rows: html,
                        scrollElem: comment_draw_box,
                        contentElem: comment_draw_box_real,
                    });

                    /**
                     * スクロール量を取得
                     */
                    function getScrollTop() {

                        // 現在の再生時間に一番近い再生時間のコメントのインデックスを取得（ +1 はおまじない）
                        let comment_current_index = getClosestArrayElementIndex(comment_time, dp.video.currentTime) + 1;

                        // 軽量モードの場合、一番近い再生時間のコメントの要素が必ずしも存在するとは限らないため、
                        // (コメントのインデックス × コメントの高さ (28px)) + パディング (11px) で擬似的に親要素からの高さを取得する
                        let comment_current_time = (comment_current_index * 28) + 11;

                        // スクロール量を返す
                        return comment_current_time - comment_draw_box.clientHeight;
                    }
                }


                // Scroll クラスのインスタンス
                let scroll_instance = new Scroll(comment_draw_box, comment_scroll, getScrollTop, true);

                // コメントがクリックされたときのイベント
                function onCommentClick(event) {

                    // 動画をシーク
                    dp.seek(event.detail);

                    // 自動スクロール中
                    scroll_instance.is_autoscroll_mode = true;

                    // スクロール
                    scroll_instance.scroll();
                }

                // コメントがクリックされたときのイベントを設定
                // カスタムイベントを敢えてトリッキーな手法で使っている理由は、特に軽量モードで要素の追加/削除が発生し
                // インラインでないと確実にイベントを発火させられず、さらにシーク後に自動スクロールに戻す場合 Scroll クラスの
                // インスタンスが必要になるので、このあたりのコードに処理を戻す必要があるため
                dp.video.addEventListener('commentclick', onCommentClick);

                // 動画の再生時間が変更されたときのイベント
                let last_scrolltop = getScrollTop();
                function onTimeUpdate(event) {

                    // プログレスバーの進捗割合を設定
                    let percentage = (dp.video.currentTime / dp.video.duration) * 100;
                    progressbar.style.width = percentage + '%';

                    // 今取得した ScrollTop が以前と異なるなら
                    if (getScrollTop() !== last_scrolltop) {

                        // last_scrolltop を更新
                        last_scrolltop = getScrollTop();

                        // 取得した要素までスクロールする（自動スクロールが有効な場合のみ）
                        if (scroll_instance.is_autoscroll_mode) {
                            scroll_instance.scroll();
                        }
                    }
                }

                // 動画の再生時間が変更されたときのイベントを設定
                dp.video.addEventListener('timeupdate', onTimeUpdate);
                dp.video.addEventListener('seeking', onTimeUpdate);

                // コメントリストが手動スクロールされたときのイベントを設定
                scroll_instance.manualScrollEvent();

                // コメントスクロールボタンがクリックされたときのイベントを設定
                scroll_instance.clickScrollButtonEvent();

                // ウインドウがリサイズされたときのイベントを設定
                scroll_instance.windowResizeEvent();
            });
        }
    });

    return {

        // コメント受信時
        read: async (options) => {

            // コメントの取得を試みる
            let comment;
            try {
                comment = await $.ajax({
                    url: '/api/jikkyo/' + stream,
                    type: 'post',
                    data: {_csrf_token: Cookies.get('tvrp_csrf_token')},
                    dataType: 'json',
                    cache: false,
                });
            } catch (error) {
                if (dp.danmaku.showing) {
                    options.error(`過去ログの取得に失敗しました。(${error.statusText})`);
                } else {
                    options.success([{}]);  // 成功したことにして通知を抑制
                }
                return;
            }

            // コメントを取得できなかった
            if (comment.result === 'error') {
                if (dp.danmaku.showing) {
                    options.error(comment.message);  // 取得失敗
                } else {
                    options.success([{}]);  // 成功したことにして通知を抑制
                }
                return;
            }

            // コメントがコメントフィルターに設定されたキーワードと一致したら描画しない
            const comment_filter = JSON.parse(localStorage.getItem('tvrp-comment-filter') || '[]');
            // Array.filter() でコメントの入った配列をフィルタリング
            comment.kakolog = comment.kakolog.filter((danmaku) => {
                for (const keyword of comment_filter) {
                    // キーワードが空でないか（空の場合、全てのコメントをフィルタリングしてしまう）
                    if (keyword !== '') {
                        // コメント内にキーワードが部分一致で含まれている
                        if (danmaku.text.includes(keyword)) {
                            // console.log(`コメントをフィルタリングしました(keyword: ${keyword}): ${danmaku.text}`);
                            return false;  // 要素を削除
                        }
                    }
                }
                return true;  // 要素を残す
            });

            // 取得したコメントを DPlayer 側に送信
            options.success(comment.kakolog);
        },

        // コメント送信時
        send: (options) => {
            options.error('過去ログ再生中はコメントできません。');
        }
    }
}


/**
 * オフライン向け
 */
function newNicoJKAPIBackendOffline() {

    return {

        // コメント受信時
        read: (options) => {
            options.success([{}]);  // 空のコメントを入れておく
        },

        // コメント送信時
        send: (options) => {
            options.error('オフライン状態ではコメントできません。');
        }
    }
}

