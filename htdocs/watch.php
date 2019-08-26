<?php

	// ヘッダー読み込み
	require_once ('../header.php');

	echo '    <pre id="debug">';

	// モジュール読み込み
	require_once ('../module.php');

	// 設定ファイル読み込み
	$ini = json_decode(file_get_contents($inifile), true);

	$search = array_merge(glob($TSfile_dir.'*.ts'), glob($TSfile_dir.'*\*.ts'));
	if (file_exists($infofile)){
		$TSfile = json_decode(file_get_contents($infofile), true);
	} else {
		$TSfile = array();
	}

	echo '</pre>';

	// ファイルリストに記録されたファイル数と異なる場合
	if (count($search) != count($TSfile['data'])){

?>


    <script type="text/javascript">

  $(function(){

    // リストを更新
    toastr.info('リストを更新しています…');
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

    </script>
<?php	} // 括弧終了 ?>


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
<?php	if (empty($TSfile_dir) or !file_exists($TSfile_dir)){ // エラーを吐く ?>
        <div class="error">
          録画ファイルのあるフォルダが正しく設定されていません。<br>
          config.php の「録画ファイルのあるフォルダ」が正しく設定されているかどうか、確認してください。<br>
        </div>
<?php	} //括弧終了 ?>
        <div id="search-info">
        </div>
        <div id="search-list">
        </div>
      </div>

      <div id="search-stream-box">
        <div id="search-stream-title"></div>
        <div id="search-stream-info"></div>
        <form id="setting-form" action="/setting/" method="post">

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

          <div id="button-box">
            <button class="bluebutton" type="submit"><i class="fas fa-play"></i>再生する</button>
            <button class="redbutton" type="button"><i class="fas fa-times"></i>キャンセル</button>
          </div>

        </form>
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