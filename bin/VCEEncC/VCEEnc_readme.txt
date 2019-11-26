---------------------------------------------------


	VCEEnc
	 by rigaya

---------------------------------------------------

VCEEnc は、
AMDのVCE(VideoCodecEngine)を使用してエンコードを行うAviutlの出力プラグインです。
VCEによるハードウェア高速エンコードを目指します。

【基本動作環境】
Windows 7, 8.1, 10 (x86/x64)
Aviutl 0.99g4 以降
VCEが載ったハードウェア
  AMD製 GPU Radeon HD 7xxx以降
  AMD製 APU Trinity世代(第2世代)以降
AMD Radeon Software Adrenalin Edition 19.7.1 以降


【VCEEnc 使用にあたっての注意事項】
無保証です。自己責任で使用してください。
VCEEncを使用したことによる、いかなる損害・トラブルについても責任を負いません。

【VCEEnc 再配布(二次配布)について】
このファイル(VCEEnc_readme.txt)と一緒に配布してください。念のため。
まあできればアーカイブまるごとで。

【VCEの現時点での仕様】
・H.264 baseline / main / high profile
・H.264 I/P フレーム (Bフレーム無し)
・プログレッシブのみ (インターレスサポート無し)
・CQP/CBR/VBRモード
・SAR比指定不可
・限定的なフレームレートサポート
     
【VCEEnc 使用方法 (簡易インストーラ使用)】
付属の簡易インストーラを使用する方法です。
手動で行う場合は、後述のVCEEnc 使用方法 (手動)を御覧ください。

1. ダウンロードしたVCEEnc_4.xx.zipを一度解凍します。

2. auo_setup.exeをダブルクリックし、実行します。
   基本的に自動で必要なもののダウンロード・インストールが行われます。

3. 途中でAviutl.exeのあるフォルダ場所を聞かれますので、
   右側のボタンをクリックしてAviutlのフォルダを指定してください。
   
4. インストールが完了しました、と出るのをお待ちください。


【VCEEnc 使用方法 (手動)】
1. 
以下のものをインストールしてください。

Visual Studio 2019 の Visual C++ 再頒布可能パッケージ (x86) 
https://aka.ms/vs/16/release/vc_redist.x86.exe

.NET Framework 4.5がインストールされていない場合、インストールしてください。
通常はWindows Updateでインストールされ、またCatalyst Control Centerでも使用されているため、
インストールの必要はありません。

.NET Framework 4.5.2 (x86)
https://www.microsoft.com/ja-jp/download/details.aspx?id=30653

2. 
auoフォルダ内の
・VCEEnc.auo
・VCEEnc.ini
・VCEEnc_stgフォルダ
をAviutlのpluginフォルダにコピーします。

3. 
Aviutlを起動し、「その他」>「出力プラグイン情報」にVCEEncがあるか確かめます。
ここでVCEEncの表示がない場合、1.で必要なものを忘れている、あるいは失敗したなどが考えられます。

4. 必要な実行ファイルを集めます。
   以下に実行ファイル名とダウンロード場所を列挙します。
   実行ファイルは32bit/64bitともに可です。
<主要エンコーダ・muxer>
 [qaac/refalac (AAC/ALACエンコーダ)]
 http://sites.google.com/site/qaacpage/
 
 [L-SMASH (mp4出力時に必要)]
 http://pop.4-bit.jp/
 
 [mkvmerge (mkv出力時に必要)]
 http://www.bunkus.org/videotools/mkvtoolnix/
 
<音声エンコーダ>
 [neroaacenc (AACエンコーダ)]
 http://www.nero.com/jpn/downloads-nerodigital-nero-aac-codec.php

 [FAW(fawcl) (FakeAACWave(偽装wav)解除)]
 http://2sen.dip.jp/cgi-bin/friioup/upload.cgi?search=FakeAacWav&sstart=0001&send=9999
 
 [faw2aac.auo (FakeAACWave(偽装wav)解除)]
 http://www.rutice.net/FAW2aac

 [qtaacenc   (AACエンコーダ, 要QuickTime)]
 http://tmkk.pv.land.to/qtaacenc/
 
 [ext_bs     (PVシリーズAAC抽出)]
 http://www.sakurachan.org/soft/mediatools/
 
 [lame       (mp3エンコーダ)]
 http://www.rarewares.org/mp3-lame-bundle.php
 
 [ffmpeg     (AC3エンコーダとして使用)]
 http://blog.k-tai-douga.com/
 
 [oggenc2    (ogg Vorbis, mkv専用)]
 http://www.rarewares.org/ogg-oggenc.php 
 
 [mp4alsrm23 (MPEG4 ALS (MPEG4 Audio Lossless Coding))]
 http://www.nue.tu-berlin.de/menue/forschung/projekte/beendete_projekte/mpeg-4_audio_lossless_coding_als/parameter/en/
 ※Reference Software のとこにある MPEG-4 ALS codec for Windows - mp4alsRM23.exe
 
