#!/bin/sh

HOOK_POINT="${1}"
STATUS_FILE="/tmp/build.status"
CMD_DIR=$(dirname "${0}")
LSADDON_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/lsws"
LSWS_HOME_DEF="${LSADDON_DIR}/LSWS_HOME.config"

# detect if litespeed installed
if [ ! -f "${LSWS_HOME_DEF}" ] ; then
    echo "   LiteSpeed is not installed, skip hook operation"
    exit 1
else
    LSWS_HOME=$(cat "${LSWS_HOME_DEF}")
    LSWS_HOME=$(expr "${LSWS_HOME}" : "LSWS_HOME=\(.*\)")
fi

if ! . "${CMD_DIR}/lsws_func" 2>/dev/null; then

    if ! . "${CMD_DIR}/lsws_func"; then
        echo "[ERROR] Can not include 'lsws_func'."
        exit 1
    fi
fi

init_var

switch_to_apache()
{
    "${CMD_DIR}/lsws_cmd.sh" "${LSWS_HOME}" "SWITCH_TO_APACHE"
}

switch_to_litespeed()
{
    "${CMD_DIR}/lsws_cmd.sh" "${LSWS_HOME}" "SWITCH_TO_LSWS"
}

LSEAHook_before_httpd_restart_tests()
{
    if [ -f "${STATUS_FILE}" ] ; then
        STATUS=$(cat "${STATUS_FILE}")

        if [ "x${STATUS}" = "xswitch" ] ; then
            echo "Switch to Apache"
            switch_to_apache
        fi
    fi
}

LSEAHook_after_httpd_restart_tests()
{
    if [ -f "${STATUS_FILE}" ] ; then
        STATUS=$(cat "${STATUS_FILE}")

        if [ "x${STATUS}" = "xswitch" ] ; then
            echo "Switch to LiteSpeed"
            switch_to_litespeed
        fi

        if [ "x${STATUS}" = "xoffset" ] ; then
            echo "Bring up LiteSpeed on port offset"
            ${LSWS_CTLCMD} restart 2>&1
        fi
    fi
}

LSEAHook_before_apache_make()
{
    # detect if Apache is running
    detect_ap_pid

    if [ "${APPID}" -eq 0 ] ; then # Apache not running
        echo "switch" > "${STATUS_FILE}"
        else
        # Apache is running
        # check if LiteSpeed is running
        detect_lsws_pid

        if [ "${LSPID}" -ne 0 ] ; then # LS running port offset
            echo "offset" > "${STATUS_FILE}"
            echo "LiteSpeed is running on port offset, need to switch to Apache first"
            switch_to_apache
        else
            echo "no" > "${STATUS_FILE}"
        fi
    fi
}

echo " -- LiteSpeed WHM hooks begin: ${HOOK_POINT} ${2}"

if [ "${HOOK_POINT}" = "before_httpd_restart_tests" ] ; then
    LSEAHook_before_httpd_restart_tests "${2}"

elif [ "${HOOK_POINT}" = "after_httpd_restart_tests" ] ; then
    LSEAHook_after_httpd_restart_tests "${2}"

elif [ "${HOOK_POINT}" = "before_apache_make" ] ; then
    LSEAHook_before_apache_make
    
elif [ "${HOOK_POINT}" = "REFRESH" ] ; then
    EasyApacheHookRefresh
    
else
    echo "Not supported hook point ${HOOK_POINT}, abort"
fi

echo " -- LiteSpeed WHM hooks end: ${HOOK_POINT}"

