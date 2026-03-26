<?php

/**
 * Admin Settings Page for Display Post Metadata.
 *
 * Option name: dpm_settings (single serialized array).
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dpm_add_settings_page' );
add_action( 'admin_init', 'dpm_register_settings' );
add_action( 'admin_enqueue_scripts', 'dpm_admin_enqueue_scripts' );

/**
 * Register the settings page under Settings menu.
 */
function dpm_add_settings_page(): void {
	add_options_page(
		__( 'Display Post Metadata', 'display-post-metadata' ),
		__( 'Post Metadata', 'display-post-metadata' ),
		'manage_options',
		'display-post-metadata',
		'dpm_render_settings_page'
	);
}

/**
 * Register setting + sanitize callback.
 */
function dpm_register_settings(): void {
	register_setting(
		'dpm_settings_group',
		'dpm_settings',
		[
			'sanitize_callback' => 'dpm_sanitize_settings',
			'default'           => dpm_default_settings(),
		]
	);
}

/**
 * Default settings values.
 *
 * @return array
 */
function dpm_default_settings(): array {
	return [
		// General
		'default_elements'   => [ 'date', 'author', 'comments' ],
		'design_variation'   => 'default',
		// Author
		'show_author_avatar' => false,
		'avatar_size'        => 32,
		// Reading time
		'reading_speed'      => 200,
		// View counter
		'skip_bots_views'    => true,
		'skip_loggedin_views'=> false,
		// Style
		'text_color'         => '',
		'bg_color'           => '',
		'border_color'       => '',
		'icon_color'         => '',
		'font_size'          => '',
		'icon_size'          => '',
		'border_radius'      => '',
	];
}

/**
 * Sanitize settings before saving.
 *
 * @param mixed $input Raw POST data.
 * @return array       Sanitized settings.
 */
