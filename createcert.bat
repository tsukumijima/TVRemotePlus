@echo off
echo.
echo  ---------------------------------------------------
echo            HTTPS用サーバー証明書の作成バッチ
echo  ---------------------------------------------------
echo.
echo   HTTPS 接続用の自己署名証明書を作成します。
echo   途中、入力を求められる箇所がありますが、全て Enter で飛ばしてください。
echo   途中でパスワードとかも聞かれますが、飛ばして構いません。
echo.
echo   続行するには何かキーを押してください。
pause > NUL
echo.
echo  ---------------------------------------------------
cd %~dp0\bin\Apache\bin\
if not exist ..\conf\openssl.cnf (
  copy ..\conf\openssl.default.cnf ..\conf\openssl.cnf
)
if not exist ..\conf\openssl.ext (
  copy ..\conf\openssl.default.ext ..\conf\openssl.ext
)
echo.
.\openssl.exe genrsa -out ..\conf\server.key 2048
.\openssl.exe req -new -key ..\conf\server.key -out ..\conf\server.csr -config ..\conf\openssl.cnf
.\openssl.exe x509 -req -in ..\conf\server.csr -out ..\conf\server.crt -days 3650 -signkey ..\conf\server.key -extfile ..\conf\openssl.ext
  copy ..\conf\server.crt ..\..\..\htdocs\server.crt
echo.
echo  ---------------------------------------------------
echo.
if %errorlevel% equ 0 (
  echo   HTTPS接続用の自己署名証明書を作成しました。
) else (
  echo   HTTPS接続用の自己署名証明書の作成に失敗しました…
)
echo   終了するには何かキーを押してください。
echo.
echo  ---------------------------------------------------
pause > NUL