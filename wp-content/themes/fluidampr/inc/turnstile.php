<?php
/**
 * Cloudflare Turnstile helpers for public forms.
 *
 * Site key and secret live in Appearance → Fluidampr setup, or in wp-config
 * as FLUIDAMPR_TURNSTILE_SITE_KEY / FLUIDAMPR_TURNSTILE_SECRET. Never commit
 * the secret.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option keys for Turnstile credentials.
 *
 * @return array<string, string>
 */
function fluidampr_turnstile_option_keys() {
	return array(
		'site_key' => 'fluidampr_turnstile_site_key',
		'secret'   => 'fluidampr_turnstile_secret',
	);
}

/**
 * Read a Turnstile setting. wp-config constants win for secrets.
 *
 * @param string $name site_key or secret.
 * @return string
 */
function fluidampr_turnstile_get( $name ) {
	$constants = array(
		'site_key' => 'FLUIDAMPR_TURNSTILE_SITE_KEY',
		'secret'   => 'FLUIDAMPR_TURNSTILE_SECRET',
	);

	if ( isset( $constants[ $name ] ) && defined( $constants[ $name ] ) ) {
		$value = constant( $constants[ $name ] );

		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	$keys = fluidampr_turnstile_option_keys();

	if ( ! isset( $keys[ $name ] ) ) {
		return '';
	}

	$saved = get_option( $keys[ $name ], '' );

	return is_scalar( $saved ) ? (string) $saved : '';
}

/**
 * Persist a Turnstile setting.
 *
 * @param string $name  site_key or secret.
 * @param string $value Value.
 * @return void
 */
function fluidampr_turnstile_set( $name, $value ) {
	$keys = fluidampr_turnstile_option_keys();

	if ( ! isset( $keys[ $name ] ) ) {
		return;
	}

	update_option( $keys[ $name ], (string) $value, false );
}

/**
 * Whether both public and secret keys are present.
 *
 * @return bool
 */
function fluidampr_turnstile_is_enabled() {
	return '' !== fluidampr_turnstile_get( 'site_key' )
		&& '' !== fluidampr_turnstile_get( 'secret' );
}

/**
 * Verify a Turnstile response token with Cloudflare.
 *
 * @param string $token    Token from the widget.
 * @param string $remote_ip Optional client IP.
 * @return true|WP_Error
 */
function fluidampr_turnstile_verify( $token, $remote_ip = '' ) {
	$token = trim( (string) $token );

	if ( '' === $token ) {
		return new WP_Error( 'fluidampr_turnstile', 'missing_token' );
	}

	$secret = fluidampr_turnstile_get( 'secret' );

	if ( '' === $secret ) {
		return new WP_Error( 'fluidampr_turnstile', 'not_configured' );
	}

	$body = array(
		'secret'   => $secret,
		'response' => $token,
	);

	if ( '' !== $remote_ip && 'unknown' !== $remote_ip ) {
		$body['remoteip'] = $remote_ip;
	}

	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 8,
			'headers' => array(
				'Accept' => 'application/json',
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || ! is_array( $data ) ) {
		return new WP_Error( 'fluidampr_turnstile', 'verify_unavailable' );
	}

	if ( ! empty( $data['success'] ) ) {
		return true;
	}

	return new WP_Error( 'fluidampr_turnstile', 'verify_failed' );
}

/**
 * Markup for an implicit Turnstile widget.
 *
 * @return string
 */
function fluidampr_turnstile_widget_html() {
	if ( ! fluidampr_turnstile_is_enabled() ) {
		return '';
	}

	ob_start();
	?>
	<div class="fluid-turnstile cf-turnstile" data-sitekey="<?php echo esc_attr( fluidampr_turnstile_get( 'site_key' ) ); ?>" data-theme="light" data-size="flexible"></div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Enqueue the Cloudflare api.js when a public form needs the widget.
 *
 * @return void
 */
function fluidampr_turnstile_enqueue_script() {
	if ( ! fluidampr_turnstile_is_enabled() || is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'fluidampr-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'fluidampr_turnstile_enqueue_script', 20 );
