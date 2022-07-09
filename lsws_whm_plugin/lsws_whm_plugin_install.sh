#!/bin/sh

# /********************************************
# LiteSpeed Web Server Plugin for WHM
#
# @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @copyright (c) 2008-2022
# *********************************************/

#
# If from lsws install, 3 params
# WHM_PLUGIN_SRCDIR=$1
# LSWS_HOME=$2
# CPANEL_PLUGIN_AUTOINSTALL=$3
#

WHM_PLUGIN_TEMPDIR="/usr/src/lsws_whm"
WHM_DOCROOT="/usr/local/cpanel/whostmgr/docroot"
WHM_PLUGIN_CGIDIR="${WHM_DOCROOT}/cgi"
WHM_PLUGIN_ICONDIR="${WHM_DOCROOT}/addon_plugins"
WHM_PLUGIN_INSDIR="${WHM_PLUGIN_CGIDIR}/lsws"
WHM_PLUGIN_TMPL_INSDIR="${WHM_DOCROOT}/templates/lsws"
WHM_PLUGIN_LSCWP_SRC_DIR="/usr/src/litespeed-wp-plugin"
WHM_PLUGIN_HTTPDIR="http://www.google.com"
THEME_JUPITER_PLUGIN_DIR="/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager"
THEME_PAPER_LANTERN_PLUGIN_DIR="/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager"
CPANEL_PLUGIN_CAPABLE=0

if [ ! -d "${WHM_PLUGIN_CGIDIR}" ] ; then
    exit
fi

WHM_PLUGIN_INST_USER=$(id)
WHM_PLUGIN_INST_USER=$(expr "${WHM_PLUGIN_INST_USER}" : 'uid=.*(\(.*\)) gid=.*')

if [ "${WHM_PLUGIN_INST_USER}" != "root" ]  ; then
    echo "Require root permission to install this plugin. Abort!"
    exit
fi

if [ "x${2}" = "x" ] ; then
    LSWS_HOME="/usr/local/lsws"
else
    LSWS_HOME="${2}"
fi

WEBCACHE_MGR_DATA_DIR="${LSWS_HOME}/admin/lscdata"
CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG="${WHM_PLUGIN_INSDIR}/cpanel_autoinstall_off"
TMP_CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG="/tmp/cpanel_autoinstall_off"
WHM_PLUGIN_DATA_DIR="${WHM_PLUGIN_INSDIR}/data"
TMP_WHM_PLUGIN_DATA_DIR="/tmp/lsws_whm_plugin_data_tmp"
WHM_PLUGIN_LSWS_HOME_FILE="${WHM_PLUGIN_INSDIR}/LSWS_HOME.config"
TMP_WHM_PLUGIN_LSWS_HOME_FILE="/tmp/LSWS_HOME.config"

whmPluginNeedsUpdate()
{
return 0;
}

gwhmPluginNeedsUpdate()
{
    CURR_WHM_VER="${1}"

    LASTEST_WHM_VER="99.99.99.99"

    FILTERED_LATEST_WHM_VER="$(echo "${LATEST_WHM_VER}" | \
      sed -e 's/^[^[:digit:]]*\.//g' -e 's/\.\+$//g' -e 's/\.+$//g' -e 's/\.\.\+/\./g' -e 's/[^[:digit:]\.]//g')"

    if [ "${LATEST_WHM_VER}" != "${FILTERED_LATEST_WHM_VER}" ] ; then
        echo "Failed to query latest WHM plugin version (unexpected content). Abort!"
        exit;
    fi

    CURR_MAJOR=$(echo "${CURR_WHM_VER}" | awk -F"." '{print $1}')
    LATEST_MAJOR=$(echo "${LATEST_WHM_VER}" | awk -F"." '{print $1}')

    if [ "${CURR_MAJOR}" -lt "${LATEST_MAJOR}" ] ; then
        return 0
    elif [ "${CURR_MAJOR}" -gt "${LATEST_MAJOR}" ] ; then
        return 1
    fi

    CURR_MINOR=$(echo "${CURR_WHM_VER}" | awk -F"." '{print $2}')
    LATEST_MINOR=$(echo "${LATEST_WHM_VER}" | awk -F"." '{print $2}')

    if [ "${CURR_MINOR}" -lt "${LATEST_MINOR}" ] ; then
        return 0
    elif [ "${CURR_MINOR}" -gt "${LATEST_MINOR}" ] ; then
        return 1
    fi

    CURR_IMPROVEMENT=$(echo "${CURR_WHM_VER}" | awk -F"." '{print match($3, /[^ ]/) ? $3 : 0}')
    LATEST_IMPROVEMENT=$(echo "${LATEST_WHM_VER}" | awk -F"." '{print match($3, /[^ ]/) ? $3 : 0}')

    if [ "${CURR_IMPROVEMENT}" -lt "${LATEST_IMPROVEMENT}" ] ; then
        return 0
    elif [ "${CURR_IMPROVEMENT}" -gt "${LATEST_IMPROVEMENT}" ] ; then
        return 1
    fi

    CURR_PATCH=$(echo "${CURR_WHM_VER}" | awk -F"." '{print match($4, /[^ ]/) ? $4 : 0}')
    LATEST_PATCH=$(echo "${LATEST_WHM_VER}" | awk -F"." '{print match($4, /[^ ]/) ? $4 : 0}')

    if [ "${CURR_PATCH}" -lt "${LATEST_PATCH}" ] ; then
        return 0
    elif [ "${CURR_PATCH}" -gt "${LATEST_PATCH}" ] ; then
        return 1
    fi

    return 1
}

