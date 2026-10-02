<?php
/**
 * Constant Contact newsletter signup for the site footer.
 *
 * Uses the V3 Contacts sign_up_form endpoint so an email field can create
 * or update a contact with explicit permission. OAuth tokens stay in the
 * database — never commit them.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option keys for Constant Contact credentials.
 *
 * @return array<string, string>
 */
function fluidampr_cc_option_keys() {
	return array(
		'api_key'        => 'fluidampr_cc_api_key',
		'client_secret'  => 'fluidampr_cc_client_secret',
		'access_token'   => 'fluidampr_cc_access_token',
		'refresh_token'  => 'fluidampr_cc_refresh_token',
		'token_expires'  => 'fluidampr_cc_token_expires',
		'list_id'          => 'fluidampr_cc_list_id',
		'list_id_customer' => 'fluidampr_cc_list_id_customer',
		'list_id_dealer'   => 'fluidampr_cc_list_id_dealer',
	);
}

/**
 * Read a Constant Contact setting. wp-config constants win for secrets.
 *
 * @param string $name api_key, client_secret, access_token, refresh_token, token_expires, list_id, list_id_customer, list_id_dealer.
 * @return string
 */
function fluidampr_cc_get( $name ) {
	$constants = array(
		'api_key'       => 'FLUIDAMPR_CC_API_KEY',
		'client_secret' => 'FLUIDAMPR_CC_CLIENT_SECRET',
	);

	if ( isset( $constants[ $name ] ) && defined( $constants[ $name ] ) ) {
		$value = constant( $constants[ $name ] );

		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	$keys = fluidampr_cc_option_keys();

	if ( ! isset( $keys[ $name ] ) ) {
		return '';
	}

	$saved = get_option( $keys[ $name ], '' );

	return is_scalar( $saved ) ? (string) $saved : '';
}

/**
 * Persist a Constant Contact setting.
 *
 * @param string $name  Setting name.
 * @param string $value Value.
 * @return void
 */
function fluidampr_cc_set( $name, $value ) {
	$keys = fluidampr_cc_option_keys();

	if ( ! isset( $keys[ $name ] ) ) {
		return;
	}

	update_option( $keys[ $name ], (string) $value, false );
}

/**
 * OAuth redirect URI that must be registered on the Constant Contact app.
 *
 * @return string
 */
function fluidampr_cc_redirect_uri() {
	return admin_url( 'admin.php?page=fluidampr-setup' );
}

/**
 * Whether the account is authorized and both signup lists are selected.
 *
 * @return bool
 */
function fluidampr_cc_is_ready() {
	return '' !== fluidampr_cc_get( 'access_token' )
		&& '' !== fluidampr_cc_get( 'refresh_token' )
		&& '' !== fluidampr_cc_list_id_for_audience( 'customer' )
		&& '' !== fluidampr_cc_list_id_for_audience( 'dealer' );
}

/**
 * Constant Contact list ID for a footer audience choice.
 *
 * @param string $audience customer or dealer.
 * @return string
 */
function fluidampr_cc_list_id_for_audience( $audience ) {
	if ( 'dealer' === $audience ) {
		return fluidampr_cc_get( 'list_id_dealer' );
	}

	$customer = fluidampr_cc_get( 'list_id_customer' );

	if ( '' !== $customer ) {
		return $customer;
	}

	return fluidampr_cc_get( 'list_id' );
}

/**
 * REST route for the footer form.
 *
 * @return void
 */
function fluidampr_cc_register_rest() {
	register_rest_route(
		'fluidampr/v1',
		'/newsletter',
		array(
			'methods'             => 'POST',
			'callback'            => 'fluidampr_cc_rest_subscribe',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email'            => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_email',
				),
				'audience'         => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
				'company'          => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'turnstile_token'  => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'fluidampr_cc_register_rest' );

/**
 * Subscribe an email address from the footer form.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function fluidampr_cc_rest_subscribe( WP_REST_Request $request ) {
	$honeypot = trim( (string) $request->get_param( 'company' ) );

	if ( '' !== $honeypot ) {
		fluidampr_cc_log( 'honeypot' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Thanks — you are signed up.', 'fluidampr' ),
			),
			200
		);
	}

	if ( fluidampr_cc_is_rate_limited() ) {
		fluidampr_cc_log( 'rate_limited' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Too many attempts. Try again later.', 'fluidampr' ),
			),
			429
		);
	}

	fluidampr_cc_hit_rate_limit();

	$email    = sanitize_email( (string) $request->get_param( 'email' ) );
	$audience = sanitize_key( (string) $request->get_param( 'audience' ) );

	if ( '' === $email || strlen( $email ) > 254 || ! is_email( $email ) ) {
		fluidampr_cc_log( 'invalid_email' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Enter a valid email address.', 'fluidampr' ),
			),
			400
		);
	}

	if ( ! in_array( $audience, array( 'customer', 'dealer' ), true ) ) {
		fluidampr_cc_log( 'invalid_audience' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Choose Customer or Dealer.', 'fluidampr' ),
			),
			400
		);
	}

	if ( function_exists( 'fluidampr_turnstile_is_enabled' ) && fluidampr_turnstile_is_enabled() ) {
		$token   = (string) $request->get_param( 'turnstile_token' );
		$verified = fluidampr_turnstile_verify( $token, fluidampr_cc_client_ip() );

		if ( is_wp_error( $verified ) ) {
			$reason = 'fluidampr_turnstile' === $verified->get_error_code()
				? (string) $verified->get_error_message()
				: 'verify_unavailable';
			fluidampr_cc_log( 'turnstile_' . $reason );

			$message = 'verify_unavailable' === $reason
				? __( 'Could not verify the security check. Try again in a moment.', 'fluidampr' )
				: __( 'Please complete the security check and try again.', 'fluidampr' );

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $message,
				),
				403
			);
		}
	}

	if ( ! fluidampr_cc_is_ready() ) {
		fluidampr_cc_log( 'not_ready' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Newsletter signup is temporarily unavailable.', 'fluidampr' ),
			),
			503
		);
	}

	$list_id = fluidampr_cc_list_id_for_audience( $audience );

	if ( '' === $list_id ) {
		fluidampr_cc_log( 'missing_list' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Newsletter signup is temporarily unavailable.', 'fluidampr' ),
			),
			503
		);
	}

	$result = fluidampr_cc_sign_up( $email, $list_id );

	if ( is_wp_error( $result ) ) {
		fluidampr_cc_log( 'signup_rejected' );

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Could not complete signup. Try again in a moment.', 'fluidampr' ),
			),
			502
		);
	}

	fluidampr_cc_log( 'signup_ok' );

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => __( 'Thanks — you are signed up for Fluidampr updates.', 'fluidampr' ),
		),
		200
	);
}

/**
 * Client IP for rate limiting and Turnstile (never logged raw).
 *
 * @return string
 */
function fluidampr_cc_client_ip() {
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return 'unknown';
}

/**
 * Transient key for the current IP’s signup attempts.
 *
 * @return string
 */
function fluidampr_cc_rate_limit_key() {
	return 'fluidampr_cc_rl_' . md5( fluidampr_cc_client_ip() );
}

/**
 * Whether this IP has exceeded 8 signup attempts in an hour.
 *
 * @return bool
 */
function fluidampr_cc_is_rate_limited() {
	return (int) get_transient( fluidampr_cc_rate_limit_key() ) >= 8;
}

/**
 * Count this request against the hourly IP cap.
 *
 * @return void
 */
function fluidampr_cc_hit_rate_limit() {
	$key  = fluidampr_cc_rate_limit_key();
	$hits = (int) get_transient( $key );
	set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
}

/**
 * Security/event log without email addresses, tokens, or secrets.
 *
 * Disable with: add_filter( 'fluidampr_cc_log_enabled', '__return_false' );
 *
 * @param string $event Event name.
 * @return void
 */
function fluidampr_cc_log( $event ) {
	if ( ! apply_filters( 'fluidampr_cc_log_enabled', true ) ) {
		return;
	}

	$event = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $event ) );

	if ( '' === $event ) {
		return;
	}

	error_log( 'fluidampr-newsletter: ' . $event ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * POST /v3/contacts/sign_up_form
 *
 * @param string $email   Email address.
 * @param string $list_id Constant Contact list ID.
 * @param bool   $retried Whether this is a retry after refreshing the token.
 * @return true|WP_Error
 */
function fluidampr_cc_sign_up( $email, $list_id, $retried = false ) {
	$token = fluidampr_cc_valid_access_token();

	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_post(
		'https://api.cc.email/v3/contacts/sign_up_form',
		array(
			'timeout' => 12,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'email_address'    => $email,
					'list_memberships' => array( $list_id ),
				)
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( $code >= 200 && $code < 300 ) {
		return true;
	}

	if ( 401 === $code && ! $retried ) {
		$refreshed = fluidampr_cc_refresh_access_token();

		if ( ! is_wp_error( $refreshed ) ) {
			return fluidampr_cc_sign_up( $email, $list_id, true );
		}
	}

	return new WP_Error( 'fluidampr_cc_signup', 'Constant Contact rejected the signup.' );
}

/**
 * Access token, refreshing when it is within five minutes of expiry.
 *
 * @return string|WP_Error
 */
function fluidampr_cc_valid_access_token() {
	$token   = fluidampr_cc_get( 'access_token' );
	$expires = (int) fluidampr_cc_get( 'token_expires' );

	if ( '' === $token ) {
		return new WP_Error( 'fluidampr_cc_auth', 'Not connected.' );
	}

	if ( $expires && $expires < ( time() + 300 ) ) {
		$refreshed = fluidampr_cc_refresh_access_token();

		if ( is_wp_error( $refreshed ) ) {
			return $refreshed;
		}

		return fluidampr_cc_get( 'access_token' );
	}

	return $token;
}

/**
 * Exchange the refresh token for a new access token.
 *
 * @return true|WP_Error
 */
function fluidampr_cc_refresh_access_token() {
	$refresh = fluidampr_cc_get( 'refresh_token' );
	$api_key = fluidampr_cc_get( 'api_key' );
	$secret  = fluidampr_cc_get( 'client_secret' );

	if ( '' === $refresh || '' === $api_key || '' === $secret ) {
		return new WP_Error( 'fluidampr_cc_auth', 'Missing OAuth credentials.' );
	}

	$response = wp_remote_post(
		'https://authz.constantcontact.com/oauth2/default/v1/token',
		array(
			'timeout' => 12,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $secret ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Accept'        => 'application/json',
			),
			'body'    => array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh,
			),
		)
	);

	return fluidampr_cc_store_token_response( $response );
}

/**
 * Save access/refresh tokens from an OAuth token endpoint response.
 *
 * @param array<string, mixed>|WP_Error $response wp_remote response.
 * @return true|WP_Error
 */
function fluidampr_cc_store_token_response( $response ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || ! is_array( $body ) || empty( $body['access_token'] ) ) {
		return new WP_Error( 'fluidampr_cc_auth', 'Could not store Constant Contact tokens.' );
	}

	fluidampr_cc_set( 'access_token', sanitize_text_field( (string) $body['access_token'] ) );

	if ( ! empty( $body['refresh_token'] ) ) {
		fluidampr_cc_set( 'refresh_token', sanitize_text_field( (string) $body['refresh_token'] ) );
	}

	$ttl = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : DAY_IN_SECONDS;
	fluidampr_cc_set( 'token_expires', (string) ( time() + max( 60, $ttl ) ) );

	return true;
}

