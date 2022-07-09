<?php

/********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 2.1
 *********************************************/

/**
 *
 * @since 2.1
 */
class CertAction
{

    /**
     * @since 2.1
     * @var string
     */
    const BOOTSTRAP_FILE = __DIR__ . '/cert_action_entry';

    /**
     * Data stored in this directory is intended to persist after plugin
     * removal.
     *
     * @since 2.1
     * @var string
     */
    const EXTERNAL_DATA_DIR = '/usr/local/cpanel/3rdparty/ls_webcache_mgr';

    /**
     * @since 2.1
     * @var string
     */
    const LE_ACCT_KEY = 'account_key.pem';

    /**
     * @since 2.1
     * @var int
     */
    const EXIT_SUCC = 0;

    /**
     * @since 2.1
     * @var int
     */
    const EXIT_ERROR = 1;

    /**
     * CAA record for domain prevents issuing a Let's Encrypt certificate.
     *
     * @since 2.1
     * @var int
     */
    const VALIDATION_FAILURE_CAA = 1;

    /**
     * Failed pre acme challenge for domain.
     *
     * @since 2.1
     * @var int
     */
    const VALIDATION_FAILURE_CHALLENGE = 2;

    /**
     * @since 2.1
     * @var string
     */
    const CMD_GENERATE_SIGNED_EC_CERT = 'geneccert';

    /**
     * @since 2.1
     * @var string
     */
    const CMD_RENEW_EC_CERT = 'renewcert';

    /**
     * @since 2.1
     * @var string
     */
    const CMD_RENEW_ALL_EC_CERTS = 'renewall';

    /**
     * @since 2.1
     * @var null|ACMECert
     */
    private $acmeCertObj = null;

    /**
     * @since 2.1
     * @var string
     */
    private $cmd;

    /**
     * @since 2.1
     * @var string[]
     */
    private $params = array();

    /**
     *
     * @since 2.1
     *
     * @param string[]  $input
     * @throws Exception  Thrown indirectly.
     */
    private function __construct( $input )
    {
        $this->init($input);
    }

    /**
     *
     * @since 2.1
     *
     * @param string[]  $input
     * @throws Exception  Thrown indirectly.
     */
    private function init( $input )
    {
        $this->parseCommands($input);
    }