5.
VCEEncの設定画面を開き、各実行ファイルの場所を指定します。
あと適当に設定します。

6.
エンコード開始。気長に待ちます。


【iniファイルによる拡張】
VCEEnc.iniを書き換えることにより、
音声エンコーダやmuxerのコマンドラインを変更できます。
また音声エンコーダを追加することもできます。

デフォルトの設定では不十分だと思った場合は、
iniファイルの音声やmuxerのコマンドラインを調整してみてください。


【ビルドについて】
ビルドにはVC++2015が必要です。

コーディングが汚いとか言わないで。

【コンパイル環境】
VC++ 2015 Community


【検証環境 2012.11～】
Win7 x64
Xeon W3680 + ASUS P6T Deluxe V2 (X58)
Radeon HD 7770
18GB RAM
CatalystControlCenter 12.8
CatalystControlCenter 12.10
CatalystControlCenter 12.11 beta

【検証環境 2013.10】
Win7 x64
Core i7 4770K + Asrock Z87 Extreme4
Radeon HD 7770
16GB RAM
CatalystControlCenter 13.4

【検証環境 2015.09】
Win7 x64
Core i7 4770K + Asrock Z97E-ITX/ac
Radeon R7 360
8GB RAM
CatalystControlCenter 15.7

【検証環境 2015.10】
なし

【検証環境 2016.06】
Win7 x64
Core i7 4770K + Asrock Z97E-ITX/ac
Radeon R7 360
8GB RAM
CatalystControlCenter 15.7


【検証環境 2016.06】
Win7 x64
Core i7 4770K + Asrock Z97E-ITX/ac
Radeon RX 460
8GB RAM
CatalystControlCenter 17.1

【検証環境 2018.11】
Win10 x64
Core i7 7700K + Asrock Z270 Gaming-ITX/ac
Radeon RX 460
16GB RAM

【検証環境 2019.11】
Win10 x64
Ryzen3 3200G + Asrock AB350 Pro4
16GB RAM

【お断り】
今後の更新で設定ファイルの互換性がなくなるかもしれません。

【どうでもいいメモ】
2019.11.19 (5.00)
[共通]
・AMF 1.4.8 -> 1.4.14に更新。
・VC++2019に移行。

[VCEEnc.auo]
・VCEEnc.auo - VCEEncC間のフレーム転送を効率化して高速化。
・簡易インストーラを更新。
・VCEEnc.auoの出力をmp4/mkv出力に変更し、特に自動フィールドシフト使用時のmux工程数を削減する。
  また、VCEEncCのmuxerを使用することで、コンテナを作成したライブラリとしQSVEncCを記載するようにする。

[VCEEncC]
・可能なら進捗表示に沿うフレーム数と予想ファイルサイズを表示。
・映像のcodec tagを指定するオプションを追加。(--video-tag)
・字幕ファイルを読み込むオプションを追加。 (--sub-source )
・--audio-sourceを改修し、より柔軟に音声ファイルを取り扱えるように。
・データストリームをコピーするオプションを追加する。(--data-copy)
・--sub-copyで字幕をデータとしてコピーするモードを追加。
  --sub-copy asdata
・--audio-codecにデコーダオプションを指定できるように。
  --audio-codec aac#dual_mono_mode=main
・avsからの音声処理に対応。
・高負荷時にデッドロックが発生しうる問題を修正。
・音声エンコードの安定性を向上。
・CPUの動作周波数が適切に取得できないことがあったのを修正。
・--chapterでmatroska形式に対応する。
・--audio-copyでTrueHDなどが正しくコピーされないのを修正。
・--trimを使用すると音声とずれてしまう場合があったのを修正。
・音声エンコード時のtimestampを取り扱いを改良、VFR時の音ズレを抑制。
・mux時にmaster-displayやmax-cllの情報が化けるのを回避。
・ffmpegと関連dllを追加/更新。
  - [追加] libxml2 2.9.9
  - [追加] libbluray 1.1.2
  - [追加] aribb24 rev85
  - [更新] libpng 1.6.34 -> 1.6.37
  - [更新] libvorbis 1.3.5 -> 1.3.6
  - [更新] opus 1.2.1 -> 1.3.1
  - [更新] soxr 0.1.2 -> 0.1.3
