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
      $('#menu-content').animate({height: 'toggle'}, 150);
      $('#menu-content').removeClass('open');
    }
  });

  // トーストのオプション
  toastr.options = {
    "closeButton": true,
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