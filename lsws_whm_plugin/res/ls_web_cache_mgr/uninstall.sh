#!/bin/sh
#
# SCRIPT: uninstall.sh
# PURPOSE: Uninstall LS Web Cache Manager cPanel Plugin
# AUTHOR: LiteSpeed Technologies
#

THEME_JUPITER_PLUGIN_DIR='/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager'
THEME_PAPER_LANTERN_PLUGIN_DIR='/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager'
PERL_MODULE='/usr/local/cpanel/Cpanel/API/lsws.pm'
API_DIR='/usr/local/cpanel/bin/admin/Lsws'

STARTING_DIR=$(pwd)
SCRIPT_DIR=$(cd "$(dirname "${0}")" && pwd)

cd "${SCRIPT_DIR}" || { echo "Unable to cd to script dir ${SCRIPT_DIR}"; exit 1; }

echo 'Uninstalling LS Web Cache Manager...'
echo "";

if [ -e "${PERL_MODULE}" ] ; then
    echo 'Removing custom LiteSpeed Perl module...'
    /bin/rm -f "${PERL_MODULE}"
    echo "";
fi

if [ -d "${API_DIR}" ] ; then
    echo 'Removing custom LiteSpeed API calls...'
    /bin/rm -rf "${API_DIR}"
    echo "";
fi

EC_HOOK_REMOVED=0

if [ -e "${THEME_JUPITER_PLUGIN_DIR}" ] ; then
    PLUGIN_DIR="${THEME_JUPITER_PLUGIN_DIR}"

    #Remove cPanel plugin and files
    if [ -e "${PLUGIN_DIR}" ] ; then

        if [ "${EC_HOOK_REMOVED}" -eq 0 ] ; then
            echo 'Removing EC support hook...'
            "${PLUGIN_DIR}/scripts/cert_support_remove.sh"
            echo ""

            EC_HOOK_REMOVED=1
        fi

        echo 'Removing Jupiter theme LS Web Cache Manager cPanel Plugin...'
        /usr/local/cpanel/scripts/uninstall_plugin ${PLUGIN_DIR}/ls_web_cache_manager.tar.gz --theme=jupiter
        /bin/rm -f "${PLUGIN_DIR}/../ls_web_cache_manager.html.tt"
        /bin/rm -rf "${PLUGIN_DIR}"
        echo ""
    fi
fi

if [ -e "${THEME_PAPER_LANTERN_PLUGIN_DIR}" ] ; then
    PLUGIN_DIR="${THEME_PAPER_LANTERN_PLUGIN_DIR}"

    #Remove cPanel plugin and files
    if [ -e "${PLUGIN_DIR}" ] ; then

        if [ "${EC_HOOK_REMOVED}" -eq 0 ] ; then
            echo 'Removing EC support hook...'
            "${PLUGIN_DIR}/scripts/cert_support_remove.sh"
            echo ""

            EC_HOOK_REMOVED=1
        fi

        echo 'Removing paper_lantern theme LS Web Cache Manager cPanel Plugin...'
        /usr/local/cpanel/scripts/uninstall_plugin ${PLUGIN_DIR}/ls_web_cache_manager.tar.gz --theme=paper_lantern
        /bin/rm -f "${PLUGIN_DIR}/../ls_web_cache_manager.html.tt"
        /bin/rm -rf "${PLUGIN_DIR}"
        echo ""
    fi
fi

echo "Uninstallation finished."
echo ""

cd "${STARTING_DIR}" || { echo "Unable to cd back to dir ${STARTING_DIR}"; exit 1; }
