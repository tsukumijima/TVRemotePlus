<?php

// サムネイルが403ならデフォルト画像を返すようにする
if (strpos($_SERVER['REQUEST_URI'], '/files/info/') !== false){
  http_response_code(200);
	header('Content-Type: image/jpg');
	readfile('../thumb_default.jpg');	
}

// 通常のなら403ページを表示
http_response_code(403);

?>
<!Doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <title>403 Forbidden</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="refresh" content="3; URL='/'" />
</head>
<body>
  <h2>403 Forbidden</h2>
  <p>
    アクセスが禁止されています。<br>
    3秒後に自動でトップページに遷移します。<br>
    遷移しない場合、<a href="/">こちら</a>をクリックしてください。<br>
  </p>
</body>
</html>