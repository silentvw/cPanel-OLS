# OpenLiteSpeed for cPanel

![](https://raw.githubusercontent.com/thomaswilbur/cPanel-OpenLitespeed/main/lsws_whm_plugin/static/icons/Logo_centered.svg)

### What is this?

This replaces the Apache web server with Openlitespeed. I have created a custom wrapper script which reads the cPanel domains, php info, and ssl certificates and generates a config file for openlitespeed.

### Features

- Replaces Apache with Openlitespeed
- Potentially upgradable to newer versions of openlitespeed
- Custom Litespeed Interface based on the Enterprise version of LiteSpeed
- Completely Free
- Easy and fast installation
- Admin Panel of Openlitespeed can modify configuration (NOT ABLE TO MODIFY VHOSTS/LISTENERS AS THESE GET MODIFIED PERIODICALLY)
- Vhost Templates
- Listener Templates
- SSL Support
- Script detects PHP Version and automatically finds cPanel PHP executable or Cloudlinux alt-php executable

### How do I install?

**RECOMMENDED: Please create a backup before installing, currently no way to remove or revert back to apache once installed. Working on a uninstall utility**

1. Clone this repo
2. Ensure Enterprise LSWS is not installed and cPanel Plugin for Enterprise LSWS is removed.
3. Create a server snapshot/backup ( RECOMMENDED )
4. Run install.sh 

### Custom OpenLitespeed Version

As of creating this script, the current version of OpenLiteSpeed was 1.7.16. This can be updated by modifying the variable OSLSWSVER in the install.sh file. Once modified you can run the install.sh file to upgrade OpenLiteSpeed.
