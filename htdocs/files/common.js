
  $(function(){

      // メニュー開閉
    $('#nav-open').click(function(event){
      $('#nav-close').addClass('open');
      $('#nav-content').addClass('open');
      $('html').addClass('open');
    });

    $('#nav-close').click(function(event){
      $('#nav-close').removeClass('open');
      $('#nav-content').removeClass('open');
      $('#broadcast-stream-box').removeClass('open');
      $('#search-stream-box').removeClass('open');
      $('#chromecast-box').removeClass('open');
      $('html').removeClass('open');
    });

    // サブメニューボタン開閉
    $('#menu-button').click(function(event){
      $('#menu-content').velocity($('#menu-content').is(':visible') ? 'slideUp' : 'slideDown', 150);
      $('#menu-content').toggleClass('open');
      $('#menu-close').toggleClass('open');
    });

    // サブメニューとサブメニューボタン以外クリックでサブメニューを引っ込める
    $(document).click(function(event) {
      if (!$(event.target).closest('#menu-button').length && !$(event.target).closest('#menu-content').length){
        $('#menu-content').velocity('slideUp', 150);
        $('#menu-content').removeClass('open');
        $('#menu-close').removeClass('open');
      }
    });

    $('#menu-close').click(function(){
      $('#menu-content').velocity('slideUp', 150);
      $('#menu-content').removeClass('open');
      $('#menu-close').removeClass('open');
    });

    // セレクトボックス開閉
    $('.select-wrap').on('mousedown', function(){
      if (document.body.clientWidth > 1024){
        $(this).toggleClass('open');
      }
    });

    $('.select-wrap select').change(function(){
      if (document.body.clientWidth > 1024){
        $(this).parent().removeClass('open');
      }
    });

    // パスワード開閉
    $('.password-box-input').click(function(){
      $('.password-box-input').toggleClass('fa-eye-slash');
      $('.password-box-input').toggleClass('fa-eye');
      var input = $(this).prev("input");
      // type切替
      if (input.attr("type") == "password") {
          input.attr("type", "text");
      } else {
          input.attr("type", "password");
      }
    });

    // 上までスクロールで戻る
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