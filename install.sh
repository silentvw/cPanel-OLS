#!/bin/bash
PWDHOME=$(pwd)
systemctl stop lshttpd
rm -rf /usr/local/lsws
echo " "
echo " "
echo " WELCOME TO THE ADACLARE LITESPEED FOR CPANEL"
echo " "
echo " "
sleep 3s
echo " EXTRACTING OPEN LITESPEED TAR FILE "
tar xvf openlitespeed.tar.xz
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
cp lswsparse.service /etc/systemd/system/lswsparse.service
systemctl start lswsparse
systemctl enable lswsparse
systemctl status lswsparse
echo " "
echo " "
echo " APACHE CONVERTED TO LSWS DONE! "
echo " "
echo " "
