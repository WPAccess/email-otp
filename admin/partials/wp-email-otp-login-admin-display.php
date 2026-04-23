<?php
/**
 * Admin settings page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'wp_email_otp_login_options', array() );
if ( ! is_array( $options ) ) {
    $options = array();
}

$otp_expiry_time = isset( $options['otp_expiry_time'] ) ? (int) $options['otp_expiry_time'] : 15;
$email_template  = isset( $options['email_template'] ) ? $options['email_template'] : WPA_Email_OTP_Login::default_email_template();
$email_subject   = isset( $options['email_subject'] ) ? $options['email_subject'] : '';
$rate_limit      = isset( $options['rate_limit'] ) ? (int) $options['rate_limit'] : 10;
$resend_limit    = isset( $options['resend_limit'] ) ? (int) $options['resend_limit'] : 3;
$enabled         = ! empty( $options['enabled'] );
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'wp_email_otp_login_options' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="eol_enabled"><?php esc_html_e( 'Enable OTP Login', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="eol_enabled" name="wp_email_otp_login_options[enabled]" value="1" <?php checked( $enabled ); ?> />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="otp_expiry_time"><?php esc_html_e( 'OTP Expiry Time (minutes)', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <input type="number" id="otp_expiry_time" name="wp_email_otp_login_options[otp_expiry_time]"
                           value="<?php echo esc_attr( $otp_expiry_time ); ?>" min="1" max="60" />
                    <p class="description">
                        <?php esc_html_e( 'How long each code remains valid (1–60 minutes).', 'wp-email-otp-login' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rate_limit"><?php esc_html_e( 'Requests per hour (per IP)', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <input type="number" id="rate_limit" name="wp_email_otp_login_options[rate_limit]"
                           value="<?php echo esc_attr( $rate_limit ); ?>" min="1" max="100" />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="resend_limit"><?php esc_html_e( 'Max resend attempts', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <input type="number" id="resend_limit" name="wp_email_otp_login_options[resend_limit]"
                           value="<?php echo esc_attr( $resend_limit ); ?>" min="1" max="10" />
                    <p class="description"><?php esc_html_e( 'After this many sends, the user must wait 30 minutes.', 'wp-email-otp-login' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="email_subject"><?php esc_html_e( 'Email Subject', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <input type="text" id="email_subject" name="wp_email_otp_login_options[email_subject]"
                           value="<?php echo esc_attr( $email_subject ); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="email_template"><?php esc_html_e( 'Email Template', 'wp-email-otp-login' ); ?></label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        $email_template,
                        'email_template',
                        array(
                            'textarea_name'   => 'wp_email_otp_login_options[email_template]',
                            'textarea_rows'   => 15,
                            'media_buttons'   => false,
                            'teeny'           => false,
                        )
                    );
                    ?>
                    <p class="description">
                        <?php esc_html_e( 'Available placeholders:', 'wp-email-otp-login' ); ?>
                    </p>
                    <ul style="list-style:disc;padding-left:20px;">
                        <li><code>{site_name}</code></li>
                        <li><code>{site_url}</code></li>
                        <li><code>{user_first_name}</code></li>
                        <li><code>{user_email}</code></li>
                        <li><code>{otp}</code></li>
                        <li><code>{expiry}</code></li>
                        <li><code>{current_year}</code></li>
                    </ul>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
