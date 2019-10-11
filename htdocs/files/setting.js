
  // 個人設定を反映
  $(window).on('load', function(){

    if (settings['twitter_show']){
      $('#twitter_show').prop('checked', true);
    } else {
      $('#twitter_show').prop('checked', false);
    }
    if (settings['comment_show']){
      $('#comment_show').prop('checked', true);
    } else {
      $('#comment_show').prop('checked', false);
    }

  });

  $(function(){

    $('#setting-user').submit(function(event){

      event.preventDefault();
      $('.bluebutton').attr('disabled', true);

      var settings = {};
      settings['twitter_show'] = $('#twitter_show').prop('checked');
      settings['comment_show'] = $('#comment_show').prop('checked');
      settings['comment_size'] = $('#comment_size').val();
      settings['comment_delay'] = $('#comment_delay').val();
      settings['onclick_stream'] = $('#onclick_stream').prop('checked');
      console.log(settings);
      var json = JSON.stringify(settings);
      Cookies.set('settings', json, { expires: 365 });
      toastr.success('個人設定を保存しました。');
      setTimeout(function(){
        $('.bluebutton').attr('disabled', false);
      }, 200);

    });

    $('#setting-env').submit(function(event){

      event.preventDefault();
      $('.redbutton').attr('disabled', true);

      // フォーム送信
      $.ajax({
        url: '/setting/',
        type: 'post',
        data: $('#setting-env').serialize(),
        cache: false,
        success: function(data) {
          toastr.success('環境設定を保存しました。');
          setTimeout(function(){
            $('.redbutton').attr('disabled', false);
          }, 200);
        }
      });

    });

  });