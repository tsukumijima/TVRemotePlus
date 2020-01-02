<?php

	// ヘッダー読み込み
	require_once ('../header.php');
  
	// モジュール読み込み
	require_once ('../stream.php');

	echo '    <pre id="debug">';

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
        = initBonChannel($BonDriver_dir);
	
	// 時計
	$clock = date("Y/m/d H:i:s");

	// POSTでフォームが送られてきた場合
	if ($_SERVER['REQUEST_METHOD'] == 'POST'){

		// ストリーム番号を取得
		if (!empty($_POST['stream'])){
			$stream = strval($_POST['stream']);
		} else {
			$stream = '1';
    }

		// 設定ファイル読み込み
		$ini = json_decode(file_get_contents($inifile), true);

		// POSTデータ読み込み
		// もし存在するなら$iniの連想配列に格納
		if (isset($_POST['state'])) $ini[$stream]['state'] = $_POST['state'];

		if ((!isset($_POST['restart']) and !isset($_POST['setting-env'])) or 
			(isset($_POST['restart']) and !isset($_POST['setting-env']) and time() - filemtime($segment_folder.'stream'.$stream.'.m3u8') > 20)){

			// 通常のストリーム開始処理

			// ストリームを終了させる
			stream_stop($stream);

			// ONAirなら
			if ($ini[$stream]['state'] == 'File'){

				// 連想配列に格納
				if ($_POST['filepath']) $ini[$stream]['filepath'] = $_POST['filepath'];
				if ($_POST['filetitle']) $ini[$stream]['filetitle'] = $_POST['filetitle'];
				if ($_POST['fileinfo']) $ini[$stream]['fileinfo'] = $_POST['fileinfo'];
				if ($_POST['fileext']) $ini[$stream]['fileext'] = $_POST['fileext'];
				if ($_POST['filechannel']) $ini[$stream]['filechannel'] = $_POST['filechannel'];
				if ($_POST['filetime']) $ini[$stream]['filetime'] = $_POST['filetime'];
				if ($_POST['start_timestamp']) $ini[$stream]['start_timestamp'] = $_POST['start_timestamp'];
				if ($_POST['end_timestamp']) $ini[$stream]['end_timestamp'] = $_POST['end_timestamp'];
				if ($_POST['quality']) $ini[$stream]['quality'] = $_POST['quality'];
				if ($_POST['encoder']) $ini[$stream]['encoder'] = $_POST['encoder'];
				if ($_POST['subtitle']) $ini[$stream]['subtitle'] = $_POST['subtitle'];

				// jsonからデコードして代入
				if (file_exists($infofile)){
					$TSfile = json_decode(file_get_contents($infofile), true);
				} else {
					$TSfile = array();
				}

				if (file_exists($historyfile)){
					$history = json_decode(file_get_contents($historyfile), true);
				} else {
					$history = array(
						'data' => array()
					);
				}

				// 再生履歴の数
				$history_count = count($history['data']);
				// 一定の値を超えたら1つずつ消す
				if ($history_count >= $history_keep){
					$i = 0;
					while (count($history['data']) >= $history_keep) {
						unset($history['data'][$i]);
						$history['data'] = array_values($history['data']); // インデックスを詰める
						$history_count = count($history['data']);
						$i++;
					}
				}

				foreach ($TSfile['data'] as $key => $value) {
					if ($ini[$stream]['filepath'] == $TSfile['data'][$key]['file']){
						$history['data'][$history_count] = $TSfile['data'][$key];
						$history['data'][$history_count]['play'] = time();
					}
				}

				// 再生履歴をファイルに保存
				file_put_contents($historyfile, json_encode($history, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

				// MP4(progressive)は除外
				if (!($ini[$stream]['fileext'] == 'mp4' and $ini[$stream]['encoder'] == 'Progressive')){

					// ストリーミング開始
					$stream_cmd = stream_file($stream, $TSfile_dir.'/'.$ini[$stream]['filepath'], $ini[$stream]['quality'], $ini[$stream]['encoder'], $ini[$stream]['subtitle']);

					// 準備中用の動画を流すためにm3u8をコピー
					if ($silent == 'true'){
						copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
					} else {
						copy($standby_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
					}

				} else {

					$cmd = 'Progressive';

				}

			} else if ($ini[$stream]['state'] == 'ONAir'){

				// 連想配列に格納
				if ($_POST['channel']) $ini[$stream]['channel'] = strval($_POST['channel']);
				if ($_POST['quality']) $ini[$stream]['quality'] = $_POST['quality'];
				if ($_POST['encoder']) $ini[$stream]['encoder'] = $_POST['encoder'];
				if ($_POST['subtitle']) $ini[$stream]['subtitle'] = $_POST['subtitle'];
				if ($_POST['BonDriver']) $ini[$stream]['BonDriver'] = $_POST['BonDriver'];

				// BonDriverのデフォルトを要求される or 何故かBonDriverが空
				if ($ini[$stream]['BonDriver'] == 'default' or empty($ini[$stream]['BonDriver'])){
					if (intval($ini[$stream]['channel']) >= 100 or intval($ini[$stream]['channel']) === 55){ // チャンネルの値が100より上(=BS・CSか・ショップチャンネルは055なので例外指定)
						$ini[$stream]['BonDriver'] = $BonDriver_default_S;
					} else { // 地デジなら
						$ini[$stream]['BonDriver'] = $BonDriver_default_T;
					}
				}

				// ストリーミング開始
				list($stream_cmd, $tstask_cmd) = stream_start($stream, $ini[$stream]['channel'], $sid[$ini[$stream]['channel']], $tsid[$ini[$stream]['channel']], $ini[$stream]['BonDriver'], $ini[$stream]['quality'], $ini[$stream]['encoder'], $ini[$stream]['subtitle']);

				// 準備中用の動画を流すためにm3u8をコピー
				if ($silent == 'true'){
					copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
				} else {
					copy($standby_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
				}

			// Offlineなら
			} else if ($ini[$stream]['state'] == 'Offline'){

				// 念のためもう一回ストリーミング終了関数を起動
				stream_stop($stream);
					
				// 強制でチャンネルを0に設定する
				$ini[$stream]['channel'] = '0';
					
				// 配信休止中用のプレイリスト
				if ($silent == 'true'){
					copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
				} else {
					copy($offline_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
				}

			}

			// iniファイル書き込み
			file_put_contents($inifile, json_encode($ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			// リダイレクトが有効なら
			if ($setting_redirect == 'true'){
				// トップページにリダイレクト
				if ($reverse_proxy){
					header('Location: '.$reverse_proxy_url.$stream.'/');
				} else {
					header('Location: '.$site_url.$stream.'/');
				}
				exit;
			}

		// 環境設定を保存する
		} else if (isset($_POST['setting-env'])){

			// リバースプロキシからのアクセスの時に環境設定を隠す設定になっていない &
			// リバースプロキシからのアクセスでないなら
			if (!($reverse_proxy and $setting_hide == 'true')){
			
				// ファイル読み込み
				$tvrp_conf = file_get_contents($tvrp_conf_file);

				// 配列で回す
				foreach ($_POST as $key => $value) {

					// シングルクォーテーションを取る（セキュリティ対策）
					$value = str_replace('\'', '', $value);

					// 数値化できるものは数値に変換しておく
					if (is_numeric($value) and mb_substr($value, 0, 1) != '0'){
						$set = intval($value);
					} else {
						$set = '\''.strval($value).'\'';
					}

					// バックスラッシュ(\)を見つけたらスラッシュに変換
					if (strpos($set, '\\') !== false){
						$set = str_replace('\\', '/', $set);
					}
					
					// config.php を書き換え
					$tvrp_conf = preg_replace("/^\\$$key =.*;/m", '$'.$key.' = '.$set.';', $tvrp_conf); // 置換

				}
				
				// ファイル書き込み
				file_put_contents($tvrp_conf_file, $tvrp_conf);

			}
		}
	}

	echo '</pre>';

?>

      <div class="information">
        <div id="setting">
<?php	if ($_SERVER["REQUEST_METHOD"] != "POST"){ // ブラウザからHTMLページを要求された場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

          <p>
            TVRemotePlus の設定を Web 上から行えます。<br>
          </p>

          <div class="setting-form-wrap">
<?php	if (!$reverse_proxy){ ?>

            <h3 class="green"><i class="fas fa-tablet-alt"></i>PWA・HTTPS</h3>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>HTTPS アクセス用の自己署名証明書のダウンロード</span>
                <p>
                  PWA (Progressive Web Apps) 機能を利用する場合は、HTTPS でのアクセスが必須です<br>
                  そのため、インストール時に作成した自己署名証明書を予め TVRemotePlus を利用する端末にインポートしておく必要があります<br>
                  右 or 下のダウンロードボタンから証明書 (server.crt) をダウンロードしてください<br>
                  証明書のインストール手順は <a href="https://github.com/tsukumijima/TVRemotePlus#PWA%20%E3%81%AE%E3%82%A4%E3%83%B3%E3%82%B9%E3%83%88%E3%83%BC%E3%83%AB%E6%89%8B%E9%A0%86" target="blank">こちら</a> を参照してください<br>
                </p>
              </div>
              <a class="download" href="/server.crt">
                <i class="fas fa-download"></i>
              </a>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>HTTPS 用 URL にアクセス</span>
                <p>
                  HTTPS 用 URL で TVRemotePlus にアクセスできます<br>
                  Chrome(iOSのみSafari) でアクセスした場合は、Androidは「TVRemotePlus をホーム画面に追加」から、
                  PC は URL バーの横に「インストール」と出てくるので、それを押してホーム画面やデスクトップに追加し、そこから起動すると PWA モードでネイティブアプリのように利用できます<br>
                  HTTPS アクセスの方が上位互換なので、自己署名証明書をインポートした端末では普段も HTTPS でアクセスする事をお勧めします<br>
                </p>
              </div>
              <a class="download" href="https://<?php echo $_SERVER['SERVER_NAME']; ?>:<?php echo $https_port; ?>/">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>

<?php		if (!empty($reverse_proxy_url)){ ?>
            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>リバースプロキシ用 URL にアクセス</span>
                <p>
                  リバースプロキシ用 URL で TVRemotePlus にアクセスできます<br>
                </p>
              </div>
              <a class="download" href="<?php echo $reverse_proxy_url; ?>">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
<?php		} // 括弧終了 ?>
            
          </div>
<?php	} // 括弧終了 ?>
          
          <form id="setting-user" class="setting-form-wrap">

            <input type="hidden" name="setting-user" value="true" />

            <h3 class="blue">
              <i class="fas fa-user-cog"></i>個人設定
              <div id="button-box">
                <button class="bluebutton" type="submit">
                  <i class="fas fa-save"></i>保存する
                </button>
              </div>
            </h3>

            <p>個人設定はブラウザ・端末ごとに反映されます。</p>

            <h4><i class="fas fa-eye"></i>表示</h4>

            <div class="setting-form">
              <span>Twitter 投稿フォーム</span>
              <div class="toggle-switch">
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['twitter_show'] == false){ ?>
                <input id="twitter_show" class="toggle-input" type="checkbox" value="true" />
<?php	} else { ?>
                <input id="twitter_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} // 括弧終了 ?>
                <label for="twitter_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>コメント一覧</span>
              <div class="toggle-switch">
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['comment_show'] == false){ ?>
                <input id="comment_show" class="toggle-input" type="checkbox" value="true" />
<?php	} else { ?>
                <input id="comment_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} // 括弧終了 ?>
                <label for="comment_show" class="toggle-label"></label>
              </div>
            </div>

            <h4><i class="fas fa-sliders-h"></i>機能</h4>

            <div class="setting-form setting-select">
              <span>コメントのフォントサイズ</span>
              <div class="select-wrap">
                <select id="comment_size" required>
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['comment_size'] == '42'){ ?>
                  <option value="42" selected>大きめ</option>
                  <option value="35">ふつう</option>
                  <option value="28">小さめ</option>
<?php	} else if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['comment_size'] == '35'){ ?>
                  <option value="42">大きめ</option>
                  <option value="35" selected>ふつう</option>
                  <option value="28">小さめ</option>
<?php	} else if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['comment_size'] == '28'){ ?>
                  <option value="42">大きめ</option>
                  <option value="35">ふつう</option>
                  <option value="28" selected>小さめ</option>
<?php	} else { ?>
                  <option value="42">大きめ</option>
                  <option value="35" selected>ふつう</option>
                  <option value="28">小さめ</option>
<?php	} // 括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-select">
              <span>コメントの遅延時間（秒）</span>
<?php	if (isset($_COOKIE['settings']) and isset(json_decode($_COOKIE['settings'], true)['comment_delay'])){ ?>
              <input class="text-box" id="comment_delay" type="number" min="0" max="60" placeholder="5" value="<?php echo json_decode($_COOKIE['settings'], true)['comment_delay']; ?>" required />
<?php	} else { ?>
              <input class="text-box" id="comment_delay" type="number" min="0" max="60" placeholder="5" value="5" required />
<?php	} // 括弧終了 ?>
            </div>

            <div class="setting-form">
              <span>デフォルト設定を使いワンクリックでストリームを開始する</span>
              <div class="toggle-switch">
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['onclick_stream'] == true){ ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="onclick_stream" class="toggle-label"></label>
              </div>
            </div>

          </form>
<?php	if (!($reverse_proxy and $setting_hide == 'true')){ ?>

          <form id="setting-env" class="setting-form-wrap">
          
            <input type="hidden" name="setting-env" value="true" />

            <h3 class="red">
              <i class="fas fa-tools"></i>環境設定
              <div id="button-box">
                <button class="redbutton" type="submit">
                  <i class="fas fa-save"></i>保存する
                </button>
              </div>
            </h3>

            <p>環境設定は全てのブラウザ・端末に反映されます。</p>

            <h4><i class="fas fa-toolbox"></i>デフォルト</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
              <span>デフォルトの動画の画質</span>
                <p>
                  新規インストール時のデフォルトは 1080p (1440×1080) です<br>
                  テレビ放送は一部の BS 局を除き基本的に 1440×1080 で放送されています<br>
                  再生させる端末のスペックや液晶の大きさや解像度等に合わせ、適宜変更してください<br>
                  240p は画質は低くなりますが、低通信量でワンセグよりも高画質で視聴できます<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="quality_default">
<?php	if ($quality_default == '1080p-high'){ ?>
                  <option value="1080p-high" selected>1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '1080p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p" selected>1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '810p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p" selected>810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '720p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p" selected>720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '540p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p" selected>540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '360p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p" selected>360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '240p'){ ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p">1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p" selected>240p (426×240)</option>
<?php	} else { ?>
                  <option value="1080p-high">1080p-high (1920×1080)</option>
                  <option value="1080p" selected>1080p (1440×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} // 括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトのエンコーダー</span>
                <p>
                  ffmpeg が通常のエンコーダー(ソフトウェアエンコーダー)、
                  QSVEncC・NVEncC・VCEEncC がハードウェアエンコーダーです<br>
                  QSVEncC・NVEncC・VCEEncC の方が CPU を消費しない・エンコードが早いためおすすめですが、
                  QSVEncC は Intel 製の一部の GPU 、NVEncC は nvidia 製の GPU 環境、VCEEncC は AMD の Radeon GPU でしか利用できません<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="encoder_default" required>
<?php	if ($encoder_default == 'ffmpeg'){ ?>
                  <option value="ffmpeg" selected>ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
                  <option value="VCEEncC">VCEEncC (ハードウェアエンコーダー)</option>
<?php	} else if ($encoder_default == 'QSVEncC'){ ?>
                  <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC" selected>QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
                  <option value="VCEEncC">VCEEncC (ハードウェアエンコーダー)</option>
<?php	} else if ($encoder_default == 'NVEncC'){ ?>
                  <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC" selected>NVEncC (ハードウェアエンコーダー)</option>
                  <option value="VCEEncC">VCEEncC (ハードウェアエンコーダー)</option>
<?php	} else if ($encoder_default == 'VCEEncC'){ ?>
                  <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
                  <option value="VCEEncC" selected>VCEEncC (ハードウェアエンコーダー)</option>
<?php	} // 括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ再生時にデフォルトで字幕をストリームに含める</span>
                <p>
                  この設定をオンにすると、ライブ再生時に字幕を表示出来るようになります<br>
                  ただし、まれにエラーを吐いてストリームが開始出来ない場合があったり、
                  字幕の無い番組やCMに入った等のタイミングで一部のセグメントのエンコードが遅れ、ストリームがカクつく場合もあります<br>
                  字幕自体は個々にプレイヤー側で表示/非表示を切り替え可能なので、デフォルトはオフにして、字幕付きで見たい時だけオンにすることをおすすめします<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="subtitle_default" value="false" />
<?php	if ($subtitle_default == 'true'){ ?>
                <input id="subtitle_default" name="subtitle_default" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="subtitle_default" name="subtitle_default" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="subtitle_default" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ファイル再生時にデフォルトで字幕をストリームに含める</span>
                <p>
                  この設定をオンにすると、ファイル再生時に字幕を表示出来るようになります<br>
                  ファイル再生時は、基本的にライブ再生時のようなエンコードの問題は起こりません<br>
                  ただ、ごく稀に字幕付きでエンコードした事で途中でエンコードが失敗する場合があるため、念のため設定できるようにしています<br>
                  字幕自体は個々にプレイヤー側で表示/非表示を切り替え可能なので、デフォルトはオンにして、問題が起きたときのみオフにすることをおすすめします<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="subtitle_file_default" value="false" />
<?php	if ($subtitle_file_default == 'true'){ ?>
                <input id="subtitle_file_default" name="subtitle_file_default" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="subtitle_file_default" name="subtitle_file_default" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="subtitle_file_default" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトの BonDriver (地デジ用)</span>
                <p>
                  デフォルトで利用する BonDriver (地デジ用) です<br>
                  うまく再生出来ない場合、BonDriver_Spinel もしくは BonDriver_Proxy を利用すると安定して視聴できる場合があります<br>
                  導入している場合は BonDriver_Spinel か BonDriver_Proxy を利用することをおすすめします<br>
                  Spinel よりも BonDriverProxyEx の方がストリーム開始にかかる時間は短くなります<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="BonDriver_default_T" required>
<?php		foreach ($BonDriver_dll_T as $i => $value){ //chの数だけ繰り返す ?>
<?php			if ($value == $BonDriver_default_T){ ?>
                  <option value="<?php echo $value; ?>" selected><?php echo $value; ?></option>
<?php			} else { ?>
                  <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
<?php			} //括弧終了 ?>
<?php		} //括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトの BonDriver (BS・CS用)</span>
                <p>
                  デフォルトで利用する BonDriver (BS・CS用) です<br>
                  うまく再生出来ない場合、BonDriver_Spinel もしくは BonDriver_Proxy を利用すると安定して視聴できる場合があります<br>
                  導入している場合は BonDriver_Spinel か BonDriver_Proxy を利用することをおすすめします<br>
                  BonDriver_Spinel よりも BonDriver_Proxy の方がストリーム開始にかかる時間は短くなります<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="BonDriver_default_S" required>
<?php		foreach ($BonDriver_dll_S as $i => $value){ //chの数だけ繰り返す ?>
<?php			if ($value == $BonDriver_default_S){ ?>
                  <option value="<?php echo $value; ?>" selected><?php echo $value; ?></option>
<?php			} else { ?>
                  <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
<?php			} //括弧終了 ?>
<?php		} //括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>録画ファイルのあるフォルダ</span>
                <p>
                  ファイル再生の際に利用します<br>
                  ネットワークドライブ内のフォルダは認識できないかもしれません<br>
                </p>
              </div>
              <input class="text-box" name="TSfile_dir" type="text" value="<?php echo $TSfile_dir; ?>" placeholder="E:/TV-Record/" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>EDCB Material WebUI (EMWUI) の API がある URL</span>
                <p>
                  番組表取得などで利用します<br>
                  この機能を利用する場合、予め <a href="https://github.com/EMWUI/EDCB_Material_WebUI" target="_blank">EDCB Material WebUI</a> を導入しておいてください<br>
                  TVRock 等を利用している場合、<a href="http://vb45wb5b.seesaa.net/" target="_blank">TVRemoteViewer_VB</a> 2.93m（再うｐ版）以降を導入し TVRemoteViewer_VB の URL（例：http://192.168.x.xx:40003/ ）を代わりに設定することで番組情報が表示できるようになります<br>
                </p>
              </div>
              <input class="text-box" name="EDCB_http_url" type="url" value="<?php echo $EDCB_http_url; ?>" placeholder="http://192.168.x.xx:5510/api/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>リバースプロキシからアクセスする場合の URL</span>
                <p>
                  リバースプロキシからのアクセス時のみ利用されます<br>
                  リバースプロキシからのアクセスをしない場合は空のままで OK です<br>
                  また、リバースプロキシから Twitter 投稿機能を利用する場合は、
                  <a href="https://github.com/tsukumijima/TVRemotePlus/blob/master/docs/Twitter_Develop.md#%E3%82%A2%E3%83%97%E3%83%AA%E4%BD%9C%E6%88%90%E7%94%BB%E9%9D%A2%E3%83%95%E3%82%A9%E3%83%BC%E3%83%A0%E4%BE%8B" target="_blank">こちら</a> を参考に Twitter API アプリ作成フォームの Callback URLs に (ここで指定したURL)/tweet/callback.php と追加しておいてください<br>
                </p>
              </div>
              <input class="text-box" name="reverse_proxy_url" type="url" value="<?php echo $reverse_proxy_url; ?>" placeholder="https://example.com/tvrp/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>リバースプロキシからのアクセス時に環境設定を非表示にする</span>
                <p>
                  リバースプロキシで外部からアクセスできる（環境設定を編集できる）状態にした場合、外部から悪意のある攻撃が行われた場合に脆弱になる可能性があります<br>
                  この設定をオンにすると、リバースプロキシからのアクセス時に設定ページの環境設定を非表示にします（環境設定を保存する処理自体を封印します）<br>
                  リバースプロキシを使っている方はオンにしておくことをおすすめします<br>
                  再びこの設定をオフにする場合は、リバースプロキシを介さずに設定ページにアクセスするか、config.php を直接編集してください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="setting_hide" value="false" />
<?php	if ($setting_hide == 'true'){ ?>
                <input id="setting_hide" name="setting_hide" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="setting_hide" name="setting_hide" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="setting_hide" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>配信休止中・配信準備中時の動画の BGM を消す</span>
                <p>
                  消す場合はオン、消さない (流す) 場合はオフです<br>
                  毎回 BGM が流れて鬱陶しい場合はオンにしてください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="silent" value="false" />
<?php	if ($silent == 'true'){ ?>
                <input id="silent" name="silent" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="silent" name="silent" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="silent" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>再生履歴を保持する件数</span>
                <p>
                  新規インストール時のデフォルトは10件です<br>
                </p>
              </div>
              <input class="text-box" name="history_keep" type="number" min="1" max="100" placeholder="10" value="<?php echo $history_keep; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TVRemotePlus のアップデートを確認する</span>
                <p>
                鬱陶しい場合・TVRemotePlus の読み込みが遅い場合はオフにしてください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="update_confirm" value="false" />
<?php	if ($update_confirm == 'true'){ ?>
                <input id="update_confirm" name="update_confirm" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="update_confirm" name="update_confirm" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="update_confirm" class="toggle-label"></label>
              </div>
            </div>

            <h4><i class="fas fa-comment-alt"></i>ニコニコ実況</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ニコニコにログインする際のメールアドレス</span>
                <p>
                  ニコニコ実況への投稿・ファイル再生時の過去ログの取得に必須です<br>
                  利用する場合、予めニコニコアカウントを作成しておく必要があります<br>
                  設定しなくても生放送のコメントは取得できますが、コメント投稿・過去ログの取得はできません<br>
                </p>
              </div>
              <input class="text-box" name="nicologin_mail" type="email" value="<?php echo $nicologin_mail; ?>" placeholder="example@gmail.com" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ニコニコにログインする際のパスワード</span>
                <p>
                  ニコニコ実況への投稿・ファイル再生時の過去ログの取得に必須です<br>
                  利用する場合、予めニコニコアカウントを作成しておく必要があります<br>
                  設定しなくても生放送のコメントは取得できますが、コメント投稿・過去ログの取得はできません<br>
                </p>
              </div>
              <div class="password-box-wrap">
                <input class="password-box" name="nicologin_password" type="password" value="<?php echo $nicologin_password; ?>" placeholder="password" />
                <i class="password-box-input fas fa-eye-slash"></i>
              </div>
            </div>

            <h4><i class="fab fa-twitter"></i>Twitter 投稿</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ハッシュタグ付きツイートを連投したと判断しハッシュタグを外すまでの秒数</span>
                <p>
                  アカウントのシャドウバンを回避するための設定です<br>
                  Twitter の規制が厳しいため、ハッシュタグをつけたツイートを60秒以下（？）の間隔で連投すると、シャドウバン (Search Ban・検索に引っかからなくなる) されてしまうことがあります<br>
                  例えば 60 (秒) に設定した場合、ハッシュタグ付きツイートを投稿してから60秒以内に、再びハッシュタグ付きツイートを投稿しようとした場合にツイートからハッシュタグを外します<br>
                  シャドウバンを避けるため、60 (秒) より下には設定しないことをお勧めします<br>
                  連投と判定されたツイートは 「#」の右にスペースを入れハッシュタグとして機能しないようにしてから投稿されますが、鬱陶しい場合は 0 (秒) に設定すればオフになります<br>
                </p>
              </div>
              <input class="text-box" name="tweet_time" type="number" min="0" max="120" placeholder="60" value="<?php echo $tweet_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>画像付きツイートを投稿する時に一度アップロードする画像の保存フォルダ</span>
                <p>
                新規インストール時のデフォルトの upload/ に設定すると、(TVRemotePlusをインストールしたフォルダ)/htdocs/tweet/upload/ に自動で保存されます<br>
                ずっと画像付きツイートをしているとそこそこのファイルサイズになるので、適宜録画用 HDD 内のフォルダを指定しておくのも良いと思います<br>
                </p>
              </div>
              <input class="text-box" name="tweet_upload" type="text" value="<?php echo $tweet_upload; ?>" placeholder="E:/TV-Capture/" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>画像付きツイートを投稿する時に一度アップロードした画像を削除する</span>
                <p>
                  削除する場合はオン、削除しない場合はオフです<br>
                  アップロードした画像を削除しない場合、画像は上の項目で設定したフォルダに保存されます<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="tweet_delete" value="false" />
<?php	if ($tweet_delete  == 'true'){ ?>
                <input id="tweet_delete" name="tweet_delete" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="tweet_delete" name="tweet_delete" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="tweet_delete" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TwitterAPI のコンシューマーキー (Consumer Key)</span>
                <p>
                TVRemotePlus からのツイート投稿に必須です<br>
                コンシューマーキーは25文字のランダムな英数字です<br>
                </p>
              </div>
              <input class="text-box" name="CONSUMER_KEY" type="text" pattern="[A-Za-z0-9]{25}" value="<?php echo $CONSUMER_KEY; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXX" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TwitterAPI のコンシューマーシークレット (Consumer Secret)</span>
                <p>
                TVRemotePlus からのツイート投稿に必須です<br>
                コンシューマーシークレットは50文字のランダムな英数字です<br>
                </p>
              </div>
              <input class="text-box" name="CONSUMER_SECRET" type="text" pattern="[A-Za-z0-9]{50}" value="<?php echo $CONSUMER_SECRET; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
            </div>
            
            <h4><i class="fas fa-lock"></i>Basic 認証</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>Basic 認証を利用する</span>
                <p>
                  利用する場合はオン、利用しない場合はオフです<br>
                  おまけ機能みたいなものなので、一部の機能が動かないこともあるかもしれません<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="basicauth" value="false" />
<?php	if ($basicauth == 'true'){ ?>
                <input id="basicauth" name="basicauth" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="basicauth" name="basicauth" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="basicauth" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>Basic 認証のユーザー名</span>
                <p>
                  Basic 認証で TVRemotePlus にログインする時のユーザー名を設定します<br>
                  デフォルトは user ですが、Basic 認証を利用する場合はできるだけ変更してください<br>
                </p>
              </div>
              <input class="text-box" name="basicauth_user" type="text" pattern="^[0-9A-Za-z]+$" value="<?php echo $basicauth_user; ?>" placeholder="user" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>Basic 認証のパスワード</span>
                <p>
                  Basic 認証で TVRemotePlus にログインする時のパスワードを設定します<br>
                  デフォルトは password ですが、Basic 認証を利用する場合はできるだけ変更してください<br>
                </p>
              </div>
              <div class="password-box-wrap">
                <input class="password-box" name="basicauth_password" type="password" value="<?php echo $basicauth_password; ?>" placeholder="password" required />
                <i class="password-box-input fas fa-eye-slash"></i>
              </div>
            </div>
            
            <h4><i class="fas fa-hammer"></i>詳細設定</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ストリーム開始後に再生画面へリダイレクトする</span>
                <p>
                  リダイレクトする場合はオン、リダイレクトしない場合はオフですが、基本的にオンで良いと思います<br>
                  ストリーム開始に失敗する時などにオフに設定して設定完了ページを表示し、デバッグできるようにするための機能です<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="setting_redirect" value="false" />
<?php	if ($setting_redirect == 'true'){ ?>
                <input id="setting_redirect" name="setting_redirect" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="setting_redirect" name="setting_redirect" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="setting_redirect" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ストリーム終了時に TSTask を強制終了する</span>
                <p>
                  TSTask がうまく終了しない・ゾンビプロセス化する場合のみオンにしてください<br>
                  強制終了させると TSTask・TSTaskCentre の動作がおかしくなる場合があるので、できるだけオフにしておく事をおすすめします<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="TSTask_shutdown" value="false" />
<?php	if ($TSTask_shutdown == 'true'){ ?>
                <input id="TSTask_shutdown" name="TSTask_shutdown" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="TSTask_shutdown" name="TSTask_shutdown" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="TSTask_shutdown" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>UDP送信時の開始ポート番号</span>
                <p>
                  通常は変更する必要はありません<br>
                  エンコーダーがすぐ落ちてしまう場合、UDP送信ポートが他のソフトとバッティングしている可能性があります<br>
                  その場合は、UDP送信ポートを空いているポートに変更してください<br>
                </p>
              </div>
              <input class="text-box" name="udp_port" type="number" min="1" max="40000" placeholder="8200" value="<?php echo $udp_port; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ライブ再生時)</span>
                <p>
                  通常は変更する必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 5 (秒) や 10 (秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  新規インストール時のデフォルトは 1 (秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlslive_time" type="number" min="1" max="60" placeholder="1" value="<?php echo $hlslive_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ファイル再生時)</span>
                <p>
                  通常は変更する必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 10 (秒) や 15 (秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  新規インストール時のデフォルトは 5 (秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlsfile_time" type="number" min="1" max="60" placeholder="5" value="<?php echo $hlsfile_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ再生時に HLS プレイリストに載せるセグメントの個数</span>
                <p>
                  通常は変更する必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 5 (個) や 10 (個) などに設定することで、安定して再生できる場合があります<br>
                  ファイル再生時は全てのセグメントをプレイリストに載せています<br>
                  新規インストール時のデフォルトは 4 (個) です<br>
                </p>
              </div>
              <input class="text-box" name="hlslive_list" type="number" min="1" max="60" placeholder="4" value="<?php echo $hlslive_list; ?>" required />
            </div>

          </form>
<?php	} // 括弧終了 ?>

<?php	} else { // POSTの場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

<?php		if (!isset($_POST['setting-env'])){ ?>
<?php			if ($ini[$stream]['state'] == 'ONAir' or $ini[$stream]['state'] == 'File'){ ?>
          <h3 class="blue">
            <i class="fas fa-video"></i>ストリーム開始
<?php			} else { ?>
          <h3 class="red">
            <i class="fas fa-video"></i>ストリーム終了
<?php			} //括弧終了 ?>
          </h3>

          <div class="setting-form-wrap">
            <p>ストリーム設定を保存しました。</p>
<?php			if ($ini[$stream]['state'] == 'ONAir' or $ini[$stream]['state'] == 'File'){ ?>
            <p>
              ストリームを開始します。<br>
              なお、ストリームの起動には数秒かかります。<br>
              再生が開始されない場合、数秒待ってからリロードしてみて下さい。<br>
            </p>
<?php			} else { ?>
            <p>ストリーミングを終了します。</p>
<?php			} //括弧終了 ?>
            <p>稼働状態：<?php echo $ini[$stream]['state']; ?></p>
            <p>ストリーム：<?php echo $stream; ?></p>
<?php			if ($ini[$stream]['state'] == 'ONAir'){ ?>
            <p>チャンネル：<?php echo $ch[$ini[$stream]['channel']]; ?></p>
            <p>動画の画質：<?php echo $ini[$stream]['quality']; ?></p>
            <p>エンコーダー：<?php echo $ini[$stream]['encoder']; ?></p>
            <p>字幕の表示：<?php echo $ini[$stream]['subtitle']; ?></p>
            <p>使用BonDriver：<?php echo $ini[$stream]['BonDriver']; ?></p>
            <p>エンコードコマンド：<?php echo $stream_cmd; ?></p>
            <p>TSTask起動コマンド：<?php echo $tstask_cmd; ?></p>

<?php			} else if ($ini[$stream]['state'] == 'File'){ ?>
            <p>タイトル：<?php echo $ini[$stream]['filetitle']; ?></p>
            <p>動画の画質：<?php echo $ini[$stream]['quality']; ?></p>
            <p>エンコーダー：<?php echo $ini[$stream]['encoder']; ?></p>
            <p>エンコードコマンド：<?php echo $stream_cmd; ?></p>
<?php			} //括弧終了 ?>
          
<?php		} else if (!($reverse_proxy and $setting_hide == 'true')){ ?>
          <div class="setting-form-wrap">
            <p>環境設定を保存しました。</p>
<?php			foreach ($_POST as $key => $value) { ?>
            <p><?php echo $key; ?>：<?php echo $value; ?></p>
<?php			} //括弧終了 ?>

<?php		} //括弧終了 ?>
            <div id="button-box">
              <button class="redbutton" type="button" onclick="location.href='/'"><i class="fas fa-home"></i>ホームに戻る</button>
            </div>
          </div>

<?php	} //括弧終了 ?>
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