echo ""
echo " Install LiteSpeed Web Server Plugin for WHM"
echo "=============================================="
echo ""

CURR_WHM_VER_FILE="${WHM_PLUGIN_INSDIR}/VERSION"

if [ -e "${CURR_WHM_VER_FILE}" ] ; then

    CURR_WHM_VER=$(cat "${CURR_WHM_VER_FILE}")

    if ! whmPluginNeedsUpdate "${CURR_WHM_VER}" ; then
        echo "Installed WHM Plugin version already up-to-date. Abort!"
        exit;
    fi
fi

if [ "x${1}" = "x" ] ; then
    echo "... creating directories ..."

    # Create temp directory to install
    if [ ! -e "${WHM_PLUGIN_TEMPDIR}" ] ; then
        mkdir -v -p "${WHM_PLUGIN_TEMPDIR}"
        echo "  Temp directory created"
    fi
    HOMEPWD=$(pwd)
    cd "${WHM_PLUGIN_TEMPDIR}"
    cp -rf $HOMEPWD $WHM_PLUGIN_TEMPDIR
fi

# Create working directories for WHM PHP files and backup any existing data
if [ -e "${WHM_PLUGIN_INSDIR}" ] ; then

    if [ -e "${WHM_PLUGIN_CGIDIR}/addon_lsws.cgi" ] ; then
        echo "  Removing old entry script addon_lsws.cgi"
        /bin/rm -f "${WHM_PLUGIN_CGIDIR}/addon_lsws.cgi"
    fi

    if [ ! -e "${WEBCACHE_MGR_DATA_DIR}" ] ; then

        if [ -e "${LSWS_HOME}/admin" ] ; then
            mkdir "${WEBCACHE_MGR_DATA_DIR}"
        fi
    fi

    if [ -e "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" ] ; then
        /bin/mv "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" "${TMP_CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}"
    fi

    if [ -e "${WHM_PLUGIN_DATA_DIR}" ] ; then
        /bin/mv "${WHM_PLUGIN_DATA_DIR}" "${TMP_WHM_PLUGIN_DATA_DIR}"
    fi

    if [ -e "${WHM_PLUGIN_LSWS_HOME_FILE}" ] ; then
        /bin/mv "${WHM_PLUGIN_LSWS_HOME_FILE}" "${TMP_WHM_PLUGIN_LSWS_HOME_FILE}"
    fi

    echo "  Removing old working directory ${WHM_PLUGIN_INSDIR}"
    /bin/rm -rf "${WHM_PLUGIN_INSDIR}"
fi

if [ -e "${WHM_PLUGIN_TMPL_INSDIR}" ] ; then
    echo " Removing old template directory ${WHM_PLUGIN_TMPL_INSDIR}"
    /bin/rm -rf "${WHM_PLUGIN_TMPL_INSDIR}"
fi

#Cleanup old lsc data from installs < 2.1.12
if [ -e "${LSWS_HOME}/add-ons/webcachemgr/shared/lsc_versions_data" ] ; then
    /bin/rm -f "${LSWS_HOME}/add-ons/webcachemgr/shared/lsc_versions_data"
fi

if [ -e "${LSWS_HOME}/add-ons/webcachemgr/shared/lsc_manager_data" ] ; then
    /bin/rm -f "${LSWS_HOME}/add-ons/webcachemgr/shared/lsc_manager_data"
fi

#cleanup old lsc data/files from installs < 3.0.0
if [ -e "${WEBCACHE_MGR_DATA_DIR}/lsc_manager_data" ] ; then
    /bin/rm -f "${WEBCACHE_MGR_DATA_DIR}/lsc_manager_data"
fi

if [ -e "${WEBCACHE_MGR_DATA_DIR}/lsc_versions_data" ] ; then
    /bin/rm -f "${WEBCACHE_MGR_DATA_DIR}/lsc_versions_data"
fi

