@echo off
cd %~dp0\bin\Apache\bin\
%~dp0\bin\PHP\php.exe -c ..\..\PHP\php.ini %~dp0\stream.php %*