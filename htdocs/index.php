<?php
  
	// ヘッダー読み込み
	require_once ('../header.php');
  
	echo '    <pre id="debug">';
  
	// モジュール読み込み
	require_once ('../module.php');

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	// basic 認証設定を実行
	basicAuth($basicauth, $basicauth_user, $basicauth_password);

	// ONAirのみ
	if ($ini['state'] == "ONAir"){
		$channel = $ch[strval($ini["channel"])];
	}

	// stream.m3u8がない場合
	if (!file_exists($base_dir.'htdocs/stream/stream.m3u8')){
		copy($offline_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
	}

	// 時計
	$clock = date("Y/m/d H:i:s");
  
	echo '</pre>';

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
<?php	if ($ini['state'] !== "File"){ ?>
    live: true,
<?php	} //括弧終了 ?>
    loop: true,
    lang: 'ja-jp',
    theme: '#007cff',
    // 読み込むm3u8を指定する
    video: {
      url: '/stream/stream.m3u8',
      type: 'hls'
    },
    // 読み込むdanmaku(コメント)
    danmaku: {
      id: 'TVRemotePlus',
      user: 'TVRemotePlus',
      api: '/api/jkapi.php/',
      bottom: '40%',
      unlimited: true
    },
    subtitle: {
      type: 'webvtt',
    },
  });

  document.getElementsByClassName('dplayer-video-current')[0].addEventListener('loadeddata', function(){
    dp.subtitle.toggle();
    dp.subtitle.toggle();
  }, false);

<?php	if ($ini['state'] == "File") { ?>
  dp.seek(1);
<?php	} //括弧終了 ?>

          </script>
        </div>

        <div id="tweet-box">
          <div id="tweet-info">
            <div id="tweet-title"><i class="fab fa-twitter"></i></div>
            <div id="tweet-status"></div>
          </div>
          <form id="tweet-form" action="javascript:void(0)">
            <div id="tweet-main">
              <input id="tweet-hashtag" name="hashtag" type="text" placeholder="#ハッシュタグ" >
              <textarea id="tweet" name="tweet" placeholder="ツイート (Ctrl+Enterで送信)"></textarea>
            </div>
            <div id="tweet-etc">
              <div data-balloon="キャプチャ (Alt+1)" data-balloon-pos="up">
                <img id="tweet-picture" src="files/picture.svg">
              </div>
              <div data-balloon="コメント付きでキャプチャ (Alt+2)" data-balloon-pos="up">
                <img id="tweet-picture-comment" src="files/comment.svg">
              </div>
              <div data-balloon="リセット (Alt+3)" data-balloon-pos="up">
                <img id="tweet-reset" src="files/reset.svg">
              </div>
              <span id="tweet-num">140</span>
            </div>
            <button id="tweet-submit" class="disabled" disabled>ツイート</button>
          </form>
          <div class="line"></div>
        </div>
        <div id="tweet-close"></div>
      </div>

      <div id="sidebar">
        <div id="comment-box-wrap">
          <div id="comment-box-header">
          <i class="fas fa-comment-alt"></i><b>　コメント一覧</b>
          </div>
          <div id="comment-box">
            <table id="comment-draw-box-header">
              <tr><th id="comment-time" class="time">時間</th><th class="comment">コメント</th></tr>
            </table>
            <table id="comment-draw-box">
            </table>
          </div>
        </div>
      </div>
    </div>

      <div id="description">
        <div id="content-box">
          <h2 class="title">
<?php	if ($ini['state'] == 'File') { ?>
            <span id="program_name"><?php echo $ini['filetitle']; ?></span>
<?php	} else if ($ini['state'] == 'Offline') { ?>
            <span id="program_name">配信休止中…</span>
<?php	} else { ?>
            <span id="program_name">取得中…</span>
<?php	} //括弧終了 ?>
            <div class="subinfo">
              <a class="reload" href="" onClick="location.reload(true);">
                <span id="time"><?php echo $clock; ?></span>
                <i class="fas fa-redo-alt"></i>
              </a>
            </div>
<?php	if ($ini['state'] == "ONAir") { ?>
            <br id="title-br">
            <span id="next">
              Next >>> <span id="next_program_name">取得中…</span> <span id="next_starttime"></span><span id="next_to"></span><span id="next_endtime"></span>
            </span>
<?php	} //括弧終了 ?>
          </h2>
