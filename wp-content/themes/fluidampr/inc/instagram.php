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
	return 'fluidampr_ig_feed_v3';
}

/**
 * Number of posts cached for homepage (3) and community (21) slices.
 *
 * @return int
 */
function fluidampr_instagram_cache_size() {
	return 21;
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

	return array(
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/community-hemi.jpg',
			'title'     => __( '2,800HP Gen III HEMI', 'fluidampr' ),
			'handle'    => '@boostedbrad',
		),
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/community-ecoboost.jpg',
			'title'     => __( '6.7 Power Stroke Build', 'fluidampr' ),
			'handle'    => '@freedom_factory',
		),
		array(
			'permalink' => $profile,
			'image'     => FLUIDAMPR_THEME_URI . '/assets/images/community-ls3.jpg',
			'title'     => __( 'LS3 Road Course', 'fluidampr' ),
			'handle'    => '@stevetorrisracing',
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
 * Fetch newest image posts from graph.instagram.com, newest first.
 *
 * @param int $count Number of tiles.
 * @return array<int, array<string, string>>
 */
function fluidampr_instagram_fetch_items( $count = 3 ) {
	$token = fluidampr_instagram_token();
	$count = max( 1, min( fluidampr_instagram_cache_size(), (int) $count ) );

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

	$items     = array();
	$media_url = add_query_arg(
		array(
			'fields'       => 'id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url}',
			'limit'        => 25,
			'access_token' => $token,
		),
		'https://graph.instagram.com/me/media'
	);

	for ( $page = 0; $page < 8 && $media_url && count( $items ) < $count; $page++ ) {
		$media_response = fluidampr_instagram_remote_get( $media_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $media_response ) || 200 !== (int) wp_remote_retrieve_response_code( $media_response ) ) {
			break;
		}

		$payload   = json_decode( (string) wp_remote_retrieve_body( $media_response ), true );
		$batch     = ( is_array( $payload ) && isset( $payload['data'] ) && is_array( $payload['data'] ) ) ? $payload['data'] : array();
		$media_url = ( is_array( $payload ) && isset( $payload['paging']['next'] ) ) ? (string) $payload['paging']['next'] : '';

		foreach ( $batch as $post ) {
			if ( ! is_array( $post ) || ! fluidampr_instagram_is_feed_post( $post ) ) {
				continue;
			}

			$image = fluidampr_instagram_post_image( $post );

			if ( '' === $image || empty( $post['permalink'] ) ) {
				continue;
			}

			$items[] = array(
				'permalink' => (string) $post['permalink'],
				'image'     => $image,
				'title'     => fluidampr_instagram_caption_title( isset( $post['caption'] ) ? (string) $post['caption'] : '' ),
				'handle'    => $handle,
				'timestamp' => isset( $post['timestamp'] ) ? (string) $post['timestamp'] : '',
			);

			if ( count( $items ) >= $count ) {
				break;
			}
		}
	}

	return $items;
}

/**
 * Whether a Graph API media object belongs on the Instagram Posts grid.
 *
 * Reels live on a separate tab. The profile grid is FEED photos, carousels,
 * and in-feed video — not REELS.
 *
 * @param array<string, mixed> $post Media object.
 * @return bool
 */
function fluidampr_instagram_is_feed_post( $post ) {
	$product = isset( $post['media_product_type'] ) ? strtoupper( (string) $post['media_product_type'] ) : '';

	if ( in_array( $product, array( 'REELS', 'STORY', 'AD' ), true ) ) {
		return false;
	}

	if ( 'FEED' === $product ) {
		return true;
	}

	$type = isset( $post['media_type'] ) ? (string) $post['media_type'] : '';

	return in_array( $type, array( 'IMAGE', 'CAROUSEL_ALBUM' ), true );
}

/**
 * Best still image for a tile (carousel children included).
 *
 * @param array<string, mixed> $post Media object.
 * @return string
 */
function fluidampr_instagram_post_image( $post ) {
	$type = isset( $post['media_type'] ) ? (string) $post['media_type'] : '';

	if ( 'VIDEO' === $type && ! empty( $post['thumbnail_url'] ) ) {
		return (string) $post['thumbnail_url'];
	}

	if ( ! empty( $post['media_url'] ) ) {
		return (string) $post['media_url'];
	}

	if ( ! empty( $post['thumbnail_url'] ) ) {
		return (string) $post['thumbnail_url'];
	}

	$children = ( isset( $post['children']['data'] ) && is_array( $post['children']['data'] ) ) ? $post['children']['data'] : array();

	foreach ( $children as $child ) {
		if ( ! is_array( $child ) ) {
			continue;
		}

		$child_type = isset( $child['media_type'] ) ? (string) $child['media_type'] : '';

		if ( 'VIDEO' === $child_type && ! empty( $child['thumbnail_url'] ) ) {
			return (string) $child['thumbnail_url'];
		}

		if ( ! empty( $child['media_url'] ) ) {
			return (string) $child['media_url'];
		}

		if ( ! empty( $child['thumbnail_url'] ) ) {
			return (string) $child['thumbnail_url'];
		}
	}

	return '';
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
	$count = max( 1, min( fluidampr_instagram_cache_size(), (int) $count ) );
	$cache = get_transient( fluidampr_instagram_transient_key() );

	if (
		is_array( $cache )
		&& ! empty( $cache['items'] )
		&& is_array( $cache['items'] )
		&& count( $cache['items'] ) >= $count
		&& ( $cache['source'] ?? '' ) === 'api'
	) {
		return array_slice( $cache['items'], 0, $count );
	}

	$fetched = fluidampr_instagram_fetch_items( fluidampr_instagram_cache_size() );

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
	delete_transient( 'fluidampr_ig_feed_v1' );
	delete_transient( 'fluidampr_ig_feed_v2' );
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
	fluidampr_instagram_get_items( fluidampr_instagram_cache_size() );
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