function dpm_sanitize_settings( $input ): array {
	$defaults = dpm_default_settings();
	$output   = $defaults;

	$all_elements = [
		'date', 'author', 'sticky', 'views', 'comments',
		'reading_time', 'modified', 'categories', 'tags', 'custom_fields',
	];

	// Checkboxes — default_elements.
	$submitted_elements = isset( $input['default_elements'] ) ? (array) $input['default_elements'] : [];
	$output['default_elements'] = array_intersect( $submitted_elements, $all_elements );

	// Select.
	$allowed_designs = [ 'default', 'inline', 'card', 'minimal' ];
	$output['design_variation'] = in_array( $input['design_variation'] ?? '', $allowed_designs, true )
		? $input['design_variation']
		: 'default';

	// Booleans.
	$output['show_author_avatar']  = ! empty( $input['show_author_avatar'] );
	$output['skip_bots_views']     = ! empty( $input['skip_bots_views'] );
	$output['skip_loggedin_views'] = ! empty( $input['skip_loggedin_views'] );

	// Integers.
	$output['avatar_size']    = max( 16, min( 96, intval( $input['avatar_size'] ?? 32 ) ) );
	$output['reading_speed']  = max( 50, min( 1000, intval( $input['reading_speed'] ?? 200 ) ) );
	$output['font_size']      = intval( $input['font_size'] ?? 0 ) ?: '';
	$output['icon_size']      = intval( $input['icon_size'] ?? 0 ) ?: '';
	$output['border_radius']  = isset( $input['border_radius'] ) && $input['border_radius'] !== ''
		? max( 0, intval( $input['border_radius'] ) )
		: '';

	// Colors.
	foreach ( [ 'text_color', 'bg_color', 'border_color', 'icon_color' ] as $key ) {
		$output[ $key ] = ! empty( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
	}

	return $output;
}

/**
 * Enqueue color picker and admin settings scripts/styles on our settings page.
 *
 * @param string $hook Current admin page hook.
 */
function dpm_admin_enqueue_scripts( string $hook ): void {
	if ( 'settings_page_display-post-metadata' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'dpm-admin', DPM_PLUGIN_URL . 'css/admin-settings.css', [], DPM_VERSION );

	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_script(
		'dpm-admin',
		DPM_PLUGIN_URL . 'js/admin-settings.js',
		[ 'jquery', 'wp-color-picker' ],
		DPM_VERSION,
		true
	);
}

/**
 * Render the settings page HTML.
 */
function dpm_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'dpm_settings', dpm_default_settings() );
	$settings = array_merge( dpm_default_settings(), $settings );

	// Build inline style string for the live preview container.
	$preview_vars = '';
	$raw_vars     = dpm_get_global_css_vars( $settings );
	if ( $raw_vars ) {
		// Strip the .dpm-wrap{...} wrapper to get just the declarations.
		$preview_vars = trim( str_replace( [ '.dpm-wrap{', '}' ], '', $raw_vars ) );
	}

	$all_elements = [
		'date'          => [ 'label' => __( 'Date',          'display-post-metadata' ), 'icon' => 'dashicons-calendar-alt' ],
		'author'        => [ 'label' => __( 'Author',        'display-post-metadata' ), 'icon' => 'dashicons-admin-users' ],
		'comments'      => [ 'label' => __( 'Comments',      'display-post-metadata' ), 'icon' => 'dashicons-admin-comments' ],
		'sticky'        => [ 'label' => __( 'Sticky',        'display-post-metadata' ), 'icon' => 'dashicons-bookmark' ],
		'views'         => [ 'label' => __( 'Views',         'display-post-metadata' ), 'icon' => 'dashicons-visibility' ],
		'reading_time'  => [ 'label' => __( 'Reading Time',  'display-post-metadata' ), 'icon' => 'dashicons-clock' ],
		'modified'      => [ 'label' => __( 'Modified Date', 'display-post-metadata' ), 'icon' => 'dashicons-edit' ],
		'categories'    => [ 'label' => __( 'Categories',    'display-post-metadata' ), 'icon' => 'dashicons-category' ],
		'tags'          => [ 'label' => __( 'Tags',          'display-post-metadata' ), 'icon' => 'dashicons-tag' ],
		'custom_fields' => [ 'label' => __( 'Custom Fields', 'display-post-metadata' ), 'icon' => 'dashicons-list-view' ],
	];

	$designs = [
		'default' => [
			'label' => __( 'Default',  'display-post-metadata' ),
			'desc'  => __( 'Horizontal bordered strip', 'display-post-metadata' ),
			'preview' => '<div class="dpm-dp-default"><span></span><span></span><span></span></div>',
		],
		'inline'  => [
			'label' => __( 'Inline',   'display-post-metadata' ),
			'desc'  => __( 'Byline style', 'display-post-metadata' ),
			'preview' => '<div class="dpm-dp-inline"><span></span><em>·</em><span></span><em>·</em><span></span></div>',
		],
		'card'    => [
			'label' => __( 'Card',     'display-post-metadata' ),
			'desc'  => __( 'Vertical with shadow', 'display-post-metadata' ),
			'preview' => '<div class="dpm-dp-card"><span></span><span></span><span></span></div>',
		],
		'minimal' => [
			'label' => __( 'Minimal',  'display-post-metadata' ),
			'desc'  => __( 'Pill badges', 'display-post-metadata' ),
			'preview' => '<div class="dpm-dp-minimal"><span></span><span></span><span></span></div>',
		],
	];
	?>
	<div class="wrap dpm-settings-wrap">

		<!-- ── HEADER ────────────────────────────────────────── -->
		<div class="dpm-header">
			<div class="dpm-header-icon">
				<span class="dashicons dashicons-tag"></span>
			</div>
			<div class="dpm-header-text">
				<h1><?php esc_html_e( 'Display Post Metadata', 'display-post-metadata' ); ?></h1>
				<p><?php esc_html_e( 'Configure global defaults. Every setting can be overridden per shortcode or block.', 'display-post-metadata' ); ?></p>
			</div>
			<span class="dpm-version-badge">v<?php echo esc_html( DPM_VERSION ); ?></span>
		</div>

		<?php settings_errors( 'dpm_settings_group' ); ?>

		<form method="post" action="options.php" id="dpm-settings-form">
			<?php settings_fields( 'dpm_settings_group' ); ?>

			<!-- ── TABS ───────────────────────────────────────── -->
			<nav class="dpm-tabs" role="tablist">
				<button type="button" class="dpm-tab active" data-tab="general">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'General', 'display-post-metadata' ); ?>
				</button>
				<button type="button" class="dpm-tab" data-tab="style">
					<span class="dashicons dashicons-art"></span>
					<?php esc_html_e( 'Style', 'display-post-metadata' ); ?>
				</button>
				<button type="button" class="dpm-tab" data-tab="views">
					<span class="dashicons dashicons-chart-bar"></span>
					<?php esc_html_e( 'View Counter', 'display-post-metadata' ); ?>
				</button>
			</nav>

			<!-- ═══════════════════════════════════════════════
			     GENERAL TAB
			     ═══════════════════════════════════════════════ -->
			<div class="dpm-tab-panel active" id="dpm-tab-general">

				<!-- Elements card -->
				<div class="dpm-card">
					<div class="dpm-card-header">
						<div class="dpm-card-header-icon">
							<span class="dashicons dashicons-list-view"></span>
						</div>
						<div class="dpm-card-header-text">
							<h2><?php esc_html_e( 'Default Elements', 'display-post-metadata' ); ?></h2>
							<p><?php esc_html_e( 'Shown when no element= attribute is given in the shortcode.', 'display-post-metadata' ); ?></p>
						</div>
					</div>
					<div class="dpm-card-body">
						<div class="dpm-chips" role="group" aria-label="<?php esc_attr_e( 'Default Elements', 'display-post-metadata' ); ?>">
							<?php foreach ( $all_elements as $key => $meta ) : ?>
								<label class="dpm-chip">
									<input type="checkbox"
										name="dpm_settings[default_elements][]"
										value="<?php echo esc_attr( $key ); ?>"
										<?php checked( in_array( $key, (array) $settings['default_elements'], true ) ); ?>>
									<span class="dpm-chip-label">
										<span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
										<?php echo esc_html( $meta['label'] ); ?>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Design variation card -->
				<div class="dpm-card">
					<div class="dpm-card-header">
						<div class="dpm-card-header-icon">
							<span class="dashicons dashicons-admin-appearance"></span>
						</div>
						<div class="dpm-card-header-text">
							<h2><?php esc_html_e( 'Design Variation', 'display-post-metadata' ); ?></h2>
							<p><?php esc_html_e( 'Default visual layout for all metadata displays.', 'display-post-metadata' ); ?></p>
						</div>
					</div>
					<div class="dpm-card-body">
						<div class="dpm-design-grid" id="dpm_design_variation" role="radiogroup">
							<?php foreach ( $designs as $val => $design ) : ?>
								<label class="dpm-design-option">
									<input type="radio"
										name="dpm_settings[design_variation]"
										value="<?php echo esc_attr( $val ); ?>"
										<?php checked( $settings['design_variation'], $val ); ?>>
									<div class="dpm-design-card-inner">
										<div class="dpm-design-preview">
											<?php echo $design['preview']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
										<div class="dpm-design-label">
											<strong><?php echo esc_html( $design['label'] ); ?></strong>
											<span><?php echo esc_html( $design['desc'] ); ?></span>
										</div>
									</div>
									<div class="dpm-design-checked">
										<span class="dashicons dashicons-yes"></span>
									</div>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Author & reading card -->
				<div class="dpm-card">
					<div class="dpm-card-header">
						<div class="dpm-card-header-icon">
							<span class="dashicons dashicons-admin-users"></span>
						</div>
						<div class="dpm-card-header-text">
							<h2><?php esc_html_e( 'Author & Reading Time', 'display-post-metadata' ); ?></h2>
							<p><?php esc_html_e( 'Avatar display and reading speed settings.', 'display-post-metadata' ); ?></p>
						</div>
					</div>
					<div class="dpm-card-body">

						<!-- Author avatar toggle -->
						<div class="dpm-toggle-row">
							<div class="dpm-toggle-text">
								<strong><?php esc_html_e( 'Show Author Avatar', 'display-post-metadata' ); ?></strong>
								<p><?php esc_html_e( 'Display the Gravatar image next to the author name.', 'display-post-metadata' ); ?></p>
							</div>
							<label class="dpm-toggle-switch">
								<input type="checkbox" name="dpm_settings[show_author_avatar]" value="1"
									<?php checked( $settings['show_author_avatar'] ); ?>>
								<span class="dpm-toggle-track"></span>
							</label>
						</div>

						<!-- Avatar size -->
						<div class="dpm-field-row">
							<label class="dpm-field-label" for="dpm_avatar_size">
								<?php esc_html_e( 'Avatar Size', 'display-post-metadata' ); ?>
							</label>
							<div>
								<div class="dpm-number-input">
									<input type="number" id="dpm_avatar_size" name="dpm_settings[avatar_size]"
										min="16" max="96" step="2"
										value="<?php echo esc_attr( $settings['avatar_size'] ); ?>">
									<span class="dpm-number-unit">px</span>
								</div>
							</div>
						</div>

						<!-- Reading speed -->
						<div class="dpm-field-row">
							<label class="dpm-field-label" for="dpm_reading_speed">
								<?php esc_html_e( 'Reading Speed', 'display-post-metadata' ); ?>
							</label>
							<div>
								<div class="dpm-number-input">
									<input type="number" id="dpm_reading_speed" name="dpm_settings[reading_speed]"
										min="50" max="1000"
										value="<?php echo esc_attr( $settings['reading_speed'] ); ?>">
									<span class="dpm-number-unit">wpm</span>
								</div>
								<p class="dpm-field-desc"><?php esc_html_e( 'Words per minute. Average reader: 200 wpm.', 'display-post-metadata' ); ?></p>
							</div>
						</div>

					</div>
				</div>

			</div><!-- /general tab -->

			<!-- ═══════════════════════════════════════════════
			     STYLE TAB
			     ═══════════════════════════════════════════════ -->
			<div class="dpm-tab-panel" id="dpm-tab-style">
				<div class="dpm-style-layout">

					<!-- Controls column -->
					<div class="dpm-style-controls">

						<!-- Colors card -->
						<div class="dpm-card">
							<div class="dpm-card-header">
								<div class="dpm-card-header-icon">
									<span class="dashicons dashicons-color-picker"></span>
								</div>
								<div class="dpm-card-header-text">
									<h2><?php esc_html_e( 'Colors', 'display-post-metadata' ); ?></h2>
									<p><?php esc_html_e( 'Leave blank to inherit from your theme.', 'display-post-metadata' ); ?></p>
								</div>
							</div>
							<div class="dpm-card-body">
								<div class="dpm-color-grid">
									<div class="dpm-color-field">
										<label for="dpm_text_color"><?php esc_html_e( 'Text Color', 'display-post-metadata' ); ?></label>
										<input type="text" id="dpm_text_color" name="dpm_settings[text_color]"
											value="<?php echo esc_attr( $settings['text_color'] ); ?>"
											class="dpm-color-picker" data-var="--dpm-text-color">
									</div>
									<div class="dpm-color-field">
										<label for="dpm_icon_color"><?php esc_html_e( 'Icon Color', 'display-post-metadata' ); ?></label>
										<input type="text" id="dpm_icon_color" name="dpm_settings[icon_color]"
											value="<?php echo esc_attr( $settings['icon_color'] ); ?>"
											class="dpm-color-picker" data-var="--dpm-icon-color">
									</div>
									<div class="dpm-color-field">
										<label for="dpm_bg_color"><?php esc_html_e( 'Background', 'display-post-metadata' ); ?></label>
										<input type="text" id="dpm_bg_color" name="dpm_settings[bg_color]"
											value="<?php echo esc_attr( $settings['bg_color'] ); ?>"
											class="dpm-color-picker" data-var="--dpm-bg-color">
									</div>
									<div class="dpm-color-field">
										<label for="dpm_border_color"><?php esc_html_e( 'Border Color', 'display-post-metadata' ); ?></label>
										<input type="text" id="dpm_border_color" name="dpm_settings[border_color]"
											value="<?php echo esc_attr( $settings['border_color'] ); ?>"
											class="dpm-color-picker" data-var="--dpm-border-color">
									</div>
								</div>

								<!-- Sizes -->
								<div class="dpm-size-fields">
									<div class="dpm-slider-row">
										<label for="dpm_font_size"><?php esc_html_e( 'Font Size', 'display-post-metadata' ); ?></label>
										<input type="range" id="dpm_font_size_range"
											min="10" max="32"
											value="<?php echo esc_attr( $settings['font_size'] ?: 15 ); ?>"
											class="dpm-range-control" data-var="--dpm-font-size" data-unit="px"
											data-target="dpm_font_size">
										<span class="dpm-slider-value" id="dpm_font_size_val">
											<?php echo esc_html( ( $settings['font_size'] ?: 15 ) . 'px' ); ?>
										</span>
										<input type="hidden" id="dpm_font_size" name="dpm_settings[font_size]"
											value="<?php echo esc_attr( $settings['font_size'] ); ?>">
									</div>
									<div class="dpm-slider-row">
										<label for="dpm_icon_size"><?php esc_html_e( 'Icon Size', 'display-post-metadata' ); ?></label>
										<input type="range" id="dpm_icon_size_range"
											min="12" max="48"
											value="<?php echo esc_attr( $settings['icon_size'] ?: 18 ); ?>"
											class="dpm-range-control" data-var="--dpm-icon-size" data-unit="px"
											data-target="dpm_icon_size">
										<span class="dpm-slider-value" id="dpm_icon_size_val">
											<?php echo esc_html( ( $settings['icon_size'] ?: 18 ) . 'px' ); ?>
										</span>
										<input type="hidden" id="dpm_icon_size" name="dpm_settings[icon_size]"
											value="<?php echo esc_attr( $settings['icon_size'] ); ?>">
									</div>
									<div class="dpm-slider-row">
										<label for="dpm_border_radius"><?php esc_html_e( 'Border Radius', 'display-post-metadata' ); ?></label>
										<input type="range" id="dpm_border_radius_range"
											min="0" max="50"
											value="<?php echo esc_attr( $settings['border_radius'] !== '' ? $settings['border_radius'] : 0 ); ?>"
											class="dpm-range-control" data-var="--dpm-border-radius" data-unit="px"
											data-target="dpm_border_radius">
										<span class="dpm-slider-value" id="dpm_border_radius_val">
											<?php echo esc_html( ( $settings['border_radius'] !== '' ? $settings['border_radius'] : 0 ) . 'px' ); ?>
										</span>
										<input type="hidden" id="dpm_border_radius" name="dpm_settings[border_radius]"
											value="<?php echo esc_attr( $settings['border_radius'] ); ?>">
									</div>
								</div>
							</div>
						</div>

					</div><!-- /style-controls -->

					<!-- Preview column -->
					<div class="dpm-style-preview">
						<div class="dpm-card">
							<div class="dpm-card-header">
								<div class="dpm-card-header-icon">
									<span class="dashicons dashicons-desktop"></span>
								</div>
								<div class="dpm-card-header-text">
									<h2><?php esc_html_e( 'Live Preview', 'display-post-metadata' ); ?></h2>
									<p><?php esc_html_e( 'Updates instantly as you make changes.', 'display-post-metadata' ); ?></p>
								</div>
							</div>
							<div class="dpm-card-body" style="padding:16px;">
								<div class="dpm-preview-browser">
									<div class="dpm-preview-bar">
										<span></span><span></span><span></span>
										<span class="dpm-preview-url">yoursite.com/sample-post/</span>
									</div>
									<div class="dpm-preview-content">
										<div id="dpm-preview-container"
											class="dpm-wrap dpm-design-<?php echo esc_attr( $settings['design_variation'] ); ?>"
											<?php if ( $preview_vars ) : ?>style="<?php echo esc_attr( $preview_vars ); ?>"<?php endif; ?>>
											<ul class="display-post-metadata">
												<li class="dpm-item date-meta">
													<?php echo dpm_get_svg( 'date' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) ); ?></span>
												</li>
												<li class="dpm-item author-meta">
													<?php echo dpm_get_svg( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<span><?php echo esc_html( wp_get_current_user()->display_name ?: 'Author Name' ); ?></span>
												</li>
												<li class="dpm-item comment-meta">
													<?php echo dpm_get_svg( 'comment' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<span><?php esc_html_e( '5 Comments', 'display-post-metadata' ); ?></span>
												</li>
												<li class="dpm-item reading-time-meta">
													<?php echo dpm_get_svg( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<span><?php esc_html_e( '4 min read', 'display-post-metadata' ); ?></span>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<p class="dpm-preview-hint"><?php esc_html_e( 'Colors and sizes update in real time.', 'display-post-metadata' ); ?></p>
							</div>
						</div>
					</div>

				</div>
			</div><!-- /style tab -->

			<!-- ═══════════════════════════════════════════════
			     VIEW COUNTER TAB
			     ═══════════════════════════════════════════════ -->
			<div class="dpm-tab-panel" id="dpm-tab-views">
				<div class="dpm-card">
					<div class="dpm-card-header">
						<div class="dpm-card-header-icon">
							<span class="dashicons dashicons-chart-bar"></span>
						</div>
						<div class="dpm-card-header-text">
							<h2><?php esc_html_e( 'View Counter Settings', 'display-post-metadata' ); ?></h2>
							<p><?php esc_html_e( 'Control what counts as a view to keep your data accurate.', 'display-post-metadata' ); ?></p>
						</div>
					</div>
					<div class="dpm-card-body">

						<div class="dpm-toggle-row">
							<div class="dpm-toggle-text">
								<strong><?php esc_html_e( 'Filter Bots & Crawlers', 'display-post-metadata' ); ?></strong>
								<p><?php esc_html_e( 'Skip Googlebot, Bingbot, and other known crawlers so they don\'t inflate your view count.', 'display-post-metadata' ); ?></p>
							</div>
							<label class="dpm-toggle-switch">
								<input type="checkbox" name="dpm_settings[skip_bots_views]" value="1"
									<?php checked( $settings['skip_bots_views'] ); ?>>
								<span class="dpm-toggle-track"></span>
							</label>
						</div>

						<div class="dpm-toggle-row">
							<div class="dpm-toggle-text">
								<strong><?php esc_html_e( 'Skip Logged-in Users', 'display-post-metadata' ); ?></strong>
								<p><?php esc_html_e( 'Don\'t count page views from logged-in editors or admins — useful for keeping front-end numbers clean.', 'display-post-metadata' ); ?></p>
							</div>
							<label class="dpm-toggle-switch">
								<input type="checkbox" name="dpm_settings[skip_loggedin_views]" value="1"
									<?php checked( $settings['skip_loggedin_views'] ); ?>>
								<span class="dpm-toggle-track"></span>
							</label>
						</div>

					</div>
				</div>
			</div><!-- /views tab -->

			<!-- ── SAVE BAR ───────────────────────────────────── -->
			<div class="dpm-save-bar">
				<p><?php esc_html_e( 'Changes apply globally. Shortcode and block overrides always take precedence.', 'display-post-metadata' ); ?></p>
				<button type="submit" class="dpm-save-btn">
					<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>
					<?php esc_html_e( 'Save Changes', 'display-post-metadata' ); ?>
				</button>
			</div>

		</form>

		<!-- ── SHORTCODE REFERENCE ───────────────────────────── -->
		<div class="dpm-shortcode-reference">
			<div class="dpm-shortcode-reference-header">
				<h2>
					<span class="dashicons dashicons-shortcode"></span>
					<?php esc_html_e( 'Shortcode Reference', 'display-post-metadata' ); ?>
				</h2>
				<button type="button" class="dpm-copy-btn" id="dpm-copy-sc">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Copy', 'display-post-metadata' ); ?>
				</button>
			</div>
			<div class="dpm-shortcode-reference-body">
				<p><?php esc_html_e( 'All attributes are optional and override the global defaults above.', 'display-post-metadata' ); ?></p>
				<pre class="dpm-code" id="dpm-sc-code"><span class="sc-tag">[metadata</span>
  <span class="sc-attr">element</span><span class="sc-sep">=</span><span class="sc-val">"date,author,comments,sticky,views,reading_time,modified,categories,tags,custom_fields"</span>
  <span class="sc-attr">design</span><span class="sc-sep">=</span><span class="sc-val">"default|inline|card|minimal"</span>
  <span class="sc-attr">text_color</span><span class="sc-sep">=</span><span class="sc-val">"#333333"</span>
  <span class="sc-attr">bg_color</span><span class="sc-sep">=</span><span class="sc-val">"#ffffff"</span>
  <span class="sc-attr">border_color</span><span class="sc-sep">=</span><span class="sc-val">"#e5e7ee"</span>
  <span class="sc-attr">icon_color</span><span class="sc-sep">=</span><span class="sc-val">"#666666"</span>
  <span class="sc-attr">font_size</span><span class="sc-sep">=</span><span class="sc-val">"16"</span>
  <span class="sc-attr">icon_size</span><span class="sc-sep">=</span><span class="sc-val">"22"</span>
  <span class="sc-attr">border_radius</span><span class="sc-sep">=</span><span class="sc-val">"0"</span>
<span class="sc-tag">]</span></pre>
			</div>
		</div>

	</div><!-- /wrap -->
	<?php
}
