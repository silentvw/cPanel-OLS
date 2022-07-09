#!/bin/sh
set -o errexit

# /********************************************************************
# LiteSpeed timezonedb.so builder
# @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @Copyright: (c) 2016-2022
# @Version: 1.4.4
# *********************************************************************/

show_help()
{
    cat <<EOF

    # ***********************************************************************
    #
    # This script will auto build the source version of timezonedb.so for PHP.
    #
    # Prerequisites:
    #    1. cPanel's EA PHP environment only.
    #
    # Command:
    #    buildtimezone.sh PHPVERSION
    #
    # Example:
    #    buildtimezone.sh 7.0.1
    #
    # Input parameters:
    #
    # 1. (Required) PHPVERSION
    #    This will build timezonedb.so for the specified version of PHP.
    # 2. (Optional) CHECK
    #    Either y or n . Checks to see if latest
    #    If y then it will exit on check. It will always check but won't always
    #    continue unless CHECK=n
    # 3. (Optional) PHPPATH
    #    This will look for phpize and php-config in the specified path.
    # 4. (Optional) MODULEPATH
    #    This will look for php modules in the specified path.
    # 5. (Optional) INIPATH
    #    This will look for php.ini in the specified path.
    #
    # ***********************************************************************

EOF
exit 1
}

# Check args
if [ "${#}" -eq 0 ] ; then
    echo "Invalid params!"
    show_help
fi

# Check to make sure php version is all numbers
if [ "${#}" -gt 0 ] ; then
    INPUT_PHP_VER="${1}"
    FILTERED_PHP_VER="$(echo "${INPUT_PHP_VER}" \
      | sed -e 's/^[^[:digit:]]*\.//g' -e 's/\.\+$//g' -e 's/\.+$//g' -e 's/\.\.\+/\./g' -e 's/[^[:digit:]\.]//g')"

    if [ "${INPUT_PHP_VER}" = "${FILTERED_PHP_VER}" ] ; then
        PHPVERSION="${INPUT_PHP_VER}"
        MMVERSION="$(echo "${PHPVERSION}" | sed -E 's/([[:digit:]]+)\.([[:digit:]]+).*/\1\2/g')"
    else
        echo "Invalid PHP Version!"
        show_help
    fi
fi

if [ "${#}" -gt 1 ]; then

    if [ "${2}" = "y" ]; then
        CHECK="y"
    fi

    if [ "${2}" = "ignorecagefs" ]; then
        IGNORECAGEFS="true"
    else
        IGNORECAGEFS="false"
    fi
fi

if [ "${#}" -gt 2 ]; then
    PHPPATH="${3}"
else
    PHPPATH="/opt/cpanel/ea-php${MMVERSION}/root/usr/bin"
fi

if [ "${#}" -gt 3 ]; then
    MODULEPATH="${4}"
else
    MODULEPATH="/opt/cpanel/ea-php${MMVERSION}/root/usr/lib64/php/modules/"
fi

if [ "${#}" -gt 4 ]; then
    INIPATH="${5}"
else
    INIPATH="/opt/cpanel/ea-php${MMVERSION}/root/etc/"
fi

# Variables
TMPFOLDER=/usr/src/buildtimezone
SOURCETZ=timezone

# Be sure to set POSSIBLE_INI as newline delimited list from Greatest to Least in hierarchy
POSSIBLE_INIS="php.d/02-pecl.ini
php.d/local.ini
php.d/timezonedb.ini
php.ini"