<?php	if ($ini['state'] == "File") { ?>
          <div id="program_info"><?php echo $ini['fileinfo']; ?></div>
<?php	} else { ?>
          <div id="program_info"></div>
<?php	} //括弧終了 ?>

          <h3 class="subtitle">
<?php	if ($ini['state'] == "ONAir"){ ?>
            <div class="chinfo"> 
              <span id="state" style="color: #007cff;" value="<?php echo $ini['state']; ?>">● ON Air</span>
              <span id="status"></span>
              <span id="channel"><?php echo $channel; ?></span>
            </div>
            <span class="livetime">
              <span id="starttime"></span><span id="to"></span><span id="endtime"></span>
            </span>
<?php	} else if ($ini['state'] == "Offline") { ?>
            <div class="chinfo">
              <span id="state" style="color: gray;" value="<?php echo $ini['state']; ?>">● Offline</span>
              <span id="status"></span>
            </div>
            <span class="livetime">
              <span id="starttime"></span><span id="to"></span><span id="endtime"></span>
            </span>
<?php	} else if ($ini['state'] == "File") { ?>
            <div class="chinfo"> 
              <span id="state" style="color: #4ECDC4;" value="<?php echo $ini['state']; ?>">● File</span>
              <span id="status"></span>
              <span id="channel"><?php echo $ini['filechannel']; ?></span>
            </div>
            <span class="livetime"><?php echo $ini['filetime']; ?></span>
<?php	} //括弧終了 ?>

            <span class="watch">
              <span id="watchnow"></span>
              <span id="ikioi"></span>
            </span>
          </h3>

          <div class="progressbar">
            <div id="progress" class="progress"></div>
          </div>

          <div class="line"></div>

          <div id="information">

<?php		if (empty($BonDriver_dll) and empty($ch)){ // エラーを吐く ?>
            <div class="error">
              BonDriverとチャンネル設定ファイルが見つからないため、ストリームを開始できません。<br>
              ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
            </div>
<?php		} else if (empty($BonDriver_dll)){ ?>
            <div class="error">
              BonDriverが見つからないため、ストリームを開始できません。<br>
              ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
            </div>
<?php		} else if (empty($ch)){ ?>
            <div class="error">
              チャンネル設定ファイルが見つからないため、ストリームを開始できません。<br>
              ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
            </div>
<?php		} //括弧終了 ?>
<?php		if (empty($EDCB_http_url) or !@file_get_contents($EDCB_http_url.'/EnumEventInfo')){ // EMWUI ?>
        <div class="error">
          EDCB Material WebUI の API がある URL が正しく設定されていないため、番組情報が表示できません。<br>
          config.php の「EDCB Material WebUI (EMWUI) の API がある URL」が正しく設定されているかどうか、確認してください。<br>
        </div>
<?php		} //括弧終了 ?>

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
                  <form class="broadcast-wrap" method="post">

                    <input type="hidden" name="state" value="ONAir">
                    <div class="broadcast-channel-id"><?php echo $i; ?></div>

                    <button type="button" class="broadcast">
                      <i class="broadcast-img material-icons">tv</i>
                      <div class="broadcast-content broadcast-ch<?php echo $i; ?>">
                        <div class="broadcast-channel-box">
                          <div class="broadcast-channel broadcast-channel-ch<?php echo $i; ?>">Ch: <?php echo sprintf('%02d', $i); ?></div>
                          <div class="broadcast-name-box">
                            <div class="broadcast-name broadcast-name-ch<?php echo $i; ?>"><?php echo $value; ?></div>
                            <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi-ch<?php echo $i; ?>"> - </span></div>
                          </div>
                        </div>
                        <div class="broadcast-title">
                          <span class="broadcast-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-title-id broadcast-title-ch<?php echo $i; ?>"></span>
                        </div>
                        <div class="broadcast-next">
                          <span class="broadcast-next-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-title-ch<?php echo $i; ?>"></span>
                        </div>
                      </div>
                    </button>
                    
                    <div class="progressbar">
                      <div class="progress progress-ch<?php echo $i; ?>"></div>
                    </div>

                  </form>