・そのほかさまざまなNVEnc/QSVEnc側の更新を反映。

2018.12.11 (4.02)  
[VCEEnc.auo]
・自動フィールドシフト使用時、widthが32で割り切れない場合に範囲外アクセスの例外で落ちる可能性があったのを修正。
・Aviutlからのフレーム取得時間(平均)の表示をログに追加。

2018.11.24 (4.01)
[VCEEncC]
・読み込みにudp等のプロトコルを使用する場合に、正常に処理できなくなっていたのを修正。
・--audio-fileが正常に動作しないことがあったのを修正。

2018.11.18 (4.00)
[共通]
・AMF 1.4.9に対応。
・colormatrix/colorprim/transfer/videoformatの指定に対応。
・HEVCのSAR比指定に対応。

[VCEEnc.auo]
・エンコーダをプラグインに内蔵せず、VCEEncCにパイプ渡しするように。
  Aviutl本体プロセスのメモリ使用量を削減する。
・設定ファイルのフォーマットを変更したため、以前までの設定ファイルは読めなくなってしまったのでご注意ください。
・簡易インストーラを更新。
  - Apple dllがダウンロードできなくなっていたので対応。
  - システムのプロキシ設定を自動的に使用するように。

2017.02.27 (3.06)
[VCEEncC]
・HEVCデコードができなくなっていたのを修正。
・ログ出力を強化。

2017.02.16 (3.05v2)
[VCEEncC]
※同梱のdll類も更新してください!
・dllの更新忘れにより、HEVCデコードができなくなっていたのを修正。

2017.02.14 (3.05)
[VCEEncC]
・enforce-hdrをデフォルトでオフに。
・enforce-hdrの有効無効の切り替えオプションを追加。(--enforce-hdr)
・fillerデータの有効無効の切り替えオプションを追加。(--filler)

2017.02.03 (3.04)
[共通]
・3.00から高くなっていたCPU使用率を低減。
・リモートデスクトップ中のエンコードをサポート。
  DX9での初期化に失敗した場合、DX11での初期化を行う。
・特に指定がない場合、levelや最大ビットレートを解像度やフレームレート、refなどから自動的に決定するように。
  Levelの不足により、HEVC 4Kエンコードができないのを修正する。

[VCEEnc.auo]
・cbr/vbrモードで、最大ビットレートの指定ができない問題を修正。
  
[VCEEncC]
・高ビット深度の入力ファイルに対しては、自動的にswデコードに回すように。
・使用するデバイスIDを指定できるように。
・VCEが使用可能か、HEVCエンコードが可能かを、デバイスIDを指定して行えるように。
・エラーでないメッセージが赤で表示されていたのを修正。
・特に指定がなく、解像度の変更もなければ、読み込んだSAR比をそのまま反映するように。

2017.02.01 (3.03)
[共通]
・HEVCでのvbr/cbrモードでのVBAQを許可。

[VCEEnc.auo]
・HEVCエンコ時にLevelが正しく保存されないのを修正。
・VCEEnc.auoからもHWリサイザを利用できるように。
・VCEEncCのHEVCエンコで--vbrが正常に動作しない問題を修正。

[VCEEncC]
・avswリーダーでYUV420 10bitの読み込みに対応(エンコードは8bit)。
・avswリーダー使用時に解像度によっては、色がずれてしまうのを修正。

2017.01.30 (3.02)
※同梱のdll類も更新してください!

[共通]
・H.264のfullrangeを指定するオプションを追加。(--fullrange)
・HEVCエンコで--qualityを指定すると常にログでnormalと表示される問題を修正。
・4Kがエンコードできない問題を修正。
・ログに出るdeblockの有効無効が反転していたのを修正。

[VCEEncC]
・HW HEVCデコードの安定性を向上。
・avcodecによるswデコードをサポート。(--avsw)
・HEVCのtierを指定するオプションを追加。(--tier <string>)
・--pre-analysisのquarterが正常に動作しなかったのを修正。
・HEVCの機能情報が取得できない問題を回避。
・音声処理のエラー耐性の向上。

