<?php
/**
 * Cached Instagram Graph API feed for the homepage community tiles.
 *
 * Requires a Business or Creator account and a long-lived token from
 * Instagram API with Instagram Login. Store the token in Customizer —
 * never commit it.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transient key for cached media.
 *
 * @return string
 */
function fluidampr_instagram_transient_key() {
	return 'fluidampr_ig_feed_v1';
}

/**
 * Long-lived Instagram access token from Customizer.
 *
 * @return string
 */
function fluidampr_instagram_token() {
	return trim( fluidampr_get_option( 'instagram_access_token' ) );
}

/**
 * Profile handle derived from the stored Instagram URL.
 *
 * @return string Handle without @.
 */
function fluidampr_instagram_handle() {
	$url  = fluidampr_get_option( 'instagram' );
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$part = explode( '/', $path );
	$last = end( $part );

	if ( is_string( $last ) && '' !== $last ) {
		$handle = preg_replace( '/[^A-Za-z0-9._]/', '', $last );

		if ( $handle ) {
			return $handle;
		}
	}

	return 'theoriginalfluidampr';
}

/**
 * Static Figma-styled tiles used when no token is set or the API fails.
 *
 * @return array<int, array<string, string>>
 */
function fluidampr_instagram_fallback_items() {
	$profile = fluidampr_get_option( 'instagram' );
	$handle  = '@' . fluidampr_instagram_handle();

	return array(
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/community-hemi.jpg',
			'title'     => __( '2,800HP Gen III HEMI', 'fluidampr' ),
			'handle'    => $handle,
		),
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/community-ecoboost.jpg',
			'title'     => __( '6.7 Power Stroke Build', 'fluidampr' ),
			'handle'    => $handle,
		),
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/cta-engine.jpg',
			'title'     => __( 'LS3 Road Course', 'fluidampr' ),
			'handle'    => $handle,
		),
	);
}

/**
 * First-line caption used as the tile title.
 *
 * @param string $caption Instagram caption.
 * @return string
 */
function fluidampr_instagram_caption_title( $caption ) {
	$caption = trim( wp_strip_all_tags( (string) $caption ) );

	if ( '' === $caption ) {
		return __( 'Fluidampr build', 'fluidampr' );
	}

	$lines = preg_split( '/\r\n|\r|\n/', $caption );
	$line  = trim( (string) $lines[0] );
	$line  = preg_replace( '/\s+/', ' ', $line );

	if ( strlen( $line ) > 48 ) {
		$line = rtrim( substr( $line, 0, 45 ) ) . '…';
	}

	return $line;
}

/**
 * Request helper that never logs the access token.
 *
 * @param string               $url  Request URL with token already applied.
 * @param array<string, mixed> $args wp_remote_get args.
 * @return array<string, mixed>|WP_Error
 */
function fluidampr_instagram_remote_get( $url, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'timeout' => 8,
			'headers' => array(
				'Accept' => 'application/json',
			),
		)
	);

	return wp_remote_get( $url, $args );
}

/**
 * Fetch latest image posts from graph.instagram.com.
 *
 * @param int $count Number of tiles.
 * @return array<int, array<string, string>>
 */
function fluidampr_instagram_fetch_items( $count = 3 ) {
	$token = fluidampr_instagram_token();

	if ( '' === $token ) {
		return array();
	}

	$user_url = add_query_arg(
		array(
			'fields'       => 'id,username',
			'access_token' => $token,
		),
		'https://graph.instagram.com/me'
	);

	$user_response = fluidampr_instagram_remote_get( $user_url );

	if ( is_wp_error( $user_response ) || 200 !== (int) wp_remote_retrieve_response_code( $user_response ) ) {
		return array();
	}

	$user     = json_decode( (string) wp_remote_retrieve_body( $user_response ), true );
	$username = is_array( $user ) && ! empty( $user['username'] ) ? (string) $user['username'] : fluidampr_instagram_handle();
	$handle   = '@' . ltrim( $username, '@' );

	$media_url = add_query_arg(
		array(
			'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
			'limit'        => 12,
			'access_token' => $token,
		),
		'https://graph.instagram.com/me/media'
	);

	$media_response = fluidampr_instagram_remote_get( $media_url );

	if ( is_wp_error( $media_response ) || 200 !== (int) wp_remote_retrieve_response_code( $media_response ) ) {
		return array();
	}

	$payload = json_decode( (string) wp_remote_retrieve_body( $media_response ), true );
	$raw     = ( is_array( $payload ) && isset( $payload['data'] ) && is_array( $payload['data'] ) ) ? $payload['data'] : array();
	$items   = array();

	foreach ( $raw as $post ) {
		if ( ! is_array( $post ) ) {
			continue;
		}

		$type  = isset( $post['media_type'] ) ? (string) $post['media_type'] : '';
		$image = '';

		if ( 'VIDEO' === $type && ! empty( $post['thumbnail_url'] ) ) {
			$image = (string) $post['thumbnail_url'];
		} elseif ( ! empty( $post['media_url'] ) ) {
			$image = (string) $post['media_url'];
		}

		if ( '' === $image || empty( $post['permalink'] ) ) {
			continue;
		}

		$items[] = array(
			'permalink' => (string) $post['permalink'],
			'image'     => $image,
			'title'     => fluidampr_instagram_caption_title( isset( $post['caption'] ) ? (string) $post['caption'] : '' ),
			'handle'    => $handle,
		);

		if ( count( $items ) >= $count ) {
			break;
		}
	}

	return $items;
}

