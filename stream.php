<?php

	// コマンドラインからの場合
	if (isset($argc) and isset($argv)){

		//ini_set('log_errors', 0);
		//ini_set('display_errors', 0);

		// モジュール読み込み
		require_once (dirname(__FILE__).'/require.php');
		require_once (dirname(__FILE__).'/module.php');

		// BonDriverとチャンネルを取得
		list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
			$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
			$sid, $sid_T, $sid_S, $sid_CS, // SID
			$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
			$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
			= initBonChannel($BonDriver_dir);

		// 設定読み込み
		$ini = json_decode(file_get_contents($inifile), true);

		// コマンドラインからのストリーム開始・停止はおまけ機能です
		// ファイル再生機能は今の所ついていません

		echo "\n";
		echo ' ---------------------------------------------------'."\n";
		echo '           TVRemotePlus-Cmdline '.$version."\n";
		echo ' ---------------------------------------------------'."\n";

		if ($argc < 3){
			echo ' ---------------------------------------------------'."\n";
			echo '   Error: Argument is missing or too many.'."\n";
			echo '   Please Retry... m(__)m'."\n";
			echo ' ---------------------------------------------------'."\n";
			exit(1);
		}

		// ストリーム開始の引数：
		// stream.bat ONAir (ストリーム番号) (チャンネル番号)
		// stream.bat ONAir (ストリーム番号) (チャンネル番号) (動画の画質) (エンコーダー) (字幕データ (true ならオン・false ならオフ)) (BonDriver)

		// ストリーム開始の場合
		if ($argv[1] == 'ONAir'){

			// ストリーム番号
			$stream = strval($argv[2]);

			// ステータス
			$ini[$stream]['state'] = 'ONAir';
			
			// チャンネル
			if (isset($argv[3]) and isset($ch[$argv[3]])){ // チャンネルが存在するかチェック
				$ini[$stream]['channel'] = $argv[3];
			} else if (!isset($ch[$argv[3]])){
				echo ' ---------------------------------------------------'."\n";
				echo '   Error: Channel '.$argv[2].' Not found.'."\n";
				echo '   Please Retry... m(__)m'."\n";
				echo ' ---------------------------------------------------'."\n";
				exit(1);
			} else {
				echo ' ---------------------------------------------------'."\n";
				echo '   Error: Argument is missing.'."\n";
				echo '   Please Retry... m(__)m'."\n";
				echo ' ---------------------------------------------------'."\n";
				exit(1);
			}

			// ↓ は指定されていなかったらデフォルト値を使う

			// 動画の画質
			if (isset($argv[4])) $ini[$stream]['quality'] = $argv[4];
			else $ini[$stream]['quality'] = $quality_default;

			// エンコーダー
			if (isset($argv[5])) $ini[$stream]['encoder'] = $argv[5];
			else $ini[$stream]['encoder'] = $encoder_default;

			// 字幕データ
			if (isset($argv[6])) $ini[$stream]['subtitle'] = $argv[6];
			else $ini[$stream]['subtitle'] = $subtitle_default;

			// BonDriver
			if (!isset($argv[7]) or $argv[5] == 'default'){
				if (intval($ini[$stream]['channel']) >= 100 or intval($ini[$stream]['channel']) === 55){ // チャンネルの値が100より上(=BS・CSか・ショップチャンネルは055なので例外指定)
					$ini[$stream]['BonDriver'] = $BonDriver_default_S;
				} else { // 地デジなら
					$ini[$stream]['BonDriver'] = $BonDriver_default_T;
				}
			} else { // デフォルトでないなら
				$ini[$stream]['BonDriver'] = $argv[3];
			}

			// ストリーム開始表示
			echo '   Starting stream...'."\n\n";
			echo '   Stream:   '.$stream."\n";
			echo '   Channel:   '.$ini[$stream]['channel']."\n";
			echo '   sid:       '.$sid[$ini[$stream]['channel']]."\n";
			echo '   tsid:      '.$tsid[$ini[$stream]['channel']]."\n";
			echo '   Quality  : '.$ini[$stream]['quality']."\n";
			echo '   Encoder  : '.$ini[$stream]['encoder']."\n";
			echo '   Subtitle : '.$ini[$stream]['subtitle']."\n";
			echo '   BonDriver: '.$ini[$stream]['BonDriver']."\n";
			echo ' ---------------------------------------------------'."\n";
			echo "\n";

			// ストリームを開始する
			stream_start($ini[$stream]['channel'], $sid[$ini[$stream]['channel']], $tsid[$ini[$stream]['channel']], $ini[$stream]['BonDriver'], $ini[$stream]['quality'], $ini[$stream]['encoder'], $ini[$stream]['subtitle']);

			// 準備中用の動画を流すためにm3u8をコピー
			if ($silent == 'true'){
				copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
			} else {
				copy($standby_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
			}

			// ファイル書き込み
			file_put_contents($inifile, json_encode($ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			echo "\n";
			echo ' ---------------------------------------------------'."\n";
			echo '   Stream started.'."\n";
			echo '   Processing completed.'."\n";
			echo ' ---------------------------------------------------'."\n";
			exit();

		// ストリーム終了の引数：
		// stream.bat Offline (ストリーム番号)
		
		// ストリーム終了の場合
		} else if ($argv[1] == 'Offline'){

			// ストリーム番号
			$stream = strval($argv[2]);

			// ステータス
			$ini[$stream]['state'] = 'Offline';
			
			// ストリーム終了表示
			echo '   Stopping stream...'."\n";
			echo ' ---------------------------------------------------'."\n";
			echo "\n";

			// ストリームを停止する
			stream_stop();

			// 配信休止中用のプレイリスト
			if ($silent == 'true'){
				copy($offline_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
			} else {
				copy($offline_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
			}

			// ファイル書き込み
			file_put_contents($inifile, json_encode($ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

			echo "\n";
			echo ' ---------------------------------------------------'."\n";
			echo '   Stream stoped.'."\n";
			echo '   Processing completed.'."\n";
			echo ' ---------------------------------------------------'."\n";
			exit();
		} else {
			
			echo ' ---------------------------------------------------'."\n";
			echo '   Error: Argument is missing or too many.'."\n";
			echo '   Please Retry... m(__)m'."\n";
			echo ' ---------------------------------------------------'."\n";
			exit(1);
		}
		
	}

	// ライブ放送のストリームを開始する関数
	function stream_start($stream, $ch, $sid, $tsid, $BonDriver, $quality, $encoder, $subtitle){
		global $udp_port, $ffmpeg_path, $qsvencc_path, $nvencc_path, $vceencc_path, $tstask_path, $segment_folder, $hlslive_time, $hlslive_list, $base_dir, $output_encodelog;
		
		// 設定

		// UDPポート
		$stream_port = $udp_port + intval($stream);

		// UDP受信スキーム
		$receive = 'udp://127.0.0.1:'.$stream_port.'?pkt_size=262144&fifo_size=1000000';

		// 字幕切り替え
		switch ($subtitle) {

			case 'true':
				// $subtitle_ffmpeg_cmd = '-scodec copy'; // ffmpeg4.2以降用・ffmpeg側のバグでうまく行かないので保留
				$subtitle_ffmpeg_cmd = '-map 0 -ignore_unknown';
				$subtitle_other_cmd = '--sub-copy asdata';
			break;

			case 'false':
				// $subtitle_ffmpeg_cmd = '-sn'; // ffmpeg4.2以降用・ffmpeg側のバグでうまく行かないので保留
				$subtitle_ffmpeg_cmd = '';
				$subtitle_other_cmd = '';
			break;

			default:
				$subtitle_ffmpeg_cmd = '';
				$subtitle_other_cmd = '';
			break;
		}

		// 画質切り替え
		switch ($quality) {

			case '1080p-high':
				$width = 1920; // 動画の横幅
				$height = 1080; // 動画の高さ

				$vb = '6200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '1080p':
				$width = 1440; // 動画の横幅
				$height = 1080; // 動画の高さ

				$vb = '6200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '4:3'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '810p':
				$width = 1440; // 動画の横幅
				$height = 810; // 動画の高さ

				$vb = '5200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '720p':
				$width = 1280; // 動画の横幅
				$height = 720; // 動画の高さ

				$vb = '4200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '540p':
				$width = 960; // 動画の横幅
				$height = 540; // 動画の高さ

				$vb = '3000k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '360p':
				$width = 640; // 動画の横幅
				$height = 360; // 動画の高さ

				$vb = '1500k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '240p':
				$width = 426; // 動画の横幅
				$height = 240; // 動画の高さ

				$vb = '300k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 44100; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;
		}

		// 内部処理

		// 一旦すべてタスクを切る
		stream_stop($stream);

		// TSTask.exeを起動する
		$tstask_cmd = 'start /min '.$tstask_path.' /min /xclient- /udp /port '.$stream_port.' /sid '.$sid.' /tsid '.$tsid.' /d '.$BonDriver.' /sendservice 1';
		win_exec($tstask_cmd);

		// 変換コマンド切り替え
		switch ($encoder) {

			case 'ffmpeg':

				// ffmpeg用コマンド
				$stream_cmd = $ffmpeg_path.

					// 入力
					' -dual_mono_mode main -i "'.$receive.'"'.
					// HLS
					' -f hls'.
					' -hls_segment_type mpegts'.
					' -hls_time '.$hlslive_time.' -g '.($hlslive_time * 30).
					' -hls_list_size '.$hlslive_list.
					' -hls_allow_cache 0'.
					' -hls_flags delete_segments'.
					' -hls_segment_filename stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' -vcodec libx264 -vb '.$vb.' -vf yadif=0:-1:1,scale='.$width.':'.$height.
					' -aspect 16:9 -preset veryfast -r 30000/1001'.
					// 音声
					' -acodec aac -ab '.$ab.' -ar '.$samplerate.' -ac 2 -af volume='.$volume.
					// 字幕
					' '.$subtitle_ffmpeg_cmd.
					// その他
					' -flags +loop+global_header -movflags +faststart -threads auto'.
					// 出力
					' stream'.$stream.'.m3u8';

				break;

			case 'QSVEncC':

				// QSVEncC用コマンド
				$stream_cmd = $qsvencc_path.

					// 入力
					' -i "'.$receive.'"'.
					// avqsvエンコード
					' --avqsv'.
					// HLS
					' -m hls_time:'.$hlslive_time.' --gop-len '.($hlslive_time * 30).
					' -m hls_list_size:'.$hlslive_list.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --quality balanced --profile Main --vpp-deinterlace normal --tff'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30 --audio-ignore-notrack-error'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --fallback-rc --max-procfps 90 --output-thread 0'.
					// 出力
					' -o stream'.$stream.'.m3u8';
		
				break;

			case 'NVEncC':

				// NVEncC用コマンド
				$stream_cmd = $nvencc_path.

					// 入力
					' -i "'.$receive.'"'.
					// avcuvidエンコード
					' --avcuvid'.
					// HLS
					' -m hls_time:'.$hlslive_time.' --gop-len '.($hlslive_time * 30).
					' -m hls_list_size:'.$hlslive_list.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --preset default --profile Main --cabac --vpp-deinterlace normal --tff'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30 --audio-ignore-notrack-error'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --max-procfps 90 --output-thread 0'.
					// 出力
					' -o stream'.$stream.'.m3u8';

				break;

			case 'VCEEncC':
	
				// VCEEncC用コマンド
				$stream_cmd = $vceencc_path.

					// 入力
					' -i "'.$receive.'"'.
					// avhwエンコード
					' --avhw'.
					// HLS
					' -m hls_time:'.$hlslive_time.' --gop-len '.($hlslive_time * 30).
					' -m hls_list_size:'.$hlslive_list.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --vpp-afs preset=default --quality balanced --profile Main'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --max-procfps 90'.
					// 出力
					' -o stream'.$stream.'.m3u8';

				break;
		}

		// ログを書き出すかどうか
		if ($output_encodelog == 'true'){
			$stream_cmd = 'start /min "'.$encoder.' Encoding..." cmd.exe /C "'.win_exec_escape($stream_cmd).' > '.$base_dir.'data/stream'.$stream.'.log 2>&1"';
		} else {
			$stream_cmd = 'start /min "'.$encoder.' Encoding..." cmd.exe /C "'.win_exec_escape($stream_cmd).'"';
		}

		// ストリームを開始する
		win_exec('pushd "'.$segment_folder.'" && '.$stream_cmd);

		// ここでチャンネルを変更する
		$ini[$stream]['channel'] = $ch;

		// エンコードコマンドとTSTaskのコマンドを返す
		return array($stream_cmd, $tstask_cmd);
	}

	// ファイル再生のストリームを開始する関数
	function stream_file($stream, $filepath, $quality, $encoder, $subtitle){
		global $ffmpeg_path, $qsvencc_path, $nvencc_path, $vceencc_path, $segment_folder, $hlsfile_time, $base_dir, $output_encodelog;
		
		// 設定

		// 字幕切り替え
		switch ($subtitle) {

			case 'true':
				// $subtitle_ffmpeg_cmd = '-scodec copy'; // ffmpeg4.2以降用・ffmpeg側のバグでうまく行かないので保留
				$subtitle_ffmpeg_cmd = '-map 0 -ignore_unknown';
				$subtitle_other_cmd = '--sub-copy asdata';
			break;

			case 'false':
				// $subtitle_ffmpeg_cmd = '-sn'; // ffmpeg4.2以降用・ffmpeg側のバグでうまく行かないので保留
				$subtitle_ffmpeg_cmd = '';
				$subtitle_other_cmd = '';
			break;


			default:
				$subtitle_ffmpeg_cmd = '';
				$subtitle_other_cmd = '';
			break;
		}
		
		// 画質切り替え
		switch ($quality) {
			case '1080p-high':
				$width = 1920; // 動画の横幅
				$height = 1080; // 動画の高さ

				$vb = '6200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '1080p':
				$width = 1440; // 動画の横幅
				$height = 1080; // 動画の高さ

				$vb = '6200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '4:3'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '810p':
				$width = 1440; // 動画の横幅
				$height = 810; // 動画の高さ

				$vb = '5200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '720p':
				$width = 1280; // 動画の横幅
				$height = 720; // 動画の高さ

				$vb = '4200k'; // 動画のビットレート
				$ab = '192k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '540p':
				$width = 960; // 動画の横幅
				$height = 540; // 動画の高さ

				$vb = '3000k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '360p':
				$width = 640; // 動画の横幅
				$height = 360; // 動画の高さ

				$vb = '1500k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;

			case '240p':
				$width = 426; // 動画の横幅
				$height = 240; // 動画の高さ

				$vb = '300k'; // 動画のビットレート
				$ab = '128k'; // 音声のビットレート
				$sar = '1:1'; // アスペクト比(SAR)
				$samplerate = 48000; // 音声のサンプルレート
				$volume = 2.0; // 音量(元の音量の何倍か)
			break;
		}

		// 内部処理

		// 一旦すべてタスクを切る
		stream_stop($stream);

		// 変換コマンド切り替え
		switch ($encoder) {

			case 'ffmpeg':

				// ffmpeg用コマンド
				$stream_cmd = $ffmpeg_path.

					// 入力
					' -dual_mono_mode main -i "'.$filepath.'"'.
					// HLS
					' -f hls'.
					' -hls_segment_type mpegts'.
					' -hls_time '.$hlsfile_time.' -g '.($hlsfile_time * 30).
					' -hls_list_size 0'.
					' -hls_allow_cache 0'.
					' -hls_flags delete_segments'.
					' -hls_segment_filename stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' -vcodec libx264 -vb '.$vb.' -vf yadif=0:-1:1,scale='.$width.':'.$height.
					' -aspect 16:9 -preset veryfast -r 30000/1001'.
					// 音声
					' -acodec aac -ab '.$ab.' -ar '.$samplerate.' -ac 2 -af volume='.$volume.
					// 字幕
					' '.$subtitle_ffmpeg_cmd.
					// その他
					' -flags +loop+global_header -movflags +faststart -threads auto'.
					// 出力
					' stream'.$stream.'.m3u8';

				break;

			case 'QSVEncC':

				// QSVEncC用コマンド
				$stream_cmd = $qsvencc_path.

					// 入力
					' -i "'.$filepath.'"'.
					// avqsvエンコード
					' --avqsv'.
					// HLS
					' -m hls_time:'.$hlsfile_time.' --gop-len '.($hlsfile_time * 30).
					' -m hls_list_size:0'.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --quality balanced --profile Main --vpp-deinterlace normal --tff'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30 --audio-ignore-notrack-error'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --fallback-rc --max-procfps 90 --output-thread 0'.
					// 出力
					' -o stream'.$stream.'.m3u8';
		
				break;

			case 'NVEncC':

				// NVEncC用コマンド
				$stream_cmd = $nvencc_path.

					// 入力
					' -i "'.$filepath.'"'.
					// avcuvidエンコード
					' --avcuvid'.
					// HLS
					' -m hls_time:'.$hlsfile_time.' --gop-len '.($hlsfile_time * 30).
					' -m hls_list_size:0'.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --preset default --profile Main --cabac --vpp-deinterlace normal --tff'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30 --audio-ignore-notrack-error'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --max-procfps 90 --output-thread 0'.
					// 出力
					' -o stream'.$stream.'.m3u8';

				break;

			case 'VCEEncC':
	
				// VCEEncC用コマンド
				$stream_cmd = $vceencc_path.

					// 入力
					' -i "'.$filepath.'"'.
					// avhwエンコード
					' --avhw'.
					// HLS
					' -m hls_time:'.$hlsfile_time.' --gop-len '.($hlsfile_time * 30).
					' -m hls_list_size:0'.
					' -m hls_allow_cache:0'.
					' -m hls_flags:delete_segments'.
					' -m hls_segment_filename:stream'.$stream.'-'.date('mdHi').'_%05d.ts'.
					// 映像
					' --vbr '.$vb.' --qp-max 24:26:28 --output-res '.$width.'x'.$height.' --sar '.$sar.
					' --vpp-afs preset=default --quality balanced --profile Main'.
					// 音声
					' --audio-codec aac#dual_mono_mode=main --audio-stream :stereo --audio-bitrate '.$ab.' --audio-samplerate '.$samplerate.
					' --audio-filter volume='.$volume.' --audio-ignore-decode-error 30'.
					// 字幕
					' '.$subtitle_other_cmd.
					// その他
					' --avsync forcecfr --max-procfps 90'.
					// 出力
					' -o stream'.$stream.'.m3u8';

				break;
		}

		// ログを書き出すかどうか
		if ($output_encodelog == 'true'){
			$stream_cmd = 'start /min "'.$encoder.' Encoding..." cmd.exe /C "'.win_exec_escape($stream_cmd).' > '.$base_dir.'data/stream'.$stream.'.log 2>&1"';
		} else {
			$stream_cmd = 'start /min "'.$encoder.' Encoding..." cmd.exe /C "'.win_exec_escape($stream_cmd).'"';
		}

		// ストリームを開始する
		win_exec('pushd "'.$segment_folder.'" && '.$stream_cmd);

		// エンコードコマンドを返す
		return $stream_cmd;
	}

	// ストリームを終了する関数
	// flgがtrueの場合は全てのストリームを終了する
	function stream_stop($stream, $flg=false){
		global $inifile, $udp_port, $process_csv, $ffmpeg_exe, $qsvencc_exe, $nvencc_exe, $vceencc_exe, $tstask_exe, $segment_folder, $TSTask_shutdown;

		// 全てのストリームを終了する
		if ($flg){

			// ffmpegを終了する
			win_exec('taskkill /F /IM '.$ffmpeg_exe);
			
			// QSVEncCを終了する
			win_exec('taskkill /F /IM '.$qsvencc_exe);
			
			// NVEncCを終了する
			win_exec('taskkill /F /IM '.$nvencc_exe);
			
			// VCEEncCを終了する
			win_exec('taskkill /F /IM '.$vceencc_exe);
			
			// TSTaskを終了する
			if ($TSTask_shutdown == 'true'){ // 強制終了
				win_exec('taskkill /F /IM '.$tstask_exe);
			} else { // 通常終了
				win_exec('taskkill /IM '.$tstask_exe);
			}

			// フォルダ内のTSを削除
			win_exec('pushd "'.$segment_folder.'" && del *.ts /S');

		// このストリームを終了する
		} else {

			// UDPポート
			$stream_port = $udp_port + intval($stream);

			// 録画ファイルのファイルパス
			$filepath = @json_decode(file_get_contents($inifile), true)[$stream]['filepath'];
			if ($filepath === null) $filepath = '';

			// 現在のプロセスのコマンドライン引数とプロセスIDを取得する
			exec('wmic process get commandline,processid /format:csv > '.$process_csv);

			// CSVをパースして取得
			$process = getCSV($process_csv, 'UTF-16LE');

			// プロセスごとに該当するかチェックしてKill
			foreach ($process as $key => $value) {

				// ffmpeg
				if (strpos($value['CommandLine'], $ffmpeg_exe) !== false and 
				(@strpos($value['CommandLine'], strval($stream_port)) !== false) or (@strpos($value['CommandLine'], $filepath) !== false)){
					win_exec('taskkill /F /PID '.$value['ProcessId']);
					// echo 'ffmpeg Killed. Stream: '.$stream.' Cmd:'.$value['CommandLine']."\n\n";
				}

				// QSVEncC
				if (strpos($value['CommandLine'], $qsvencc_exe) !== false and 
				(@strpos($value['CommandLine'], strval($stream_port)) !== false) or (@strpos($value['CommandLine'], $filepath) !== false)){
					win_exec('taskkill /F /PID '.$value['ProcessId']);
					// echo 'QSVEncC Killed. Stream: '.$stream.' Cmd:'.$value['CommandLine']."\n\n";
				}
				
				// NVEncC
				if (strpos($value['CommandLine'], $nvencc_exe) !== false and 
				(@strpos($value['CommandLine'], strval($stream_port)) !== false) or (@strpos($value['CommandLine'], $filepath) !== false)){
					win_exec('taskkill /F /PID '.$value['ProcessId']);
					// echo 'NVEncC Killed. Stream: '.$stream.' Cmd:'.$value['CommandLine']."\n\n";
				}

				// VCEEncC
				if (strpos($value['CommandLine'], $vceencc_exe) !== false and 
				(@strpos($value['CommandLine'], strval($stream_port)) !== false) or (@strpos($value['CommandLine'], $filepath) !== false)){
					win_exec('taskkill /F /PID '.$value['ProcessId']);
					// echo 'VCEEncC Killed. Stream: '.$stream.' Cmd:'.$value['CommandLine']."\n\n";
				}


				// TSTask
				if (strpos($value['CommandLine'], $tstask_exe) !== false and 
				(@strpos($value['CommandLine'], strval($stream_port)) !== false) or (@strpos($value['CommandLine'], $filepath) !== false)){
					if ($TSTask_shutdown == 'true'){ // 強制終了
						win_exec('taskkill /F /PID '.$value['ProcessId']);
					} else { // 通常終了
						win_exec('taskkill /PID '.$value['ProcessId']);
					}
					// echo 'TSTask Killed. Stream: '.$stream.' Cmd:'.$value['CommandLine']."\n\n";
				}

			}

			// CSVファイル自体はもういらないので削除
			unlink($process_csv);

			// フォルダ内のTSを削除
			win_exec('pushd "'.$segment_folder.'" && del stream'.$stream.'*.ts /S');

		}

	}
