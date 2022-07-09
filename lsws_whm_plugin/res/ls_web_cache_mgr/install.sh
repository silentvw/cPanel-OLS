#!/bin/sh
#
# SCRIPT: install.sh
# PURPOSE: Install LS Web Cache Manager cPanel Plugin
# AUTHOR: LiteSpeed Technologies
#

PLUGIN_DIR_NAME='ls_web_cache_manager';
PLUGIN_3RD_PARTY_DIR='/usr/local/cpanel/3rdparty/ls_webcache_mgr'
PLUGIN_CONF_FILE="${PLUGIN_3RD_PARTY_DIR}/lswcm.conf"
CPANEL_FRONTEND_DIR='/usr/local/cpanel/base/frontend'
THEME_DIR_PAPER_LANTERN="${CPANEL_FRONTEND_DIR}/paper_lantern"
THEME_DIR_JUPITER="${CPANEL_FRONTEND_DIR}/jupiter"
TEMP_CONF_FILE='/usr/local/cpanel/whostmgr/docroot/cgi/lsws/data/lswcm.conf'
PERL_MODULE_DIR='/usr/local/cpanel/Cpanel/API'
API_DIR='/usr/local/cpanel/bin/admin/Lsws'
THEME_PAPER_LANTERN_DETECTED=0
THEME_JUPITER_DETECTED=0

STARTING_DIR=$(pwd)
SCRIPT_DIR=$(cd "$(dirname "${0}")" && pwd)

cd "${SCRIPT_DIR}" || { echo "Unable to cd to script dir ${SCRIPT_DIR}"; exit 1; }

echo 'Installing LS Web Cache Manager...'
echo ""

PLUGIN_DIR=''

if [ -d "${THEME_DIR_JUPITER}" ]
then
    THEME_JUPITER_DETECTED=1
    PLUGIN_DIR="${THEME_DIR_JUPITER}/${PLUGIN_DIR_NAME}"

    # checks for existing plugin folder and	deletes	if exists
    if [ -d "${PLUGIN_DIR}" ]
    then
        rm -rf "${PLUGIN_DIR}"
    fi

    # Create the directory for the plugin
    mkdir -p "${PLUGIN_DIR}"

    # Move all files to plugin directory
    cp -r -- * "${PLUGIN_DIR}/"

    chmod -R 644 "${PLUGIN_DIR}"
    find "${PLUGIN_DIR}/" -type d -execdir chmod 755 {} +
    chmod 700 "${PLUGIN_DIR}"/*.sh
    chmod -R 700 "${PLUGIN_DIR}/scripts"
    chmod 600 "${PLUGIN_DIR}"/scripts/*.php

    #Move interface file to theme root directory.
    mv -f "${PLUGIN_DIR}/ls_web_cache_manager.html.tt" "${PLUGIN_DIR}/.."
fi

if [ -d "${THEME_DIR_PAPER_LANTERN}" ]
then
    THEME_PAPER_LANTERN_DETECTED=1
    PLUGIN_DIR="${THEME_DIR_PAPER_LANTERN}/${PLUGIN_DIR_NAME}"

    # checks for existing plugin folder and	deletes	if exists
    if [ -d "${PLUGIN_DIR}" ]
    then
        rm -rf "${PLUGIN_DIR}"
    fi

    # Create the directory for the plugin
    mkdir -p "${PLUGIN_DIR}"

    # Move all files to plugin directory
    cp -r -- * "${PLUGIN_DIR}/"

    chmod -R 644 "${PLUGIN_DIR}"
    find "${PLUGIN_DIR}/" -type d -execdir chmod 755 {} +
    chmod 700 "${PLUGIN_DIR}"/*.sh
    chmod -R 700 "${PLUGIN_DIR}/scripts"
    chmod 600 "${PLUGIN_DIR}"/scripts/*.php

    #Move interface file to theme root directory.
    mv -f "${PLUGIN_DIR}/ls_web_cache_manager.html.tt" "${PLUGIN_DIR}/.."
fi


if [ "${PLUGIN_DIR}" = '' ] ; then
    echo 'A recognized cPanel theme was not detected. LS Web Cache Manager plugin not installed.'
    echo ""
    exit 1;
fi

#create plugin 3rdparty dir if needed
if [ ! -d "${PLUGIN_3RD_PARTY_DIR}" ] ; then
    mkdir -m 755 "${PLUGIN_3RD_PARTY_DIR}"
else
    chmod 755 "${PLUGIN_3RD_PARTY_DIR}"
fi

if [ ! -f "${PLUGIN_CONF_FILE}" ] ; then
    # Copy WHM temp cPanel plugin conf file or default plugin conf file to plugin 3rdparty directory
    if [ -f "${TEMP_CONF_FILE}" ] ; then
        cp "${TEMP_CONF_FILE}" "${PLUGIN_3RD_PARTY_DIR}/"
    else
        cp "${PLUGIN_DIR}/lswcm.conf.default" "${PLUGIN_CONF_FILE}"
    fi

    chmod 644 "${PLUGIN_CONF_FILE}"
fi

echo 'Installing needed Perl module and custom API calls...'
echo ""

cp -f ../lsws.pm "${PERL_MODULE_DIR}/"

if [ ! -d "${API_DIR}" ] ; then
    mkdir "${API_DIR}"
fi

cp -f ../lswsAdminBin* "${API_DIR}/"

chmod 700 "${API_DIR}/lswsAdminBin"
chmod 644 "${API_DIR}/lswsAdminBin.conf"
chmod 644 "${PERL_MODULE_DIR}/lsws.pm"

# Install the plugin to the appropriate theme(s). This will also place the png image in the proper location.

if [ "${THEME_JUPITER_DETECTED}" -eq 1 ] ; then
    /usr/local/cpanel/scripts/install_plugin \
        "${THEME_DIR_JUPITER}/${PLUGIN_DIR_NAME}/ls_web_cache_manager.tar.gz" \
        --theme=jupiter
fi

if [ "${THEME_PAPER_LANTERN_DETECTED}" -eq 1 ] ; then
    /usr/local/cpanel/scripts/install_plugin \
        "${THEME_DIR_PAPER_LANTERN}/${PLUGIN_DIR_NAME}/ls_web_cache_manager.tar.gz" \
        --theme=paper_lantern
fi

# Check EC cert support setting
EC_SUPPORT_SETTING=$(grep -oP '(?<=GENERATE_EC_CERTS = )(\d)' "${PLUGIN_CONF_FILE}")

if [ "${EC_SUPPORT_SETTING}" != "0" ] ; then
    echo "Enabling EC cert support..."
    echo ""

    "${PLUGIN_DIR}/scripts/cert_support_add.sh"
fi

echo 'Installation for LS Web Cache Manager Completed!'
echo ""

cd "${STARTING_DIR}" || { echo "Unable to cd back to dir ${STARTING_DIR}"; exit 1; }
