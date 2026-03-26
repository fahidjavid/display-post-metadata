<?php

/**
 * View counter helpers.
 *
 * @since 1.0.0
 */

/**
 * Return the formatted view count for a post.
 *
 * @param int $postID
 * @return string  e.g. "42 Views"
 */
function display_pmd_getPostViews( int $postID ): string {
	$count_key = 'post_views_count';
	$count     = get_post_meta( $postID, $count_key, true );

	if ( '' === $count ) {
		delete_post_meta( $postID, $count_key );
		add_post_meta( $postID, $count_key, '0' );
		return '0 ' . esc_html__( 'Views', 'display-post-metadata' );
	}

	$n = intval( $count );
	return $n . ' ' . esc_html( _n( 'View', 'Views', $n, 'display-post-metadata' ) );
}

/**
 * Increment the view count for a post.
 *
 * Respects the skip_bots_views and skip_loggedin_views global settings.
 *
 * @since 1.0.0
 * @since 2.0.0 Added bot/logged-in filtering.
 *
 * @param int $postID
 */
function display_pmd_setPostViews( int $postID ): void {
	$settings = get_option( 'dpm_settings', [] );

	// Optionally skip bot/crawler traffic.
	if ( ! empty( $settings['skip_bots_views'] ) && display_pmd_is_bot() ) {
		return;
	}

	// Optionally skip logged-in users.
	if ( ! empty( $settings['skip_loggedin_views'] ) && is_user_logged_in() ) {
		return;
	}

	$count_key = 'post_views_count';
	$count     = get_post_meta( $postID, $count_key, true );

	if ( '' === $count ) {
		delete_post_meta( $postID, $count_key );
		add_post_meta( $postID, $count_key, '0' );
	} else {
		update_post_meta( $postID, $count_key, intval( $count ) + 1 );
	}
}

/**
 * Detect common bots and crawlers via User-Agent string.
 *
 * @since 2.0.0
 *
 * @return bool True if the current request looks like a bot.
 */
function display_pmd_is_bot(): bool {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return true; // No UA = likely a bot/script.
	}

	$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

	$bot_signatures = [
		'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
		'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduck',
		'facebookexternalhit', 'twitterbot', 'linkedinbot',
		'wget', 'curl', 'python-requests', 'go-http-client',
		'apache-httpclient', 'okhttp', 'scrapy',
	];

	foreach ( $bot_signatures as $sig ) {
		if ( str_contains( $ua, $sig ) ) {
			return true;
		}
	}

	return false;
}

// Prevent browser link prefetch from inflating view counts.
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
