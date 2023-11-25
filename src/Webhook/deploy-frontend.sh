#!/bin/bash
set -e

source ~/.bashrc
source ~/.profile

cd ~/RestockFrontend
git checkout main
git pull

npm i
npm run build
rm -fr /var/www/pantrysync.pro/public
mv dist/ /var/www/pantrysync.pro/public

