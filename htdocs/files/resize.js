  $(function(){

    // ウインドウサイズ
    $(window).on('load resize',function() {
      // console.log('resize');
      // 1024px以上
      if ($(window).width() > 1024){
        // ウィンドウを読み込んだ時、リサイズされた時に発動
        // 何故か上手くいかないので8回繰り返す
        var result = 0;
        while (true){
          var WindowHeight = $(window).height() - $('#top').height();
          var width = $('section').width();
          var height= $('#dplayer').width() * (9 / 16) + 136; // $('#tweet-box').height()
          if (result == (width * WindowHeight) / height) break; // 同じならループを抜ける
          if (width < ($(window).width() / 2)){ // widthが変なとき用
            $('section').css('max-width', '1250px');
            break;
          }
          result = (width * WindowHeight) / height;
          // console.log('width: ' + width);
          // console.log('result: ' + result);
          $('section').css('max-width', result + 'px');
        }
      }
    });

  });