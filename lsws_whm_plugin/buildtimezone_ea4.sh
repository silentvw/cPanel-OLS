#!/bin/sh
#set -x
#set -o errexit

# /********************************************************************
# LiteSpeed EA4 TimeZoneDB Auto Installer
# @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @Copyright: (c) 2016-2022
# @Version: 1.2.7
# *********************************************************************/

show_help()
{
    cat <<EOF

    # ***********************************************************************
    #
    # This script will auto build the source version of timezonedb.so for
    # all instances of EA4 PHP.
    #
    # Prerequisites:
    #    1. cPanel's EA PHP environment only.
    #
    # Command:
    #    ./ea4.sh
    #
    # Example:
    #    ./ea4.sh y true
    #
    # Input parameters:
    #
    # 1. (Optional) YN
    #    Auto build it without input.
    #    Only accepts "y" or "X.X" or "X.X.X", X being integers.
    #
    # 2. (Optional) check
    #    Checks all versions and exits.
    #    Valid input is "true".
    #
    # ***********************************************************************

EOF
    exit 1
}

if [ "${#}" -gt 0 ] ; then

    if [ "${1}" = "y" ] ; then
        yn="y"
    else
        INPUT_PHP_VER="${1}"
        FILTERED_PHP_VER="$(echo "${INPUT_PHP_VER}" | \
          sed -e 's/^[^[:digit:]]*\.//g' -e 's/\.\+$//g' -e 's/\.+$//g' -e 's/\.\.\+/\./g' -e 's/[^[:digit:]\.]//g')"

        if [ "${INPUT_PHP_VER}" = "${FILTERED_PHP_VER}" ] ; then
            PHP_VER="${INPUT_PHP_VER}"
            yn="php_ver"
        else
            echo "Invalid parameter."
            show_help
        fi
    fi
else
    yn="false"
fi

if [ "${#}" -gt 1 ]; then

    if [ "${2}" = "true" ]; then
        check="true"
    fi
fi

cagefs()
{
    echo "Checking to see if CageFS is enabled."

    if [ -e "/usr/sbin/cagefsctl" ]; then

        if /usr/sbin/cagefsctl --cagefs-status | grep -q "Enabled"; then
            echo "CageFS found and enabled."
            echo "Updating CageFS to support the new module."

            if /usr/sbin/cagefsctl --force-update >/dev/null 2>&1; then
                echo "CageFS updated successfully."
            else
                echo "**ERROR** Error running 'cagefsctl --force-update'. Please try running it manually."
            fi
        else
            echo "CageFS not enabled."
            echo "Skipping."
        fi
    else
        echo "CageFS not installed."
        echo "Skipping."
    fi
}

# Error Handling
checkErrs()
{
    if [ "${1}" -ne 0 ] ; then

        if [ "${BACKUPMODULE}" = "true" ]; then
            cp "${MODULEPATH}/timezonedb.so.${NOW}" "${MODULEPATH}/timezonedb.so"
        fi

        if [ "${BACKUPINI}" = "true" ]; then
            cp "${INIPATH}/php.ini.${NOW}" "${INIPATH}/php.ini"
        fi

        echo "**ERROR** EA4 PHP ${PHPVERSION} ${2}"
        exit "${1}"
    fi
}

check_devel()
{
  if ! rpm -qa | grep "ea-php${MMVERSION}-php-devel" >/dev/null 2>&1; then
    checkErrs 1 "ea-php${MMVERSION}-php-devel was not found. This is required to build TimeZomeDB. Please run 'yum install -y ea-php${MMVERSION}-php-devel' to fix this issue."
  fi
}

if [ "${yn}" = "false" ]; then
    printf "Do you wish to install timezonedb for all Easy Apache 4 PHP Versions? (y, n, x.x, or x.x.x) "; read -r yn
    printf "Do you wish to check timezonedb for all Easy Apache 4 PHP Versions? (true or false) "; read -r check
fi

echo ""
echo ""

if [ "${yn}" = "y" ]; then
    # EAV is a newline delimited list.
    EAV="5.4
    5.5
    5.6
    7.0
    7.1
    7.2
    7.3
    7.4
    8.0"

    echo "${EAV}" | while read -r EAVERSION; do
        MMVERSION="$(echo "${EAVERSION}" | sed 's/\.//g')"
        echo "Checking to see if Easy Apache 4 PHP ${EAVERSION} is installed....."

        if [ -e "/opt/cpanel/ea-php${MMVERSION}/root/usr/bin/phpize" ]; then

            if [ "${check}" = "true" ]; then
                ./buildtimezone.sh "${EAVERSION}" y
            else
                check_devel
                ./buildtimezone.sh "${EAVERSION}" ignorecagefs
            fi
        else
            echo "EA4 PHP ${EAVERSION} not installed!"
        fi

        echo ""
        echo ""
    done

    echo ""
    echo ""
    echo "TimeZoneDB installed for all Easy Apache 4 PHP Versions."

elif [ "${yn}" = 'php_ver' ]; then
    MMVERSION="$(echo "${PHP_VER}" | sed 's/\.//g')"

    if [ "${check}" = "true" ]; then
        ./buildtimezone.sh "${PHP_VER}" y
    else
        check_devel
        ./buildtimezone.sh "${PHP_VER}" ignorecagefs
    fi

    echo ""
    echo ""
    echo "TimeZoneDB installed for all Easy Apache 4 PHP Versions."
else
    echo "You have cancelled the install."
    show_help
fi

if [ "${check}" != "true" ]; then
  cagefs
fi

exit 0
