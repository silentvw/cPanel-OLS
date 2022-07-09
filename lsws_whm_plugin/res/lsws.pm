
package Cpanel::API::lsws;

use strict;
use warnings 'all';
use utf8;

# Cpanel Dependencies
use Cpanel         ();
use Cpanel::API    ();
use Cpanel::Locale ();
use Cpanel::Logger ();

use Cpanel::AdminBin::Call();
use Data::Dumper;
use IPC::Run;
use JSON;

# Globals
my $logger;
my $locale;

sub getDocrootData
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $username = _getUsername();

    my $cmd =  'grep -hro --exclude="cache" --exclude="main" '
            . '--exclude="*.cache" "documentroot.*\|serveralias.*\|servername.*" '
            . '/var/cpanel/userdata/' . $username;

    my $ret = `$cmd`;

    $result->data(
        {
            docrootData => $ret
        }
    );
}

sub getScanDirs
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $docroot = $args->get_length_required('docroot');
    my $depth = $args->get_length_required('depth');

    my @cmd = (
        "find",
        "-L",
        $docroot,
        "-maxdepth",
        $depth,
        "-name",
        "wp-admin",
        "-print"
    );

    my $output = '';

    IPC::Run::run \@cmd, \undef, \$output;

    chomp($output);

    $result->data(
        {
            scanData => $output
        }
    );
}

sub retrieveLscwpTranslation
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $locale = $args->get_length_required('locale');
    my $pluginVer = $args->get_length_required('pluginVer');

    # Strip invalid chars from input
    $locale =~ s/[^A-Za-z_]//g;
    $pluginVer =~ s/[^0-9.]//g;

    my $ret = Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin',
      'RETRIEVE_LSCWP_TRANSLATION', $locale, $pluginVer);

    $result->data(
        {
            result => $ret
        }
    );
}

sub getDomainSslData
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $domain = $args->get_length_required('domain');
    my $allowEcCertGen = $args->get_length_required('allowEcCertGen');
    my $username = _getUsername();

    my %ret = Cpanel::AdminBin::Call::call(
        'Lsws',
        'lswsAdminBin',
        'GET_DOMAIN_SSL_DATA',
        $username,
        $domain,
        $allowEcCertGen
    );

    my $retVar = $ret{'retVar'};
    my $retCert = $ret{'retCert'};
    my $retKey = $ret{'retKey'};

    $result->data(
        {
            'retVar'  => $retVar,
            'retCert' => $retCert,
            'retKey'  => $retKey,
        }
    );
}

sub removeLscwpTranslationZip
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args) = @_;

    my $locale = $args->get_length_required('locale');
    my $pluginVer = $args->get_length_required('pluginVer');

    # Strip invalid chars from input
    $locale =~ s/[^A-Za-z_]//g;
    $pluginVer =~ s/[^0-9.]//g;

    my $ret = Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin',
      'REMOVE_LSCWP_TRANSLATION_ZIP', $locale, $pluginVer);
}

sub removeNewLscwpFlagFile
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $path = $args->get_length_required('path');

    my $ret = Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin',
            'REMOVE_NEW_LSCWP_FLAG_FILE', $path);

    $result->data(
        {
            result => $ret
        }
    );
}

sub execIssueCmd
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $username = _getUsername();

    my $cmd = $args->get_length_required('cmd');

    my %ret =
        Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin', 'EXEC_ISSUE_CMD',
            $username, $cmd);

    my $retVar = $ret{'retVar'};
    my $output = $ret{'output'};

    $result->data(
        {
            retVar => $retVar,
            output => $output
        }
    );
}

sub generateEcCert
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $domain = $args->get_length_required('domain');

    my $username = _getUsername();

    my %ret = Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin', 'GENERATE_EC_CERT', $username, $domain);

    my $retVar = $ret{'retVar'};
    my $output = $ret{'output'};
    my $sslVh = $ret{'sslVh'};
    my $ecCert = $ret{'ecCert'};
    my $ecCertFingerprint = $ret{'ecCertFingerprint'};

    $result->data(
        {
            retVar            => $retVar,
            output            => $output,
            sslVh             => $sslVh,
            ecCert            => $ecCert,
            ecCertFingerprint => $ecCertFingerprint

        }
    )
}

sub removeEcCert
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $domain = $args->get_length_required('domain');

    my $username = _getUsername();

    my %ret = Cpanel::AdminBin::Call::call('Lsws', 'lswsAdminBin', 'REMOVE_EC_CERT', $username, $domain);

    my $retVar = $ret{'retVar'};
    my $output = $ret{'output'};
    my $sslVh = $ret{'sslVh'};
    my $ecCert = $ret{'ecCert'};
    my $ecCertFingerprint = $ret{'ecCertFingerprint'};

    $result->data(
        {
            retVar            => $retVar,
            output            => $output,
            sslVh             => $sslVh,
            ecCert            => $ecCert,
            ecCertFingerprint => $ecCertFingerprint
        }
    )
}

sub getUpdatedEcCertList
{
    #Prevent potential action-at-a-distance bugs.
    #(cf. documentation for CPAN's Try::Tiny module)
    local $@;

    my ($args, $result) = @_;

    my $user = _getUsername();

    my $ecCertListDataRef = Cpanel::AdminBin::Call::call(
        'Lsws',
        'lswsAdminBin',
        'GET_UPDATED_EC_LIST',
        $user
    );

    my $ecCertListDataJson = encode_json $ecCertListDataRef;

    $result->data(
        {
            'ecCertListDataJson'  => $ecCertListDataJson
        }
    );
}

sub _getUsername
{
    my $username = getpwuid($<);

    return $username;
}

1;
