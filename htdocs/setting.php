<?php

	// モジュール読み込み
	require_once ('../module.php');
  require_once ('../stream.php');
  
  // ヘッダー読み込み
  require_once ('../header.php');
  
  echo '    <pre id="debug">';

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
				if ($ini['filepath'] == $TSfile['data'][$key]['file']){
					$history['data'][$history_count]['play'] = time();
					$history['data'][$history_count]['file'] = $TSfile['data'][$key]['file'];
					$history['data'][$history_count]['title'] = $TSfile['data'][$key]['title'];
					$history['data'][$history_count]['update'] = $TSfile['data'][$key]['update'];
					$history['data'][$history_count]['thumb'] = $TSfile['data'][$key]['thumb'];
					$history['data'][$history_count]['data'] = $TSfile['data'][$key]['data'];
					$history['data'][$history_count]['date'] = $TSfile['data'][$key]['date'];
					$history['data'][$history_count]['info'] = $TSfile['data'][$key]['info'];
					$history['data'][$history_count]['channel'] = $TSfile['data'][$key]['channel'];
					$history['data'][$history_count]['start'] = $TSfile['data'][$key]['start'];
					$history['data'][$history_count]['end'] = $TSfile['data'][$key]['end'];
					$history['data'][$history_count]['duration'] = $TSfile['data'][$key]['duration'];
					$history['data'][$history_count]['start_timestamp'] = $TSfile['data'][$key]['start_timestamp'];
					$history['data'][$history_count]['end_timestamp'] = $TSfile['data'][$key]['end_timestamp'];
				}
			}

			// 再生履歴をファイルに保存
			file_put_contents($historyfile, json_encode($history, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			// ストリーミング開始
			stream_file($ini['filepath'], $ini['quality'], $ini['encoder']);

			// 準備中用の動画を流すためにm3u8をコピー
			if ($silent == 'true'){
				copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			} else {
				copy($standby_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			}

		} else if ($ini['state'] == "ONAir"){

			// 連想配列に格納
			if ($_POST['channel']) $ini['channel'] = strval($_POST['channel']);
			if ($_POST['quality']) $ini['quality'] = $_POST['quality'];
			if ($_POST['encoder']) $ini['encoder'] = $_POST['encoder'];
			if ($_POST['subtitle']) $ini['subtitle'] = $_POST['subtitle'];
			if ($_POST['BonDriver']) $ini['BonDriver'] = $_POST['BonDriver'];

			// BonDriverのデフォルトを要求される or 何故かBonDriverが空
			if ($ini['BonDriver'] == 'default' or empty($ini['BonDriver'])){
				if (intval($ini['channel']) >= 100 or intval($ini['channel']) == 55){ // チャンネルの値が100より(=BSか)
					$ini['BonDriver'] = $BonDriver_default_S;
				} else { // 地デジなら
					$ini['BonDriver'] = $BonDriver_default_T;
				}
			}

			// ストリーミング開始
			stream_start($ini['channel'], $sid[$ini['channel']], $tsid[$ini['channel']], $ini['BonDriver'], $ini['quality'], $ini['encoder'], $ini['subtitle']);

			// 準備中用の動画を流すためにm3u8をコピー
			if ($silent == 'true'){
				copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			} else {
				copy($standby_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			}

		// Offlineなら
		} else if ($ini['state'] == "Offline"){

			// 念のためもう一回ストリーミング終了関数を起動
			stream_stop();
				
			// 強制でチャンネルを0に設定する
			$ini['channel'] = '0';
				
			// 配信休止中用のプレイリスト
			if ($silent == 'true'){
				copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			} else {
				copy($offline_m3u8, $base_dir.'htdocs/stream/stream.m3u8');
			}

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
  
  echo '</pre>';

?>

      <div class="information">
        <div id="setting">
<?php	if ($_SERVER["REQUEST_METHOD"] != "POST"){ // ブラウザからHTMLページを要求された場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

          <p>
            ※環境設定は今の所モックアップです。保存しても反映されません。<br>
            個人設定は一応反映出来るようにしました<br>
          </p>

          <div class="setting-form-wrap">

            <h3 class="green"><i class="fas fa-tablet-alt"></i>PWA・https</h3>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>https アクセス用の自己署名証明書をダウンロード</span>
                <p>
                  PWA (Progressive Web Apps) 機能を利用する場合は、https でのアクセスが必須です<br>
                  そのため、インストール時に作成した自己署名証明書を予め TVRemotePlus を利用する端末にインポートしておく必要があります<br>
                  右(もしくは下)のダウンロードボタンから、server.crt をダウンロードしてください<br>
                  なお、「この種類のファイルはコンピュータに損害を与える可能性が～」とか出てくる場合がありますが、安全なので「保存」をクリックします<br>
                  その後、ダウンロードした server.crt をクリックし、PCであれば自己署名証明書を「証明書を全て次のストアに配置する」→「信頼されたルート証明機関」に設定してからインポートします<br>
                </p>
              </div>
              <a class="download" href="/server.crt">
                <i class="fas fa-download"></i>
              </a>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content large">
                <span>https 用 URL にアクセス</span>
                <p>
                  右のボタンから https 用 URL にて TVRemotePlus にアクセスできます<br>
                  Chrome(iOSのみSafari) でアクセスした場合は、Androidは「TVRemotePlusをホーム画面に追加」・iOSはSafariのメニュー真ん中の □に↑ のアイコンから「ホーム画面に追加」
                  PCはURLバーの横に「インストール」と出てくるので、それを押してホーム画面・デスクトップに追加し、そこから起動するとPWAモードでネイティブアプリのように利用できます<br>
                  https アクセスの方が上位互換なので、自己署名証明書をインポートした端末では普段も https でアクセスする事をお勧めします<br>
                </p>
              </div>
              <a class="download" href="https://<?php echo $_SERVER['SERVER_NAME']; ?>:8100/">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
            
          </div>
          
          <div class="setting-form-wrap">

            <h3 class="blue">
              <i class="fas fa-user-cog"></i>個人設定
              <div id="button-box">
                <button class="bluebutton" type="button">
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
                <input id="twitter_show" class="toggle-input" type="checkbox" />
<?php	} else { ?>
                <input id="twitter_show" class="toggle-input" type="checkbox" checked />
<?php	} // 括弧終了 ?>
                <label for="twitter_show" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form">
              <span>コメント一覧</span>
              <div class="toggle-switch">
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['comment_show'] == false){ ?>
                <input id="comment_show" class="toggle-input" type="checkbox" />
<?php	} else { ?>
                <input id="comment_show" class="toggle-input" type="checkbox" checked />
<?php	} // 括弧終了 ?>
                <label for="comment_show" class="toggle-label"></label>
              </div>
            </div>

            <h4><i class="fas fa-sliders-h"></i>機能</h4>

            <div class="setting-form">
              <span>デフォルト設定を使いワンクリックでストリームを開始する</span>
              <div class="toggle-switch">
<?php	if (isset($_COOKIE['settings']) and json_decode($_COOKIE['settings'], true)['onclick_stream'] == true){ ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="onclick_stream" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="onclick_stream" class="toggle-label"></label>
              </div>
            </div>

          </div>

          <div class="setting-form-wrap">

            <h3 class="red">
              <i class="fas fa-tools"></i>環境設定
              <div id="button-box">
                <button class="redbutton" type="button">
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
                  新規インストール時のデフォルトは 810p です<br>
                  240p は画質は低くなりますが、低通信量でワンセグよりも高画質で視聴できます<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="quality_default">
<?php	if ($quality_default == '1080p'){ ?>
                  <option value="1080p" selected>1080p (1920×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '810p'){ ?>
                  <option value="1080p">1080p (1920×1080)</option>
                  <option value="810p" selected>810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '720p'){ ?>
                  <option value="1080p">1080p (1920×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p" selected>720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '540p'){ ?>
                  <option value="1080p">1080p (1920×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p" selected>540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '360p'){ ?>
                  <option value="1080p">1080p (1920×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p" selected>360p (640×360)</option>
                  <option value="240p">240p (426×240)</option>
<?php	} else if ($quality_default == '240p'){ ?>
                  <option value="1080p">1080p (1920×1080)</option>
                  <option value="810p">810p (1440×810)</option>
                  <option value="720p">720p (1280×720)</option>
                  <option value="540p">540p (960×540)</option>
                  <option value="360p">360p (640×360)</option>
                  <option value="240p" selected>240p (426×240)</option>
<?php	} // 括弧終了 ?>
                </select>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトのエンコーダー</span>
                <p>
                  ffmpeg が通常のエンコーダー(ソフトウェアエンコーダー)、
                  QSVEncC・NVEncC がハードウェアエンコーダーです<br>
                  QSVEncC・NVEncC の方が CPU を消費しない・エンコードが早いのでおすすめですが、
                  QSVEncC は Intel 製の一部の CPU・NVEncC は nvidia 製の GPU 環境しか使えません<br>
                </p>
              </div>
              <div class="select-wrap">
                <select name="encoder_default">
<?php	if ($encoder_default == 'ffmpeg'){ ?>
                  <option value="ffmpeg" selected>ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
<?php	} else if ($encoder_default == 'QSVEncC'){ ?>
                  <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC" selected>QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC">NVEncC (ハードウェアエンコーダー)</option>
<?php	} else if ($encoder_default == 'NVEncC'){ ?>
                  <option value="ffmpeg">ffmpeg (ソフトウェアエンコーダー)</option>
                  <option value="QSVEncC">QSVEncC (ハードウェアエンコーダー)</option>
                  <option value="NVEncC" selected>NVEncC (ハードウェアエンコーダー)</option>
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
                  また、ファイル再生時は常に字幕データをストリームに含んだ状態で配信されます<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($subtitle_default == 'true'){ ?>
                <input id="subtitle_default" name="subtitle_default" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="subtitle_default" name="subtitle_default" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="subtitle_default" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトの BonDriver (地デジ用)</span>
                <p>
                  デフォルトで利用する BonDriver (地デジ用) です<br>
                  うまく再生出来ない場合、BonDriver_Spinel などを利用すると安定して視聴できる場合があります<br>
                </p>
              </div>
              <input class="text-box" name="BonDriver_default_T" type="text" required value="<?php echo $BonDriver_default_T; ?>" placeholder="BonDriver_○○_T0.dll" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>デフォルトの BonDriver (BS・CS用)</span>
                <p>
                  デフォルトで利用する BonDriver (BS・CS用) です<br>
                  うまく再生出来ない場合、BonDriver_Spinel などを利用すると安定して視聴できる場合があります<br>
                </p>
              </div>
              <input class="text-box" name="BonDriver_default_S" type="text" required value="<?php echo $BonDriver_default_S; ?>" placeholder="BonDriver_○○_S0.dll" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>録画ファイルのあるフォルダ</span>
                <p>
                  ファイル再生の際に利用します<br>
                  ネットワークドライブ内のフォルダは認識できないかもしれません<br>
                </p>
              </div>
              <input class="text-box" name="TSfile_dir" type="text" required value="<?php echo $TSfile_dir; ?>" placeholder="E:/TV-Record/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>EDCB Material WebUI (EMWUI) の API がある URL</span>
                <p>
                  番組表取得などで利用します<br>
                  この機能を利用する場合、予め EDCB_Material_WebUI を導入しておいてください<br>
                </p>
              </div>
              <input class="text-box" name="EDCB_http_url" type="text" value="<?php echo $EDCB_http_url; ?>" placeholder="http://192.168.1.11:5510/api/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>配信休止中・配信準備中時の動画の音楽を消す</span>
                <p>
                  毎回音楽が流れて鬱陶しい場合はオンにしてください<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($silent == 'true'){ ?>
                <input id="silent" name="silent" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="silent" name="silent" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="silent" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>再生履歴を何件まで保持するか</span>
                <p>
                  新規インストール時のデフォルトは10件です<br>
                </p>
              </div>
              <input class="text-box" name="history_keep" type="number" min="1" max="100"  value="<?php echo $history_keep; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TVRemotePlus のアップデートを確認する</span>
                <p>
                鬱陶しい場合・TVRemotePlusの読み込みが遅い場合はオフにしてください<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($update_confirm == 'true'){ ?>
                <input id="update_confirm" name="update_confirm" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="update_confirm" name="update_confirm" class="toggle-input" type="checkbox" />
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
                </p>
              </div>
              <input class="text-box" name="nicologin_mail" type="text" value="<?php echo $nicologin_mail; ?>" placeholder="example@gmail.com" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ニコニコにログインする際のパスワード</span>
                <p>
                  ニコニコ実況への投稿・ファイル再生時の過去ログの取得に必須です<br>
                </p>
              </div>
              <div class="password-box-wrap">
                <input class="password-box" name="nicologin_password" type="password" value="<?php echo $nicologin_password; ?>" placeholder="password" />
                <i class="password-box-input fas fa-eye"></i>
              </div>
            </div>

            <h4><i class="fab fa-twitter"></i>Twitter 投稿</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ハッシュタグ付きツイートを連投した際に何秒以内ならハッシュタグを消すか</span>
                <p>
                  アカウントのシャドウバン回避のためのの設定です<br>
                  Twitter の規制が厳しいため、60秒以内(？)にハッシュタグつけて連投するとシャドウバン(Search Ban・検索に引っかからなくなる)されてしまうみたいです<br>
                  シャドウバンを避けるため、60(秒)より下には設定しないことをお勧めします<br>
                  鬱陶しいのであれば0(秒)に設定すればオフになります<br>
                </p>
              </div>
              <input class="text-box" name="tweet_time" type="number" min="0" max="120"  value="<?php echo $tweet_time; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>画像付きツイートを投稿する際に一度アップロードする画像の保存フォルダ</span>
                <p>
                新規インストール時のデフォルトの upload/ に設定すると、(TVRemotePlusをインストールしたフォルダ)/htdocs/tweet/upload/ に自動で保存されます<br>
                ずっと画像付きツイートをしているとそこそこのファイルサイズになるので、適宜録画用 HDD 内のフォルダを指定しておくのも良いと思います<br>
                </p>
              </div>
              <input class="text-box" name="tweet_upload" type="text" value="<?php echo $tweet_upload; ?>" placeholder="E:/TV-Capture/" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>画像付きツイートを投稿した際に一度アップロードした画像を削除する</span>
                <p>
                  削除する場合はオン、削除しない場合はオフです<br>
                  アップロードした画像を削除しない場合、画像付きツイートをした際の画像は (TVRemotePlusをインストールしたフォルダ)\htdocs\tweet\upload\ に保存されています<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($tweet_delete  == 'true'){ ?>
                <input id="tweet_delete" name="tweet_delete" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="tweet_delete" name="tweet_delete" class="toggle-input" type="checkbox" />
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
              <input class="text-box" name="CONSUMER_KEY" type="text" value="<?php echo $CONSUMER_KEY; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXX" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TwitterAPI のコンシューマーシークレット (Consumer Secret)</span>
                <p>
                TVRemotePlus からのツイート投稿に必須です<br>
                コンシューマーシークレットは50文字のランダムな英数字です<br>
                </p>
              </div>
              <input class="text-box" name="CONSUMER_SECRET" type="text" value="<?php echo $CONSUMER_SECRET; ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
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
<?php	if ($basicauth == 'true'){ ?>
                <input id="basicauth" name="basicauth" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="basicauth" name="basicauth" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="basicauth" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>Basic 認証のユーザー名</span>
                <p>
                  Basic 認証で TVRemotePlus にログインする時のユーザー名を設定します<br>
                  Basic 認証を利用する場合は必ず設定してください<br>
                </p>
              </div>
              <input class="text-box" name="basicauth_user" type="text" value="<?php echo $basicauth_user; ?>" placeholder="user" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>Basic 認証のパスワード</span>
                <p>
                  Basic 認証で TVRemotePlus にログインする時のパスワードを設定します<br>
                  Basic 認証を利用する場合は必ず設定してください<br>
                </p>
              </div>
              <div class="password-box-wrap">
                <input class="password-box" name="basicauth_password" type="password" value="<?php echo $basicauth_password; ?>" placeholder="12345678" />
                <i class="password-box-input fas fa-eye"></i>
              </div>
            </div>
            
            <h4><i class="fas fa-hammer"></i>詳細設定</h4>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ストリーム開始設定後の画面を表示せずに再生画面へリダイレクトする</span>
                <p>
                  リダイレクトする場合はオン、リダイレクトしない場合はオフです<br>
                  ストリーム開始が失敗する時などにオフに設定してデバッグするための機能です、基本的にオンで良いと思います<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($setting_redirect == 'true'){ ?>
                <input id="setting_redirect" name="setting_redirect" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="setting_redirect" name="setting_redirect" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="setting_redirect" class="toggle-label"></label>
              </div>
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>UDP送信時の開始ポート番号</span>
                <p>
                  基本的に変更する必要はありません<br>
                  エンコーダーがすぐ落ちてしまう場合、UDP送信ポートが他のソフトとバッティングしている可能性があります<br>
                  その場合は、UDP送信ポートを空いているポートに変更してください<br>
                </p>
              </div>
              <input class="text-box" name="udp_port" type="number" min="1" max="40000"  value="<?php echo $udp_port; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ライブ再生時)</span>
                <p>
                  基本は変える必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 5(秒) や 10(秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  新規インストール時のデフォルトは 1(秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlslive_time" type="number" min="1" max="60"  value="<?php echo $hlslive_time; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>HLS セグメントあたりの秒数 (ファイル再生時)</span>
                <p>
                  基本は変える必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 10(秒) や 15(秒) などに伸ばすことで、安定して再生できる場合があります<br>
                  新規インストール時のデフォルトは 5(秒) です<br>
                </p>
              </div>
              <input class="text-box" name="hlsfile_time" type="number" min="1" max="60"  value="<?php echo $hlsfile_time; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>ライブ再生時に HLS プレイリストに載せるセグメントの個数</span>
                <p>
                  基本は変える必要はありませんが、外部から視聴する場合でネットワークが不安定な場合、
                  秒数を 5(個) や 10(個) などに設定することで、安定して再生できる場合があります<br>
                  ファイル再生時は全てのセグメントをプレイリストに載せています<br>
                  新規インストール時のデフォルトは 3(個) です<br>
                </p>
              </div>
              <input class="text-box" name="$hlslive_list" type="number" min="1" max="60"  value="<?php echo $hlslive_list; ?>" />
            </div>

            <div class="setting-form setting-input">
              <div class="setting-content">
                <span>TSTask を強制終了させる</span>
                <p>
                  一部の環境にて TSTask がうまく終了しない場合のみオンにしてください<br>
                  強制終了させると TSTask・TSTaskCentre の動作がおかしくなる場合があるので、できるだけオフにしておく事をおすすめします<br>
                </p>
              </div>
              <div class="toggle-switch">
<?php	if ($TSTask_shutdown == 'true'){ ?>
                <input id="TSTask_shutdown" name="TSTask_shutdown" class="toggle-input" type="checkbox" checked />
<?php	} else { ?>
                <input id="TSTask_shutdown" name="TSTask_shutdown" class="toggle-input" type="checkbox" />
<?php	} // 括弧終了 ?>
                <label for="TSTask_shutdown" class="toggle-label"></label>
              </div>
            </div>

          </div>

<?php	} else { // POSTの場合 ?>

          <h2>
            <i class="fas fa-cog"></i>設定
          </h2>

<?php		if ($ini['state'] == 'ONAir' or $ini['state'] == 'File'){ ?>
          <h3 class="blue">
            <i class="fas fa-video"></i>ストリーム開始
<?php		} else { ?>
          <h3 class="red">
            <i class="fas fa-video"></i>ストリーム終了
<?php		} //括弧終了 ?>
          </h3>

          <div class="setting-form-wrap">
            <p>ストリーム設定を保存しました。</p>
<?php		if ($ini['state'] == 'ONAir' or $ini['state'] == 'File'){ ?>
            <p>
              ストリームを開始します。<br>
              なお、ストリームの起動には数秒かかります。<br>
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
              <button class="redbutton" type="button" onclick="location.href='/'"><i class="fas fa-home"></i>ホームに戻る</button>
            </div>
          </div>
          
<?php		} //括弧終了 ?>
        </div>
      </div>
    </div>

  </section>
	
  <section id="footer">
    <?php echo $site_title.' '.$version; ?>

  </section>
</body>

</html>