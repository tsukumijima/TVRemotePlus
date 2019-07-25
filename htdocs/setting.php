<?php

	// 設定読み込み
	require_once ('../config.php');
	require_once ('../stream.php');

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);
	$state = $ini["state"];
	
	// 時計
	$clock = date("Y/m/d H:i:s");

	// POSTでフォームが送られてきた場合
	if ($_SERVER["REQUEST_METHOD"] == "POST"){
  
  		// POSTデータ読み込み
		// もし存在するなら$iniの連想配列に格納
		if ($_POST['state']) $ini['state'] = $_POST['state'];

		// ストリーミングを終了させる
		stream_stop();

		// ONAirなら
		if ($ini['state'] == "File"){

			// 連想配列に格納
			if ($_POST['filepath']) $ini['filepath'] = $_POST['filepath'];
			if ($_POST['filetitle']) $ini['filetitle'] = $_POST['filetitle'];
			if ($_POST['fileinfo']) $ini['fileinfo'] = $_POST['fileinfo'];
			if ($_POST['filechannel']) $ini['filechannel'] = $_POST['filechannel'];
			if ($_POST['filetime']) $ini['filetime'] = $_POST['filetime'];
			if ($_POST['start_timestamp']) $ini['start_timestamp'] = $_POST['start_timestamp'];
			if ($_POST['end_timestamp']) $ini['end_timestamp'] = $_POST['end_timestamp'];
			if ($_POST['quality']) $ini['quality'] = $_POST['quality'];
			if ($_POST['encoder']) $ini['encoder'] = $_POST['encoder'];

			// jsonからデコードして代入
			if (file_exists($infofile)){
				$TSfile = json_decode(file_get_contents($infofile), true);
			} else {
				$TSfile = array();
			}

			if (file_exists($historyfile)){
				$history = json_decode(file_get_contents($historyfile), true);
			} else {
				$history = array();
			}

			// 再生履歴の数
			$history_count = count($history);
			// 一定の値を超えたら徐々に消す
			if ($history_count >= $history_keep){
				$i = 0;
				while (count($history) >= $history_keep) {
					unset($history[$i]);
					$history = array_values($history); // インデックスを詰める
					$history_count = count($history);
					$i++;
				}
			}

			foreach ($TSfile as $key => $value) {
				if ($ini['filepath'] == $TSfile[$key]['file']){
					$history[$history_count]['play'] = time();
					$history[$history_count]['file'] = $TSfile[$key]['file'];
					$history[$history_count]['title'] = $TSfile[$key]['title'];
					$history[$history_count]['update'] = $TSfile[$key]['update'];
					$history[$history_count]['thumb'] = $TSfile[$key]['thumb'];
					$history[$history_count]['data'] = $TSfile[$key]['data'];
					$history[$history_count]['date'] = $TSfile[$key]['date'];
					$history[$history_count]['info'] = $TSfile[$key]['info'];
					$history[$history_count]['channel'] = $TSfile[$key]['channel'];
					$history[$history_count]['start'] = $TSfile[$key]['start'];
					$history[$history_count]['end'] = $TSfile[$key]['end'];
					$history[$history_count]['duration'] = $TSfile[$key]['duration'];
					$history[$history_count]['start_timestamp'] = $TSfile[$key]['start_timestamp'];
					$history[$history_count]['end_timestamp'] = $TSfile[$key]['end_timestamp'];
				}
			}

			// 再生履歴をファイルに保存
			file_put_contents($historyfile, json_encode($history, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			// ストリーミング開始
			stream_file($ini['filepath'], $ini['quality'], $ini['encoder']);

			// 準備中用の動画を流すためにm3u8をコピー
			copy($standby_m3u8, $base_dir.'htdocs/stream/stream.m3u8');

		} else if ($ini['state'] == "ONAir"){

			// 連想配列に格納
			if ($_POST['channel']) $ini['channel'] = strval($_POST['channel']);
			if ($_POST['quality']) $ini['quality'] = $_POST['quality'];
			if ($_POST['encoder']) $ini['encoder'] = $_POST['encoder'];
			if ($_POST['subtitle']) $ini['subtitle'] = $_POST['subtitle'];
			if ($_POST['BonDriver']) $ini['BonDriver'] = $_POST['BonDriver'];

			// BonDriverのデフォルトを要求されたら
			if ($ini['BonDriver'] == 'default'){
				if (intval($ini['channel']) >= 100){ // チャンネルの値が100より(=BSか)
					$ini['BonDriver'] = $BonDriver_default_S;
				} else { // 地デジなら
					$ini['BonDriver'] = $BonDriver_default_T;
				}
			} else { // デフォルトでないなら
				$ini['BonDriver'] = $BonDriver_dll[$ini['BonDriver']];
			}

			// ストリーミング開始
			stream_start($ini['channel'], $sid[$ini['channel']], $ini['BonDriver'], $ini['quality'], $ini['encoder'], $ini['subtitle']);

			// 準備中用の動画を流すためにm3u8をコピー
			copy($standby_m3u8, $base_dir.'htdocs/stream/stream.m3u8');

		// Offlineなら
		} else if ($ini['state'] == "Offline"){

			// 念のためもう一回ストリーミング終了関数を起動
			stream_stop();
				
			// 強制でチャンネルを0に設定する
			$ini['channel'] = '0';
				
			// 配信休止中用のプレイリスト
			copy($offline_m3u8, $base_dir.'htdocs/stream/stream.m3u8');

		}

		// iniファイル書き込み
		file_put_contents($inifile, json_encode($ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

		// リダイレクトが有効なら
		if ($setting_redirect == 'true'){
			// トップページにリダイレクト
			header('Location: '.$BASEURL);
			exit;
		}
		
	}

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
    autoplay: false,
    screenshot: true,
<?php	if ($state !== "File"){ ?>
    live: true,
<?php	} ?>
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
      </div>
		
      <div id="sidebar">
        <div id="comment-box-wrap">
          <div id="comment-box-header">
          <i class="fas fa-comment-alt"></i><b>　コメント一覧</b>
          </div>
          <div id="comment-box">
            <table id="comment-draw-box-header">
              <tr><th class="time">時間</th><th class="comment">コメント</th></tr>
            <table id="comment-draw-box">
            </table>
          </div>
        </div>
      </div>
    </div>

    <div id="description">
      <h2 class="title">
        <span id="setting-title"><?php echo $site_title; ?></span>
        <div class="subinfo">
          <a class="reload" href="" onClick="location.reload(true);">
            <span id="time"><?php echo $clock; ?></span>
            <i class="fas fa-redo-alt"></i>
          </a>
        </div>
      </h2>

      <h3 class="subtitle">
        <span id="setting-subtitle">設定画面</span>
      </h3>

      <div class="information">
        <div class="line"></div>
          <div id="setting">

<?php	if ($_SERVER["REQUEST_METHOD"] != "POST"){ // ブラウザからHTMLページを要求された場合 ?>

            <form id="setting-form" action="./setting.php" method="post">
              <input type="hidden" name="state" value="ONAir">

              <div class="setchannel form">
                チャンネル：
                <div class="select-wrap">
                  <select name="channel">
<?php		foreach ($ch as $i => $value){ //chの数だけ繰り返す ?>
                    <option value="<?php echo $i; ?>"><?php echo $value; ?></option>
<?php		} //括弧終了 ?>
                  </select>
                </div>
              </div>

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
                    <option value="<?php echo $i; ?>"><?php echo $value; ?></option>
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

              <div id="button-box">
<?php		if (!empty($BonDriver_dll) and !empty($ch)){ ?>
                <button class="bluebutton" type="submit"><i class="fas fa-play"></i>ストリーム開始</button>
<?php		} else {?>
                <button class="bluebutton" type="submit" disabled><i class="fas fa-play"></i>ストリーム開始</button>
<?php		} //括弧終了 ?>
                <button class="redbutton" type="button" onclick="location.href='./'"><i class="fas fa-home"></i>ホームに戻る</button>
              </div>

            </form>

<?php	} else { // POSTの場合 ?>

            <div id="setting-form" class="form">
              <p>ストリーム設定を保存しました。</p>
<?php		if ($ini['state'] == 'ONAir' or $ini['state'] == 'File'){ ?>
              <p>
                ストリーミングを開始します。<br>
                なお、ストリーミングの起動には数秒かかります。<br>
                再生が開始されない場合、数秒待ってからリロードしてみて下さい。<br>
              </p>
<?php		} else { ?>
              <p>ストリーミングを終了します。</p>
<?php		} //括弧終了 ?>
              <p>稼働状態：<?php echo $ini['state']; ?></p>
<?php		if ($ini['state'] == "ONAir"){ ?>
              <p>チャンネル：<?php echo $ch[$ini['channel']]; ?></p>
              <p>動画の画質：<?php echo $ini['quality']; ?></p>
              <p>エンコーダー：<?php echo $ini['encoder']; ?></p>
              <p>字幕の表示：<?php echo $ini['subtitle']; ?></p>
              <p>使用BonDriver：<?php echo $ini['BonDriver']; ?></p>

<?php		} else if ($ini['state'] == "File"){ ?>
              <p>タイトル：<?php echo $ini['filetitle']; ?></p>
              <p>動画の画質：<?php echo $ini['quality']; ?></p>
              <p>エンコーダー：<?php echo $ini['encoder']; ?></p>
<?php		} //括弧終了 ?>
              <div id="button-box">
                <button class="redbutton" type="button" onclick="location.href='./'"><i class="fas fa-home"></i>ホームに戻る</button>
                <button class="bluebutton" type="button" onclick="location.href='./setting.php'"><i class="fas fa-cog"></i>設定に戻る</button>
              </div>
            </div>
            
<?php		} //括弧終了 ?>
          </div>
      	</div>
     </div>

  </section>
	
  <section id="footer">
    <?php echo $site_title; ?>

  </section>
</body>

</html>