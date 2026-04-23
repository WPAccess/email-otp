=== Email OTP Login ===
Contributors: wpaccess
Tags: otp, login, passwordless, email, two-factor
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Secure your WordPress login with fast, passwordless email OTP authentication.

Say goodbye to weak passwords and hello to a simpler, safer login experience.

== Description ==

== 🔐 Why Email OTP Login? ==

Traditional passwords are vulnerable to leaks, brute force attacks, and poor user habits. Email OTP Login replaces passwords with secure, time-limited one-time codes sent directly to the user’s email.

✔ No passwords to remember
✔ No password reset frustration
✔ Strong protection against brute-force attacks

== ⚡ Features ==

🔑 Passwordless Login
Allow users to log in using a one-time passcode sent to their email.

🛡️ Secure by Design
* OTPs are hashed using WordPress password hashing
* Expiry system (1–60 minutes configurable)
* Nonce-protected requests
* Full input sanitization & output escaping

🚫 Built-in Abuse Protection
* IP-based rate limiting
* Honeypot bot protection
* Prevents OTP spam and brute force attempts

⚙️ Easy Configuration
* Enable/disable OTP login
* Customize expiry time
* Works with default WordPress login

📧 Native Email Integration
Uses WordPress `wp_mail()` — no external services required.

== 🚀 Perfect For ==

* Membership websites
* Client portals
* Agencies & B2B platforms
* Anyone who wants a secure, passwordless login system

== 🧠 How It Works ==

1. User enters their email
2. Receives a one-time passcode
3. Enters OTP and logs in securely

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/`
2. Activate the plugin
3. Configure settings from the admin panel

== Frequently Asked Questions ==

= Is this secure? =

Yes, OTPs are hashed using WordPress password hashing and expire automatically. Rate limiting is implemented to prevent abuse.

= Can administrators use OTP login? =

No. For safety, administrator accounts must use the normal password form.

= Are OTP codes stored in plaintext? =

No. Codes are hashed with `wp_hash_password()` and only the hash is kept.

= Does this plugin send data to any external service? =

No. All emails are sent via `wp_mail()` on your own server. No tracking, no remote API calls.

= Can I customise the email? =

Yes — the subject line and full HTML template are editable from the settings screen with placeholders like `{otp}`, `{user_first_name}`, `{site_name}`.

== Changelog ==

= 1.1.0 =
* Converted to standalone plugin (no parent-module dependency).
* OTP codes now stored hashed instead of plaintext.
* Removed hardcoded branding / Reply-To address.
* Added proper plugin header, activation hook, and uninstall cleanup.
* Tightened nonce, sanitization and escaping throughout.
* Admin settings page now manages rate limit and resend limit.

= 1.0.1 =
* Initial internal release.

== Upgrade Notice ==

= 1.1.0 =
Security + standalone rewrite. Existing settings are preserved; any in-flight (unhashed) OTPs will be invalidated — users simply request a new code.
