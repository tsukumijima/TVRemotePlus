
$(function(){

  // 個人設定を反映
  if (settings['twitter_show']) $('#twitter_show').prop('checked', true)
  else $('#twitter_show').prop('checked', false)
  if (settings['comment_show']) $('#comment_show').prop('checked', true);
  else $('#comment_show').prop('checked', false)

  $('.bluebutton').click(function(){
    var settings = {};
    settings['twitter_show'] = $('#twitter_show').prop('checked');
    settings['comment_show'] = $('#comment_show').prop('checked');
    settings['onclick_stream'] = $('#onclick_stream').prop('checked');
    console.log(settings);
    var json = JSON.stringify(settings);
    Cookies.set('settings', json);
    toastr.success('個人設定を保存しました。');
  });

  $('.redbutton').click(function(){
    toastr.success('実装中です…');
  });

});