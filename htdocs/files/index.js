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
    });

    // キャンセル
    $('.redbutton').click(function(event){
      $('#nav-close').removeClass('open');
      $('#broadcast-stream-box').removeClass('open');
      $('html').removeClass('open');
    });

  });
