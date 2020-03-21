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
pushd "%~dp0\bin\Apache\bin\"
.\openssl.exe req -new -newkey rsa:2048 -nodes -config ..\conf\openssl.cnf -keyout ..\conf\server.key -out ..\conf\server.crt ^
              -x509 -days 3650 -sha256 -subj "/C=JP/ST=Tokyo/O=TVRemotePlus/CN=%ip%" -addext "subjectAltName = IP:127.0.0.1,IP:%ip%"
echo.
echo   -------------------------------------------------------------------
echo.
if %errorlevel% equ 0 (
  copy ..\conf\server.crt ..\..\..\htdocs\files\TVRemotePlus.crt > NUL
  echo     自己署名証明書を正常に作成しました。
  echo.
  echo     自己署名証明書は ^(TVRemotePlus^)/bin/Apache/conf/ フォルダに作成されています。
  echo     また、ダウンロード用の証明書は ^(TVRemotePlus^)/htdocs/files/ フォルダ内にコピーしてあります。
) else (
  echo     自己署名証明書の作成に失敗しました…
  echo.
  echo     入力したローカル IP アドレスが正しいかどうか確認し、もう一度試してください。
)
echo.
echo     終了するには何かキーを押してください。
echo.
echo   -------------------------------------------------------------------
echo.
popd
pause > NUL