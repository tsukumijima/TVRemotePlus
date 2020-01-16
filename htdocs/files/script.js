  $(function(){

    // 生放送・ファイル再生共通

    // ***** 一般 *****

    // clock() を毎秒実行する
    setInterval(clock, 1000);

    // 最初に実行
    if (Cookies.get('twitter_session')){
      $("#tweet-status").html('<a id="tweet-logout" href="javascript:void(0)">ログアウト</a>');
    } else {
      $("#tweet-status").html('<a id="tweet-login" href="/tweet/auth">ログイン</a>');
    }
    
    // Twitterアカウント情報を読み込み
    twitter = {account_name:'ログインしていません', account_id:'', account_icon:'/files/account_default.jpg'};
    if (Cookies.get('twitter') != undefined){
      twitter = JSON.parse(Cookies.get('twitter'));
      $('#tweet-account-icon').attr('src', twitter['account_icon']);
      $('#tweet-account-name').text(twitter['account_name']);
      $('#tweet-account-name').attr('href', 'https://twitter.com/' + twitter['account_id']);
      $('#tweet-account-id').text('@' + twitter['account_id']);
    }

    // ***** 視聴数カウント・ストリーム状態把握 *****

    setInterval((function status(){
      $.ajax({
        url: '/api/status/' + stream,
        dataType: 'json',
        cache: false,
        success: function(data) {

          // 視聴数を表示
          document.getElementById('watching').textContent = data['watching'] + '人が視聴中';

          var status = document.getElementById('status').textContent;

          if (data['status'] == 'failed' && status != 'failed'){
            toastr.error('ストリームの開始に失敗しました…');
            $.ajax({
              url: '/settings/',
              type: 'post',
              data: {
                'state': 'Offline',
                'stream': stream
              },
              cache: false,
              success: function(data) {
                toastr.info('ストリームを終了します。');
              }
            });
          }

          if (data['status'] == 'restart' && status != 'restart'){
            toastr.warning('ストリームが途中で中断しました…');
            $.ajax({
              url: '/settings/',
              type: 'post',
              data: {
                'state': 'ONAir',
                'stream': stream,
                'restart': 'true'
              },
              cache: false,
              success: function(data) {
                var paused = dp.video.paused;
                if (data['streamtype'] == 'progressive'){
                  dp.video.src = '/api/stream?_=' + time();
                  dp.initVideo(dp.video, 'normal');
                } else {
                  dp.video.src = '/stream/stream' + stream + '.m3u8';
                  dp.initVideo(dp.video, 'hls');
                }
                if (!paused){
                  dp.video.play();
                } else {
                  dp.video.pause();
                }
                toastr.info('ストリームを再起動しています…');
              }
            });
          }
          
          // 状態を隠しHTMLに書き出して変化してたらリロードする
          if ((data['status'] != status) && status != ''){

            // stateが同じの場合のみ読み込みし直し
            if ((document.getElementById('state').value == data['state']) &&
              (data['state'] == 'ONAir' || (data['state'] == 'File' && data['status'] == 'onair'))){

              if (data['status'] == 'failed' || data['status'] != 'restart'){

                // ストリームを読み込みし直す
                var paused = dp.video.paused;
                if (data['streamtype'] == 'progressive'){
                  dp.video.src = '/api/stream?_=' + time();
                  dp.initVideo(dp.video, 'normal');
                } else {
                  dp.video.src = '/stream/stream' + stream + '.m3u8';
                  dp.initVideo(dp.video, 'hls');
                }
                if (!paused){
                  dp.video.play();
                } else {
                  dp.video.pause();
                }

              }

            // それ以外は諸々問題があるので一旦リロード
            } else {
              if (data['status'] == 'failed'){
                setTimeout('location.reload(true)', 3000);
              } else {
                location.reload(true);
              }
            }
          }

          document.getElementById('status').textContent = data['status'];
          console.log('status: ' + data['status']);
        }

      });
      return status;
    }()),1000);


    // ***** 番組表・ストリーム一覧表示 *****

    setInterval((function status(){
      $.ajax({
        url: '/api/epginfo/' + stream,
        dataType: 'json',
        cache: false,
        success: function(data) {

          // 結果をHTMLにぶち込む

          // 高さフラグ
          if (document.getElementsByClassName('broadcast-title-ch1')[0]){
            if (document.getElementsByClassName('broadcast-title-ch1')[0].textContent == ''){
              var flg = true;
            } else {
              var flg = false;
            }
          } else {
            var flg = false;
          }
          
          if (data['stream'][stream]['state'] == 'ONAir'){

            // 変化がある場合のみ書き換え
            if (document.getElementById('epg-starttime').innerHTML != data['stream'][stream]['starttime'] ||
                document.getElementById('epg-title').innerHTML != data['stream'][stream]['program_name'] ||
                document.getElementById('epg-channel').innerHTML != data['stream'][stream]['channel']){

              // 現在の番組
              document.getElementById('epg-starttime').textContent = data['stream'][stream]['starttime'];
              document.getElementById('epg-to').textContent = data['stream'][stream]['to'];
              document.getElementById('epg-endtime').textContent = data['stream'][stream]['endtime'];
              
              if (data['stream'][stream]['ch'] < 55){
                document.getElementById('epg-channel').textContent = 'Ch: ' + zeroPadding(data['stream'][stream]['ch'], 2) + ' ' + data['stream'][stream]['channel'];
              } else {
                document.getElementById('epg-channel').textContent = 'Ch: ' + zeroPadding(data['stream'][stream]['ch'], 3) + ' ' + data['stream'][stream]['channel'];
              }
              document.getElementById('epg-title').innerHTML = data['stream'][stream]['program_name'];
              document.getElementById('epg-info').innerHTML = data['stream'][stream]['program_info'];
          
              // 次の番組
              document.getElementById('epg-next-starttime').textContent = data['stream'][stream]['next_starttime'];
              document.getElementById('epg-next-to').textContent = data['stream'][stream]['to'];
              document.getElementById('epg-next-endtime').textContent = data['stream'][stream]['next_endtime'];
              document.getElementById('epg-next-title').innerHTML = data['stream'][stream]['next_program_name'];

              // ON Air
              document.getElementById('state').textContent = '● ON Air';
              document.getElementById('state').style.color = '#007cff';
            }

          } else if (data['stream'][stream]['state'] == 'Offline') {

            // Offline
            document.getElementById('state').textContent = '● Offline';
            document.getElementById('state').style.color = 'gray';
          }

          // stateを記録しておく
          document.getElementById('state').value = data['stream'][stream]['state'];

          // progressbarの割合を計算して代入
          var percent = ((Math.floor(Date.now() / 1000) - data['stream'][stream]['timestamp']) / data['stream'][stream]['duration']) * 100;
          document.getElementById('progress').style.width = percent + '%';

          // チャンネルごとに実行
          for (key in data['onair']){

            // 変化がある場合のみ書き換え
            // 特に内容変わってもいないのにDOM再構築するの無駄じゃんやめろ
            if (document.getElementsByClassName('broadcast-start-ch' + key)[0].innerHTML != data['onair'][key]['starttime'] ||
                document.getElementsByClassName('broadcast-title-ch' + key)[0].innerHTML != data['onair'][key]['program_name']){

              // 書き換え用html
              var html = '  <div class="broadcast-channel-box">' +
                        '    <div class="broadcast-channel broadcast-channel-ch' + key + '">' + $('.broadcast-channel-ch' + key).html() + '</div>' +
                        '    <div class="broadcast-name-box">' +
                        '      <div class="broadcast-name broadcast-name-ch' + key + '">' + $('.broadcast-name-ch' + key).html() + '</div>' +
                        '      <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi-ch' + key + '"></span></div>' +
                        '    </div>' +
                        '  </div>' +
                        '  <div class="broadcast-title">' +
                        '    <span class="broadcast-start-ch' + key + '">' + data['onair'][key]['starttime'] + '</span>' +
                        '    <span class="broadcast-to-ch' + key + '">' + data['onair'][key]['to'] + '</span>' +
                        '    <span class="broadcast-end-ch' + key + '">' + data['onair'][key]['endtime'] + '</span>' +
                        '    <span class="broadcast-title-id broadcast-title-ch' + key + '">' + data['onair'][key]['program_name'] + '</span>' +
                        '  </div>' +
                        '  <div class="broadcast-next">' +
                        '    <span class="broadcast-next-start-ch' + key + '">' + data['onair'][key]['next_starttime'] + '</span>' +
                        '    <span class="broadcast-next-to-ch' + key + '">' + data['onair'][key]['to'] + '</span>' +
                        '    <span class="broadcast-next-end-ch' + key + '">' + data['onair'][key]['next_endtime'] + '</span>' +
                        '    <span class="broadcast-next-title-ch' + key + '">' + data['onair'][key]['next_program_name'] + '</span>' +
                        '  </div>';

              // 番組情報を書き換え
              document.getElementsByClassName('broadcast-ch' + key)[0].innerHTML = html;
            }

            // 実況勢いが変化していれば書き換え
            var ikioi = document.getElementsByClassName('broadcast-ikioi-ch' + key)[0];
            if (ikioi.textContent.toString() !== data['onair'][key]['ikioi'].toString()){
              ikioi.textContent = data['onair'][key]['ikioi'];
            }

            // プログレスバー
            var percent = ((Math.floor(Date.now() / 1000) - data['onair'][key]['timestamp']) / data['onair'][key]['duration']) * 100;
            document.getElementsByClassName('progress-ch' + key)[0].style.width = percent + '%';

          }

          // ストリーム番号ごとに実行
          for (key in data['stream']){

            var elem = document.getElementsByClassName('stream-view-' + key)[0];
            
            switch (data['stream'][key]['state']){
              
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
                (elem === undefined || elem.getElementsByClassName('stream-title')[0].innerHTML != data['stream'][key]['program_name'])){

              // 書き換え用html
              var streamview = `<div class="stream-box">
                                  <div class="stream-number-title">Stream</div><div class="stream-number">` + key + `</div>
                                  <div class="stream-stop ` + (data['stream'][key]['state'] == 'Offline' ? 'disabled' : '') + `">
                                    <i class="stream-stop-icon far fa-stop-circle"></i>
                                  </div>
                                  <div class="stream-state ` + color + `">` + state + `</div>
                                  <div class="stream-info">
                                    <div class="stream-title">` + data['stream'][key]['program_name'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'') + `</div>
                                    <div class="stream-channel">` + data['stream'][key]['channel'] + `</div>
                                    <div class="stream-description">` + data['stream'][key]['program_info'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'') + `</div>
                                  </div>
                                </div>`;

              // 番組情報を書き換え
              if (elem === undefined){

                // 親要素を追加
                streamview = `<button class="stream-view stream-view-` + key + `" type="button" data-num="` + key + `" data-url="/` + key + `/">` + streamview + `</button>`;

                // 新規で要素を作る
                document.getElementById('stream-view-box').insertAdjacentHTML('beforeend', streamview);

              } else {

                // 既存のものを書き換え
                elem.innerHTML = streamview;
              }

            // オフラインかつ要素が存在する場合
            } else if (elem !== undefined && data['stream'][key]['state'] == 'Offline' && key != '1'){

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

        }
      });
      return status;
    }()), 10000);


    // ***** ストリーム終了・遷移 *****

    $('body').on('click','.stream-view',function(event){

      // ストリーム終了ボタン
      if ($(event.target).hasClass('stream-stop-icon') && !$(event.target).parent().hasClass('disabled')){

        var streamview = this;
        var streamnum = $(streamview).attr('data-num');
        
        toastr.info('ストリーム ' + streamnum + ' を終了します。');

        $.ajax({
          url: '/settings/',
          type: 'post',
          data: {state: 'Offline', stream: streamnum},
          cache: false,
          success: function(data) {
            
            toastr.success('ストリーム ' + streamnum + ' を終了しました。');

            // Offlineにする
            $(streamview).find('.stream-stop').addClass('disabled');
            $(streamview).find('.stream-state').removeClass('blue');
            $(streamview).find('.stream-state').removeClass('green');
            $(streamview).find('.stream-state').html('● Offline');
            $(streamview).find('.stream-title').html('配信休止中…');
            $(streamview).find('.stream-channel').empty();
            $(streamview).find('.stream-description').empty();

            // 自分のストリームでない&ストリーム1でないなら要素を削除する
            if (stream != streamnum && streamnum != '1'){
              $(streamview).slideUp(400).animate(
                { opacity: 0 },
                { queue: false, duration: 400, easing: 'swing' }
              ).queue(function() {
                $(streamview).remove();
              });
            }

          }, error: function(){
            toastr.error('ストリーム ' + streamnum + ' の終了に失敗しました…');
          }
        });

      } else if ($(event.target).parent().hasClass('disabled')){

        event.preventDefault();

      } else {

        // 他のストリームへ遷移
        location.href = $(this).attr('data-url');

      }

    });


    // ***** ツイート関連 *****

    // ツイートの文字数をカウント
    var count;
    var limit = 140;
    $('#tweet').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });
    $('#tweet-hashtag').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });

    // クリップボードの画像を格納する
    var file = null;
    $('#tweet').on('paste', function(event){
      // event からクリップボードのアイテムを取り出す
      var items = event.originalEvent.clipboardData.items; // ここがミソ
      for (var i = 0 ; i < items.length ; i++) {
        var item = items[i];
        if (item.type.indexOf("image") != -1) {
          // 画像だけ代入
          $('#tweet-status').html('取得中…');
          $('#tweet-submit').prop('disabled', true).addClass('disabled');
          file = item.getAsFile();
          // limit内なら
          if (limit >= 0){
            $('#tweet-submit').prop('disabled', false).removeClass('disabled');
          }
          $('#tweet-status').html('クリップボードの画像を選択しました。');
        }
      }
    });
    
    // アカウント情報ボックスを表示する
    var clickEventType = ((window.ontouchstart!==null) ? 'mouseenter mouseleave' : 'touchstart');
    $('#tweet-title').on(clickEventType, function(event) {
      if ($('#tweet-account-box').css('visibility') === 'hidden' && (event.type === 'mouseenter' || event.type === 'touchstart')){
        $('#tweet-account-box').css('visibility', 'visible');
        $('#tweet-account-box').css('opacity', 1);
        console.log('visible')
      } else {
        $('#tweet-account-box').css('visibility', 'hidden');
        $('#tweet-account-box').css('opacity', 0);
        console.log('hidden')
      }
    });

    $('#tweet-account-box').on(clickEventType, function(event) {
      if (event.type === 'mouseenter'){
        $('#tweet-account-box').css('visibility', 'visible');
        $('#tweet-account-box').css('opacity', 1);
      } else {
        $('#tweet-account-box').css('visibility', 'hidden');
        $('#tweet-account-box').css('opacity', 0);
      }
    });

    // スマホの場合にTwitterだけ下にフロート表示
    $('#tweet').focusin(function(event) {
      if ($(window).width() <= 500){
        $('#top').hide();
        $('#tweet-box').addClass('open');
        $('#tweet-close').addClass('open');
        $('html').addClass('open');
      }
    });

    $('#tweet-hashtag').focusin(function(event) {
      if ($(window).width() <= 500){
        $('#top').hide();
        $('#tweet-box').addClass('open');
        $('#tweet-close').addClass('open');
        $('html').addClass('open');
      }
    });

    $('#tweet-close').click(function(event) {
      if ($(window).width() <= 500){
        $('#top').show();
        $('#tweet-box').removeClass('open');
        $('#tweet-close').removeClass('open');
        $('html').removeClass('open');
      }
    });

    // キャプチャした画像をblobにして格納する
    $('#tweet-picture').click(function(event){
      capVideo(event);
    });

    // キャプチャした画像をコメント付きでblobにして格納する
    $('#tweet-picture-comment').click(function(event){
      capVideoComment(event);
    });

    // フォームをハッシュタグ以外リセット
    $('#tweet-reset').click(function(event){
      tweet_reset(event);
    });

    // Twitterからログアウト
    $('#tweet-status').on('click', '#tweet-logout', function(event){
      $.ajax({
        url: "/tweet/logout",
        type: "post",
        processData: false,
        contentType: false
      })
      .done(function(data) {
        $("#tweet-status").html(data);
        $('#tweet-account-icon').attr('src', '/files/account_default.jpg');
        $('#tweet-account-name').text('ログインしていません');
        $('#tweet-account-name').removeAttr('href');
        $('#tweet-account-id').text('Not Login');
      })
      .fail(function(data){
        $("#tweet-status").html('<span class="tweet-failed">ログアウト中にエラーが発生しました…</span>');
      });
    });

    // tabキーが押されたとき：フォーカス
    // Alt + 1キーが押された時：キャプチャ
    // Alt + 2キーが押された時：コメント付きキャプチャ
    // Alt + 3キーが押された時：フォームリセット
    $(document).keydown(function(event){

      // tabキー
      if (event.which == 9){
        // フォーカス
        $('#tweet-hashtag').focus();
      }

      // Alt(or option)キー
      if (event.altKey){
        event.preventDefault(); // Mac用
        switch (event.which){

          case 49:
            // Alt + 1
            capVideo(event);
          break;

          case 50:
            // Alt + 2
            capVideoComment(event);
          break;

          case 51:
            // Alt + 3
            tweet_reset(event);
          break;
        }
      }
    });

    // ツイートボタンが押された時にツイートを送信する
    $('#tweet-submit').click(function(event){
      tweet_send(event);
    });

    // Ctrl + Enterキーが押された時にツイートを送信する
    $('#tweet-form').keydown(function(event){
      // クロスブラウザ対応用
      var event = event || window.event;
      // limit内なら
      if ((limit < 140 || file != null) && limit >= 0){
        // Ctrl(or Command) + Enterキーなら送信
        if (event.ctrlKey || event.metaKey){
          if (event.which == 13){
            tweet_send(event);
          }
        }
      }
    });


    // ***** キャプチャ関連の関数 *****

    // キャプチャした画像をblobにして格納する関数
    function capVideo(event){

      $('#tweet-status').html('キャプチャ中…');
      $('#tweet-submit').prop('disabled', true).addClass('disabled');
      // 動画のキャンバス
      var canvas = document.createElement('canvas');
      var video = document.getElementsByClassName('dplayer-video-current')[0];
      var subtitles = video.textTracks[1].activeCues;

      // 字幕オンなら
      if (video.textTracks[1].mode == 'showing' && video.textTracks[1].cues.length){

        var subtitle_html = '<div class="video-subtitle-box">\n';
        for(var i = (subtitles.length - 1); i >= 0; i--){
          
          // 下からの高さ
          var bottom = 18 + i * 8.5;

          // html用に置換する
          subtitle_html = subtitle_html + subtitles[i].text.replace(/<v.b24js rgb(.*?)>/,
            '<span class="video-subtitle-wrap" style="bottom: ' + bottom + '%; color: #$1;"><span class="video-subtitle">')
            .replace(/<v.b24js rgb(.*?)>/g, '').replace(/<\/v>/g, '')
            .replace(/color: #ffff;/, 'color: #00ffff;').replace(/color: #ff00;/, 'color: #00ff00;') + '</span></span>\n';
        }

        html = subtitle_html + '</div>\n';

        // 字幕をHTMLにゴリ押しで変換した後にレンダリング
        nicoVideoToCanvas({video, html}).then(({canvas}) => {
          canvas.toBlob(function(blob){
            file = blob;
            console.log('Render Blob: ' + URL.createObjectURL(blob));
            // limit内なら
            if (limit > 0){
              $('#tweet-submit').prop('disabled', false).removeClass('disabled');
            }
            $('#tweet-status').html('キャプチャした画像を選択しました。');
          }, 'image/jpeg', 1);
        });

      } else {

        // 普通にキャプチャする
        videoToCanvas(video).then(({canvas}) => {
          canvas.toBlob(function(blob){
            file = blob;
            console.log('Render Blob: ' + URL.createObjectURL(blob));
            // limit内なら
            if (limit > 0){
              $('#tweet-submit').prop('disabled', false).removeClass('disabled');
            }
            $('#tweet-status').html('キャプチャした画像を選択しました。');
          }, 'image/jpeg', 1);
        });

      }
    }

    // キャプチャした画像をコメント付きでblobにして格納する関数
    function capVideoComment(event){

      $('#tweet-status').html('コメント付きでキャプチャ中…');
      $('#tweet-submit').prop('disabled', true).addClass('disabled');

      // 要素を取得
      var video = document.getElementsByClassName('dplayer-video-current')[0];
      var html = document.querySelector('.dplayer-danmaku').outerHTML;
      var danmaku = document.getElementsByClassName('dplayer-danmaku-move');
      var subtitles = video.textTracks[1].activeCues;

      // このままだとSVG化に失敗するため修正する
      for (var i = 0; i < danmaku.length; i++){ // コメントの数だけ置換
        // コメント位置を計算
        var position = danmaku[i].getBoundingClientRect().left - video.getBoundingClientRect().left;
        html = html.replace(/transform: translateX\(.*?\)\;/, 'left: ' + position + 'px;');
      }

      // 字幕オンなら
      if (video.textTracks[1].mode == 'showing' && video.textTracks[1].cues.length){

        var subtitle_html = '<div class="video-subtitle-box">\n';
        for(var i = (subtitles.length - 1); i >= 0; i--){
          
          // 下からの高さ
          var bottom = 18 + i * 8.5;

          // html用に置換する
          subtitle_html = subtitle_html + subtitles[i].text.replace(/<v.b24js rgb(.*?)>/,
            '<span class="video-subtitle-wrap" style="bottom: ' + bottom + '%; color: #$1;"><span class="video-subtitle">')
            .replace(/<v.b24js rgb(.*?)>/g, '').replace(/<\/v>/g, '')
            .replace(/color: #ffff;/, 'color: #00ffff;').replace(/color: #ff00;/, 'color: #00ff00;') + '</span></span>\n';
        }

        html = subtitle_html + '</div>\n' + html;
      }

      nicoVideoToCanvas({video, html}).then(({canvas}) => {
        canvas.toBlob(function(blob){
          file = blob;
          console.log('Render Blob: ' + URL.createObjectURL(blob));
          // limit内なら
          if (limit > 0){
            $('#tweet-submit').prop('disabled', false).removeClass('disabled');
          }
          $('#tweet-status').html('キャプチャした画像を選択しました。');
        }, "image/jpeg", 1);
      });
    }

    // Zenzawatchのコードより一部改変した上で使わせて頂いています
    // 参考
    // https://developer.mozilla.org/ja/docs/Web/HTML/Canvas/Drawing_DOM_objects_into_a_canvas
    // ChromeだとtoBlobした際に汚染されるのでDataURIに変換する
    // https://qiita.com/kjunichi/items/f5993d34838e1623daf5
    
    const htmlToSvg = function(html, width = 640, height = 360) {
      scale = 1;
      var tablet = document.body.clientWidth <= 768;
      var mobile = document.body.clientWidth <= 500;
      var subtitle_fontsize = tablet ? (mobile ? 55 : 100) : 125;
      const data =
        (`<svg xmlns='http://www.w3.org/2000/svg' width='${width*scale}' height='${height*scale}'>
            <foreignObject width='100%' height='100%'>
              <div xmlns="http://www.w3.org/1999/xhtml">
                <style>
                .video-subtitle-box {
                  position: absolute;
                  left: 0;
                  right: 0;
                  top: 0;
                  bottom: 0;
                  font-size: 22px;
                  font-family: メイリオ, "Hiragino Kaku Gothic ProN", "ヒラギノ角ゴ ProN W3", sans-serif;
                  color: #ffffff;
                }
                .video-subtitle-wrap {
                  display: inline-block;
                  position: absolute;
                  width: 100%;
                  text-align: center;
                }
                .video-subtitle {
                  display: inline-block;
                  padding: 2px 2px 0px 2px;
                  font-size: ${subtitle_fontsize}%;
                  text-align: center;
                  background: rgba(0,0,0,.5);
                }
                .dplayer-danmaku {
                  position: absolute;
                  left: 0;
                  right: 0;
                  top: 0;
                  bottom: 0;
                  font-size: 29px;
                  font-family: メイリオ, "Hiragino Kaku Gothic ProN", "ヒラギノ角ゴ ProN W3", sans-serif;
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
      var canvas = document.createElement('canvas');
      var video = document.getElementsByClassName('dplayer-video-current')[0];
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      // 描画
      return new Promise((resolve, reject) => {
        var draw = function(){
          try {
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
          } catch (error){
            // エラー補足（Android版Firefoxのバグ対策のはずだった）
            console.log('catch:' + error.name)
            if (error.name == 'NS_ERROR_NOT_AVAILABLE'){
              // return setTimeout(draw, 100);
              $('#tweet-status').html('<span class="error">キャプチャに失敗しました…（ Android 版 Firefox コアの技術的問題によるものです）</span>');
              throw error;
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
      img.width  = width;
      img.height = height;
      const canvas = document.createElement('canvas');

      const context = canvas.getContext('2d');
      canvas.width  = width;
      canvas.height = height;

      return new Promise((resolve, reject) => {
        img.onload = () => {
          context.drawImage(
            img,
            (width  - imageW) / 2,
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
        scale  = Math.floor(minHeight / height);
        width  *= scale;
        height *= scale;
      }
  
      const canvas = document.createElement('canvas');
      const ct = canvas.getContext('2d');
  
      canvas.width = width;
      canvas.height = height;
  
      return videoToCanvas(video).then(({canvas, img}) => {
  
        //canvas.style.border = '2px solid red'; document.body.appendChild(canvas);
        ct.fillStyle = 'rgb(0, 0, 0)';
        ct.fillRect(0, 0, width, height);
  
        ct.drawImage(
          canvas,
          (width  - video.videoWidth  * scale) / 2,
          (height - video.videoHeight * scale) / 2,
          video.videoWidth  * scale,
          video.videoHeight * scale
        );
  
        return htmlToCanvas(video, html, width, height);}).then(({canvas, img}) => {
  
        //canvas.style.border = '2px solid green'; document.body.appendChild(canvas);
  
        ct.drawImage(canvas, 0, 0, width, height);
  
        return Promise.resolve({canvas, img});
      }).then(() => {
        return Promise.resolve({canvas});
      });
    };

    // ここまでZenzaWatchより拝借


    // ***** ツイート関連の関数 *****

    // ツイートの文字数をカウントする関数
    function tweet_count(event){

      // 現在のカウント数
      count = Array.from($('#tweet').val()).length + Array.from($('#tweet-hashtag').val()).length;
      limit = 140 - count;

      if (limit <= 140) {
        $('#tweet-num').text(limit);
        $('#tweet-num').removeClass('over');
        $('#tweet-num').removeClass('warn');
        // 送信中 or キャプチャ中でないなら
        if ($("#tweet-status").text() != 'ツイートを送信中…' && $("#tweet-status").text() != 'コメント付きでキャプチャ中…' && $("#tweet-status").text() != 'キャプチャ中…'){
          $('#tweet-submit').prop('disabled', false).removeClass('disabled');
        }
        if (limit == 140) {
          $('#tweet-num').text(limit);
          if (file == null){ // キャプチャされてないなら
            $('#tweet-submit').prop('disabled', true).addClass('disabled');
          }
        }
        if (limit <= 20) {
          $('#tweet-num').text(limit);
          $('#tweet-num').addClass('warn');
        }
        if (limit == 0) {
          $('#tweet-num').text(limit);
          $('#tweet-num').addClass('over');
        }
        if (limit < 0) {
          $('#tweet-num').text(limit);
          $('#tweet-num').addClass('over');
          $('#tweet-submit').prop('disabled', true).addClass('disabled');
        }
      }
    }

    // ツイートを送信する関数
    function tweet_send(event) {

      event.preventDefault(); // 通常のイベントをキャンセル
      $('#tweet-submit').prop('disabled', true).addClass('disabled');
      $("#tweet-status").html('ツイートを送信中…');

      // フォームデータ
      var formData = new FormData($('#tweet-form').get(0));
      formData.append('picture', file);

      // 通常表示
      $('#content-box').show();
      $('#footer').show();
      $('#top').show();
      $('#tweet-box').removeClass('open');
      $('#tweet-close').removeClass('open');
      $('html').removeClass('open');

      // 送信
      $.ajax({
        url:  '/tweet/tweet',
        type: 'post',
        data: formData,
        processData: false,
        contentType: false
      })
      .done(function(data) {
        file = null;
        $('#tweet').val(null);
        $('#tweet-file').val(null);
        $("#tweet-status").html(data);
        $('#tweet-num').text(140);
      })
      .fail(function(data){
        $("#tweet-status").html('<span class="tweet-failed">送信中にエラーが発生しました…</span>');
      });
    }

    // フォームをハッシュタグ以外リセットする関数
    function tweet_reset(event){
      file = null;
      limit = 140;
      $('#tweet-num').text(limit);
      $('#tweet-num').removeClass('over');
      $('#tweet-num').removeClass('warn');
      $('#tweet-submit').prop('disabled', true).addClass('disabled');
      $('#tweet').val(null);
      $('#tweet-file').val(null);
      $('#content-box').show();
      $('#footer').show();
      if (Cookies.get('twitter_session')){
        $("#tweet-status").html('<a id="tweet-logout" href="javascript:void(0)">ログアウト</a>');
      } else {
        $("#tweet-status").html('<a id="tweet-login" href="/tweet/auth">ログイン</a>');
      }
    }
    

    // ***** Utils *****

    // 0埋めする関数
    function zeroPadding(num, length){
      return ('0000000000' + num).slice(-length);
    }

  	// 時計用
	  function clock(){

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
    function time(){
      var date = new Date();
      return Math.floor( date.getTime() / 1000 );
    }

  });

