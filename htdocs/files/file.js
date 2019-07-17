  $(function(){

    // コメントを取得してコメント一覧画面にコメントを流し込む
    // スマホ以外のみ発動（スマホだと動作が遅くなるため）
    if ($(window).width() > 768){

      $.ajax({
        url: 'api/jkapi.php/v3/?id=TVRemotePlus',
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

              html += '<tr><td class="time">' + time + '</td>'
                    + '<td class="comment">' + data["data"][i][4].toString() +'</td></tr>';
            }

            // コメントを一気にコメント一覧に挿入
            // 1つずつだと遅すぎるため一気に、さらにスピード重視であえてJavaScriptで実装しています
            document.getElementById('comment-draw-box').innerHTML = html;

          }
        }
      });
    }

    // progressbar関連
    $('.dplayer-video-current').on('timeupdate',function(){
      // progressbarの割合を計算して代入
      var video = $('.dplayer-video-current').get(0);
      var percent = (video.currentTime / video.duration) * 100;
      $('#progress').width(percent + '%');
    });

  });
