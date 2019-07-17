<?php

	// 設定読み込み
	require_once ('../config.php');

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	// ヘッダー読み込み
	require_once ('../header.php');

?>

  <section id="main">
    <div id="search-wrap">
      <div id="search-find-box">
      <div id="search-find-wrap">
        <div id="search-find-title">
          <i id="search-find-submit" class="fas fa-search"></i>
          <input id="search-find-form" type="text" placeholder="ファイルを検索" />
          <i id="search-find-toggle" class="fas fa-caret-down"></i>
        </div>
        <div id="search-find-link-box">
          <a id="rec-new" class="search-find-link search-find-selected">
            <i class="fas fa-sort-amount-up"></i>
            <span class="search-find-href">録画が新しい順</span>
          </a>
          <a id="rec-old" class="search-find-link">
            <i class="fas fa-sort-amount-down"></i>
            <span class="search-find-href">録画が古い順</span>
          </a>
          <a id="name-up" class="search-find-link">
            <i class="fas fa-sort-alpha-up"></i>
            <span class="search-find-href">名前昇順</span>
          </a>
          <a id="name-down" class="search-find-link">
            <i class="fas fa-sort-alpha-down"></i>
            <span class="search-find-href">名前降順</span>
          </a>
          <a id="play-history" class="search-find-link">
            <i class="fas fa-history"></i>
            <span class="search-find-href">再生履歴</span>
          </a>
        </div>
        </div>
      </div>

      <div id="search-box">
      </div>

      <div id="search-stream-box">
        <div id="search-stream-title"></div>
        <div id="search-stream-info"></div>
        <form id="setting-form" action="./setting.php" method="post">

          <input type="hidden" name="state" value="File">
          <input id="stream-filepath" type="hidden" name="filepath" value="">
          <input id="stream-filetitle" type="hidden" name="filetitle" value="">
          <input id="stream-fileinfo" type="hidden" name="fileinfo" value="">
          <input id="stream-filechannel" type="hidden" name="filechannel" value="">
          <input id="stream-filetime" type="hidden" name="filetime" value="">
          <input id="stream-start_timestamp" type="hidden" name="start_timestamp" value="">
          <input id="stream-end_timestamp" type="hidden" name="end_timestamp" value="">

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

          <div id="button-box">
            <button class="bluebutton" type="submit"><i class="fas fa-play"></i>再生する</button>
            <button class="redbutton" type="button"><i class="fas fa-times"></i>キャンセル</button>
          </div>

        </form>
      </div>
    </div>
  </section>

  <section id="footer">
    <?php echo $site_title; ?>
    
  </section>
</body>

</html>