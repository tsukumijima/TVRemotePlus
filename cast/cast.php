<?php

// Demonstrates class to send pictures/videos to Chromecast
// using reverse engineered Castv2 protocol.
//
// Note: To work from internet you must open a route to port 8009 on
// your Chromecast through your firewall. Preferably with port forwarding
// from a different port address.

@require_once (str_replace('cast', '', dirname(__FILE__)).'config.php');
require_once ('Chromecast.php');

// 引数確認
if (!isset($argv[1])){
	echo '  Error:  Argument is missing or too many, please retry.';
	exit(1);
}

// スキャンモード
if ($argv[1] == 'scan'){

	// Create Chromecast object and give IP and Port
	$scanInfo = Chromecast::scan();
	echo json_encode($scanInfo, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

	exit;
}

echo "\n".'  ***** Chromecast Connect *****'."\n\n";

$cc = new Chromecast($argv[2], $argv[3]);
echo "\n".'  Chromecast Init.'."\n\n";

$cc->DMP->play("http://".$argv[1].":".$http_port."/stream/stream.m3u8", "BUFFERED", "application/vnd.apple.mpegurl", true, 0);
$cc->DMP->UnMute();

// 通知
$cast = json_decode(file_get_contents(dirname(__FILE__).'/cast.json'), true);
$cast['status'] = 'play';
file_put_contents(dirname(__FILE__).'/cast.json', json_encode($cast, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

echo '  Chromecast Play.'."\n\n";

while(true){
	usleep(500);
	set_time_limit(0);
	$cc->pingpong();
	if (isset($cmd)){
		$cmd_old = $cmd;
	} else {
		$cmd_old = '';
	}
	$cmd = json_decode(file_get_contents(dirname(__FILE__).'/cast.json'), true);

	if ($cmd != $cmd_old){

		// 一時停止
		if ($cmd['cmd'] == 'pause'){
			$cc->DMP->pause();
			echo '  Chromecast Pause.'."\n\n";
		}

		// 再生再開
		if ($cmd['cmd'] == 'restart'){
			$cc->DMP->restart();
			echo '  Chromecast Restart.'."\n\n";
		}

		// シーク
		if ($cmd['cmd'] == 'seek'){
			$cc->DMP->seek($cmd['arg']);
			echo '  Chromecast Seek '.$cmd['arg'].'.'."\n\n";
		}

		// 音量
		if ($cmd['cmd'] == 'volume'){
			$cc->DMP->SetVolume($cmd['arg']);
			echo '  Chromecast Volume '.$cmd['arg'].'.'."\n\n";
		}

		// ミュート
		if ($cmd['cmd'] == 'mute'){
			$cc->DMP->Mute();
			echo '  Chromecast Mute.'."\n\n";
		}

		// ミュート解除
		if ($cmd['cmd'] == 'unmute'){
			$cc->DMP->unMute();
			echo '  Chromecast UnMute.'."\n\n";
		}

		// 停止(終了)
		if ($cmd['cmd'] == 'stop'){
			$cc->DMP->Mute();
			$cc->DMP->Stop();
			$cc->disconnect();
			echo '  Chromecast Stop.'."\n\n";
			break;
		}

	}
}

echo '  Chromecast Exit.'."\n\n";

