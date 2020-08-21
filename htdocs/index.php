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
<?php	if ($ini[$stream]['state'] !== 'File'){ ?>
    live: true,
<?php	} //括弧終了 ?>
    loop: true,
    lang: 'ja-jp',
    theme: '#007cff',
    // 読み込むm3u8を指定する
    video: {
<?php	if ($ini[$stream]['state'] == 'File' and $ini[$stream]['fileext'] != 'ts' and $ini[$stream]['encoder'] == 'Progressive'){ ?>
      url: '/api/stream/<?php echo $stream; ?>?_=<?php echo time(); ?>',
      type: 'normal'
<?php	} else { ?>
      url: '/stream/stream<?php echo $stream; ?>.m3u8',
      type: 'hls'
<?php	} //括弧終了 ?>
    },
    // 読み込むdanmaku(コメント)
    danmaku: {
      id: 'TVRemotePlus',
      user: 'TVRemotePlus',
      api: '/api/jikkyo/<?php echo $stream; ?>',
      bottom: '10%',
      height: settings['comment_size'],
      unlimited: false
    },
    subtitle: {
      type: 'webvtt',
    },
  });

  document.getElementsByClassName('dplayer-video-current')[0].addEventListener('loadeddata', function(){
    dp.subtitle.toggle();
    dp.subtitle.toggle();
  }, false);

<?php	if ($ini[$stream]['state'] == 'File') { ?>
  dp.seek(1);
<?php	} //括弧終了 ?>

          </script>
        </div>

<?php	if (isSettingsItem('twitter_show', true, true)){ ?>
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
              <textarea id="tweet" name="tweet" placeholder="ツイート (Ctrl + Enterで送信)"></textarea>
              <span id="tweet-num">140</span>
            </div>
            <div id="tweet-etc">
              <div id="tweet-picture" class="tweet-etc-item" aria-label="キャプチャ (Alt+1)" data-balloon-pos="up">
                <img src="/files/picture.svg">
              </div>
              <div id="tweet-picture-comment" class="tweet-etc-item" aria-label="コメント付きでキャプチャ (Alt+2)" data-balloon-pos="up">
                <img src="/files/comment.svg">
              </div>
              <div id="tweet-reset" class="tweet-etc-item" aria-label="リセット (Alt+3)" data-balloon-pos="up">
                <img src="/files/reset.svg">
              </div>
            </div>
            <button id="tweet-submit" class="disabled" disabled>ツイート</button>
          </form>
          <div class="line"></div>
        </div>
<?php	} //括弧終了 ?>
        <div id="tweet-close"></div>
      </div>

<?php	if (isSettingsItem('comment_show', true, true)){ ?>
      <div id="sidebar">
        <div id="comment-box-wrap">
          <div id="comment-box-header">
          <i class="fas fa-comment-alt"></i><b>　コメント一覧</b>
          </div>
          <div id="comment-box">
            <table id="comment-draw-box-header">
              <tr><th id="comment-time" class="time">時間</th><th id="comment" class="comment">コメント</th></tr>
            </table>
            <table id="comment-draw-box"></table>
          </div>
          <div id="comment-scroll">
            <i class="fas fa-arrow-down"></i>
          </div>
        </div>
      </div>
<?php	} //括弧終了 ?>
    </div>

    <div id="description">
      <div id="epg-box">
        <div id="epg">
<?php	if ($ini[$stream]['state'] == 'File') { ?>
          <div id="epg-title"><?php echo $ini[$stream]['filetitle']; ?></div>
<?php	} else if ($ini[$stream]['state'] == 'Offline') { ?>
          <div id="epg-title">配信休止中…</div>
<?php	} else { ?>
          <div id="epg-title">取得中…</div>
<?php	} //括弧終了 ?>
          <div id="reload-box">
            <a id="reload" aria-label="再生が止まった時に押してください" data-balloon-pos="up">
              <span id="clock"><?php echo $clock; ?></span>
              <i class="fas fa-redo-alt"></i>
            </a>
          </div>
