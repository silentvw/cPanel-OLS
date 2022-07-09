#!/bin/sh

# /********************************************
# LiteSpeed Web Server Plugin for WHM
# @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @Copyright: (c) 2008-2021
# *********************************************/

DOCROOT="/usr/local/cpanel/whostmgr/docroot"
CGIDIR="${DOCROOT}/cgi"
INSDIR="${CGIDIR}/lsws"
TMPLDIR="${DOCROOT}/templates/lsws"
ICONFILE="${DOCROOT}/addon_plugins/lsws_icon.png"
THEME_JUPITER_PLUGIN_DIR="/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager"
THEME_PAPER_LANTERN_PLUGIN_DIR="/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager"

# Unregister
if [ -e "/usr/local/cpanel/bin/register_appconfig" ] ; then
    REGISTERED=$(/usr/local/cpanel/bin/is_registered_with_appconfig whostmgr lsws)

    if [ "${REGISTERED}" -eq 1 ] ; then
        echo "Unregister from AppConfig"
        /usr/local/cpanel/bin/unregister_appconfig "${INSDIR}/lsws.conf"
    fi
fi


echo ""
echo "Removing LiteSpeed web server Plugin for WHM"
echo ""


CPANEL_PLUGIN_DIR=''

if [ -e "${THEME_JUPITER_PLUGIN_DIR}" ] ; then
    CPANEL_PLUGIN_DIR="${THEME_JUPITER_PLUGIN_DIR}"
elif [ -e "${THEME_PAPER_LANTERN_PLUGIN_DIR}" ] ; then
    CPANEL_PLUGIN_DIR="${THEME_PAPER_LANTERN_PLUGIN_DIR}"
fi

#Remove cPanel plugin and files
if [ "${CPANEL_PLUGIN_DIR}" != '' ] ; then
    echo "Removing cPanel Plugin..."
    "${CPANEL_PLUGIN_DIR}/uninstall.sh"
    echo ""
fi

echo "...removing directories.."
# Removing working directories for WHM PHP files
if [ -e "${INSDIR}" ]; then
    rm -rf "${INSDIR}"
    echo "Working directory removed : ${INSDIR}"
    echo ""
fi

#Removes the template directory
if [ -e "${TMPLDIR}" ] ; then
    rm -rf "${TMPLDIR}"
    echo "Template directory removed : ${TMPLDIR}"
fi
echo ""

if [ -e "${CGIDIR}/addon_lsws.cgi" ] ; then
    # used in old version of whm
    echo "Removing the old CGI addon file.."
    rm -f "${CGIDIR}/addon_lsws.cgi"
fi

if [ -e "${ICONFILE}" ] ; then
    rm -f "${ICONFILE}"
fi

echo "Uninstallation finished."
echo ""


echo " LiteSpeed WHM Plugin uninstalled."
echo " This script will only remove LiteSpeed Plugin for WHM. It does not remove your LiteSpeed web server installation if you haven't uninstalled it."
 
