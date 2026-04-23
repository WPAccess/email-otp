<?php
/**
 * Public-facing functionality for Email OTP Login.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA_Email_OTP_Login_Public {

    const NONCE_ACTION         = 'wp_email_otp_nonce';
    const REFRESH_NONCE_ACTION = 'email_otp_refresh_nonce';
    const OPTION_KEY           = 'wp_email_otp_login_options';

    public function __construct() {
        add_action( 'wp_ajax_nopriv_generate_otp',               array( $this, 'generate_otp' ) );
        add_action( 'wp_ajax_nopriv_verify_otp',                 array( $this, 'verify_otp' ) );
        add_action( 'wp_ajax_nopriv_email_otp_refresh_nonce',    array( $this, 'refresh_otp_nonce' ) );

        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_scripts' ) );
        add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_shortcode( 'email_otp_login',    array( $this, 'render_otp_button' ) );
        add_action( 'wp_footer',             array( $this, 'render_modal' ) );
        add_action( 'login_footer',          array( $this, 'render_modal' ) );
    }

    public function enqueue_scripts() {
        if ( is_admin() || is_user_logged_in() ) {
            return;
        }

        wp_enqueue_style(
            'wp-email-otp-login-public',
            WP_EMAIL_OTP_LOGIN_PLUGIN_URL . 'public/css/wp-email-otp-login-public.css',
            array(),
            WP_EMAIL_OTP_LOGIN_VERSION
        );

        wp_enqueue_script(
            'wp-email-otp-login-public',
            WP_EMAIL_OTP_LOGIN_PLUGIN_URL . 'public/js/wp-email-otp-login-public.js',
            array( 'jquery' ),
            WP_EMAIL_OTP_LOGIN_VERSION,
            true
        );

        wp_localize_script(
            'wp-email-otp-login-public',
            'wp_email_otp',
            array(
                'ajax_url'             => admin_url( 'admin-ajax.php' ),
                'nonce'                => wp_create_nonce( self::NONCE_ACTION ),
                'max_resend_attempts'  => 3,
                'refresh_nonce_action' => self::REFRESH_NONCE_ACTION,
            )
        );
    }

    public function render_otp_button( $atts ) {
        $atts = shortcode_atts(
            array(
                'text'  => __( 'Sign In Without Password', 'wp-email-otp-login' ),
                'class' => 'button button-primary',
            ),
            $atts,
            'email_otp_login'
        );

        wp_enqueue_script( 'wp-email-otp-login-public' );
        wp_enqueue_style( 'wp-email-otp-login-public' );

        return sprintf(
            '<button type="button" class="%s" id="trigger-otp-modal">%s</button>',
            esc_attr( $atts['class'] ),
            esc_html( $atts['text'] )
        );
    }

    public function render_modal() {
        if ( is_user_logged_in() ) {
            return;
        }
        ?>
        <div id="otp-modal" class="otp-modal" style="display:none;" aria-hidden="true">
            <div class="otp-modal-content" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
                <span class="otp-modal-close" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close', 'wp-email-otp-login' ); ?>">&times;</span>

                <div id="otp-email-form">
                    <h2 id="otp-modal-title"><?php esc_html_e( 'Sign In with Code', 'wp-email-otp-login' ); ?></h2>
                    <p><?php esc_html_e( 'Enter your registered email address to receive a code.', 'wp-email-otp-login' ); ?></p>
                    <div class="form-group">
                        <label for="user_email"><?php esc_html_e( 'Email Address', 'wp-email-otp-login' ); ?></label>
                        <input type="email" name="user_email" id="user_email" class="input" required />
                        <input type="text" name="website" id="website" style="display:none !important" tabindex="-1" autocomplete="off" aria-hidden="true" />
                    </div>
                    <div class="form-group">
                        <button type="button" id="generate-otp" class="button button-primary">
                            <?php esc_html_e( 'Send me a Code', 'wp-email-otp-login' ); ?>
                        </button>
                    </div>
                </div>

                <div id="otp-verification-form" style="display:none;">
                    <h2><?php esc_html_e( 'Enter Code', 'wp-email-otp-login' ); ?></h2>
                    <p><?php esc_html_e( 'Please enter the 6-digit code we emailed you.', 'wp-email-otp-login' ); ?></p>
                    <div class="form-group">
                        <label for="otp_code"><?php esc_html_e( '6-digit Code', 'wp-email-otp-login' ); ?></label>
                        <input type="text" name="otp_code" id="otp_code" class="input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required />
                        <input type="text" name="website_verify" id="website_verify" style="display:none !important" tabindex="-1" autocomplete="off" aria-hidden="true" />
                    </div>
                    <div class="form-group">
                        <button type="button" id="verify-otp" class="button button-primary">
                            <?php esc_html_e( 'Submit', 'wp-email-otp-login' ); ?>
                        </button>
                        <button type="button" id="resend-otp" class="button button-secondary">
                            <?php esc_html_e( 'Resend Code', 'wp-email-otp-login' ); ?>
                        </button>
                    </div>
                    <p class="resend-info" style="display:none;">
                        <?php
                        printf(
                            /* translators: %s: remaining attempts HTML element */
                            esc_html__( 'Resend attempts remaining: %s', 'wp-email-otp-login' ),
                            '<span id="resend-attempts">3</span>'
                        );
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: generate and send OTP.
     */
    public function generate_otp() {
        try {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE_ACTION ) ) {
                throw new Exception( __( 'Security check failed. Please refresh the page and try again.', 'wp-email-otp-login' ) );
            }

            $this->check_honeypot( 'website' );
            $this->check_rate_limit();

            if ( empty( $_POST['email'] ) ) {
                throw new Exception( __( 'Email address is required.', 'wp-email-otp-login' ) );
            }

            $email = sanitize_email( wp_unslash( $_POST['email'] ) );
            if ( ! is_email( $email ) ) {
                throw new Exception( __( 'Invalid email address.', 'wp-email-otp-login' ) );
            }

            $user = get_user_by( 'email', $email );
            if ( ! $user ) {
                // Generic response — avoid user enumeration.
                throw new Exception( __( 'If that email matches an account, a code will be sent.', 'wp-email-otp-login' ) );
            }

            if ( in_array( 'administrator', (array) $user->roles, true ) ) {
                throw new Exception( __( 'Code login is not available for administrators. Please use the regular login form.', 'wp-email-otp-login' ) );
            }

            $options       = $this->get_options();
            $resend_limit  = (int) $options['resend_limit'];

            $resend_attempts  = (int) get_user_meta( $user->ID, 'otp_resend_attempts', true );
            $last_resend_time = (int) get_user_meta( $user->ID, 'otp_last_resend_time', true );

            if ( $resend_attempts >= $resend_limit ) {
                $time_passed = time() - $last_resend_time;
                if ( $time_passed < 1800 ) {
                    $wait_time = (int) ceil( ( 1800 - $time_passed ) / 60 );
                    throw new Exception(
                        sprintf(
                            /* translators: %d: wait time in minutes */
                            __( 'Maximum resend attempts reached. Please try again after %d minutes.', 'wp-email-otp-login' ),
                            $wait_time
                        )
                    );
                }
                $resend_attempts = 0;
            }

            $otp         = sprintf( '%06d', wp_rand( 0, 999999 ) );
            $expiry_mins = max( 1, min( 60, (int) $options['otp_expiry_time'] ) );
            $expiry_time = $expiry_mins * MINUTE_IN_SECONDS;

            update_user_meta( $user->ID, 'otp_code_hash', wp_hash_password( $otp ) );
            update_user_meta( $user->ID, 'otp_expires', time() + $expiry_time );
            update_user_meta( $user->ID, 'otp_resend_attempts', $resend_attempts + 1 );
            update_user_meta( $user->ID, 'otp_last_resend_time', time() );
            delete_user_meta( $user->ID, 'otp_code' );

            $first_name = get_user_meta( $user->ID, 'first_name', true );
            if ( empty( $first_name ) ) {
                $first_name = $user->display_name;
            }

            $site_name = get_bloginfo( 'name' );
            $replacements = array(
                '{site_name}'       => $site_name,
                '{site_url}'        => home_url(),
                '{user_first_name}' => $first_name,
                '{user_email}'      => $email,
                '{otp}'             => $otp,
                '{expiry}'          => $expiry_mins,
                '{current_year}'    => gmdate( 'Y' ),
            );

            $message = str_replace( array_keys( $replacements ), array_values( $replacements ), $options['email_template'] );
            $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $options['email_subject'] );

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
            );

            $mail_sent = wp_mail( $email, $subject, $message, $headers );
            if ( ! $mail_sent ) {
                throw new Exception( __( 'Failed to send email. Please try again later.', 'wp-email-otp-login' ) );
            }

            $remaining = max( 0, $resend_limit - ( $resend_attempts + 1 ) );
            $wait_time = ( 0 === $remaining ) ? 1800 : 0;

            wp_send_json_success(
                array(
                    'message'             => __( 'We\'ve sent a code to your email. Enter it below to continue.', 'wp-email-otp-login' ),
                    'attempts_remaining'  => $remaining,
                    'wait_time'           => $wait_time,
                )
            );
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * AJAX: verify OTP and log in user.
     */
    public function verify_otp() {
        try {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE_ACTION ) ) {
                throw new Exception( __( 'Security check failed. Please refresh the page and try again.', 'wp-email-otp-login' ) );
            }

            $this->check_honeypot( 'website_verify' );

            $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
            $otp   = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';

            if ( ! is_email( $email ) ) {
                throw new Exception( __( 'Invalid email address.', 'wp-email-otp-login' ) );
            }

            if ( ! preg_match( '/^\d{6}$/', $otp ) ) {
                throw new Exception( __( 'Invalid code format.', 'wp-email-otp-login' ) );
            }

            $user = get_user_by( 'email', $email );
            if ( ! $user ) {
                throw new Exception( __( 'Invalid or expired code.', 'wp-email-otp-login' ) );
            }

            if ( in_array( 'administrator', (array) $user->roles, true ) ) {
                throw new Exception( __( 'Code login is not available for administrators. Please use the regular login form.', 'wp-email-otp-login' ) );
            }

            $stored_hash = get_user_meta( $user->ID, 'otp_code_hash', true );
            $otp_expires = (int) get_user_meta( $user->ID, 'otp_expires', true );

            if ( ! $stored_hash || ! $otp_expires ) {
                throw new Exception( __( 'No code found. Please request a new one.', 'wp-email-otp-login' ) );
            }

            if ( time() > $otp_expires ) {
                delete_user_meta( $user->ID, 'otp_code_hash' );
                delete_user_meta( $user->ID, 'otp_expires' );
                throw new Exception( __( 'Code expired. Please request a new one.', 'wp-email-otp-login' ) );
            }

            if ( ! wp_check_password( $otp, $stored_hash ) ) {
                throw new Exception( __( 'Invalid code.', 'wp-email-otp-login' ) );
            }

            delete_user_meta( $user->ID, 'otp_code_hash' );
            delete_user_meta( $user->ID, 'otp_expires' );
            delete_user_meta( $user->ID, 'otp_resend_attempts' );
            delete_user_meta( $user->ID, 'otp_last_resend_time' );

            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );

            wp_send_json_success(
                array(
                    'message'  => __( 'Login successful.', 'wp-email-otp-login' ),
                    'redirect' => apply_filters( 'wp_email_otp_login_redirect', home_url(), $user ),
                )
            );
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * AJAX: refresh nonce (rate-limited, unauthenticated).
     */
    public function refresh_otp_nonce() {
        $client_ip = $this->get_client_ip();
        $key       = 'otp_nonce_refresh_' . md5( $client_ip );
        $attempts  = (int) get_transient( $key );

        if ( $attempts >= 5 ) {
            wp_send_json_error( __( 'Too many refresh attempts. Please reload the page.', 'wp-email-otp-login' ) );
        }

        set_transient( $key, $attempts + 1, 5 * MINUTE_IN_SECONDS );

        wp_send_json_success(
            array(
                'nonce' => wp_create_nonce( self::NONCE_ACTION ),
            )
        );
    }

    public function add_otp_login_option() {
        if ( is_user_logged_in() ) {
            return;
        }

        echo '<div class="otp-login-option">';
        echo '<p class="message">' . esc_html__( 'Or sign in with a one-time code', 'wp-email-otp-login' ) . '</p>';
        echo do_shortcode( '[email_otp_login]' );
        echo '</div>';
    }

    private function check_honeypot( $field ) {
        if ( ! empty( $_POST[ $field ] ) ) {
            throw new Exception( __( 'Invalid request.', 'wp-email-otp-login' ) );
        }
    }

    private function check_rate_limit() {
        $options   = $this->get_options();
        $max_hour  = max( 1, (int) $options['rate_limit'] );
        $client_ip = $this->get_client_ip();

        $last_key = 'otp_last_submission_' . md5( $client_ip );
        $last     = get_transient( $last_key );
        if ( false !== $last && ( time() - (int) $last ) < 3 ) {
            throw new Exception( __( 'Please wait a moment before trying again.', 'wp-email-otp-login' ) );
        }
        set_transient( $last_key, time(), MINUTE_IN_SECONDS );

        $ip_key   = 'otp_ip_attempts_' . md5( $client_ip );
        $attempts = (int) get_transient( $ip_key );
        if ( $attempts >= $max_hour ) {
            throw new Exception( __( 'Too many attempts from this IP. Please try again later.', 'wp-email-otp-login' ) );
        }
        set_transient( $ip_key, $attempts + 1, HOUR_IN_SECONDS );
    }

    private function get_options() {
        $defaults = array(
            'otp_expiry_time' => 15,
            'email_subject'   => sprintf(
                /* translators: %s: site name */
                __( 'Your sign-in code for %s', 'wp-email-otp-login' ),
                get_bloginfo( 'name' )
            ),
            'email_template'  => WPA_Email_OTP_Login::default_email_template(),
            'rate_limit'      => 10,
            'resend_limit'    => 3,
        );

        $options = get_option( self::OPTION_KEY );
        if ( ! is_array( $options ) ) {
            return $defaults;
        }
        return array_merge( $defaults, $options );
    }

    private function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}
