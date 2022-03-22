
// 生放送・ファイル再生共通その2 (script.jsが肥大化したためこっちに)

// 定義
let slider;
let remotePlayer;
let remotePlayerController;

// php の isset みたいなの
function isset(data){
    if (data === '' || data === null || data === undefined){
        return false;
    } else {
        return true;
    }
}

/**
 * フルスクリーンかどうかを返す
 * @return {Boolean} フルスクリーンなら true、そうでないなら false
 */
function isFullScreen() {
    if ((document.fullscreenElement !== undefined && document.fullscreenElement !== null) || // HTML5 標準
        (document.mozFullScreenElement !== undefined && document.mozFullScreenElement !== null) || // Firefox
        (document.webkitFullscreenElement !== undefined && document.webkitFullscreenElement !== null) || // Chrome・Safari
        (document.webkitCurrentFullScreenElement !== undefined && document.webkitCurrentFullScreenElement !== null) || // Chrome・Safari (old)
        (document.msFullscreenElement !== undefined && document.msFullscreenElement !== null)){ // IE・Edge Legacy
        return true; // fullscreenElement に何か入ってる = フルスクリーン中
    } else {
        return false; // フルスクリーンではない or フルスクリーン非対応の環境（iOS Safari など）
    }
}

// ロード時 & リサイズ時に発火
var timer = false;
var lastWindowWidth = window.innerWidth;
$(window).on('DOMContentLoaded resize', function(event){

    // ロード時のみ発火
    if (event.type == 'DOMContentLoaded'){

        // 個人設定を反映
        if (!settings['twitter_show']){
            $('#tweet-box').hide();
        }
        if (!settings['comment_show']){
            $('#sidebar').hide();
            $('#content').width('100%');
        }

    }

    // 画面の横幅を取得
    var windowWidth = window.innerWidth;
    // 画面の高さを取得
    var windowHeight = window.innerHeight;
    // 画面の向きを取得
    var orientation = window.orientation;

    $(window).on('load', function() {
        // スマホ・タブレットならplaceholder書き換え
        if (settings['twitter_show']) {
            if (windowWidth > 1024) {
                if (navigator.userAgent.indexOf('Macintosh') != -1) {
                    document.getElementById('tweet').setAttribute('placeholder', 'ツイート (Command + Enterで送信)');
                } else {
                    document.getElementById('tweet').setAttribute('placeholder', 'ツイート (Ctrl + Enterで送信)');
                }
            }
        }
    });

    // スマホならスクロールに応じて動画を固定できるようdivを移動させる
    // フルスクリーンで無いことを確認してから
    // 縦画面のみ発動
    if (windowWidth <= 500 && (orientation === 0 || orientation === undefined) &&
        (isset(document.getElementById('dplayer-script').previousElementSibling) &&
        document.getElementById('dplayer-script').previousElementSibling.getAttribute('id') == 'dplayer') && !isFullScreen()) {
        $('#content-wrap').before($('#dplayer'));
    } else if (windowWidth > 500 && (isset(document.getElementById('content-wrap').previousElementSibling) &&
               document.getElementById('content-wrap').previousElementSibling.getAttribute('id') == 'dplayer') && !isFullScreen()) {
        $('#dplayer-script').before($('#dplayer'));
    }

    if (timer !== false) {
        clearTimeout(timer);
    }

    timer = setTimeout(function() {

        // リサイズが終了した後に実行する

        // 1024px以上
        if (windowWidth > 1024){

            // リサイズ対象の幅
            var targetWidth = document.getElementById('content-wrap').clientWidth * 0.99;
            var targetHeight = document.getElementById('content-wrap').clientHeight;
            var headerheight = (settings['vertical_navmenu'] ? 0 : 54); // ヘッダーの高さ分
            var percentage = (windowHeight - headerheight) / targetHeight; // windowHeight は targetHeight の percentage 倍

            // リサイズする
            var resize = Math.ceil(targetWidth * percentage) + 'px';
            document.getElementById('main').style.maxWidth = resize;

            // 高さのずれを補正
            var gap = (windowHeight - headerheight) - document.getElementById('content-wrap').clientHeight;

            // なぜ大体ずれ×2.3追加で直るかはしらない（本当は ↑ だけで綺麗に収まるはず・調べてもよくわからなかったので妥協…）
            document.getElementById('main').style.maxWidth = Math.ceil(targetWidth * percentage + (gap * 2.3)) + 'px';

            // フッターも忘れずに
            document.getElementById('footer').style.maxWidth = Math.ceil(targetWidth * percentage + (gap * 2.3)) + 'px';

        }

        // 縦メニューで横の余白が広い時、メインカラムが右に寄っているように見えるのを解消する
        // メインカラムの margin-left が 0px 以外なら、body に margin-right: 54px を設定する
        // メインカラムの margin-left が 0px なら、body の margin-right: 54px を解除する
        if (settings['vertical_navmenu']) {
            if (getComputedStyle(document.getElementById('main')).marginLeft !== '0px') {
                document.body.style.marginRight = '54px';
            } else {
                document.body.style.marginRight = '';
            }
        }

        // DOMContentLoaded or resize(横方向)
        if (event.type == 'DOMContentLoaded' || (event.type == 'resize' && lastWindowWidth != windowWidth)){
            // 幅を記録しておく
            lastWindowWidth = windowWidth;
            // スライダーのサイズを更新（重要）
            // プレイヤー周辺を独自でリサイズしている関係で Swiper 本体のリサイズ検知機構がうまく動かない
            // そのため手動でサイズを更新してあげる必要がある
            if (slider) {
                slider.update();
            }
        }

    }, 200);
});


