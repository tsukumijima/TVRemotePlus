<?php

	// レスポンスをバッファに貯める
	ob_start();

	// ヘッダー読み込み
	require_once ('../modules/header.php');
  
	// モジュール読み込み
	require_once ('../modules/stream.php');

	echo '    <pre id="debug">';

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
        = initBonChannel($BonDriver_dir);
	
	// 時計
	$clock = date('Y/m/d H:i:s');

	// POSTでフォームが送られてきた場合
	if ($_SERVER['REQUEST_METHOD'] == 'POST'){

		if (!isset($_POST['_csrf_token']) || $_POST['_csrf_token'] !== $csrf_token) {
			trigger_error('Csrf token error', E_USER_ERROR);
		}

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

		// 環境設定設定時に処理を行わないようにする
		// 2行目の条件文は重複してストリームを再起動しないための措置
		if ((!isset($_POST['restart']) and !isset($_POST['setting-env'])) or 
			(isset($_POST['restart']) and !isset($_POST['setting-env']) and time() - filemtime($segment_folder.'stream'.$stream.'.m3u8') > 20)){

			// File
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
				else $ini[$stream]['quality'] = getQualityDefault();
				if ($_POST['encoder']) $ini[$stream]['encoder'] = $_POST['encoder'];
				else $ini[$stream]['quality'] = $encoder_default;
				if ($_POST['subtitle']) $ini[$stream]['subtitle'] = $_POST['subtitle'];
				else $ini[$stream]['quality'] = $subtitle_default;

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

				// MP4・MKV(progressive)は除外
				if (!(($ini[$stream]['fileext'] == 'mp4' or $ini[$stream]['fileext'] == 'mkv') and $ini[$stream]['encoder'] == 'Progressive')){

					// ストリーミング開始
					$stream_cmd = stream_file($stream, $TSfile_dir.'/'.$ini[$stream]['filepath'], $ini[$stream]['fileext'], $ini[$stream]['quality'], $ini[$stream]['encoder'], $ini[$stream]['subtitle']);

					// 準備中用の動画を流すためにm3u8をコピー
					if ($silent == 'true'){
						copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
					} else {
						copy($standby_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
					}

				} else {

					$stream_cmd = 'Progressive';
				}

			// ON Air
			} else if ($ini[$stream]['state'] == 'ONAir'){

				// 連想配列に格納
				if (isset($_POST['channel'])) $ini[$stream]['channel'] = strval($_POST['channel']);
				if (isset($_POST['quality'])) $ini[$stream]['quality'] = $_POST['quality'];
				else $ini[$stream]['quality'] = getQualityDefault();
				if (isset($_POST['encoder'])) $ini[$stream]['encoder'] = $_POST['encoder'];
				else $ini[$stream]['encoder'] = $encoder_default;
				if (isset($_POST['subtitle'])) $ini[$stream]['subtitle'] = $_POST['subtitle'];
				else $ini[$stream]['subtitle'] = $subtitle_default;
				if (isset($_POST['BonDriver'])) $ini[$stream]['BonDriver'] = $_POST['BonDriver'];

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

			// Offline
			} else if ($_POST['state'] == 'Offline'){

				// このストリームを終了
				if (!isset($_POST['allstop'])){
					
					// 現在のストリームを終了する
					stream_stop($stream);

					// Offline に設定する
					$ini[$stream]['state'] = 'Offline';
					$ini[$stream]['channel'] = '0';

					// 配信休止中用のプレイリスト (Stream 1のみ)
					if ($stream == '1'){
						if ($silent == 'true'){
							copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
						} else {
							copy($offline_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
						}
					// Stream 1 以外なら配列のキーごと削除する
					// m3u8 も削除
					} else {
						unset($ini[$stream]);
						@unlink($base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
					}

				// 全てのストリームを終了
				} else {

					// 全てのストリームを終了する
					stream_stop($stream, true);

					// ストリーム番号ごとに実行
					foreach ($ini as $key => $value) {

						$key = strval($key);
					
						// 全てのストリームを Offline に設定する
						$ini[$key]['state'] = 'Offline';
						$ini[$key]['channel'] = '0';

						// 配信休止中用のプレイリスト (Stream 1のみ)
						if ($key == '1'){
							if ($silent == 'true'){
								copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream'.$key.'.m3u8');
							} else {
								copy($offline_m3u8, $base_dir.'htdocs/stream/stream'.$key.'.m3u8');
							}
						// Stream 1 以外なら配列のキーごと削除する
						// m3u8 も削除
						} else {
							unset($ini[$key]);
							@unlink($base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
						}
					}
				}
			}

			// 昇順にソート
			ksort($ini);

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

					// PHPの文字列リテラルにあると面倒な文字を取り除く
					$value = str_replace(array("\n", "\r", '\'', '"'), '', $value);

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
					
					// キーに不正な文字がなければ
					if (preg_match('/[^0-9A-Za-z_]/', $key) === 0) {
						// config.php を書き換え
						$tvrp_conf = preg_replace("/^\\$$key =.*;/m", '$'.$key.' = '.$set.';', $tvrp_conf); // 置換
					}

				}
				
				// ファイル書き込み
				file_put_contents($tvrp_conf_file, $tvrp_conf);

			}
		}
	}

	echo '</pre>';

	// 溜めてあった出力を解放しフラッシュする
	ob_end_flush();
	ob_flush();
	flush();

?>

      <div class="information">
        <div id="setting">
<?php	if ($_SERVER["REQUEST_METHOD"] != "POST"){ // ブラウザからHTMLページを要求された場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

          <p>
            <?= $site_title; ?> の設定ができます。<br>
          </p>
          
          <form id="setting-user" class="setting-form-wrap">

            <input type="hidden" name="setting-user" value="true" />

            <h3 class="blue">
              <i class="fas fa-user-cog"></i>個人設定
              <div id="button-box">
                <button class="bluebutton setting" type="submit">
                  <i class="fas fa-save"></i>保存する
                </button>
              </div>
            </h3>

            <p>個人設定はブラウザ・端末ごとに反映されます。</p>
            <p>(＊) … PC・タブレットのみ適用される設定</p>

            <h4><i class="fas fa-eye"></i>表示</h4>

            <div class="setting-form">
              <span>Twitter 投稿</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('twitter_show', true, true) !== false){ ?>
                <input id="twitter_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="twitter_show" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="twitter_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>コメントリスト (＊)</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('comment_show', true, true) !== false){ ?>
                <input id="comment_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="comment_show" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="comment_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>ダークモード</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('dark_theme', true) !== false){ ?>
                <input id="dark_theme" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="dark_theme" class="toggle-input" type="checkbox" value="true" /> 
<?php	} // 括弧終了 ?>
                <label for="dark_theme" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>サブチャンネル</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('subchannel_show', true) !== false){ ?>
                <input id="subchannel_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="subchannel_show" class="toggle-input" type="checkbox" value="true" /> 
<?php	} // 括弧終了 ?>
                <label for="subchannel_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>録画番組のリスト表示</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('list_view', true) !== false){ ?>
                <input id="list_view" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="list_view" class="toggle-input" type="checkbox" value="true" /> 
