<?php

	// レスポンスをバッファに貯める
	ob_start();

	// ヘッダー読み込み
	require_once ('../modules/header.php');

	echo '    <pre id="debug">';

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
		= initBonChannel($BonDriver_dir);

	// ストリーム番号を取得
	$stream = getStreamNumber($_SERVER['REQUEST_URI']);

	// ストリーム番号が指定されていなかった or ストリーム番号が存在しなかったらストリーム1にリダイレクト
	if (!getStreamNumber($_SERVER['REQUEST_URI'], true) or !isset($ini[$stream])){
		// トップページにリダイレクト
		if ($reverse_proxy){
			header('Location: '.$reverse_proxy_url.'1/');
    } else {
			header('Location: '.$site_url.'1/');
		}
		exit;
	}

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	// basic 認証設定を実行
	basicAuth($basicauth, $basicauth_user, $basicauth_password);

	// ONAirのみ
	if ($ini[$stream]['state'] == 'ONAir'){
		$channel = @$ch[strval($ini[$stream]['channel'])];
	}

	// stream.m3u8がない場合
	if (!file_exists($base_dir.'htdocs/stream/stream'.$stream.'.m3u8')){
		if ($silent == 'true'){
			copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
    } else {
			copy($offline_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
		}
	}

	// 時計
	$clock = date("Y/m/d H:i:s");

	echo '</pre>';

	// 溜めてあった出力を解放しフラッシュする
	ob_end_flush();
	ob_flush();
	flush();

?>

    <div id="content-wrap">
      <div id="content">
        <div id="dplayer-box">
          <div id="dplayer"></div>
          <script id="dplayer-script">

    const dp = new DPlayer({
        container: document.getElementById('dplayer'),
        volume: 1.0,
        autoplay: true,
        screenshot: true,
        airplay: false,
        apiBackend: newNicoJKAPIBackend('<?= $ini[$stream]['state']; ?>'),
        live: <?= ($ini[$stream]['state'] !== 'File' ? 'true' : 'false'); ?>,
        loop: true,
        lang: 'ja-jp',
        theme: '#007cff',
        // 読み込む m3u8 を指定する
        video: {
<?php	if ($ini[$stream]['state'] == 'File' and $ini[$stream]['fileext'] != 'ts' and $ini[$stream]['encoder'] == 'Progressive'): ?>
            url: '/api/stream/<?= $stream; ?>?_=<?= time(); ?>',
            type: 'normal'
<?php	else: ?>
            url: '/stream/stream<?= $stream; ?>.m3u8',
            type: 'hls'
<?php	endif; ?>
        },
        // コメント設定
        danmaku: {
            id: 'TVRemotePlus',
            user: 'TVRemotePlus',
            api: '',
            bottom: '10%',
            height: settings['comment_size'],
            unlimited: false
        },
        pluginOptions: {
            // hls-b24.js
            hls: {
              liveSyncDurationCount: 1
            },
            // aribb24.js
            aribb24: {
                forceStrokeColor: 'black',
                normalFont: '"Windows TV MaruGothic","Yu Gothic",sans-serif',
                gaijiFont: '"Windows TV MaruGothic","Yu Gothic",sans-serif',
                drcsReplacement: true
            }
        },
        subtitle: {
            type: 'webvtt',
        },
    });

<?php	if ($ini[$stream]['state'] == 'File'): ?>
    // ファイル再生でエンコード中、再生時間が最新のセグメントの範囲にシークされてしまうのを防ぐ
    // 動画の読み込みが終わった後に（👈重要）currentTime を 0（秒）に設定する
    dp.video.addEventListener('loadedmetadata', () => {
        // Safari のネイティブ HLS プレイヤーでは再生開始前に 0 秒にシークすることができない
        // 0.000001 秒にすることで再生開始前でもシークできるようになる
        dp.video.currentTime = 0.000001;
        console.log(dp.video.currentTime)
    });
<?php	endif; ?>

          </script>
        </div>

<?php	if (isSettingsItem('twitter_show', true, true)): ?>
        <div id="tweet-account-box">
          <div id="tweet-account">
            <img id="tweet-account-icon" src="/files/account_default.jpg">
            <div id="tweet-account-info">
              <a id="tweet-account-name" target="_blank">ログインしていません</a>
              <div id="tweet-account-id">Not Login</div>
            </div>
          </div>
        </div>

        <div id="tweet-box">
          <div id="tweet-info">
            <div id="tweet-title">
              <i class="fab fa-twitter"></i>
            </div>
            <div id="tweet-status"></div>
          </div>
          <form id="tweet-form" action="javascript:void(0)" autocomplete="off">
            <div id="tweet-main">
              <input id="tweet-hashtag" name="hashtag" type="text" placeholder="#ハッシュタグ">
              <textarea id="tweet" name="tweet" placeholder="ツイート"></textarea>
              <svg id="tweet-capture-num-img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <g>
                  <path d="M19.75 2H4.25C3.01 2 2 3.01 2 4.25v15.5C2 20.99 3.01 22 4.25 22h15.5c1.24 0 2.25-1.01 2.25-2.25V4.25C22 3.01 20.99 2 19.75 2zM4.25 3.5h15.5c.413 0 .75.337.75.75v9.676l-3.858-3.858c-.14-.14-.33-.22-.53-.22h-.003c-.2 0-.393.08-.532.224l-4.317 4.384-1.813-1.806c-.14-.14-.33-.22-.53-.22-.193-.03-.395.08-.535.227L3.5 17.642V4.25c0-.413.337-.75.75-.75zm-.744 16.28l5.418-5.534 6.282 6.254H4.25c-.402 0-.727-.322-.744-.72zm16.244.72h-2.42l-5.007-4.987 3.792-3.85 4.385 4.384v3.703c0 .413-.337.75-.75.75z"></path>
                  <circle cx="8.868" cy="8.309" r="1.542"></circle>
                </g>
              </svg>
              <span id="tweet-capture-num">0/4</span>
              <span id="tweet-num-img" class="fab fa-twitter"></span>
              <span id="tweet-num">140</span>
              <div id="tweet-capture-box"></div>
            </div>
            <div id="tweet-etc">
              <div id="tweet-picture" class="tweet-etc-item" aria-label="キャプチャ (Alt+1)" data-balloon-pos="up">
                <img src="/files/picture.svg">
              </div>
              <div id="tweet-picture-comment" class="tweet-etc-item" aria-label="コメント付きでキャプチャ (Alt+2)" data-balloon-pos="up">
                <img src="/files/comment.svg">
              </div>
              <div id="tweet-capture-list" class="tweet-etc-item" aria-label="キャプチャ画像リスト (Alt+Q)" data-balloon-pos="up">
                <img src="/files/list.svg">
              </div>
              <div id="tweet-reset" class="tweet-etc-item" aria-label="リセット (Alt+3)" data-balloon-pos="up">
                <img src="/files/reset.svg">
              </div>
            </div>
            <button id="tweet-submit" class="disabled" disabled>ツイート</button>
          </form>
          <div class="line"></div>
        </div>
<?php	endif; ?>
        <div id="tweet-close"></div>
      </div>

<?php	if (isSettingsItem('comment_show', true, true)): ?>
      <div id="sidebar">
        <div id="comment-box">
          <div id="comment-box-header">
            <i class="fas fa-comment-alt"></i><b>　コメントリスト</b>
          </div>
          <table id="comment-draw-box-header">
            <tr><th id="comment-time" class="time">時間</th><th id="comment" class="comment">コメント</th></tr>
          </table>
          <div id="comment-draw-wrap">
            <table id="comment-draw-box">
              <tbody></tbody>
            </table>
          </div>
          <div id="comment-scroll">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
              <path fill="currentColor" d="M176 32h96c13.3 0 24 10.7 24 24v200h103.8c21.4 0 32.1 25.8 17 41L241 473c-9.4 9.4-24.6 9.4-34 0L31.3 297c-15.1-15.1-4.4-41 17-41H152V56c0-13.3 10.7-24 24-24z" class=""></path>
            </svg>
          </div>
        </div>
      </div>
<?php endif; ?>
    </div>

    <div id="description">
      <div id="epg-box">
        <div id="epg">
<?php	if ($ini[$stream]['state'] == 'File'): ?>
          <div id="epg-title"><?= $ini[$stream]['filetitle']; ?></div>
<?php	elseif ($ini[$stream]['state'] == 'Offline'): ?>
          <div id="epg-title">配信休止中…</div>
<?php	else: ?>
          <div id="epg-title">取得中…</div>
<?php	endif; ?>
          <div id="reload-box">
            <a id="reload" aria-label="再生が止まった時に押してください" data-balloon-pos="up">
              <span id="clock"><?= $clock; ?></span>
              <i class="fas fa-redo-alt"></i>
            </a>
          </div>
<?php	if ($ini[$stream]['state'] == 'ONAir'): ?>
          <div id="epg-next">
            Next >>> <span id="epg-next-title">取得中…</span> <span id="epg-next-starttime"></span><span id="epg-next-to"></span><span id="epg-next-endtime"></span>
          </div>
<?php	endif; ?>
<?php	if ($ini[$stream]['state'] == 'File'): ?>
          <div id="epg-info"><?= $ini[$stream]['fileinfo']; ?></div>
<?php	else: ?>
          <div id="epg-info"></div>
<?php	endif; ?>
        </div>

        <div id="epg-subinfo">
<?php	if ($ini[$stream]['state'] == 'ONAir'): ?>
          <span id="state" style="color: #007cff;" value="ONAir">● ON Air</span>
          <span id="status"></span>
          <div id="epg-chinfo"> 
<?php		if ($ini[$stream]['channel'] < 55): ?>
            <span id="epg-channel">Ch: <?= sprintf('%03d', str_replace('_', '', $ini[$stream]['channel'])).' '.$channel; ?></span>
<?php		else: ?>
            <span id="epg-channel">Ch: <?= sprintf('%03d', $ini[$stream]['channel']).' '.$channel; ?></span>
<?php		endif; ?>
            <span id="epg-time">
              <span id="epg-starttime"></span> <span id="epg-to"></span> <span id="epg-endtime"></span>
            </span>
          </div>
<?php	elseif ($ini[$stream]['state'] == 'File'): ?>
          <span id="status"></span>
          <div id="epg-chinfo"> 
            <span id="state" style="color: #4ECDC4;" value="File">● File</span>
            <span id="epg-channel"><?= $ini[$stream]['filechannel']; ?></span>
          </div>
          <span id="epg-time"><?= $ini[$stream]['filetime']; ?></span>
<?php	else: ?>
          <span id="state" style="color: gray;" value="Offline">● Offline</span>
          <span id="status"></span>
          <div id="epg-chinfo">
            <span id="epg-time">
              <span id="epg-starttime"></span> <span id="epg-to"></span> <span id="epg-endtime"></span>
            </span>
          </div>
<?php	endif; ?>

          <div id="watch">
            <span id="watching">1人が視聴中</span>
<?php	if ($ini[$stream]['state'] == 'ONAir'): ?>
            <span id="ikioi">実況勢い: -</span>
<?php	endif; ?>
<?php	if ($ini[$stream]['state'] == 'ONAir' or $ini[$stream]['state'] == 'File'): ?>
            <span id="comment-counter">コメント数: -</span>
<?php	endif; ?>
          </div>
        </div>

        <div class="progressbar">
          <div id="progress" class="progress"></div>
        </div>
      </div>

      <div id="stream-view-box">
<?php	foreach ($ini as $key => $value): // 地デジchの数だけ繰り返す ?>
<?php		if ($value['state'] != 'Offline' || $key == '1'): ?>
        <div class="stream-view stream-view-<?= $key; ?>" data-num="<?= $key; ?>" data-url="/<?= $key; ?>/">
          <div class="stream-box">
            <div class="stream-number-title">Stream</div><div class="stream-number"><?= $key; ?></div>
            <div class="stream-stop <?= $value['state'] == 'Offline' ? 'disabled' : ''; ?>">
              <i class="stream-stop-icon far fa-stop-circle"></i>
            </div>
<?php			if ($value['state'] == 'ONAir'): ?>
            <div class="stream-state blue">● ON Air</div>
<?php			elseif ($value['state'] == 'File'): ?>
            <div class="stream-state green">● File</div>
<?php			else: ?>
            <div class="stream-state">● Offline</div>
<?php			endif; ?>
            <div class="stream-info">
              <div class="stream-title"><?= $value['state'] == 'Offline' ? '配信休止中…' : '取得中…'; ?></div>
              <div class="stream-channel">
                <?= $value['state'] == 'File' ? $value['filechannel'] : ($value['state'] == 'ONAir' ? @$ch[strval($value['channel'])] : '') ?>
              </div>
              <div class="stream-description"></div>
            </div>
          </div>
        </div>
<?php		endif; ?>
<?php	endforeach; ?>
      </div>

      <div id="information">
<?php	if (empty($BonDriver_dll) and empty($ch)): // エラーを吐く ?>
        <div class="error">
          BonDriver とチャンネル設定ファイルが見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	elseif (empty($BonDriver_dll)): ?>
        <div class="error">
          BonDriver が見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	elseif (empty($ch)): ?>
        <div class="error">
          チャンネル設定ファイルが見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	endif;

		if (empty($EDCB_http_url) or !@file_get_contents($EDCB_http_url.'api/EnumEventInfo', false, $ssl_context)): // EMWUI ?>
        <div class="error">
          EEDCB Material WebUI のある URL が正しく設定されていないため、番組情報が表示できません。<br>
          設定ページの「EDCB Material WebUI (EMWUI) のある URL」が正しく設定されているかどうか、確認してください。<br>
        </div>
<?php	endif; ?>

        <div id="broadcast-tab-box" class="swiper-container">
          <div id="broadcast-tab" class="swiper-wrapper">
            <div class="broadcast-button swiper-slide">地デジ</div>
            <div class="broadcast-button swiper-slide">BS</div>
            <div class="broadcast-button swiper-slide">CS</div>
          </div>
        </div>

        <div id="broadcast-box" class="swiper-container">
          <div class="swiper-wrapper">

            <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_T as $i => $value): // 地デジchの数だけ繰り返す ?>