#force new data files permissions
if [ -e "${WEBCACHE_MGR_DATA_DIR}/lscm.data" ] ; then
    chmod 600 "${WEBCACHE_MGR_DATA_DIR}/lscm.data"
fi

if [ -e "${WEBCACHE_MGR_DATA_DIR}/lscm.data.cust" ] ; then
    chmod 600 "${WEBCACHE_MGR_DATA_DIR}/lscm.data.cust"
fi

mkdir -v "${WHM_PLUGIN_INSDIR}"
mkdir -v "${WHM_PLUGIN_TMPL_INSDIR}"

if [ -e "${TMP_CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" ] ; then
    /bin/mv "${TMP_CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}"
    echo "  Retained disable cPanel plugin auto install flag file"
fi

if [ -e "${TMP_WHM_PLUGIN_DATA_DIR}" ] ; then
    /bin/mv "${TMP_WHM_PLUGIN_DATA_DIR}" "${WHM_PLUGIN_DATA_DIR}"
    echo "  Retained WHM plugin data dir files"
fi

if [ -e "${TMP_WHM_PLUGIN_LSWS_HOME_FILE}" ] ; then
    /bin/mv "${TMP_WHM_PLUGIN_LSWS_HOME_FILE}" "${WHM_PLUGIN_LSWS_HOME_FILE}"
    echo "  Retained LSWS_HOME.config file"
fi

echo ""


