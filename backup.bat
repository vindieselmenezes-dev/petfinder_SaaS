@echo off
:: Configura a data e hora no formato brasileiro para o nome do arquivo
for /f "tokens=1-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%b-%%a)
for /f "tokens=1-2 delims=: " %%a in ('time /t') do (set mytime=%%a-%%b)

:: CONFIGURAÇÕES DO SEU XAMPP (Ajuste se o seu usuário/senha do MySQL forem diferentes)
set MYSQL_USER=root
set MYSQL_PASSWORD=
set DATABASE_NAME=petfinder
set BACKUP_DIR=C:\Backup_PetFinder

:: Nome final do arquivo de backup (Ex: backup_petfinder_2026-08-10_10-30.sql)
set BACKUP_FILE=%BACKUP_DIR%\backup_%DATABASE_NAME%_%mydate%_%mytime%.sql

:: Comando oficial do XAMPP que faz a mágica acontecer
"C:\xampp\mysql\bin\mysqldump.exe" --user=%MYSQL_USER% --password=%MYSQL_PASSWORD% %DATABASE_NAME% > "%BACKUP_FILE%"

echo Backup do Banco de Dados concluido com sucesso em: %BACKUP_FILE%