<?php		// リモコン番号が被ってるチャンネル
			// もうちょっとスマートに実装したかったけどうまくいかなかったのでハードコード
			$subchcount = substr($i, -1);
			if ($i > 60){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 60).$subchcount.'-3';
      		} elseif ($i > 40){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 40).$subchcount.'-2';
			} elseif ($i > 20){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 20).$subchcount.'-1';
			// 通常
      		} else {
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i)).$subchcount;
			}
?>
              <div id="ch<?= str_replace('.', '_', $i); ?>" class="broadcast-wrap" data-ch="<?= $i; ?>"
                    data-channel="<?= $ch_T_channel; ?>" data-name="<?= $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?= getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?= $ch_T_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?= $value; ?></div>
                        <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi"> - </span></div>
                      </div>
                    </div>
                    <div class="broadcast-title">
                      <span class="broadcast-start">00:00</span>
                      <span class="broadcast-to">～</span>
                      <span class="broadcast-end">00:00</span>
                      <span class="broadcast-title-id">取得中です…</span>
                    </div>
                    <div class="broadcast-next">
                      <span">00:00</span>
                      <span>～</span>
                      <span>00:00</span>
                      <span>取得中です…</span>
                    </div>
                  </div>
                </div>
                
                <div class="progressbar">
                  <div class="progress"></div>
                </div>

              </div>
