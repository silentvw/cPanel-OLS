#!/bin/bash

OSLSWSVER=1.7.16

PWDHOME=$(pwd)

systemctl stop lshttpd
rm -rf /usr/local/lsws
systemctl stop lswsparse
systemctl disable lswsparse
clear

echo " "
echo " "
echo " WELCOME TO THE ADACLARE LITESPEED FOR CPANEL"
echo " "
echo " "
sleep 3s
echo "DOWNLOADING & EXTRACTING OPEN LITESPEED $OSLSWSVER TAR FILE "
curl https://openlitespeed.org/packages/openlitespeed-$OSLSWSVER.tgz -o openlitespeed.tar.xz
tar xvf openlitespeed.tar.xz
rm -rf openlitespeed.tar.xz
cd openlitespeed
echo " INSTALLING OPEN LITESPEED "
./install.sh
cd $PWDHOME
rm -rf openlitespeed
echo " INSTALLING COMPATIBLE LSWS PHP VERSIONS "
echo "LSPHP 7.4"
yum -y -q remove lsphp74*
yum -y -q install lsphp74*
echo "LSPHP 7.3"
yum -y -q remove lsphp73*
yum -y -q install lsphp73*
echo "LSPHP 7.2"
yum -y -q remove lsphp72*
yum -y -q install lsphp72*
echo " STARTING LSWS WEB SERVER "
systemctl disable httpd
systemctl stop httpd
systemctl start lshttpd
systemctl enable lshttpd
systemctl status lshttpd
echo " SETTING UP CPANEL LITESPEED CONVERSION SCRIPT "
rm -rf /var/run/chkservd/apache*
cp -rf conversion /usr/local/lsws/configparse
cp -rf lswsparse.service /etc/systemd/system/lswsparse.service
rm -rf /usr/local/lsws/conf/httpd_config.conf
cp -rf httpd_config.conf /usr/local/lsws/conf/
systemctl start lswsparse
systemctl enable lswsparse
systemctl status lswsparse
bash updateService.sh
echo " Installing Modified Litespeed cPanel Extension "
cd lsws_whm_plugin && bash lsws_whm_plugin_install.sh

echo " "
echo " "
echo " APACHE CONVERTED TO LSWS DONE! "
echo " "
echo " "
