#!/bin/sh

cd "$(dirname "${0}")"
# shellcheck disable=SC2034
LSWS_HOME="${1}"
CMD="${2}"

if [ "x${CMD}" = "x" ] ; then
    echo "[ERROR] Missing command."
    exit 1
fi

if ! . ./lsws_func 2>/dev/null; then

    if ! . ./lsws_func; then
        echo "[ERROR] Can not include 'lsws_func'."
        exit 1
    fi
fi

init_var

if [ "${CMD}" = "CHANGE_PORT_OFFSET" ] ; then
    change_port_offset "${3}"

elif [ "${CMD}" = "CHECK_LSWS_RUNNING" ] ; then
    detect_lsws_pid
    echo "${LSPID}"

elif [ "${CMD}" = "CHECK_AP_RUNNING" ] ; then
    detect_ap_pid
    echo "${APPID}"

elif [ "${CMD}" = "RESTART_LSWS" ] ; then
    ${LSWS_CTLCMD} restart 2>&1

elif [ "${CMD}" = "STOP_LSWS" ] ; then
    stop_lsws

elif [ "${CMD}" = "CHECK_LICENSE" ] ; then
    CheckLicense

#
# Update cp_switch_ws.sh when making changes that affect this command.
#
elif [ "${CMD}" = "SWITCH_TO_LSWS" ] ; then
    SwitchToLiteSpeed
    SetRunOnBoot

#
# Update cp_switch_ws.sh when making changes that affect this command.
#
elif [ "${CMD}" = "SWITCH_TO_APACHE" ] ; then
    SwitchToApache
    SetRunOnBoot

#
# 01/29/19: This command is called directly by the cPanel team. Do not change
#           the call interface.
#
elif [ "${CMD}" = "CHANGE_LICENSE" ] ; then
    SwitchLicense "${3}"

elif [ "${CMD}" = "TRANSFER_LICENSE" ] ; then
    TransferLicense

elif [ "${CMD}" = "VER_UP" ] ; then
    VersionUp "${3}"

elif [ "${CMD}" = "VER_SWITCH" ] ; then
    VersionSwitch "${3}"

elif [ "${CMD}" = "VER_DEL" ] ; then
    VersionDel "${3}"

elif [ "${CMD}" = "UNINSTALL" ] ; then
    UninstallLiteSpeed "${3}" "${4}"

else
    echo "Unknown CMD"
fi