<?php	endforeach; ?>
            </nav>

            <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_S as $i => $value): // BSchの数だけ繰り返す ?>
<?php		$ch_S_channel = 'Ch: '.sprintf('%03d', $i); ?>
              <div id="ch<?= $i; ?>" class="broadcast-wrap" data-ch="<?= $i; ?>"
                    data-channel="<?= $ch_S_channel; ?>" data-name="<?= $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?= getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?= $ch_S_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?= $value; ?></div>
                        <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi"> - </span></div>
                      </div>
                    </div>
                    <div class="broadcast-title">
                      <span class="broadcast-start">00:00</span>
                      <span class="broadcast-to">～</span>
                      <span class="broadcast-end">00:00</span>
                      <span class="broadcast-title-id">取得中です…</span>
                    </div>
                    <div class="broadcast-next">
                      <span">00:00</span>
                      <span>～</span>
                      <span>00:00</span>
                      <span>取得中です…</span>
                    </div>
                  </div>
                </div>
                
                <div class="progressbar">
                  <div class="progress"></div>
                </div>

              </div>
<?php	endforeach; ?>
            </nav>

            <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_CS as $i => $value): // CSchの数だけ繰り返す ?>
<?php		$ch_CS_channel = 'Ch: '.sprintf('%03d', $i); ?>
              <div id="ch<?= $i; ?>" class="broadcast-wrap" data-ch="<?= $i; ?>"
                    data-channel="<?= $ch_CS_channel; ?>" data-name="<?= $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?= getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?= $ch_CS_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?= $value; ?></div>
                        <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi"> - </span></div>
                      </div>
                    </div>
                    <div class="broadcast-title">
                      <span class="broadcast-start">00:00</span>
                      <span class="broadcast-to">～</span>
                      <span class="broadcast-end">00:00</span>
                      <span class="broadcast-title-id">取得中です…</span>
                    </div>
                    <div class="broadcast-next">
                      <span">00:00</span>
                      <span>～</span>
                      <span>00:00</span>
                      <span>取得中です…</span>
                    </div>
                  </div>
                </div>
                
                <div class="progressbar">
                  <div class="progress"></div>
                </div>

              </div>
