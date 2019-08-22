  $(function(){

    // 個人設定を反映
    if (!settings['twitter_show']){
      $('#tweet-box').hide();
    }
    if (!settings['comment_show']){
      $('#sidebar').hide();
      $('#content').width('100%');
    }

    // focusを常にオンにする
    dp.focus = true;
    $(document).click(function(){
      dp.focus = true;
    });

    // ウインドウサイズ
    $(window).on('load resize', function(){

      // console.log('resize');
      // 1024px以上
      if ($(window).width() > 1024){

        // ウィンドウを読み込んだ時・リサイズされた時に発動
        // 何故か上手くいかないので8回繰り返す
        // 正直どうなってるのか自分でもわからない
        var result = 0; // 初期化

        while (true){
          var WindowHeight = $(window).height() - $('#top').height();
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
      
      // スマホ
      } else {
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
      }

    });

    // 再生開始ボックス
    $('body').on('click','.broadcast-wrap',function(){
      $('#broadcast-stream-title').html($(this).find('.broadcast-channel').html() + ' ' + $(this).find('.broadcast-name').html());
      $('#broadcast-stream-info').html($(this).find('.broadcast-title-id').html());
      $('#broadcast-stream-channel').val($(this).find('.broadcast-channel-id').text());
      $('#nav-close').toggleClass('open');
      $('#broadcast-stream-box').toggleClass('open');
      $('html').toggleClass('open');
      // ワンクリックでストリーム開始する場合
      if (settings['onclick_stream']){
        $('#broadcast-stream-box').hide();
        $('.bluebutton').click();
      }
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

      // キャスト開始
      if ($('#cast-toggle > .menu-link-href').text() == 'キャストを開始'){

        $('#menu-content').animate({height: 'toggle'}, 150);
        $('#menu-content').removeClass('open');
        $('#nav-close').toggleClass('open');
        $('#chromecast-box').toggleClass('open');
        $('html').removeClass('open');

        $.ajax({
          url: '/api/chromecast.php',
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

            // 一気に代入
            document.getElementById('chromecast-device-box').innerHTML = html;

          }
        });

      // キャスト終了
      } else if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){
        
        $('#menu-content').animate({height: 'toggle'}, 150);
        $('#menu-content').removeClass('open');

        $.ajax({
          url: '/api/chromecast.php?cmd=stop',
          dataType: 'json',
          cache: false,
          success: function(data) {
            dp.video.muted = false;
            $('#cast-toggle > .menu-link-href').text('キャストを開始');
            toastr.success('キャストを終了しました。');
          }
        });
      }

    });

    $('body').on('click','.chromecast-device',function(){
      dp.pause();
      $('#nav-close').removeClass('open');
      $('#chromecast-box').removeClass('open');
      $('html').removeClass('open');
      toastr.info('キャストを開始しています…');

      $.ajax({
        url: '/api/chromecast.php?cmd=start&ip=' + $(this).attr('data-ip') + '&port=' + $(this).attr('data-port'),
        dataType: 'json',
        cache: false,
        success: function(data) {

          if (data['status'] == 'play'){

            $('#cast-toggle > .menu-link-href').text('キャストを終了');
            toastr.success('キャストを開始しました。');

            // 再生系処理 
            $.ajax({
              url: '/api/chromecast.php?cmd=seek&arg=' + dp.video.currentTime,
              dataType: 'json',
              cache: false,
              success: function(data) {
              }
            });
            dp.video.muted = true;
            dp.play();

            if ($('#cast-toggle > .menu-link-href').text() == 'キャストを終了'){

              // 再生・一時停止・シーク
              $('.dplayer-video-current').on('play', function(){

                $.ajax({
                  url: '/api/chromecast.php?cmd=restart',
                  dataType: 'json',
                  cache: false,
                  success: function(data) {
                  }
                });
              });

              $('.dplayer-video-current').on('pause', function(){

                  $.ajax({
                    url: '/api/chromecast.php?cmd=pause',
                    dataType: 'json',
                    cache: false,
                    success: function(data) {
                    }
                  });
              });

              $('.dplayer-video-current').on('seeked', function(){

                $.ajax({
                  url: '/api/chromecast.php?cmd=seek&arg=' + dp.video.currentTime,
                  dataType: 'json',
                  cache: false,
                  success: function(data) {
                  }
                });
            });

            }

          } else {
            toastr.error('キャストの開始に失敗しました…');
          }

        }
      });
    });

    $('#cast-scan').click(function(){
      $('#menu-content').animate({height: 'toggle'}, 150);
      $('#menu-content').removeClass('open');
      toastr.info('スキャンしています…');
      $.ajax({
        url: '/api/chromecast.php?cmd=scan',
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

  });
