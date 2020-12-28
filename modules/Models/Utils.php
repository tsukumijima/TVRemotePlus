<?php

require_once ('classloader.php');

class Utils {

    /**
     * TVRemotePlus が外部 API にアクセスする際に設定するユーザーエージェントを返す
     *
     * @return string ユーザーエージェント
     */
    public static function getUserAgent(): string {
        require ('require.php');
        return 'TVRemotePlus/'.str_replace('v', '', $version);
    }
}
