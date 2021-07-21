$(function() {

    // 生放送・ファイル再生共通

    // ***** 一般 *****

    // clock() を毎秒実行する
    setInterval(clock, 1000);

    // 最初に実行
    if (Cookies.get('tvrp_twitter_settings')) {
        $('#tweet-status').html('<a id="tweet-logout" href="javascript:void(0)"><i class="fas fa-sign-out-alt"></i>ログアウト</a>');
    } else {
        $('#tweet-status').html('<a id="tweet-login" href="/tweet/auth"><i class="fas fa-sign-in-alt"></i>ログイン</a>');
    }

    // Twitterアカウント情報を読み込み
    twitter = {account_name:'ログインしていません', account_id:'', account_icon:'/files/account_default.jpg'};
    if (Cookies.get('tvrp_twitter_settings') != undefined) { // Cookieがあれば読み込む
        twitter = JSON.parse(Cookies.get('tvrp_twitter_settings'));
    }
    $('#tweet-account-icon').attr('src', twitter['account_icon']);
    $('#tweet-account-name').text(twitter['account_name']);
    if (twitter['account_id'] != '') { // スクリーンネームが空でなければ
        $('#tweet-account-name').attr('href', 'https://twitter.com/' + twitter['account_id']);
        $('#tweet-account-id').text('@' + twitter['account_id']);
    }

    // ***** 視聴数カウント・ストリーム状態把握 *****

    let status_hash = '';
    let status_data = {};
    function refresh_status() {
        $.ajax({
            url: '/api/status/' + stream,
            data: { 'hash': status_hash, 'hold': 1 },
            dataType: 'json',
            cache: false,
        }).done(function(data) {

            status_hash = data[0];
            if (data[1]) {
                status_data = data[1];
            }
            data = status_data;

            // 視聴数を表示
            document.getElementById('watching').textContent = data['watching'] + '人が視聴中';

            var status = document.getElementById('status').textContent;

            if (data['status'] == 'failed' && status != 'failed') {
                toastr.error('ストリームの開始に失敗しました…');
                $.ajax({
                    url: '/settings/',
                    type: 'post',
                    data: {
                        '_csrf_token': Cookies.get('tvrp_csrf_token'),
                        'state': 'Offline',
                        'stream': stream
                    },
                    cache: false,
                }).done(function(data) {
                    toastr.info('ストリームを終了します。');
                });
            }

            if (data['status'] == 'restart' && status != 'restart') {
                toastr.warning('ストリームが途中で中断しました…');
                $.ajax({
                    url: '/settings/',
                    type: 'post',
                    data: {
                        '_csrf_token': Cookies.get('tvrp_csrf_token'),
                        'state': 'ONAir',
                        'stream': stream,
                        'restart': 'true'
                    },
                    cache: false,
                }).done(function(data) {
                    var paused = dp.video.paused;
                    if (data['streamtype'] == 'progressive') {
                        dp.video.src = '/api/stream?_=' + time();
                        dp.initVideo(dp.video, 'normal');
                    } else {
                        dp.video.src = '/stream/stream' + stream + '.m3u8';
                        dp.initVideo(dp.video, 'hls');
                    }
                    if (!paused) {
                        dp.video.play();
                    } else {
                        dp.video.pause();
                    }
                    toastr.info('ストリームを再起動しています…');
                });
            }

            // 状態を隠しHTMLに書き出して変化してたらリロードする
            if ((data['status'] != status) && status != '') {

                if (document.getElementById('state').value === undefined) {
                    document.getElementById('state').value = data['state'];
                }

                // stateが同じの場合のみ読み込みし直し
                if ((document.getElementById('state').value == data['state']) &&
                    (data['state'] == 'ONAir' || (data['state'] == 'File' && data['status'] == 'onair'))) {

                    if (data['status'] == 'failed' || data['status'] != 'restart') {

                        // ストリームを読み込みし直す
                        var paused = dp.video.paused;
                        if (data['streamtype'] == 'progressive') {
                            dp.video.src = '/api/stream?_=' + time();
                            dp.video.load();
                            dp.initVideo(dp.video, 'normal');
                        } else {
                            dp.video.src = '/stream/stream' + stream + '.m3u8';
                            dp.video.load();
                            dp.initVideo(dp.video, 'hls');
                        }

                        try {
                            // 読み込みし直す前の再生状態を復元
                            if (!paused) {
                                dp.video.play();
                            } else {
                                dp.video.pause();
                            }
                        } catch (error) {
                            console.error(error);
                        }

                        // status が standby のみ
                        if (data['status'] === 'standby') {

                            // status の update イベントを発行
                            document.getElementById('status').dispatchEvent(new CustomEvent('update'));
                        }
                    }

                // それ以外は諸々問題があるので一旦リロード
                } else {
                    if (data['status'] == 'failed') {
                        setTimeout(function() {
                            $('#cover').addClass('open');
                            location.reload(true);
                        }, 3000);
                    } else {
                        $('#cover').addClass('open');
                        location.reload(true);
                    }
                }
            }

            document.getElementById('status').textContent = data['status'];
            // console.log('status: ' + data['status']);

            setTimeout(refresh_status, 1000);
        }).fail(function(data, status, error) {

            // エラーメッセージ
            message = 'failed to get status. status: ' + status + '\nerror: ' + error.message;
            console.error(message);
            setTimeout(refresh_status, 1000);
        });
    }
    refresh_status();


    // ***** 番組表・ストリーム一覧表示 *****

    // 要素を一括取得
    // 最初に全部取得しておいた方が負荷がかからない
    const epginfo_epg_title = document.getElementById('epg-title');
    const epginfo_epg_info = document.getElementById('epg-info');
    const epginfo_epg_channel = document.getElementById('epg-channel');
    const epginfo_epg_starttime = document.getElementById('epg-starttime');
    const epginfo_epg_to = document.getElementById('epg-to');
    const epginfo_epg_endtime = document.getElementById('epg-endtime');
    const epginfo_epg_next_title = document.getElementById('epg-next-title');
    const epginfo_epg_next_starttime = document.getElementById('epg-next-starttime');
    const epginfo_epg_next_to = document.getElementById('epg-next-to');
    const epginfo_epg_next_endtime = document.getElementById('epg-next-endtime');
    const epginfo_state = document.getElementById('state');
    const epginfo_ikioi = document.getElementById('ikioi');
    const epginfo_progress = document.getElementById('progress');
    let broadcast_elems = [];

    let epginfo_hash = '';
    let epginfo_data = {};
    function refresh_epginfo() {
        $.ajax({
            url: '/api/epginfo',
            data: { 'hash': epginfo_hash },
            dataType: 'json',
            cache: false,
        }).done(function(data) {

            epginfo_hash = data[0];
            if (data[1]) {
                epginfo_data = data[1];
            }
            data = epginfo_data;

            // 結果をHTMLにぶち込む

            // 高さフラグ
            if (document.getElementsByClassName('broadcast-title-ch1')[0]) {
                if (document.getElementsByClassName('broadcast-title-ch1')[0].textContent == '') {
                    var flg = true;
                } else {
                    var flg = false;
                }
            } else {
                var flg = false;
            }

            if (data['stream'][stream]['state'] == 'ONAir') {

                // 変化がある場合のみ書き換え
                if (epginfo_epg_starttime.innerHTML != data['stream'][stream]['starttime'] ||
                    epginfo_epg_title.innerHTML != data['stream'][stream]['program_name'] ||
                    epginfo_epg_channel.innerHTML != data['stream'][stream]['channel']) {

                    // 現在の番組
                    epginfo_epg_starttime.textContent = data['stream'][stream]['starttime'];
                    epginfo_epg_to.textContent = data['stream'][stream]['to'];
                    epginfo_epg_endtime.textContent = data['stream'][stream]['endtime'];

                    if (data['stream'][stream]['ch'] < 55) {
                        epginfo_epg_channel.textContent =
                            'Ch: ' + zeroPadding(data['stream'][stream]['ch_str'].replace('_', ''), 3) + ' ' + data['stream'][stream]['channel'];
                    } else {
                        epginfo_epg_channel.textContent =
                            'Ch: ' + zeroPadding(data['stream'][stream]['ch_str'].replace('_', ''), 3) + ' ' + data['stream'][stream]['channel'];
                    }
                    epginfo_epg_title.innerHTML = data['stream'][stream]['program_name'];
                    epginfo_epg_info.innerHTML = data['stream'][stream]['program_info'];

                    // 次の番組
                    epginfo_epg_next_title.innerHTML = data['stream'][stream]['next_program_name'];
                    epginfo_epg_next_starttime.textContent = data['stream'][stream]['next_starttime'];
                    epginfo_epg_next_to.textContent = data['stream'][stream]['to'];
                    epginfo_epg_next_endtime.textContent = data['stream'][stream]['next_endtime'];

                    // ON Air
                    epginfo_state.textContent = '● ON Air';
                    epginfo_state.style.color = '#007cff';
                }

                // 実況勢いは変化に関わらず常に更新
                epginfo_ikioi.textContent = `実況勢い: ${data['stream'][stream]['ikioi']}`;

            } else if (data['stream'][stream]['state'] == 'Offline') {

                // Offline
                epginfo_state.textContent = '● Offline';
                epginfo_state.style.color = 'gray';
            }

            // state を記録しておく
            epginfo_state.value = data['stream'][stream]['state'];

            // progressbar の割合を計算して代入
            let percentage = ((Math.floor(Date.now() / 1000) - data['stream'][stream]['timestamp']) / data['stream'][stream]['duration']) * 100;
            epginfo_progress.style.width = `${percentage}%`;

            // **** チャンネルリストの更新 ****
            for (key in data['onair']) {

                // そのチャンネルの要素が取得されていないなら取得
                if (!broadcast_elems[`ch${key}`]) {
                    broadcast_elems[`ch${key}`] = {
                        'wrap': document.querySelector(`#ch${key}`),
                        'content': document.querySelector(`#ch${key} .broadcast-content`),
                        'progress': document.querySelector(`#ch${key} .progress`),
                    };
                }

                // 変化がある場合のみ書き換え
                // 特に内容変わってもいないのに DOM を再構築するのはリソースの無駄
                if (broadcast_elems[`ch${key}`]['wrap'].dataset.starttime != data['onair'][key]['starttime'] ||
                    broadcast_elems[`ch${key}`]['wrap'].dataset.endtime != data['onair'][key]['endtime'] ||
                    broadcast_elems[`ch${key}`]['wrap'].dataset.title != data['onair'][key]['program_name']) {

                    // 書き換え用html
                    let html = 
                        `<div class="broadcast-channel-box">
                            <div class="broadcast-channel">` + broadcast_elems[`ch${key}`]['wrap'].dataset.channel + `</div>
                            <div class="broadcast-name-box">
                                <div class="broadcast-name">` + broadcast_elems[`ch${key}`]['wrap'].dataset.name + `</div>
                                <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi">` + data['onair'][key]['ikioi'] + `</span></div>
                            </div>
                        </div>
                        <div class="broadcast-title">
                            <span class="broadcast-start">` + data['onair'][key]['starttime'] + `</span>
                            <span class="broadcast-to">` + data['onair'][key]['to'] + `</span>
                            <span class="broadcast-end">` + data['onair'][key]['endtime'] + `</span>
                            <span class="broadcast-title-id">` + data['onair'][key]['program_name'] + `</span>
                        </div>
                        <div class="broadcast-next">
                            <span>` + data['onair'][key]['next_starttime'] + `</span>
                            <span>` + data['onair'][key]['to'] + `</span>
                            <span>` + data['onair'][key]['next_endtime'] + `</span>
                            <span>` + data['onair'][key]['next_program_name'] + `</span>
                        </div>`;

                    // 番組情報を書き換え
                    broadcast_elems[`ch${key}`]['content'].innerHTML = html;

                    // 番組情報を保存
                    broadcast_elems[`ch${key}`]['wrap'].dataset.starttime = data['onair'][key]['starttime'];
                    broadcast_elems[`ch${key}`]['wrap'].dataset.endtime = data['onair'][key]['endtime'];
                    broadcast_elems[`ch${key}`]['wrap'].dataset.title =  data['onair'][key]['program_name'];

                } else {

                    // 実況勢いは変化に関わらず常に更新
                    broadcast_elems[`ch${key}`]['wrap'].querySelector('.broadcast-ikioi').textContent = data['onair'][key]['ikioi'];
                }

                // プログレスバー
                let percentage_channel = ((Math.floor(Date.now() / 1000) - data['onair'][key]['timestamp']) / data['onair'][key]['duration']) * 100;
                broadcast_elems[`ch${key}`]['progress'].style.width = `${percentage_channel}%`;
            }

            // **** ストリームリストの更新 ****
            for (key in data['stream']) {

                var elem = document.getElementsByClassName('stream-view-' + key)[0];

                switch (data['stream'][key]['state']) {

                    case 'ONAir':
                        var state = '● ON Air'
                        var color = 'blue';
                        var time = data['stream'][key]['starttime'] + ' ～ ' + data['stream'][key]['endtime'];
                        break;

                    case 'File':
                        var state = '● File'
                        var color = 'green';
                        var time = data['stream'][key]['time'];
                        break;

                    default:
                        var state = '● Offline'
                        var color = '';
                        var time = '';
                        break;
                }

                // 要素が存在しない・変化がある場合のみ書き換え
                if ((data['stream'][key]['state'] != 'Offline' || key == '1') &&
                        (elem === undefined || elem.getElementsByClassName('stream-title')[0].innerHTML != data['stream'][key]['program_name'])) {

                    // 書き換え用 html
                    var streamview = 
                        `<div class="stream-box">
                            <div class="stream-number-title">Stream</div><div class="stream-number">` + key + `</div>
                            <div class="stream-stop ` + (data['stream'][key]['state'] == 'Offline' ? 'disabled' : '') + `">
                                <i class="stream-stop-icon far fa-stop-circle"></i>
                            </div>
                            <div class="stream-state ` + color + `">` + state + `</div>
                            <div class="stream-info">
                                <div class="stream-title">` + data['stream'][key]['program_name'].replace(/<br>/g,' ') + `</div>
                                <div class="stream-channel">` + data['stream'][key]['channel'] + `</div>
                                <div class="stream-description">` + data['stream'][key]['program_info'].replace(/<br>/g,' ') + `</div>
                            </div>
                        </div>`;

                    // 番組情報を書き換え
                    if (elem === undefined) {

                        // 親要素を追加
                        streamview = `<div class="stream-view stream-view-` + key + `" type="button" data-num="` + key + `" data-url="/` + key + `/" style="display: none; opacity: 0;">` + streamview + `</div>`;

                        // 新規で要素を作る
                        document.getElementById('stream-view-box').insertAdjacentHTML('beforeend', streamview);

                        // スライドダウン
                        $('.stream-view-' + key).slideDown(400).animate(
                            { opacity: 1 },
                            { queue: false, duration: 400, easing: 'swing' }
                        );

                    } else {

                        // 既存のものを書き換え
                        elem.innerHTML = streamview;
                    }

                // オフラインかつ要素が存在する場合
                } else if (elem !== undefined && data['stream'][key]['state'] == 'Offline' && key != '1') {

                    // 要素を削除する
                    $('.stream-view-' + key).slideUp(400).animate(
                        { opacity: 0 },
                        { queue: false, duration: 400, easing: 'swing' }
                    ).queue(function() {
                        $('.stream-view-' + key).remove();
                    });
                }
            }

            // 高さ調整(初回のみ)
            if (flg) $('.swiper-wrapper').eq(1).css('height', $('.broadcast-nav.swiper-slide').height() + 'px');

            setTimeout(refresh_epginfo, 8000);
        }).fail(function(data, status, error) {

            // エラーメッセージ
            console.error(`failed to get epginfo. status: ${status}\nerror: ${error.message}`);
            setTimeout(refresh_epginfo, 8000);
        });
    }
    refresh_epginfo();


    // ***** ストリーム開始 *****

    // 再生開始ボックス
    $('body').on('click','.broadcast-wrap',function() {
        var $elem = $(this);
        $('#broadcast-stream-title').html($elem.data('channel') + ' ' + $elem.data('name'));
        $('#broadcast-stream-info').html($elem.find('.broadcast-title-id').html());
        $('#broadcast-stream-channel').val($elem.data('ch'));
        // 地デジ・BSCS判定
        if ($('.swiper-slide-thumb-active').text() == '地デジ') {
            $('#broadcast-BonDriver-T').show();
            $('#broadcast-BonDriver-T').find('select').prop('disabled', false);
            $('#broadcast-BonDriver-S').hide();
            $('#broadcast-BonDriver-S').find('select').prop('disabled', true);
        } else {
            $('#broadcast-BonDriver-S').show();
            $('#broadcast-BonDriver-S').find('select').prop('disabled', false);
            $('#broadcast-BonDriver-T').hide();
            $('#broadcast-BonDriver-T').find('select').prop('disabled', true);
        }
        // 開閉
        $('#nav-close').addClass('open');
        $('#broadcast-stream-box').addClass('open');
        $('html').addClass('open');
        // ワンクリックでストリーム開始する場合
        if (settings['onclick_stream']) {
            $('#broadcast-stream-box').hide();
            $('#broadcast-stream-box .bluebutton').click();
        }
    });

    // 再生開始
    $('#broadcast-stream-box .bluebutton').click(function() {
        $('#broadcast-stream-box .bluebutton').addClass('disabled');
    });

    // キャンセル
    $('.redbutton').click(function(event) {
        $('#nav-close').removeClass('open');
        $('#broadcast-stream-box').removeClass('open');
        $('#chromecast-box').removeClass('open');
        $('#hotkey-box').removeClass('open');
        $('#ljicrop-box').removeClass('open');
        $('html').removeClass('open');
    });


    // ***** ストリーム終了・遷移 *****

    $('body').on('click','.stream-view',function(event) {

        // ストリーム終了ボタン
        if ($(event.target).hasClass('stream-stop-icon') && !$(event.target).parent().hasClass('disabled')) {

            var streamview = this;
            var streamnum = $(streamview).attr('data-num');

            toastr.info('ストリーム ' + streamnum + ' を終了します。');

            $.ajax({
                url: '/settings/',
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token'), state: 'Offline', stream: streamnum},
                cache: false,
            }).done(function(data) {

                toastr.success('ストリーム ' + streamnum + ' を終了しました。');

                // Offlineにする
                $(streamview).find('.stream-stop').addClass('disabled');
                $(streamview).find('.stream-state').removeClass('blue');
                $(streamview).find('.stream-state').removeClass('green');
                $(streamview).find('.stream-state').html('● Offline');
                $(streamview).find('.stream-title').html('配信休止中…');
                $(streamview).find('.stream-channel').empty();
                $(streamview).find('.stream-description').empty();

                // ストリーム開始のセレクトボックスの表示も書き換える
                $('select[name=stream] option[value=' + streamnum + ']').text('Stream ' + streamnum + ' - Offline');

                // 自分のストリームでない&ストリーム1でないなら要素を削除する
                if (stream != streamnum && streamnum != '1') {
                    $(streamview).slideUp(400).animate(
                        { opacity: 0 },
                        { queue: false, duration: 400, easing: 'swing' }
                    ).queue(function() {
                        $(streamview).remove();
                    });
                }

            }).fail(function() {
                toastr.error('ストリーム ' + streamnum + ' の終了に失敗しました…');
            });

        } else if ($(event.target).parent().hasClass('disabled')) {

            event.preventDefault();

        } else {

            // 他のストリームへ遷移
            location.href = $(this).attr('data-url');

        }

    });


    // ***** ツイート関連 *****

    // キャプチャ画像が入る連想配列
    let capture = [];

    // 選択したキャプチャが入る配列
    let capture_selected = [];

    // キャプチャ画像の最大保持数
    let capture_maxcount = 10; // 10個

    // キャプチャ画像リストにフォーカスしているか
    let capture_list_focus = false;

    // ツイートの文字数をカウント
    var count;
    var limit = 140;
    $('#tweet, #tweet-hashtag').on('keydown keyup keypress change',function(event) {
        tweet_count(event);
    });

    // アカウント情報ボックスを表示する
    var clickEventType = ((window.ontouchstart!==null) ? 'mouseenter mouseleave' : 'touchstart');
    $('#tweet-title').on(clickEventType, function(event) {
        if ($('#tweet-account-box').css('visibility') === 'hidden' && (event.type === 'mouseenter' || event.type === 'touchstart')) {
            $('#tweet-account-box').css('visibility', 'visible');
            $('#tweet-account-box').css('opacity', 1);
        } else {
            $('#tweet-account-box').css('visibility', 'hidden');
            $('#tweet-account-box').css('opacity', 0);
        }
    });

    $('#tweet-account-box').on(clickEventType, function(event) {
        if (event.type === 'mouseenter') {
            $('#tweet-account-box').css('visibility', 'visible');
            $('#tweet-account-box').css('opacity', 1);
        } else {
            $('#tweet-account-box').css('visibility', 'hidden');
            $('#tweet-account-box').css('opacity', 0);
        }
    });

    // スマホの場合に Twitter フォームだけ下にフロート表示
    $('#tweet').focusin(function(event) {
        if ($(window).width() <= 500) {
            $('#top').hide();
            $('#tweet-box').addClass('open');
            $('#tweet-close').addClass('open');
            $('html').addClass('open');
        }
    });
    $('#tweet-hashtag').focusin(function(event) {
        if ($(window).width() <= 500) {
            $('#top').hide();
            $('#tweet-box').addClass('open');
            $('#tweet-close').addClass('open');
            $('html').addClass('open');
        }
    });
    $('#tweet-close').click(function(event) {
        if ($(window).width() <= 500) {
            $('#top').show();
            $('#tweet-box').removeClass('open');
            $('#tweet-close').removeClass('open');
            $('html').removeClass('open');
        }
    });

    // キャプチャした画像を blob に格納する
    $('#tweet-picture').click(function(event) {
        captureVideo(event);
    });

    // キャプチャした画像をコメント付きで blob に格納する
    $('#tweet-picture-comment').click(function(event) {
        captureVideoWithComment(event);
    });

    // フォームをハッシュタグ以外リセット
    $('#tweet-reset').click(function(event) {
        // ツイートをリセット
        tweet_reset(event);
    });

    // Twitterからログアウト
    $('#tweet-status').on('click', '#tweet-logout', function(event) {
        $.ajax({
            url: "/tweet/logout",
            type: "post",
            processData: false,
            contentType: false,
        }).done(function(data) {
            $('#tweet-status').html(data);
            $('#tweet-account-icon').attr('src', '/files/account_default.jpg');
            $('#tweet-account-name').text('ログインしていません');
            $('#tweet-account-name').removeAttr('href');
            $('#tweet-account-id').text('Not Login');
        }).fail(function(data) {
            $('#tweet-status').html('<span class="tweet-failed">ログアウト中にエラーが発生しました…</span>');
        });
    });

    // クリップボードの画像を格納する
    $('#tweet').on('paste', function(event) {

        // event からクリップボードのアイテムを取り出す
        var items = event.originalEvent.clipboardData.items; // ここがミソ

        for (var i = 0 ; i < items.length ; i++) {

            var item = items[i];
            if (item.type.indexOf('image') != -1) {

                // 画像だけ代入
                $('#tweet-status').text('取得中…');
                $('#tweet-submit').prop('disabled', true).addClass('disabled');
                $('#tweet-status').text('クリップボードの画像を取り込みました。');

                // キャプチャ画像を追加
                addCaptureImage(item.getAsFile());
            }
        }
    });

    // Shift キー
    window.isShiftKey = false;
    $(document).on('keydown keyup', function(event) {
        window.isShiftKey = event.shiftKey;
    });

    // 現在 IME 変換中か
    window.isComposing = false;
    $('#tweet').on('compositionstart', function() {
        window.isComposing = true;
    });
    $('#tweet').on('compositionend', function() {
        window.isComposing = false;
    });
    $('#tweet-hashtag').on('compositionstart', function() {
        window.isComposing = true;
    });
    $('#tweet-hashtag').on('compositionend', function() {
        window.isComposing = false;
    });
    $('.dplayer-comment-input').on('compositionstart', function() {
        window.isComposing = true;
    });
    $('.dplayer-comment-input').on('compositionend', function() {
        window.isComposing = false;
    });

    // ツイートボタンが押された時にツイートを送信する
    $('#tweet-submit').click(function(event) {
        if (!$('#tweet-submit').prop('disabled')) { // ボタンが無効でなければ
            tweet_send(event);
        }
    });


    // キーボードショートカット
    //   Ctrl + Enter：ツイートを送信
    //   Tab キー：フォーカス
    //   E キー：画面全体のフルスクリーンの切り替え
    //   ? キー：ショートカット一覧
    //   Alt + Q キー: キャプチャ画像リストの表示 / 非表示の切り替え
    //   Alt + 1 キー：キャプチャ
    //   Alt + 2 キー：コメント付きでキャプチャ
    //   Alt + 3 キー：フォームをリセット

    // ページ全体
    $(document).keydown(function(event) {

        // クロスブラウザ対応用
        var event = event || window.event;

        // Ctrl + Enter キー
        // Twitter 機能が有効 & 送信ボタンが有効
        if (settings['twitter_show'] && !$('#tweet-submit').prop('disabled')) {

            if ((event.ctrlKey || event.metaKey) && event.key == 'Enter') {
                tweet_send(event); // ツイートを送信
            }
        }

        // Tab キー
        // Twitter 機能が有効 & 変換中でない
        if (settings['twitter_show'] && window.isComposing === false) {

            if (event.key === 'Tab') {

                // デフォルトの処理を止める
                event.preventDefault();

                // キャプチャ画像リストが表示されていない
                if (capture_list_focus === false) {

                    // ツイートフォームにフォーカス
                    if ($(':focus').is('#tweet')) {
                        $('#tweet').blur();
                    } else {
                        $('#tweet').focus();
                    }

                // キャプチャ画像リストが表示されている
                } else {

                    // プレイヤーにフォーカス
                    if (dp.focus === false) {
                        dp.focus = true;
                        if (document.querySelectorAll('.tweet-capture.focus').length === 1) { // フォーカスがあれば

                            // フォーカスをはずす
                            document.querySelector('.tweet-capture.focus').classList.remove('focus');
                        }
                    // キャプチャ画像リストにフォーカス
                    } else {
                        dp.focus = false;
                        if (document.querySelectorAll('.tweet-capture').length > 0) { // キャプチャ画像があれば

                            // コメント入力フォームのフォーカスを外す
                            dp.comment.hide();

                            // 他のフォーカスがあれば削除
                            $('.tweet-capture').each(function(index, elem) {
                                elem.classList.remove('focus');
                            });

                            // フォーカスする
                            document.querySelector('.tweet-capture').classList.add('focus');
                        }
                    }
                }
            }
        }

        // ツイートフォーム・ハッシュタグフォーム・コメント入力フォームいずれにもフォーカスしていない
        if (document.activeElement.id != 'tweet' &&
            document.activeElement.id != 'tweet-hashtag' &&
            document.activeElement.className != 'dplayer-comment-input') {

            // E キー
            if (event.key.toUpperCase() == 'E') {
                event.preventDefault();
                $('#fullscreen').click();
            }

            // ? キー
            if (event.key == '?') {
                event.preventDefault();
                $('#hotkey-box').toggleClass('open');
                $('#nav-close').toggleClass('open');
            }
        }

        // Alt (or option) キー
        // Twitter 機能が有効
        if (settings['twitter_show']) {

            if (event.altKey) {

                // デフォルトの処理を止める
                event.preventDefault();

                // Mac だと Option キーを押しながら入力すると œ など謎の文字が入力されてしまうので、
                // 敢えて event.keyCode で実装
                switch (event.keyCode) {

                    // Alt + Q
                    case 81:
                        // キャプチャ画像リストの表示 / 非表示の切り替え
                        tweet_capture_list(event);
                    break;

                    // Alt + 1
                    case 49:
                        // キャプチャ
                        captureVideo(event);
                    break;

                    // Alt + 2
                    case 50:
                        // コメント付きでキャプチャ
                        captureVideoWithComment(event);
                    break;

                    // Alt + 3
                    case 51:
                        // フォームをリセット
                        tweet_reset(event);
                    break;
                }
            }
        }

        // Twitter 機能が有効 & 
        // キャプチャ画像リストにフォーカスしている &
        // プレイヤーにフォーカスされていない &
        // キャプチャが存在する &
        // 変換中ではない
        if (settings['twitter_show'] && capture_list_focus && dp.focus === false && capture.length > 0 && window.isComposing === false) {

            // ボックス
            let box_elem = document.getElementById('tweet-capture-box');

            // 要素
            let focus_elems = document.querySelectorAll('.tweet-capture.focus');
            let focus_elem = focus_elems[0];

            // フォーカスされている要素があるか
            let exists_focus_elem = (focus_elems.length === 1);

            // focus_elem があれば
            if (exists_focus_elem) {

                // キャプチャ画像の要素の margin (margin-right)
                var focus_elem_margin = Number(getComputedStyle(focus_elem).marginRight.replace('px', ''));

                // キャプチャ画像の要素 1 つ分の幅（ダミー用）
                var focus_elem_dummy = focus_elem.getBoundingClientRect().width + focus_elem_margin;

                // スクロールにかける時間
                var focus_elem_scroll_time = 350; // 350 (ミリ秒)
            }

            switch (event.key) {

                // Space
                case ' ':

                    // イベントをキャンセル
                    event.preventDefault();

                    // コメント入力フォームのフォーカスを外す
                    dp.comment.hide();

                    // focus_elem があれば
                    if (exists_focus_elem) {

                        // 選択されてなかったら選択、選択されてたら選択解除
                        if (focus_elem.classList.contains('selected')) {
                            deselectCaptureImage(focus_elem);
                        } else {
                            selectCaptureImage(focus_elem);
                        }
                    }

                break;

                // ←
                case 'ArrowLeft':

                    // イベントをキャンセル
                    event.preventDefault();

                    // コメント入力フォームのフォーカスを外す
                    dp.comment.hide();

                    // focus_elem があれば
                    if (exists_focus_elem) {

                        // 前の要素があれば
                        if (focus_elem.previousElementSibling !== null) {

                            // 前の要素にフォーカス
                            focus_elem.previousElementSibling.classList.add('focus');

                            // 自分のフォーカスを解除
                            focus_elem.classList.remove('focus');

                            // tweet-capture-box の左端（絶対座標）
                            let box_elem_leftedge = box_elem.getBoundingClientRect().left;

                            // フォーカス中の画像の左（絶対座標）
                            // focus_elem_dummy 分の幅を引く
                            let focus_elem_leftedge = focus_elem.getBoundingClientRect().left - focus_elem_dummy;

                            // キャプチャ画像リストの表示領域に収まってない
                            if (focus_elem_leftedge < box_elem_leftedge) { // フォーカス中の画像の左端が tweet-capture-box の左端よりも左にある

                                // スクロール可能な幅
                                // 既にスクロールした分の幅
                                let box_elem_scrollableWidth = Math.round(box_elem.scrollLeft);

                                // スクロールしたい幅
                                let box_elem_scrollLeft = box_elem.getBoundingClientRect().width;

                                // スクロールしたい幅がスクロール可能な幅よりも大きい
                                if (box_elem_scrollLeft > box_elem_scrollableWidth) {
                                    box_elem_scrollLeft = box_elem_scrollableWidth; // スクロール可能な幅で制限
                                }

                                // 今までのスクロール幅を引く
                                $(box_elem).animate({scrollLeft: box_elem.scrollLeft - box_elem_scrollLeft}, focus_elem_scroll_time, 'swing');
                            }
                        }

                    } else {

                        // 他のフォーカスがあれば削除
                        $('.tweet-capture').each(function(index, elem) {
                            elem.classList.remove('focus');
                        });

                        // 最初の要素にフォーカス
                        document.getElementsByClassName('tweet-capture')[0].classList.add('focus');
                    }

                break;

                // →
                case 'ArrowRight':

                    // イベントをキャンセル
                    event.preventDefault();

                    // コメント入力フォームのフォーカスを外す
                    dp.comment.hide();

                    // focus_elem があれば
                    if (exists_focus_elem) {

                        // 次の要素があれば
                        if (focus_elem.nextElementSibling !== null) {

                            // 次の要素にフォーカス
                            focus_elem.nextElementSibling.classList.add('focus');

                            // 自分のフォーカスを解除
                            focus_elem.classList.remove('focus');

                            // tweet-capture-box の右端（絶対座標）
                            let box_elem_rightedge = (box_elem.getBoundingClientRect().left + box_elem.getBoundingClientRect().width);

                            // フォーカス中の画像の右端（絶対座標）
                            // focus_elem_dummy 分の幅を足す
                            let focus_elem_rightedge = (focus_elem.getBoundingClientRect().left + focus_elem.getBoundingClientRect().width + focus_elem_dummy);

                            // キャプチャ画像リストの表示領域に収まってない
                            if (box_elem_rightedge < focus_elem_rightedge) { // フォーカス中の画像の右端が tweet-capture-box の右端よりも右にある

                                // スクロール可能な幅
                                // 全体の幅 - (表示されている幅 + 既にスクロールした分の幅)
                                let box_elem_scrollableWidth = box_elem.scrollWidth - (box_elem.offsetWidth + Math.round(box_elem.scrollLeft));

                                // スクロールしたい幅
                                let box_elem_scrollLeft = box_elem.getBoundingClientRect().width;

                                // スクロールしたい幅がスクロール可能な幅よりも大きい
                                if (box_elem_scrollLeft > box_elem_scrollableWidth) {
                                    box_elem_scrollLeft = box_elem_scrollableWidth; // スクロール可能な幅で制限
                                }

                                // 今までのスクロール幅を足す
                                $(box_elem).animate({scrollLeft: box_elem.scrollLeft + box_elem_scrollLeft}, focus_elem_scroll_time, 'swing');
                            }
                        }

                    } else {

                        // 他のフォーカスがあれば削除
                        $('.tweet-capture').each(function(index, elem) {
                            elem.classList.remove('focus');
                        });

                        // 最初の要素にフォーカス
                        document.getElementsByClassName('tweet-capture')[0].classList.add('focus');
                    }

                break;
            }
        }
    });


    // ***** キャプチャ画像リスト *****

    // キャプチャ画像表示用の Luminous インスタンス
    let luminous = null;

    // キャプチャした画像の一覧を表示
    $('#tweet-capture-list').click(function(event) {

        // キャプチャ画像リストを表示/非表示
        tweet_capture_list(event);
    });

    // キャプチャした画像をクリック
    $(document).on('click', '.tweet-capture', function(event) {

        // フォーカスを解除
        if (document.querySelectorAll('.tweet-capture.focus').length === 1) {
            document.querySelector('.tweet-capture.focus').classList.remove('focus');
        }

        if (!$(this).hasClass('selected')) {

            // 4枚まで
            if (capture_selected.length < 4) {

                // 選択されたキャプチャ画像を追加
                selectCaptureImage(this);
            }

        } else {

            // 選択解除されたキャプチャ画像を削除
            deselectCaptureImage(this);
        }
    });

    /**
     * キャプチャした画像をリストに追加する
     * @param {Blob} blob 
     */
    function addCaptureImage(blob) {

        // キャプチャ画像が capture_maxcount を超えていたら
        // 超えた分のキャプチャ画像を削除する
        if (capture.length >= capture_maxcount) {

            // 配列から削除
            capture.pop();

            // 削除する要素
            let removeelemlist = document.getElementsByClassName('tweet-capture');
            let removeelem = removeelemlist[removeelemlist.length -1];

            // blob URL を無効化
            URL.revokeObjectURL(removeelem.dataset.url);

            // 選択されていれば解除
            deselectCaptureImage(removeelem); // jQuery オブジェクトではなく通常の element として渡す

            // 要素を削除
            removeelem.remove();
        }

        // blob URL を生成
        let bloburl = URL.createObjectURL(blob);

        // キャプチャした画像を格納
        capture.unshift(blob); // ISO8601 のタイムスタンプをキーにする

        // html を追加
        document.getElementById('tweet-capture-box').insertAdjacentHTML('afterbegin', `
            <div class="tweet-capture" data-index="0" data-url="` + bloburl + `">
                <img class="tweet-capture-img" src="` + bloburl + `" />
                <div class="tweet-capture-cover"></div>
                <div class="tweet-capture-focus"></div>
            </div>`
        );

        // 追加したキャプチャを自動選択
        $('.tweet-capture').each(function(index, elem) {

            // 先頭の要素でなければ
            if (index !== 0) {

                // 自動選択を解除
                deselectCaptureImage(elem, true);

                // インデックスを書き換え
                elem.dataset.index++; 
            }
        });

        // 自動選択
        selectCaptureImage(document.getElementsByClassName('tweet-capture')[0], true);

        // Luminous がすでに初期化されていたら一旦破棄
        if (luminous !== null) {
            luminous.destroy();
            luminous = null;
        }

        // Luminous を初期化
        const luminousTrigger = document.querySelectorAll('.tweet-capture');
        if (luminousTrigger !== null) {
            luminous = new LuminousGallery(luminousTrigger, {
                arrowNavigation: true,  // 左右のボタンを表示する
            }, {
                sourceAttribute: 'data-url',  // 拡大画像の URL がある属性
                openTrigger: 'contextmenu',  // 右クリックで開く
                closeTrigger: 'click',  // 普通のクリックで閉じる
            });
        }
    }

    /**
     * キャプチャ画像を選択する
     * @param {HTMLElement} elem 選択された要素
     * @param {Boolean} autoselect true を指定すると data-autoselect を付与する
     */
    function selectCaptureImage(elem, autoselect = false) {

        // 4枚未満なら実行（まだ追加されていないため「未満」）
        if (capture_selected.length < 4) {

            // 選択されたキャプチャを配列に追加
            capture_selected.push(capture[elem.dataset.index]);

            // data-order を追加
            elem.dataset.order = (capture_selected.length - 1);
            $(elem).find('.tweet-capture-cover').text(capture_selected.length);

            // data-autoselect を追加
            if (autoselect) {
                elem.dataset.autoselect = true;
            }

            $('.tweet-capture').each(function(index, elem) {

                // 手動選択だったら自動選択を解除
                if (autoselect === false) {
                    deselectCaptureImage(elem, true);
                }

                // 4枚選択されていたら他のキャプチャを無効にする
                if (capture_selected.length === 4) {

                    // order がなければ
                    if (typeof elem.dataset === 'undefined' || typeof elem.dataset.order === 'undefined') {
                        elem.classList.add('disabled'); // 無効化
                    }
                }
            });

            // ツイートが limit 内なら送信ボタンを有効化する
            if (limit >= 0) {
                document.getElementById('tweet-submit').disabled = false;
                document.getElementById('tweet-submit').classList.remove('disabled');
            }

            // カバーを表示
            elem.classList.add('selected');

            // 枚数を表示
            document.getElementById('tweet-capture-num').textContent = capture_selected.length + '/4';

            // メッセージを表示
            if (!autoselect || capture_selected.length > 1) {
                document.getElementById('tweet-status').textContent = capture_selected.length + ' 枚の画像を選択しました。';
            }

        // 5枚以上は無効化
        } else {
            elem.classList.add('disabled');
        }
    }

    /**
     * キャプチャ画像を選択解除する
     * @param {HTMLElement} elem 選択された要素
     * @param {Boolean} autoselect true を指定すると data-autoselect が付与されている要素のみ選択を解除
     */
    function deselectCaptureImage(this_, autoselect = false) {

        // order が定義されていれば
        if (typeof this_.dataset !== 'undefined' && typeof this_.dataset.order !== 'undefined') {

            // autoselect が true のとき、data-autoselect が存在するか
            if ((autoselect === true && typeof this_.dataset.autoselect !== 'undefined') || autoselect === false) {

                // 選択解除されたキャプチャを配列から削除
                capture_selected.splice(this_.dataset.order, 1);

                // order を書き換え
                $('.tweet-capture').each(function(index, elem) {

                    // そのキャプチャの order が削除された order よりも大きければ
                    if (elem.dataset.order > this_.dataset.order) {

                        // 配列に合わせて orderを詰める
                        elem.dataset.order--;
                        $(elem).find('.tweet-capture-cover').text(parseInt(elem.dataset.order) + 1);
                    }

                    // キャプチャを有効化
                    if (elem.classList.contains('disabled')) {
                        elem.classList.remove('disabled');
                    }
                });

                // data-order を削除
                delete this_.dataset.order;
                $(this_).find('.tweet-capture-cover').text('');

                // data-autoselect を削除
                if (typeof this_.dataset.autoselect !== 'undefined') {
                    delete this_.dataset.autoselect;
                }

                // 本文が空でかつ選択されている画像が 0 なら送信ボタンを無効化する
                if (document.getElementById('tweet').value.length === 0 && capture_selected.length === 0) {
                    document.getElementById('tweet-submit').disabled = true;
                    document.getElementById('tweet-submit').classList.add('disabled');
                }

                // カバーを非表示
                this_.classList.remove('selected');

                // 枚数を表示
                document.getElementById('tweet-capture-num').textContent = capture_selected.length + '/4';

                // メッセージを表示
                if (!autoselect) {
                    if (capture_selected.length === 0) {
                        document.getElementById('tweet-status').innerHTML = '<a id="tweet-logout" href="javascript:void(0)"><i class="fas fa-sign-out-alt"></i>ログアウト</a>';
                    } else {
                        document.getElementById('tweet-status').textContent = capture_selected.length + ' 枚の画像を選択しました。';
                    }
                }
            }
        }
    }

    /**
     * キャプチャ画像をすべて選択解除する
     */
    function deselectAllCaptureImage() {

        // 配列を空にする
        capture_selected = [];

        // 本文が空なら送信ボタンを無効化する
        if (document.getElementById('tweet').value.length === 0) {
            document.getElementById('tweet-submit').disabled = true;
            document.getElementById('tweet-submit').classList.add('disabled');
        }

        // 要素ごとに実行
        $('.tweet-capture').each(function(index, elem) {

            // order が定義されていれば
            if (typeof elem.dataset !== 'undefined' && typeof elem.dataset.order !== 'undefined') {

                // data-order を削除
                delete elem.dataset.order;
                $(elem).find('.tweet-capture-cover').text('');

                // data-autoselect を削除
                if (typeof elem.dataset.autoselect !== 'undefined') {
                    delete elem.dataset.autoselect;
                }

                // カバーを非表示
                elem.classList.remove('selected');
            }

            // キャプチャを有効化
            if (elem.classList.contains('disabled')) {
                elem.classList.remove('disabled');
            }
        });

        // 枚数を表示
        document.getElementById('tweet-capture-num').textContent = '0/4';
    }


    // ***** ツイート関連の関数 *****

    // ツイートの文字数をカウントする関数
    function tweet_count(event) {

        // 現在のカウント数
        count_tweet = Array.from($('#tweet').val()).length;
        count_hashtag = Array.from($('#tweet-hashtag').val()).length;
        count = count_hashtag + count_tweet;
        limit = 140 - count;

        if (limit <= 140) {

            // 初期化
            $('#tweet-num').text(limit);
            $('#tweet-num').removeClass('over');
            $('#tweet-num').removeClass('warn');

            // 送信中 or キャプチャ中でないなら
            if ($('#tweet-status').text() != 'ツイートを送信中…' &&
                $('#tweet-status').text() != 'キャプチャ中…' &&
                $('#tweet-status').text() != 'コメント付きでキャプチャ中…') {
                $('#tweet-submit').prop('disabled', false).removeClass('disabled'); // 一旦ボタンを有効化
            }

            // ハッシュタグ以外のツイート文が空
            if (count_tweet === 0) {
                if (capture_selected.length === 0) { // キャプチャがない（ハッシュタグ以外送信するものがない）場合はボタンを無効に
                    $('#tweet-submit').prop('disabled', true).addClass('disabled');
                }
            }

            // 残り20字以下
            if (limit <= 20) {
                $('#tweet-num').addClass('warn');
            }

            // 残り0文字
            if (limit == 0) {
                $('#tweet-num').addClass('over');
            }

            // 文字数オーバー
            if (limit < 0) {
                $('#tweet-num').addClass('over');
                $('#tweet-submit').prop('disabled', true).addClass('disabled'); // エラーになるので送信できないよう無効化
            }
        }
    }

    // ツイートを送信する関数
    function tweet_send(event) {

        event.preventDefault(); // 通常のイベントをキャンセル
        $('#tweet-submit').prop('disabled', true).addClass('disabled');
        $('#tweet-status').text('ツイートを送信中…');

        // フォームデータ
        var formData = new FormData($('#tweet-form').get(0));

        // 選択した画像を追加
        for (index in capture_selected) {
            formData.append('picture' + (Number(index) + 1), capture_selected[index]);
        }

        // 値をリセット
        // キャプチャ画像の選択をすべて解除
        deselectAllCaptureImage();

        // フォーカスを外す
        if (document.querySelectorAll('.tweet-capture.focus').length === 1) {
            document.querySelector('.tweet-capture.focus').classList.remove('focus');
        }

        // 文字数リミットをリセット
        limit = 140;
        $('#tweet-num').text(140);
        $('#tweet-num').removeClass('over');
        $('#tweet-num').removeClass('warn');

        // 本文をクリア
        $('#tweet').val(null);

        // 通常表示
        $('#content-box').show();
        $('#footer').show();
        $('#top').show();
        $('#tweet-box').removeClass('open');
        $('#tweet-close').removeClass('open');
        $('html').removeClass('open');

        // 送信
        $.ajax({
            url: '/tweet/tweet',
            type: 'post',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(data) {
            $('#tweet-status').html(data);
        }).fail(function(data) {
            $('#tweet-status').html('<span class="tweet-failed">送信中にエラーが発生しました…</span>');
        });
    }

    // キャプチャ画像リストを表示したり非表示にしたりする関数
    function tweet_capture_list(event) {

        // キャプチャ画像リストを隠す
        if ($('#tweet-capture-box').hasClass('show')) {

            // フォーカスを外す
            capture_list_focus = false;
            if (document.querySelectorAll('.tweet-capture.focus').length === 1) {
                document.querySelector('.tweet-capture.focus').classList.remove('focus');
            }

            // プレイヤーのホットキー機能を有効にする
            dp.options.hotkey = true;

            $('#tweet-capture-box').removeClass('show');

            // 0.1 秒遅らせてから display: none; を適用
            setTimeout(function() {
                $('#tweet-capture-box').removeClass('display'); // 必ず後
            }, 100);

        // キャプチャ画像リストを表示
        } else {

            // ツイート本文へのフォーカスを外す
            $('#tweet').blur();

            // フォーカスを当てる
            capture_list_focus = true;

            // プレイヤーのホットキー機能を無効にする
            dp.options.hotkey = false;

            // 先に display: none; を解除
            $('#tweet-capture-box').addClass('display'); // 必ず先

            setTimeout(function() {
                $('#tweet-capture-box').addClass('show');
            }, 10); // 0.01 秒遅らせるのがポイント
        }
    }

    // フォームをハッシュタグ以外リセットする関数
    function tweet_reset(event) {

        // キャプチャ画像の選択をすべて解除
        deselectAllCaptureImage();

        // フォーカスを外す
        if (document.querySelectorAll('.tweet-capture.focus').length === 1) {
            document.querySelector('.tweet-capture.focus').classList.remove('focus');
        }

        // 文字数リミットをリセット
        limit = 140;
        $('#tweet-num').text(limit);
        $('#tweet-num').removeClass('over');
        $('#tweet-num').removeClass('warn');

        $('#tweet-submit').prop('disabled', true).addClass('disabled');
        $('#tweet').val(null);
        $('#content-box').show();
        $('#footer').show();

        if (Cookies.get('tvrp_twitter_settings')) {
            $('#tweet-status').html('<a id="tweet-logout" href="javascript:void(0)"><i class="fas fa-sign-out-alt"></i>ログアウト</a>');
        } else {
            $('#tweet-status').html('<a id="tweet-login" href="/tweet/auth"><i class="fas fa-sign-in-alt"></i>ログイン</a>');
        }
    }


    // ***** キャプチャ関連の関数 *****

    // キャプチャした画像をblobにして格納する関数
    function captureVideo(event) {

        $('#tweet-status').text('キャプチャ中…');
        $('#tweet-submit').prop('disabled', true).addClass('disabled');

        // 要素を取得
        const video = document.querySelector('video.dplayer-video-current');

        // キャプチャを実行する
        videoToCanvas(video).then(({canvas}) => {
            canvas.toBlob(function(blob) {

                $('#tweet-status').text('キャプチャしました。');

                // キャプチャした画像を格納
                console.log('Render Blob: ' + URL.createObjectURL(blob));
                addCaptureImage(blob);

            }, 'image/jpeg', 1);
        });
    }

    // キャプチャした画像をコメント付きでblobにして格納する関数
    function captureVideoWithComment(event) {

        $('#tweet-status').text('コメント付きでキャプチャ中…');
        $('#tweet-submit').prop('disabled', true).addClass('disabled');

        // 要素を取得
        const video = document.querySelector('video.dplayer-video-current');
        const danmaku = document.querySelectorAll('.dplayer-danmaku-move');
        let html = document.querySelector('.dplayer-danmaku').outerHTML;

        // このままだと SVG 化に失敗するため修正する
        for (let i = 0; i < danmaku.length; i++) { // コメントの数だけ置換
            // コメント位置を計算
            let position = danmaku[i].getBoundingClientRect().left - video.getBoundingClientRect().left;
            html = html.replace(/transform: translateX\(.*?\)\;/, 'left: ' + position + 'px;');
        }

        nicoVideoToCanvas({video, html}).then(({canvas}) => {
            canvas.toBlob(function(blob) {

                $('#tweet-status').text('コメント付きでキャプチャしました。');

                // キャプチャした画像を格納
                console.log('Render Blob: ' + URL.createObjectURL(blob));
                addCaptureImage(blob);

            }, 'image/jpeg', 1);
        });
    }

    // Zenzawatch のコードより一部改変した上で使わせて頂いています
    // 参考
    // https://developer.mozilla.org/ja/docs/Web/HTML/Canvas/Drawing_DOM_objects_into_a_canvas
    // Chrome だと toBlob した際に汚染されるので DataURI に変換する
    // https://qiita.com/kjunichi/items/f5993d34838e1623daf5

    const htmlToSvg = function(html, width = 640, height = 360) {
        let scale = 1;
        const data =
            (`<svg xmlns='http://www.w3.org/2000/svg' width='${width*scale}' height='${height*scale}'>
                    <foreignObject width='100%' height='100%'>
                        <div xmlns="http://www.w3.org/1999/xhtml">
                            <style>
                            .dplayer-danmaku {
                                position: absolute;
                                left: 0;
                                right: 0;
                                top: 0;
                                bottom: 0;
                                font-size: 29px;
                                font-family: 'Open Sans','Segoe UI','Arial',sans-serif;
                                color: #fff;
                            }
                            .dplayer-danmaku .dplayer-danmaku-item {
                                display: inline-block;
                                pointer-events: none;
                                user-select: none;
                                cursor: default;
                                white-space: nowrap;
                                font-weight: bold;
                                text-shadow: 1.5px 1.5px 4px rgba(0, 0, 0, 0.9);
                            }
                            .dplayer-danmaku .dplayer-danmaku-item--demo {
                                position: absolute;
                                visibility: hidden;
                            }
                            .dplayer-danmaku .dplayer-danmaku-right {
                                position: absolute;
                                left: 0;
                            }
                            .dplayer-danmaku .dplayer-danmaku-top,
                            .dplayer-danmaku .dplayer-danmaku-bottom {
                                position: absolute;
                                width: 100%;
                                text-align: center;
                                visibility: hidden;
                            }
                            @keyframes danmaku-center {
                                from {
                                    visibility: visible;
                                }
                                to {
                                    visibility: visible;
                                }
                            }
                            </style>
                            ${html}
                        </div>
                    </foreignObject>
        </svg>`).trim();
        const svg = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(data);
        console.log('Comment Canvas Size: ' + width + 'x' + height);
        console.log('Render Comment DataURI: ' + svg);
        return {svg, data};
    };

    const videoToCanvas = function(video) {
        // 動画のキャンバス
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const caption = dp.plugins.aribb24.getRawCanvas();
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        // 描画
        return new Promise((resolve, reject) => {
            const draw = function() {
                try {
                    // キャプチャを描画
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    // 字幕を描画（ Shift キーが押されていない & 字幕キャンバスが存在する場合のみ）
                    if (!window.isShiftKey && caption !== null) {
                        context.drawImage(caption, 0, 0, canvas.width, canvas.height);
                    }
                } catch (error) {
                    // エラーを捕捉
                    console.error(`Error: Capture failed. (${error.name}: ${error.message})`)
                    // Android 向け Firefox 
                    if (error.name === 'NS_ERROR_NOT_AVAILABLE' ||
                        error.message === 'CanvasRenderingContext2D.drawImage: Passed-in image is "broken"') {
                        $('#tweet-status').html('<span class="error">Android 向け Firefox では、ブラウザの不具合が原因でキャプチャができません。</span>');
                        throw error;
                    // それ以外
                    } else { 
                        $('#tweet-status').html('<span class="error">キャプチャに失敗しました…</span>');
                        throw error;
                    } 
                }
                console.log('Video Canvas Size: ' + canvas.width + 'x' + canvas.height);
                resolve({canvas});
            };
            draw();
        });
    };

    const htmlToCanvas = function(video, html, width = 640, height = 360) {

        const imageW = height * 16 / 9;
        const imageH = imageW * 9 / 16;
        const {svg, data} = htmlToSvg(html, video.clientWidth, video.clientHeight);

        const url = svg;
        if (!url) {
            return Promise.reject(new Error('convert svg fail'));
        }
        const img = new Image();
        img.width = width;
        img.height = height;

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.width = width;
        canvas.height = height;

        return new Promise((resolve, reject) => {
            img.onload = () => {
                context.drawImage(
                    img,
                    (width - imageW) / 2,
                    (height - imageH) / 2,
                    imageW,
                    imageH);
                resolve({canvas, img});
                //window.console.info('img size', img.width, img.height);
                window.URL.revokeObjectURL(url);
            };
            img.onerror = (e) => {
                window.console.error('img.onerror', e, data);
                reject(e);
                window.URL.revokeObjectURL(url);
            };

            img.src = url;
        });
    };

    const nicoVideoToCanvas = function({video, html, minHeight = 1080}) {
        let scale = 1;
        let width = Math.max(video.videoWidth, video.videoHeight * 16 / 9);
        let height = video.videoHeight;
        // 動画の解像度が低いときは、可能な範囲で整数倍に拡大する
        if (height < minHeight) {
            scale = Math.floor(minHeight / height);
            width *= scale;
            height *= scale;
        }

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        canvas.width = width;
        canvas.height = height;

        return videoToCanvas(video).then(({canvas, img}) => {

            //canvas.style.border = '2px solid red'; document.body.appendChild(canvas);
            context.fillStyle = 'rgb(0, 0, 0)';
            context.fillRect(0, 0, width, height);

            context.drawImage(
                canvas,
                (width - video.videoWidth * scale) / 2,
                (height - video.videoHeight * scale) / 2,
                video.videoWidth * scale,
                video.videoHeight * scale
            );

            return htmlToCanvas(video, html, width, height);}).then(({canvas, img}) => {

            //canvas.style.border = '2px solid green'; document.body.appendChild(canvas);

            context.drawImage(canvas, 0, 0, width, height);

            return Promise.resolve({canvas, img});
        }).then(() => {
            return Promise.resolve({canvas});
        });
    };

    // ここまでZenzaWatchより拝借


    // ***** Utils *****

    // 0埋めする関数
    function zeroPadding(num, length) {
        return ('0000000000' + num).slice(-length);
    }

    // 時計用
    function clock() {

        // 曜日を表す各文字列の配列
        var weeks = new Array("Sun","Mon","Thu","Wed","Thr","Fri","Sat");
        // 現在日時を表すインスタンスを取得
        var now = new Date();
        var y = now.getFullYear(); // 年
        var mo = now.getMonth() + 1; // 月 0~11で取得されるので実際の月は+1したものとなる
        var d = now.getDate(); // 日
        var w = weeks[now.getDay()]; // 曜日 0~6で日曜始まりで取得されるのでweeks配列のインデックスとして指定する

        var h = now.getHours(); // 時
        var mi = now.getMinutes(); // 分
        var s = now.getSeconds(); // 秒

        // 日付時刻文字列のなかで常に2ケタにしておきたい部分はここで処理
        if (mo < 10) mo = "0" + mo;
        if (d < 10) d = "0" + d;
        if (h < 10) h = "0" + h;
        if (mi < 10) mi = "0" + mi;
        if (s < 10) s = "0" + s;

        $('#clock').text(y + '/' + mo + '/' + d + ' ' + h + ':' + mi + ':' + s);
    }

    // タイムスタンプ取得
    function time() {
        var date = new Date();
        return Math.floor( date.getTime() / 1000 );
    }

});

