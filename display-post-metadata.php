<?php
/**
 * Plugin Name: Display Post Metadata
 * Plugin URI: https://fahidjavid.com
 * Description: Display post/page metadata and custom fields via shortcode or Gutenberg block. Supports reading time, categories, tags, author avatar, 4 design variations, and full style customization.
 * Version: 2.0.0
 * Tested up to: 6.9
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Fahid Javid
 * Author URI: https://www.fahidjavid.com
 * Text Domain: display-post-metadata
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPM_VERSION', '2.0.0' );
define( 'DPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DPM_PLUGIN_DIR . 'include/views.php';
require_once DPM_PLUGIN_DIR . 'include/display-metadata-shortcode.php';
require_once DPM_PLUGIN_DIR . 'include/admin-settings.php';
require_once DPM_PLUGIN_DIR . 'include/block.php';


/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function display_post_metadata_load_textdomain() {
	load_plugin_textdomain( 'display-post-metadata', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'display_post_metadata_load_textdomain' );


/**
 * Enqueue frontend stylesheet and inject global CSS custom properties.
 *
 * @since 1.0.0
 */
function display_post_metadata_styles() {
	wp_enqueue_style( 'dpm-style', DPM_PLUGIN_URL . 'css/style.css', [], DPM_VERSION );

	$inline_css = dpm_get_global_css_vars();
	if ( $inline_css ) {
		wp_add_inline_style( 'dpm-style', $inline_css );
	}
}
add_action( 'wp_enqueue_scripts', 'display_post_metadata_styles' );


/**
 * Return inline SVG markup by icon name.
 *
 * SVGs live in /svg/{name}.svg and use currentColor so they respond
 * to the --dpm-icon-color CSS custom property.
 *
 * @since 2.0.0
 *
 * @param string $name  Icon filename without extension.
 * @return string       SVG markup, or empty string if file not found.
 */
function dpm_get_svg( string $name ): string {
	$file = DPM_PLUGIN_DIR . 'svg/' . sanitize_file_name( $name ) . '.svg';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	// Add accessibility and style attributes.
	$svg = str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" class="dpm-icon" ', $svg );
	return $svg;
}


/**
 * Build a CSS string declaring .dpm-wrap custom properties from global settings.
 *
 * Shortcode/block instances can override these with inline style attributes
 * on their own wrapper element, which cascade naturally.
 *
 * @since 2.0.0
 *
 * @param array $overrides  Per-instance overrides (shortcode atts).
 * @return string
 */
function dpm_get_global_css_vars( array $overrides = [] ): string {
	$settings = get_option( 'dpm_settings', [] );
	$s        = array_merge( $settings, array_filter( $overrides ) );

	$vars = [];

	if ( ! empty( $s['text_color'] ) ) {
		$vars[] = '--dpm-text-color:' . sanitize_hex_color( $s['text_color'] );
	}
	if ( ! empty( $s['bg_color'] ) ) {
		$vars[] = '--dpm-bg-color:' . sanitize_hex_color( $s['bg_color'] );
	}
	if ( ! empty( $s['border_color'] ) ) {
		$vars[] = '--dpm-border-color:' . sanitize_hex_color( $s['border_color'] );
	}
	if ( ! empty( $s['icon_color'] ) ) {
		$vars[] = '--dpm-icon-color:' . sanitize_hex_color( $s['icon_color'] );
	}
	if ( ! empty( $s['font_size'] ) && intval( $s['font_size'] ) > 0 ) {
		$vars[] = '--dpm-font-size:' . intval( $s['font_size'] ) . 'px';
	}
	if ( ! empty( $s['icon_size'] ) && intval( $s['icon_size'] ) > 0 ) {
		$vars[] = '--dpm-icon-size:' . intval( $s['icon_size'] ) . 'px';
	}
	if ( isset( $s['border_radius'] ) && $s['border_radius'] !== '' ) {
		$vars[] = '--dpm-border-radius:' . intval( $s['border_radius'] ) . 'px';
	}

	if ( empty( $vars ) ) {
		return '';
	}

	return '.dpm-wrap{' . implode( ';', $vars ) . '}';
}


new Display_Metadata_Shortcode();
