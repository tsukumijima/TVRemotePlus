$(function(){

  // 個人設定を反映
  if (settings['twitter_show']) $('#twitter_show').prop('checked', true)
  else $('#twitter_show').prop('checked', false)
  if (settings['comment_show']) $('#comment_show').prop('checked', true);
  else $('#comment_show').prop('checked', false)

  $('#setting-user').submit(function(event){

    event.preventDefault();

    var settings = {};
    settings['twitter_show'] = $('#twitter_show').prop('checked');
    settings['comment_show'] = $('#comment_show').prop('checked');
    settings['onclick_stream'] = $('#onclick_stream').prop('checked');
    console.log(settings);
    var json = JSON.stringify(settings);
    Cookies.set('settings', json);
    toastr.success('個人設定を保存しました。');

  });

  $('#setting-env').submit(function(event){

    event.preventDefault();

    // フォーム送信
    $.ajax({
      url: '/setting/',
      type: 'post',
      data: $('#setting-env').serialize(),
      cache: false,
      success: function(data) {
        toastr.success('環境設定を保存しました。');
      }
    });

  });

});