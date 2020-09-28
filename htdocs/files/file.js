
  // ロード時 & リサイズ時に発火
  $(window).on('load resize', function(event){

    // コメントを取得してコメント一覧画面にコメントを流し込む
    // スマホ以外のみ発動（スマホだと動作が遅くなるため）
    if (document.body.clientWidth > 768 && document.querySelector('#comment-draw-box > tbody').innerHTML == ''){

      // コメント一覧の時間欄のwidthを調整
      $('#comment-time').css('width', '62px');

      // コメントを読み込みが完了したときに発火
      dp.on('danmaku_load_end', function() {
        
        let html = [];

        for (danmaku of dp.danmaku.dan) {

          // 分と秒を計算
          let videotime = (danmaku['time']).toString();
          let ss = Math.floor(videotime % 60);
          let mm = Math.floor(videotime / 60);
          if (ss < 10) ss = '0' + ss;
          if (mm < 10) mm = '0' + mm;
          let time = mm + ':' + ss;

          html.push(`<tr class="comment-file" data-time="` + Math.floor(videotime) + `">
                      <td class="time" align="center" value="` + videotime + `">` + time + `</td>
                      <td class="comment">` + danmaku['text'] + `</td>
                    </tr>`);
        }

        // 軽量モードのみ
        if (settings['comment_list_performance'] === 'light') {

          // 軽量モード中のクラス
          document.querySelector('#comment-box').classList.add('comment-lightmode')

          // Clusterize.js で高速スクロール
          let clusterize = new Clusterize({
            rows: html,
            scrollElem: document.querySelector('#comment-draw-box'),
            contentElem: document.querySelector('#comment-draw-box > tbody'),
          });

        } else {

          // 軽量モード中のクラスを削除
          document.querySelector('#comment-box').classList.remove('comment-lightmode')
        
          // コメントを一気にコメント一覧に挿入
          // 1つずつだと遅すぎるため一気に、さらにスピード重視であえてJavaScriptで実装
          document.querySelector('#comment-draw-box > tbody').innerHTML = html.join('');

        }

      });


      // 時間クリック時にその再生位置に飛ぶやつ
      $(document).on('click', '.comment-file', function(){
        // durationよりも大きい場合
        if ($(this).find('.time').attr('value') > dp.video.duration){
          dp.video.currentTime = dp.video.duration;
        // 通常
        } else {
          dp.video.currentTime = $(this).find('.time').attr('value');
        }
      });

    }

  });

  $(function(){

    // passive ネームスペースで addEventListener でスクロールの preventDefault() が効くようにする
    // addEventListener の実行時に { passive: false } を設定する
    // 参考: https://blog.webgoto.net/639/・https://qiita.com/yukiTTT/items/773356c2483b96c9d4e0
    jQuery.event.special.wheel = {
      setup: function( _, ns, handle ){
        if ( !ns.includes('passive') ) return false;
        this.addEventListener('wheel', handle, { passive: false });
      }
    };
    jQuery.event.special.touchmove = {
      setup: function( _, ns, handle ){
        if ( !ns.includes('passive') ) return false;
        this.addEventListener('touchmove', handle, { passive: false });
      }
    };
    jQuery.event.special.mousedown = {
      setup: function( _, ns, handle ){
        if ( !ns.includes('passive') ) return false;
        this.addEventListener('mousedown', handle, { passive: false });
      }
    };

    // コメントスクロール・プログレスバー
    var time = 0; // 秒数を記録
    var count = 0; // 同じ秒数の要素をカウント
    var autoscroll = true;  // 自動スクロール中かどうか

    $('.dplayer-video-current').on('timeupdate seeking', function(){

      var current = Math.floor(dp.video.currentTime); // 小数点以下は切り捨て
      if (time != current) count = 0; // カウントをリセット

      // プログレスバーの進捗割合を設定
      let percent = (dp.video.currentTime / dp.video.duration) * 100;
      document.getElementById('progress').style.width = percent + '%';

      // 標準モードのみ
      if (settings['comment_list_performance'] !== 'light') {

        // 自動スクロール中
        if (autoscroll){

          // ボタンを非表示
          document.getElementById('comment-scroll').style.visibility = 'hidden';
          document.getElementById('comment-scroll').style.opacity = 0;

          // 768px以上 & その秒数にコメントが存在する & 重複していない
          if (document.body.clientWidth > 768 && $('.comment-file[data-time=' + current + ']').length){

            // 要素を取得
            var $comment = $('.comment-file[data-time=' + current + ']').eq(count);
            var $commentbox = $('#comment-draw-box');

            // コメントまでスクロールする
            $comment.velocity('scroll', {
              container: $commentbox,
              duration: 150,
              offset: -$commentbox.height() + $comment.height(),
            });

            // 値を保存しておく
            count++;
            time = current;
            scroll = $commentbox.scrollTop();

          }
        } else {

          autoscroll = false;

          // ボタンを表示
          document.getElementById('comment-scroll').style.visibility = 'visible';
          document.getElementById('comment-scroll').style.opacity = 1;
        }

      // 軽量モード
      } else {

        // 自動スクロールを無効化
        autoscroll = false;

      }

    });

    // マウスホイール or スワイプ or mousedown
    $('#comment-draw-box').on('wheel.passive touchmove.passive mousedown.passive', function(event){

      // 標準モードのみ
      if (settings['comment_list_performance'] !== 'light') {
      
        // 手動スクロール中
        autoscroll = false;

        // ボタンを表示
        document.getElementById('comment-scroll').style.visibility = 'visible';
        document.getElementById('comment-scroll').style.opacity = 1;

      }

    });

    // コメントスクロールボタンがクリックされたとき
    $('#comment-scroll').click(function(){
      
      // 自動スクロールに戻す
      autoscroll = true;

      // 要素を取得
      var $comment = $('.comment-file[data-time=' + Math.floor(dp.video.currentTime) + ']').eq(count);
      var $commentbox = $('#comment-draw-box');

      // スクロール
      $comment.velocity('scroll', {
        container: $commentbox,
        duration: 300,
        offset: -$commentbox.height() + $comment.height(),
      });

      // ボタンを非表示
      document.getElementById('comment-scroll').style.visibility = 'hidden';
      document.getElementById('comment-scroll').style.opacity = 0;
    });

  });