$(function() {

    // ***** スライダー *****

    // タブを初期化
    slider = new Swiper('#broadcast-box', {
        slideActiveClass: 'swiper-slide-active',
        slidesPerView: 'auto',  // コンテナーに同時に表示されるスライドの数
        autoHeight: true,  // コンテナの高さを自動調整するか
        resizeObserver: true, // ResizeObserver を利用する
        updateOnWindowResize: true,  // リサイズ時にコンテナの幅や高さを調整する
        watchSlidesProgress: true,  // 各スライドの進行状況を計算する
    });

    // ボタンを初期化
    // 最初に表示するスライドのインデックス
    // localStorage の値か、ない場合は 0（地デジ）
    const sliderInitialIndex = localStorage.getItem('tvrp-slider-index') || 0;
    // スライドする
    slider.slideTo(sliderInitialIndex, 0);  // 第二引数を 0 にするとアニメーションされない
    // ハイライト用のクラスを付与
    $(`.broadcast-button[data-index=${sliderInitialIndex}]`).addClass('swiper-slide-thumb-active');

    // ボタンクリックでスライド
    $('.broadcast-button').click((event) => {
        // クリックされたボタンのインデックス
        const sliderCurrentIndex = event.target.dataset.index;
        // スライドする
        slider.slideTo(sliderCurrentIndex);
        // 一旦全てのクラスを削除
        $('.broadcast-button').removeClass('swiper-slide-thumb-active');
        // ハイライト用のクラスを付与
        $(`.broadcast-button[data-index=${sliderCurrentIndex}]`).addClass('swiper-slide-thumb-active');
    });

    // スライド時のイベント
    slider.on('slideChange', () => {
        // 一旦全てのクラスを削除
        $('.broadcast-button').removeClass('swiper-slide-thumb-active');
        // ハイライト用のクラスを付与
        $(`.broadcast-button[data-index=${slider.activeIndex}]`).addClass('swiper-slide-thumb-active');
    });

    // ページから離れるときのイベント
    $(window).on('beforeunload', () => {
        // localStorage に現在アクティブなスライドのインデックスを保存
        localStorage.setItem('tvrp-slider-index', slider.activeIndex);
    });

    // ***** フルスクリーン *****

    $('#fullscreen').click((event) => {

        // プロパティ名を統一
        const fullscreenElement = (
            document.fullscreenElement || // HTML5 standard
            document.mozFullScreenElement || // Gecko
            document.webkitFullscreenElement || // Webkit
            document.webkitCurrentFullScreenElement || // Webkit (old)
            document.msFullscreenElement // Trident
        );

        // すでにフルスクリーンになっている
        if (fullscreenElement && fullscreenElement.tagName.toLowerCase() === 'html') {

            // メソッド名を統一
            document.exitFullscreen = (
                document.exitFullscreen || // HTML5 standard
                document.mozCancelFullScreen || // Gecko
                document.webkitExitFullscreen || // Webkit
                document.webkitCancelFullScreen || // Webkit (old)
                document.msExitFullscreen // Trident
            );

            // フルスクリーンを終了
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }

        } else {

            // メソッド名を統一
            document.documentElement.requestFullscreen = (
                document.documentElement.requestFullscreen || // HTML5 standard
                document.documentElement.mozRequestFullScreen || // Gecko
                document.documentElement.webkitRequestFullscreen || // Webkit
                document.documentElement.webkitRequestFullScreen || // Webkit (old)
                document.documentElement.msRequestFullscreen // Trident
            );

            // フルスクリーンを開始
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else {  // FullScreen API をサポートしていないブラウザ（主に iOS Safari ）
                if (/Mobile/.test(navigator.userAgent) && /Safari/.test(navigator.userAgent)) {
                    toastr.error('iOS Safari では、フルスクリーン機能はサポートされていません。');
                } else {
                    toastr.error('フルスクリーン機能はサポートされていません。');
                }
            }
        }
    });

    // フルスクリーン状態が変わったとき
    $(document).on('fullscreenchange webkitfullscreenchange', () => {

        // プロパティ名を統一
        const fullscreenElement = (
            document.fullscreenElement || // HTML5 standard
            document.mozFullScreenElement || // Gecko
            document.webkitFullscreenElement || // Webkit
            document.webkitCurrentFullScreenElement || // Webkit (old)
            document.msFullscreenElement // Trident
        );

        // フルスクリーンを開始
        if (fullscreenElement && fullscreenElement.tagName.toLowerCase() === 'html') {

            $('#fullscreen').attr('aria-label', 'フルスクリーン表示を終了します');
            $('#fullscreen i').removeClass('fa-expand').addClass('fa-compress');
            $('#fullscreen .menu-link-href').text('フルスクリーンを終了');

        // フルスクリーンを終了
        } else {

            $('#fullscreen').attr('aria-label', '画面全体をフルスクリーンで表示します');
            $('#fullscreen i').removeClass('fa-compress').addClass('fa-expand');
            $('#fullscreen .menu-link-href').text('フルスクリーンで表示');
        }
    });

    // ***** スクロールでプレイヤーをフロート表示 *****

    // 個人設定で有効
    if (settings['player_floating']) {

        // スクロール時のイベント
        $(window).scroll(() => {

            // 501px より大きい（スマホを除外）
            if (window.innerWidth > 500) {

                const position_current = $(this).scrollTop() + (settings['vertical_navmenu'] ? 0 : 54);
                const position_target = $('#epg-box').offset().top; // 計算めんどいのであえて jQuery

                // State を取得
                const state = document.getElementById('state').value;

                // State が Offline 以外
                if (state !== 'Offline' && state !== '' && state !== undefined) {

                    // ターゲット座標以上
                    if (position_current > position_target) {
                        if (!dp.video.classList.contains('dplayer-floating')) {

                            // 一旦 transition を削除
                            dp.video.style.transition = 'none';
                            // 透明度を 0 に設定
                            dp.video.style.opacity = 0;
                            // transition を再付与
                            dp.video.style.transition = 'opacity 0.2s ease-in-out';

                            setTimeout(() => {

                                // 動画をフロート化
                                dp.video.classList.add('dplayer-floating');
                                // 透明度を 1 に設定
                                dp.video.style.opacity = 1;

                            }, 200);
                        }

                    // ターゲット座標以内
                    } else if (position_current < position_target) {
                        if (dp.video.classList.contains('dplayer-floating')) {

                            // 透明度を 0 に設定
                            dp.video.style.opacity = 0;

                            setTimeout(() => {

                                // 動画のフロート化を解除
                                dp.video.classList.remove('dplayer-floating');
                                // 透明度を 1 に設定
                                dp.video.style.opacity = 1;

                            }, 200);
                        }
                    }
                }

            } else {

                // 動画のフロート化を解除
                dp.video.classList.remove('dplayer-floating');
            }
        });
    }

    // ***** リロードボタン *****

    $('#reload').click(function(){
        $('#cover').addClass('open');
        location.reload(true);
    });

    // ***** サブチャンネル *****

    $('#subchannel-show').click(function(){
        $('#nav-close').toggleClass('open');
        // Cookieに書き込み
        settings['subchannel_show'] = true;
        var json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
        // リロード
        setTimeout(function(){
            location.reload();
        }, 300);
    });

    $('#subchannel-hide').click(function(){
        $('#nav-close').toggleClass('open');
        // Cookieに書き込み
        settings['subchannel_show'] = false;
        var json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
        // リロード
        setTimeout(function(){
            location.reload();
        }, 300);
    });

    // ***** Ｌ字画面クロップ設定 *****

    $('#ljicrop').click(function(){
        $('#nav-close').toggleClass('open');
        $('#ljicrop-box').toggleClass('open');
    });

    $('#ljicrop-box').click(function(event){
        $('#nav-close').removeClass('open');
        $('#ljicrop-box').removeClass('open');
    });

    $('#ljicrop-wrap').click(function(event){
        event.stopPropagation();
    });

    // 設定読み込み
    // 設定が存在しないなら初期値を使う
    $('input[name=ljicrop_toggle]').prop('checked', Boolean(Number(localStorage.getItem('tvrp-ljicrop-toggle') || '0')));  // Boolean に戻す
    $('input[name="ljicrop_magnification"]').val(localStorage.getItem('tvrp-ljicrop-magnification') || 100);
    $('input[name="ljicrop_coordinateX"]').val(localStorage.getItem('tvrp-ljicrop-coordinateX') || 0);
    $('input[name="ljicrop_coordinateY"]').val(localStorage.getItem('tvrp-ljicrop-coordinateY') || 0);
    $('input[name="ljicrop_type"]').val([localStorage.getItem('tvrp-ljicrop-type') || 'upperright']);  // ラジオボタンにおいては配列にするのが重要

    // イベントハンドラーを設定
    $('input[name="ljicrop_toggle"]').on('change', ljicrop);
    $('input[name="ljicrop_magnification"]').on('input', ljicrop);
    $('input[name="ljicrop_coordinateX"]').on('input', ljicrop);
    $('input[name="ljicrop_coordinateY"]').on('input', ljicrop);
    $('input[name="ljicrop_type"]').on('input', ljicrop);

    // Ｌ字クロップを実行
    ljicrop();

    function ljicrop() {

        // 要素を取得
        const ljicrop_video = dp.video;

        if (ljicrop_video) {

            // 有効/無効
            const ljicrop_toggle = document.querySelector('input[name=ljicrop_toggle]').checked;
            // 拡大率
            const ljicrop_magnification = parseInt(document.querySelector('input[name=ljicrop_magnification]').value);
            // X座標
            const ljicrop_coordinateX = parseInt(document.querySelector('input[name=ljicrop_coordinateX]').value);
            // Y座標
            const ljicrop_coordinateY = parseInt(document.querySelector('input[name=ljicrop_coordinateY]').value);
            // 拡大起点
            const ljicrop_type = document.querySelector('input[name=ljicrop_type]:checked').value;

            // 設定を保存
            localStorage.setItem('tvrp-ljicrop-toggle', Number(ljicrop_toggle)); // Number に変換
            localStorage.setItem('tvrp-ljicrop-magnification', ljicrop_magnification);
            localStorage.setItem('tvrp-ljicrop-coordinateX', ljicrop_coordinateX);
            localStorage.setItem('tvrp-ljicrop-coordinateY', ljicrop_coordinateY);
            localStorage.setItem('tvrp-ljicrop-type', ljicrop_type);

            // 表示
            document.querySelector('#ljicrop-magnification-percentage').textContent = ljicrop_magnification + '%';
            document.querySelector('#ljicrop-coordinatex-percentage').textContent = ljicrop_coordinateX + '%';
            document.querySelector('#ljicrop-coordinatey-percentage').textContent = ljicrop_coordinateY + '%';

            // 無効ならフォームを無効化
            if (!ljicrop_toggle) {

                // disabled を設定
                $('input[name="ljicrop_magnification"]').prop('disabled', true);
                $('input[name="ljicrop_coordinateX"]').prop('disabled', true);
                $('input[name="ljicrop_coordinateY"]').prop('disabled', true);
                $('input[name="ljicrop_type"]').prop('disabled', true);

                // スタイルを除去
                ljicrop_video.style.position = '';
                ljicrop_video.style.transform = '';
                ljicrop_video.style.transformOrigin = '';

            // 有効ならＬ字クロップを実行
            } else if (ljicrop_toggle) {

                // disabled を解除
                $('input[name="ljicrop_magnification"]').prop('disabled', false);
                $('input[name="ljicrop_coordinateX"]').prop('disabled', false);
                $('input[name="ljicrop_coordinateY"]').prop('disabled', false);
                $('input[name="ljicrop_type"]').prop('disabled', false);

                // 全てデフォルト（オフ）状態ならスタイルを削除
                if (ljicrop_magnification === 100 && ljicrop_coordinateX === 0 && ljicrop_coordinateY === 0) {

                    // 空文字を入れると style 属性から当該スタイルが除去される
                    ljicrop_video.style.position = '';
                    ljicrop_video.style.transform = '';
                    ljicrop_video.style.transformOrigin = '';

                } else {

                    // transform をクリア
                    ljicrop_video.style.position = 'relative';
                    ljicrop_video.style.transform = '';

                    // 拡大起点別に
                    switch (ljicrop_type) {

                        // 右上
                        case 'upperright': {

                            // 拡大起点を右上に設定
                            ljicrop_video.style.transformOrigin = 'right top';

                            // 動画の表示サイズを 100% として、拡大率を超えない範囲で座標をずらす
                            ljicrop_video.style.transform += `translateX(${(ljicrop_magnification - 100) * (ljicrop_coordinateX / 100)}%) `;
                            ljicrop_video.style.transform += `translateY(-${(ljicrop_magnification - 100) * (ljicrop_coordinateY / 100)}%) `;
                            break;
                        }

                        // 右下
                        case 'lowerright': {

                            // 拡大起点を右下に設定
                            ljicrop_video.style.transformOrigin = 'right bottom';

                            // 動画の表示サイズを 100% として、拡大率を超えない範囲で座標をずらす
                            ljicrop_video.style.transform += `translateX(${(ljicrop_magnification - 100) * (ljicrop_coordinateX / 100)}%) `;
                            ljicrop_video.style.transform += `translateY(${(ljicrop_magnification - 100) * (ljicrop_coordinateY / 100)}%) `;
                            break;
                        }

                        // 左上
                        case 'upperleft': {

                            // 拡大起点を左上に設定
                            ljicrop_video.style.transformOrigin = 'left top';

                            // 動画の表示サイズを 100% として、拡大率を超えない範囲で座標をずらす
                            ljicrop_video.style.transform += `translateX(-${(ljicrop_magnification - 100) * (ljicrop_coordinateX / 100)}%) `;
                            ljicrop_video.style.transform += `translateY(-${(ljicrop_magnification - 100) * (ljicrop_coordinateY / 100)}%) `;
                            break;
                        }

                        // 左下
                        case 'lowerleft': {

                            // 拡大起点を左下に設定
                            ljicrop_video.style.transformOrigin = 'left bottom';

                            // 動画の表示サイズを 100% として、拡大率を超えない範囲で座標をずらす
                            ljicrop_video.style.transform += `translateX(-${(ljicrop_magnification - 100) * (ljicrop_coordinateX / 100)}%) `;
                            ljicrop_video.style.transform += `translateY(${(ljicrop_magnification - 100) * (ljicrop_coordinateY / 100)}%) `;
                            break;
                        }
                    }

                    // video 要素を拡大
                    // transform は後ろから適用されるため、先にリサイズしておかないと正しく座標をずらせない
                    // ref: https://techblog.kayac.com/css-transform-tips
                    ljicrop_video.style.transform += `scale(${ljicrop_magnification / 100})`;
                }
            }
        }
    }


    // ***** キーボードショートカット一覧 *****

    $('#hotkey').click(function(){
        $('#nav-close').toggleClass('open');
        $('#hotkey-box').toggleClass('open');
    });

    $('#hotkey-box').click(function(event){
        $('#nav-close').removeClass('open');
        $('#hotkey-box').removeClass('open');
    });

    $('#hotkey-wrap').click(function(event){
        event.stopPropagation();
    });

    // ***** 終了時に行う処理 *****

    /*
    // リロードと終了との区別ができないので諦めた
    // Ajax
    $(window).on('beforeunload', function(event){

        console.log('unloaded');

        $.ajax({
            url: '/settings/',
            type: 'post',
            data: {state: 'Offline', stream: stream},
            cache: false,
            async: false,
        }).done(function(data) {
        });

        // 最近ではunload時にAjaxが実行できなくなっているらしいので
        // Navigator.sendBeacon() を使う
        var payload = new FormData();
        payload.append('state', 'Offline');
        payload.append('stream', stream);
        navigator.sendBeacon('/settings/', payload)

    });
    */

    // ***** キャスト関連 *****

    // デバイスをスキャン
    $('#cast-scan').click(function(){
        $('#cast-scan').prop('disabled', true).addClass('disabled');
        toastr.info('スキャンしています…');
        $.ajax({
            url: '/api/chromecast/' + stream,
            type: 'post',
            data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'scan'},
            dataType: 'json',
            cache: false,
        }).done(function(data) {
            $('#cast-scan').prop('disabled', false).removeClass('disabled');
            if (data['scan'] == true){
                toastr.success('スキャンに成功しました。');
            } else {
                toastr.error('スキャンに失敗しました…');
                toastr.error('Chromecast が同じ Wi-Fi ネットワーク上にないか、Bonjour がインストールされていない可能性があります。');
                if (data['bonjour'] == false){
                    toastr.error('TVRemotePlus (Apache) を管理者権限で起動させてから、もう一度試してみてください。');
                } else {
                    toastr.error('もう一度試してみてください。');
                }
            }
        });
    });

    // ボックス開閉
    $('#chromecast-box').click(function(event){
        $('#nav-close').removeClass('open');
        $('#chromecast-box').removeClass('open');
    });
    $('#chromecast-wrap').click(function(event){
        event.stopPropagation();
    });

    // 現在すでにキャスト中かを調べる
    $.ajax({
        url: '/api/chromecast/' + stream,
        type: 'post',
        data: {_csrf_token: Cookies.get('tvrp_csrf_token')},
        dataType: 'json',
        cache: false,
    }).done(function(data) {
        if (data['status'] == 'play'){
            $('#cast-toggle > .menu-link-href').text('キャストを終了');
            setTimeout(function(){
                var state = document.getElementById('state').value;
                if (state == 'File'){
                    dp.pause();
                }
                controlServerCast(state);
            }, 1500);
        }
    });

    // ︙メニューの「キャストを開始」がクリックされたとき
    $('#cast-toggle').click(function(){

        // キャスト画面
        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを開始'){

            $('#nav-close').toggleClass('open');
            $('#chromecast-box').toggleClass('open');
            $('html').removeClass('open');

            $.ajax({
                url: '/api/chromecast/' + stream,
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token')},
                dataType: 'json',
                cache: false,
            }).done(function(data) {

                var html = '';

                // デバイスごとに
                Object.keys(data['scandata']).forEach(function(key){

                    // htmlを生成
                    html +=
                        `<div class="chromecast-device" data-ip="` + data['scandata'][key]['ip'] + `" data-port="` + data['scandata'][key]['port'] + `">
                            <i class="fas fa-tv"></i>
                            <div class="chromecast-name-box">
                                <span class="chromecast-name">` + data['scandata'][key]['friendlyname'] + `</span>
                                <span class="chromecast-type">` + data['scandata'][key]['type'] + `</span>
                            </div>
                        </div>`;
                });

                // 空ならメッセージを入れる
                if (html == ''){

                    // htmlを生成
                    html +=
                        `<div class="error">
                            キャストするデバイスがありません。<br>
                            右上の︙メニューから、デバイスをスキャンしてください。<br>
                        </div>`;
                }

                // 一気に代入
                document.getElementById('chromecast-device-box').innerHTML = html;

                // クリックイベントを付与
                document.querySelectorAll('.chromecast-device').forEach(function(elem) {

                    // キャスト開始
                    elem.addEventListener('click', function() {
                        initServerCast(elem);
                    });

                });
            });

        // キャスト終了
        } else if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

            $.ajax({
                url: '/api/chromecast/' + stream,
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'stop'},
                dataType: 'json',
                cache: false,
            }).done(function(data) {

                $('#cast-toggle > .menu-link-href').text('キャストを開始');
                toastr.success('キャストを終了しました。');
                // 端末のミュートを解除
                dp.video.muted = false;
                // 音量を戻す
                dp.video.volume = 1;

                // 動画表示を戻す
                dp.video.style.opacity = 1;
                $('.dplayer-casting').css('opacity', 0);

            });
        }

    });

});

