<?php

return [
    /*
     * Enable or disable captchas
     */
    'enabled' => env('RECAPTCHA_ENABLED', false),

    /*
     * API endpoint for recaptcha checks. You should not edit this.
     */
    'domain' => env('RECAPTCHA_DOMAIN', 'https://www.google.com/recaptcha/api/siteverify'),

    /*
     * Use a custom secret key. There is no default; set it via RECAPTCHA_SECRET_KEY
     * in the environment, otherwise reCAPTCHA verification will fail closed.
     */
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    /*
     * Use a custom website key. There is no default; set it via RECAPTCHA_WEBSITE_KEY.
     */
    'website_key' => env('RECAPTCHA_WEBSITE_KEY'),

    /*
     * Domain verification is enabled by default and compares the domain used when solving the captcha
     * as public keys can't have domain verification on google's side enabled (obviously).
     */
    'verify_domain' => true,
];