# check if user is root
checkPermission()
{
    INST_USER="$(id)"
    INST_USER="$(expr "${INST_USER}" : 'uid=.*(\(.*\)) gid=.*')"

    if [ "${INST_USER}" != "root" ]  ; then
	     checkErrs 1 "Require root permission to install this script. Abort!"
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

# Get timezonedb source
getTimeZone()
{
    wget -q --no-check-certificate --output-document="${SOURCETZ}.tar.gz" https://pecl.php.net/get/timezonedb
}

success()
{
    echo "Success!"
}

# funcIni: Handle adding/removing/checking to make sure extension is loaded via ini
# Updated: December 17
funcIni() {
  COUNT="$(grep -Ro "^extension=.*timezonedb.so.*$" "${INIPATH}" | grep -cv 'grep' || true)"

  if [ "${COUNT}" -gt 1 ] || [ ! -f "${MODULEPATH}/timezonedb.so" ]; then
    ENABLED_INIS="$(grep -Ro "^extension=.*timezonedb.so.*$" "${INIPATH}" |  grep -v 'grep' | cut -d : -f 1 | sed "s/\/opt\/cpanel\/ea-php${MMVERSION}\/root\/etc\///g")"

    if echo "${ENABLED_INIS}" | grep -q 'php.d/02-pecl.ini'; then
      IGNORED_INI='php.d/02-pecl.ini'
    elif echo "${ENABLED_INIS}" | grep -q 'php.d/local.ini'; then

      if ! echo "${ENABLED_INIS}" | grep -q 'php.d/02-pecl.ini'; then
        IGNORED_INI='php.d/local.ini'
      fi
    elif echo "${ENABLED_INIS}" | grep -q 'php.d/timezonedb.ini'; then

      if ! echo "${ENABLED_INIS}" | grep -q 'php.d/02-pecl.ini' && ! echo "${ENABLED_INIS}" | grep -q 'php.d/local.ini'; then
        IGNORED_INI='php.d/timezonedb.ini'
      fi
    elif echo "${ENABLED_INIS}" | grep -q 'php.ini'; then

      if ! echo "${ENABLED_INIS}" | grep -q 'php.d/02-pecl.ini' && ! echo "${ENABLED_INIS}" | grep -q 'php.d/local.ini' && ! echo "${ENABLED_INIS}" | grep -q 'php.d/timezonedb.ini'; then
        IGNORED_INI='php.ini'
      fi
    fi

    echo "${ENABLED_INIS}" | while read -r ENABLED_INI; do

      if [ "${ENABLED_INI}" = '' ] || { [ "${ENABLED_INI}" = "${IGNORED_INI}" ] && [ -f "${MODULEPATH}/timezonedb.so" ]; } then
        continue
      fi

      sed -i "/extension=.*timezonedb.so.*/d" "${INIPATH}${ENABLED_INI}"
    done
  elif [ "${COUNT}" -eq 0 ] && [ -f "${MODULEPATH}/timezonedb.so" ]; then

    # POSSIBLE_INI is a newline delimited string.
    echo "${POSSIBLE_INIS}" | while read -r POSSIBLE_INI; do

      if [ -f "${INIPATH}${POSSIBLE_INI}" ]; then
        # shellcheck disable=SC1003
        sed -i -e '$a\' "${INIPATH}${POSSIBLE_INI}"
        echo "extension=\"timezonedb.so\"" >> "${INIPATH}${POSSIBLE_INI}"
        break
      fi
    done
  else
    ONLY_INI="$(grep -Ro "^extension=.*timezonedb.so.*$" "${INIPATH}" |  grep -v 'grep' | cut -d : -f 1 | sed "s/\/opt\/cpanel\/ea-php${MMVERSION}\/root\/etc\///g")"
    echo "TimeZoneDB already loaded via a '${ONLY_INI}'."
  fi
}

checkTimeZone()
{
    # Lets check to see if latest or not.
    LATEST="$(curl -I https://pecl.php.net/get/timezonedb 2>/dev/null | grep 'filename=' | grep -o '[0-9]\+.[0-9]\+')"
    echo "Latest Version: ${LATEST}"
    NOW="$(date +"%Y_%m_%d-%H_%M_%S")"

    if [ -e "${MODULEPATH}/timezonedb.so" ]; then
      echo "timezonedb.so already installed"
      echo "checking to see if it is enabled in ini"
      funcIni
      OLSON="$("${PHPPATH}/php" -i | grep '"Olson" Timezone Database Version' | grep -o '[0-9]\+.[0-9]\+')"

      LATEST_MAJOR=$(echo "${LATEST}" | awk -F"." '{print $1}')
      OLSON_MAJOR=$(echo "${OLSON}" | awk -F"." '{print $1}')

      if [ "${LATEST_MAJOR}" -gt "${OLSON_MAJOR}" ] ; then
        NEEDSUPDATE="true"
      else
        LATEST_MINOR=$(echo "${LATEST}" | awk -F"." '{print $2}')
        OLSON_MINOR=$(echo "${OLSON}" | awk -F"." '{print $2}')

        if [ "${LATEST_MINOR}" -gt "${OLSON_MINOR}" ] ; then
          NEEDSUPDATE="true"
        fi
      fi

      if [ "${NEEDSUPDATE}" = "true" ]; then
        echo "Installed Version: ${OLSON}"

        if [ "${CHECK}" = "y" ]; then
          echo "Newer version found for ${PHPVERSION}"
          exit 1
        else

          if [ -e "${MODULEPATH}/timezonedb.so" ]; then
            BACKUPMODULE="true"
            cp "${MODULEPATH}/timezonedb.so" "${MODULEPATH}/timezonedb.so.${NOW}"
            rm -rf "${MODULEPATH}/timezonedb.so"
          fi

          if [ ! -e "${INIPATH}/php.d/timezonedb.ini" ] || [ ! -e "${INIPATH}/php.d/local.ini" ] ; then

            if grep -q -e "extension=timezonedb.so" "${INIPATH}/php.ini" ; then
              BACKUPINI="true"
              cp "${INIPATH}/php.ini" "${INIPATH}/php.ini.${NOW}"
              sed -i -e "/extension=timezonedb.so/d" "${INIPATH}/php.ini"
            fi
          fi
        fi
      else
        echo "Success! timezonedb.so already built, latest version, and enabled"
        echo "Installed Version: ${OLSON}"
        echo "EA4 PHP ${PHPVERSION} timezonedb ${LATEST} already installed!"
        exit 1
      fi
    elif [ "${CHECK}" = "y" ]; then
      echo "Installed Version: N/A Not Installed"
      exit 1
    fi
}

cagefs()
{
    if [ "${IGNORECAGEFS}" != "true" ]; then
        echo "Checking to see if CageFS is enabled."

        if [ -e "/usr/sbin/cagefsctl" ]; then

            if /usr/sbin/cagefsctl --cagefs-status | grep -q "Enabled"; then
                echo "CageFS found and enabled."
                echo "Updating CageFS to support the new module."

                if /usr/sbin/cagefsctl --force-update >/dev/null 2>&1; then
                    echo "CageFS updated successfully."
                else
                    echo "Error running 'cagefsctl --force-update'. Please try running it manually."
                fi
            else
                echo "CageFS not enabled."
                echo "Skipping."
            fi
        else
            echo "CageFS not installed."
            echo "Skipping."
        fi
    fi
}

# Check for root
echo "Checking if user is root....."
checkPermission
success

# Run fixIni to fix if multiple ini files have extension=timezonedb.so
funcIni

# Check to see if timezonedb is already installed and latest version
checkTimeZone

echo "Setup temporary directory and move to the directory....."
# Setup the temporary folder where we will store everything
if [ -e "${TMPFOLDER}" ]; then
    echo "Temp folder already found, deleting"
    rm -rf "${TMPFOLDER}"
fi

mkdir -p "${TMPFOLDER}"

if [ -e "${TMPFOLDER}" ]; then
    cd "${TMPFOLDER}"
else
    checkErrs 1 "${TMPFOLDER} not found please try running the script again."
fi
success

# Extract the files
echo "Download and extract latest timezonedb pecl from php.net....."
getTimeZone

if [ -e "${SOURCETZ}.tar.gz" ]; then
    tar -xzf "${SOURCETZ}.tar.gz"
else
    checkErrs 1 "${SOURCETZ}.tar.gz was not found. Make sure the file was actually downloaded and try the script again."
fi
success

# Figure out which folder to use
echo "Check to see if easy apache 4 php directory exsists......."
if [ ! -e "${PHPPATH}" ]; then
    checkErrs 1 "Easy Apache 4 PHP Folder for version given not found. Please make sure Easy Apache 4 PHP version is installed."
fi
success

# Build the extension
cd timezone*/
echo "Building extension this process could take awhile (5-20min depending on machine). Please do not stop the process......."
if [ -e "${PHPPATH}/phpize" ]; then
    "${PHPPATH}/phpize"

    if [ -e "${PHPPATH}/php-config" ]; then
        ./configure --with-php-config="${PHPPATH}/php-config" 2>/dev/null
        make 2>/dev/null
        make install 2>/dev/null
    else
        checkErrs 1 "php-config was not found please make sure the corresponding Easy Apache 4 php-config for ${PHPVERSION} is in default location"
    fi
else
    checkErrs 1 "phpize was not found please make sure the corresponding Easy Apache 4 phpize for ${PHPVERSION} is in default location"
fi
success

# Enable newly build extension in php.ini
echo "Enable timezonedb.so in php.ini......"
funcIni
success

# Check and Enable CageFS
cagefs

# Remove TMPFOLDER
rm -rf "${TMPFOLDER}"

# check to make sure everything is installed
# Updated: December 17
if [ -e "${MODULEPATH}/timezonedb.so" ]; then

  # POSSIBLE_INI is a newline delimited string.

    if ! (
        echo "${POSSIBLE_INIS}" |
        (
            while read -r POSSIBLE_INI; do

                FOUND="false"
                if [ -f "${INIPATH}${POSSIBLE_INI}" ]; then

                  if grep -Rqo "^extension=.*timezonedb.so.*$" "${INIPATH}${POSSIBLE_INI}"; then
                    FOUND="true"
                    echo "TimeZoneDB has successfully been built from source and is now installed and enabled by PHP."
                    echo "Please restart LSWS for changes to take effect."

                        if [ "${NEEDSUPDATE}" = "true" ]; then
                            echo "EA4 PHP ${PHPVERSION} timezonedb has been updated from ${OLSON} to ${LATEST}!"
                        else
                            echo "EA4 PHP ${PHPVERSION} timezonedb ${OLSON} has been installed!"
                        fi

                        break
                    fi
                fi
            done

            if [ "${FOUND}" = 'true' ] ; then
                exit 0
            else
                exit 1
            fi
        )
    )
    then
        checkErrs 1 "Extension not loaded by any ini, please run script again."
    fi
else
    checkErrs 1 "Module not located where it should be, please run script again."
fi

# remove NOW files as long as it updated correctly
# three ini
# Add if they exisit#
if [ -e "${MODULEPATH}/timezonedb.so.${NOW}" ]; then
    rm -rf "${MODULEPATH}/timezonedb.so.${NOW}"
fi

if [ -e "${INIPATH}/php.ini.${NOW}" ]; then
    rm -rf "${INIPATH}/php.ini.${NOW}"
fi

if [ -e "${INIPATH}/php.d/local.ini.${NOW}" ]; then
    rm -rf "${INIPATH}/php.d/local.ini.${NOW}"
fi

if [ -e "${INIPATH}/php.d/timezonedb.ini.${NOW}" ]; then
    rm -rf "${INIPATH}/php.d/timezonedb.ini.${NOW}"
fi

exit 0
