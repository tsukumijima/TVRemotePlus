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
          url: "api/jkapi.php?res=" + res,
          dataType: "json",
          cache: false,
          success: function(data) {
  
            var danmaku = {}; 
            res = data["last_res"];
            last_res = data["res"];
  
            // console.log('―― GetComment postres:' + last_res + ' getres:' + res + ' ――');
            // console.log(data['danmaku']);
  
            if (data["ikioi"] !== null){
              // 実況勢いを表示
              $("#ikioi").text('実況勢い: ' + data["ikioi"]);
            }

            // 5秒後に実行(コメント遅延分)
            wait(5).done(function(){
  
              if (data["data"] != null && data["data"][0]){ //data["data"] があれば(nullでなければ)
  
                for (i = 0; i <= data["data"].length-1; i++){
  
                  // 代入する際にきちんと文字型とか数値型とかに変換してないとうまく代入できない
                  danmaku['text'] = data["data"][i][4].toString();
                  danmaku['color'] =　data["data"][i][2].toString();
  
                  if (danmaku['text'] !== ''){ // 空でないなら
  
                    // 表示タイプを解析
                    if (data["data"][i][1] == 0){
                      danmaku['type'] = 0;
                    } else if (data["data"][i][1] == 1){
                      danmaku['type'] = 1;
                    } else if (data["data"][i][1] == 2){
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
                      $('#comment-draw-box').append('<tr><td class="time">' + time + '</td><td class="comment">' + danmaku['text'] +'</td></tr>');
  
                      // コメント描画
                      dp.danmaku.draw(danmaku);
                  
                    }
                  }

                // アニメーション
                $('#comment-draw-box').animate({scrollTop:$('#comment-draw-box')[0].scrollHeight}, 0.5);
              }
            });
          }
        });
        return status;
      }()),500);
  
  });