<?php	} // 括弧終了 ?>
                <label for="list_view" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>番組表に局ロゴを表示</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('logo_show', true, true) !== false){ ?>
                <input id="logo_show" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="logo_show" class="toggle-input" type="checkbox" value="true" /> 
<?php	} // 括弧終了 ?>
                <label for="logo_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>ナビゲーションメニューを垂直に配置 (＊)</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('vertical_navmenu', true, false) !== false){ ?>
                <input id="vertical_navmenu" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="vertical_navmenu" class="toggle-input" type="checkbox" value="true" /> 
<?php	} // 括弧終了 ?>
                <label for="vertical_navmenu" class="toggle-label"></label>
              </div>
            </div>

            <h4><i class="fas fa-comment-alt"></i>コメント</h4>

            <div class="setting-form setting-select">
              <span>コメントのフォントサイズ</span>
              <div class="select-wrap">
                <select id="comment_size" required>
<?php	if (isSettingsItem('comment_size', '42') !== false){ ?>
                  <option value="42" selected>大きめ</option>
                  <option value="35">ふつう</option>
                  <option value="28">小さめ</option>
<?php	} else if (isSettingsItem('comment_size', '35') !== false){ ?>
                  <option value="42">大きめ</option>
                  <option value="35" selected>ふつう</option>
                  <option value="28">小さめ</option>
<?php	} else if (isSettingsItem('comment_size', '28') !== false){ ?>
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
              <span>コメントの遅延時間（ライブ配信・秒）</span>
<?php	if (isSettingsItem('comment_delay') !== false){ ?>
              <input class="text-box" id="comment_delay" type="number" min="0" max="120" placeholder="5" value="<?= isSettingsItem('comment_delay'); ?>" required />
<?php	} else { ?>
              <input class="text-box" id="comment_delay" type="number" min="0" max="120" placeholder="5" value="5" required />
<?php	} // 括弧終了 ?>
            </div>

            <div class="setting-form setting-select">
              <span>コメントの遅延時間（ファイル再生・秒）</span>
<?php	if (isSettingsItem('comment_file_delay') !== false){ ?>
              <input class="text-box" id="comment_file_delay" type="number" min="0" max="120" placeholder="0" value="<?= isSettingsItem('comment_file_delay'); ?>" required />
<?php	} else { ?>
              <input class="text-box" id="comment_file_delay" type="number" min="0" max="120" placeholder="0" value="0" required />
<?php	} // 括弧終了 ?>
            </div>

            <div class="setting-form setting-select">
              <span>コメントリストのパフォーマンス（ファイル再生のみ）</span>
              <div class="select-wrap">
                <select id="comment_list_performance" required>
<?php	if (isSettingsItem('comment_list_performance', 'normal') !== false){ ?>
                  <option value="light">軽量</option>
                  <option value="normal" selected>標準</option>
<?php	} else if (isSettingsItem('comment_list_performance', 'light') !== false){ ?>
                  <option value="light" selected>軽量</option>
                  <option value="normal">標準</option>
<?php	} else { ?>
                  <option value="light" selected>軽量</option>
                  <option value="normal">標準</option>
<?php	} // 括弧終了 ?>
                </select>
              </div>
            </div>

            <h4><i class="fas fa-sliders-h"></i>機能</h4>

            <div class="setting-form setting-select">
              <span>デフォルトの動画の画質（環境設定よりも優先されます）</span>
              <div class="select-wrap">
                <select id="quality_user_default" required>
                  <?php $quality_user_default = isSettingsItem('quality_user_default'); ?>
                  <option value="environment"<?php if ($quality_user_default == 'environment') echo ' selected'; ?>>環境設定を引き継ぐ</option>
                  <option value="1080p-high"<?php if ($quality_user_default == '1080p-high') echo ' selected'; ?>>1080p-high (1920×1080)</option>
                  <option value="1080p"<?php if ($quality_user_default == '1080p') echo ' selected'; ?>>1080p (1440×1080)</option>
                  <option value="810p"<?php if ($quality_user_default == '810p') echo ' selected'; ?>>810p (1440×810)</option>
                  <option value="720p"<?php if ($quality_user_default == '720p') echo ' selected'; ?>>720p (1280×720)</option>
                  <option value="540p"<?php if ($quality_user_default == '540p') echo ' selected'; ?>>540p (960×540)</option>
                  <option value="360p"<?php if ($quality_user_default == '360p') echo ' selected'; ?>>360p (640×360)</option>
                  <option value="240p"<?php if ($quality_user_default == '240p') echo ' selected'; ?>>240p (426×240)</option>
                  <option value="144p"<?php if ($quality_user_default == '144p') echo ' selected'; ?>>144p (256×144)</option>
                </select>
              </div>
            </div>

            <div class="setting-form setting-select">
              <span>一度に表示する録画番組リストの番組数（件）</span>
<?php	if (isSettingsItem('list_view_number') !== false){ ?>
              <input class="text-box" id="list_view_number" type="number" min="10" max="100" placeholder="30" value="<?= isSettingsItem('list_view_number'); ?>" required />
<?php	} else { ?>
              <input class="text-box" id="list_view_number" type="number" min="10" max="100" placeholder="30" value="30" required />
<?php	} // 括弧終了 ?>
            </div>

            <div class="setting-form">
              <span>デフォルト設定を使い 1 クリックでストリームを開始する</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('onclick_stream', true) !== false){ ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="onclick_stream" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>番組表へスクロールした時にプレイヤーをフローティング表示する (＊)</span>
              <div class="toggle-switch">
<?php	if (isSettingsItem('player_floating', true) !== false){ ?>
                <input id="player_floating" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="player_floating" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="player_floating" class="toggle-label"></label>
              </div>
            </div>

          </form>
<?php	if (!($reverse_proxy and $setting_hide == 'true')){ ?>

          <form id="setting-env" class="setting-form-wrap">
          
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="setting-env" value="true" />

            <h3 class="red">
              <i class="fas fa-tools"></i>環境設定
              <div id="button-box">
                <button class="redbutton setting" type="submit">
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
                  240p は画質は低くなりますが、ワンセグよりも高画質で低通信量にて視聴できます<br>
                  144p はさらに画質が低くなりますが、高音質で通信量を 約3.8MB/分 まで削減できます<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="quality_default" required>
                  <option value="1080p-high"<?php if ($quality_default == '1080p-high') echo ' selected'; ?>>1080p-high (1920×1080)</option>
                  <option value="1080p"<?php if ($quality_default == '1080p' or $quality_default == '') echo ' selected'; ?>>1080p (1440×1080)</option>
                  <option value="810p"<?php if ($quality_default == '810p') echo ' selected'; ?>>810p (1440×810)</option>
                  <option value="720p"<?php if ($quality_default == '720p') echo ' selected'; ?>>720p (1280×720)</option>
                  <option value="540p"<?php if ($quality_default == '540p') echo ' selected'; ?>>540p (960×540)</option>
                  <option value="360p"<?php if ($quality_default == '360p') echo ' selected'; ?>>360p (640×360)</option>
                  <option value="240p"<?php if ($quality_default == '240p') echo ' selected'; ?>>240p (426×240)</option>
                  <option value="144p"<?php if ($quality_default == '144p') echo ' selected'; ?>>144p (256×144)</option>
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
                  QSVEncC は Intel 製の一部の GPU 、NVEncC は nvidia 製の GPU 、VCEEncC は AMD の Radeon GPU でしか利用できません<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="encoder_default" required>
                  <option value="ffmpeg"<?php if ($encoder_default == 'ffmpeg' or $encoder_default == '') echo ' selected'; ?>>ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC"<?php if ($encoder_default == 'QSVEncC') echo ' selected'; ?>>QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC"<?php if ($encoder_default == 'NVEncC') echo ' selected'; ?>>NVEncC (ハードウェアエンコーダー)</option>
                  <option value="VCEEncC"<?php if ($encoder_default == 'VCEEncC') echo ' selected'; ?>>VCEEncC (ハードウェアエンコーダー)</option>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトの BonDriver (地デジ用)</span>
                <p>
                  デフォルトで利用する BonDriver (地デジ用) です<br>
                  うまく再生出来ない場合、BonDriver_Spinel もしくは BonDriver_Proxy を利用すると安定して視聴できる場合があります<br>
                  Spinel よりも BonDriverProxyEx の方がストリーム開始にかかる時間は短くなります<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="BonDriver_default_T">
<?php		foreach ($BonDriver_dll_T as $i => $value){ //chの数だけ繰り返す ?>
<?php			if ($value == $BonDriver_default_T){ ?>
                  <option value="<?= $value; ?>" selected><?= $value; ?></option>
<?php			} else { ?>
                  <option value="<?= $value; ?>"><?= $value; ?></option>
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
                  BonDriver_Spinel よりも BonDriver_Proxy の方がストリーム開始にかかる時間は短くなります<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="BonDriver_default_S">
<?php		foreach ($BonDriver_dll_S as $i => $value){ //chの数だけ繰り返す ?>
<?php			if ($value == $BonDriver_default_S){ ?>
                  <option value="<?= $value; ?>" selected><?= $value; ?></option>
<?php			} else { ?>
                  <option value="<?= $value; ?>"><?= $value; ?></option>
<?php			} //括弧終了 ?>
<?php		} //括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ配信開始時に現在視聴中のストリームをデフォルトのストリームにする</span>
                <p>
                  この設定をオンにすると、現在視聴中のストリームをライブ配信を開始するときのデフォルトのストリームにします（同時配信機能が追加される前の動作に近い）<br>
                  この設定をオフにすると、ライブ配信開始時点で空いているストリームをデフォルトのストリームにし、配信中のストリームを選択しないようにします<br>
                  個人設定の [デフォルト設定を使い1クリックでストリームを開始する] をオンにしている場合は、自動でデフォルトに設定されているストリームでライブ配信を開始します<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="stream_current_live" value="false" />
<?php	if ($stream_current_live == 'true'){ ?>
                <input id="stream_current_live" name="stream_current_live" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="stream_current_live" name="stream_current_live" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="stream_current_live" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ファイル再生開始時に常にメインストリームをデフォルトのストリームにする</span>
                <p>
                  この設定をオンにすると、メインストリーム (Stream 1) をファイル再生を開始するときのデフォルトのストリームにします（同時配信機能が追加される前の動作に近い）<br>
                  この設定をオフにすると、ファイル再生開始時点で空いているストリームをデフォルトのストリームにし、配信中のストリームを選択しないようにします<br>
                  個人設定の [デフォルト設定を使い1クリックでストリームを開始する] をオンにしている場合は、自動でデフォルトに設定されているストリームでライブ配信を開始します<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="stream_current_file" value="false" />
<?php	if ($stream_current_file == 'true'){ ?>
                <input id="stream_current_file" name="stream_current_file" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="stream_current_file" name="stream_current_file" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="stream_current_file" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ配信時にデフォルトで字幕をストリームに含める</span>
                <p>
                  この設定をオンにすると、ライブ配信時に字幕を表示出来るようになります<br>
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
                  ファイル再生時は、基本的にライブ配信時のようなエンコードの問題は起こりません<br>
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
                <span>録画ファイルのあるフォルダ</span>
                <p>
                  ファイル再生の際に利用します<br>
                  UNC パスなど、特殊なパスは認識できないかもしれません<br>
                </p>
              </div>
              <input class="text-box" name="TSfile_dir" type="text" value="<?= $TSfile_dir; ?>" placeholder="E:/TV-Record/" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>番組情報ファイルのあるフォルダ</span>
                <p>
                  ファイル再生の際、番組情報が録画ファイルから取得できない場合 ( MP4 ファイル等) に利用します<br>
                  フォルダを指定しない場合、録画ファイルと同じファイル名の .ts.program.txt を参照します<br>
                </p>
              </div>
              <input class="text-box" name="TSinfo_dir" type="text" value="<?= $TSinfo_dir; ?>" placeholder="E:/TV-Record/録画情報/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>EDCB Material WebUI (EMWUI) のある URL</span>
                <p>
                  番組表取得などで利用します<br>
                  この機能を利用する場合、予め <a href="https://github.com/EMWUI/EDCB_Material_WebUI" target="_blank">EDCB Material WebUI</a> を導入しておいてください<br>
                  以前は http://192.168.x.xx:5510/api/ のように指定していましたが、変更になりました<br>
                  TVRock 等を利用している場合、<a href="http://vb45wb5b.seesaa.net/" target="_blank">TVRemoteViewer_VB</a> 2.93m（再うｐ版）以降を導入し TVRemoteViewer_VB の URL（例：http://192.168.x.xx:40003/ ）を代わりに設定することで番組情報が表示できるようになります<br>
                </p>
              </div>
              <input class="text-box" name="EDCB_http_url" type="url" value="<?= $EDCB_http_url; ?>" placeholder="http://192.168.x.xx:5510/" />
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
              <input class="text-box" name="reverse_proxy_url" type="url" value="<?= $reverse_proxy_url; ?>" placeholder="https://example.com/tvrp/" />
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
                  新規インストール時のデフォルトは15件です<br>
                </p>
              </div>
              <input class="text-box" name="history_keep" type="number" min="1" max="100" placeholder="15" value="<?= $history_keep; ?>" required />
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
                  ニコニコ実況へのコメントの投稿に必須です（過去ログ再生では不要になりました）<br>
                  利用する場合、予めニコニコアカウントを作成しておく必要があります<br>
                  ログインしなくても生放送のコメントは取得できますが、コメント投稿はできません<br>
                  また、同時視聴者数が多くなった場合に追い出されやすくなります<br>
                </p>
              </div>
              <input class="text-box" name="nicologin_mail" type="email" value="<?= $nicologin_mail; ?>" placeholder="example@gmail.com" autocomplete="off" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ニコニコにログインする際のパスワード</span>
                <p>
                  ニコニコ実況へのコメントの投稿に必須です（過去ログ再生では不要になりました）<br>
                  利用する場合、予めニコニコアカウントを作成しておく必要があります<br>
                  ログインしなくても生放送のコメントは取得できますが、コメント投稿はできません<br>
                  また、同時視聴者数が多くなった場合に追い出されやすくなります<br>
                </p>
              </div>
              <div class="password-box-wrap">
                <input class="password-box" name="nicologin_password" type="password" value="<?= $nicologin_password; ?>" placeholder="password" autocomplete="new-password" />
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
              <input class="text-box" name="tweet_time" type="number" min="0" max="120" placeholder="60" value="<?= $tweet_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>画像付きツイートを投稿する時に一度アップロードする画像の保存フォルダ</span>
                <p>
                空に設定すると、自動で (TVRemotePlusをインストールしたフォルダ)/data/upload/ に保存されます<br>
                ずっと画像付きツイートをしているとそこそこのファイルサイズになるので、適宜録画用の HDD 内のフォルダを指定しておくのも良いと思います<br>
                </p>
              </div>
              <input class="text-box" name="tweet_upload" type="text" value="<?= $tweet_upload; ?>" placeholder="E:/TV-Capture/" />
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
              <input class="text-box" name="CONSUMER_KEY" type="text" pattern="[A-Za-z0-9]{25}" value="<?= $CONSUMER_KEY; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXX" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TwitterAPI のコンシューマーシークレット (Consumer Secret)</span>
                <p>
                TVRemotePlus からのツイート投稿に必須です<br>
                コンシューマーシークレットは50文字のランダムな英数字です<br>
                </p>
              </div>
              <input class="text-box" name="CONSUMER_SECRET" type="text" pattern="[A-Za-z0-9]{50}" value="<?= $CONSUMER_SECRET; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
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
              <input class="text-box" name="basicauth_user" type="text" pattern="^[0-9A-Za-z]+$" value="<?= $basicauth_user; ?>" placeholder="user" required autocomplete="off" />
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
                <input class="password-box" name="basicauth_password" type="password" value="<?= $basicauth_password; ?>" placeholder="password" required autocomplete="new-password" />
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
                <span>エンコーダーのログをファイルに書き出す</span>
                <p>
                  この設定がオンの場合、エンコーダーのログを (TVRemotePlus)/logs/stream(ストリーム番号).encoder.log に書き出します（デフォルトはオンです）<br>
                  エンコーダーが途中で落ちる場合はこの設定をオンにし、logs フォルダに書き出されたログを確認してみてください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="encoder_log" value="false" />
<?php	if ($encoder_log == 'true'){ ?>
                <input id="encoder_log" name="encoder_log" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="encoder_log" name="encoder_log" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="encoder_log" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>エンコーダーのウインドウを表示する</span>
                <p>
                  この設定がオンの場合、エンコーダーのコンソールウインドウを表示します（デフォルトはオフです）<br>
                  [エンコーダーのログをファイルに書き出す] がオンの場合は、コンソールウインドウには何も出力されなくなります<br>
                  エンコードが不安定な場合はこの設定をオンにした上で [エンコーダーのログをファイルに書き出す] をオフにし、ウインドウが表示される（起動されている）かどうか、エンコードが止まっていないかを確認してみてください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="encoder_window" value="false" />
<?php	if ($encoder_window == 'true'){ ?>
                <input id="encoder_window" name="encoder_window" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="encoder_window" name="encoder_window" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="encoder_window" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TSTask の起動時に TSTaskCentre も起動する</span>
                <p>
                  この設定がオンの場合、TSTask の起動時に TSTaskCentre（TSTask のクライアントプログラム）も一緒に起動します（デフォルトはオフです）<br>
                  正常に放送が受信できているか、TSTask が起動できているか確認したい場合などにオンにしてみてください<br>
                </p>
              </div>
              <div class="toggle-switch">
                <input type="hidden" name="TSTask_window" value="false" />
<?php	if ($TSTask_window == 'true'){ ?>
                <input id="TSTask_window" name="TSTask_window" class="toggle-input" type="checkbox" value="true" checked />
<?php	} else { ?>
                <input id="TSTask_window" name="TSTask_window" class="toggle-input" type="checkbox" value="true" />
<?php	} // 括弧終了 ?>
                <label for="TSTask_window" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ストリーム終了時に TSTask を強制終了する</span>
                <p>
                  TSTask が終了できない・ゾンビプロセス化する場合のみオンにしてください<br>
                  強制終了させると TSTaskCentre の動作がおかしくなる場合があるので、できるだけオフにしておく事をおすすめします<br>
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
                <span>UDP 送信時の開始ポート番号</span>
                <p>
                  通常は変更する必要はありません<br>
                  実際に利用されるポート番号は（ここで設定したポート番号）+（選択したストリーム番号）になります<br>
                  エンコーダーがすぐ落ちてしまう場合、UDP 送信ポートが他のソフトとバッティングしている可能性があります<br>
                  その場合は、UDP 送信ポートを空いているポートに変更してください<br>
                </p>
              </div>
              <input class="text-box" name="udp_port" type="number" min="1" max="40000" placeholder="8200" value="<?= $udp_port; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ライブ配信時)</span>
                <p>
                  通常は変更する必要はありませんが、外出先から視聴する場合など回線が不安定な場合、
                  秒数を 5 (秒) や 10 (秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  ただし、秒数を伸ばせば伸ばすほど、放送波との遅延が大きくなってしまいます<br>
                  新規インストール時のデフォルトは 1 (秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlslive_time" type="number" step="0.1" min="0.5" max="60" placeholder="1" value="<?= $hlslive_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ファイル再生時)</span>
                <p>
                  通常は変更する必要はありませんが、外出先から視聴する場合など回線が不安定な場合、
                  秒数を 10 (秒) や 20 (秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  ただし、秒数を伸ばせば伸ばすほど、再生開始までにかかる待機時間が長くなります<br>
                  新規インストール時のデフォルトは 8 (秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlsfile_time" type="number" step="0.1" min="0.5" max="60" placeholder="8" value="<?= $hlsfile_time; ?>" required />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ配信時に HLS プレイリストに載せるセグメントの個数</span>
                <p>
                  通常は変更する必要はありませんが、外出先から視聴する場合など回線が不安定な場合、
                  秒数を 10 (個) や 20 (個) などに設定することで、安定して再生できる場合があります<br>
                  ただし、秒数を伸ばせば伸ばすほど、放送波との遅延が大きくなってしまいます<br>
                  ファイル再生時は全てのセグメントをプレイリストに載せています<br>
                  新規インストール時のデフォルトは 8 (個) です<br>
                </p>
              </div>
              <input class="text-box" name="hlslive_list" type="number" min="1" max="60" placeholder="8" value="<?= $hlslive_list; ?>" required />
            </div>

		  </form>
		  

		  <?php	if (!$reverse_proxy){ ?>
          <div id="setting-other" class="setting-form-wrap">

            <h3 class="green"><i class="fas fa-tablet-alt"></i>PWA・HTTPS</h3>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>HTTPS アクセス用の自己署名証明書のダウンロード</span>
                <p>
                  PWA (Progressive Web Apps) 機能を利用する場合は、HTTPS でのアクセスが必須です<br>
                  そのため、インストール時に作成した自己署名証明書を予め TVRemotePlus を利用する端末にインポートしておく必要があります<br>
                  右 or 下のダウンロードボタンから証明書 (server.crt) をダウンロードしてください<br>
                  証明書のインストール手順は <a href="https://github.com/tsukumijima/TVRemotePlus#pwa-%E3%81%AE%E3%82%A4%E3%83%B3%E3%82%B9%E3%83%88%E3%83%BC%E3%83%AB%E6%89%8B%E9%A0%86" target="_blank">こちら</a> を参照してください<br>
                </p>
              </div>
              <a class="download" href="/files/TVRemotePlus.crt" download>
                <i class="fas fa-download"></i>
              </a>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>HTTPS 用 URL にアクセス</span>
                <p>
                  HTTPS 用 URL で TVRemotePlus にアクセスできます<br>
                  Chrome (iPhone・iPad は Safari) で TVRemotePlus にアクセスし、Android は「TVRemotePlus をホーム画面に追加」から、iPhone・iPad は共有ボタンから
                  PC は URL バーの横に「インストール」と出てくるので、それを押してホーム画面やデスクトップに追加し、そこから起動すると PWA モードでネイティブアプリのように利用できます<br>
                  HTTPS アクセスの方が上位互換なので、自己署名証明書をインポートした端末では普段も HTTPS でアクセスする事をお勧めします<br>
                </p>
              </div>
              <a class="download" href="https://<?= $_SERVER['SERVER_NAME']; ?>:<?= $https_port; ?>/">
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
              <a class="download" href="<?= $reverse_proxy_url; ?>">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
<?php		} // 括弧終了 ?>
            
          </div>
<?php	} // 括弧終了 ?>
<?php	} // 括弧終了 ?>

<?php	} else { // POSTの場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

<?php		if (!isset($_POST['setting-env'])){ ?>
<?php			if ($_POST['state'] == 'ONAir' or $_POST['state'] == 'File'){ ?>
          <h3 class="blue">
            <i class="fas fa-video"></i>ストリーム開始
<?php			} else { ?>
          <h3 class="red">
            <i class="fas fa-video"></i>ストリーム終了
<?php			} //括弧終了 ?>
          </h3>

          <div class="setting-form-wrap">
            <p>ストリーム設定を保存しました。</p>
<?php			if ($_POST['state'] == 'ONAir' or $_POST['state'] == 'File'){ ?>
            <p>
              ストリームを開始します。<br>
              なお、ストリームの起動には数秒かかります。<br>
              再生が開始されない場合、数秒待ってからリロードしてみて下さい。<br>
            </p>
<?php			} else { ?>
            <p>ストリームを終了します。</p>
<?php			} //括弧終了 ?>
            <p>稼働状態：<?= $_POST['state']; ?></p>
<?php			if (!isset($_POST['allstop'])){ ?>
            <p>ストリーム：Stream <?= $stream; ?></p>
<?php			} else { ?>
            <p>ストリーム：全てのストリーム</p>
<?php			} //括弧終了 ?>
<?php			if ($_POST['state'] == 'ONAir'){ ?>
            <p>チャンネル：<?= $ch[$ini[$stream]['channel']]; ?></p>
            <p>動画の画質：<?= $ini[$stream]['quality']; ?></p>
            <p>エンコーダー：<?= $ini[$stream]['encoder']; ?></p>
            <p>字幕の表示：<?= $ini[$stream]['subtitle']; ?></p>
            <p>使用 BonDriver：<?= $ini[$stream]['BonDriver']; ?></p>
            <p>エンコードコマンド：<?= $stream_cmd; ?></p>
            <p>TSTask 起動コマンド：<?= $tstask_cmd; ?></p>

<?php			} else if ($_POST['state'] == 'File'){ ?>
            <p>タイトル：<?= $ini[$stream]['filetitle']; ?></p>
            <p>動画の画質：<?= $ini[$stream]['quality']; ?></p>
            <p>エンコーダー：<?= $ini[$stream]['encoder']; ?></p>
            <p>エンコードコマンド：<?= $stream_cmd; ?></p>
<?php			} //括弧終了 ?>
          
<?php		} else if (!($reverse_proxy and $setting_hide == 'true')){ ?>
          <div class="setting-form-wrap">
            <p>環境設定を保存しました。</p>
<?php			foreach ($_POST as $key => $value) { ?>
            <p><?= $key; ?>：<?= $value; ?></p>
<?php			} //括弧終了 ?>

<?php		} //括弧終了 ?>
            <div id="button-box">
              <button class="redbutton" type="button" onclick="location.href='<?= $site_url.$stream; ?>/'"><i class="fas fa-home"></i>ホームに戻る</button>
            </div>
          </div>

<?php	} //括弧終了 ?>
        </div>
      </div>
    </div>

    <div id="scroll">
      <i class="fas fa-arrow-up"></i>
    </div>
    <div id="save">
      <i class="fas fa-save"></i>
    </div>

  </section>
	
  <section id="footer">
    <?= $site_title.' '.$version; ?>

  </section>
</body>

</html>