<?php

	// Apache環境変数に deflate(gzip) 無効をセット
	apache_setenv('no-gzip', '1');

	// レスポンスをバッファに貯める
	ob_start();

	// ヘッダー読み込み
	require_once ('../header.php');

	echo '    <pre id="debug">';

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
		= initBonChannel($BonDriver_dir);

	echo '</pre>';

?>


    <div id="search-wrap">
      <div id="search-find-box">
      <div id="search-find-wrap">
        <div id="search-find-title">
          <div id="search-find-submit">
            <i class="fas fa-search"></i>
          </div>
          <input id="search-find-form" type="text" placeholder="番組を検索" autocomplete="off" />
          <div id="search-find-toggle">
            <i class="fas fa-caret-down"></i>
          </div>
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
          設定ページの「録画ファイルのあるフォルダ」が正しく設定されているかどうか、確認してください。<br>
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
        <form id="setting-form" action="/settings/" method="post">

          <input type="hidden" name="state" value="File">
          <input id="stream-filepath" type="hidden" name="filepath" value="">
          <input id="stream-filetitle" type="hidden" name="filetitle" value="">
          <input id="stream-fileinfo" type="hidden" name="fileinfo" value="">
          <input id="stream-fileext" type="hidden" name="fileext" value="">
          <input id="stream-filechannel" type="hidden" name="filechannel" value="">
          <input id="stream-filetime" type="hidden" name="filetime" value="">
          <input id="stream-start_timestamp" type="hidden" name="start_timestamp" value="">
          <input id="stream-end_timestamp" type="hidden" name="end_timestamp" value="">

          <div class="setstream form">
            <span>ストリーム：</span>
            <div class="select-wrap">
              <select name="stream">
<?php	if (!isStreamActive($ini, 1)){ ?>
                <option value="1" selected>Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php	} else if (!isStreamActive($ini, 2)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2" selected>Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php	} else if (!isStreamActive($ini, 3)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3" selected>Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php	} else if (!isStreamActive($ini, 4)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4" selected>Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php	} //括弧終了 ?>
<?php	if (isStreamActive($ini, 1) and isStreamActive($ini, 2) and isStreamActive($ini, 3) and isStreamActive($ini, 4)){ ?>
                <option value="1">Stream 1 - <?php echo getFormattedState($ini, 1, true); ?></option>
                <option value="2">Stream 2 - <?php echo getFormattedState($ini, 2, true); ?></option>
                <option value="3">Stream 3 - <?php echo getFormattedState($ini, 3, true); ?></option>
                <option value="4">Stream 4 - <?php echo getFormattedState($ini, 4, true); ?></option>
<?php		for ($i = 5; isStreamActive($ini, ($i - 1)); $i++){ ?>
                <option value="<?php echo $i; ?>"<?php if (!isStreamActive($ini, $i)) echo ' selected'; ?>>Stream <?php echo $i; ?> - <?php echo getFormattedState($ini, $i, true); ?></option>
<?php		} //括弧終了 ?>
<?php	} //括弧終了 ?>
              </select>
            </div>
          </div>

          <div class="setchannel form">
            <span>動画の画質：</span>
            <div class="select-wrap">
            	<select name="quality">
                <option value="<?php echo $quality_default; ?>" data-value="<?php echo $quality_default; ?>" data-text="デフォルト (<?php echo $quality_default; ?>)">デフォルト (<?php echo $quality_default; ?>)</option>
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
                <option value="<?php echo $encoder_default; ?>" data-value="<?php echo $encoder_default; ?>" data-text="デフォルト (<?php echo $encoder_default; ?>)">デフォルト (<?php echo $encoder_default; ?>)</option>
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
<?php		if ($subtitle_file_default == 'true'){ ?>
                <option value="<?php echo $subtitle_file_default; ?>">デフォルト (字幕オン)</option>
<?php		} else { ?>
                <option value="<?php echo $subtitle_file_default; ?>">デフォルト (字幕オフ)</option>
<?php		} //括弧終了 ?>
                <option value="true">字幕オン</option>
                <option value="false">字幕オフ</option>
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

<?php

	// 溜めてあった出力を解放しフラッシュする
	ob_end_flush();
	ob_flush();
	flush();

	// ファイルを四階層まで検索する
	// MP4・MKVファイルも検索する
	$search = array_merge(glob($TSfile_dir.'/*{.ts,.mts,.m2t,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*{.ts,.mts,.m2t,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*/*{.ts,.mts,.m2t,.m2ts,.mp4,.mkv}', GLOB_BRACE),
						  glob($TSfile_dir.'/*/*/*/*{.ts,.mts,.m2t,.m2ts,.mp4,.mkv}', GLOB_BRACE));
  
	if (file_exists($infofile)){
		$TSfile = json_decode(file_get_contents($infofile), true);
	} else {
		$TSfile['data'] = array();
	}

	// ファイルリストに記録されたファイル数と異なる場合
	if (count($search) != count($TSfile['data'])){

?>

    <script type="text/javascript">

  $(function(){

    // リストを更新
    toastr.info('リストを更新しています…');
    $.ajax({
      url: '/api/listupdate',
      dataType: 'json',
      cache: false,
      success: function(data) {

        if (data['status'] == 'success'){
          $('#rec-new').addClass('search-find-selected');
          $('#rec-old').removeClass('search-find-selected');
          $('#name-up').removeClass('search-find-selected');
          $('#name-down').removeClass('search-find-selected');
          $('#play-history').removeClass('search-find-selected');
          sortFileinfo('fileinfo', 1);
          toastr.success('リストを更新しました。');
        } else {
            $('#search-info').html('録画リストを更新中です。しばらく待ってからリロードしてみてください。');
        }
      }
    });
  
  });

    </script>
<?php	} // 括弧終了 ?>
</body>

</html>