
  // ロード時 & リサイズ時に発火
  $(window).on('load resize', function(event){

    // コメントを取得してコメント一覧画面にコメントを流し込む
    // スマホ以外のみ発動（スマホだと動作が遅くなるため）
    if (document.body.clientWidth > 768 && document.getElementById('comment-draw-box').innerHTML == ''){

      // コメント一覧の時間欄のwidthを調整
      $('#comment-time').css('width', '62px');

      $.ajax({
        url: '/api/jkapi.php/v3/?id=TVRemotePlus',
        dataType: 'json',
        cache: false,
        success: function(data) {

          if (data["data"] != null && data["data"][0]){ //data["data"] があれば(nullでなければ)

            var html = '';

            for (i = 0; i <= data["data"].length-1; i++){

              // 分と秒を計算
              var videotime = (data["data"][i][0]).toString();
              ss = Math.floor(videotime % 60);
              mm = Math.floor(videotime / 60);
              if (ss < 10) ss = "0" + ss;
              if (mm < 10) mm = "0" + mm;
              var time = mm + ':' + ss;            

              html += '<tr class="comment-file"><td class="time" style="width: 62px;" align="center" value="' + videotime+ '">' + time + '</td>'
                    + '<td class="comment">' + data["data"][i][4].toString() +'</td></tr>';
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

    // progressbar関連
    $('.dplayer-video-current').on('timeupdate',function(){
      // progressbarの割合を計算して代入
      var video = $('.dplayer-video-current').get(0);
      var percent = (video.currentTime / video.duration) * 100;
      document.getElementById('progress').style.width = percent + '%';
    });

  });
