<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 2.1
 * ******************************************* */

namespace LsUserPanel;

use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserUtil;

/**
 *
 * @since 2.1
 */
class QuicCloudApiUtil
{

    /**
     *
     * @since 2.1
     *
     * @param string  $apiKey
     * @param string  $siteUrl
     * @return string[]  Requested data.
     * @throws UserLSCMException  All error messages will be thrown as
     *                            exceptions.
     */
    public static function callInfo( $apiKey, $siteUrl )
    {
        $postData = array(
            'site_url' => $siteUrl,
            'domain_key' => $apiKey,
            'svc' => 'cdn',
            'fields' => '["dmlist"]'
        );

        $response = UserUtil::PostToUrl(
            "https://api.quic.cloud/d/info",
            $postData
        );

        $result = json_decode($response, true);

        if ($result == null || !isset($result['_res'])) {
            throw new UserLSCMException(
                sprintf(
                    _('%s API call failed.'),
                    'QUIC.cloud'
                )
            );
        }

        $res = $result['_res'];

        if ( $res != 'ok' ) {

            switch ( $res ) {
                case 'err':

                    if (isset($result['_msg'])) {

                        switch ($result['_msg']) {
                            case 'err_key':
                            case 'site_not_registered':

                                throw new UserLSCMException(
                                    sprintf(
                                        _(
                                            'Encountered issue when attempting '
                                                . 'to communicate with %s. '
                                                . 'Please visit %s in the '
                                                . 'WordPress Dashboard and '
                                                . 'register/refresh this '
                                                . 'site\'s domain key.'
                                        ),
                                        'QUIC.cloud',
                                        '"LiteSPeed Cache -> General"'
                                    )
                                );

                            case 'non_cdn_domain':

                                throw new UserLSCMException(
                                    sprintf(
                                        _(
                                            'Unable to get required '
                                                . 'information from %s for '
                                                . 'this domain. Please enable '
                                                . 'the %s CDN feature for this '
                                                . 'domain and try again.'
                                        ),
                                        'QUIC.cloud',
                                        'QUIC.cloud'
                                    )
                                );

                            //no default
                        }
                    }

                    throw new UserLSCMException(
                        sprintf(
                            _('%s API call encountered an error.'),
                            'QUIC.cloud'
                        )
                    );

                default:
                    throw new UserLSCMException(
                        sprintf(
                            _(
                            '%s API call returned unrecognized %s value.'
                            ),
                            'QUIC.cloud',
                            "'_res'"

                        )
                    );
            }
        }

        if ( !isset($result['data']['dmlist']) ) {
            throw new UserLSCMException(
                sprintf(
                    _('%s API call did not return expected data.'),
                    'QUIC.cloud'
                )
            );
        }

        return $result['data']['dmlist'];
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $apiKey
     * @param string  $domain
     * @param string  $siteUrl
     * @param string  $cert
     * @param string  $key
     * @return string  Success message
     * @throws UserLSCMException  All error messages will be thrown as
     *                            exceptions.
     */
    public static function uploadCert( $apiKey, $domain, $siteUrl, $cert, $key)
    {
        $postData = array(
            'site_url' => $siteUrl,
            'domain_key' => $apiKey,
            'ssl_cert' => $cert,
            'ssl_key' => $key
        );

        $response = UserUtil::PostToUrl(
            "https://api.quic.cloud/d/upload_cert/{$domain}",
            $postData
        );

        $result = json_decode($response, true);

        if ( $result == null || !isset($result['_res']) ) {
            throw new UserLSCMException(
                sprintf(
                    _('%s API call failed.'),
                    'QUIC.cloud'
                )
            );
        }

        switch ( $result['_res'] ) {

            case 'ok':

                if ( isset($result['_msg']) ) {
                    return "QC: {$result['_msg']}";
                }
                else {
                    return _('Successfully uploaded SSL Cert info.');
                }

            case 'err':

                if ( isset($result['_msg']) ) {
                    throw new UserLSCMException(
                        "QC: {$result['_msg']}"
                    );
                }
                else {
                    throw new UserLSCMException(
                        sprintf(
                            _('%s API call encountered an error.'),
                            'QUIC.cloud'
                        )
                    );
                }

                break;

            default:
                throw new UserLSCMException(
                    sprintf(
                        _('%s API call returned unrecognized %s value.'),
                        'QUIC.cloud',
                        "'_res'"
                    )
                );
        }
    }

}