<?php	endforeach; ?>
            </nav>

          </div>
        </div>
      </div>
    </div>

    <div id="broadcast-stream-box">
      <div id="broadcast-stream-title"></div>
      <div id="broadcast-stream-info"></div>
      <form id="setting-form" action="/settings/" method="post">
        <input type="hidden" name="state" value="ONAir">
        <input id="broadcast-stream-channel" type="hidden" name="channel" value="">

        <div class="setstream form">
          <span>ストリーム：</span>
          <div class="select-wrap">
            <select name="stream">
<?php	if ($stream_current_live == 'true'): ?>
              <option value="1"<?php if ($stream == '1') echo ' selected'; ?>>Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
              <option value="2"<?php if ($stream == '2') echo ' selected'; ?>>Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
              <option value="3"<?php if ($stream == '3') echo ' selected'; ?>>Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
              <option value="4"<?php if ($stream == '4') echo ' selected'; ?>>Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php		if (isStreamActive($ini, 2) and isStreamActive($ini, 3) and isStreamActive($ini, 4)): ?>
<?php			for ($i = 5; isStreamActive($ini, ($i - 1)); $i++): ?>
              <option value="<?= $i; ?>"<?php if ($stream == $i) echo ' selected'; ?>>Stream <?= $i; ?> - <?= getFormattedState($ini, $i, true); ?></option>
