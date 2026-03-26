<?php

/**
 * Gutenberg block registration for Display Post Metadata.
 *
 * The block uses server-side rendering so the same PHP rendering logic
 * from the shortcode is reused. No build process is required.
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'dpm_register_block' );

/**
 * Register the Gutenberg block.
 */
function dpm_register_block(): void {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	wp_register_script(
		'dpm-block-editor',
		DPM_PLUGIN_URL . 'js/block.js',
		[
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'wp-components',
			'wp-server-side-render',
			'wp-i18n',
		],
		DPM_VERSION,
		true
	);

	register_block_type(
		'display-post-metadata/metadata',
		[
			'editor_script'   => 'dpm-block-editor',
			'render_callback' => 'dpm_block_render',
			'attributes'      => [
				// Elements
				'showDate'         => [ 'type' => 'boolean', 'default' => true ],
				'showAuthor'       => [ 'type' => 'boolean', 'default' => true ],
				'showComments'     => [ 'type' => 'boolean', 'default' => true ],
				'showSticky'       => [ 'type' => 'boolean', 'default' => false ],
				'showViews'        => [ 'type' => 'boolean', 'default' => false ],
				'showReadingTime'  => [ 'type' => 'boolean', 'default' => false ],
				'showModified'     => [ 'type' => 'boolean', 'default' => false ],
				'showCategories'   => [ 'type' => 'boolean', 'default' => false ],
				'showTags'         => [ 'type' => 'boolean', 'default' => false ],
				'showCustomFields' => [ 'type' => 'boolean', 'default' => false ],
				// Design
				'design'           => [ 'type' => 'string',  'default' => 'default' ],
				// Style overrides
				'textColor'        => [ 'type' => 'string',  'default' => '' ],
				'bgColor'          => [ 'type' => 'string',  'default' => '' ],
				'borderColor'      => [ 'type' => 'string',  'default' => '' ],
				'iconColor'        => [ 'type' => 'string',  'default' => '' ],
				'fontSize'         => [ 'type' => 'integer', 'default' => 0 ],
				'iconSize'         => [ 'type' => 'integer', 'default' => 0 ],
				'borderRadius'     => [ 'type' => 'integer', 'default' => 0 ],
			],
		]
	);
}

/**
 * Server-side render callback for the block.
 *
 * Assembles a shortcode string from block attributes and runs it through
 * do_shortcode so the same output path is used for both block and shortcode.
 *
 * @param array $attributes Block attributes.
 * @return string           Rendered HTML.
 */
function dpm_block_render( array $attributes ): string {
	$element_map = [
		'showDate'         => 'date',
		'showAuthor'       => 'author',
		'showComments'     => 'comments',
		'showSticky'       => 'sticky',
		'showViews'        => 'views',
		'showReadingTime'  => 'reading_time',
		'showModified'     => 'modified',
		'showCategories'   => 'categories',
		'showTags'         => 'tags',
		'showCustomFields' => 'custom_fields',
	];

	$elements = [];
	foreach ( $element_map as $attr => $element ) {
		if ( ! empty( $attributes[ $attr ] ) ) {
			$elements[] = $element;
		}
	}

	if ( empty( $elements ) ) {
		return '<p class="dpm-empty-block">'
			. esc_html__( 'Select at least one metadata element in the block settings.', 'display-post-metadata' )
			. '</p>';
	}

	$atts_parts = [
		'element="' . esc_attr( implode( ',', $elements ) ) . '"',
		'design="' . esc_attr( $attributes['design'] ?? 'default' ) . '"',
	];

	$string_overrides = [
		'textColor'   => 'text_color',
		'bgColor'     => 'bg_color',
		'borderColor' => 'border_color',
		'iconColor'   => 'icon_color',
	];
	foreach ( $string_overrides as $attr => $sc_att ) {
		if ( ! empty( $attributes[ $attr ] ) ) {
			$atts_parts[] = $sc_att . '="' . esc_attr( $attributes[ $attr ] ) . '"';
		}
	}

	$int_overrides = [
		'fontSize'     => 'font_size',
		'iconSize'     => 'icon_size',
		'borderRadius' => 'border_radius',
	];
	foreach ( $int_overrides as $attr => $sc_att ) {
		if ( ! empty( $attributes[ $attr ] ) ) {
			$atts_parts[] = $sc_att . '="' . intval( $attributes[ $attr ] ) . '"';
		}
	}

	return do_shortcode( '[metadata ' . implode( ' ', $atts_parts ) . ']' );
}
