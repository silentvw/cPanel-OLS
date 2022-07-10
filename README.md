# Openlitespeed for cPanel

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

### How do I install?

1. Clone this repo
2. Create a server snapshot/backup ( RECOMMENDED )
3. Run install.sh 
