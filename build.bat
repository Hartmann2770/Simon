@echo off
cd /d "%~dp0"
echo Bygger deploy-mapper...

:: ── simonhartmann.dk ──────────────────────────────────
copy /Y simonhartmann\index.html        deploy\simonhartmann.dk\index.html
copy /Y simonhartmann\about.html        deploy\simonhartmann.dk\about.html
copy /Y simonhartmann\simon.png         deploy\simonhartmann.dk\simon.png
copy /Y simonhartmann\favicon.svg       deploy\simonhartmann.dk\favicon.svg

copy /Y memorypalace\memory-palace.html deploy\simonhartmann.dk\memory-palace.html
copy /Y memorypalace\api.php            deploy\simonhartmann.dk\api.php
copy /Y memorypalace\favicon-palace.svg deploy\simonhartmann.dk\favicon-palace.svg

:: ── madklubben.com ────────────────────────────────────
copy /Y madklubben\index.html           deploy\madklubben.com\index.html
copy /Y madklubben\favicon.svg          deploy\madklubben.com\favicon.svg
copy /Y madklubben\api.php              deploy\madklubben.com\api.php
copy /Y madklubben\config.php           deploy\madklubben.com\config.php
copy /Y madklubben\kalender.php         deploy\madklubben.com\kalender.php

echo.
echo Faerdig! Upload indholdet af disse mapper via FileZilla:
echo   deploy\simonhartmann.dk\  --^>  simonhartmann.dk  /  public_html/
echo   deploy\madklubben.com\    --^>  madklubben.com    /  public_html/
echo.
pause