// *** Chromecast 関連の関数 ***

// JavaScript からのキャストに対応しているかどうかのリスナー
window.__onGCastApiAvailable = function(isAvailable) {
    if (isAvailable){
        timer = setInterval(function() {
            // console.log('typeof cast !== undefined: ' + (typeof cast !== 'undefined'))
            if (typeof cast !== 'undefined') {
                // console.log('initBrowserCast()')
                initBrowserCast();
                clearInterval(timer);
            }
        }, 500);
        setInterval(function() {
            $('google-cast-launcher').css('display', 'block');
        }, 1000);
    }
};

// JavaScript から Chromecast を初期化・起動する関数
function initBrowserCast(){

    // キャストオプション
    cast.framework.CastContext.getInstance().setOptions({
        receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
        autoJoinPolicy: chrome.cast.AutoJoinPolicy.PAGE_SCOPED,
    });

    // リモートプレーヤーを初期化
    remotePlayer = new cast.framework.RemotePlayer();
    remotePlayerController = new cast.framework.RemotePlayerController(remotePlayer);

    // キャストボタンクリック時に発火
    remotePlayerController.addEventListener(
        cast.framework.RemotePlayerEventType.IS_CONNECTED_CHANGED, function() {

            if (remotePlayer.isConnected){

                $('#cast-toggle > .menu-link-href').text('キャストを終了');
                toastr.info('キャストを開始しています…');

                // 動画を一旦止める
                dp.video.pause();
                // 端末はミュートにする
                dp.video.muted = true;
                // 音量を半分にする
                dp.video.volume = 0.7;

                // シークを通知
                $('#dplayer').addClass('dplayer-seeking');
                // ローディング表示
                $('#dplayer').addClass('dplayer-loading');
                // 動画表示を消す
                dp.video.style.transition = 'opacity 0.3s ease';
                dp.video.style.opacity = 0;

                // キャスト端末の名前
                var castName = cast.framework.CastContext.getInstance().getCurrentSession().getSessionObj().receiver.friendlyName;

                // 「〇〇で再生しています」を出す
                if (!$('.dplayer-casting').length){
                    $('.dplayer-danmaku').before('<div class="dplayer-casting">' + castName + 'で再生しています</div>');
                } else if ($('.dplayer-casting').text() !== castName + 'で再生しています'){
                    $('.dplayer-casting').text(castName + 'で再生しています');
                }
                $('.dplayer-casting').css('opacity', 0.8);

                // 制御は別の関数に投げる
                controlBrowserCast();

            } else {

                $('#cast-toggle > .menu-link-href').text('キャストを開始');
                toastr.success('キャストを終了しました。');
                // 端末のミュートを解除
                dp.video.muted = false;
                // 音量を戻す
                dp.video.volume = 1;

                // 動画表示を戻す
                dp.video.style.opacity = 1;
                $('.dplayer-casting').css('opacity', 0);
            }
        }
    );

}

