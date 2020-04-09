@echo off
pushd "%~dp0\bin\Apache\bin\"
"%~dp0\bin\PHP\php.exe" -c "%~dp0\bin\PHP\php.default.ini" "%~dp0\install.php"
popd