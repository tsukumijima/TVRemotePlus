
# TwitterAPI 開発者アカウントの取得について

TVRemotePlus のツイート機能を利用するには、まずお使いの Twitter アカウントで Twitter API の開発者アカウントを取得する必要があります。  
ですが、スパムをばら撒くような不正利用が横行しているせいか、近年開発者アカウントの登録申請が厳しくなってしまっています。

ここでは、私が TwitterAPI 開発者アカウントを取得した際の備忘録を（参考程度に）載せています。  
一部現状と異なっている場合もあるかもしれませんが、取得の際の参考にしてください。  
ただし、長くなってしまうため、あまり詳細な方法は解説しません。  
解説されている記事は沢山あるので、それを参考にしてください。

## 開発者アカウントの取得

まず、https://developer.twitter.com/en/apps にアクセスし、「 Create an app 」をクリックし、  
「開発者アカウントを取得してね」と出るので、「Apply」をクリックします。  

### What is your primary reason for using Twitter developer tools?

Making a Bot を選択し、「 Next 」をクリックします｡

### Individual developer account

#### What country do you live in?

Japan を選択します。

#### What would you like us to call you?

開発者アカウントの名前を入力します。  
基本的にはいつもの Twitter のハンドルネームで良いと思います。

#### Want updates about the Twitter API?

TwitterAPI に関するメールを受け取りたい場合はチェックします。

以上を設定したら、「 Next 」をクリックします｡

### Key things to keep in mind

#### In your words

ここで、英語で 200 文字以上、TwitterAPI を取得する理由を説明します。  

日本語で書いて日本語→英語、英語→日本語と Google 翻訳して、意味が違ってないか確認しながら書くのがいいと思います。  
この文章を書く際、確認としてサブアカウントにて以前通った時の文をコピペして申請したら「申請されませんでした」と一発却下され、  
以後そのアカウントで認証できなくなってしまったので、あくまで「自分の言葉で」書いてください…（そのため、コピペ用の文章は敢えて置いていません）   
一度入力した内容はもしかすると再度コピペすると一発却下されるようになってしまったのかもしれません…

#### The specifics

##### Are you planning to analyze Twitter data?

ツイートなどの分析はしないので、「 No 」にします。

##### Will your app use Tweet, Retweet, like, follow, or Direct Message functionality?

利用するので、英語で 100 文字以上、機能の利用予定を説明します。
こちらも同じく、「自分の言葉で」書いてください…

##### Do you plan to display Tweets or aggregate data about Twitter content outside of Twitter?

集計したデータを表示したりはしないので、「 No 」にします。

##### Will your product, service or analysis make Twitter content or derived information available to a government entity?

Twitter コンテンツが政府機関に利用可能にはならないので、「 No 」にします。

以上を設定したら、「 Next 」をクリックします｡

### Check your information

今までの入力内容が表示されるので、確認してOKなら、「 Looks Good! 」をクリックします。

### Developer Agreement & Policy

利用規約への同意を求められるので、最後までスクロールした後、下のチェックボックスにクリックし、  
「 Submit Application 」をクリックし、開発者登録を申請します。

### You did!

と出るので、Twitterアカウントに紐づけているメールアドレス宛に送られてくるメールを確認します。

### メール確認

多分（申請が通れば）「 Thanks for applying for a Twitter Developer account.  
Please confirm your email address to complete your application. 」  
というメールが来ると思うので、「 Confirm your email 」をクリックします。

### Application Under Review

と出るので、メールが届くまで待ちます。  
何回か追加の情報を求められる場合があります。その際は、そのメールに追加情報を書いて返信してください。  
上記の通り、この文章を書く際に、確認として以前通った文を修正・コピペして登録を申請した所、一発でリジェクトされてしまい、  
以後そのアカウントでは登録申請ができなくなってしまったので、本当に申請が通るかは微妙です… 頑張ってくださいとしか…

## アプリ作成画面フォーム(例)

もし開発者アカウントを取得できたら、ようやくアプリの作成に入ります。  
このアプリのコンシューマーキー・コンシューマーシークレットキーを TVRemotePlus に登録し、  
ここで作ったアプリに TVRemotePlus からお使いの Twitter アカウントでアプリ連携することで、ツイートできるようになります。  
アカウント作成は通っているので、App name・Callback URLs 以外は適当で大丈夫だと思います。

### App name(必須・重複不可らしい)

ここの名前がツイートの via として表示されます。  
いわゆる「独自 via 」と呼ばれるものです。後で変えることもできるので、好きな via にしましょう。

（例）TVRemotePlus@（自分の TwitterID ）  
（例）Twitter for （自分の Twitter 名）  

### Application description(必須)

（例）TVRemotePlus@example からツイートを投稿するためのアプリケーションです。

### Website URL（必須）

（例）https://example.com/  
（例）https://(自分のサイトのドメイン)/

### Enable Sign in with Twitter

必ずチェックを入れます（入れないと Twitter ログインができません）

### Callback URLs

http://(稼働させているPCのLAN内IPアドレス):8000/tweet/callback.php と入力  
（例）http://192.168.1.11:8000/tweet/callback.php

HTTPS接続用にもう一つ、https://(稼働させているPCのLAN内IPアドレス):8100/tweet/callback.php と入力  
（例）https://192.168.1.11:8100/tweet/callback.php

### Terms of service URL
無記入で OK

### Privacy policy URL

無記入で OK

### Organization name

無記入で OK

### Organization website URL

無記入で OK

### Tell us how this app will be used（必須）  

（例）私が運営している TVRemotePlus という私のためだけの非公開のサイトから、直接つぶやきを投稿できるようにするアプリケーションです。  
私のアプリケーションはツイートと1枚の画像だけを TVRemotePlus から投稿します。スパムに使うことはありません。  
投稿したツイートに返事があった場合は手動で返信したいと思っています。  
「 ReTweet 」・「 Likes 」はしません。

#### (英語・こちらをコピペ)

It is an application that allows you to post tweets directly from  TVRemotePlus, a private site only for me, which I operate.  
My application only posts tweets and one image from TVRemotePlus. It is not used for spam.  
I would like to reply manually if there is a reply to the posted tweets.
"ReTweet" · "Likes" does not do.  