<?php	if ($ini[$stream]['state'] == 'ONAir') { ?>
          <div id="epg-next">
            Next >>> <span id="epg-next-title">取得中…</span> <span id="epg-next-starttime"></span><span id="epg-next-to"></span><span id="epg-next-endtime"></span>
          </div>
<?php	} //括弧終了 ?>
<?php	if ($ini[$stream]['state'] == 'File') { ?>
          <div id="epg-info"><?php echo $ini[$stream]['fileinfo']; ?></div>
<?php	} else { ?>
          <div id="epg-info"></div>
<?php	} //括弧終了 ?>
        </div>

        <div id="epg-subinfo">
<?php	if ($ini[$stream]['state'] == 'ONAir'){ ?>
          <span id="state" style="color: #007cff;" value="ONAir">● ON Air</span>
          <span id="status"></span>
          <div id="epg-chinfo"> 
<?php		if ($ini[$stream]['channel'] < 55){ ?>
            <span id="epg-channel">Ch: <?php echo sprintf('%03d', str_replace('_', '', $ini[$stream]['channel'])).' '.$channel; ?></span>
<?php		} else { //括弧終了 ?>
            <span id="epg-channel">Ch: <?php echo sprintf('%03d', $ini[$stream]['channel']).' '.$channel; ?></span>
<?php		} //括弧終了 ?>
            <span id="epg-time">
              <span id="epg-starttime"></span> <span id="epg-to"></span> <span id="epg-endtime"></span>
            </span>
          </div>
<?php	} else if ($ini[$stream]['state'] == 'File') { ?>
          <span id="status"></span>
          <div id="epg-chinfo"> 
            <span id="state" style="color: #4ECDC4;" value="File">● File</span>
            <span id="epg-channel"><?php echo $ini[$stream]['filechannel']; ?></span>
          </div>
          <span id="epg-time"><?php echo $ini[$stream]['filetime']; ?></span>
<?php	} else { ?>
          <span id="state" style="color: gray;" value="Offline">● Offline</span>
          <span id="status"></span>
          <div id="epg-chinfo">
            <span id="epg-time">
              <span id="epg-starttime"></span> <span id="epg-to"></span> <span id="epg-endtime"></span>
            </span>
          </div>
<?php	} //括弧終了 ?>

          <div id="watch">
            <span id="watching">1人が視聴中</span>
<?php	if ($ini[$stream]['state'] == 'ONAir'){ ?>
            <span id="ikioi">実況勢い: -</span>
<?php	} //括弧終了 ?>
          </div>
        </div>

        <div class="progressbar">
          <div id="progress" class="progress"></div>
        </div>
      </div>

      <div id="stream-view-box">
<?php	foreach ($ini as $key => $value){ // 地デジchの数だけ繰り返す ?>
<?php		if ($value['state'] != 'Offline' || $key == '1'){ ?>
        <div class="stream-view stream-view-<?php echo $key; ?>" data-num="<?php echo $key; ?>" data-url="/<?php echo $key; ?>/">
          <div class="stream-box">
            <div class="stream-number-title">Stream</div><div class="stream-number"><?php echo $key; ?></div>
            <div class="stream-stop <?php echo $value['state'] == 'Offline' ? 'disabled' : ''; ?>">
              <i class="stream-stop-icon far fa-stop-circle"></i>
            </div>
<?php			if ($value['state'] == 'ONAir'){ ?>
            <div class="stream-state blue">● ON Air</div>
<?php			} else if ($value['state'] == 'File') { ?>
            <div class="stream-state green">● File</div>
<?php			} else { ?>
            <div class="stream-state">● Offline</div>
<?php			} //括弧終了 ?>
            <div class="stream-info">
              <div class="stream-title"><?php echo $value['state'] == 'Offline' ? '配信休止中…' : '取得中…'; ?></div>
              <div class="stream-channel">
                <?php echo $value['state'] == 'File' ? $value['filechannel'] : ($value['state'] == 'ONAir' ? @$ch[strval($value['channel'])] : '') ?>
              </div>
              <div class="stream-description"></div>
            </div>
          </div>
        </div>
<?php		} //括弧終了 ?>
<?php	} //括弧終了 ?>
      </div>

      <div id="information">