// JavaScript から Chromecast を制御する関数
function controlBrowserCast(){

    // セッション周り
    var castSession = cast.framework.CastContext.getInstance().getCurrentSession();
    var mediaInfo = new chrome.cast.media.MediaInfo(streamurl, streamtype);

    mediaInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
    mediaInfo.metadata.metadataType = chrome.cast.media.MetadataType.GENERIC;

    // 動画の読み込みをリクエストする
    var request = new chrome.cast.media.LoadRequest(mediaInfo);

    // 読み込み後に発火
    castSession.loadMedia(request).then(
        function() {

            setTimeout(function(){
                toastr.success('キャストを開始しました。');
            }, 3000);

            // Chromecast制御用RemotePlayerの初期化
            var player = new cast.framework.RemotePlayer();
            var playerController = new cast.framework.RemotePlayerController(player);

            // ここでChromecastと再生状態をだいたい同期させる
            var playerState = 'BUFFERING'; // playerStateを比較用に格納
            var buffering = false; // バッファリング中に再生/停止に反応させないための変数

            // メディア状態が変わった(IDLE・BUFFERING・PLAYING・PAUSED)とき
            playerController.addEventListener(
                cast.framework.RemotePlayerEventType.PLAYER_STATE_CHANGED, function() {

                    // 読み込み中のとき
                    if (player.playerState == 'BUFFERING'){

                        buffering = true;
                        // シークを通知
                        $('#dplayer').addClass('dplayer-seeking');
                        // ローディング表示
                        $('#dplayer').addClass('dplayer-loading');
                        // 動画を一旦止める
                        dp.video.pause();

                    // 以前読み込み中でかつ今再生中のとき
                    } else if (player.playerState === 'PLAYING' && playerState === 'BUFFERING'){

                        buffering = false;
                        // シーク通知を消す
                        $('#dplayer').removeClass('dplayer-seeking');
                        // ローディング表示を消す
                        $('#dplayer').removeClass('dplayer-loading');
                        // 動画をもう一度再生させる(同期させる)
                        dp.video.play();

                    // アイドル状態 (=再生終了)
                    } else if (player.playerState === 'IDLE' && playerState !== 'IDLE'){

                        // Chromecast を終了する

                        // キャストを終了
                        castSession.endSession(true);

                        buffering = false;
                        // シーク通知を消す
                        $('#dplayer').removeClass('dplayer-seeking');
                        // ローディング表示を消す
                        $('#dplayer').removeClass('dplayer-loading');

                    }

                    // playerState を比較用に記録
                    playerState = player.playerState;

                }
            );

            // ミュート解除
            player.volumeLevel = dp.video.volume;
            playerController.setVolumeLevel();
            if (player.isMuted){
                playerController.muteOrUnmute();
            }

            // 最初に現在の位置までシーク
            player.currentTime = dp.video.currentTime;
            playerController.seek();

            // 再生
            $('.dplayer-video-current').on('play playing', function(){
                if ($('#cast-toggle > .menu-link-href').text() === 'キャストを終了'){
                    if (player.isPaused && !buffering){
                        dp.video.currentTime = player.currentTime;
                        playerController.playOrPause();
                    }
                }
            });

            // 停止
            $('.dplayer-video-current').on('pause', function(){
                if ($('#cast-toggle > .menu-link-href').text() === 'キャストを終了'){
                    if (!player.isPaused && !buffering){
                        dp.video.currentTime = player.currentTime;
                        playerController.playOrPause();
                    }
                }
            });

            // シーク
            $('.dplayer-video-current').on('seeking', function(){
                if ($('#cast-toggle > .menu-link-href').text() === 'キャストを終了'){
                    player.currentTime = dp.video.currentTime;
                    playerController.seek();
                    dp.template.notice.style.opacity = 0;
                }
            });

            // 音量
            $('.dplayer-video-current').on('volumechange', function(){
                if ($('#cast-toggle > .menu-link-href').text() === 'キャストを終了'){
                    dp.video.muted = true; // 端末はミュートにする
                    player.volumeLevel = dp.video.volume;
                    playerController.setVolumeLevel();
                    dp.template.notice.style.opacity = 0;
                }
            });

        },
        function(e) {
            console.error(e);
        }
    );
}

