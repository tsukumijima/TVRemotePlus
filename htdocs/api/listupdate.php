<?php

	// モジュール読み込み
	require_once ('../../modules/require.php');
	require_once ('../../modules/module.php');
	require_once ('../../modules/classloader.php');

	// Symfony/Process を読み込み
	use Symfony\Component\Process\Exception\ProcessTimedOutException;
	use Symfony\Component\Process\Process;

	// かなり長くなることがあるので実行時間制限をオフに
	ignore_user_abort(true);
	set_time_limit(0);

	// jsonからデコードして代入
	$TSfile = file_get_contents_lock_sh($infofile);
	if ($TSfile !== false) {
		$TSfile = json_decode($TSfile, true);
	} else {
		$TSfile = ['data' => []];
	}

	// リストリセット
	if (isset($_GET['list_reset'])){

		// jsonを空にする
		file_put_contents($infofile, json_encode(['data' => []], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
		// ロックファイルを削除
		@unlink($infofile.'.lock');

		$json = [
			'api' => 'listupdate',
			'status' => 'list_reset',
		];

		// レスポンス
		echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

		exit;
	}

	// 再生履歴を削除
	if (isset($_GET['history_reset'])){

		// jsonを空にする
		file_put_contents($historyfile, json_encode(['data' => []], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);

		$json = [
			'api' => 'listupdate',
			'status' => 'history_reset',
		];

		// レスポンス
		echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

		exit;
	}

	// 録画終了時刻を計算する関数
	function calc_end_time($start, $duration){

		// それぞれ切り出す
		$start_hour = mb_substr($start, 0, 2);
		$start_min = mb_substr($start, 3, 2);

		// 十の位が0なら消す
		if (mb_substr($start_hour, 0, 1) == 0) $start_hour = mb_substr($start_hour, 1, 1);
		if (mb_substr($start_min, 0, 1) == 0) $start_min = mb_substr($start_min, 1, 1);

		// 数値に変換
		$start_hour = intval($start_hour);
		$start_min = intval($start_min);

		if ($start_hour !== 0){
			// 繰り上がり
			$start = ($start_hour * 60) + $start_min;
		} else {
			// 繰り上がらない
			$start = $start_min;
		}

		// 開始時間と長さを足して終了時間(分)を出す
		$end_ = $start + $duration;
		// 割って繰り上がりさせる
		$end_hour = floor($end_ / 60);
		$end_min = floor($end_ % 60);

		// 0埋めする
		$end = sprintf('%02d', $end_hour).':'.sprintf('%02d', $end_min);

		return $end;
	}

	// 録画ファイルの長さ(分)を計算する関数
	function calc_duration_time($duration){

		// それぞれ切り出す
		$duration_hour = mb_substr($duration, 0, 2);
		$duration_min = mb_substr($duration, 3, 2);

		// 十の位が0なら消す
		if (mb_substr($duration_hour, 0, 1) == 0) $duration_hour = mb_substr($duration_hour, 1, 1);
		if (mb_substr($duration_min, 0, 1) == 0) $duration_min = mb_substr($duration_min, 1, 1);

		// 数値に変換
		$duration_hour = intval($duration_hour);
		$duration_min = intval($duration_min);

		if ($duration_hour !== 0){
			// 繰り上がり
			$duration = ($duration_hour * 60) + $duration_min;
		} else {
			// 繰り上がらない
			$duration = $duration_min;
		}

		return $duration;
	}

	// $TSfile['data'] にはファイル自体の情報を配列として格納、
	// $TSfile['info'] にはファイルの情報をキー名を拡張子なしファイル名とした連想配列に格納
	// $TSfile['data'] は今の所リスト更新毎に $TSfile['info'] からデータを持ってきて作り直している
	// このあたりかなりコードぐっちゃぐちゃなのでそのうち作り直したい

	// lockファイルがない or 手動更新なら
	if (!file_exists($infofile.'.lock') or isset($_GET['manual'])){

		// lockファイルを作成
		file_put_contents($infofile.'.lock', '');

		// ファイルを四階層まで検索する
		// MP4・MKVファイルも検索する
		$search_extensions = ['ts', 'mts', 'm2t', 'm2ts', 'mp4', 'mkv'];
		$search = @scandir_and_match_files($TSfile_dir, '/.\\.('.implode('|', $search_extensions).')$/i', 4);
		if ($search === false) {
			$search = [];
		}

		// 拡張子順にソート
		usort($search, function ($a, $b) use ($search_extensions) {
			$cmp = array_search(strtolower(substr($a, strrpos($a, '.') + 1)), $search_extensions, true) -
			       array_search(strtolower(substr($b, strrpos($b, '.') + 1)), $search_extensions, true);
			return $cmp !== 0 ? $cmp : strcmp($a, $b);
		});

		// $TSfile['data'] のデータを初期化する
		$TSfile['data'] = [];

		foreach ($search as $key => $value) {

			// 誤作動の原因になるので変数は破棄しておく
			unset($ffmpeg_process, $ffmpeg_process_successful, $rplsinfo_process, $rplsinfo_result, $ffprobe_process);

			// 録画ファイル保存フォルダからのパスを含めたファイル名
			$TSfile['data'][$key]['file'] = '/'.$value;
			// Pathinfo
			$TSfile['data'][$key]['pathinfo'] = pathinfo($TSfile_dir.'/'.$value);
			// 拡張子なしファイル名を暫定でタイトルにしておく
			$TSfile['data'][$key]['title'] = decorateMark(str_replace('　', ' ', $TSfile['data'][$key]['pathinfo']['filename']));
			// タイトル(HTML抜き)
			$TSfile['data'][$key]['title_raw'] = str_replace('　', ' ', $TSfile['data'][$key]['pathinfo']['filename']);
			// ファイルの更新日時(Unix時間)
			$TSfile['data'][$key]['update'] = filemtime($TSfile_dir.'/'.$value);

			// dirnameは削除しておく(セキュリティ上の問題)
			$pathinfo_dirname = $TSfile['data'][$key]['pathinfo']['dirname'];
			unset($TSfile['data'][$key]['pathinfo']['dirname']);

			// ファイル名のmd5
			$md5 = md5($TSfile_dir.'/'.$value);

			// 現在の録画ファイルに対応する $TSfile['info'] の値
			$TSfile_info = @$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']];

			// サムネイルが存在するなら
			if (file_exists("{$base_dir}htdocs/files/thumb/{$md5}.jpg")) {

				$TSfile['data'][$key]['thumb_state'] = 'generated'; // サムネイル生成フラグを generated に
				$TSfile['data'][$key]['thumb'] = $md5.'.jpg'; // サムネイル画像のパス(拡張子なしファイル名のmd5)

			// 以前サムネイル生成に失敗している場合
			// サムネイル生成に失敗した＝壊れてるTSファイルなので、毎回生成させると時間を食う
			} else if ($TSfile_info !== null and array_key_exists('thumb_state', $TSfile_info) and $TSfile_info['thumb_state'] === 'failed') {

				$TSfile['data'][$key]['thumb_state'] = 'failed'; // サムネイル生成フラグを failed に
				$TSfile['data'][$key]['thumb'] = 'thumb_default.jpg'; // サムネイル画像のパス

			} else {

				// サムネイルがないならデフォルトのサムネイル画像にする
				$TSfile['data'][$key]['thumb'] = 'thumb_default.jpg'; // サムネイル画像のパス

				// ffmpeg でサムネイルを生成するコマンドを実行 (開始 72 秒でキャプチャを撮る)
				// タイムアウトは 10 秒（タイムアウトを設定するために Symfony/Process を導入したようなもの）
				$ffmpeg_process = new Process([
					$ffmpeg_path, '-y', '-ss', '72', '-i', "{$TSfile_dir}/{$value}",
					'-vframes', '1', '-f', 'image2', '-s', '480x270', "{$base_dir}htdocs/files/thumb/{$md5}.jpg",
				]);
				$ffmpeg_process->setTimeout(10);
				$ffmpeg_process_successful = true;
				try {
					$ffmpeg_process->run();
					$ffmpeg_process_successful = $ffmpeg_process->isSuccessful();
				} catch (ProcessTimedOutException $ex) {  // タイムアウトした場合
					$ffmpeg_process_successful = false;
				}

				// コマンドの実行に成功 & 実際にサムネイル画像が生成されている
				if ($ffmpeg_process_successful === true and file_exists("{$base_dir}htdocs/files/thumb/{$md5}.jpg")){

					// サムネイル生成フラグを generated に
					$TSfile['data'][$key]['thumb_state'] = 'generated';

				// コマンドの実行に失敗
				} else {

					// サムネイル生成フラグを failed に
					$TSfile['data'][$key]['thumb_state'] = 'failed';

					// ffmpeg のコマンド自体は成功していて、かつ ffmpeg のコマンド出力に "Output file is empty, nothing was encoded (check -ss / -t / -frames parameters if used)" が含まれる場合
					// 録画データが 72 秒未満だったため、72 秒までシークできずにサムネイルを生成できなかったと考えられる
					if ($ffmpeg_process_successful === true and str_contains($ffmpeg_process->getErrorOutput(), 'Output file is empty, nothing was encoded (check -ss / -t / -frames parameters if used)')) {

						// 開始 30 秒でキャプチャを撮り直してみる
						$ffmpeg_process = new Process([
							$ffmpeg_path, '-y', '-ss', '30', '-i', "{$TSfile_dir}/{$value}",
							'-vframes', '1', '-f', 'image2', '-s', '480x270', "{$base_dir}htdocs/files/thumb/{$md5}.jpg",
						]);
						$ffmpeg_process->setTimeout(10);
						try {
							$ffmpeg_process->run();
						} catch (ProcessTimedOutException $ex) {  // タイムアウトした場合
							// 何もしない
						}

						// コマンドの実行に成功 & 実際にサムネイル画像が生成されている
						// 2回目はうまくいったので、サムネイル生成フラグを generated にする
						if ($ffmpeg_process_successful === true and file_exists("{$base_dir}htdocs/files/thumb/{$md5}.jpg")){
							$TSfile['data'][$key]['thumb_state'] = 'generated';
						}
					}

					// この時点でサムネイル生成フラグが failed で、かつファイルの最終更新時刻から 10 分経過している場合
					// 録画中はサムネイル生成ができない事があるが、最終更新時刻から時間が立っているならその可能性もない
					// 今後サムネイルの再生成を試みても失敗する可能性が高いため、$TSfile['info'] の方の thumb_state も failed に設定する
					clearstatcache();  // 念のためキャッシュをクリアしておく
					if ($TSfile['data'][$key]['thumb_state'] === 'failed' and filemtime("{$TSfile_dir}/{$value}")) {
						if ($TSfile_info !== null) {
							$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['thumb_state'] = 'failed';
						} else {
							// 何らかの理由で $TSfile['info'] にキーがない場合
							$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = [];
							$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['thumb_state'] = 'failed';
							$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']]['info_state'] = 'failed';
						}
						// 今後の参照のために更新しておく
						$TSfile_info = @$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']];
					}
				}

				// サムネイル画像のパス(拡張子なしファイル名のmd5)
				// 仮になかった場合デフォルト画像が表示される
				$TSfile['data'][$key]['thumb'] = "{$md5}.jpg";
			}

			// デフォルト値（取得に失敗したとき用）
			$TSfile['data'][$key]['date'] = date('Y/m/d', $TSfile['data'][$key]['update']); // 更新日時から推測
			$TSfile['data'][$key]['info_state'] = 'failed'; // 番組情報取得フラグ
			$TSfile['data'][$key]['info'] = '取得できませんでした';
			$TSfile['data'][$key]['channel'] = '取得失敗';
			$TSfile['data'][$key]['start'] = date('H:i', $TSfile['data'][$key]['update'] - (30 * 60));
			$TSfile['data'][$key]['end'] = date('H:i', $TSfile['data'][$key]['update']);
			$TSfile['data'][$key]['duration'] = '30?';
			$TSfile['data'][$key]['start_timestamp'] = $TSfile['data'][$key]['update'] - (30 * 60); // 分からないので取りあえず30分引いとく
			$TSfile['data'][$key]['end_timestamp'] = $TSfile['data'][$key]['update'];

			// 番組情報が取得できているなら
			if ($TSfile_info !== null and array_key_exists('info_state', $TSfile_info) and $TSfile_info['info_state'] == 'generated'){

				// 拡張子の情報を一時的に保管
				$extension = $TSfile['data'][$key]['pathinfo']['extension'];

				// 前に取得した情報を読み込む
				// MP4・MKVからは番組情報を取得できないので、同じファイル名のTSがあればその番組情報を使う
				$TSfile['data'][$key] = $TSfile_info;

				// ファイルパスがTSのものに上書きされてしまうのでここで戻しておく
				$TSfile['data'][$key]['file'] = '/'.$value;

				// 拡張子も.tsとして上書きされてしまうのでこれも戻しておく（ついでに小文字化）
				$TSfile['data'][$key]['pathinfo']['extension'] = strtolower($extension);

				// 番組情報取得フラグ
				$TSfile['data'][$key]['info_state'] = 'generated';

			// 番組情報を取得していないなら
			// MP4・MKVには番組情報は含まれていないので除外
			} else if ($TSfile['data'][$key]['pathinfo']['extension'] != 'mp4' and $TSfile['data'][$key]['pathinfo']['extension'] != 'mkv'){

				// rplsinfo でファイル情報を取得
				// タイムアウトは 30 秒
				$rplsinfo_process = new Process([$rplsinfo_path, '-C', '-dtpcbieg', '-l', '10', "{$TSfile_dir}/{$value}"]);
				$rplsinfo_process->setTimeout(30);
				try {
					$rplsinfo_process->run();
				} catch (ProcessTimedOutException $ex) {  // タイムアウトした場合
					// 何もしない
				}

				// 取得成功
				if ($rplsinfo_process->isSuccessful()){

					$rplsinfo_result = mb_convert_encoding($rplsinfo_process->getOutput(), 'UTF-8', 'SJIS-WIN'); // 実行結果の配列を連結して一旦文字列に
					// 正規表現でエラーメッセージを置換する
					$rplsinfo_result = preg_replace("/番組情報元ファイル.*?は有効なTS, rplsファイルではありません./", '', $rplsinfo_result);
					$rplsinfo_result = preg_replace("/番組情報元ファイル.*?から有効な番組情報を検出できませんでした./", '', $rplsinfo_result);
					$rplsinfo_result = preg_replace("/番組情報元ファイル.*?を開くのに失敗しました./", '', $rplsinfo_result);

					$fileinfo = str_getcsv(str_replace('　', ' ', mb_convert_kana($rplsinfo_result, 'asv', 'UTF-8'))); // Parseして配列にする

					// 出力
					$TSfile['data'][$key]['title'] = decorateMark($fileinfo[4]); // 取得した番組名の方が正確なので修正
					$TSfile['data'][$key]['title_raw'] = $fileinfo[4]; // 取得した番組名の方が正確なので修正
					$TSfile['data'][$key]['date'] = $fileinfo[0]; // 録画日付
					$TSfile['data'][$key]['info_state'] = 'generated'; // 番組情報取得フラグ
					$TSfile['data'][$key]['info'] = $fileinfo[5]; // 番組情報
					$TSfile['data'][$key]['channel'] = $fileinfo[3]; //チャンネル名
					$TSfile['data'][$key]['start'] = substr($fileinfo[1], 0, strlen($fileinfo[1])-3); // 番組の開始時刻
					$TSfile['data'][$key]['end'] = calc_end_time($fileinfo[1], calc_duration_time($fileinfo[2])); // 番組の終了時刻
					$TSfile['data'][$key]['duration'] = calc_duration_time($fileinfo[2]); // ファイルの時間を算出
					$TSfile['data'][$key]['start_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']); // 開始時刻のタイムスタンプ
					$TSfile['data'][$key]['end_timestamp'] = strtotime($fileinfo[0].' '.$TSfile['data'][$key]['start']) + (calc_duration_time($fileinfo[2]) * 60); // 終了時刻のタイムスタンプ

					// start_timestamp よりも end_timestamp の方が小さい場合は日付を跨いだと計算し1日足す
					if ($TSfile['data'][$key]['start_timestamp'] > $TSfile['data'][$key]['end_timestamp']){
						$TSfile['data'][$key]['end_timestamp'] += 86400;
					}

					unset($TSfile['data'][$key]['tsinfo_state']);

					// 結果を保存する
					$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = $TSfile['data'][$key];
				}
			}

			// この時点で番組情報を取得できていない場合、.ts.program.txt からの取得を試みる
			if ($TSfile['data'][$key]['duration'] === '30?' and !isset($TSfile['data'][$key]['tsinfo_state'])){

				// .ts.program.txt の場所
				// 録画情報フォルダがセット済み
				if (!empty($TSinfo_dir)){

					$program_file = $TSinfo_dir.'/'.$TSfile['data'][$key]['pathinfo']['filename'].'.ts.program.txt';
					$program_file_ = str_replace('\\', '/', $pathinfo_dirname).'/'.$TSfile['data'][$key]['pathinfo']['filename'].'.ts.program.txt';

				// 録画情報フォルダが空（録画ファイルと同じフォルダに設定）
				} else {

					$program_file = str_replace('\\', '/', $pathinfo_dirname).'/'.$TSfile['data'][$key]['pathinfo']['filename'].'.ts.program.txt';
					$program_file_ = $program_file;
				}

				// 実際に .ts.program.txt があれば取得を実行
				// 録画情報フォルダがセットされていた場合でも録画フォルダのほうにあればそれを使う（念のため）
				if (file_exists($program_file) or file_exists($program_file_)){

					// .ts.program.txt を解析
					$program = programToArray($program_file);

					$TSfile['data'][$key]['title'] = decorateMark($program['title']); // 取得した番組名の方が正確なので修正
					$TSfile['data'][$key]['title_raw'] = $program['title']; // 取得した番組名の方が正確なので修正
					$TSfile['data'][$key]['date'] = $program['date']; // 録画日付
					$TSfile['data'][$key]['info_state'] = 'generated'; // 番組情報取得フラグ
					$TSfile['data'][$key]['info'] = $program['info']; // 番組情報
					$TSfile['data'][$key]['channel'] = $program['channel']; //チャンネル名
					$TSfile['data'][$key]['start'] = $program['start']; // 番組の開始時刻
					$TSfile['data'][$key]['end'] = $program['end']; // 番組の終了時刻
					$TSfile['data'][$key]['duration'] = $program['duration']; // ファイルの時間を算出
					$TSfile['data'][$key]['start_timestamp'] = $program['start_timestamp']; // 開始時刻のタイムスタンプ
					$TSfile['data'][$key]['end_timestamp'] = $program['end_timestamp']; // 終了時刻のタイムスタンプ

					unset($TSfile['data'][$key]['tsinfo_state']);

					// 結果を保存する
					$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = $TSfile['data'][$key];

				// ffprobe で動画の長さだけでも取得する
				} else {

					// コマンドを実行
					// タイムアウトはサムネイル生成と同じく 10 秒
					$ffprobe_process = new Process([
						$ffprobe_path, '-i', "{$TSfile_dir}/{$value}",
						'-loglevel', 'quiet', '-show_streams', '-print_format', 'json',
					]);
					$ffprobe_process->setTimeout(10);
					try {
						$ffprobe_process->run();
					} catch (ProcessTimedOutException $ex) {  // タイムアウトした場合
						// 何もしない
					}

					// 動画の情報を取得できた
					if ($ffprobe_process->isSuccessful()){

						// JSON をデコード
						$TSinfo = json_decode($ffprobe_process->getOutput(), true);
						$TSfile['data'][$key]['tsinfo_state'] = 'generated';  // generated に設定

						// 取得した情報を格納
						if (isset($TSinfo['streams'][0]['duration'])){
							$duration = round($TSinfo['streams'][0]['duration']); // 小数点以下四捨五入
						} else {
							$duration = 0; // 取得できなかった場合
						}
						$TSfile['data'][$key]['info_state'] = 'generated';  // 生成できたことにする
						$TSfile['data'][$key]['start_timestamp'] = $TSfile['data'][$key]['update'] - $duration;
						$TSfile['data'][$key]['end_timestamp'] = $TSfile['data'][$key]['update'];
						$TSfile['data'][$key]['start'] = date('H:i', $TSfile['data'][$key]['start_timestamp']).'?';
						$TSfile['data'][$key]['end'] = date('H:i', $TSfile['data'][$key]['end_timestamp']).'?';
						$TSfile['data'][$key]['duration'] = intval(round($duration / 60));

						// 結果を保存する
						$TSfile['info'][$TSfile['data'][$key]['pathinfo']['filename']] = $TSfile['data'][$key];
					}
				}
			}

			// 無駄な空白や改行を削除
			if (isset($TSfile['data'][$key]['title'])) $TSfile['data'][$key]['title'] = trim($TSfile['data'][$key]['title']);
			if (isset($TSfile['data'][$key]['title_raw'])) $TSfile['data'][$key]['title_raw'] = trim($TSfile['data'][$key]['title_raw']);
			if (isset($TSfile['data'][$key]['info'])) $TSfile['data'][$key]['info'] = trim($TSfile['data'][$key]['info']);
		}

		// もう存在しないファイルの情報を削除
		if (isset($TSfile['info'])) {
			$swept_info = [];
			foreach ($TSfile['data'] as $value) {
				if (isset($TSfile['info'][$value['pathinfo']['filename']])) {
					$swept_info[$value['pathinfo']['filename']] = $TSfile['info'][$value['pathinfo']['filename']];
				}
			}
			$TSfile['info'] = $swept_info;
		}

		// ファイルに保存
		file_put_contents($infofile, json_encode($TSfile, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);

		// lockファイルを削除
		@unlink($infofile.'.lock');

		$json = [
			'api' => 'listupdate',
			'status' => 'success',
		];

	} else {

		$json = [
			'api' => 'listupdate',
			'status' => 'duplication',
		];
	}

	// 出力
	header('content-type: application/json; charset=utf-8');
	echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