/**
 * Refresh a long-lived token about once a week so it does not expire at 60 days.
 *
 * @return void
 */
function fluidampr_instagram_maybe_refresh_token() {
	$token = fluidampr_instagram_token();

	if ( '' === $token ) {
		return;
	}

	$last = (int) get_option( 'fluidampr_ig_token_refreshed', 0 );

	if ( $last && ( time() - $last ) < WEEK_IN_SECONDS ) {
		return;
	}

	$refresh_url = add_query_arg(
		array(
			'grant_type'   => 'ig_refresh_token',
			'access_token' => $token,
		),
		'https://graph.instagram.com/refresh_access_token'
	);

	$response = fluidampr_instagram_remote_get( $refresh_url );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return;
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
		return;
	}

	set_theme_mod( 'fluidampr_instagram_access_token', sanitize_text_field( (string) $body['access_token'] ) );
	update_option( 'fluidampr_ig_token_refreshed', time(), false );
}

/**
 * Latest feed items, cached. Falls back to curated tiles.
 *
 * @param int $count Number of tiles.
 * @return array<int, array<string, string>>
 */
function fluidampr_instagram_get_items( $count = 3 ) {
	$count = max( 1, min( 6, (int) $count ) );
	$cache = get_transient( fluidampr_instagram_transient_key() );

	if ( is_array( $cache ) && ! empty( $cache['items'] ) && is_array( $cache['items'] ) ) {
		return array_slice( $cache['items'], 0, $count );
	}

	$fetched = fluidampr_instagram_fetch_items( $count );

	if ( $fetched ) {
		set_transient(
			fluidampr_instagram_transient_key(),
			array(
				'items'   => $fetched,
				'source'  => 'api',
				'fetched' => time(),
			),
			HOUR_IN_SECONDS
		);
		fluidampr_instagram_maybe_refresh_token();

		return array_slice( $fetched, 0, $count );
	}

	$fallback = fluidampr_instagram_fallback_items();
	$ttl      = fluidampr_instagram_token() ? 5 * MINUTE_IN_SECONDS : 12 * HOUR_IN_SECONDS;

	set_transient(
		fluidampr_instagram_transient_key(),
		array(
			'items'   => $fallback,
			'source'  => 'fallback',
			'fetched' => time(),
		),
		$ttl
	);

	return array_slice( $fallback, 0, $count );
}

/**
 * Clear cached media when the token changes.
 *
 * @return void
 */
function fluidampr_instagram_clear_cache() {
	delete_transient( fluidampr_instagram_transient_key() );
}
add_action( 'customize_save_after', 'fluidampr_instagram_clear_cache' );

/**
 * Scheduled refresh so the homepage does not hit Instagram on every request.
 *
 * @return void
 */
function fluidampr_instagram_cron_refresh() {
	delete_transient( fluidampr_instagram_transient_key() );
	fluidampr_instagram_get_items( 3 );
}
add_action( 'fluidampr_instagram_cron', 'fluidampr_instagram_cron_refresh' );

/**
 * Register the hourly cron on init.
 *
 * @return void
 */
function fluidampr_instagram_schedule_cron() {
	if ( ! wp_next_scheduled( 'fluidampr_instagram_cron' ) ) {
		wp_schedule_event( time() + 120, 'hourly', 'fluidampr_instagram_cron' );
	}
}
add_action( 'init', 'fluidampr_instagram_schedule_cron' );
