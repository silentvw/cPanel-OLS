#!/bin/sh

cd `dirname "$0"`


if ! . ./functions.sh 2>/dev/null; then

    if ! . ./functions.sh; then
        echo '[ERROR] Can not include functions.sh'.
        exit 1
    fi
fi


test_license()
{
    if [ -f "${LSWS_HOME}/conf/license.key" ] && [ ! -f "${LSINSTALL_DIR}/license.key" ]; then
        cp "${LSWS_HOME}/conf/license.key" "${LSINSTALL_DIR}/license.key"
    fi

    if [ -f "${LSWS_HOME}/conf/serial.no" ] && [ ! -f "${LSINSTALL_DIR}/serial.no" ]; then
        cp "${LSWS_HOME}/conf/serial.no" "${LSINSTALL_DIR}/serial.no"
    fi

    if [ -f "${LSINSTALL_DIR}/license.key" ] && [ -f "${LSINSTALL_DIR}/serial.no" ]; then
        echo "License key and serial number are available, testing..."
        echo

        if bin/lshttpd -t 2>&1; then
            LICENSE_OK=1
        fi

        echo
    fi
    
    if [ "x${LICENSE_OK}" = "x" ]; then

        if [ -f "${LSINSTALL_DIR}/serial.no" ]; then
            echo "Serial number is available."
            echo "Contacting licensing server ..."
            echo ""

            if "${LSINSTALL_DIR}/bin/lshttpd" -r 2>&1; then
                echo "[OK] License key received."

                if "${LSINSTALL_DIR}/bin/lshttpd" -t 2>&1; then
                    LICENSE_OK=1
                else
                    echo "The license key received does not work."
                fi
            fi
        fi
    fi

    if [ "x${LICENSE_OK}" = "x" ]; then

        if [ -f "${LSINSTALL_DIR}/trial.key" ]; then

            if ! "${LSINSTALL_DIR}/bin/lshttpd" -t 2>&1; then
                exit 1
            fi
        else
            cat <<EOF
[ERROR] Sorry, installation will abort without a valid license key.
 
For evaluation purpose, please obtain a trial license key from our web 
site https://www.litespeedtech.com, copy it to this directory 
and run Installer again.

If a production license has been purchased, please copy the serial number
from your confirmation email to this directory and run Installer again.

NOTE:
Please remember to set ftp to BINARY mode when you ftp trial.key from 
another machine.

EOF
            exit 1
        fi
    fi
    
}

installLicense()
{
    if [ -f ./serial.no ]; then
        cp -f ./serial.no "${LSWS_HOME}/conf"
        chown "${DIR_OWN}" "${LSWS_HOME}/conf/serial.no"
        chmod "${CONF_MOD}" "${LSWS_HOME}/conf/serial.no"
    fi
    
    if [ -f ./license.key ]; then
        cp -f ./license.key "${LSWS_HOME}/conf"
        chown "${DIR_OWN}" "${LSWS_HOME}/conf/license.key"
        chmod "${CONF_MOD}" "${LSWS_HOME}/conf/license.key"
    fi
    
    if [ -f ./trial.key ]; then
        cp -f ./trial.key "${LSWS_HOME}/conf"
        chown "${DIR_OWN}" "${LSWS_HOME}/conf/trial.key"
        chmod "${CONF_MOD}" "${LSWS_HOME}/conf/trial.key"
    fi
}


LSINSTALL_DIR=$(dirname "$0")
cd "${LSINSTALL_DIR}"

init

# shellcheck disable=SC2034
INSTALL_TYPE="reinstall"
LSWS_HOME=$1
# shellcheck disable=SC2034
AP_PORT_OFFSET=$2
# shellcheck disable=SC2034
PHP_SUEXEC=$3 # 1 or 0
# shellcheck disable=SC2034
PHP_SUFFIX=php
ADMIN_USER=$4
PASS_ONE=$5
# shellcheck disable=SC2034
ADMIN_EMAIL=$6

# shellcheck disable=SC2034
SETUP_PHP=1
# shellcheck disable=SC2034
ADMIN_PORT=7080
# shellcheck disable=SC2034
DEFAULT_PORT=8088

# shellcheck disable=SC2034
HOST_PANEL="cpanel"
# shellcheck disable=SC2034
WS_USER=nobody
# shellcheck disable=SC2034
WS_GROUP=nobody
# shellcheck disable=SC2034
PANEL_VARY=".ea4"

if [ "x${ADMIN_USER}" != 'x' ] && [ "x${PASS_ONE}" != 'x' ]; then

    if [ -f "${LSINSTALL_DIR}/admin/fcgi-bin/admin_php5" ]; then
        ENCRYPT_PASS=$("${LSINSTALL_DIR}/admin/fcgi-bin/admin_php5" -q "${LSINSTALL_DIR}/admin/misc/htpasswd.php" "${PASS_ONE}")
    else
        ENCRYPT_PASS=$("${LSINSTALL_DIR}/admin/fcgi-bin/admin_php" -q "${LSINSTALL_DIR}/admin/misc/htpasswd.php" "${PASS_ONE}")
    fi

    echo "${ADMIN_USER}:${ENCRYPT_PASS}" > "${LSINSTALL_DIR}/admin/conf/htpasswd"
fi

configRuby

if [ ! -e "${LSWS_HOME}" ]; then
    mkdir  "${LSWS_HOME}"
    chmod 0755 "${LSWS_HOME}"
fi

test_license


cat <<EOF

Installing LiteSpeed web server, please wait... 

EOF


buildApConfigFiles

installation

installLicense

if [ -e '/opt/cpanel/ea-php56/root/usr/bin/lsphp' ] ; then

    # shellcheck disable=SC2022
    if rpm -qf '/usr/local/bin/lsphp' | grep -q 'ea-php-cli*'; then
        /bin/cp -pf '/opt/cpanel/ea-php56/root/usr/bin/lsphp' '/usr/local/bin/lsphp'
    fi

    ln -sf '/opt/cpanel/ea-php56/root/usr/bin/lsphp' '/usr/local/lsws/fcgi-bin/lsphp5'
fi

if [ -e '/opt/cpanel/ea-php70/root/usr/bin/lsphp' ] ; then
    echo 'link lsphp7 to lsws directory'
    ln -sf '/opt/cpanel/ea-php70/root/usr/bin/lsphp' '/usr/local/lsws/fcgi-bin/lsphp7'
fi

echo ""
"${LSWS_HOME}/admin/misc/rc-inst.sh"
