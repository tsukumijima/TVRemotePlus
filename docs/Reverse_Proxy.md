# リバースプロキシで TVRemotePlus に外出先からアクセスする（上級者向け）

外部ネットワークに公開する PC や （ VPN 経由で予め同じネットワークに繋げた）VPS 等に予めドメインを割り当て（ここでは example.com とします）、  
そこから https://example.com/ や https://example.com/tvrp/ などの URL で TVRemotePlus にアクセス出来るように出来ます。  

ここでは、リバースプロキシを使い外出先からアクセスする方法を解説します。  
初心者の方にはお勧めしません（後述）。VPN を使ってアクセス出来るようにするのが何かと手っ取り早いと思いますが、ここでは割愛します。

## 注意

 - **既に Apache や nginx などの Web サーバーについて比較的知識がある人向けです**
   - よくわからない方（ Apache や nginx が何か分からない人・設定ファイルを弄ったことがない人・リバースプロキシの意味が全くわからない人）はやらない方が良いと思います…  
   - また、**設定を誤ると外部からの攻撃に脆弱になる可能性があります**・細心の注意を払って作業してください
 - リバースプロキシとは、TVRemotePlus のサーバー PC に直接アクセスするのではなく、リバースプロキシになる PC が代わりに TVRemotePlus にアクセスし、そのデータをそっくりそのまま返してくる（アクセスにワンクッションおく）事を指します（詳しくは [Wikipedia](https://ja.wikipedia.org/wiki/%E3%83%AA%E3%83%90%E3%83%BC%E3%82%B9%E3%83%97%E3%83%AD%E3%82%AD%E3%82%B7) や [リバースプロキシ (reverse proxy) とは](https://wa3.i-3-i.info/word1755.html) を参照してください）
 - **予め Let's Encrypt を使い、HTTPS で接続出来るようにしておいてください**
   - HTTP 接続の場合、通信内容が盗聴される可能性があります・セキュリティ向上の為、 HTTPS にすることを強く推奨します
 - V6プラスを契約している環境では、ポート解放が出来ない場合があります
   - VPS を契約した上で OpenVPN サーバーを VPS に建て、TVRemotePlus のサーバ PC と VPN で接続し、VPS をリバースプロキシにすることも出来ます（難易度高）
 - リバースプロキシを利用せず直接 TVRemotePlus のサーバー PC を外部に公開することも出来るとは思いますが、外部からの攻撃に常に晒される事になるためお勧めしません
 - **Apache と nginx は同じ Web サーバー用ソフトウェアです。どちらか好きな方を選んでリバースプロキシにする PC にインストール・設定してください**
   - 両方設定する必要はありません（むしろポートがバッティングしてどっちかが落ちます）
   - Apache を使う場合は［Apache の設定］の方を、nginx を使う場合は［nginx の設定］の方を参照してください

## TVRemotePlus 側の設定

 1. 設定ページから、［リバースプロキシからアクセスする場合の URL］の箇所を各自のリバースプロキシの URL に変更します。
 2. また、Twitter 投稿機能を使う場合は、[こちら](Twitter_Develop.md) を参考に Callback URLs にリバースプロキシの URL を追加してください。
 3. セキュリティ向上のため、Basic 認証なしで利用する場合は［リバースプロキシからのアクセス時に環境設定を非表示にする］の設定をオンにしておくことをおすすめします。

## Apache の設定
ここでは https://example.com/tvrp/ でアクセス出来るようにします（お好みで tvrp の部分を書き換えてください）。  
TVRemotePlus のポートはデフォルトの 8000・8100 としています（変更している場合は適宜書き換えてください）。  
https://example.com/ でアクセスする場合は、<Location /tvrp/></Location> と RequestHeader unset Accept-Encoding から下の書き換え関連の項目を適宜コメントアウトしてください。  
予め、前述のように Let's Encrypt でアクセス出来る事が前提です。

この他、mod_proxy mod_proxy_http mod_headers mod_substitute（いずれも Apache の拡張機能）を利用します。  
Ubuntu であれば `a2enmod proxy proxy_http headers substitute` と実行、  
その他の環境であれば　httpd.conf のコメントアウトを適宜解除するなどして、拡張機能を予め有効化しておいてください。

    <IfModule mod_ssl.c>
    <VirtualHost *:443>
      ServerName example.com
      ServerAdmin webmaster@example.com
      DocumentRoot (ドキュメントルートへのパス)
      <IfModule mod_php5.c>
        php_value include_path "."
      </IfModule>
      Include /etc/letsencrypt/options-ssl-apache.conf
      SSLCertificateFile ( Let's Encrypt で作成した example.com の HTTPS 用証明書へのパス)
      SSLCertificateKeyFile ( Let's Encrypt で作成した example.com の HTTPS 用暗号鍵へのパス)
      ProxyRequests off
      SSLProxyEngine on
      <Location /tvrp/>
        # require mod_proxy mod_proxy_http mod_headers mod_substitute (use a2enmod)
        ProxyPass http://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8000/
        ProxyPassReverse http://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8000/
        ProxyPassReverseCookieDomain (TVRemotePlusをインストールしたPCのローカルIPアドレス):8000 example.com
        ProxyPassReverseCookiePath / /tvrp/
        RequestHeader unset Accept-Encoding
        SetEnvIf Host .* filter-errordocs
        AddOutputFilterByType SUBSTITUTE text/plain text/html application/json application/javascript text/javascript text/css
        Substitute "s|http://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8000/|https://example.com/tvrp/|q"
        Substitute "s|https://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8100/|https://example.com/tvrp/|q"
        Substitute "s|/api/chromecast|/tvrp/api/chromecast|q"
        Substitute "s|/api/epginfo|/tvrp/api/epginfo|q"
        Substitute "s|/api/jikkyo|/tvrp/api/jikkyo|q"
        Substitute "s|/api/listupdate|/tvrp/api/listupdate|q"
        Substitute "s|/api/status|/tvrp/api/status|q"
        Substitute "s|/api/stream|/tvrp/api/stream|q"
        Substitute "s|/files/|/tvrp/files/|q"
        Substitute "s|/stream/|/tvrp/stream/|q"
        Substitute "s|/tweet/|/tvrp/tweet/|q"
        Substitute "s|/settings/|/tvrp/settings/|q"
        Substitute "s|/watch/|/tvrp/watch/|q"
        Substitute "s| \"/\"| \"/tvrp/\"|q"
        Substitute "s|href=\"/|href=\"/tvrp/|q"
        Substitute "s|data-url=\"/|data-url=\"/tvrp/|q"
        Substitute "s|URL='/'|URL='/tvrp/'|q"
        Substitute "s|/serviceworker.js|/tvrp/serviceworker.js|q"
      </Location>
    </VirtualHost>
    </IfModule>

## nginx の設定
ここでは https://example.com/tvrp/ でアクセス出来るようにします（お好みで tvrp の部分を書き換えてください）。  
TVRemotePlus のポートはデフォルトの 8000・8100 としています（変更している場合は適宜書き換えてください）。  
https://example.com/ でアクセスする場合は、location /tvrp/ { の括弧と sub_filter がついている書き換え関連の項目を適宜コメントアウトしてください。  
予め、前述のように Let's Encrypt でアクセス出来る事が前提です。  

    server {
        listen  443 ssl;
        server_name  example.com;
    
        ssl_certificate     (Let's Encrypt で作成した example.com の HTTPS 用証明書へのパス);
        ssl_certificate_key (Let's Encrypt で作成した example.com の HTTPS 用暗号鍵へのパス);
      
        location /tvrp/ {
            sub_filter_once off;
            sub_filter_types text/plain text/css text/xml application/json application/javascript;
          
            sub_filter "http://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8000/" "https://example.com/tvrp/";
            sub_filter "https://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8100/" "https://example.com/tvrp/";
            sub_filter "/api/chromecast" "/tvrp/api/chromecast";
            sub_filter "/api/epginfo" "/tvrp/api/epginfo";
            sub_filter "/api/jikkyo" "/tvrp/api/jikkyo";
            sub_filter "/api/listupdate" "/tvrp/api/listupdate";
            sub_filter "/api/status" "/tvrp/api/status";
            sub_filter "/api/stream" "/tvrp/api/stream";
            sub_filter "/files/" "/tvrp/files/";
            sub_filter "/stream/" "/tvrp/stream/";
            sub_filter "/tweet/" "/tvrp/tweet/";
            sub_filter "/settings/" "/tvrp/settings/";
            sub_filter "/watch/" "/tvrp/watch/";
            sub_filter '"start_url": "/"' '"start_url": "/tvrp/"';
            sub_filter 'href="/"' 'href="/tvrp/"';
            sub_filter 'data-url="/"' 'data-url="/tvrp/"';
            sub_filter "URL='/'" "URL='/tvrp/'";
            sub_filter "/serviceWorker.js" "/tvrp/serviceWorker.js";
            sub_filter "Cookies.set('settings', json)" "Cookies.set('settings', json, {path: '/tvrp/'})";
          
            proxy_cookie_path / /tvrp/;
            proxy_set_header Accept-Encoding "";
            proxy_pass http://(TVRemotePlusをインストールしたPCのローカルIPアドレス):8000/;
        }
    }

