#!/bin/bash
echo "Start Deploy..."

cd /tmp
curl -L https://github.com/CJY1204/Cloud-Computing-For-Business-Assignment/archive/refs/heads/main.zip -o code.zip
unzip -o code.zip

sudo rm -rf /var/www/html/*
sudo cp -r Cloud-Computing-For-Business-Assignment-main/pool-booking-php/* /var/www/html/
sudo chown -R apache:apache /var/www/html/
sudo chmod -R 755 /var/www/html/

rm -rf code.zip Cloud-Computing-For-Business-Assignment-main

echo "Deploy Done！"
