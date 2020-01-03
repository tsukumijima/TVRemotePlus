<?php
	
	// ***** 各種モジュール関数 *****

	// Windows用非同期コマンド実行関数
	function win_exec($cmd){
		$fp = popen($cmd.' > nul 2>&1', "r");
		pclose($fp);
	}

	// BOM除去
	function removeBOM($text){
		// 悪しきBOM
		$utf8bom = pack('H*', 'EFBBBF'); // UTF-8
		$utf16bom = pack('H*', 'FFFE'); // UTF-16

		// 置換して返す
		$text = preg_replace("/^$utf8bom/", '', $text);
		$text = preg_replace("/^$utf16bom/", '', $text);
		return $text;
	}

	// [新]とかをHTML化
	function convertSymbol($string){
		$string = str_replace('[', '<span class="mark">', $string);
		$string = str_replace(']', '</span>', $string);
		return $string;
	}

	// basic認証関連
	// 若干時間がかかるため、index.php の読み込み時のみに実行する
	function basicAuth($basicauth, $basicauth_user, $basicauth_password){

		global $base_dir, $htaccess, $htpasswd;

		// basic認証有効
		if ($basicauth == 'true'){

			// .htpasswd ファイル作成
			$htpasswd_conf = $basicauth_user.':'.password_hash($basicauth_password, PASSWORD_BCRYPT);
			file_put_contents($htpasswd, $htpasswd_conf);

			// .htaccess 書き換え
			$htaccess_conf = file_get_contents($htaccess);

			// 記述がない場合は追加する
			if (!preg_match("/# Basic認証をかける.*/", $htaccess_conf)){

				// .htpasswd の絶対パスを修正
				$htaccess_conf = $htaccess_conf."\n".
					'# Basic認証をかける'."\n".
					'AuthType Basic'."\n".
					'AuthName "Input your ID and Password."'."\n".
					'AuthUserFile '.$base_dir.'htdocs/.htpasswd'."\n".
					'require valid-user'."\n";
				
				file_put_contents($htaccess, $htaccess_conf);
			}

		} else {

			// .htpasswd 削除
			if (file_exists($htpasswd)) unlink($htpasswd);

			// .htaccess の記述を削除
			$htaccess_conf = file_get_contents($htaccess);
			if (preg_match("/# Basic認証をかける.*/", $htaccess_conf)){
				$htaccess_conf = preg_replace("/# Basic認証をかける.*/s", '', $htaccess_conf);
				$htaccess_conf = rtrim($htaccess_conf, "\n")."\n";
				file_put_contents($htaccess, $htaccess_conf);
			}

		}
	}

	// URLからストリーム番号を取得する関数
	// flgをtrueにするとストリーム番号が指定されているかどうかを返す
	function getStreamNumber($url, $flg=false){

		// クエリを除外
		$url = parse_url($url, PHP_URL_PATH);

		// URLの最初と最後にあるかもしれないスラッシュと
		// v3/ を削除しておくのがポイント
		$slash = explode('/', str_replace('v3/', '', trim($url, '/')));

		// 配列の最後の値を取得
		$stream = end($slash);

		// URLに正しいストリーム番号が入っていなかった場合はストリーム1とする
		if (empty($stream) or !is_numeric($stream)){
			$stream = 1;
			$stream_flg = false;
		} else {
			$stream_flg = true;
		}

		if (!$flg){
			return strval($stream);
		} else {
			return $stream_flg;
		}
	}

	// ニコニコ実況IDをチャンネル名から取得する関数
	function getJKchannel($channel){
		global $base_dir;

		// ch_sid.txtを改行ごとに区切って配列にする
		$ch_sid = explode("\n", removeBOM(file_get_contents($base_dir.'data/ch_sid.txt')));

		// 配列を回す
		foreach ($ch_sid as $key => $value) {

			// Tabで区切る
			$ch_sid[$key] = explode('	', $value);

			// 抽出したチャンネル名
			$jkch = mb_convert_kana($ch_sid[$key][4], 'asv');

			// 正規表現パターン
			mb_regex_encoding('UTF-8');
			$match = "{".$jkch."[0-9]}u";
			$match2 = "{".preg_quote(mb_substr($jkch, 0, 5))."[0-9]".preg_quote(mb_substr($jkch, 5, 3))."}u"; // NHK総合用パターン
			$match3 = "{".preg_quote(mb_substr($jkch, 0, 6))."[0-9]".preg_quote(mb_substr($jkch, 6, 3))."}u"; // NHKEテレ用パターン

			// チャンネル名が一致したら
			if ($channel == $jkch or preg_match($match, $channel) or preg_match($match2, $channel) or preg_match($match3, $channel)){
				// 実況IDを返す
				return $ch_sid[$key][0];
			}
		}
	}

	// CSVファイルを読み込む関数
	function getCSV($csvfile, $encoding='UTF-16LE'){

		// ファイル存在確認
		if (!file_exists($csvfile)) return false;

		// 行頭と行末の改行・BOM削除・UTF-8へ変換
		file_put_contents($csvfile, trim(removeBOM(mb_convert_encoding(file_get_contents($csvfile), 'UTF-8', $encoding))));
	
		// SplFileObject()を使用してCSVロード
		$file = new SplFileObject($csvfile);
		$file->setFlags(
			SplFileObject::READ_CSV |
			SplFileObject::SKIP_EMPTY |
			SplFileObject::READ_AHEAD
		);
	
		// 各行を処理
		$records = array();
		foreach ($file as $i => $row){

			// 1行目はキーヘッダ行として取り込み
			if($i===0) {
				foreach($row as $j => $col) $colbook[$j] = $col;
				continue;
			}
	
			// 2行目以降はデータ行として取り込み
			$line = array();
			foreach($colbook as $j=>$col) $line[$colbook[$j]] = @$row[$j];
			$records[] = $line;
		}
		return $records;
	}

	// ch2を整形して連想配列化する関数
	function ch2ToArray($ch2_file, $flg){

		// ch2を取得
		$ch2_rawdata = removeBOM(file_get_contents($ch2_file));

		// 文字コード判定
		if (!empty(mb_detect_encoding($ch2_rawdata, 'SJIS, SJIS-WIN, EUC-JP, UTF-8'))){
			$charset = mb_detect_encoding($ch2_rawdata, 'SJIS, SJIS-WIN, EUC-JP, UTF-8');
		} else { // 何故かUTF-16だけ上手く検知されないバグが…
			$charset = 'UTF-16LE';
		}

		// ch2の文字コードをUTF-8に
		$ch2_data = mb_convert_encoding($ch2_rawdata, 'UTF-8', $charset);

		// 置換
		$ch2_data = str_replace("\r\n", "\n", $ch2_data); // CR+LFからLFに変換
		$ch2_data = str_replace("; TVTest チャンネル設定ファイル\n", "", $ch2_data);
		$ch2_data = preg_replace("/; 名称,チューニング空間.*/", "", $ch2_data);
		$ch2_data = str_replace(',,', ',1,', $ch2_data); // サービスタイプ欄がない場合に1として換算しておく

		// 地上波
		if ($flg == 'UHF'){
			$ch2_data = preg_replace("/;#SPACE\(.\,BS\).*$/s", "", $ch2_data); // BS・CSを削除
		// BS
		} else if ($flg == 'BS'){
			$ch2_data = preg_replace("/;#SPACE\(.\,UHF\).*;#SPACE\(.\,BS\)/s", "", $ch2_data); // 地上波を削除
			$ch2_data = preg_replace("/;#SPACE\(.\,CS110\).*$/s", "", $ch2_data); // CSを削除
		// CS
		} else if ($flg == 'CS') {
			$ch2_data = preg_replace("/;#SPACE\(.\,UHF\).*;#SPACE\(.\,CS110\)/s", "", $ch2_data); // 地上波・BSを削除（混合チューナー用）
			$ch2_data = preg_replace("/;#SPACE\(.\,BS\).*;#SPACE\(.\,CS110\)/s", "", $ch2_data); // BSを削除
		}

		//余計なコメントを削除
		$ch2_data = preg_replace("/;#SPACE.*/", "", $ch2_data);
		// 空行削除
		$ch2_data = str_replace("\n\n", "", $ch2_data);
		$ch2_data = rtrim($ch2_data);

		// 改行で分割
		$ch2 = explode("\n", $ch2_data);

		// さらにコンマで分割
		foreach ($ch2 as $key => $value) {
			$ch2[$key] = explode(",", $ch2[$key]);
		}

		return $ch2;
	}

	// ch2からチャンネルリストとサービスIDリストを取得する関数
	function initBonChannel($BonDriver_dir){

		// BonDriver_dirからBonDriverを検索
		foreach (glob($BonDriver_dir.'[bB]on[dD]river_*.dll') as $i => $file) {
			$BonDriver_dll[$i] = str_replace($BonDriver_dir, '', $file);
		}
		if (!isset($BonDriver_dll)) $BonDriver_dll = array();
	
		// BonDriver_dirから地デジ用BonDriverを検索
		$search_T = array_merge(
			glob($BonDriver_dir.'[bB]on[dD]river_*[tT].dll'),
			glob($BonDriver_dir.'[bB]on[dD]river_*_[tT][0-9]*.dll'),
			glob($BonDriver_dir.'[bB]on[dD]river_*-[tT][0-9]*.dll')
		);
		foreach ($search_T as $i => $file) {
			$BonDriver_dll_T[$i] = str_replace($BonDriver_dir, '', $file);
		}
		if (!isset($BonDriver_dll_T)) $BonDriver_dll_T = array();

		// BonDriver_dirからBSCS用BonDriverを検索
		$search_S = array_merge(
			glob($BonDriver_dir.'[bB]on[dD]river_*[sS].dll'),
			glob($BonDriver_dir.'[bB]on[dD]river_*_[sS][0-9]*.dll'),
			glob($BonDriver_dir.'[bB]on[dD]river_*-[sS][0-9]*.dll')
		);
		foreach ($search_S as $i => $file) {
			$BonDriver_dll_S[$i] = str_replace($BonDriver_dir, '', $file);
		}
		if (!isset($BonDriver_dll_S)) $BonDriver_dll_S = array();

		// 無印BonDriverを洗い出す
		$BonDriver_dll_raw = $BonDriver_dll;
		foreach ($BonDriver_dll as $key => $value) {
			foreach ($BonDriver_dll_T as $key1 => $value1) {
				if ($value === $value1){
					unset($BonDriver_dll_raw[$key]);
				}
			}
			foreach ($BonDriver_dll_S as $key2 => $value2) {
				if ($value === $value2){
					unset($BonDriver_dll_raw[$key]);
				}
			}
		}
		
		// 配列のインデックスを詰める
		$BonDriver_dll_raw = array_values($BonDriver_dll_raw);

		// 無印BonDriverを配列の末尾に足す
		$BonDriver_dll_T = array_merge($BonDriver_dll_T, $BonDriver_dll_raw);
		$BonDriver_dll_S = array_merge($BonDriver_dll_S, $BonDriver_dll_raw);

		// ch2を検索する
		// 地デジ用
		$BonDriver_ch2_file_T = array_merge(
			glob($BonDriver_dir.'[bB]on[dD]river_*[tT].ch2'),
			glob($BonDriver_dir.'[bB]on[dD]river_*_[tT][0-9]*.ch2'),
			glob($BonDriver_dir.'[bB]on[dD]river_*-[tT][0-9]*.ch2')
		);

		// BS・CS用
		$BonDriver_ch2_file_S = array_merge(
			glob($BonDriver_dir.'[bB]on[dD]river_*[sS].ch2'),
			glob($BonDriver_dir.'[bB]on[dD]river_*_[sS][0-9]*.ch2'),
			glob($BonDriver_dir.'[bB]on[dD]river_*-[sS][0-9]*.ch2')
		);

		// その他（混合チューナー用）
		$BonDriver_ch2_file_raw = glob($BonDriver_dir.'[bB]on[dD]river_*.ch2');


		// 地デジのch2があれば
		// 地デジ用もBS・CS用もないが無印ch2はある場合も含める（混合チューナー用）
		if (!empty($BonDriver_ch2_file_T) || (empty($BonDriver_ch2_file_T) && empty($BonDriver_ch2_file_S) && !empty($BonDriver_ch2_file_raw))){

			// ch2を連想配列に変換
			$BonDriver_ch2_T = ch2ToArray(array_merge($BonDriver_ch2_file_T, $BonDriver_ch2_file_raw)[0], 'UHF');

			if (!empty($BonDriver_ch2_T[0][0])){

				// 地デジ(T)用チャンネルをセット
				foreach ($BonDriver_ch2_T as $key => $value) {

					// サービス状態が1の物のみセットする
					// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
					if ($value[4] != 2 and $value[8] == 1){ //  and !isset($ch_T[strval($value[3])])
						// 全角は半角に直す
						// 衝突回避でリモコン番号が衝突したら元番号 + 10にする
						if (empty($ch_T[strval($value[3])])){
							// チャンネル名
							$ch_T[strval($value[3])] = mb_convert_kana($value[0], 'asv');
							// サービスID(SID)
							$sid_T[strval($value[3])] = mb_convert_kana($value[5], 'asv');
							// ネットワークID(NID・ONID)
							$onid_T[strval($value[3])] = mb_convert_kana($value[6], 'asv');
							// トランスポートストリームID(TSID)
							$tsid_T[strval($value[3])] = mb_convert_kana($value[7], 'asv');
						// 衝突した場合
						} else {
							// チャンネル名
							$ch_T[strval($value[3] + 10)] = mb_convert_kana($value[0], 'asv');
							// サービスID(SID)
							$sid_T[strval($value[3] + 10)] = mb_convert_kana($value[5], 'asv');
							// ネットワークID(NID・ONID)
							$onid_T[strval($value[3] + 10)] = mb_convert_kana($value[6], 'asv');
							// トランスポートストリームID(TSID)
							$tsid_T[strval($value[3] + 10)] = mb_convert_kana($value[7], 'asv');
						}
					}
				}

			} else {
				$ch_T = array();
				$sid_T = array();
				$onid_T = array();
				$tsid_T = array();
			}

		} else {
			$ch_T = array();
			$sid_T = array();
			$onid_T = array();
			$tsid_T = array();
		}

		// BSCSのch2があれば
		// 地デジ用もBS・CS用もないが無印ch2はある場合も含める（混合チューナー用）
		if (!empty($BonDriver_ch2_file_S) || (empty($BonDriver_ch2_file_T) && empty($BonDriver_ch2_file_S) && !empty($BonDriver_ch2_file_raw))){

			// ch2を連想配列に変換
			$BonDriver_ch2_S = ch2ToArray(array_merge($BonDriver_ch2_file_S, $BonDriver_ch2_file_raw)[0], 'BS');
			$BonDriver_ch2_CS = ch2ToArray(array_merge($BonDriver_ch2_file_S, $BonDriver_ch2_file_raw)[0], 'CS');

			if (!empty($BonDriver_ch2_S[0][0]) && !empty($BonDriver_ch2_CS[0][0])){

				// BS用チャンネルをセット
				foreach ($BonDriver_ch2_S as $key => $value) {

					// サービス状態が1の物のみセットする
					// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
					if ($value[4] != 2 and $value[8] == 1 and !isset($ch_S[strval($value[5])])){
						// 全角は半角に直す
						// チャンネル名
						$ch_S[strval($value[5])] = mb_convert_kana($value[0], 'asv');
						// サービスID(SID)
						$sid_S[strval($value[5])] = mb_convert_kana($value[5], 'asv');
						// ネットワークID(NID・ONID)
						$onid_S[strval($value[5])] = mb_convert_kana($value[6], 'asv');
						// トランスポートストリームID(TSID)
						$tsid_S[strval($value[5])] = mb_convert_kana($value[7], 'asv');
					}
				}

				// CS用チャンネルをセット
				foreach ($BonDriver_ch2_CS as $key => $value) {
					// サービス状態が1の物のみセットする
					// あとサブチャンネル・ラジオチャンネル・データ放送はセットしない
					if ($value[4] != 2 and $value[8] == 1 and !isset($ch_CS[strval($value[5])])){
						// 全角は半角に直す
						// BS-TBSとQVCバッティング問題
						if (intval($value[5]) !== 161){
							// チャンネル名
							$ch_CS[strval($value[5])] = mb_convert_kana($value[0], 'asv');
							// サービスID(SID)
							$sid_CS[strval($value[5])] = mb_convert_kana($value[5], 'asv');
							// ネットワークID(NID・ONID)
							$onid_CS[strval($value[5])] = mb_convert_kana($value[6], 'asv');
							// トランスポートストリームID(TSID)
							$tsid_CS[strval($value[5])] = mb_convert_kana($value[7], 'asv');
						// QVCのみ
						} else {
							// チャンネル名
							$ch_CS['161cs'] = mb_convert_kana($value[0], 'asv');
							// サービスID(SID)
							$sid_CS['161cs'] = mb_convert_kana($value[5], 'asv');
							// ネットワークID(NID・ONID)
							$onid_CS['161cs'] = mb_convert_kana($value[6], 'asv');
							// トランスポートストリームID(TSID)
							$tsid_CS['161cs'] = mb_convert_kana($value[7], 'asv');
						}
					}
				}

			} else {
				$ch_S = array();
				$ch_CS = array();
				$sid_S = array();
				$sid_CS = array();
				$onid_S = array();
				$onid_CS = array();
				$tsid_S = array();
				$tsid_CS = array();
			}

		} else {
			$ch_S = array();
			$ch_CS = array();
			$sid_S = array();
			$sid_CS = array();
			$onid_S = array();
			$onid_CS = array();
			$tsid_S = array();
			$tsid_CS = array();
		}

		// 合体させる
		$ch = $ch_T + $ch_S + $ch_CS;
		$sid = $sid_T + $sid_S + $sid_CS;
		$onid = $onid_T + $onid_S + $onid_CS;
		$tsid = $tsid_T + $tsid_S + $tsid_CS;

		return array($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
					 $ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
					 $sid, $sid_T, $sid_S, $sid_CS, // SID
					 $onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
					 $tsid, $tsid_T, $tsid_S, $tsid_CS); // TSID
	}

