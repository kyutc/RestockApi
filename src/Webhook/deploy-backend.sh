#!/bin/bash
set -e
shopt -s extglob
shopt -u dotglob
GLOBIGNORE=".:.."

source ~/.bashrc
source ~/.profile

cd ~/RestockApi
git checkout dev-deployment
git pull

rm -fr /var/www/api.pantrysync.pro/v1/!(vendor)
cp -rT ~/RestockApi /var/www/api.pantrysync.pro/v1/

cd /var/www/api.pantrysync.pro/v1

cp ~/restock/config.php src/config.php
chmod 400 src/config.php

chmod +x src/Webhook/*.sh

composer install

php bin/doctrine.php orm:schema-tool:drop --force --full-database
php bin/doctrine.php orm:schema-tool:create