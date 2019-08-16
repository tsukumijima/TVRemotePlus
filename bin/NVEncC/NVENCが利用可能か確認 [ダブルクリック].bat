@echo off
%~dp0\NVEncC64.exe --check-hw
if errorlevel 0 (
    echo NVENCは利用可能です。
) else (
    echo NVENCができません。
)
pause