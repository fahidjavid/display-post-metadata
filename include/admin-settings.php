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

	$all_elements = [
		'date'         => __( 'Date', 'display-post-metadata' ),
		'author'       => __( 'Author', 'display-post-metadata' ),
		'comments'     => __( 'Comments', 'display-post-metadata' ),
		'sticky'       => __( 'Sticky', 'display-post-metadata' ),
		'views'        => __( 'Views', 'display-post-metadata' ),
		'reading_time' => __( 'Reading Time', 'display-post-metadata' ),
		'modified'     => __( 'Modified Date', 'display-post-metadata' ),
		'categories'   => __( 'Categories', 'display-post-metadata' ),
		'tags'         => __( 'Tags', 'display-post-metadata' ),
		'custom_fields'=> __( 'Custom Fields', 'display-post-metadata' ),
	];
	?>
	<div class="wrap dpm-settings-wrap">
		<h1><?php esc_html_e( 'Display Post Metadata', 'display-post-metadata' ); ?></h1>
		<p class="dpm-tagline"><?php esc_html_e( 'Configure global defaults. Any setting can be overridden per shortcode or block.', 'display-post-metadata' ); ?></p>

		<?php settings_errors( 'dpm_settings_group' ); ?>

		<form method="post" action="options.php" id="dpm-settings-form">
			<?php settings_fields( 'dpm_settings_group' ); ?>

			<!-- Tab navigation -->
			<nav class="dpm-tabs" role="tablist">
				<button type="button" class="dpm-tab active" data-tab="general"><?php esc_html_e( 'General', 'display-post-metadata' ); ?></button>
				<button type="button" class="dpm-tab" data-tab="style"><?php esc_html_e( 'Style', 'display-post-metadata' ); ?></button>
				<button type="button" class="dpm-tab" data-tab="views"><?php esc_html_e( 'View Counter', 'display-post-metadata' ); ?></button>
			</nav>

			<!-- General Tab -->
			<div class="dpm-tab-panel active" id="dpm-tab-general">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Elements', 'display-post-metadata' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Default Elements', 'display-post-metadata' ); ?></legend>
								<?php foreach ( $all_elements as $key => $label ) : ?>
									<label class="dpm-checkbox-label">
										<input type="checkbox"
											name="dpm_settings[default_elements][]"
											value="<?php echo esc_attr( $key ); ?>"
											<?php checked( in_array( $key, (array) $settings['default_elements'], true ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'Used when no element attribute is provided in the shortcode.', 'display-post-metadata' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpm_design_variation"><?php esc_html_e( 'Design Variation', 'display-post-metadata' ); ?></label></th>
						<td>
							<select name="dpm_settings[design_variation]" id="dpm_design_variation">
								<?php
								$designs = [
									'default' => __( 'Default (horizontal bordered list)', 'display-post-metadata' ),
									'inline'  => __( 'Inline (byline style)', 'display-post-metadata' ),
									'card'    => __( 'Card (vertical with shadow)', 'display-post-metadata' ),
									'minimal' => __( 'Minimal (pill badges)', 'display-post-metadata' ),
								];
								foreach ( $designs as $val => $label ) :
									?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings['design_variation'], $val ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Author Avatar', 'display-post-metadata' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dpm_settings[show_author_avatar]" value="1"
									<?php checked( $settings['show_author_avatar'] ); ?>>
								<?php esc_html_e( 'Show avatar image next to the author name', 'display-post-metadata' ); ?>
							</label>
							<p class="description">
								<label>
									<?php esc_html_e( 'Avatar size:', 'display-post-metadata' ); ?>
									<input type="number" name="dpm_settings[avatar_size]" min="16" max="96" step="2"
										value="<?php echo esc_attr( $settings['avatar_size'] ); ?>" class="small-text"> px
								</label>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpm_reading_speed"><?php esc_html_e( 'Reading Speed', 'display-post-metadata' ); ?></label></th>
						<td>
							<input type="number" id="dpm_reading_speed" name="dpm_settings[reading_speed]"
								min="50" max="1000" value="<?php echo esc_attr( $settings['reading_speed'] ); ?>" class="small-text">
							<?php esc_html_e( 'words per minute', 'display-post-metadata' ); ?>
							<p class="description"><?php esc_html_e( 'Used to calculate the estimated reading time. Average is 200 wpm.', 'display-post-metadata' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Style Tab -->
			<div class="dpm-tab-panel" id="dpm-tab-style">
				<div class="dpm-style-layout">
					<div class="dpm-style-controls">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="dpm_text_color"><?php esc_html_e( 'Text Color', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="text" id="dpm_text_color" name="dpm_settings[text_color]"
										value="<?php echo esc_attr( $settings['text_color'] ); ?>"
										class="dpm-color-picker" data-var="--dpm-text-color">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_icon_color"><?php esc_html_e( 'Icon Color', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="text" id="dpm_icon_color" name="dpm_settings[icon_color]"
										value="<?php echo esc_attr( $settings['icon_color'] ); ?>"
										class="dpm-color-picker" data-var="--dpm-icon-color">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_bg_color"><?php esc_html_e( 'Background Color', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="text" id="dpm_bg_color" name="dpm_settings[bg_color]"
										value="<?php echo esc_attr( $settings['bg_color'] ); ?>"
										class="dpm-color-picker" data-var="--dpm-bg-color">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_border_color"><?php esc_html_e( 'Border Color', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="text" id="dpm_border_color" name="dpm_settings[border_color]"
										value="<?php echo esc_attr( $settings['border_color'] ); ?>"
										class="dpm-color-picker" data-var="--dpm-border-color">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_font_size"><?php esc_html_e( 'Font Size', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="number" id="dpm_font_size" name="dpm_settings[font_size]"
										min="10" max="32" value="<?php echo esc_attr( $settings['font_size'] ); ?>"
										class="small-text dpm-range-control" data-var="--dpm-font-size" data-unit="px">
									px
									<p class="description"><?php esc_html_e( 'Leave blank to use your theme\'s default.', 'display-post-metadata' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_icon_size"><?php esc_html_e( 'Icon Size', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="number" id="dpm_icon_size" name="dpm_settings[icon_size]"
										min="12" max="48" value="<?php echo esc_attr( $settings['icon_size'] ); ?>"
										class="small-text dpm-range-control" data-var="--dpm-icon-size" data-unit="px">
									px
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="dpm_border_radius"><?php esc_html_e( 'Border Radius', 'display-post-metadata' ); ?></label></th>
								<td>
									<input type="number" id="dpm_border_radius" name="dpm_settings[border_radius]"
										min="0" max="50" value="<?php echo esc_attr( $settings['border_radius'] ); ?>"
										class="small-text dpm-range-control" data-var="--dpm-border-radius" data-unit="px">
									px
								</td>
							</tr>
						</table>
					</div>

					<!-- Live Preview -->
					<div class="dpm-style-preview">
						<h3><?php esc_html_e( 'Live Preview', 'display-post-metadata' ); ?></h3>
						<div id="dpm-preview-container"
							class="dpm-wrap dpm-design-<?php echo esc_attr( $settings['design_variation'] ); ?>"
							style="<?php echo esc_attr( dpm_get_global_css_vars( $settings ) ? ltrim( str_replace( '.dpm-wrap{', '', rtrim( dpm_get_global_css_vars( $settings ), '}' ) ) ) : '' ); ?>">
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
						<p class="description"><?php esc_html_e( 'Updates in real time as you change settings above.', 'display-post-metadata' ); ?></p>
					</div>
				</div>
			</div>

			<!-- View Counter Tab -->
			<div class="dpm-tab-panel" id="dpm-tab-views">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Bot Filtering', 'display-post-metadata' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dpm_settings[skip_bots_views]" value="1"
									<?php checked( $settings['skip_bots_views'] ); ?>>
								<?php esc_html_e( 'Skip known bots and crawlers', 'display-post-metadata' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Prevents Googlebot, Bingbot, and other crawlers from inflating the view count.', 'display-post-metadata' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Logged-in Users', 'display-post-metadata' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dpm_settings[skip_loggedin_views]" value="1"
									<?php checked( $settings['skip_loggedin_views'] ); ?>>
								<?php esc_html_e( 'Do not count views from logged-in users', 'display-post-metadata' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Useful for keeping admin/editor visits from skewing your view numbers.', 'display-post-metadata' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button(); ?>
		</form>

		<div class="dpm-shortcode-reference">
			<h2><?php esc_html_e( 'Shortcode Reference', 'display-post-metadata' ); ?></h2>
			<p><?php esc_html_e( 'Use the shortcode below in any post, page, or widget. All attributes are optional and override the global defaults above.', 'display-post-metadata' ); ?></p>
			<pre class="dpm-code">[metadata
  element="date,author,comments,sticky,views,reading_time,modified,categories,tags,custom_fields"
  design="default|inline|card|minimal"
  text_color="#333333"
  bg_color="#ffffff"
  border_color="#e5e7ee"
  icon_color="#666666"
  font_size="16"
  icon_size="22"
  border_radius="0"
]</pre>
		</div>
	</div>
	<?php
}
