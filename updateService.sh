systemctl stop lswsparse
rm -rf /usr/local/lsws/conf/httpd_config.conf
cp httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
rm -rf /usr/local/lsws/configparse
cp -rf conversion /usr/local/lsws/configparse
cp -rf lswsparse.service /etc/systemd/system/lswsparse.service
rm -rf /usr/local/lsws/conf/httpd_config.conf
cp -rf httpd_config.conf /usr/local/lsws/conf/
rm -rf /usr/local/lsws/.changesDetect
systemctl daemon-reload
systemctl start lswsparse
