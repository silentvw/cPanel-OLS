#!/bin/bash

OSLSWSVER=$(curl -s https://openlitespeed.org/packages/release)

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
clear
echo " INSTALLING OPEN LITESPEED "
./install.sh
cd $PWDHOME
rm -rf openlitespeed
clear
echo " STARTING LSWS WEB SERVER "
systemctl disable httpd
systemctl stop httpd
systemctl start lshttpd
systemctl enable lshttpd
systemctl status lshttpd
clear
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
clear
echo " Installing Modified Litespeed cPanel Extension "
cd lsws_whm_plugin && bash lsws_whm_plugin_install.sh
clear
echo " "
echo " "
echo " Thank you for using the Adaclare Openlitespeed cPanel Plugin "
echo " NOTE: It may take some time for LSWSParse to generate a config and for litespeed to come online"
echo " Please monitor using the following commands: "
echo " "
echo " systemctl status lshttpd "
echo " systemctl status lswsparse "
echo " "
echo " If you run into issues, please create a issue here and we will have a look at it. "
echo " "
echo " "
