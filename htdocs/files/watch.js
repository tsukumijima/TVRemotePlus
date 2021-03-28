
// データを取得して引数にあわせてソートする関数
function sortFileinfo(json, sortnum, flg = 'normal'){

    $.ajax({
        url: '/files/' + json + '.json',
        dataType: 'json',
        cache: false,
    }).done(function(data) {

        // 透明にする
        $('#search-list').css('opacity', 0);

        // 中身を空にしてリセットする
        if ($('#search-info').html().indexOf('更新中') === -1) $('#search-info').empty();

        if (flg != 'more') $('#search-list').empty();

        // 一旦もっと見るを消す
        $('#search-more-box').remove();

        // 録画ファイル情報リスト
        var fileinfo = data['data'];

        // キーワード検索
        var text = $('#search-find-form').val();
        if (text !== undefined && text !== ''){ // 検索キーワードがあるなら
            var fileinfo = $.grep(fileinfo,
                function(a, b) {
                    // 正規表現
                    regexp = new RegExp('.*' + text + '.*', 'ig');
                    // 配列にフィルターをかける
                    return a.title.match(regexp);
                }
            );
            $('#search-info').html(fileinfo.length + '件ヒットしました。').hide().delay(200).velocity('fadeIn', 500);
        }

        // console.log(fileinfo);

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

        var length = $('.search-file-box').length + Number(settings['list_view_number'] || 30);
        // console.log(length)
        // 全体の配列数より表示する動画数の方が大きくなったら
        if (fileinfo.length < length){
            length = fileinfo.length;
        }

        // ファイルが1件以上あれば
        if (fileinfo.length > 0){

            for (var i = $('.search-file-box').length; i < length; i++){

                download = 
                    `<a class="search-file-download" href="/api/stream?file=` + encodeURIComponent(fileinfo[i]['file']) + `" target="blank" download="` + fileinfo[i]['title_raw'] + '.' + fileinfo[i]['pathinfo']['extension'] + `">
                        <i class="fas fa-download"></i>
                    </a>`;

                encode = 
                    `<div class="search-file-encode">
                        <i class="fas fa-film"></i>
                    </div>`;

                html += 
                    `<div class="search-file-box">
                        <div class="search-file-thumb">
                            <img class="search-file-thumb-img" src="/files/thumb/` + fileinfo[i]['thumb'] + `">
                            <div class="search-file-ext ` + fileinfo[i]['pathinfo']['extension'] + `">` + fileinfo[i]['pathinfo']['extension'].toUpperCase() + `</div>
                                ` + (fileinfo[i]['pathinfo']['extension'].toLowerCase() == 'ts' ? encode : download) + `
                            </div>
                            <div class="search-file-content">
                                <div class="search-file-path">` + fileinfo[i]['file'] + `</div>
                                <div class="start_timestamp">` + fileinfo[i]['start_timestamp'] + `</div>
                                <div class="end_timestamp">` + fileinfo[i]['end_timestamp'] + `</div>
                                <div class="search-file-title">` + fileinfo[i]['title'] + `</div>
                                <div class="search-file-info">
                                    <span class="search-file-channel">` + fileinfo[i]['channel'] + `</span>
                                    <span class="search-file-date">` + fileinfo[i]['date'] + `</span>
                                    <span class="search-file-time">` + fileinfo[i]['start'] + ` ～ ` + fileinfo[i]['end'] + ` (` + fileinfo[i]['duration'] + `分)</span>
                                </div>
                                <div class="search-file-description">
                                    ` + fileinfo[i]['info'] + `
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            // まだ表示しきれてないのがあるなら
            if (fileinfo.length > length){
                // もっと見る
                html += 
                    `<div id="search-more-box">
                        <i class="fas fa-angle-down"></i>
                        <span>もっと見る</span>
                    </div>`;
            }

            // 1つずつだと遅すぎるため一気に出す
            $('#search-list').append(html).hide().delay(200).velocity('fadeIn', 500);

            // 検索キーワードがあるなら上までスクロール
            if (flg == 'search'){
                $('html, body').velocity('scroll', { duration: 700, offset: (settings['vertical_navmenu'] ? 0 : -54) });
            }

        // 1件も見つからなかった場合
        } else {
            $('#search-info').html('<span class="error-text">キーワードに一致する録画番組が見つかりませんでした…</span>').hide().delay(200).velocity('fadeIn', 500);
        }

    }).fail(function(fileinfo) {

        // 中身を空にしてリセットする
        if (flg != 'more') $('#search-list').empty();

        // もっと見るを消す
        $('#search-more-box').remove();

        // エラー吐く
        if (sortnum == 5){
            $('#search-info').html('再生履歴がありません。録画番組を再生するとここに履歴が表示されます。').hide().delay(200).velocity('fadeIn', 500);
        } else {
            $('#search-info').html('録画リストがありません。<br>右上の︙メニュー →「リストを更新」から作成してください。').hide().delay(200).velocity('fadeIn', 500);
        }

    });
}

$(function(){

    // カバーを隠す
    $('#cover').removeClass('open');

    // 最初に表示させる
    sortFileinfo('fileinfo', 1);

    // 最初は収めておく
    if ($(window).width() <= 760){
        $('#search-find-link-box').hide();
    }

    // リストを手動で更新
    $('#list-update').click(function(event){ 	
        toastr.info('リストを更新しています…');
        $.ajax({
            url: '/api/listupdate?manual',
            dataType: 'json',
            cache: false,
        }).done(function(data) {
            if (data['status'] == 'success') {
                $('#rec-new').addClass('search-find-selected');
                $('#rec-old').removeClass('search-find-selected');
                $('#name-up').removeClass('search-find-selected');
                $('#name-down').removeClass('search-find-selected');
                $('#play-history').removeClass('search-find-selected');
                $('#search-info').empty();
                sortFileinfo('fileinfo', 1);
                toastr.success('リストを更新しました。');
            }
        }).fail(function(data) {
            toastr.error('リストの更新に失敗しました…');
        });
    });

    // リストをリセット
    $('#list-reset').click(function(event){
        $.ajax({
            url: '/api/listupdate?list_reset',
            dataType: 'json',
            cache: false,
        }).done(function(data) {
            sortFileinfo('fileinfo', 1);
            toastr.success('リストをリセットしました。');
        }).fail(function(data) {
            toastr.error('リストのリセットに失敗しました…');
        });
    });

    // 再生履歴をリセット
    $('#history-reset').click(function(event){
        $.ajax({
            url: '/api/listupdate?history_reset',
            dataType: 'json',
            cache: false,
        }).done(function(data) {
            sortFileinfo('fileinfo', 1);
            toastr.success('再生履歴をリセットしました。');
        }).fail(function(data) {
            toastr.error('再生履歴のリセットに失敗しました…');
        });
    });

    // ファイル検索メニュー開閉
    $('#search-find-toggle').click(function(event){
        $('#search-find-toggle i').toggleClass('fa-caret-down');
        $('#search-find-toggle i').toggleClass('fa-caret-up');
        $('#search-find-link-box').velocity($('#search-find-link-box').is(':visible') ? 'slideUp' : 'slideDown', { duration: 300, easing: 'ease-in-out', });
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
        sortFileinfo('fileinfo', 1, 'search');
    });

    // Enterで検索
    $('#search-find-form').keydown(function(event){
        if (event.which == 13){
            $('#rec-new').addClass('search-find-selected');
            $('#rec-old').removeClass('search-find-selected');
            $('#name-up').removeClass('search-find-selected');
            $('#name-down').removeClass('search-find-selected');
            $('#play-history').removeClass('search-find-selected');
            sortFileinfo('fileinfo', 1, 'search');
        }
    });

    // もっと見る
    $('body').on('click','#search-more-box',function(){
        // モード確認
        if ($('#rec-new').hasClass('search-find-selected')){
            sortFileinfo('fileinfo', 1, 'more');
        } else if ($('#rec-old').hasClass('search-find-selected')) {
            sortFileinfo('fileinfo', 2, 'more');
        } else if ($('#name-up').hasClass('search-find-selected')) {
            sortFileinfo('fileinfo', 3, 'more');
        } else if ($('#name-down').hasClass('search-find-selected')) {
            sortFileinfo('fileinfo', 4, 'more');
        } else if ($('#play-history').hasClass('search-find-selected')) {
            sortFileinfo('history', 5, 'more');
        }
    });

    // ファイルがクリックされた際に視聴ウインドウ(？)を出す
    $('body').on('click','.search-file-box',function(){

        // 怒涛のDOM追加
        var $elem = $(this);
        $('#search-stream-title').html($elem.find('.search-file-title').html());
        $('#search-stream-info').text($elem.find('.search-file-date').text() + ' ' + $elem.find('.search-file-time').text());
        $('#stream-filepath').val($elem.find('.search-file-path').text());
        $('#stream-filetitle').val($elem.find('.search-file-title').html());
        $('#stream-fileinfo').val($elem.find('.search-file-description').html());
        $('#stream-fileext').val($elem.find('.search-file-ext').text().toLowerCase());
        $('#stream-filechannel').val($elem.find('.search-file-channel').text());
        $('#stream-filetime').val($elem.find('.search-file-date').text() + ' ' + $elem.find('.search-file-time').text());
        $('#stream-start_timestamp').val($elem.find('.start_timestamp').text());
        $('#stream-end_timestamp').val($elem.find('.end_timestamp').text());
        $('#nav-close').toggleClass('open');
        $('#search-stream-box').toggleClass('open');
        $('html').toggleClass('open');

        // MP4・MKVの場合
        // この辺クソコード、保守もうむりしんどい
        if (($('#stream-fileext').val() == 'mp4' || $('#stream-fileext').val() == 'mkv') && $('option[name=quality_default]').text() != 'デフォルト (Original)'){
            changeToProgressiveEncoder();
            // プログレッシブオプションを追加
            $('option[name=quality_default]').after('<option name="Original" value="Original">Original (元画質)</option>');
            $('option[name=encoder_default]').after('<option name="Progressive" value="Progressive">Progressive (プログレッシブダウンロード)</option>');
            // デフォルトオプションの値と表示を変更
            $('option[name=quality_default]').val('Original');
            $('option[name=quality_default]').text('デフォルト (Original)');
        } else {
            changeToNormalEncoder();
            // プログレッシブオプションを削除
            $('option[name=Original]').remove();
            $('option[name=Progressive]').remove();
            // デフォルトオプションの値と表示を戻す
            $('option[name=quality_default]').val($('option[name=quality_default]').data('value'));
            $('option[name=quality_default]').text($('option[name=quality_default]').data('text'));
        }

        // ワンクリックでストリーム開始する場合
        if (settings['onclick_stream']){
            $('#search-stream-box').hide();
            $('.bluebutton').click();
        }

    });

    // プログレッシブとそれ以外の切り替え周り
    $('select[name=quality]').change(function(event){
        // console.log('changed')
        if ($(this).val() == 'Original'){
            // console.log('Original')
            changeToProgressiveEncoder();
        } else {
            // console.log('Original以外')
            changeToNormalEncoder();
        }
    });


    function changeToProgressiveEncoder(){
        // プログレッシブ以外を無効化
        $('option[name=Progressive]').prop('disabled', false);
        $('option[name=ffmpeg]').prop('disabled', true);
        $('option[name=QSVEncC]').prop('disabled', true);
        $('option[name=NVEncC]').prop('disabled', true);
        $('option[name=VCEEncC]').prop('disabled', true);
        // デフォルトオプションの値と表示を変更
        $('option[name=encoder_default]').val('Progressive');
        $('option[name=encoder_default]').text('デフォルト (Progressive)');
        // デフォルトオプションを既定で選択する
        $('option[name=encoder_default]').prop('selected', true);
    }

    function changeToNormalEncoder(){
        // プログレッシブ以外を無効化
        $('option[name=Progressive]').prop('disabled', true);
        $('option[name=ffmpeg]').prop('disabled', false);
        $('option[name=QSVEncC]').prop('disabled', false);
        $('option[name=NVEncC]').prop('disabled', false);
        $('option[name=VCEEncC]').prop('disabled', false);
        // デフォルトオプションの値と表示を戻す
        $('option[name=encoder_default]').val($('option[name=encoder_default]').data('value'));
        $('option[name=encoder_default]').text($('option[name=encoder_default]').data('text'));
        // デフォルトオプションを既定で選択する
        $('option[name=encoder_default]').prop('selected', true);
    }


    // サムネイルクリック時
    $('body').on('click', '.search-file-thumb', function(event){
        if ($(window).width() <= 1024){
            event.stopPropagation()
            $(this).find('.search-file-encode').toggleClass('open');
            $(this).find('.search-file-download').toggleClass('open');
        }
    });

    // ダウンロードボタン
    $('body').on('click', '.search-file-download', function(event){
        event.stopPropagation();
    });

    // エンコードボタン
    $('body').on('click', '.search-file-encode', function(event){
        event.stopPropagation();
    });

    // 再生開始
    $('.bluebutton').click(function(){
        $('.bluebutton').addClass('disabled');
    });

    // キャンセル
    $('.redbutton').click(function(event){
        $('#nav-close').removeClass('open');
        $('#search-stream-box').removeClass('open');
        $('html').removeClass('open');
    });

    // ***** リスト表示 / 通常表示 *****

    $('body').on('click', '#list-view', function(){
        // Cookieに書き込み
        settings['list_view'] = true;
        var json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
        // 再表示
        sortFileinfo('fileinfo', 1, 'search');
        setTimeout(function(){
            $('#search-list').addClass('list');
            $('#list-view span').text('通常表示に切り替え');
            $('#list-view i').removeClass('fa-list').addClass('fa-th-list');
            $('#list-view').attr('aria-label', '録画番組を通常通り表示します');
            $('#list-view').attr('id', 'normal-view');
            toastr.success('リスト表示に切り替えました。');
        }, 300);
    });

    $('body').on('click', '#normal-view', function(){
        // Cookieに書き込み
        settings['list_view'] = false;
        var json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
        // 再表示
        sortFileinfo('fileinfo', 1, 'search');
        setTimeout(function(){
            $('#search-list').removeClass('list');
            $('#normal-view span').text('リスト表示に切り替え');
            $('#normal-view i').removeClass('fa-th-list').addClass('fa-list');
            $('#normal-view').attr('aria-label', '録画番組を細いリストで表示します');
            $('#normal-view').attr('id', 'list-view');
            toastr.success('通常表示に切り替えました。');
        }, 300);
    });

});