if [ "x${1}" = "x" ] ; then
    WHM_PLUGIN_SRCDIR="${WHM_PLUGIN_TEMPDIR}/lsws_whm_plugin"
    /bin/cp -r "${WHM_PLUGIN_SRCDIR}"/* "${WHM_PLUGIN_INSDIR}/"

    cd "${WHM_PLUGIN_INSDIR}"
#    # Removes install files
    /bin/rm -rf "${WHM_PLUGIN_TEMPDIR}"
else
    # install from lsws addon
    WHM_PLUGIN_SRCDIR="${1}"
    /bin/cp -r "${WHM_PLUGIN_SRCDIR}"/* "${WHM_PLUGIN_INSDIR}/"
    echo "LSWS_HOME=${LSWS_HOME}" > "${WHM_PLUGIN_LSWS_HOME_FILE}"
fi

echo "... moving files ..."

if [ ! -e "${WHM_PLUGIN_ICONDIR}" ] ; then
    mkdir -v "${WHM_PLUGIN_ICONDIR}"
fi

/bin/cp -f "${WHM_PLUGIN_INSDIR}/lsws_icon.png" "${WHM_PLUGIN_ICONDIR}/"
/bin/mv -f "${WHM_PLUGIN_INSDIR}/lsws.html.tt" "${WHM_PLUGIN_TMPL_INSDIR}/"

echo "... setting permission to files ..."
chmod -R 600 "${WHM_PLUGIN_INSDIR}"
chmod 700 "${WHM_PLUGIN_INSDIR}"/*.cgi
chmod 700 "${WHM_PLUGIN_INSDIR}"/*.sh
chmod 700 "${WHM_PLUGIN_INSDIR}/bin"
chmod 700 "${WHM_PLUGIN_INSDIR}"/bin/*.sh

if [ -e "${WHM_PLUGIN_INSDIR}/res/ls_web_cache_mgr" ] ; then
    chmod 700 "${WHM_PLUGIN_INSDIR}"/res/ls_web_cache_mgr/*.sh
    CPANEL_PLUGIN_CAPABLE=1;
fi

# update easyapache hooks
"${WHM_PLUGIN_INSDIR}/bin/whm_eahook.sh" REFRESH

sed -i 's/target=mainFrame/target=_self/' "${WHM_PLUGIN_INSDIR}/lsws.conf"

if [ -e "/usr/local/cpanel/bin/register_appconfig" ] ; then
    REGISTERED=$(/usr/local/cpanel/bin/is_registered_with_appconfig whostmgr lsws)

    if [ "x${REGISTERED}" != "x1" ] ; then
        echo "Register LSWS Plugin ..."

        if [ ! -e "/var/cpanel/apps" ] ; then
            mkdir -v "/var/cpanel/apps"
            echo "  apps registration directory created"
        fi
    else
        # check if config changed
        CHANGED=$(diff /var/cpanel/apps/lsws.conf "${WHM_PLUGIN_INSDIR}/lsws.conf")

        if [ "${CHANGED}" != "" ] ; then
            REGISTERED=0
        fi
    fi

    if [ "x${REGISTERED}" != "x1" ] ; then
        /usr/local/cpanel/bin/register_appconfig "${WHM_PLUGIN_INSDIR}/lsws.conf"
    fi

    /bin/rm -f "${WHM_PLUGIN_INSDIR}/addon_lsws.cgi"
else
    echo "old version no AppConfig, place entry at parent dir"
    /bin/mv -f "${WHM_PLUGIN_INSDIR}/addon_lsws.cgi" "${WHM_PLUGIN_CGIDIR}/"
    /bin/rm -f "${WHM_PLUGIN_INSDIR}/lsws.cgi"
fi

#Create and add needed LSCWP source directory
if [ ! -e "${WHM_PLUGIN_LSCWP_SRC_DIR}" ] ; then
    mkdir -m 755 "${WHM_PLUGIN_LSCWP_SRC_DIR}"
fi

#CageFs remount for $WHM_PLUGIN_LSCWP_SRC_DIR handled in fix_cagefs.sh
if [ -f "/etc/cagefs/cagefs.mp" ] ; then
    FORCE_REMOUNT=0

    if ! grep -v 'deleted' /proc/mounts | grep -q 'litespeed-wp-plugin' ; then
        FORCE_REMOUNT=1

        if  ! grep -Eq "^${WHM_PLUGIN_LSCWP_SRC_DIR}$" /etc/cagefs/cagefs.mp; then
            # shellcheck disable=SC1003
            sed -i -e '$a\' /etc/cagefs/cagefs.mp
            echo "${WHM_PLUGIN_LSCWP_SRC_DIR}" >> /etc/cagefs/cagefs.mp
        fi
    fi

    THIS_DIR=$( cd "$( dirname "${0}" )" && pwd )

    if [ -f "${LSWS_HOME}/admin/misc/fix_cagefs.sh" ] ; then
        #If CageFS, add LSWS to cage if missing.
        "${LSWS_HOME}/admin/misc/fix_cagefs.sh" "${FORCE_REMOUNT}"

    elif [ -f "${THIS_DIR}/../../../admin/misc/fix_cagefs.sh" ] ; then
        #LSWS not installed yet, run package relative script.
        "${THIS_DIR}/../../../admin/misc/fix_cagefs.sh" "${FORCE_REMOUNT}"

    else
        echo "Could not find fix_cagefs.sh! Script was not executed."
    fi
fi

#Exclude LSCache files from backup
CP_BACKUP_EXCLUDE_MASTER=/usr/local/cpanel/etc/cpbackup-exclude.conf
CP_BACKUP_EXCLUDE_GLOBAL=/etc/cpbackup-exclude.conf

if [ ! -f "${CP_BACKUP_EXCLUDE_GLOBAL}" ] && [ -f "${CP_BACKUP_EXCLUDE_MASTER}" ] ; then
    cp "${CP_BACKUP_EXCLUDE_MASTER}" "${CP_BACKUP_EXCLUDE_GLOBAL}"
fi

if [ -f "${CP_BACKUP_EXCLUDE_GLOBAL}" ] ; then
    grep -Fxq "lscache/" "${CP_BACKUP_EXCLUDE_GLOBAL}"
    FOUND="${?}"
fi

if [ ! -f "${CP_BACKUP_EXCLUDE_GLOBAL}" ] || [ "${FOUND}" -ne 0 ] ; then
    echo "lscache/" >> "${CP_BACKUP_EXCLUDE_GLOBAL}"
fi

##
# Install/Update cPanel Plugin if found
##
if [ "${CPANEL_PLUGIN_CAPABLE}" -eq 1 ] ; then
    CPANEL_PLUGIN_AUTOINSTALL=1

    if [ "x${3}" = "x"  ] ; then

        if [ -e "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" ] ; then
            CPANEL_PLUGIN_AUTOINSTALL=0
        fi
    else
        CPANEL_PLUGIN_AUTOINSTALL="${3}"

        if [ "${CPANEL_PLUGIN_AUTOINSTALL}" -eq 1 ] ; then

            if [ -e "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}" ] ; then
                /bin/rm -f "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}"
            fi
        else
            touch "${CPANEL_PLUGIN_AUTOINSTALL_DISABLE_FLAG}"
        fi
    fi

    LSCMCTL_SCRIPT="${LSWS_HOME}/admin/misc/lscmctl"

    if [ -e "${LSCMCTL_SCRIPT}" ] ; then

        if "${LSCMCTL_SCRIPT}" --help | grep -q 'cpanelplugin' \
                && [ "${CPANEL_PLUGIN_AUTOINSTALL}" -eq 1 ] \
                || [ -e "${THEME_JUPITER_PLUGIN_DIR}" ] \
                || [ -e "${THEME_PAPER_LANTERN_PLUGIN_DIR}" ]
        then
            "${LSCMCTL_SCRIPT}" cpanelplugin --install
        fi
    fi
fi

echo ""
echo " LiteSpeed WHM Plugin Installed Successfully."
echo "=============================================="
