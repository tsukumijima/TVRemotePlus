<?php

// サムネイルが404ならデフォルト画像を返すようにする
if (strpos($_SERVER['REQUEST_URI'], '/files/thumb/') !== false){
  http_response_code(200);
	header('Content-Type: image/jpg');
	readfile('../thumb_default.jpg');	
}

// 通常のなら404ページを表示
http_response_code(404);

?>
<!Doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <title>404 Not Found</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="refresh" content="3; URL='/'" />
</head>
<body>
  <h2>404 Not Found</h2>
  <p>
    お探しのページは見つかりませんでした。<br>
    3秒後に自動でトップページに遷移します。<br>
    遷移しない場合、<a href="/">こちら</a>をクリックしてください。<br>
  </p>
</body>
</html>