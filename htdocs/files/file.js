
  // クリックされた部分がスクロールバーかどうかをevent情報から返す関数
  function isClickScrollbar(event){

    var target_width = event.currentTarget.offsetWidth
    var scrollbar_width = target_width - event.currentTarget.clientWidth;
    var x = event.clientX -  event.currentTarget.getBoundingClientRect().left;

    if (target_width - x < scrollbar_width){
      return true;
    } else {
      return false;
    }
  }

  // ロード時 & リサイズ時に発火
  $(window).on('load resize', function(event){

    // コメントを取得してコメント一覧画面にコメントを流し込む
    // スマホ以外のみ発動（スマホだと動作が遅くなるため）
    if (document.body.clientWidth > 768 && document.getElementById('comment-draw-box').innerHTML == ''){

      // コメント一覧の時間欄のwidthを調整
      $('#comment-time').css('width', '62px');

      $.ajax({
        url: '/api/jikkyo/' + stream + '?id=TVRemotePlus',
        dataType: 'json',
        cache: false,
        success: function(data) {

          if (data['data'] != null && data['data'][0]){ //data['data'] があれば(nullでなければ)

            var html = '';

            for (i = 0; i <= data['data'].length-1; i++){

              // 分と秒を計算
              var videotime = (data['data'][i][0]).toString();
              ss = Math.floor(videotime % 60);
              mm = Math.floor(videotime / 60);
              if (ss < 10) ss = '0' + ss;
              if (mm < 10) mm = '0' + mm;
              var time = mm + ':' + ss;

              html +=  `<tbody class="comment-file" data-time="` + Math.floor(videotime) + `">
                          <tr>
                            <td class="time" align="center" value="` + videotime + `">` + time + `</td>
                            <td class="comment">` + data['data'][i][4].toString() + `</td>
                          </tr>
                        </tbody>`;
            }

            // コメントを一気にコメント一覧に挿入
            // 1つずつだと遅すぎるため一気に、さらにスピード重視であえてJavaScriptで実装
            document.getElementById('comment-draw-box').innerHTML = html;

          }
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

    // コメントスクロール・progressbar
    var time = 0; // 秒数を記録
    var count = 0; // 同じ秒数の要素をカウント
    var auto = true;  // 自動スクロール中かどうか
    $('.dplayer-video-current').on('timeupdate seeking', function(){

      var current = Math.floor(dp.video.currentTime); // 小数点以下は切り捨て
      if (time != current) count = 0; // カウントをリセット

      // 手動でスクロールしていないなら
      if (auto){

        // ボタンを非表示
        document.getElementById('comment-scroll').style.visibility = 'hidden';
        document.getElementById('comment-scroll').style.opacity = 0;

        // 768px以上 & その秒数にコメントが存在する & 重複していない
        if (document.body.clientWidth > 768 && $('.comment-file[data-time=' + current + ']').length){

          // コメントまでスクロールする
          $('.comment-file[data-time=' + current + ']').eq(count).velocity('scroll', {
            container: $('#comment-draw-box'),
            duration: 150,
            offset: -$('#comment-draw-box')[0].clientHeight,
          });

          // 値を保存しておく
          count++;
          time = current;
          scroll = $('#comment-draw-box').scrollTop();

        }
      } else {

        auto = false;
        // ボタンを表示
        document.getElementById('comment-scroll').style.visibility = 'visible';
        document.getElementById('comment-scroll').style.opacity = 1;
      }

      // progressbarの割合を計算して代入
      var percent = (dp.video.currentTime / dp.video.duration) * 100;
      document.getElementById('progress').style.width = percent + '%';

    });

    // マウスホイール or スワイプ or mousedown
    $('#comment-draw-box').on('wheel touchmove mousedown', function(){
      // 手動スクロール中
      auto = false;
      // ボタンを表示
      document.getElementById('comment-scroll').style.visibility = 'visible';
      document.getElementById('comment-scroll').style.opacity = 1;
    });

    // コメントスクロールボタンがクリックされたとき
    $('#comment-scroll').click(function(){
      
      // 自動スクロールに戻す
      auto = true;

      // スクロール
      $('.comment-file[data-time=' + Math.floor(dp.video.currentTime) + ']').eq(count).velocity('scroll', {
        container: $('#comment-draw-box'),
        duration: 300,
        offset: -$('#comment-draw-box')[0].clientHeight,
      });

      // ボタンを非表示
      document.getElementById('comment-scroll').style.visibility = 'hidden';
      document.getElementById('comment-scroll').style.opacity = 0;
    });

  });