<?php			endfor; ?>
<?php		endif; ?>
<?php	else: ?>
<?php		if (!isStreamActive($ini, 1)): ?>
                <option value="1" selected>Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php		elseif (!isStreamActive($ini, 2)): ?>
                <option value="1">Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
                <option value="2" selected>Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php		elseif (!isStreamActive($ini, 3)): ?>
                <option value="1">Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
                <option value="3" selected>Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php		elseif (!isStreamActive($ini, 4)): ?>
                <option value="1">Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
                <option value="4" selected>Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php		endif; ?>
<?php		if (isStreamActive($ini, 2) and isStreamActive($ini, 3) and isStreamActive($ini, 4)): ?>
                <option value="1">Stream 1 - <?= getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?= getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?= getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?= getFormattedState($ini, 4, true); ?></option>
<?php			for ($i = 5; isStreamActive($ini, ($i - 1)); $i++): ?>
                <option value="<?= $i; ?>"<?php if (!isStreamActive($ini, $i)) echo ' selected'; ?>>Stream <?= $i; ?> - <?= getFormattedState($ini, $i, true); ?></option>
<?php			endfor; ?>
<?php		endif; ?>
<?php	endif; ?>
            </select>
          </div>
        </div>

        <div class="setchannel form">
          <span>動画の画質：</span>
          <div class="select-wrap">
            <select name="quality">
              <option value="<?= $quality_default; ?>">デフォルト (<?= $quality_default; ?>)</option>
              <option value="1080p-high">1080p-high (1920×1080)</option>
              <option value="1080p">1080p (1440×1080)</option>
              <option value="810p">810p (1440×810)</option>
              <option value="720p">720p (1280×720)</option>
              <option value="540p">540p (960×540)</option>
              <option value="360p">360p (640×360)</option>
              <option value="240p">240p (426×240)</option>
              <option value="144p">144p (256×144)</option>
            </select>
          </div>
        </div>
        
        <div class="setencoder form">
          <span>エンコード：</span>
          <div class="select-wrap">
            <select name="encoder">
              <option value="<?= $encoder_default; ?>">デフォルト (<?= $encoder_default; ?>)</option>
              <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
              <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
              <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
              <option value="VCEEncC">VCEEncC (ハードウェアエンコーダー)</option>
            </select>
          </div>
        </div>

        <div class="setsubtitle form">
          <span>字幕データ：</span>
          <div class="select-wrap">
            <select name="subtitle">