// サーバー経由で Chromecast を初期化・起動する関数
function initServerCast(elem){

    var state = document.getElementById('state').value;

    toastr.info('キャストを開始しています…');

    // 動画を一旦止める
    dp.video.pause();
    // 端末はミュートにする
    dp.video.muted = true;
    // 音量を半分にする
    dp.video.volume = 0.7;

    // シークを通知
    $('#dplayer').addClass('dplayer-seeking');
    // ローディング表示
    $('#dplayer').addClass('dplayer-loading');
    // 動画表示を消す
    dp.video.style.transition = 'opacity 0.3s ease';
    dp.video.style.opacity = 0;

    // キャスト端末の名前
    var castName = $(elem).find('.chromecast-name').text();

    // 「〇〇で再生しています」を出す
    if (!$('.dplayer-casting').length){
        $('.dplayer-danmaku').before('<div class="dplayer-casting">' + castName + 'で再生しています</div>');
    } else if ($('.dplayer-casting').text() !== castName + 'で再生しています'){
        $('.dplayer-casting').text(castName + 'で再生しています');
    }
    $('.dplayer-casting').css('opacity', 0.7);

    // ボックスを閉じる
    $('#nav-close').removeClass('open');
    $('#chromecast-box').removeClass('open');
    $('#hotkey-box').removeClass('open');
    $('#ljicrop-box').removeClass('open');
    $('html').removeClass('open');

    // Chromecast を起動
    $.ajax({
        url: '/api/chromecast/' + stream,
        type: 'post',
        data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'start', ip: $(elem).attr('data-ip'), port: $(elem).attr('data-port')},
        dataType: 'json',
        cache: false,
    }).done(function(data) {

        if (data['status'] == 'play'){

            $('#cast-toggle > .menu-link-href').text('キャストを終了');

            // 制御は別の関数に投げる
            controlServerCast(state);

        } else {
            toastr.error('キャストの開始に失敗しました…');
        }

    });
}

