@echo off
if not "%PROCESSOR_ARCHITECTURE%" == "AMD64" (
    echo "GPU使用率の表示は64bit版OSでのみ可能です。"
    pause
    exit /B
)
reg add "HKLM\SOFTWARE\Intel\EventTrace" /v EtwRenderSubmitCommandEnable /t REG_DWORD /d 1 /f
if not %ERRORLEVEL% == 0 (
    echo batファイルを右クリックして、「管理者として実行」してください。
    pause
    exit /B
)
echo GPU使用率の表示を有効にしました。[QSVEncC64.exeのみ]
pause
exit  /B
