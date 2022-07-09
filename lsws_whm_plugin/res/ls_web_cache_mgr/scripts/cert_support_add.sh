#!/bin/sh

# /********************************************
# LiteSpeed Web Cache Management Plugin for cPanel
#
# @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @copyright (c) 2020-2021
# @since 2.1
# *********************************************/

# Add and register autossl event hook for EC cert auto-update

THEME_JUPITER_PLUGIN_DIR='/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager'
THEME_PAPER_LANTERN_PLUGIN_DIR='/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager'
HOOK_SCRIPT='LsWebCacheMgrAutosslHook.php'
CPANEL_3RD_PARTY_BIN='/usr/local/cpanel/3rdparty/bin'

if [ -d "${THEME_JUPITER_PLUGIN_DIR}" ] ; then
    PLUGIN_DIR="${THEME_JUPITER_PLUGIN_DIR}"
else
    PLUGIN_DIR="${THEME_PAPER_LANTERN_PLUGIN_DIR}"
fi

/bin/cp -f "${PLUGIN_DIR}/lib/${HOOK_SCRIPT}" "${CPANEL_3RD_PARTY_BIN}/"

/bin/chmod 755 "${CPANEL_3RD_PARTY_BIN}/${HOOK_SCRIPT}"

/usr/local/cpanel/bin/manage_hooks \
    add script "${CPANEL_3RD_PARTY_BIN}/${HOOK_SCRIPT}" \
    --manual 1 \
    --category Whostmgr \
    --event AutoSSL::installssl \
    --stage pre

# Add EC cert auto-renew cron job

CRON_SCRIPT='ls-renew-ec-cert'
DAILY_CRON_DIR='/etc/cron.daily'

/bin/cp -f "${PLUGIN_DIR}/lib/cron/daily/${CRON_SCRIPT}" "${DAILY_CRON_DIR}/"
/bin/chmod 700 "${DAILY_CRON_DIR}/${CRON_SCRIPT}"