2017.01.25 (3.01)
[共通]
・実行時にVCEの機能を確認し、パラメータのチェックを行うように。
・参照距離を指定するオプションを追加。(--ref <int>)
・LTRフレーム数を指定するオプションを追加。(--ltr <int>)
・H.264 Level 5.2を追加。
・バージョン情報にAMFのバージョンを追記。

[VCEEncC]
・ヘルプの誤字脱字等を修正。
・VCEの機能をチェックするオプションを追加。(--check-features)
  HEVCの機能については正常に表示できない。
・HEVC(8bit)のHWデコードを追加。
・wmv3のHWデコードが正常に動作しないため、削除。

2017.01.22 (3.00)
[共通]
・AMD Media Framework 1.4に対応。
  AMD Radeon Software Crimson 17.1.1 以降が必要。
・PolarisのHEVCエンコードを追加。(-c hevc)
・SAR比の指定を追加。
・先行探索を行うオプションを追加。(--pre-analysis)
・VBAQオプションを追加。(H.264のみ)
・その他、複数の不具合を修正。

2016.06.21 (2.00)
[VCEEncC]
・H.264/MPEG2のハードウェアデコードに対応。(avvceリーダー)
・muxしながら出力する機能を追加。
・音声の抽出/エンコードに対応。
・ハードウェアリサイズに対応。
・そのほかいろいろ。

注意点
・QSVEncC/NVEncCにあるような以下の機能には対応していません。
  - avsync
  - trim
  - crop
  - インタレ保持エンコード
  - インタレ解除
  - colormatrix, colorprim, transfer, videoformatなど

2016.01.14 (1.03v2)
・簡易インストーラでQuickTimeがダウンロードできなくなっていたのを修正。

2015.10.24 (1.03)
[VCEEnc]
・エンコ後バッチ処理の設定画面を修正。

2015.09.26 (1.02)
[VCEEnc]
・FAWCheckが働かないのを修正。

2015.09.24 (1.01)
[VCEEnc]
・音声エンコード時に0xc0000005例外で落ちることがあるのを修正。

[VCEEncC]
・更新なし。

2015.09.23 (1.00)
[共通]
・AMD APP SDK 3.0 + 新APIに移行し、Catalyst15.7以降で動作しなくなっていたのを修正。
・VC++ 2015に移行。
・.NET Framework 4.5.2に移行。

[VCEEnc]
・設定ファイルの互換性がなくなってしまいました。
・様々な機能をx264guiEx 2.34相当にアップデート。

[VCEEncC]
・新たに追加。(x86/x64)
・読み込みはraw/y4m/avs/vpy。

2013.12.07 (0.02v2)
・簡易インストーラを更新
  - 簡易インストーラをインストール先のAviutlフォルダに展開すると
    一部ファイルのコピーに失敗する問題を修正
  - L-SMASHがダウンロードできなくなっていたのを修正。
  - インストール先が管理者権限を必要とする際は、
    これを取得するダイアログを表示するようにした。
    
2013.10.20 (0.02)
・自動フィールドシフト対応。
・簡易インストーラを更新
  - Windows 8.1に対応した(と思う)
  - アップデートの際にプリセットを上書き更新するかを選択できるようにした。
・x264guiEx 2.03までの更新を反映。
  - ログウィンドウの位置を保存するようにした。
  - 高Dpi設定時の表示の崩れを修正。
  - エンコード開始時にクラッシュする可能性があるのを修正。
  - エンコ前後バッチ処理を最小化で実行する設定を追加。
  - 出力ファイルの種類のデフォルトを変更できるようにした。
  - FAW half size mix モード対応。
  - mux時にディスクの空き容量の取得に失敗した場合でも、警告を出して続行するようにした。
  - 設定画面で「デフォルト」をクリックした時の挙動を修正。
    「デフォルト」をクリックしたあと、「キャンセル」してもキャンセルされていなかった。
  - ログウィンドウで出力ファイル名を右クリックから
    「動画を再生」「動画のあるフォルダを開く」機能を追加。
  - 変更したフォントの(標準⇔斜体)が保存されない問題を修正。

2012.11.17 (0.01)
・シークができなくなる問題に対処。
・ログウィンドウの色の指定を可能に。ログウィンドウ右クリックから。

2012.11.06 (0.00)
  公開

2012.11.04
  エンコードスレッドを別に立てて高速化
  
2012.11.03
  パイプライン型エンコードにより高速化
  
2012.11.01
  なんか遅い
  
2012.10.28
  結構遅い。CPUが遊ぶ
  
2012.10.27
  とりあえず動く