    /**
     *
     * @since 2.1
     *
     * @param string[]  $input
     * @throws Exception  Thrown directly and indirectly.
     */
    private function parseCommands( $input )
    {

        $cmd = array_shift($input);

        switch ($cmd) {

            case self::CMD_GENERATE_SIGNED_EC_CERT:
            case self::CMD_RENEW_EC_CERT:
                $this->handleEcCertOperationInput($cmd, $input);
                break;

            default:
                throw new Exception(
                    'Invalid Command Provided.',
                    self::EXIT_ERROR
                );
        }

        if ( !empty($input) ) {
            throw new Exception(
                'Invalid Command Parameters Provided.',
                self::EXIT_ERROR
            );
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param string    $cmd
     * @param string[]  $input
     * @throws Exception
     */
    private function handleEcCertOperationInput($cmd, &$input )
    {
        switch ( $cmd ) {

            case self::CMD_GENERATE_SIGNED_EC_CERT:

                if ( ($key = array_search('-user', $input)) === false ) {
                    throw new Exception(
                        'Invalid Command, missing required \'-user\' '
                            . 'parameter.',
                        self::EXIT_ERROR
                    );
                }

                if ( empty($input[$key + 1])
                        || ($user = trim($input[$key + 1])) == '' ) {

                    throw new Exception(
                        'Invalid Command, missing \'-user\' value.',
                        self::EXIT_ERROR
                    );
                }

                $userdataDir = "/var/cpanel/userdata/{$user}";

                if ( !file_exists($userdataDir) ) {
                    throw new Exception(
                        'Invalid value for parameter \'-user\'.',
                        self::EXIT_ERROR
                    );
                }

                $this->params['user'] = $user;

                unset($input[$key], $input[$key + 1]);

                if ( ($key = array_search('-domain', $input)) === false ) {
                    throw new Exception(
                        'Invalid Command, missing required \'-domain\' '
                            . 'parameter.',
                        self::EXIT_ERROR
                    );
                }

                if ( empty($input[$key + 1])
                    || ($domain = trim($input[$key + 1])) == '' ) {

                    throw new Exception(
                        'Invalid Command, missing \'-domain\' value.',
                        self::EXIT_ERROR
                    );
                }

                preg_match(
                    '/(?:http:\/\/|https:\/\/)*(?:www.)*(.*)/',
                    $domain,
                    $matches
                );

                $domain = $matches[1];

                if ( !file_exists("{$userdataDir}/{$domain}") ) {
                    throw new Exception(
                        'Invalid value for parameter \'-domain\'.',
                        self::EXIT_ERROR
                    );
                }

                $this->params['domain'] = $domain;

                unset($input[$key], $input[$key + 1]);

                $this->cmd = $cmd;

                break;

            case self::CMD_RENEW_EC_CERT:

                if (($allKey = array_search('--all', $input)) !== false) {
                    unset($input[$allKey]);

                    $this->cmd = self::CMD_RENEW_ALL_EC_CERTS;
                }
                elseif ( ($userKey = array_search('-user', $input)) !== false
                        && ($domainKey = array_search('-domain', $input)) !== false ) {

                    if (empty($input[$userKey + 1])
                            || ($user = trim($input[$userKey + 1])) == '') {

                        throw new Exception(
                            'Invalid Command, missing \'-user\' value.',
                            self::EXIT_ERROR
                        );
                    }

                    $userdataDir = "/var/cpanel/userdata/{$user}";

                    if ( !file_exists($userdataDir) ) {
                        throw new Exception(
                            'Invalid value for parameter \'-user\'.',
                            self::EXIT_ERROR
                        );
                    }

                    $this->params['user'] = $user;

                    unset($input[$userKey], $input[$userKey + 1]);

                    if (empty($input[$domainKey + 1])
                            || ($domain = trim($input[$domainKey + 1])) == '') {

                        throw new Exception(
                            'Invalid Command, missing \'-domain\' value.',
                            self::EXIT_ERROR
                        );
                    }

                    preg_match(
                        '/(?:http:\/\/|https:\/\/)*(?:www.)*(.*)/',
                        $domain,
                        $matches
                    );

                    $domain = $matches[1];

                    if ( !file_exists("{$userdataDir}/{$domain}") ) {
                        throw new Exception(
                            'Invalid value for parameter \'-domain\'.',
                            self::EXIT_ERROR
                        );
                    }

                    $this->params['domain'] = $domain;

                    unset($input[$domainKey], $input[$domainKey + 1]);

                    $this->cmd = $cmd;

                    break;
                }
                else {
                    throw new Exception(
                        'Invalid Command, missing required parameters '
                            . '{--all | -user <user> -domain <domain>}.',
                        self::EXIT_ERROR
                    );
                }

                break;
        }
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception  Thrown indirectly.
     */
    private function initACMECertObj()
    {
        /**
         * Requires: PHP v7.1+, OpenSSL extension,
         *           [allow_url_fopen=1 | cURL extension].
         */

        $ac = new ACMECert();

        $accountKeyFile = self::EXTERNAL_DATA_DIR . '/' . self::LE_ACCT_KEY;

        $registerNewAccount = false;

        if ( !file_exists($accountKeyFile) ) {

            if (!file_exists(self::EXTERNAL_DATA_DIR)) {
                self::createExtDataDir();
            }
            else {
                chmod(self::EXTERNAL_DATA_DIR, 0755);
            }

            try{
                $key = $ac->generateEcKey('P-384');
            }
            catch ( Exception $e ) {
                throw new Exception(
                    'Error encountered while generating new Let\'s Encrypt '
                        . 'account key.',
                    self::EXIT_ERROR
                );
            }

            file_put_contents($accountKeyFile, $key);
            chmod($accountKeyFile, 0600);

            $registerNewAccount = true;
        }

        try {
            $ac->loadAccountKey("file://{$accountKeyFile}");
        }
        catch ( Exception $e ) {
            throw new Exception(
                'Error encountered while loading Let\'s Encrypt account key.',
                self::EXIT_ERROR
            );
        }

        if ($registerNewAccount) {
            try {
                $ac->register(true, 'admin@quic.cloud');
            }
            catch( ACME_Exception $e ) {
                throw new Exception(
                    $e->getMessage(),
                    self::EXIT_ERROR
                );
            }
            catch ( Exception $e ) {
                throw new Exception(
                    'Error encountered while registering Let\'s Encrpt '
                        . 'account.',
                    self::EXIT_ERROR
                );
            }
        }

        $this->acmeCertObj = $ac;
    }

    /**
     *
     * @since 2.1
     *
     * @return ACMECert
     * @throws Exception  Thrown indirectly.
     */
    private function getACMECertObj()
    {
        if ( $this->acmeCertObj == null ) {
            $this->initACMECertObj();
        }

        return $this->acmeCertObj;
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception
     */
    private static function createExtDataDir()
    {
        if ( !mkdir(self::EXTERNAL_DATA_DIR, 0755) ) {
            throw new Exception(
                'Could not create external data directory '
                    . self::EXTERNAL_DATA_DIR . '.',
                self::EXIT_ERROR
            );
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $url
     * @param bool    $headerOnly
     * @return string
     */
    private static function get_url_contents( $url, $headerOnly = false )
    {
        if ( ini_get('allow_url_fopen') ) {
            /**
             * silence warning when OpenSSL missing while getting LSCWP ver
             * file.
             */
            $url_content = @file_get_contents($url);

            if ( $url_content !== false ) {

                if ( $headerOnly ) {
                    return implode("\n", $http_response_header);
                }

                return $url_content;
            }
        }

        if ( function_exists('curl_version') ) {
            $ch = curl_init();

            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => $headerOnly,
                    CURLOPT_NOBODY => $headerOnly,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                )
            );

            $url_content = curl_exec($ch);
            curl_close($ch);

            if ( $url_content !== false ) {
                return $url_content;
            }
        }

        $cmd = 'curl';

        if ( $headerOnly ) {
            $cmd .= ' -s -I';
        }

        exec("{$cmd} {$url}", $output, $ret);

        if ( $ret === 0 ) {
            $url_content = implode("\n", $output);
            return $url_content;
        }

        return '';
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $domain
     * @return string[]
     */
    private static function getSslDomainAliases( $domain )
    {
        $aliases = array();

        $fileContent = file_get_contents('/etc/apache2/conf/httpd.conf');

        $escapedDomain = preg_quote($domain);


        $matches = preg_match(
            "!:443>\s+ServerName\s+{$escapedDomain}\s+ServerAlias\s+(.*)!",
            $fileContent,
            $m
        );

        if ( $matches ) {
            $aliases = explode(" ", trim($m[1]));
        }

        return $aliases;
    }

    /**
     *
     * Requires: PHP v7.1.2+ to check CAA records with dns_get_record().
     *
     * @since 2.1
     *
     * @param string    $docroot
     * @param string[]  $domainNames
     * @return string[][]
     * @throws Exception
     */
    private static function doDomainPreValidation( $docroot, $domainNames )
    {
        $ret = array( 'valid' => array(), 'failed' => array() );

        foreach ( $domainNames as $index => $domainName ) {
            $dnsCaaRecords = dns_get_record($domainName, DNS_CAA);

            /**
             * Empty case will inherit root domain CAA record (or lack thereof),
             * so if root domain passes we can assume these do as well.
             */

            if ( empty($dnsCaaRecords) ) {
                continue;
            }

            $found = false;

            foreach($dnsCaaRecords as $caaRecord) {

                if ( $caaRecord['tag'] == 'issue'
                        && $caaRecord['value'] == 'letsencrypt.org') {

                    $found = true;
                    break;
                }
            }

            if ( !$found ) {
                $ret['failed'][$domainName] = self::VALIDATION_FAILURE_CAA;
                unset($domainNames[$index]);
                continue;
            }
        }

        if ( ($pid = getmypid()) === false ) {
            throw new Exception(
                'Failed getting PID during domain pre-validation',
                self::EXIT_ERROR
            );
        }

        $checkFile = "ls_pre_validate{$pid}";
        $wellKnownDir = "{$docroot}/.well-known";
        $acmeChallengeDir = "{$wellKnownDir}/acme-challenge";

        if ( !file_exists($acmeChallengeDir) ) {

            if ( ($owner = fileowner($wellKnownDir)) === false ) {
                throw new Exception(
                    'Unable to get owner for .well-known/ directory',
                    self::EXIT_ERROR
                );
            }

            if ( ($group = filegroup($wellKnownDir)) === false ) {
                throw new Exception(
                    'Unable to get group for .well-known/ directory',
                    self::EXIT_ERROR
                );
            }

            mkdir($acmeChallengeDir, 0755);
            chown($acmeChallengeDir, $owner);
            chgrp($acmeChallengeDir, $group);
        }

        $checkFilePath = "{$acmeChallengeDir}/{$checkFile}";
        $num = rand();

        file_put_contents($checkFilePath, $num);
        chmod($checkFilePath, 0644);

        foreach ( $domainNames as $index => $domainName ) {
            $content = self::get_url_contents(
                "http://{$domainName}/.well-known/acme-challenge/{$checkFile}"
            );

            if ( $content != (string)$num ) {
                unlink($checkFilePath);
                $ret['failed'][$domainName] =
                    self::VALIDATION_FAILURE_CHALLENGE;
                unset($domainNames[$index]);
                continue;
            }
        }

        unlink($checkFilePath);

        $ret['valid'] = $domainNames;

        return $ret;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $combinedEcCertFile
     * @return string
     */
    private function getEcCertFingerprint($combinedEcCertFile)
    {
        $fileContent = file_get_contents($combinedEcCertFile);

        $foundEcCertFingerprint = preg_match(
            '/(-+BEGIN CERTIFICATE-+[\s\S]*?-+END CERTIFICATE-+)/',
            $fileContent,
            $m
        );

        if ( $foundEcCertFingerprint ) {
            return $m[1];
        }

        return '';
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $user
     * @param string  $domain
     * @param bool    $renewal
     * @throws Exception       Thrown directly and indirectly.
     */
    private function generateSignedEcCert( $user, $domain, $renewal = false )
    {
        $sslVhostDir = '/var/cpanel/ssl/apache_tls';

        if ( !file_exists("{$sslVhostDir}/{$domain}")
                || file_exists("{$sslVhostDir}/.pending_delete/{$domain}") ) {

            throw new Exception(
                'No SSL Vhost set for this domain or related SSL Vhost is '
                    . 'pending deletion',
                self::EXIT_ERROR
            );
        }

        $siteDataFile = "/var/cpanel/userdata/{$user}/{$domain}";

        if ( !file_exists($siteDataFile) ) {
            throw new Exception(
                'Could not find expected site data file.',
                self::EXIT_ERROR
            );
        }

        $siteData = file_get_contents($siteDataFile);

        preg_match('/documentroot:(.*)/', $siteData, $matches);

        if ( !isset($matches[1]) ) {
            throw new Exception(
                'Could not find documentroot for given domain.',
                self::EXIT_ERROR
            );
        }

        $docroot = trim($matches[1]);

        $domainNames = self::getSslDomainAliases($domain);
        array_unshift($domainNames, $domain);

        $preValidationResults =
            self::doDomainPreValidation($docroot, $domainNames);

        $validDomainNames = $preValidationResults['valid'];

        $combinedEcCertFile = "{$sslVhostDir}/{$domain}/combined.ecc";

        if ( !$renewal && file_exists($combinedEcCertFile) ) {
            $ecCertFingerprint =
                $this->getEcCertFingerprint($combinedEcCertFile);

            if ( $ecCertFingerprint != '' ) {
                $certInfo = openssl_x509_parse($ecCertFingerprint);

                $rawEcCertAltNames = array_map(
                    'trim',
                    explode(',', $certInfo['extensions']['subjectAltName'])
                );

                $ecCertAltNames = preg_replace(
                    '/DNS:(.+)/',
                    '$1',
                    $rawEcCertAltNames
                );

                if ( empty(array_diff($validDomainNames, $ecCertAltNames)) ) {
                    throw new Exception(
                        'Qualifying EC cert detected for this domain. EC cert '
                            . 'generation aborted.',
                        self::EXIT_ERROR
                    );
                }
            }
        }

        $ac = $this->getACMECertObj();

        $domain_config = array();

        foreach ( $validDomainNames as $domainName ) {
            $domain_config[$domainName] = array(
                'challenge' => 'http-01',
                'docroot' => $docroot
            );
        }

        $handler =
            function ($opts) {
                $fn = $opts['config']['docroot'] . $opts['key'];
                @mkdir(dirname($fn), 0777, true);
                file_put_contents($fn, $opts['value']);

                return
                    function ($opts) {
                        unlink($opts['config']['docroot'] . $opts['key']);
                    };
            };

        try {
            $privateKeyPem = $ac->generateEcKey('P-384');
        }
        catch ( Exception $e ) {
            throw new Exception(
                'Error encountered while generating new certificate key.',
                self::EXIT_ERROR
            );
        }

        try {
            $fullchain = $ac->getCertificateChain(
                $privateKeyPem,
                $domain_config,
                $handler
            );
        }
        catch ( ACME_Exception $e ) {
            throw new Exception(
                $e->getMessage(),
                self::EXIT_ERROR
            );
        }
        catch ( Exception $e ) {
            throw new Exception(
                'Encountered an error while getting certificate chain.',
                self::EXIT_ERROR
            );
        }

        touch($combinedEcCertFile);
        chmod($combinedEcCertFile, 0640);

        $combinedContent = preg_replace(
            "!\n\n!m",
            "\n",
            "{$privateKeyPem}\n{$fullchain}"
        );

        if ($combinedContent === null ) {
            throw new Exception(
                'Error occurred when creating combined EC cert.',
                self::EXIT_ERROR
            );
        }

        file_put_contents($combinedEcCertFile, $combinedContent);

        $coveredDomains = implode(', ', $validDomainNames);
        $excludedDomains = implode(
            ', ',
            array_keys($preValidationResults['failed'])
        );

        throw new Exception(
            "Successfully generated combined EC cert.\n\n"
                . "Covered Domains: {$coveredDomains}\n\n"
                . "Excluded Domains: {$excludedDomains}",
            self::EXIT_SUCC
        );
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $ecCertPath
     * @param int     $days         Time in days when a certificate is
     *                              considered to be expiring soon.
     * @return bool
     * @throws Exception  Thrown directly and indirectly.
     */
    private function certIsExpiringSoon($ecCertPath, $days = 15 )
    {
        $ecCertFingerprint = $this->getEcCertFingerprint($ecCertPath);

        if ( $ecCertFingerprint == '' ) {
            throw new Exception(
                "Could not get ecCertFingerprint for cert file {$ecCertPath}",
                self::EXIT_ERROR
            );
        }

        $ac = $this->getACMECertObj();

        try {
            $daysRemaining = $ac->getRemainingDays($ecCertFingerprint);
        }
        catch ( Exception $e ) {
            throw new Exception(
                'Error encountered while trying to get remaining time before '
                    . 'expiry for certificate.',
                self::EXIT_ERROR
            );
        }

        if ( $daysRemaining <= $days ) {
            return true;
        }

        return false;
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception
     */
    private function renewAllEcCerts()
    {
        exec(
            'find /var/cpanel/ssl/apache_tls/ -type f -name combined.ecc '
                . '-not -path \'*/\.*\'',
            $ecCertPaths
        );

        foreach ( $ecCertPaths as $ecCertPath ) {
            preg_match('!apache_tls/(.+)/combined.ecc!', $ecCertPath, $m);

            $domain = $m[1];

            $output1 = array();
            exec(
                '/usr/local/cpanel/bin/whmapi1 getdomainowner '
                . 'domain=' . escapeshellarg($domain)
                . ' --output=json',
                $output1,
                $ret
            );

            $data = json_decode($output1[0], true);

            if ( !isset($data['data']['user'])
                    || $data['data']['user'] === null ) {

                echo "\n{$domain}: Failed to get domain owner.";
                continue;
            }

            $user = $data['data']['user'];

            try {
                $this->renewEcCert($user, $domain);
            }
            catch ( Exception $e ) {
                echo "\n{$domain}: " . $e->getMessage();
            }
        }

        echo "\n";

        throw new Exception(
            'Completed renew all command',
            self::EXIT_SUCC
        );
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $user
     * @param string  $domain
     * @throws Exception  Thrown directly and indirectly.
     */
    private function renewEcCert( $user, $domain )
    {
        $combinedEcCert = "/var/cpanel/ssl/apache_tls/{$domain}/combined.ecc";

        if ( !file_exists($combinedEcCert) ) {
            throw new Exception(
                'No combined EC cert found.',
                self::EXIT_ERROR
            );
        }

        if ( $this->certIsExpiringSoon($combinedEcCert) ) {
            $this->generateSignedEcCert($user, $domain, true);
        }
        else {
            throw new Exception(
                'Certificate expires in 15+ days. No need to renew.',
                self::EXIT_SUCC
            );
        }
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception
     */
    private function runEcCertCmd()
    {
        try {
            require_once __DIR__ . '/../lib/ACMECert/ACMECert.php';

            switch ($this->cmd) {

                case self::CMD_GENERATE_SIGNED_EC_CERT:
                    $user = $this->params['user'];
                    $domain = $this->params['domain'];

                    $this->generateSignedEcCert($user, $domain);
                    break;

                case self::CMD_RENEW_ALL_EC_CERTS:
                    $this->renewAllEcCerts();
                    break;

                case self::CMD_RENEW_EC_CERT:
                    $user = $this->params['user'];
                    $domain = $this->params['domain'];

                    $this->renewEcCert($user, $domain);
                    break;

                // no default case
            }
        }
        catch ( Exception $e ) {

            /**
             * Re-throw success.
             */
            if ( $e->getCode() == self::EXIT_SUCC ) {
                throw $e;
            }

            throw new Exception(
                "[ERROR] {$e->getMessage()}\n",
                self::EXIT_ERROR
            );
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param string    $caller
     * @param string[]  $input
     * @throws Exception  Thrown directly and indirectly.
     */
    public static function run( $caller, $input )
    {
        /**
         * Only run if called from bootstrap file.
         */
        if ( realpath($caller) != self::BOOTSTRAP_FILE ) {
            return;
        }

        if ( version_compare(phpversion(), '7.1.2', '<') ) {
            throw new Exception(
                'PHP version must be at least v7.1.2 to run this script.',
                self::EXIT_ERROR
            );
        }

        date_default_timezone_set('UTC');

        $certAction = new self($input);
        $certAction->runEcCertCmd();
    }

}
