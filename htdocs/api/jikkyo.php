<?php

// カレントディレクトリを modules/ 以下に変更（こうしないと読み込んでくれない）
chdir('../../modules/');

// クラスローダーを読み込み
require_once ('classloader.php');

if (!isset($_COOKIE['tvrp_csrf_token']) || !is_string($_COOKIE['tvrp_csrf_token']) ||
    !isset($_POST['_csrf_token']) || $_POST['_csrf_token'] !== $_COOKIE['tvrp_csrf_token']) {
	trigger_error('Csrf token error', E_USER_ERROR);
}

// インスタンスを初期化
new JikkyoController();
