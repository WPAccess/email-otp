<?php
/**
 * Main orchestrator for Email OTP Login.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA_Email_OTP_Login {

    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WP_EMAIL_OTP_LOGIN_PLUGIN_DIR . 'admin/class-email-otp-login-admin.php';
        require_once WP_EMAIL_OTP_LOGIN_PLUGIN_DIR . 'public/class-email-otp-login-public.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new WPA_Email_OTP_Login_Admin();

        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( WP_EMAIL_OTP_LOGIN_PLUGIN_FILE ),
            array( $plugin_admin, 'add_action_links' )
        );
    }

    private function define_public_hooks() {
        $plugin_public = new WPA_Email_OTP_Login_Public();
        add_action( 'login_form', array( $plugin_public, 'add_otp_login_option' ) );
    }

    public function run() {
        // Plugin is running.
    }

    /**
     * Install default options on activation.
     */
    public static function activate() {
        $defaults = array(
            'otp_expiry_time' => 15,
            'email_subject'   => sprintf(
                /* translators: %s: site name */
                __( 'Your sign-in code for %s', 'wp-email-otp-login' ),
                get_bloginfo( 'name' )
            ),
            'email_template'  => self::default_email_template(),
            'enabled'         => 1,
            'rate_limit'      => 10,
            'resend_limit'    => 3,
        );

        $existing = get_option( 'wp_email_otp_login_options' );
        if ( ! is_array( $existing ) ) {
            add_option( 'wp_email_otp_login_options', $defaults );
        } else {
            update_option( 'wp_email_otp_login_options', array_merge( $defaults, $existing ) );
        }
    }

    public static function deactivate() {
        // Nothing to tear down; data is removed on uninstall.
    }

    public static function default_email_template() {
        return '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Your login code</title>
</head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:20px;">
<div style="max-width:600px;margin:0 auto;background:#ffffff;padding:20px;border-radius:5px;">
<p>Hello {user_first_name},</p>
<p>You requested to sign in to your account at {site_name}. Use the code below to continue:</p>
<div style="background:#f7f7f7;border:1px solid #ddd;border-radius:4px;font-family:monospace;font-size:24px;font-weight:bold;letter-spacing:2px;margin:20px 0;padding:15px;text-align:center;">{otp}</div>
<p>This code expires in {expiry} minutes.</p>
<p>If you did not request this code, please ignore this email.</p>
<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
<p style="font-size:12px;color:#666;">&copy; {current_year} {site_name}. This is an automated message — do not reply.</p>
</div>
</body>
</html>';
    }
}
