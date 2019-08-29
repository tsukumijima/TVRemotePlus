  $(function(){

    // 生放送・ファイル再生共通

    // dp.danmaku.opacity(0.9); //透明度を設定
    // 最初に実行
    var cookie = getCookieArray();
    if (cookie['twitter_session']){
        $("#tweet-status").html('<a id="tweet-logout" href="javascript:void(0)">ログアウト</a>');
      } else {
        $("#tweet-status").html('<a id="tweet-login" href="tweet/auth.php">ログイン</a>');
    }

    // リサイズ時の実行
    $(window).on('load resize', function(){

      // スマホ・タブレットならplaceholder書き換え
      if ($(window).width() <= 768){
        $("#tweet").attr('placeholder','ツイート');
      }

      // スマホならスクロールに応じて動画を固定できるようdivを移動させる
      // フルスクリーンで無いことを確認してから
      if ($(window).width() <= 500 && $(window).height() >= 350 && (document.fullscreenElement === null || document.webkitFullscreenElement === null)){
        $('#content-wrap').before($('#dplayer'));
      } else if ($(window).width() > 500 && $(window).height() >= 350 && (document.fullscreenElement === null || document.webkitFullscreenElement === null)){
        $('#dplayer-script').before($('#dplayer'));
      }
      
    });
    
	  // clock()を1000ミリ秒ごと(毎秒)に実行する
    setInterval(clock, 1000);

    // 視聴数カウント & ストリーム状態把握
    setInterval((function status(){
      $.ajax({
        url: "/api/watchnow.php",
        dataType: "json",
        cache: false,
        success: function(data) {

          // 視聴数を表示
          document.getElementById('watchnow').textContent = data["watchnow"] + '人が視聴中';

          var status = document.getElementById('status').textContent;

          if (data['status'] == 'failed' && status != 'failed'){
            toastr.error('ストリームの開始に失敗しました…');
            $.ajax({
              url: '/setting/',
              type: 'post',
              data: {
                'state': 'Offline'
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
              url: '/setting/',
              type: 'post',
              data: {
                'state': 'ONAir',
                'restart': 'true'
              },
              cache: false,
              success: function(data) {
                var paused = dp.video.paused;
                dp.video.src = 'stream/stream.m3u8';
                dp.initVideo(dp.video, 'hls');
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
                dp.video.src = 'stream/stream.m3u8';
                dp.initVideo(dp.video, 'hls');
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


    // 番組情報を取得
    setInterval((function status(){
      $.ajax({
        url: "/api/epgguide.php",
        dataType: "json",
        cache: false,
        success: function(data) {

          // 結果をHTMLにぶち込む

          // 高さフラグ
          if ($('.broadcast-title-ch1').text() == ''){
            var flg = true;
          } else {
            var flg = false;
          }
          
          if (data['info']['state'] == 'ONAir'){

            // 変化がある場合のみ書き換え
            if (document.getElementById('starttime').innerHTML != data['play']['starttime'] ||
                document.getElementById('program_name').innerHTML != data['play']['program_name'] ||
                document.getElementById('channel').innerHTML != data['play']['channel']){

              // 現在の番組
              $("#starttime").text(data['play']['starttime']);
              $("#to").text(data['play']['to']);
              $("#endtime").text(data['play']['endtime']);
              
              if (data['play']['ch'] < 55){
                $("#channel").text('Ch: ' + zeroPadding(data['play']['ch'], 2) + ' ' + data['play']['channel']);
              } else {
                $("#channel").text('Ch: ' + zeroPadding(data['play']['ch'], 3) + ' ' + data['play']['channel']);
              }
              $("#program_name").html(data['play']['program_name']);
              $("#program_info").html(data['play']['program_info']);
          
              // 次の番組
              $("#next_starttime").text(data['play']['next_starttime']);
              $("#next_to").text(data['play']['to']);
              $("#next_endtime").text(data['play']['next_endtime']);
              $("#next_program_name").html(data['play']['next_program_name']);
              $("#next_program_info").html(data['play']['next_program_info']);

              // ON Air
              $("#state").text('● ON Air');
              $("#state").css('color','#007cff');
            }

          } else if (data['info']['state'] == 'Offline') {

            // Offline
            $('#state').text('● Offline');
            $('#state').css('color','gray');

          }

          // stateを記録しておく
          document.getElementById('state').value = data['info']['state'];

          // progressbarの割合を計算して代入
          var percent = ((Math.floor(Date.now() / 1000) - data['play']['timestamp']) / data['play']['duration']) * 100;
          document.getElementById('progress').style.width = percent + '%';

          // チャンネルごとに実行
          Object.keys(data['onair']).forEach(function(key){

            // 変化がある場合のみ書き換え
            // てか特に内容変わってもいないのにDOM再構築するの無駄じゃんやめろ
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

            // 実況勢いは毎回書き換え
            document.getElementsByClassName('broadcast-ikioi-ch' + key)[0].innerHTML = data['onair'][key]['ikioi'];

            // プログレスバー
            var percent = ((Math.floor(Date.now() / 1000) - data['onair'][key]['timestamp']) / data['onair'][key]['duration']) * 100;
            document.getElementsByClassName('progress-ch' + key)[0].style.width = percent + '%';

          });

          // 高さ調整
          if (flg) $('.swiper-wrapper').eq(1).css('height', $('.broadcast-nav.swiper-slide').height() + 'px');

        }
      });
      return status;
    }()), 5000);

    // Zenzawatchのコードより一部改変した上で使わせて頂いています
    // 参考
    // https://developer.mozilla.org/ja/docs/Web/HTML/Canvas/Drawing_DOM_objects_into_a_canvas
    // ChromeだとtoBlobした際に汚染されるのでDataURIに変換する
    // https://qiita.com/kjunichi/items/f5993d34838e1623daf5
    const htmlToSvg = function(html, width = 640, height = 360) {
      scale = 1;
      if ($(window).width() < 500){ // スマホ用分岐
        fontsize = 19;
        subtitle_fontsize = 55;
      } else {
        fontsize = 29;
        subtitle_fontsize = 125;
      }
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
                  font-family: sans-serif;
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
                  font-size: 22px;
                  font-family: sans-serif;
                  color: #fff;
                }
                .dplayer-danmaku .dplayer-danmaku-item {
                  display: inline-block;
                  pointer-events: none;
                  user-select: none;
                  cursor: default;
                  white-space: nowrap;
                  font-weight: bold;
                  font-size: ${fontsize}px;
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
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      console.log('Video Canvas Size: ' + canvas.width + 'x' + canvas.height);
      return Promise.resolve({canvas: canvas});
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

    var count;
    var limit = 140;
    $('#tweet').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });
    $('#tweet-hashtag').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });

    // クリップボードの画像を格納する変数
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

    // キャプチャした画像をblobにして格納する関数
    $('#tweet-picture').click(function(event){
      capVideo(event);
    });

    // キャプチャした画像をコメント付きでblobにして格納する関数
    $('#tweet-picture-comment').click(function(event){
      capVideoComment(event);
    });

    // フォームをハッシュタグ以外リセットする関数
    $('#tweet-reset').click(function(event){
      tweet_reset(event);
    });

    // Twitterからログアウトさせる関数
    $('#tweet-status').on('click', '#tweet-logout', function(event){
      $.ajax({
        url: "tweet/logout.php",
        type: "post",
        processData: false,
        contentType: false
      })
      .done(function(data) {
        $("#tweet-status").html(data);
      })
      .fail(function(data){
        $("#tweet-status").html('<span class="tweet-failed">ログアウト中にエラーが発生しました…</span>');
      });
    });

    $("#tweet-file").on('change', function(event) {
      $("#tweet-status").html('画像を選択しました。');
    });

    $('#tweet-submit').click(function(event){
      $("#tweet-status").html('ツイートを送信中…');
      $("#tweet-submit").prop('disabled', true).addClass('disabled');
      tweet_send(event);
    });

    // Ctrl + Enterキーが押された時送信する
    $('#tweet-form').keydown(function(event){
      // クロスブラウザ対応用
      var event = event || window.event;
      // limit内なら
      if ((limit < 140 || file != null) && limit >= 0){
        // Ctrl(or Command) + Enterキーなら送信
        if (event.ctrlKey){
          if (event.which == 13){
            $("#tweet-status").html('ツイートを送信中…');
            $("#tweet-submit").prop('disabled', true).addClass('disabled');
            tweet_send(event);
          }
        }
        if (event.metaKey){
          if (event.which == 13){
            $("#tweet-status").html('ツイートを送信中…');
            $("#tweet-submit").prop('disabled', true).addClass('disabled');
            tweet_send(event);
          }
        }
      }
    });

    // tabキーが押されたときはフォーカス
    // Alt + 1キーが押された時はキャプチャ・
    // Alt + 2キーが押された時はコメント付きキャプチャ・
    // Alt + 3キーが押された時はフォームリセット
    $(document).keydown(function(event){

      // tabキー
      if (event.which == 9){
        // フォーカス
        $('#tweet-hashtag').focus();
      }

      // Alt(or option)キー
      if (event.altKey){
        if (event.which == 49){
          // 関数呼び出し
          capVideo(event);
        }
        if (event.which == 50){
          // 関数呼び出し
          capVideoComment(event);
        }
        if (event.which == 51){
          // 関数呼び出し
          tweet_reset(event);
        }
      }
    });


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
          }, "image/jpeg", 1);
        });

      } else {

        // 字幕が有効でない場合は通常のキャプチャ
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        console.log('Video Canvas Size: ' + canvas.width + 'x' + canvas.height);
        canvas.toBlob(function(blob){
          file = blob;
          console.log('Render Blob: ' + URL.createObjectURL(blob));
          // limit内なら
          if (limit >= 0){
            $('#tweet-submit').prop('disabled', false).removeClass('disabled');
          }
          $('#tweet-status').html('キャプチャした画像を選択しました。');
        }, "image/jpeg", 1);
      }
    }

    function capVideoComment(event){

      $('#tweet-status').html('コメント付きでキャプチャ中…');
      $('#tweet-submit').prop('disabled', true).addClass('disabled');
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

        html = html + subtitle_html + '</div>\n';
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

    // 0埋めする関数
    function zeroPadding(num, length){
      return ('0000000000' + num).slice(-length);
    }

  	// 時計のメインとなる関数
	  function clock(){
		  // 曜日を表す各文字列の配列
		  var weeks = new Array("Sun","Mon","Thu","Wed","Thr","Fri","Sat");
		  // 現在日時を表すインスタンスを取得
		  var now = new Date();
		  var y = now.getFullYear(); // 年
		  var mo = now.getMonth() + 1; // 月 0~11で取得されるので実際の月は+1したものとなる
		  var d = now.getDate();// 日
		  var w = weeks[now.getDay()]; // 曜日 0~6で日曜始まりで取得されるのでweeks配列のインデックスとして指定する

		  var h = now.getHours(); // 時
		  var mi = now.getMinutes();// 分
		  var s = now.getSeconds();// 秒

		  // 日付時刻文字列のなかで常に2ケタにしておきたい部分はここで処理
		  if (mo < 10) mo = "0" + mo;
		  if (d < 10) d = "0" + d;
		  if (h < 10) h = "0" + h;
		  if (mi < 10) mi = "0" + mi;
      if (s < 10) s = "0" + s;
      
		 $('#time').text(y + '/' + mo + '/' + d + ' ' + h + ':' + mi + ':' + s);
	  }

    // ツイートの文字数をカウントする関数
    function tweet_count(event){
      count = Array.from($('#tweet').val()).length + Array.from($('#tweet-hashtag').val()).length;
      limit = 140 - count;
      if (limit <= 140) {
        $("#tweet-num").text(limit);
        $("#tweet-num").removeClass('over');
        $("#tweet-num").removeClass('warn');
        // 送信中orキャプチャ中でないなら
        if ($("#tweet-status").text() != 'ツイートを送信中…' && $("#tweet-status").text() != 'コメント付きでキャプチャ中…' && $("#tweet-status").text() != 'キャプチャ中…'){
          $("#tweet-submit").prop('disabled', false).removeClass('disabled');
        }
        if (limit == 140) {
          $("#tweet-num").text(limit);
          if (file == null){ // キャプチャされてないなら
            $("#tweet-submit").prop('disabled', true).addClass('disabled');
          }
        }
        if (limit <= 20) {
          $("#tweet-num").text(limit);
          $("#tweet-num").addClass('warn');
        }
        if (limit == 0) {
          $("#tweet-num").text(limit);
          $("#tweet-num").addClass('over');
        }
        if (limit < 0) {
          $("#tweet-num").text(limit);
          $("#tweet-num").addClass('over');
          $("#tweet-submit").prop('disabled', true).addClass('disabled');
        }
      }
    }

    // ツイートを送信する関数
    function tweet_send(event) {
      event.preventDefault();
      var formData = new FormData($('#tweet-form').get(0));
      formData.append('picture', file);
      $('#content-box').show();
      $('#footer').show();
      $('#top').show();
      $('#tweet-box').removeClass('open');
      $('#tweet-close').removeClass('open');
      $('html').removeClass('open');
      $.ajax({
        url:  "tweet/tweet.php",
        type: "post",
        data: formData,
        processData: false,
        contentType: false
      })
      .done(function(data) {
        file = null;
        $('#tweet').val(null);
        $('#tweet-file').val(null);
        $("#tweet-status").html(data);
        $("#tweet-num").text(140);
      })
      .fail(function(data){
        $("#tweet-status").html('<span class="tweet-failed">送信中にエラーが発生しました…</span>');
      });
    }

    // フォームをハッシュタグ以外リセットする関数
    function tweet_reset(event){
      file = null;
      limit = 140;
      $("#tweet-num").text(limit);
      $("#tweet-num").removeClass('over');
      $("#tweet-num").removeClass('warn');
      $('#tweet-submit').prop('disabled', true).addClass('disabled');
      $('#tweet').val(null);
      $('#tweet-file').val(null);
      $('#content-box').show();
      $('#footer').show();
      var cookie = getCookieArray();
      if (cookie['twitter_session']){
        $("#tweet-status").html('<a id="tweet-logout" href="javascript:void(0)">ログアウト</a>');
      } else {
        $("#tweet-status").html('<a id="tweet-login" href="tweet/auth.php">ログイン</a>');
      }
    }

    // Cookieを配列にする関数
    function getCookieArray(){
      var arr = new Array();
      if(document.cookie != ''){
        var tmp = document.cookie.split('; ');
        for(var i = 0; i < tmp.length; i++){
          var data = tmp[i].split('=');
          arr[data[0]] = decodeURIComponent(data[1]);
        }
      }
      return arr;
    }

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

  });

