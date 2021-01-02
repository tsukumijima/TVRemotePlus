
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

        // 設定を保存
        settings['twitter_show'] = $('#twitter_show').prop('checked');
        settings['comment_show'] = $('#comment_show').prop('checked');
        settings['dark_theme'] = $('#dark_theme').prop('checked');
        settings['subchannel_show'] = $('#subchannel_show').prop('checked');
        settings['list_view'] = $('#list_view').prop('checked');
        settings['logo_show'] = $('#logo_show').prop('checked');
        settings['comment_size'] = $('#comment_size').val();
        settings['comment_delay'] = $('#comment_delay').val();
        settings['comment_file_delay'] = $('#comment_file_delay').val();
        settings['comment_list_performance'] = $('#comment_list_performance').val();
        settings['list_view_number'] = $('#list_view_number').val();
        settings['onclick_stream'] = $('#onclick_stream').prop('checked');
        settings['player_floating'] = $('#player_floating').prop('checked');

        // ダークモード切り替え
        if (settings['dark_theme']){
            $('html').addClass('dark');
        } else {
            $('html').removeClass('dark');
        }

        var json = JSON.stringify(settings);
        Cookies.set('settings', json, { expires: 365 });
        toastr.success('個人設定を保存しました。');
        setTimeout(function(){
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