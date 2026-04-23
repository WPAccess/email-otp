<?php
/**
 * Plugin Name:       Email OTP Login
 * Plugin URI:        https://example.com/email-otp-login
 * Description:       Secure passwordless login for WordPress using a 6-digit one-time code sent to the user's registered email address.
 * Version:           1.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            WPAccess
 * Author URI:        https://wpaccess.in
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-email-otp-login
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_EMAIL_OTP_LOGIN_VERSION', '1.1.0' );
define( 'WP_EMAIL_OTP_LOGIN_PLUGIN_FILE', __FILE__ );
define( 'WP_EMAIL_OTP_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_EMAIL_OTP_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_EMAIL_OTP_LOGIN_PLUGIN_DIR . 'includes/class-email-otp-login.php';

register_activation_hook( __FILE__, array( 'WPA_Email_OTP_Login', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPA_Email_OTP_Login', 'deactivate' ) );

add_action( 'plugins_loaded', 'wp_email_otp_login_init' );
function wp_email_otp_login_init() {
    load_plugin_textdomain( 'wp-email-otp-login', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    $plugin = new WPA_Email_OTP_Login();
    $plugin->run();
}
