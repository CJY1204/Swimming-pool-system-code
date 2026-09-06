#!/bin/bash
echo "Start Deploy..."

cd /tmp
curl -L https://github.com/CJY1204/Swimming-pool-system-code/archive/refs/heads/main.zip -o code.zip
unzip -o code.zip

sudo rm -rf /var/www/html/*
sudo cp -r Swimming-pool-system-code-main/* /var/www/html/
sudo rm -f /var/www/html/deploy.sh
sudo chown -R apache:apache /var/www/html/
sudo chmod -R 755 /var/www/html/

rm -rf code.zip Swimming-pool-system-code-main

echo "Deploy Complete！"

echo "Testing..."
