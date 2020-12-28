<?php

/**
 * Classが定義されていない場合に、ファイルを探すクラス
 * 参考: https://qiita.com/misogi@github/items/8d02f2eac9a91b4e6215
 */
class ClassLoader
{
    // class ファイルがあるディレクトリのリスト
    private static $dirs;

    /**
     * クラスが見つからなかった場合呼び出されるメソッド
     * spl_autoload_register でこのメソッドを登録してください
     * 
     * @param  string $class 名前空間など含んだクラス名
     * @return bool 成功すればtrue
     */
    public static function loadClass($class)
    {
        foreach (self::directories() as $directory) {
            // 名前空間や疑似名前空間をここでパースして
            // 適切なファイルパスにしてください
            $file_name = "{$directory}/{$class}.php";

            if (is_file($file_name)) {
                require $file_name;

                return true;
            }
        }
    }

    /**
     * ディレクトリリスト
     * 
     * @return array フルパスのリスト
     */
    private static function directories()
    {
        if (empty(self::$dirs)) {
            $base = str_replace('\\', '/', dirname(__FILE__));
            self::$dirs = array(
                // ここに読み込んでほしいディレクトリを足していきます
                $base . '/Controllers',
                $base . '/Models'
            );
        }

        return self::$dirs;
    }
}

// これを実行しないとオートローダーとして動かない
spl_autoload_register(array('ClassLoader', 'loadClass'));
