#!/bin/sh
eval 'if [ -x /usr/local/cpanel/3rdparty/bin/perl ]; then exec /usr/local/cpanel/3rdparty/bin/perl -x -- $0 ${1+"$@"}; else exec /usr/bin/perl -x -- $0 ${1+"$@"};fi'
if 0;
#!/usr/bin/perl

#WHMADDON:lsws:LiteSpeed Web Server:lsws_icon.png
####################################################
# Copyright 2013-2017 LiteSpeed Technologies
# https://www.litespeedtech.com
##################################################### 

use strict;
use lib '/usr/local/cpanel/';
use Whostmgr::ACLS();
Whostmgr::ACLS::init_acls();

package cgi::lsws;
use warnings;
use Cpanel::Template();

run() unless caller();

sub run {
    print "Content-type: text/html; charset=utf-8\n\n";

    if (!Whostmgr::ACLS::hasroot()) {
        print "You do not have access to the LiteSpeed Web Server Plugin.\n";
        exit;
    }

    Cpanel::Template::process_template(
        'whostmgr',
        {
            'template_file' => 'lsws/lsws.html.tt',
            'print'   => 1,
        }
    );
    exit();
}