<?php	if (empty($BonDriver_dll) and empty($ch)){ // エラーを吐く ?>
        <div class="error">
          BonDriver とチャンネル設定ファイルが見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	} else if (empty($BonDriver_dll)){ ?>
        <div class="error">
          BonDriver が見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	} else if (empty($ch)){ ?>
        <div class="error">
          チャンネル設定ファイルが見つからないため、ライブ配信を開始できません。<br>
          ファイルが BonDriver フォルダに正しく配置されているか、確認してください。<br>
        </div>
<?php	} //括弧終了

		if (empty($EDCB_http_url) or !@file_get_contents($EDCB_http_url.'api/EnumEventInfo', false, $ssl_context)){ // EMWUI ?>
        <div class="error">
          EEDCB Material WebUI のある URL が正しく設定されていないため、番組情報が表示できません。<br>
          設定ページの「EDCB Material WebUI (EMWUI) のある URL」が正しく設定されているかどうか、確認してください。<br>
        </div>
<?php	} //括弧終了 ?>

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
<?php	foreach ($ch_T as $i => $value){ // 地デジchの数だけ繰り返す ?>
<?php		// リモコン番号が被ってるチャンネル
			// もうちょっとスマートに実装したかったけどうまくいかなかったのでハードコード
			$subchcount = substr($i, -1);
			if ($i > 60){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 60).$subchcount.'-3';
			} else if ($i > 40){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 40).$subchcount.'-2';
			} else if ($i > 20){
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i) - 20).$subchcount.'-1';
			// 通常
			} else {
				$ch_T_channel = 'Ch: '.sprintf('%02d', intval($i)).$subchcount;
			}
