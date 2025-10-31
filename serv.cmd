@echo off
::docker compose up php -d
set port=%1
if "%port%"=="" set port=8080
php -S 0.0.0.0:%port% index.php > serv.log 2>&1
::php -S 0.0.0.0:%port% sys.phar > serv.log 2>&1
::php -S 0.0.0.0:%port% index.php

