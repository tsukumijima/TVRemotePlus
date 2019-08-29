  $(function(){

    var res = ''; // 初回だけ空にする

    // jQueryでSleep
    function wait(sec) {
 
      // jQueryのDeferredを作成
      var objDef = new $.Deferred;
   
      setTimeout(function () {
        // sec秒後にresolve()を実行してPromiseする
        objDef.resolve(sec);
      }, sec*1000);
   
      return objDef.promise();
    }
   

    // コメント取得
    setInterval((function status(){
        $.ajax({
          url: "/api/jkapi.php?res=" + res,
          dataType: "json",
          cache: false,
          success: function(data) {
  
            var danmaku = {}; 
            res = data["last_res"];
            last_res = data["res"];
  
            // console.log('―― GetComment postres:' + last_res + ' getres:' + res + ' draw:' + (res - last_res) + ' ――');
            // console.log(data['data']);
  
            if (data["ikioi"] !== null){
              // 実況勢いを表示
              document.getElementById('ikioi').textContent = '実況勢い: ' + data['ikioi'];
            }

            // 5秒後に実行(コメント遅延分)
            wait(5).done(function(){
  
              if (data['data'] != null && data['data'][0]){ //data['data'] があれば(nullでなければ)

                // コメントを無制限に表示 がオンの場合は全て流す
                // オフの場合は一度に最大8個のみ
                if (document.getElementsByClassName('dplayer-danunlimit-setting-input')[0].checked){
                  var length = data['data'].length;
                } else {
                  if (data['data'].length > 8){
                    var length = 8;
                  } else {
                    var length = data['data'].length;
                  }
                }
  
                for (i = 0; i < length; i++){
  
                  // 代入する際にきちんと文字型とか数値型とかに変換してないとうまく代入できない
                  danmaku['text'] = data['data'][i][4].toString();
                  danmaku['color'] =　data['data'][i][2].toString();
  
                  if (danmaku['text'] !== ''){ // 空でないなら
  
                    // 表示タイプを解析
                    if (data['data'][i][1] == 0){
                      danmaku['type'] = 0;
                    } else if (data['data'][i][1] == 1){
                      danmaku['type'] = 1;
                    } else if (data['data'][i][1] == 2){
                      danmaku['type'] = 2;
                    }
  
                    // console.log('For:' + i + ' DrawComment:' + danmaku['text'] + ' Color:' + danmaku['color'] + ' Type:' + danmaku['type']);
  
                    // 分と秒を計算
                    var now = new Date();
                    var hour = now.getHours(); // 時
                    var min = now.getMinutes(); // 分
                    var sec = now.getSeconds(); // 秒
                    if (hour < 10) {
                      hour = "0" + hour;
                    }
                    if (min < 10) {
                      min = "0" + min;
                    }
                    if (sec < 10) {
                      sec = "0" + sec;
                    }
                    var time = hour + ':' + min + ':' + sec;

                    // コメントをウインドウに出す
                    // 768px 以上のみ
                    if (document.body.clientWidth > 768){
                      document.getElementById('comment-draw-box').insertAdjacentHTML('beforeend', '<tr><td class="time" align="center">' + time + '</td><td class="comment">' + danmaku['text'] +'</td></tr>');
                    }

                    // コメント描画
                    dp.danmaku.draw(danmaku);
                
                  }
                }

                // コメント欄を下にアニメーション
                // 768px 以上のみ
                if (document.body.clientWidth > 768){
                  $('#comment-draw-box').velocity('scroll', { container: $("#comment-draw-box"), duration: 0.5, offset: $('#comment-draw-box')[0].scrollHeight});
                }
              }
            });
          }
        });
        return status;
      }()),500);
  
  });
