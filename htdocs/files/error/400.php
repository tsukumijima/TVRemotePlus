<?php
http_response_code(500);
?>
<!Doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <title>400 Bad Request</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="refresh" content="3; URL='<?php echo 'https://'.$_SERVER["HTTP_HOST"].'/'; ?>'" />
</head>
<body>
  <h2>400 Bad Request</h2>
  <p>
    https:// 用の URL に http:// でアクセスした場合に発生するエラーです。<br>
    3秒後に自動で https:// 用の URL に遷移します。<br>
    遷移しない場合、<a href="<?php echo 'https://'.$_SERVER["HTTP_HOST"].'/'; ?>">こちら</a>をクリックしてください。<br>
  </p>
</body>
</html>