<?php		if ($subtitle_default == 'true'): ?>
              <option value="<?= $subtitle_default; ?>">デフォルト (字幕オン)</option>
<?php		else: ?>
              <option value="<?= $subtitle_default; ?>">デフォルト (字幕オフ)</option>
<?php		endif; ?>
              <option value="true">字幕オン</option>
              <option value="false">字幕オフ</option>
            </select>
          </div>
        </div>

        <div class="setBonDriver form">
          <span>使用 BonDriver：</span>
          <div id="broadcast-BonDriver-T" class="select-wrap">
            <select name="BonDriver">
<?php		if (!empty($BonDriver_default_T)): ?>
              <option value="default">デフォルトの BonDriver</option>
<?php		endif; ?>
<?php		foreach ($BonDriver_dll_T as $i => $value): //chの数だけ繰り返す ?>
              <option value="<?= $value; ?>"><?= $value; ?></option>
<?php		endforeach; ?>
            </select>
          </div>
          <div id="broadcast-BonDriver-S" class="select-wrap">
            <select name="BonDriver">
<?php		if (!empty($BonDriver_default_S)): ?>
              <option value="default">デフォルトの BonDriver</option>
<?php		endif; ?>
<?php		foreach ($BonDriver_dll_S as $i => $value): //chの数だけ繰り返す ?>
              <option value="<?= $value; ?>"><?= $value; ?></option>
<?php		endforeach; ?>
            </select>
          </div>
        </div>

        <div id="button-box" class="broadcast-button-box">
<?php		if (!empty($BonDriver_dll) and !empty($ch)): ?>
          <button class="bluebutton" type="submit"><i class="fas fa-play"></i>ストリーム開始</button>
<?php		else: ?>
          <button class="bluebutton" type="submit" disabled><i class="fas fa-play"></i>ストリーム開始</button>
