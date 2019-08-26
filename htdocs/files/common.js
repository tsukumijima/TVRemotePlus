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
  $('#menubutton').click(function(event){
    $('#menu-content').animate({height: 'toggle'}, 150);
    $('#menu-content').toggleClass('open');
  });

  // サブメニューとサブメニューボタン以外クリックでサブメニューを引っ込める
  $(document).click(function(event) {
    if (!$(event.target).closest('#menubutton').length && !$(event.target).closest('#menu-content').length){
      $('#menu-content').animate({height: 'hide'}, 150);
      $('#menu-content').removeClass('open');
    }
  });

  // パスワード開閉
  $('.password-box-input').click(function(){
    $('.password-box-input').toggleClass('fa-eye');
    $('.password-box-input').toggleClass('fa-eye-slash');
    var input = $(this).prev("input");
    // type切替
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
  });

  // 個人設定読み込み
  settings = {twitter_show:true, comment_show:true, onclick_stream:false};
  if (Cookies.get('settings') != undefined){
    settings = JSON.parse(Cookies.get('settings'));
  }

  // 上までスクロールで戻る
  $(window).scroll(function() {

    // スクロール位置を取得
    var topPos = $(this).scrollTop();

    // 表示・非表示
    if (topPos > 400) {
      $('#scroll').css('opacity', '1');
    } else {
      $('#scroll').css('opacity', '0');
    }

  });

  // 一番上まで戻る
  $('#scroll').click(function(){
    $('html, body').animate({ scrollTop: 0 }, 700);
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