<?php	} //括弧終了 ?>
                </nav>

                <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_S as $i => $value){ // BSchの数だけ繰り返す ?>
                  <form class="broadcast-wrap" method="post">

                    <input type="hidden" name="state" value="ONAir">
                    <div class="broadcast-channel-id"><?php echo $i; ?></div>

                    <button type="button" class="broadcast">
                      <i class="broadcast-img material-icons">tv</i>
                      <div class="broadcast-content broadcast-ch<?php echo $i; ?>">
                        <div class="broadcast-channel-box">
                          <div class="broadcast-channel broadcast-channel-ch<?php echo $i; ?>">Ch: <?php echo sprintf('%03d', $i); ?></div>
                          <div class="broadcast-name-box">
                            <div class="broadcast-name broadcast-name-ch<?php echo $i; ?>"><?php echo $value; ?></div>
                            <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi-ch<?php echo $i; ?>"> - </span></div>
                          </div>
                        </div>
                        <div class="broadcast-title">
                          <span class="broadcast-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-title-id broadcast-title-ch<?php echo $i; ?>"></span>
                        </div>
                        <div class="broadcast-next">
                          <span class="broadcast-next-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-title-ch<?php echo $i; ?>"></span>
                        </div>
                      </div>
                    </button>
                    
                    <div class="progressbar">
                      <div class="progress progress-ch<?php echo $i; ?>"></div>
                    </div>

                  </form>
<?php	} //括弧終了 ?>
                </nav>

                <nav class="broadcast-nav swiper-slide">
<?php	foreach ($ch_CS as $i => $value){ // CSchの数だけ繰り返す ?>
                  <form class="broadcast-wrap" method="post">

                    <input type="hidden" name="state" value="ONAir">
                    <div class="broadcast-channel-id"><?php echo $i; ?></div>

                    <button type="button" class="broadcast">
                      <i class="broadcast-img material-icons">tv</i>
                      <div class="broadcast-content broadcast-ch<?php echo $i; ?>">
                        <div class="broadcast-channel-box">
                          <div class="broadcast-channel broadcast-channel-ch<?php echo $i; ?>">Ch: <?php echo sprintf('%03d', $i); ?></div>
                          <div class="broadcast-name-box">
                            <div class="broadcast-name broadcast-name-ch<?php echo $i; ?>"><?php echo $value; ?></div>
                            <div class="broadcast-jikkyo">実況勢い: <span class="broadcast-ikioi-ch<?php echo $i; ?>"> - </span></div>
                          </div>
                        </div>
                        <div class="broadcast-title">
                          <span class="broadcast-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-title-id broadcast-title-ch<?php echo $i; ?>"></span>
                        </div>
                        <div class="broadcast-next">
                          <span class="broadcast-next-start-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-to-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-end-ch<?php echo $i; ?>"></span>
                          <span class="broadcast-next-title-ch<?php echo $i; ?>"></span>
                        </div>
                      </div>
                    </button>
                    
                    <div class="progressbar">
                      <div class="progress progress-ch<?php echo $i; ?>"></div>
                    </div>

                  </form>
<?php	} //括弧終了 ?>
                </nav>

              </div>
            </div>
          </div>

          <div id="broadcast-stream-box">
            <div id="broadcast-stream-title"></div>
            <div id="broadcast-stream-info"></div>
            <form id="setting-form" action="/setting/" method="post">
              <input type="hidden" name="state" value="ONAir">
              <input id="broadcast-stream-channel" type="hidden" name="channel" value="">

              <div class="setchannel form">
                動画の画質：
                <div class="select-wrap">
                  <select name="quality">
                    <option value="<?php echo $quality_default; ?>">デフォルト (<?php echo $quality_default; ?>)</option>
                    <option value="1080p">1080p (1920×1080)</option>
                    <option value="810p">810p (1440×810)</option>
                    <option value="720p">720p (1280×720)</option>
                    <option value="540p">540p (960×540)</option>
                    <option value="360p">360p (640×360)</option>
                    <option value="240p">240p (426×240)</option>
                  </select>
                </div>
              </div>
							
              <div class="setencoder form">
                エンコード：
                <div class="select-wrap">
                  <select name="encoder">
                    <option value="<?php echo $encoder_default; ?>">デフォルト (<?php echo $encoder_default; ?>)</option>
                    <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                    <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                    <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
                  </select>
                </div>
              </div>

              <div class="setsubtitle form">
                字幕データ：
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
                使用BonDriver：
                <div class="select-wrap">
                  <select name="BonDriver">
<?php		if (!empty($BonDriver_default_T) or !empty($BonDriver_default_S)){ ?>
                    <option value="default">デフォルトのBonDriver</option>
<?php		} //括弧終了 ?>
<?php		foreach ($BonDriver_dll as $i => $value){ //chの数だけ繰り返す ?>
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
      </div>
  </section>

  <section id="footer">
    <?php echo $site_title.' '.$version; ?>

  </section>
</body>

</html>