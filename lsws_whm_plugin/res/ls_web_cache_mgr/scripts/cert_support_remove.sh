#!/bin/sh

# /********************************************
# LiteSpeed Web Cache Management Plugin for cPanel
#
# @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
# @copyright (c) 2020
# @since 2.1
# *********************************************/

# De-register and remove autossl event hook for EC cert auto-update

HOOK_FILE_PATH='/usr/local/cpanel/3rdparty/bin/LsWebCacheMgrAutosslHook.php'

if [ -f "$HOOK_FILE_PATH" ] ; then
    /usr/local/cpanel/bin/manage_hooks delete script "$HOOK_FILE_PATH" --manual 1 --category Whostmgr \
                                       --event AutoSSL::installssl --stage pre
                                       
    /bin/rm -f "$HOOK_FILE_PATH"
fi

# Remove EC cert auto-renew cron job

CRON_JOB_FILE='/etc/cron.daily/ls-renew-ec-cert'

if [ -f "$CRON_JOB_FILE" ] ; then
    /bin/rm -f "$CRON_JOB_FILE"
fi

# Remove all plugin generated EC certs

find /var/cpanel/ssl/apache_tls/ -type f -name combined.ecc -not -path '*/\.*' -exec /bin/rm -f "{}" \;