<?php		endif; ?>
          <button class="redbutton" type="button"><i class="fas fa-times"></i>キャンセル</button>
        </div>

      </form>
    </div>

    <div id="chromecast-box">
      <div id="chromecast-wrap">
        <span id="chromecast-title-box">
        <svg style="width: 21px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="chromecast" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-chromecast fa-w-16">
          <path fill="currentColor" d="M447.83 64H64a42.72 42.72 0 0 0-42.72 42.72v63.92H64v-63.92h383.83v298.56H298.64V448H448a42.72 42.72 0 0 0 42.72-42.72V106.72A42.72 42.72 0 0 0 448 64zM21.28 383.58v63.92h63.91a63.91 63.91 0 0 0-63.91-63.92zm0-85.28V341a106.63 106.63 0 0 1 106.64 106.66v.34h42.72a149.19 149.19 0 0 0-149-149.36h-.33zm0-85.27v42.72c106-.1 192 85.75 192.08 191.75v.5h42.72c-.46-129.46-105.34-234.27-234.8-234.64z" class="">
          </path>
        </svg>
          <span id="chromecast-title">キャストするデバイス</span>
        </span>
        <div id="chromecast-device-box">
        </div>
        <div id="button-box" class="broadcast-button-box">
          <button id="cast-scan" class="bluebutton" type="button" aria-label="キャストするデバイスをスキャンします" data-balloon-pos="up">
            <i class="fas fa-sync-alt"></i><span class="menu-link-href">デバイスをスキャン</span>
          </button>
          <button class="redbutton" type="button">
            <i class="fas fa-times"></i>キャンセル
          </button>
        </div>
      </div>
    </div>

    <div id="ljicrop-box">
      <div id="ljicrop-wrap">
        <div class="ljicrop-head-box title">
          <i class="fas fa-tv"></i>
          <span class="ljicrop-head">Ｌ字画面のクロップ</span>
        </div>
        <div class="ljicrop-head-box">
          <i class="fas fa-search-plus" style="font-size: 13.5px;"></i>
          <span class="ljicrop-head">拡大率 : <span id="ljicrop-magnify-percentage">100%<span></span>
        </div>
        <div class="ljicrop-range-box">
          <span class="ljicrop-range-start">100%</span>
          <input id="ljicrop-magnify" class="custom-range" name="ljicrop_magnify" type="range" min="100" max="200" value="100">
          <span class="ljicrop-range-end">200%</span>
        </div>
        <div class="ljicrop-head-box">
          <i class="fas fa-arrows-alt-h" style="font-size: 13.5px;"></i>
          <span class="ljicrop-head">X 座標 : <span id="ljicrop-coordinatex-percentage">0%<span></span>
        </div>
        <div class="ljicrop-range-box">
          <span class="ljicrop-range-start" style="padding-left: 18px;">0%</span>
          <input id="ljicrop-coordinatex" class="custom-range" name="ljicrop_coordinateX" type="range" min="0" max="100" value="0">
          <span class="ljicrop-range-end">100%</span>
        </div>
        <div class="ljicrop-head-box">
          <i class="fas fa-arrows-alt-v" style="font-size: 13.5px;"></i>
          <span class="ljicrop-head">Y 座標 : <span id="ljicrop-coordinatey-percentage">0%<span></span>
        </div>
        <div class="ljicrop-range-box">
          <span class="ljicrop-range-start" style="padding-left: 18px;">0%</span>
          <input id="ljicrop-coordinatey" class="custom-range" name="ljicrop_coordinateY" type="range" min="0" max="100" value="0">
          <span class="ljicrop-range-end">100%</span>
        </div>
        <div class="ljicrop-head-box">
          <i class="fas fa-crosshairs" style="font-size: 13.5px;"></i>
          <span class="ljicrop-head">拡大起点</span>
        </div>
        <div id="ljicrop-point-box">
          <label class="ljicrop-point">
            <div class="custom-control custom-radio">
              <input type="radio" class="custom-control-input"  name="ljicrop_type" value="upperright" checked>
              <div class="custom-control-label"></div>
            </div>
            右上
          </label>
          <label class="ljicrop-point">
            <div class="custom-control custom-radio">
              <input type="radio" class="custom-control-input"  name="ljicrop_type" value="lowerright" checked>
              <div class="custom-control-label"></div>
            </div>
            右下
          </label>
          <label class="ljicrop-point">
            <div class="custom-control custom-radio">
              <input type="radio" class="custom-control-input"  name="ljicrop_type" value="upperleft" checked>
              <div class="custom-control-label"></div>
            </div>
            左上
          </label>
          <label class="ljicrop-point">
            <div class="custom-control custom-radio">
              <input type="radio" class="custom-control-input"  name="ljicrop_type" value="lowerleft" checked>
              <div class="custom-control-label"></div>
            </div>
            左下
          </label>
        </div>
        <div id="button-box" class="broadcast-button-box">
          <button class="redbutton" type="button"><i class="fas fa-times"></i>閉じる</button>
        </div>
      </div>
    </div>

    <div id="hotkey-box">
      <div id="hotkey-wrap">
        <div class="hotkey-head-box title">
          <i class="fas fa-keyboard"></i>
          <span class="hotkey-head">キーボードショートカット一覧</span>
          <span class="hotkey-head-sub">(＊) … ツイート入力フォーム以外にフォーカスした状態</span>
        </div>
        <div id="hotkey-list-box">
          <div class="hotkey-list-wrap">
            <div class="hotkey-head-box">
              <i class="fas fa-play" style="font-size: 13.5px;"></i>
              <span class="hotkey-head">再生</span>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">再生 / 一時停止の切り替え</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Space</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">5秒巻き戻し</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key"><i class="fas fa-arrow-left"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">5秒早送り</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key"><i class="fas fa-arrow-right"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">15秒巻き戻し</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Ctrl (or Command)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-left"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">15秒早送り</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Ctrl (or Command)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-right"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">30秒巻き戻し</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Shift</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-left"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">30秒早送り</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Shift</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-right"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">60秒巻き戻し</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-left"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">60秒早送り</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-right"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">音量を10%上げる</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(プレイヤーにフォーカス)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-up"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">音量を10%下げる</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(プレイヤーにフォーカス)</div> + <div class="hotkey-list-key"><i class="fas fa-arrow-down"></i></div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">字幕の表示 / 非表示の切り替え</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">S</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">コメントの表示 / 非表示の切り替え</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">D</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">ストリームを同期する（ライブ配信時のみ）</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">L</div>
              </div>
            </div>
          </div>
          <div class="hotkey-list-wrap">
            <div class="hotkey-head-box">
              <i class="fas fa-home"></i>
              <span class="hotkey-head">全般</span>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">全画面のオン / オフの切り替え</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">F</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">ブラウザ全画面のオン / オフの切り替え</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">W</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">ピクチャーインピクチャーのオン / オフの切り替え（対応ブラウザのみ）</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">P</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">コメント入力フォームを表示してフォーカスする</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">C</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">キーボードショートカットの一覧を表示する</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">?</div>
              </div>
            </div>
            <div class="hotkey-head-box">
              <i class="fab fa-twitter"></i>
              <span class="hotkey-head">ツイート</span>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">
                ツイート入力フォームにフォーカスする / フォーカスを外す<br>
                プレイヤーにフォーカスする / フォーカスを外す（キャプチャ画像リスト表示時のみ）
              </div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Tab</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">キャプチャ画像リストを表示する</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key">Q</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">ストリームをキャプチャする</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key">1</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">ストリームをコメント付きでキャプチャする</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key">2</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">キャプチャとツイートをリセットする</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Alt (or Option)</div> + <div class="hotkey-list-key">3</div>
              </div>
            </div>
            <div class="hotkey-list">
              <div class="hotkey-list-name">クリップボードの画像を取り込む</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(ツイート入力フォームにフォーカス)</div> + <div class="hotkey-list-key">Ctrl (or Command)</div> + <div class="hotkey-list-key alphabet">V</div>
              </div>
            </div>
          </div>
        </div>
        <div id="button-box" class="broadcast-button-box">
          <button class="redbutton" type="button"><i class="fas fa-times"></i>閉じる</button>
        </div>
      </div>
    </div>

    <div id="scroll">
      <i class="fas fa-arrow-up"></i>
    </div>

  </section>

  <section id="footer">
    <?= $site_title.' '.$version; ?>

  </section>
</body>

</html>