
# TVRemotePlus-Launcher

TVRemotePlus を起動するためのランチャーアプリです。C# (WPF) で構築されています。

TVRemotePlus の起動中、サーバー (Apache) のコンソールウインドウがずっとタスクバーに居座ってしまう問題を解消するために開発されました。  
起動するとタスクトレイに格納され、Apache はバックグラウンドで実行されます。  

## Usage

TVRemotePlus.exe をダブルクリックして起動すると、タスクトレイにアイコンが表示されます。少し時間がかかるかもしれません。  
実行には .NET Framework 4.8 以降が必要です。もしインストールしていない場合は Windows Update を実行するか、[こちら](https://www.ipentec.com/document/windows-install-dotnet-framework-48-runtime) を参考にランタイムのインストールを行ってください。

![Screenshot](https://user-images.githubusercontent.com/39271166/90086206-d8a67380-dd54-11ea-8734-7217648429c5.png)

タスクトレイのアイコンを右クリックするとメニューが表示されます。  
[TVRemotePlus にアクセス]・[TVRemotePlus にアクセス (HTTPS)] をクリックすると、TVRemotePlus の Web アプリに遷移します。  

![Screenshot](https://user-images.githubusercontent.com/39271166/90086195-d47a5600-dd54-11ea-8f79-25b69ec91885.png)

[サーバー (Apache) の設定] をクリックすると、設定ウインドウが表示されます。  

\[設定] タブではサーバーの設定を行えるようにする予定ですが、現時点ではこの画面からの設定変更はできません。  
サーバーの設定を編集する場合は TVRemotePlus をインストールしたフォルダの bin/Apache/conf/httpd.conf を手動で編集してください。  

![Screenshot](https://user-images.githubusercontent.com/39271166/90086201-d6dcb000-dd54-11ea-9961-de63909c43c3.png)

\[ログ] タブではサーバーの起動ログを閲覧できます。  
ログは複数選択でき、右クリックでクリップボードにコピーできます。Apache を起動できなかった / 異常終了したときのログもここに表示されます。

[サーバー (Apache) を終了] をクリックすると、TVRemotePlus を終了できます。  
サーバーを終了すると、TVRemotePlus の Web アプリにもアクセスできなくなります。再度アクセスするときはもう一度 TVRemotePlus.exe を実行してください。  
スタートアップに TVRemotePlus.exe のショートカットを登録しておくと、PC 起動時に自動で TVRemotePlus サーバーを起動できます。

## License

本体のライセンスに準じます。

[MIT License](LICENSE.txt)
