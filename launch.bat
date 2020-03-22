@echo off
pushd "%~dp0\bin\Apache\bin\"
start /min "TVRemotePlus - launch" httpd.exe
popd