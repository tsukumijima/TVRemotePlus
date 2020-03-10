
  // 参考: https://qiita.com/yukiTTT/items/773356c2483b96c9d4e0
  function handleTouchMove(event) {
    event.preventDefault();
  }

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

  $(function(){
    
    // ***** リンク *****
    $('a[href]:not(a[target="_blank"]), .stream-view, button[type="submit"]').click(function(event){
      if (!event.target.classList.contains('stream-stop-icon')){
        $('#cover').addClass('open');
      }
    });

    // ***** ダークモード *****
    if (settings['dark_theme']){
      $('html').addClass('dark');
    } else {
      $('html').removeClass('dark');
    }

    // ***** メニュー開 *****
    $('#nav-open').click(function(event){
      $('#nav-close').addClass('open');
      $('#nav-content').addClass('open');
      //スクロール禁止
      document.addEventListener('touchmove', handleTouchMove, { passive: false });
    });

    // ***** メニュー閉 *****
    $('#nav-close').click(function(event){
      $('#nav-close').removeClass('open');
      $('#nav-content').removeClass('open');
      $('#broadcast-stream-box').removeClass('open');
      $('#search-stream-box').removeClass('open');
      $('#chromecast-box').removeClass('open');
      $('#hotkey-box').removeClass('open');
      //スクロール復帰
      document.removeEventListener('touchmove', handleTouchMove, { passive: false });
    });

    // ***** サブメニューボタン開閉 *****
    $('#menu-button').click(function(event){
      $('#menu-content').velocity($('#menu-content').is(':visible') ? 'slideUp' : 'slideDown', 160);
      $('#menu-content').toggleClass('open');
      $('#menu-close').toggleClass('open');
      if ($('#menu-content').is(':visible')){
        //スクロール復帰
        document.removeEventListener('touchmove', handleTouchMove, { passive: false });
      } else {
        //スクロール禁止
        document.addEventListener('touchmove', handleTouchMove, { passive: false });
      }
    });

    // サブメニューかサブメニューボタン以外クリックでサブメニューを引っ込める
    $('.menu-link, google-cast-launcher, #menu-close').click(function(){
      $('#menu-content').velocity('slideUp', 160);
      $('#menu-content').removeClass('open');
      $('#menu-close').removeClass('open');
      //スクロール復帰
      document.removeEventListener('touchmove', handleTouchMove, { passive: false });
    });

    // ***** パスワード開閉 *****
    $('.password-box-input').click(function(){
      $('.password-box-input').toggleClass('fa-eye-slash');
      $('.password-box-input').toggleClass('fa-eye');
      var input = $(this).prev("input");
      // type切替
      if (input.attr('type') == 'password') {
          input.attr('type', 'text');
      } else {
          input.attr('type', 'password');
      }
    });

    $(window).scroll(function() {

      // アカウント情報ボックスを非表示
      $('#tweet-account-box').css('opacity', 0);
      $('#tweet-account-box').css('visibility', 'hidden');

      // スクロール位置を取得
      var topPos = $(this).scrollTop();

      // 表示・非表示
      if (topPos > 400) {
        $('#scroll').css('opacity', '1');
        $('#scroll').css('visibility', 'visible');
      } else {
        $('#scroll').css('opacity', '0');
        $('#scroll').css('visibility', 'hidden');
      }

    });

    // 一番上まで戻る
    $('#scroll').click(function(){

      $('#scroll').addClass('hover');
      var topPos = $(window).scrollTop();
      
      if (topPos > 400) {
        $('html, body').velocity('scroll', { duration: 700, offset: -54 });
        setTimeout(function(){
          $('#scroll').removeClass('hover');
        }, 700);
      }

    });

    // トーストのオプション
    toastr.options = {
      "closeButton": false,
      "debug": false,
      "newestOnTop": false,
      "progressBar": false,
      "positionClass": "toast-bottom-left",
      "preventDuplicates": false,
      "onclick": null,
      "showDuration": "200",
      "hideDuration": "200",
      "timeOut": "5000",
      "extendedTimeOut": "1000",
      "showEasing": "linear",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut"
    }

  });