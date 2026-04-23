<?php
/**
 * Admin-specific functionality for Email OTP Login.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA_Email_OTP_Login_Admin {

    const OPTION_KEY = 'wp_email_otp_login_options';

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting(
            self::OPTION_KEY,
            self::OPTION_KEY,
            array(
                'sanitize_callback' => array( $this, 'validate_settings' ),
                'default'           => array(),
            )
        );
    }

    public function validate_settings( $input ) {
        if ( ! is_array( $input ) ) {
            $input = array();
        }

        $output = array();

        $expiry = isset( $input['otp_expiry_time'] ) ? absint( $input['otp_expiry_time'] ) : 15;
        if ( $expiry < 1 ) {
            $expiry = 15;
        } elseif ( $expiry > 60 ) {
            $expiry = 60;
        }
        $output['otp_expiry_time'] = $expiry;

        $output['email_template'] = isset( $input['email_template'] )
            ? wp_kses_post( $input['email_template'] )
            : WPA_Email_OTP_Login::default_email_template();

        if ( '' === trim( wp_strip_all_tags( $output['email_template'] ) ) ) {
            $output['email_template'] = WPA_Email_OTP_Login::default_email_template();
        }

        $output['email_subject'] = isset( $input['email_subject'] )
            ? sanitize_text_field( $input['email_subject'] )
            : sprintf(
                /* translators: %s: site name */
                __( 'Your sign-in code for %s', 'wp-email-otp-login' ),
                get_bloginfo( 'name' )
            );

        $output['rate_limit']   = isset( $input['rate_limit'] ) ? max( 1, min( 100, absint( $input['rate_limit'] ) ) ) : 10;
        $output['resend_limit'] = isset( $input['resend_limit'] ) ? max( 1, min( 10, absint( $input['resend_limit'] ) ) ) : 3;
        $output['enabled']      = ! empty( $input['enabled'] ) ? 1 : 0;

        return $output;
    }

    public function add_plugin_admin_menu() {
        add_options_page(
            __( 'Email OTP Login Settings', 'wp-email-otp-login' ),
            __( 'Email OTP Login', 'wp-email-otp-login' ),
            'manage_options',
            'wp-email-otp-login',
            array( $this, 'display_plugin_admin_page' )
        );
    }

    public function add_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=wp-email-otp-login' ) ) . '">' . esc_html__( 'Settings', 'wp-email-otp-login' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function display_plugin_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        include_once WP_EMAIL_OTP_LOGIN_PLUGIN_DIR . 'admin/partials/wp-email-otp-login-admin-display.php';
    }
}
