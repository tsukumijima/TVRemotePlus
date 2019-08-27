
  // データを取得して引数にあわせてソートする関数
  function sortFileinfo(json, sortnum, flg = true){

    $.ajax({
      url: '/files/' + json + '.json',
      dataType: 'json',
      cache: false,
      success: function(data) {

        // 中身を空にしてリセットする
        $('#search-info').empty();
        if (flg == true){
          $("#search-list").empty();
        }
        // 一旦もっと見るを消す
        $("#search-more-box").remove();

        // 録画ファイル情報リスト
        var fileinfo = data['data'];

        // キーワード検索
        var text = $('#search-find-form').val();
        if (text !== undefined && text !== ''){ // 検索キーワードがあるなら
          var fileinfo = $.grep(fileinfo,
            function(a, b) {
              // 正規表現
              regexp = new RegExp('.*' + text + '.*', 'g');
              // 配列にフィルターをかける
              return a.title.match(regexp);
            }
          );
          $('#search-info').html(fileinfo.length + '件ヒットしました。').hide().fadeIn(500);
        }

        //console.log(fileinfo);
  
        switch (sortnum){
          case 1:
            fileinfo.sort(function(a, b) {
              return (a.start_timestamp > b.start_timestamp) ? -1 : 1;
            });
            break;
          case 2:
            fileinfo.sort(function(a, b) {
              return (a.start_timestamp < b.start_timestamp) ? -1 : 1;
            });
            break;
          case 3:
            fileinfo.sort(function(a, b) {
              return (a.title < b.title) ? -1 : 1;
            });
            break;
          case 4:
            fileinfo.sort(function(a, b) {
              return (a.title > b.title) ? -1 : 1;
            });
            break;
          case 5:
            fileinfo.sort(function(a, b) {
              return (a.play > b.play) ? -1 : 1;
            });
            break;
        }
        
        // html
        var html = '';

        var length = $('.search-file-box').length + 30;
        // 全体の配列数より表示する動画数の方が大きくなったら
        if (fileinfo.length < length){
          length = fileinfo.length;
        }

        // ファイルが1件以上あれば
        if (fileinfo.length > 0){
        
          for (var i = $('.search-file-box').length; i < length; i++){
            html += 
              '<div class="search-file-box">' + "\n"
            + '  <img class="search-file-thumb" src="/files/thumb/' + fileinfo[i]['thumb'] + '">' + "\n"
            + '  <div class="search-file-content">' + "\n"
            + '    <div class="search-file-path">' + fileinfo[i]['file'] + '</div>' + "\n"
            + '    <div class="start_timestamp">' + fileinfo[i]['start_timestamp'] + '</div>' + "\n"
            + '    <div class="end_timestamp">' + fileinfo[i]['end_timestamp'] + '</div>' + "\n"
            + '    <div class="search-file-title">' + fileinfo[i]['title'] + '</div>' + "\n"
            + '    <div class="search-file-info">' + "\n"
            + '      <span class="search-file-channel">' + fileinfo[i]['channel'] + '</span>' + "\n"
            + '      <div class="search-file-time">'
            + '        <span>' + fileinfo[i]['date'] + '</span>' + "\n"
            + '        <span>' + fileinfo[i]['start'] + '～' + fileinfo[i]['end'] + '(' + fileinfo[i]['duration'] + '分)</span>' + "\n"
            + '      </div>' + "\n"
            + '    </div>' + "\n"
            + '    <div class="search-file-description">' + "\n"
            + '      ' + fileinfo[i]['info'] + "\n"
            + '    </div>' + "\n"
            + '  </div>' + "\n"
            + '</div>'; + "\n";
          }

          // まだ表示しきれてないのがあるなら
          if (fileinfo.length > length){
            // もっと見る
            html += 
              '<div id="search-more-box">' + "\n"
            + '  <i class="fas fa-angle-down"></i>' + "\n"
            + '  <span>もっと見る</span>' + "\n"
            + '</div>'; + "\n";
          }

          // 1つずつだと遅すぎるため一気に出す
          $('#search-list').append(html).hide().fadeIn(500);
          
          // 検索キーワードがあるなら上までスクロール
          if (text !== undefined && text !== '' && fileinfo.length == length){
            $('html, body').animate({ scrollTop: 0 }, 700);
          }

        // 1件も見つからなかった場合
        } else {
          $('#search-info').html('<span class="error-text">一致する録画ファイルが見つかりませんでした…</span>').hide().fadeIn(500);
        }

      },
      error: function(fileinfo) {
        // 中身を空にしてリセットする
        if (flg == true){
          $("#search-list").empty();
        }
        // もっと見るを消す
        $("#search-more-box").remove();
        // エラー吐く
        $('#search-info').html('録画ファイルリストがありません。右上の︙メニュー →「リストを更新」から作成してください。').hide().fadeIn(500);
      }
    });
  }

  $(function(){

    // 最初に表示させる
    sortFileinfo('fileinfo', 1);

    // 最初は収めておく
    if ($(window).width() <= 760){
      $('#search-find-link-box').hide();
    }

    // リストを更新
    $('#list-update').click(function(event){ 	
      toastr.info('リストを更新しています…');
      $('#menu-content').animate({height: 'toggle'}, 150);
      $('#menu-content').removeClass('open');
      $.ajax({
        url: "/api/searchfile.php",
        dataType: "json",
        cache: false,
        success: function(data) {
          $('#rec-new').addClass('search-find-selected');
          $('#rec-old').removeClass('search-find-selected');
          $('#name-up').removeClass('search-find-selected');
          $('#name-down').removeClass('search-find-selected');
          $('#play-history').removeClass('search-find-selected');
          sortFileinfo('fileinfo', 1);
          toastr.success('リストを更新しました。');
        }
      });
    });

    // リストをリセット
    $('#list-reset').click(function(event){ 	
      $('#menu-content').animate({height: 'toggle'}, 150);
      $('#menu-content').removeClass('open');
      $.ajax({
        url: "/api/searchfile.php?reset",
        dataType: "json",
        cache: false,
        success: function(data) {
          sortFileinfo('fileinfo', 1);
          toastr.success('リストをリセットしました。');
        }
      });
    });

    // ファイル検索メニュー開閉
    $('#search-find-toggle').click(function(event){
      $('#search-find-toggle').toggleClass('fa-caret-down');
      $('#search-find-toggle').toggleClass('fa-caret-up');
      $('#search-find-link-box').slideToggle(300);
    });

    // 並び替えを切り替え
    $('#rec-new,#rec-old,#name-up,#name-down,#play-history').click(function(event){
      $('#rec-new').removeClass('search-find-selected');
      $('#rec-old').removeClass('search-find-selected');
      $('#name-up').removeClass('search-find-selected');
      $('#name-down').removeClass('search-find-selected');
      $('#play-history').removeClass('search-find-selected');
      $(this).addClass('search-find-selected');

      switch ($(this).attr("id")){
        case 'rec-new':
          sortFileinfo('fileinfo', 1);
          break;
        case 'rec-old':
          sortFileinfo('fileinfo', 2);
          break;
        case 'name-up':
          sortFileinfo('fileinfo', 3);
          break;
        case 'name-down':
          sortFileinfo('fileinfo', 4);
          break;
        case 'play-history':
          sortFileinfo('history', 5);
          break;
      }
    });
    
    // 検索を実行
    $('#search-find-submit').click(function(event){
      $('#rec-new').addClass('search-find-selected');
      $('#rec-old').removeClass('search-find-selected');
      $('#name-up').removeClass('search-find-selected');
      $('#name-down').removeClass('search-find-selected');
      $('#play-history').removeClass('search-find-selected');
      sortFileinfo('fileinfo', 1);
    });

    // Enterで検索
    $('#search-find-form').keydown(function(event){
      if (event.which == 13){
        $('#rec-new').addClass('search-find-selected');
        $('#rec-old').removeClass('search-find-selected');
        $('#name-up').removeClass('search-find-selected');
        $('#name-down').removeClass('search-find-selected');
        $('#play-history').removeClass('search-find-selected');
        sortFileinfo('fileinfo', 1);
      }
    });

    // もっと見る
    $('body').on('click','#search-more-box',function(){
      // モード確認
      if ($('#rec-new').hasClass('search-find-selected')){
        sortFileinfo('fileinfo', 1, false);
      } else if ($('#rec-old').hasClass('search-find-selected')) {
        sortFileinfo('fileinfo', 2, false);
      } else if ($('#name-up').hasClass('search-find-selected')) {
        sortFileinfo('fileinfo', 3, false);
      } else if ($('#name-down').hasClass('search-find-selected')) {
        sortFileinfo('fileinfo', 4, false);
      } else if ($('#play-history').hasClass('search-find-selected')) {
        sortFileinfo('history', 5, false);
      }
    });

    // ファイルがクリックされた際に視聴ウインドウ(？)を出す
    $('body').on('click','.search-file-box',function(){
      $('#search-stream-title').html($(this).find('.search-file-title').html());
      $('#search-stream-info').text($(this).find('.search-file-time').text());
      $('#stream-filepath').val($(this).find('.search-file-path').text());
      $('#stream-filetitle').val($(this).find('.search-file-title').html());
      $('#stream-fileinfo').val($(this).find('.search-file-description').html());
      $('#stream-filechannel').val($(this).find('.search-file-channel').text());
      $('#stream-filetime').val($(this).find('.search-file-time').text());
      $('#stream-start_timestamp').val($(this).find('.start_timestamp').text());
      $('#stream-end_timestamp').val($(this).find('.end_timestamp').text());
      $('#nav-close').toggleClass('open');
      $('#search-stream-box').toggleClass('open');
      $('html').toggleClass('open');
      // ワンクリックでストリーム開始する場合
      if (settings['onclick_stream']){
        $('#search-stream-box').hide();
        $('.bluebutton').click();
      }
    });

    // キャンセル
    $('.redbutton').click(function(event){
      $('#nav-close').removeClass('open');
      $('#search-stream-box').removeClass('open');
      $('html').removeClass('open');
    });

  });