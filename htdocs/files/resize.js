  $(function(){

    // ウインドウサイズ
    $(window).on('load resize',function() {
      // 1024px以上
      if ($(window).width() > 1024){
        // ウィンドウを読み込んだ時、リサイズされた時に発動
        // 何故か上手くいかないので6回繰り返す
        for (var i = 0; i < 8; i++){
          var WindowHeight = $(window).height() - $('#top').height();
          var width = $('#content-wrap').width();
          var height= $('#dplayer').width() * (9 / 16) + 136; // $('#tweet-box').height()
          $('section').css('max-width',(width * WindowHeight) / height + "px" );
        }
      }
    });

  });