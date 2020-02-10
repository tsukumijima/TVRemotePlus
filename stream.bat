@echo off
pushd %~dp0\bin\Apache\bin\
%~dp0\bin\PHP\php.exe -c %~dp0\bin\PHP\php.ini %~dp0\stream.php %*