/**
 * Fetch active email lists for the connected account.
 *
 * @return array<int, array<string, string>>|WP_Error
 */
function fluidampr_cc_get_lists() {
	$token = fluidampr_cc_valid_access_token();

	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'status' => 'active',
				'limit'  => 100,
			),
			'https://api.cc.email/v3/contact_lists'
		),
		array(
			'timeout' => 12,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'fluidampr_cc_lists', 'Could not load contact lists.' );
	}

	$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$lists   = ( is_array( $payload ) && isset( $payload['lists'] ) && is_array( $payload['lists'] ) ) ? $payload['lists'] : array();
	$out     = array();

	foreach ( $lists as $list ) {
		if ( ! is_array( $list ) || empty( $list['list_id'] ) ) {
			continue;
		}

		$out[] = array(
			'id'   => (string) $list['list_id'],
			'name' => isset( $list['name'] ) ? (string) $list['name'] : (string) $list['list_id'],
		);
	}

	return $out;
}

/**
 * Handle OAuth callback and settings POSTs on the setup screen.
 *
 * @return void
 */
function fluidampr_cc_handle_admin() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'fluidampr-setup' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( isset( $_GET['code'], $_GET['state'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		fluidampr_cc_handle_oauth_callback();
	}

	if ( isset( $_POST['fluidampr_cc_save'] ) && check_admin_referer( 'fluidampr_cc_settings' ) ) {
		if ( ! defined( 'FLUIDAMPR_CC_API_KEY' ) ) {
			fluidampr_cc_set( 'api_key', sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_api_key'] ?? '' ) ) );
		}

		if ( ! defined( 'FLUIDAMPR_CC_CLIENT_SECRET' ) ) {
			$secret = sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_client_secret'] ?? '' ) );

			if ( '' !== $secret ) {
				fluidampr_cc_set( 'client_secret', $secret );
			}
		}

		if ( isset( $_POST['fluidampr_cc_list_id_customer'] ) ) {
			$customer_list = sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_list_id_customer'] ) );
			fluidampr_cc_set( 'list_id_customer', $customer_list );
			fluidampr_cc_set( 'list_id', $customer_list );
		}

		if ( isset( $_POST['fluidampr_cc_list_id_dealer'] ) ) {
			fluidampr_cc_set( 'list_id_dealer', sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_list_id_dealer'] ) ) );
		}

		if ( function_exists( 'fluidampr_turnstile_set' ) ) {
			if ( ! defined( 'FLUIDAMPR_TURNSTILE_SITE_KEY' ) ) {
				fluidampr_turnstile_set( 'site_key', sanitize_text_field( wp_unslash( $_POST['fluidampr_turnstile_site_key'] ?? '' ) ) );
			}

			if ( ! defined( 'FLUIDAMPR_TURNSTILE_SECRET' ) ) {
				$turnstile_secret = sanitize_text_field( wp_unslash( $_POST['fluidampr_turnstile_secret'] ?? '' ) );

				if ( '' !== $turnstile_secret ) {
					fluidampr_turnstile_set( 'secret', $turnstile_secret );
				}
			}
		}

		wp_safe_redirect( add_query_arg( 'cc_saved', '1', fluidampr_cc_redirect_uri() ) );
		exit;
	}

	if ( isset( $_POST['fluidampr_cc_disconnect'] ) && check_admin_referer( 'fluidampr_cc_settings' ) ) {
		foreach ( array( 'access_token', 'refresh_token', 'token_expires', 'list_id', 'list_id_customer', 'list_id_dealer' ) as $key ) {
			fluidampr_cc_set( $key, '' );
		}

		wp_safe_redirect( add_query_arg( 'cc_disconnected', '1', fluidampr_cc_redirect_uri() ) );
		exit;
	}

	if ( isset( $_POST['fluidampr_cc_connect'] ) && check_admin_referer( 'fluidampr_cc_settings' ) ) {
		if ( ! defined( 'FLUIDAMPR_CC_API_KEY' ) ) {
			fluidampr_cc_set( 'api_key', sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_api_key'] ?? '' ) ) );
		}

		if ( ! defined( 'FLUIDAMPR_CC_CLIENT_SECRET' ) ) {
			$secret = sanitize_text_field( wp_unslash( $_POST['fluidampr_cc_client_secret'] ?? '' ) );

			if ( '' !== $secret ) {
				fluidampr_cc_set( 'client_secret', $secret );
			}
		}

		$api_key = fluidampr_cc_get( 'api_key' );

		if ( '' === $api_key || '' === fluidampr_cc_get( 'client_secret' ) ) {
			wp_safe_redirect( add_query_arg( 'cc_error', 'missing', fluidampr_cc_redirect_uri() ) );
			exit;
		}

		$state = wp_create_nonce( 'fluidampr_cc_oauth' );
		$url   = add_query_arg(
			array(
				'client_id'     => $api_key,
				'redirect_uri'  => fluidampr_cc_redirect_uri(),
				'response_type' => 'code',
				'scope'         => 'contact_data offline_access',
				'state'         => $state,
			),
			'https://authz.constantcontact.com/oauth2/default/v1/authorize'
		);

		wp_redirect( $url );
		exit;
	}
}
add_action( 'admin_init', 'fluidampr_cc_handle_admin' );

/**
 * Exchange the authorization code for tokens.
 *
 * @return void
 */
function fluidampr_cc_handle_oauth_callback() {
	$code  = sanitize_text_field( wp_unslash( $_GET['code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$state = sanitize_text_field( wp_unslash( $_GET['state'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! wp_verify_nonce( $state, 'fluidampr_cc_oauth' ) ) {
		wp_safe_redirect( add_query_arg( 'cc_error', 'state', fluidampr_cc_redirect_uri() ) );
		exit;
	}

	$api_key = fluidampr_cc_get( 'api_key' );
	$secret  = fluidampr_cc_get( 'client_secret' );

	$response = wp_remote_post(
		'https://authz.constantcontact.com/oauth2/default/v1/token',
		array(
			'timeout' => 12,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $secret ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Accept'        => 'application/json',
			),
			'body'    => array(
				'grant_type'   => 'authorization_code',
				'code'         => $code,
				'redirect_uri' => fluidampr_cc_redirect_uri(),
			),
		)
	);

	$stored = fluidampr_cc_store_token_response( $response );

	if ( is_wp_error( $stored ) ) {
		wp_safe_redirect( add_query_arg( 'cc_error', 'token', fluidampr_cc_redirect_uri() ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'cc_connected', '1', fluidampr_cc_redirect_uri() ) );
	exit;
}

/**
 * Settings UI on Appearance → Fluidampr setup.
 *
 * @return void
 */
function fluidampr_render_constant_contact_settings() {
	$connected = '' !== fluidampr_cc_get( 'access_token' );
	$lists     = $connected ? fluidampr_cc_get_lists() : array();
	$customer  = fluidampr_cc_list_id_for_audience( 'customer' );
	$dealer    = fluidampr_cc_list_id_for_audience( 'dealer' );

	if ( isset( $_GET['cc_connected'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Constant Contact is connected. Map Customer and Dealer to their lists, then save.', 'fluidampr' ) . '</p></div>';
	}

	if ( isset( $_GET['cc_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Constant Contact settings saved.', 'fluidampr' ) . '</p></div>';
	}

	if ( isset( $_GET['cc_disconnected'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Constant Contact was disconnected.', 'fluidampr' ) . '</p></div>';
	}

	if ( isset( $_GET['cc_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not complete Constant Contact authorization. Check the API key, secret, and redirect URI, then try again.', 'fluidampr' ) . '</p></div>';
	}

	echo '<hr />';
	echo '<h2>' . esc_html__( 'Constant Contact', 'fluidampr' ) . '</h2>';
	echo '<p>' . esc_html__( 'The footer Sign Up field adds contacts through the V3 API. Create a private app in the Constant Contact developer portal, paste the API key and secret, add the redirect URI below to the app, then connect the Fluidampr account. Choose which list Customer signups join and which list Dealer signups join.', 'fluidampr' ) . '</p>';
	echo '<p><code>' . esc_html( fluidampr_cc_redirect_uri() ) . '</code></p>';

	echo '<form method="post">';
	wp_nonce_field( 'fluidampr_cc_settings' );

	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row"><label for="fluidampr_cc_api_key">' . esc_html__( 'API key (client ID)', 'fluidampr' ) . '</label></th><td>';
	printf(
		'<input class="regular-text" type="text" id="fluidampr_cc_api_key" name="fluidampr_cc_api_key" value="%s" autocomplete="off" %s>',
		esc_attr( fluidampr_cc_get( 'api_key' ) ),
		defined( 'FLUIDAMPR_CC_API_KEY' ) ? 'readonly' : ''
	);
	echo '</td></tr>';

	echo '<tr><th scope="row"><label for="fluidampr_cc_client_secret">' . esc_html__( 'Client secret', 'fluidampr' ) . '</label></th><td>';
	echo '<input class="regular-text" type="password" id="fluidampr_cc_client_secret" name="fluidampr_cc_client_secret" value="" autocomplete="new-password" placeholder="' . esc_attr( fluidampr_cc_get( 'client_secret' ) ? __( 'Saved — leave blank to keep', 'fluidampr' ) : '' ) . '">';
	echo '</td></tr>';

	echo '<tr><th scope="row">' . esc_html__( 'Status', 'fluidampr' ) . '</th><td>';
	echo $connected
		? '<span>' . esc_html__( 'Connected. Tokens refresh automatically.', 'fluidampr' ) . '</span>'
		: '<span>' . esc_html__( 'Not connected.', 'fluidampr' ) . '</span>';
	echo '</td></tr>';

	if ( $connected ) {
		if ( is_wp_error( $lists ) ) {
			echo '<tr><th scope="row">' . esc_html__( 'Signup lists', 'fluidampr' ) . '</th><td>';
			echo '<p>' . esc_html__( 'Connected, but lists could not be loaded. Reconnect if this continues.', 'fluidampr' ) . '</p>';
			echo '</td></tr>';
		} else {
			echo '<tr><th scope="row"><label for="fluidampr_cc_list_id_customer">' . esc_html__( 'Customer list', 'fluidampr' ) . '</label></th><td>';
			fluidampr_cc_render_list_select( 'fluidampr_cc_list_id_customer', $customer, $lists );
			echo '<p class="description">' . esc_html__( 'Used when someone chooses Customer on the footer form.', 'fluidampr' ) . '</p>';
			echo '</td></tr>';

			echo '<tr><th scope="row"><label for="fluidampr_cc_list_id_dealer">' . esc_html__( 'Dealer list', 'fluidampr' ) . '</label></th><td>';
			fluidampr_cc_render_list_select( 'fluidampr_cc_list_id_dealer', $dealer, $lists );
			echo '<p class="description">' . esc_html__( 'Used when someone chooses Dealer on the footer form.', 'fluidampr' ) . '</p>';
			echo '</td></tr>';
		}
	}

	echo '<tr><th scope="row"><label for="fluidampr_turnstile_site_key">' . esc_html__( 'Turnstile site key', 'fluidampr' ) . '</label></th><td>';
	printf(
		'<input class="regular-text" type="text" id="fluidampr_turnstile_site_key" name="fluidampr_turnstile_site_key" value="%s" autocomplete="off" %s>',
		esc_attr( function_exists( 'fluidampr_turnstile_get' ) ? fluidampr_turnstile_get( 'site_key' ) : '' ),
		defined( 'FLUIDAMPR_TURNSTILE_SITE_KEY' ) ? 'readonly' : ''
	);
	echo '<p class="description">' . esc_html__( 'Cloudflare Turnstile widget key. Required with the secret to enable the security check on the footer form.', 'fluidampr' ) . '</p>';
	echo '</td></tr>';

	echo '<tr><th scope="row"><label for="fluidampr_turnstile_secret">' . esc_html__( 'Turnstile secret key', 'fluidampr' ) . '</label></th><td>';
	$turnstile_saved = function_exists( 'fluidampr_turnstile_get' ) && '' !== fluidampr_turnstile_get( 'secret' );
	echo '<input class="regular-text" type="password" id="fluidampr_turnstile_secret" name="fluidampr_turnstile_secret" value="" autocomplete="new-password" placeholder="' . esc_attr( $turnstile_saved ? __( 'Saved — leave blank to keep', 'fluidampr' ) : '' ) . '"' . ( defined( 'FLUIDAMPR_TURNSTILE_SECRET' ) ? ' readonly' : '' ) . '>';
	echo '<p class="description">' . esc_html__( 'Verified on the server. Never commit this value.', 'fluidampr' ) . '</p>';
	echo '</td></tr>';

	echo '<tr><th scope="row">' . esc_html__( 'Turnstile status', 'fluidampr' ) . '</th><td>';
	echo ( function_exists( 'fluidampr_turnstile_is_enabled' ) && fluidampr_turnstile_is_enabled() )
		? '<span>' . esc_html__( 'Enabled. Footer signups require a successful Turnstile check.', 'fluidampr' ) . '</span>'
		: '<span>' . esc_html__( 'Not enabled. Honeypot and rate limiting still run.', 'fluidampr' ) . '</span>';
	echo '</td></tr>';

	echo '</tbody></table>';

	submit_button( __( 'Save Constant Contact settings', 'fluidampr' ), 'secondary', 'fluidampr_cc_save', false );
	echo ' ';
	submit_button( __( 'Connect Constant Contact', 'fluidampr' ), 'primary', 'fluidampr_cc_connect', false );

	if ( $connected ) {
		echo ' ';
		submit_button( __( 'Disconnect', 'fluidampr' ), 'delete', 'fluidampr_cc_disconnect', false );
	}

	echo '</form>';
}

/**
 * List dropdown for the setup screen.
 *
 * @param string                           $name    Select name and id.
 * @param string                           $current Selected list ID.
 * @param array<int, array<string, string>> $lists  Lists from the API.
 * @return void
 */
function fluidampr_cc_render_list_select( $name, $current, $lists ) {
	printf( '<select id="%1$s" name="%1$s">', esc_attr( $name ) );
	echo '<option value="">' . esc_html__( 'Select a list', 'fluidampr' ) . '</option>';

	foreach ( $lists as $list ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $list['id'] ),
			selected( $current, $list['id'], false ),
			esc_html( $list['name'] )
		);
	}

	echo '</select>';
}
