@echo off
echo.
echo   -------------------------------------------------------------------
echo                    HTTPS 接続用自己署名証明書の作成ツール
echo   -------------------------------------------------------------------
echo.
echo     HTTPS 接続用の自己署名証明書を作成します。
echo.
echo     インストール時に何らかの問題で自己署名証明書の作成に失敗した場合や、
echo     ローカル IP アドレスが変わったとき、証明書の有効期限が切れたときなどに使ってください。
echo.
echo   -------------------------------------------------------------------
echo.
:input
echo     TVRemotePlus をインストールした PC の、ローカル IP アドレスを入力してください。
echo.
set /P ip=":   ローカルIPアドレス："
if "%ip%" equ "" (
  echo.
  echo     入力欄が空です。もう一度入力してください。
  echo.
  goto input
)
echo.
echo     自己署名証明書を作成します。
echo.
echo   -------------------------------------------------------------------
echo.
cd %~dp0\bin\Apache\bin\
if not exist ..\conf\openssl.ext (
  copy ..\conf\openssl.default.ext ..\conf\openssl.ext
)
.\openssl.exe genrsa -out ..\conf\server.key 2048
.\openssl.exe req -new -key ..\conf\server.key -out ..\conf\server.csr -config ..\conf\openssl.cnf -subj "/C=JP/ST=Tokyo/O=TVRemotePlus/CN=%ip%"
.\openssl.exe x509 -req -in ..\conf\server.csr -out ..\conf\server.crt -days 3650 -signkey ..\conf\server.key -extfile ..\conf\openssl.ext
copy ..\conf\server.crt ..\..\..\htdocs\server.crt
echo.
echo   -------------------------------------------------------------------
echo.
if %errorlevel% equ 0 (
  echo     自己署名証明書を正常に作成しました。
) else (
  echo     自己署名証明書の作成に失敗しました…
)
echo.
echo     自己署名証明書は (TVRemotePlus)/bin/Apache/conf/ フォルダに作成されています。
echo     また、ダウンロード用の証明書は (TVRemotePlus)/htdocs/ フォルダ内にコピーしてあります。
echo.
echo     終了するには何かキーを押してください。
echo.
echo   -------------------------------------------------------------------
echo.
pause > NUL