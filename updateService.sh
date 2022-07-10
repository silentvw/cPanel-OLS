#!/bin/bash
echo "Stopping LSWS Parse & LSHTTPD"
systemctl stop lswsparse
systemctl disable lshttpd
systemctl stop lshttpd
echo "Updating Files"
rm -rf /usr/local/lsws/conf/httpd_config.conf
cp httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
rm -rf /usr/local/lsws/configparse
cp -rf conversion /usr/local/lsws/configparse
cp -rf lswsparse.service /etc/systemd/system/lswsparse.service
rm -rf /usr/local/lsws/conf/httpd_config.conf
cp -rf httpd_config.conf /usr/local/lsws/conf/
rm -rf /usr/local/lsws/.changesDetect
systemctl enable lshttpd
systemctl daemon-reload
systemctl start lswsparse
echo "Waiting for LSWSparse to generate our config"
sleep 3s
echo " "
echo " "
echo "It may take some time for lshttpd to come online"
echo " "
echo " "
