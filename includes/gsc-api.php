<?php
// File: includes/gsc-api.php
if ( ! defined('ABSPATH') ) exit;

/**
 * Fetch a single batch of Search Analytics rows for a given GSC property.
 *
 * @param string $site_url   GSC property identifier (e.g. "sc-domain:example.com" or full URL)
 * @param array  $date_range ['startDate'=>'YYYY-MM-DD','endDate'=>'YYYY-MM-DD']
 * @param int    $startRow   Row offset for pagination
 * @param int    $rowLimit   Maximum rows per request
 * @return array             Array of row arrays
 * @throws Exception         On HTTP or auth errors
 */
function scc_fetch_gsc_batch( $site_url, array $date_range, $startRow = 0, $rowLimit = 25000 ) {
    // Load service account JSON
    $cred_path = dirname( ABSPATH, 2 ) . '/private/credentials/axiom-ad-web-1ed1651b9fb4.json';
    if ( ! file_exists( $cred_path ) ) {
        throw new Exception( "GSC credentials not found at $cred_path" );
    }
    $creds = json_decode( file_get_contents( $cred_path ), true );

    // Build & sign JWT
    $now     = time();
    $header  = [ 'alg' => 'RS256', 'typ' => 'JWT' ];
    $payload = [
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ];
    $b64 = function( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    };
    $jwt = $b64( json_encode( $header ) ) . '.' . $b64( json_encode( $payload ) );
    openssl_sign( $jwt, $sig, $creds['private_key'], 'sha256' );
    $assertion = $jwt . '.' . $b64( $sig );

    // Exchange JWT for access token
    $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
        'body'    => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $assertion,
        ],
        'timeout' => 30,
    ] );
    if ( is_wp_error( $resp ) ) {
        throw new Exception( $resp->get_error_message() );
    }
    $token_data = json_decode( wp_remote_retrieve_body( $resp ), true );
    $token = $token_data['access_token'] ?? '';
    if ( ! $token ) {
        throw new Exception( 'Failed to retrieve GSC access token' );
    }

    // Prepare Search Analytics query
    $api_url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode( $site_url ) . '/searchAnalytics/query';
    $body = [
        'startDate'  => $date_range['startDate'],
        'endDate'    => $date_range['endDate'],
        'dimensions' => [ 'date', 'query', 'page', 'device' ],
        'rowLimit'   => $rowLimit,
        'startRow'   => $startRow,
    ];
    $res = wp_remote_post( $api_url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ],
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
    ] );
    if ( is_wp_error( $res ) ) {
        throw new Exception( "GSC query error for $site_url: " . $res->get_error_message() );
    }
    $data = json_decode( wp_remote_retrieve_body( $res ), true );

    $rows = [];
    if ( ! empty( $data['rows'] ) && is_array( $data['rows'] ) ) {
        foreach ( $data['rows'] as $r ) {
            $rows[] = [
                'keys'        => $r['keys'],
                'clicks'      => $r['clicks'],
                'impressions' => $r['impressions'],
                'ctr'         => $r['ctr'],
                'position'    => $r['position'],
            ];
        }
    }

    return $rows;
}

/**
 * Fetch Search Analytics rows in daily batches with pagination.
 *
 * @param string[] $site_urls   Array of GSC property identifiers
 * @param array    $date_range  ['startDate'=>'YYYY-MM-DD','endDate'=>'YYYY-MM-DD']
 * @param int      $rowLimit    Maximum rows per request
 * @return array                [site_url => [ row1, row2, … ], …]
 * @throws Exception
 */
function scc_fetch_gsc_data( array $site_urls, array $date_range, $rowLimit = 10000 ) {
    // Build one-day spans
    $batches = [];
    $cursor  = strtotime( $date_range['startDate'] );
    $end_ts  = strtotime( $date_range['endDate'] );
    while ( $cursor <= $end_ts ) {
        $day       = date( 'Y-m-d', $cursor );
        $batches[] = ['startDate'=>$day, 'endDate'=>$day];
        $cursor   += DAY_IN_SECONDS;
    }

    $results = [];
    foreach ( $site_urls as $url ) {
        $all_rows = [];
        foreach ( $batches as $range ) {
            $startRow = 0;
            do {
                // pull one page of that single-day batch
                $rows = scc_fetch_gsc_batch( $url, $range, $startRow, $rowLimit );
                $count = count( $rows );
                if ( $count ) {
                    $all_rows = array_merge( $all_rows, $rows );
                    $startRow += $count;
                }
            } while ( $count === $rowLimit );  // more pages for that day
        }
        $results[ $url ] = $all_rows;
    }
    return $results;
}
