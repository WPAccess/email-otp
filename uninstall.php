<?php
/**
 * Uninstall cleanup for Email OTP Login.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'wp_email_otp_login_options' );

global $wpdb;

$meta_keys = array(
    'otp_code',
    'otp_code_hash',
    'otp_expires',
    'otp_resend_attempts',
    'otp_last_resend_time',
);

foreach ( $meta_keys as $meta_key ) {
    $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta_key ) );
}
