@echo off
echo Démarrage du serveur Symfony avec php.ini local...

REM Utiliser le php.ini local en définissant la variable d'environnement PHP_INI_SCAN_DIR
set PHP_INI_SCAN_DIR=
php -c . bin/console cache:clear

REM Démarrer le serveur Symfony
symfony server:start --no-tls 