?>
              <div id="ch<?php echo str_replace('.', '_', $i); ?>" class="broadcast-wrap" data-ch="<?php echo $i; ?>"
                    data-channel="<?php echo $ch_T_channel; ?>" data-name="<?php echo $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?php echo getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?php echo $ch_T_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?php echo $value; ?></div>
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
<?php	} //括弧終了 ?>
            </nav>

            <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_S as $i => $value){ // BSchの数だけ繰り返す ?>
<?php		$ch_S_channel = 'Ch: '.sprintf('%03d', $i); ?>
              <div id="ch<?php echo $i; ?>" class="broadcast-wrap" data-ch="<?php echo $i; ?>"
                    data-channel="<?php echo $ch_S_channel; ?>" data-name="<?php echo $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?php echo getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?php echo $ch_S_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?php echo $value; ?></div>
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
<?php	} //括弧終了 ?>
            </nav>

            <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_CS as $i => $value){ // CSchの数だけ繰り返す ?>
<?php		$ch_CS_channel = 'Ch: '.sprintf('%03d', $i); ?>
              <div id="ch<?php echo $i; ?>" class="broadcast-wrap" data-ch="<?php echo $i; ?>"
                    data-channel="<?php echo $ch_CS_channel; ?>" data-name="<?php echo $value; ?>">

                <div class="broadcast">
                  <div class="broadcast-img material-icons">tv
                    <div class="broadcast-logo" style="background-image: url(<?php echo getLogoURL($i); ?>);"></div>
                  </div>
                  <div class="broadcast-content">
                    <div class="broadcast-channel-box">
                      <div class="broadcast-channel"><?php echo $ch_CS_channel; ?></div>
                      <div class="broadcast-name-box">
                        <div class="broadcast-name"><?php echo $value; ?></div>
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
<?php	} //括弧終了 ?>
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
<?php	if ($stream_current_live == 'true'){ ?>
              <option value="1"<?php if ($stream == '1') echo ' selected'; ?>>Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
              <option value="2"<?php if ($stream == '2') echo ' selected'; ?>>Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
              <option value="3"<?php if ($stream == '3') echo ' selected'; ?>>Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
              <option value="4"<?php if ($stream == '4') echo ' selected'; ?>>Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		if (isStreamActive($ini, 1) and isStreamActive($ini, 2) and isStreamActive($ini, 3) and isStreamActive($ini, 4)){ ?>
<?php			for ($i = 5; isStreamActive($ini, ($i - 1)); $i++){ ?>
              <option value="<?php echo $i; ?>"<?php if ($stream == $i) echo ' selected'; ?>>Stream <?php echo $i; ?> - <?php echo getFormattedState($ini, $i, true); ?></option>
<?php			} //括弧終了 ?>
<?php		} //括弧終了 ?>
<?php	} else { ?>
<?php		if (!isStreamActive($ini, 1)){ ?>
                <option value="1" selected>Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		} else if (!isStreamActive($ini, 2)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2" selected>Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		} else if (!isStreamActive($ini, 3)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3" selected>Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		} else if (!isStreamActive($ini, 4)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4" selected>Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		} //括弧終了 ?>
<?php		if (isStreamActive($ini, 1) and isStreamActive($ini, 2) and isStreamActive($ini, 3) and isStreamActive($ini, 4)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php			for ($i = 5; isStreamActive($ini, ($i - 1)); $i++){ ?>
                <option value="<?php echo $i; ?>"<?php if (!isStreamActive($ini, $i)) echo ' selected'; ?>>Stream <?php echo $i; ?> - <?php echo getFormattedState($ini, $i, true); ?></option>
<?php			} //括弧終了 ?>
<?php		} //括弧終了 ?>
<?php	} //括弧終了 ?>
            </select>
          </div>
        </div>

        <div class="setchannel form">
          <span>動画の画質：</span>
          <div class="select-wrap">
            <select name="quality">
              <option value="<?php echo $quality_default; ?>">デフォルト (<?php echo $quality_default; ?>)</option>
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
              <option value="<?php echo $encoder_default; ?>">デフォルト (<?php echo $encoder_default; ?>)</option>
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
<?php		if ($subtitle_default == 'true'){ ?>
              <option value="<?php echo $subtitle_default; ?>">デフォルト (字幕オン)</option>
<?php		} else { ?>
              <option value="<?php echo $subtitle_default; ?>">デフォルト (字幕オフ)</option>
<?php		} //括弧終了 ?>
              <option value="true">字幕オン</option>
              <option value="false">字幕オフ</option>
            </select>
          </div>
        </div>

        <div class="setBonDriver form">
          <span>使用 BonDriver：</span>
          <div id="broadcast-BonDriver-T" class="select-wrap">
            <select name="BonDriver">
<?php		if (!empty($BonDriver_default_T)){ ?>
              <option value="default">デフォルトの BonDriver</option>
<?php		} //括弧終了 ?>
<?php		foreach ($BonDriver_dll_T as $i => $value){ //chの数だけ繰り返す ?>
              <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
<?php		} //括弧終了 ?>
            </select>
          </div>
          <div id="broadcast-BonDriver-S" class="select-wrap">
            <select name="BonDriver">
<?php		if (!empty($BonDriver_default_S)){ ?>
              <option value="default">デフォルトの BonDriver</option>
<?php		} //括弧終了 ?>
<?php		foreach ($BonDriver_dll_S as $i => $value){ //chの数だけ繰り返す ?>
              <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
<?php		} //括弧終了 ?>
            </select>
          </div>
        </div>

        <div id="button-box" class="broadcast-button-box">
<?php		if (!empty($BonDriver_dll) and !empty($ch)){ ?>
          <button class="bluebutton" type="submit"><i class="fas fa-play"></i>ストリーム開始</button>
<?php		} else {?>
          <button class="bluebutton" type="submit" disabled><i class="fas fa-play"></i>ストリーム開始</button>
<?php		} //括弧終了 ?>
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
          <button class="redbutton" type="button"><i class="fas fa-times"></i>キャンセル</button>
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
              <div class="hotkey-list-name">ストリームを同期する（ライブ配信時のみ）</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">(＊)</div> + <div class="hotkey-list-key alphabet">L</div>
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
              <div class="hotkey-list-name">ツイート入力フォームにフォーカスする / フォーカスを外す</div>
              <div class="hotkey-list-key-box">
                <div class="hotkey-list-key">Tab</div>
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
    <?php echo $site_title.' '.$version; ?>

  </section>
</body>

</html>