<?php

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

	// ヘッダー読み込み
	require_once ('../header.php');

?>

  <section id="main">
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
      url: 'stream/stream.m3u8',
      type: 'hls'
    },
    // 読み込むdanmaku(コメント)
    danmaku: {
      id: 'TVRemotePlus',
      user: 'TVRemotePlus',
      api: 'api/jkapi.php/',
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
              <img id="tweet-picture" src="files/picture.svg">
              <img id="tweet-picture-comment" src="files/comment.svg">
              <img id="tweet-reset" src="files/reset.svg">
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
                            <div class="broadcast-name"><?php echo $value; ?></div>
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
                            <div class="broadcast-name"><?php echo $value; ?></div>
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
                            <div class="broadcast-name"><?php echo $value; ?></div>
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
            <form id="setting-form" action="./setting.php" method="post">
              <input type="hidden" name="state" value="ONAir">
              <input id="broadcast-stream-channel" type="hidden" name="channel" value="">

              <div class="setchannel form">
                動画の画質：
                <div class="select-wrap">
                  <select name="quality">
                    <option value="<?php echo $quality_default; ?>">デフォルト</option>
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
                    <option value="<?php echo $encoder_default; ?>">デフォルト</option>
                    <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                    <option value="QSVEnc">QSVEnc (ハードウェアエンコーダー)</option>
                  </select>
                </div>
              </div>

              <div class="setsubtitle form">
                字幕データ：
                <div class="select-wrap">
                  <select name="subtitle">
                    <option value="<?php echo $subtitle_default; ?>">デフォルト</option>
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
                    <option value="default">デフォルト</option>
<?php		} //括弧終了 ?>
<?php		foreach ($BonDriver_dll as $i => $value){ //chの数だけ繰り返す ?>
                    <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
<?php		} //括弧終了 ?>
                  </select>
                </div>
              </div>
			  
<?php		if (empty($BonDriver_dll) and empty($ch)){ ?>
              <div class="form">
                BonDriverとチャンネル設定ファイルが見つかりませんでした…<br>
                ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
              </div>
<?php		} else if (empty($BonDriver_dll)){ ?>
              <div class="form">
                BonDriverが見つかりませんでした…<br>
                ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
              </div>
<?php		} else if (empty($ch)){ ?>
              <div class="form">
                チャンネル設定ファイルが見つかりませんでした…<br>
                ファイルがBonDriverフォルダに正しく配置されているか、確認してください。<br>
              </div>
<?php		} //括弧終了 ?>

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
        </div>
      </div>
  </section>

  <section id="footer">
    <?php echo $site_title.' '.$version; ?>

  </section>
</body>

</html>