#!/bin/bash
echo "Start deploy..."

cd /tmp
curl -L https://github.com/CJY1204/Swimming-pool-system-code/archive/refs/heads/main.zip -o code.zip
unzip -o code.zip

sudo rm -rf /var/www/html/*
sudo cp -r Cloud-Computing-For-Business-Assignment-main/* /var/www/html/
sudo chown -R apache:apache /var/www/html/
sudo chmod -R 755 /var/www/html/

rm -rf code.zip Cloud-Computing-For-Business-Assignment-main

echo "Deploy complete！"
