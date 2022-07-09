#!/bin/sh
eval 'if [ -x /usr/local/cpanel/3rdparty/bin/perl ]; then exec /usr/local/cpanel/3rdparty/bin/perl -x -- $0 ${1+"$@"}; else exec /usr/bin/perl -x -- $0 ${1+"$@"};fi'
if 0;
#!/usr/bin/perl

#WHMADDON:lsws:LiteSpeed Web Server:lsws_icon.png

use lib '/usr/local/cpanel/';
use Whostmgr::ACLS();
Whostmgr::ACLS::init_acls();


print "Content-Type: text/html\n\n";

if (!Whostmgr::ACLS::hasroot()) {
    print "You do not have access to LiteSpeed Web Server Plug in.\n";
    exit();
}

print "<meta http-equiv=\"refresh\" content=\"0;url=lsws/index.php\"/>" ;
