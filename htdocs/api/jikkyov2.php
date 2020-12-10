<?php

// カレントディレクトリを modules/ 以下に変更（こうしないと読み込んでくれない）
chdir('../../modules/');

// クラスローダーを読み込み
require_once ('classloader.php');

// インスタンスを初期化
new JikkyoController();
