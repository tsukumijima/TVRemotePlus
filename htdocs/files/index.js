  
  // 生放送・ファイル再生共通その2 (script.jsが肥大化したためこっちに)

  // document.getElementsByTagName('head')[0].insertAdjacentHTML('beforeend', '<style>#main { opacity: 0; }</style>');

  // php の isset みたいなの
  function isset(data){
    if (data === "" || data === null || data === undefined){
      return false;
    } else {
      return true;
    }
  };

  // ロード時 & リサイズ時に発火
  $(window).on('DOMContentLoaded resize', function(event){

    if (event.type == 'DOMContentLoaded'){

      // フェード
      // $('#main').delay(100).velocity('fadeIn', 500);

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
    var _width = document.body.clientWidth;
    // 画面の高さを取得
    var _height = window.innerHeight;
    // 画面の向きを取得
    var orientation = window.orientation;

    $(window).on('load', function(){
      // スマホ・タブレットならplaceholder書き換え
      if (_width <= 1024){
        document.getElementById('tweet').setAttribute('placeholder', 'ツイート');
      } else {
        document.getElementById('tweet').setAttribute('placeholder', 'ツイート (Ctrl+Enterで送信)');
      }
    });

    // スマホならスクロールに応じて動画を固定できるようdivを移動させる
    // フルスクリーンで無いことを確認してから
    // 縦画面のみ発動
    if (_width <= 500 && (orientation === 0 || orientation === undefined)
        && (isset(document.getElementById('dplayer-script').previousElementSibling)
            && document.getElementById('dplayer-script').previousElementSibling.getAttribute('id') == 'dplayer')
        && (document.fullscreenElement === null || document.webkitFullscreenElement === null)){
      $('#content-wrap').before($('#dplayer'));
    } else if (_width > 500
      && (isset(document.getElementById('content-wrap').previousElementSibling)
          && document.getElementById('content-wrap').previousElementSibling.getAttribute('id') == 'dplayer')
        && (document.fullscreenElement === null || document.webkitFullscreenElement === null)){
      $('#dplayer-script').before($('#dplayer'));
    }

    // 1024px以上
    if (_width > 1024){

      // ウィンドウを読み込んだ時・リサイズされた時に発動
      // 何故か上手くいかないので8回繰り返す
      // 正直どうなってるのか自分でもわからない
      var result = 0; // 初期化

      while (true){
        var WindowHeight = _height - document.getElementById('top').clientHeight;
        var width = $('section').width();

        // Twitter非表示時
        if (!settings['twitter_show']){
          var height= $('#dplayer').width() * (9 / 16);
        } else {
          var height= $('#dplayer').width() * (9 / 16) + 136; // $('#tweet-box').height()
        }

        // 同じならループを抜ける
        if (result == (width * WindowHeight) / height) break;

        // widthが変なとき用
        if (width < ($(window).width() / 2)){
          $('section').css('max-width', '1250px');
          break;
        }

        result = (width * WindowHeight) / height;
        // console.log('width: ' + width);
        // console.log('result: ' + result);
        $('section').css('max-width', result + 'px');

      }
    }

    // スライダー関係
    var galleryThumbs = new Swiper('#broadcast-tab-box', {
      slidesPerView: 'auto',
      watchSlidesVisibility: true,
      watchSlidesProgress: true,
      slideActiveClass: 'swiper-slide-active'
    });
    galleryThumbs.on('tap', function () {
      var current = galleryTop.activeIndex;
      galleryThumbs.slideTo(current, 500, true);
    });
    var galleryTop = new Swiper('#broadcast-box', {
      autoHeight: true,
      thumbs: {
        swiper: galleryThumbs
      }
    });

  });
  
  $(function(){

    $.ajax({
      url: '/api/chromecast',
      dataType: 'json',
      cache: false,
      success: function(data) {
        if (data['status'] == 'play'){
          $('#cast-toggle > .menu-link-href').text('キャストを終了');
          setTimeout(function(){
            var state = document.getElementById('state').value;
            if (state == 'File'){
              dp.pause();
            }
            chromecast(state);
          }, 1500);
        }
      }
    });

    // 再生開始ボックス
    $('body').on('click','.broadcast-wrap',function(){
      $('#broadcast-stream-title').html($(this).find('.broadcast-channel').html() + ' ' + $(this).find('.broadcast-name').html());
      $('#broadcast-stream-info').html($(this).find('.broadcast-title-id').html());
      $('#broadcast-stream-channel').val($(this).find('.broadcast-channel-id').text());
      // 地デジ・BSCS判定
      if ($(this).find('.broadcast-channel-id').text() < 55){
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
      if (settings['onclick_stream']){
        $('#broadcast-stream-box').hide();
        $('.bluebutton').click();
      }
    });

    // 再生開始
    $('.bluebutton').click(function(){
      $('.bluebutton').addClass('disabled');
    });

    // キャンセル
    $('.redbutton').click(function(event){
      $('#nav-close').removeClass('open');
      $('#broadcast-stream-box').removeClass('open');
      $('#chromecast-box').removeClass('open');
      $('html').removeClass('open');
    });

    // キャスト関連
    $('#cast-toggle').click(function(){

      // キャスト画面
      if ($('#cast-toggle > .menu-link-href').text() == 'キャストを開始'){

        $('#menu-content').velocity($('#menu-content').is(':visible') ? 'slideUp' : 'slideDown', 150);
        $('#menu-content').removeClass('open');
        $('#nav-close').toggleClass('open');
        $('#chromecast-box').toggleClass('open');
        $('html').removeClass('open');

        $.ajax({
          url: '/api/chromecast',
          dataType: 'json',
          cache: false,
          success: function(data) {

            var html = '';
            
            // デバイスごとに
            Object.keys(data['scandata']).forEach(function(key){

              // htmlを生成
              html = html + '<div class="chromecast-device" data-ip="' + data['scandata'][key]['ip'] +'" data-port="' + data['scandata'][key]['port'] +'">' +
                            '  <i class="fas fa-tv"></i>' +
                            '  <div class="chromecast-name-box">' +
                            '    <span class="chromecast-name">' + data['scandata'][key]['friendlyname'] +'</span>' +
                            '    <span class="chromecast-type">' + data['scandata'][key]['type'] +'</span>' +
                            '  </div>' +
                            '</div>';
            });

            // 空ならメッセージを入れる
            if (html == ''){

              // htmlを生成
              html = html + '<div class="error">' +
                            '  キャストするデバイスがありません。<br>' +
                            '  右上の︙メニューから、デバイスをスキャンしてください。<br>' +
                            '</div>';
            }

            // 一気に代入
            document.getElementById('chromecast-device-box').innerHTML = html;

          }
        });

      // キャスト終了
      } else if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){
        
      $('#menu-content').velocity($('#menu-content').is(':visible') ? 'slideUp' : 'slideDown', 150);
        $('#menu-content').removeClass('open');

        $.ajax({
          url: '/api/chromecast?cmd=stop',
          dataType: 'json',
          cache: false,
          success: function(data) {

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
        });
      }

    });

    // キャスト開始
    $('body').on('click','.chromecast-device',function(){
      var state = document.getElementById('state').value;
      
      toastr.info('キャストを開始しています…');

      // 動画を一旦止める
      dp.video.pause();
      // 端末はミュートにする
      dp.video.muted = true;
      // 音量を半分にする
      dp.video.volume = 0.5;

      // キャスト端末の名前
      var castName = $(this).find('.chromecast-name').text();

      // シークを通知
      $('#dplayer').addClass('dplayer-seeking');
      // ローディング表示
      $('#dplayer').addClass('dplayer-loading');
      // 動画表示を消す
      dp.video.style.transition = 'opacity 0.3s ease';
      dp.video.style.opacity = 0;
      
      // 「〇〇で再生しています」を出す
      if (!$('.dplayer-casting').length){
        $('.dplayer-danmaku').before('<div class="dplayer-casting">' + castName + 'で再生しています</div>');
      } else if ($('.dplayer-casting').text() !== castName + 'で再生しています'){
        $('.dplayer-casting').text(castName + 'で再生しています');
      }
      $('.dplayer-casting').css('opacity', 0.7);

      $('#nav-close').removeClass('open');
      $('#chromecast-box').removeClass('open');
      $('html').removeClass('open');

      $.ajax({
        url: '/api/chromecast?cmd=start&ip=' + $(this).attr('data-ip') + '&port=' + $(this).attr('data-port'),
        dataType: 'json',
        cache: false,
        success: function(data) {

          if (data['status'] == 'play'){

            $('#cast-toggle > .menu-link-href').text('キャストを終了');

            // 操作系は関数に投げる
            chromecast(state);

          } else {
            toastr.error('キャストの開始に失敗しました…');
          }

        }
      });
    });

    $('#cast-scan').click(function(){
      $('#menu-content').velocity($('#menu-content').is(':visible') ? 'slideUp' : 'slideDown', 150);
      $('#menu-content').removeClass('open');
      toastr.info('スキャンしています…');
      $.ajax({
        url: '/api/chromecast?cmd=scan',
        dataType: 'json',
        cache: false,
        success: function(data) {
          if (data['scan'] == true){
            toastr.success('スキャンに成功しました。');
          } else {
            toastr.error('スキャンに失敗しました…');
            toastr.error('ChromeCast が同じ Wi-Fi ネットワーク上にないか、Bonjour がインストールされていない可能性があります。');
            if (data['bonjour'] == false){
              toastr.error('TVRemotePlus (Apache) を管理者権限で起動させてから、もう一度試してみてください。');
            } else {
              toastr.error('もう一度試してみてください。');
            }
          }
        }
      });

    });

    // Chromecast の操作関連をまとめた関数
    function chromecast(state){

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
          url: '/api/chromecast?cmd=seek&arg=' + dp.video.currentTime,
          dataType: 'json',
          cache: false,
          success: function(data) {
            dp.video.muted = true;
          }
        });

      } else {
        dp.video.muted = true;
      }

      // 再生
      $('.dplayer-video-current').on('play', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

          $.ajax({
            url: '/api/chromecast?cmd=restart',
            dataType: 'json',
            cache: false,
            success: function(data) {
            }
          });
        
        }
      });

      // 一時停止
      $('.dplayer-video-current').on('pause', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

          // ファイル再生のみ
          if (state == 'File'){

            $.ajax({
              url: '/api/chromecast?cmd=seek&arg=' + dp.video.currentTime,
              dataType: 'json',
              cache: false,
              success: function(data) {
              }
            });

          } else {

            $.ajax({
              url: '/api/chromecast?cmd=pause',
              dataType: 'json',
              cache: false,
              success: function(data) {
                dp.pause();
              }
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
              url: '/api/chromecast?cmd=seek&arg=' + dp.video.currentTime,
              dataType: 'json',
              cache: false,
              success: function(data) {
                dp.pause();
              }
            });

          }
        });
      }

      // 音量
      $('.dplayer-video-current').on('volumechange', function(){

        if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

          $.ajax({
            url: '/api/chromecast?cmd=volume&arg=' + dp.video.volume,
            dataType: 'json',
            cache: false,
            success: function(data) {
            }
          });
          
          dp.video.muted = true; // 端末はミュートにする

        }
      });

    }

    // Chromecastをjsから起動

    window.__onGCastApiAvailable = function(isAvailable) {
      if (isAvailable) {
        initializeCastApi();
        display();
      }
    };

    // ボタンが押されたらメニューを引っ込める
    $('google-cast-launcher').click(function() {
      $('#menu-content').velocity('slideUp', 150);
      $('#menu-content').removeClass('open');
      $('#menu-close').removeClass('open');
    });

    // 何故か display: none; されがちなので強制で表示させる関数
    function display() {
      setInterval(function() {
        $('google-cast-launcher').css('display', 'block');
      }, 1000);
    }

    // 1. 初期化
    var remotePlayer;
    var remotePlayerController;
    function initializeCastApi() {
      cast.framework.CastContext.getInstance().setOptions({
        receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
        autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED,
      });

      launchApp();
    }

    // 2. 端末とChromecastが接続されたらのリスナー
    function launchApp() {
      remotePlayer = new cast.framework.RemotePlayer();
      remotePlayerController = new cast.framework.RemotePlayerController(remotePlayer);
      // キャスト起動時に発火
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
            dp.video.volume = 0.5;

            // キャスト端末の名前
            var castName = cast.framework.CastContext.getInstance().getCurrentSession().getSessionObj().receiver.friendlyName;
            
            // シークを通知
            $('#dplayer').addClass('dplayer-seeking');
            // ローディング表示
            $('#dplayer').addClass('dplayer-loading');
            // 動画表示を消す
            dp.video.style.transition = 'opacity 0.3s ease';
            dp.video.style.opacity = 0;

            // 「〇〇で再生しています」を出す
            if (!$('.dplayer-casting').length){
              $('.dplayer-danmaku').before('<div class="dplayer-casting">' + castName + 'で再生しています</div>');
            } else if ($('.dplayer-casting').text() !== castName + 'で再生しています'){
              $('.dplayer-casting').text(castName + 'で再生しています');
            }
            $('.dplayer-casting').css('opacity', 0.8);

            // メディアを読み込み
            loadMedia();

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

    // 3. メディアをロードする
    function loadMedia() {
      var castSession = cast.framework.CastContext.getInstance().getCurrentSession();
      var mediaInfo = new chrome.cast.media.MediaInfo(streamurl, 'application/vnd.apple.mpegurl');

      mediaInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
      mediaInfo.metadata.metadataType = chrome.cast.media.MetadataType.GENERIC;

      var request = new chrome.cast.media.LoadRequest(mediaInfo);

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

  });
