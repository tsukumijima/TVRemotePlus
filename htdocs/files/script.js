  $(function(){

    // 生放送・ファイル再生共通

    // ***** 一般 *****

    // clock() を毎秒実行する
    setInterval(clock, 1000);

    // 最初に実行
    if (Cookies.get('twitter')){
      $('#tweet-status').html('<a id="tweet-logout" href="javascript:void(0)"><i class="fas fa-sign-out-alt"></i>ログアウト</a>');
    } else {
      $('#tweet-status').html('<a id="tweet-login" href="/tweet/auth"><i class="fas fa-sign-in-alt"></i>ログイン</a>');
    }
    
    // Twitterアカウント情報を読み込み
    twitter = {account_name:'ログインしていません', account_id:'', account_icon:'/files/account_default.jpg'};
    if (Cookies.get('twitter') != undefined){ // Cookieがあれば読み込む
      twitter = JSON.parse(Cookies.get('twitter'));
    }
    $('#tweet-account-icon').attr('src', twitter['account_icon']);
    $('#tweet-account-name').text(twitter['account_name']);
    if (twitter['account_id'] != '') { // スクリーンネームが空でなければ
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

            if (document.getElementById('state').value === undefined){
              document.getElementById('state').value = data['state'];
            }

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

                // コメ番をリセット
                // リセットしないと前の局のコメ番より今の局のコメ番が小さい場合にコメントが流れない
                commentnumber = 0;
                res = '';
                // console.log('【コメ番をリセットしました】')

              }

            // それ以外は諸々問題があるので一旦リロード
            } else {
              if (data['status'] == 'failed'){
                setTimeout(function(){
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
                document.getElementById('epg-channel').textContent =
                    'Ch: ' + zeroPadding(data['stream'][stream]['ch_str'].replace('_', ''), 3) + ' ' + data['stream'][stream]['channel'];
              } else {
                document.getElementById('epg-channel').textContent =
                    'Ch: ' + zeroPadding(data['stream'][stream]['ch_str'].replace('_', ''), 3) + ' ' + data['stream'][stream]['channel'];
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
            if (document.querySelector('#ch' + key + ' .broadcast-start').innerHTML != data['onair'][key]['starttime'] ||
                document.querySelector('#ch' + key + ' .broadcast-title').innerHTML != data['onair'][key]['program_name']){

              // 書き換え用html
              var html = `<div class="broadcast-channel-box">
                            <div class="broadcast-channel">` + document.getElementById('ch' + key).dataset.channel + `</div>
                              <div class="broadcast-name-box">
                                <div class="broadcast-name">` + document.getElementById('ch' + key).dataset.name + `</div>
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
                            </div>
                          </div>`;

              // 番組情報を書き換え
              document.querySelector('#ch' + key + ' .broadcast-content').innerHTML = html;
            }

            // プログレスバー
            var percent = ((Math.floor(Date.now() / 1000) - data['onair'][key]['timestamp']) / data['onair'][key]['duration']) * 100;
            document.querySelector('#ch' + key + ' .progress').style.width = percent + '%';

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
                                    <div class="stream-title">` + data['stream'][key]['program_name'].replace(/<br>/g,' ') + `</div>
                                    <div class="stream-channel">` + data['stream'][key]['channel'] + `</div>
                                    <div class="stream-description">` + data['stream'][key]['program_info'].replace(/<br>/g,' ') + `</div>
                                  </div>
                                </div>`;

              // 番組情報を書き換え
              if (elem === undefined){

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


    // ***** ストリーム開始 *****

    // 再生開始ボックス
    $('body').on('click','.broadcast-wrap',function(){
      var $elem = $(this);
      $('#broadcast-stream-title').html($elem.data('channel') + ' ' + $elem.data('name'));
      $('#broadcast-stream-info').html($elem.find('.broadcast-title-id').html());
      $('#broadcast-stream-channel').val($elem.data('ch'));
      // 地デジ・BSCS判定
      if ($('.swiper-slide-thumb-active').text() == '地デジ'){
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
      $('#hotkey-box').removeClass('open');
      $('html').removeClass('open');
    });


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

            // ストリーム開始のセレクトボックスの表示も書き換える
            $('select[name=stream] option[value=' + streamnum + ']').text('Stream ' + streamnum + ' - Offline');

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
    
    // キャプチャ画像が入る連想配列
    window.capture = [];

    // 選択したキャプチャが入る配列
    window.capture_selected = [];

    // キャプチャ画像の最大保持数
    window.capture_maxcount = 10; // 10個

    // ツイートの文字数をカウント
    var count;
    var limit = 140;
    $('#tweet').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });
    $('#tweet-hashtag').on('keydown keyup keypress change',function(event){
      tweet_count(event);
    });
    
    // アカウント情報ボックスを表示する
    var clickEventType = ((window.ontouchstart!==null) ? 'mouseenter mouseleave' : 'touchstart');
    $('#tweet-title').on(clickEventType, function(event) {
      if ($('#tweet-account-box').css('visibility') === 'hidden' && (event.type === 'mouseenter' || event.type === 'touchstart')){
        $('#tweet-account-box').css('visibility', 'visible');
        $('#tweet-account-box').css('opacity', 1);
        // console.log('visible')
      } else {
        $('#tweet-account-box').css('visibility', 'hidden');
        $('#tweet-account-box').css('opacity', 0);
        // console.log('hidden')
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
      captureVideo(event);
    });

    // キャプチャした画像をコメント付きでblobにして格納する
    $('#tweet-picture-comment').click(function(event){
      captureVideoWithComment(event);
    });

    // フォームをハッシュタグ以外リセット
    $('#tweet-reset').click(function(event){
      // ツイートをリセット
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
        $('#tweet-status').html(data);
        $('#tweet-account-icon').attr('src', '/files/account_default.jpg');
        $('#tweet-account-name').text('ログインしていません');
        $('#tweet-account-name').removeAttr('href');
        $('#tweet-account-id').text('Not Login');
      })
      .fail(function(data){
        $('#tweet-status').html('<span class="tweet-failed">ログアウト中にエラーが発生しました…</span>');
      });
    });

    // クリップボードの画像を格納する
    $('#tweet').on('paste', function(event){
      
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

    // tabキーが押されたとき：フォーカス
    // ?キー：ショートカット一覧
    // Alt + 1キーが押された時：キャプチャ
    // Alt + 2キーが押された時：コメント付きキャプチャ
    // Alt + 3キーが押された時：フォームリセット
    // Ctrl + Enter：ツイート送信
    $(document).keydown(function(event){
      
      // クロスブラウザ対応用
      var event = event || window.event;

      // tabキー
      if (event.which === 9){
        // フォーカス
        event.preventDefault();
        if($(':focus').is('#tweet')) {
          $('#tweet').blur();
        } else {
          $('#tweet').focus();
        }
      }

      // ?キー
      // dplayer-comment-input
      if (document.activeElement.id != 'tweet' && document.activeElement.className != 'dplayer-comment-input' && event.key == '?'){
        event.preventDefault();
        $('#hotkey-box').toggleClass('open');
        $('#nav-close').toggleClass('open');
      }

      // Alt(or option)キー
      if (event.altKey){
        event.preventDefault();
        switch (event.which){

          case 49:
            // Alt + 1
            captureVideo(event);
          break;

          case 50:
            // Alt + 2
            captureVideoWithComment(event);
          break;

          case 51:
            // Alt + 3
            tweet_reset(event);
          break;
        }
      }

      // Ctrl + Enterキーが押された時にツイートを送信する
      // limit 内なら
      if (!$('#tweet-submit').prop('disabled')){ // ボタンが無効でなければ
        // Ctrl(or Command) + Enterキーなら送信
        if ((event.ctrlKey || event.metaKey) && event.which == 13){
          tweet_send(event);
        }
      }

    });

    // ツイートボタンが押された時にツイートを送信する
    $('#tweet-submit').click(function(event){
      if (!$('#tweet-submit').prop('disabled')){ // ボタンが無効でなければ
        tweet_send(event);
      }
    });


    // ***** キャプチャ画像リスト *****

    // キャプチャした画像の一覧を表示
    $('#tweet-picture-list').click(function(event){

      // キャプチャ画像リストを隠す
      if ($('#tweet-capture-box').hasClass('show')) {

        $('#tweet-capture-box').removeClass('show');

        // 0.1 秒遅らせてから display: none; を適用
        setTimeout(function(){
          $('#tweet-capture-box').removeClass('display'); // 必ず後
        }, 100);
      
      // キャプチャ画像リストを表示
      } else {
        
        // 先に display: none; を解除
        $('#tweet-capture-box').addClass('display'); // 必ず先

        setTimeout(function(){
          $('#tweet-capture-box').addClass('show');
        }, 1); // 0.001 秒遅らせるのがポイント
      
      }
    });

    // キャプチャした画像をクリック
    $(document).on('click', '.tweet-capture', function(event){

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

    // キャプチャした画像をリストに追加する関数
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
      capture.unshift(blob); // ISO8601のタイムスタンプをキーにする

      // html を追加
      document.getElementById('tweet-capture-box').insertAdjacentHTML('afterbegin', `
        <div class="tweet-capture" data-index="0" data-url="` + bloburl + `">
          <img class="tweet-capture-img" src="` + bloburl + `" />
          <div class="tweet-capture-cover"></div>
        </div>`
      );

      // 追加したキャプチャを自動選択
      $('.tweet-capture').each(function(index, elem){

        // 配列の先頭の要素
        if (index === 0) {

          // 自動選択
          if (capture_selected.length < 4) { // 4枚未満なら（まだ追加されていないため「未満」）
            selectCaptureImage(elem, true);
          } else { // 5枚以上
            elem.classList.add('disabled'); // 無効化
          }
        
        } else {
          
          // 自動選択を解除
          deselectCaptureImage(elem, true);
          
          // インデックスを書き換え
          elem.dataset.index++; 
        }
      
      });

    }

    // キャプチャ画像を選択する関数
    // 第1引数: 選択された要素を指定
    // 第2引数: true を指定すると data-autoselect を付与する
    function selectCaptureImage(elem, autoselect = false) {

      // 選択されたキャプチャを配列に追加
      capture_selected.push(capture[elem.dataset.index]);

      // data-order を追加
      elem.dataset.order = (capture_selected.length - 1);
      $(elem).find('.tweet-capture-cover').text(capture_selected.length);

      // data-autoselect を追加
      if (autoselect) {
        elem.dataset.autoselect = true;
      }

      // 4 枚選択されていたら他のキャプチャを無効にする
      if (capture_selected.length === 4) {

        $('.tweet-capture').each(function(index, elem){

          // order がなければ
          if (typeof elem.dataset === 'undefined' || typeof elem.dataset.order === 'undefined') {
            elem.classList.add('disabled'); // 無効化
          }
  
        });
      }

      // ツイートが limit 内なら送信ボタンを有効化する
      if (limit >= 0){
        document.getElementById('tweet-submit').disabled = false;
        document.getElementById('tweet-submit').classList.remove('disabled');
      }

      // カバーを表示
      elem.classList.add('selected');

      // メッセージを表示
      if (!autoselect) {
      
        document.getElementById('tweet-status').textContent = capture_selected.length + ' 枚の画像を選択しました。';
      
      // (capture_selected.length + document.querySelectorAll('.tweet-capture[data-autoselect]').length) はおまじない
      } else if ((Number(capture_selected.length) - (document.querySelectorAll('.tweet-capture[data-autoselect]').length - 1)) > 1) {
      
        document.getElementById('tweet-status').textContent = 
          (Number(capture_selected.length) - (document.querySelectorAll('.tweet-capture[data-autoselect]').length - 1)) + ' 枚の画像を選択しました。';
      
      }

    }

    // キャプチャ画像を選択解除する関数
    // 第1引数: 選択された要素を指定
    // 第2引数: true を指定すると data-autoselect が付与されている要素のみ選択を解除
    function deselectCaptureImage(this_, autoselect = false) {

      // order が定義されていれば
      if (typeof this_.dataset !== 'undefined' && typeof this_.dataset.order !== 'undefined') {

        // autoselect が true のとき、data-autoselect が存在するか
        if ((autoselect === true && typeof this_.dataset.autoselect !== 'undefined') || autoselect === false) {

          // 選択解除されたキャプチャを配列から削除
          capture_selected.splice(this_.dataset.order, 1);
          
          // order を書き換え
          $('.tweet-capture').each(function(index, elem){
    
            // そのキャプチャの order が削除された order よりも大きければ
            if (elem.dataset.order > this_.dataset.order) {
              
              // 配列に合わせて orderを詰める
              elem.dataset.order--;
              $(elem).find('.tweet-capture-cover').text(parseInt(elem.dataset.order) + 1);
    
            }
            
            // キャプチャを有効化
            if (elem.classList.contains('disabled')){
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
          if (document.getElementById('tweet').value.length === 0 && capture_selected.length === 0){
            document.getElementById('tweet-submit').disabled = true;
            document.getElementById('tweet-submit').classList.add('disabled');
          }
    
          // カバーを非表示
          this_.classList.remove('selected');

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

    // キャプチャ画像をすべて選択解除する関数
    function deselectAllCaptureImage() {

      // 配列を空にする
      capture_selected = [];

      // 本文が空なら送信ボタンを無効化する
      if (document.getElementById('tweet').value.length === 0) {
        document.getElementById('tweet-submit').disabled = true;
        document.getElementById('tweet-submit').classList.add('disabled');
      }

      // 要素ごとに実行
      $('.tweet-capture').each(function(index, elem){

        // order が定義されていれば
        if (typeof elem.dataset !== 'undefined' && typeof elem.dataset.order !== 'undefined') {
    
          // data-order を削除
          delete elem.dataset.order;

          // data-autoselect を削除
          if (typeof elem.dataset.autoselect !== 'undefined') {
            delete elem.dataset.autoselect;
          }
    
          // カバーを非表示
          elem.classList.remove('selected');
            
        }
            
        // キャプチャを有効化
        if (elem.classList.contains('disabled')){
          elem.classList.remove('disabled');
        }

      });

    }


    // ***** キャプチャ関連の関数 *****

    // キャプチャした画像をblobにして格納する関数
    function captureVideo(event){

      $('#tweet-status').text('キャプチャ中…');
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

            $('#tweet-status').text('キャプチャしました。');

            // キャプチャした画像を格納
            console.log('Render Blob: ' + URL.createObjectURL(blob));
            addCaptureImage(blob);
          
          }, 'image/jpeg', 1);
        });

      } else {

        // 普通にキャプチャする
        videoToCanvas(video).then(({canvas}) => {
          canvas.toBlob(function(blob){
          
            $('#tweet-status').text('キャプチャしました。');
            
            // キャプチャした画像を格納
            console.log('Render Blob: ' + URL.createObjectURL(blob));
            addCaptureImage(blob);
          
          }, 'image/jpeg', 1);
        });

      }
    }

    // キャプチャした画像をコメント付きでblobにして格納する関数
    function captureVideoWithComment(event){

      $('#tweet-status').text('コメント付きでキャプチャ中…');
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

          $('#tweet-status').text('コメント付きでキャプチャしました。');
          
          // キャプチャした画像を格納
          console.log('Render Blob: ' + URL.createObjectURL(blob));
          addCaptureImage(blob);
        
        }, 'image/jpeg', 1);
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
                  font-family: 'Open Sans','Segoe UI','Arial',sans-serif;
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
        if ($('#tweet-status').text() != 'ツイートを送信中…' && $('#tweet-status').text() != 'コメント付きでキャプチャ中…' && $('#tweet-status').text() != 'キャプチャ中…'){
          $('#tweet-submit').prop('disabled', false).removeClass('disabled'); // 一旦ボタンを有効化
        }

        // ハッシュタグ以外のツイート文が空
        if (count_tweet === 0) {
          if (capture_selected.length === 0){ // キャプチャがない（ハッシュタグ以外送信するものがない）場合はボタンを無効に
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

        // キャプチャ画像の選択をすべて解除
        deselectAllCaptureImage();

        // 文字数リミットをリセット
        limit = 140;
        $('#tweet-num').text(140);
        
        $('#tweet').val(null);
        $('#tweet-status').html(data);
      
      })
      .fail(function(data){
        $('#tweet-status').html('<span class="tweet-failed">送信中にエラーが発生しました…</span>');
      });
    }

    // フォームをハッシュタグ以外リセットする関数
    function tweet_reset(event){

      // キャプチャ画像の選択をすべて解除
      deselectAllCaptureImage();

      // 文字数リミットをリセット
      limit = 140;
      $('#tweet-num').text(limit);
      $('#tweet-num').removeClass('over');
      $('#tweet-num').removeClass('warn');

      $('#tweet-submit').prop('disabled', true).addClass('disabled');
      $('#tweet').val(null);
      $('#content-box').show();
      $('#footer').show();
      
      if (Cookies.get('twitter')){
        $('#tweet-status').html('<a id="tweet-logout" href="javascript:void(0)"><i class="fas fa-sign-out-alt"></i>ログアウト</a>');
      } else {
        $('#tweet-status').html('<a id="tweet-login" href="/tweet/auth"><i class="fas fa-sign-in-alt"></i>ログイン</a>');
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

