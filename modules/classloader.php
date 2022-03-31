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
     * spl_autoload_register でこのメソッドを登録する
     *
     * @param  string $class 名前空間など含んだクラス名
     * @return bool 成功すれば true
     */
    public static function loadClass($class)
    {
        foreach (self::directories() as $directory) {

            // 名前空間や疑似名前空間をここでパースして適切なファイルパスにする
            $class_name = @end(explode('\\', $class));
            $file_name = "{$directory}/{$class_name}.php";

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
            self::$dirs = [
                // ここに読み込んでほしいディレクトリを足していきます
                $base . '/Controllers',
                $base . '/Models',
                $base . '/Thirdparty/ca-bundle/src',
                $base . '/Thirdparty/polyfill-php80',
                $base . '/Thirdparty/polyfill-php80/Resources/stubs',
                $base . '/Thirdparty/Process',
                $base . '/Thirdparty/Process/Exception',
                $base . '/Thirdparty/Process/Pipes',
                $base . '/Thirdparty/TwitterOAuth/src',
                $base . '/Thirdparty/TwitterOAuth/src/Util',
            ];
        }

        return self::$dirs;
    }
}

// これを実行しないとオートローダーとして動かない
spl_autoload_register(['ClassLoader', 'loadClass']);

// polyfill-php80 のブートストラップを実行
require_once (str_replace('\\', '/', dirname(__FILE__)).'/Thirdparty/polyfill-php80/bootstrap.php');
