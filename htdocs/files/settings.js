
// 個人設定を反映
$(window).on('load', () => {

    if (settings['twitter_show']) {
        $('#twitter_show').prop('checked', true);
    } else {
        $('#twitter_show').prop('checked', false);
    }
    if (settings['comment_show']) {
        $('#comment_show').prop('checked', true);
    } else {
        $('#comment_show').prop('checked', false);
    }

    // コメントフィルターの値を読み込み
    const comment_filter = JSON.parse(localStorage.getItem('tvrp-comment-filter') || '[]');
    // ; で連結してフォームに表示
    $('#comment_filter').val(comment_filter.join(';'));

});

$(function(){

    $('#setting-user').submit((event) => {

        event.preventDefault();
        $('.bluebutton').attr('disabled', true);

        // 設定を保存
        settings['twitter_show'] = $('#twitter_show').prop('checked');
        settings['comment_show'] = $('#comment_show').prop('checked');
        settings['dark_theme'] = $('#dark_theme').prop('checked');
        settings['subchannel_show'] = $('#subchannel_show').prop('checked');
        settings['list_view'] = $('#list_view').prop('checked');
        settings['logo_show'] = $('#logo_show').prop('checked');
        settings['vertical_navmenu'] = $('#vertical_navmenu').prop('checked');
        settings['comment_size'] = $('#comment_size').val();
        settings['comment_delay'] = $('#comment_delay').val();
        settings['comment_file_delay'] = $('#comment_file_delay').val();
        settings['comment_list_performance'] = $('#comment_list_performance').val();
        settings['quality_user_default'] = $('#quality_user_default').val();
        settings['list_view_number'] = $('#list_view_number').val();
        settings['onclick_stream'] = $('#onclick_stream').prop('checked');
        settings['player_floating'] = $('#player_floating').prop('checked');

        // ダークモード切り替え
        if (settings['dark_theme']) {
            $('html').addClass('dark-theme');
        } else {
            $('html').removeClass('dark-theme');
        }

        // ナビゲーションメニュー切り替え
        if (settings['vertical_navmenu']) {
            $('html').addClass('vertical-navmenu');
        } else {
            $('html').removeClass('vertical-navmenu');
        }

        // コメントフィルターを保存
        // 参考: https://ezolab.blog.fc2.com/blog-entry-41.html
        if (!String.prototype.trimAny) {
            String.prototype.trimAny = function(any) {
              return this.replace(new RegExp("^" + any + "+|" + any + "+$", "g"),'');
            };
          }
        // 両端から ; を削除した上で、; でキーワードを分割して配列にする
        const comment_filter = $('#comment_filter').val().trimAny(';').split(';');
        // localStorage に保存
        localStorage.setItem('tvrp-comment-filter', JSON.stringify(comment_filter));

        // 個人設定を Cookie に保存
        const json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
        toastr.success('個人設定を保存しました。');
        setTimeout(() => {
            $('.bluebutton').attr('disabled', false);
        }, 200);

    });

    $('#setting-env').submit(function(event){

        event.preventDefault();
        $('#save').addClass('disabled');
        $('.redbutton').attr('disabled', true);

        // フォーム送信
        $.ajax({
            url: '/settings/',
            type: 'post',
            data: $('#setting-env').serialize(),
            cache: false,
        }).done(function(data) {
            toastr.success('環境設定を保存しました。');
            setTimeout(function(){
                $('.redbutton').attr('disabled', false);
                $('#save').removeClass('disabled');
            }, 200);
        });

    });

    // 保存ボタン
    $(window).scroll(function() {

        // スクロール位置を取得
        var topPos = $(this).scrollTop();

        if ($('#setting-env').length === 1){
            // 表示・非表示
            if (topPos > $('#setting-env').offset().top &&
                ($('#setting-other').length === 0 || topPos + $(window).height() < $('#setting-other').offset().top)) {
                $('#save').css('opacity', '1');
                $('#save').css('visibility', 'visible');
            } else {
                $('#save').css('opacity', '0');
                $('#save').css('visibility', 'hidden');
            }
        }

    });

    // 保存する
    $('#save').click(function(){
        $('#setting-env .redbutton').click();
    });

});