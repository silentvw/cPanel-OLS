#!/bin/bash
while true; do
systemctl stop httpd
systemctl disable httpd
cd /usr/local/lsws/configparse/
php service.php
done