// サーバー経由で Chromecast を制御する関数
function controlServerCast(state){

    setTimeout(function(){
        toastr.success('キャストを開始しました。');
    }, 1000);

    // シーク通知を消す
    $('#dplayer').removeClass('dplayer-seeking');
    // ローディング表示を消す
    $('#dplayer').removeClass('dplayer-loading');

    // ファイル再生のみ
    if (state == 'File'){

        // 再生系処理
        $.ajax({
            url: '/api/chromecast/' + stream,
            type: 'post',
            data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'seek', arg: dp.video.currentTime},
            dataType: 'json',
            cache: false,
        }).done(function(data) {
            dp.video.muted = true;
            dp.pause();
        });

    } else {
        dp.video.muted = true;
    }

    // 再生
    $('.dplayer-video-current').on('play', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

            $.ajax({
                url: '/api/chromecast/' + stream,
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'restart'},
                dataType: 'json',
                cache: false,
            }).done(function(data) {
            });

        }
    });

    // 一時停止
    $('.dplayer-video-current').on('pause', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

            // ファイル再生のみ
            if (state == 'File'){

                $.ajax({
                    url: '/api/chromecast/' + stream,
                    type: 'post',
                    data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'seek', arg: dp.video.currentTime},
                    dataType: 'json',
                    cache: false,
                }).done(function(data) {
                });

            } else {

                $.ajax({
                    url: '/api/chromecast/' + stream,
                    type: 'post',
                    data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'pause'},
                    dataType: 'json',
                    cache: false,
                }).done(function(data) {
                    dp.pause();
                });

            }
        }
    });

    // ファイル再生のみ
    if (state == 'File'){

        // シーク
        $('.dplayer-video-current').on('seeking', function(){

            if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

                $.ajax({
                    url: '/api/chromecast/' + stream,
                    type: 'post',
                    data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'seek', arg: dp.video.currentTime},
                    dataType: 'json',
                    cache: false,
                }).done(function(data) {
                    dp.pause();
                });

            }
        });
    }

    // 音量
    $('.dplayer-video-current').on('volumechange', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

            $.ajax({
                url: '/api/chromecast/' + stream,
                type: 'post',
                data: {_csrf_token: Cookies.get('tvrp_csrf_token'), cmd: 'volume', arg: dp.video.volume},
                dataType: 'json',
                cache: false,
            }).done(function(data) {
            });

            dp.video.muted = true; // 端末はミュートにする

        }
    });
}

