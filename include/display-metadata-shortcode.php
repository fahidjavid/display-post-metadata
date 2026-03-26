<?php

/**
 * Display_Metadata_Shortcode
 *
 * Handles the [metadata] shortcode and the classic TinyMCE editor button.
 *
 * Shortcode usage:
 *   [metadata element="date,author,comments,sticky,views,reading_time,modified,categories,tags,custom_fields"
 *            design="default|inline|card|minimal"
 *            text_color="#333333" bg_color="#ffffff" border_color="#e5e7ee"
 *            icon_color="#666666" font_size="16" icon_size="22" border_radius="0"]
 */
class Display_Metadata_Shortcode {

	/**
	 * Shortcode tag name.
	 *
	 * @var string
	 */
	public string $shortcode_tag = 'metadata';

	/**
	 * Instance counter for unique wrapper IDs.
	 *
	 * @var int
	 */
	private static int $instance_count = 0;

	/**
	 * All recognized metadata element keys.
	 *
	 * @var string[]
	 */
	private array $known_elements = [
		'date', 'author', 'sticky', 'views', 'comments',
		'reading_time', 'modified', 'categories', 'tags',
	];

	/**
	 * Constructor — registers shortcode and admin hooks.
	 *
	 * @param array $args Unused; kept for back-compat.
	 */
	public function __construct( array $args = [] ) {
		add_shortcode( $this->shortcode_tag, [ $this, 'shortcode_handler' ] );

		if ( is_admin() ) {
			add_action( 'admin_head', [ $this, 'admin_head' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		}
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed content (unused).
	 * @return string               Rendered HTML.
	 */
	public function shortcode_handler( $atts, ?string $content = null ): string {

		$settings = get_option( 'dpm_settings', [] );

		$default_elements = ! empty( $settings['default_elements'] )
			? implode( ',', (array) $settings['default_elements'] )
			: 'date,author,comments';

		$atts = shortcode_atts(
			[
				'element'       => $default_elements,
				'design'        => $settings['design_variation'] ?? 'default',
				'text_color'    => '',
				'bg_color'      => '',
				'border_color'  => '',
				'icon_color'    => '',
				'font_size'     => '',
				'icon_size'     => '',
				'border_radius' => '',
			],
			$atts,
			$this->shortcode_tag
		);

		$elements = array_map( 'trim', explode( ',', $atts['element'] ) );
		$elements = array_filter( $elements );
		$design   = sanitize_html_class( $atts['design'] ?: 'default' );

		self::$instance_count++;
		$wrap_id = 'dpm-wrap-' . self::$instance_count;

		// Build per-instance CSS variable overrides.
		$inline_style = $this->build_inline_style( $atts );

		ob_start();

		printf(
			'<div id="%s" class="dpm-wrap dpm-design-%s"%s>',
			esc_attr( $wrap_id ),
			esc_attr( $design ),
			$inline_style ? ' style="' . esc_attr( $inline_style ) . '"' : ''
		);

		if ( $this->has_standard_elements( $elements ) ) {
			echo '<ul class="display-post-metadata">';
			foreach ( $elements as $element ) {
				$this->render_element( $element, $settings );
			}
			echo '</ul>';
		}

		if ( in_array( 'custom_fields', $elements, true ) ) {
			$this->render_custom_fields();
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render a single metadata element as a <li>.
	 *
	 * @param string $element  Element key.
	 * @param array  $settings Global plugin settings.
	 */
	private function render_element( string $element, array $settings ): void {

		$post_id = get_the_ID();

		switch ( $element ) {

			case 'date':
				echo '<li class="dpm-item date-meta">'
					. dpm_get_svg( 'date' )
					. '<span>' . esc_html( get_the_date() ) . '</span>'
					. '</li>';
				break;

			case 'modified':
				$modified = get_the_modified_date();
				if ( $modified && $modified !== get_the_date() ) {
					echo '<li class="dpm-item modified-meta">'
						. dpm_get_svg( 'modified' )
						. '<span>' . esc_html( $modified ) . '</span>'
						. '</li>';
				}
				break;

			case 'author':
				$show_avatar = ! empty( $settings['show_author_avatar'] );
				$avatar_size = intval( $settings['avatar_size'] ?? 32 );

				echo '<li class="dpm-item author-meta">';

				if ( $show_avatar ) {
					echo get_avatar(
						get_the_author_meta( 'ID' ),
						$avatar_size,
						'',
						esc_attr( get_the_author() ),
						[ 'class' => 'dpm-avatar' ]
					);
				} else {
					echo dpm_get_svg( 'user' );
				}

				echo '<span>' . esc_html( get_the_author() ) . '</span></li>';
				break;

			case 'sticky':
				if ( is_sticky() ) {
					echo '<li class="dpm-item sticky-meta">'
						. dpm_get_svg( 'sticky' )
						. '<span>' . esc_html__( 'Sticky', 'display-post-metadata' ) . '</span>'
						. '</li>';
				}
				break;

			case 'views':
				display_pmd_setPostViews( $post_id );
				echo '<li class="dpm-item views-meta">'
					. dpm_get_svg( 'eye' )
					. '<span>' . display_pmd_getPostViews( $post_id ) . '</span>'
					. '</li>';
				break;

			case 'comments':
				echo '<li class="dpm-item comment-meta">'
					. dpm_get_svg( 'comment' )
					. '<span>';
				comments_number(
					esc_html__( 'No Comments', 'display-post-metadata' ),
					esc_html__( 'one Comment', 'display-post-metadata' ),
					'% ' . esc_html__( 'Comments', 'display-post-metadata' )
				);
				echo '</span></li>';
				break;

			case 'reading_time':
				$minutes = $this->calculate_reading_time( $post_id, $settings );
				/* translators: %d: number of minutes */
				$label = sprintf( _n( '%d min read', '%d min read', $minutes, 'display-post-metadata' ), $minutes );
				echo '<li class="dpm-item reading-time-meta">'
					. dpm_get_svg( 'clock' )
					. '<span>' . esc_html( $label ) . '</span>'
					. '</li>';
				break;

			case 'categories':
				$cats = get_the_category( $post_id );
				if ( ! empty( $cats ) ) {
					$names = array_map( static fn( $c ) => esc_html( $c->name ), $cats );
					echo '<li class="dpm-item categories-meta">'
						. dpm_get_svg( 'category' )
						. '<span>' . implode( ', ', $names ) . '</span>'
						. '</li>';
				}
				break;

			case 'tags':
				$tags = get_the_tags( $post_id );
				if ( ! empty( $tags ) ) {
					$names = array_map( static fn( $t ) => esc_html( $t->name ), $tags );
					echo '<li class="dpm-item tags-meta">'
						. dpm_get_svg( 'tag' )
						. '<span>' . implode( ', ', $names ) . '</span>'
						. '</li>';
				}
				break;
		}
	}

	/**
	 * Calculate estimated reading time in minutes.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $settings Plugin settings.
	 * @return int            Minutes (minimum 1).
	 */
	private function calculate_reading_time( int $post_id, array $settings ): int {
		$wpm     = max( 1, intval( $settings['reading_speed'] ?? 200 ) );
		$content = get_post_field( 'post_content', $post_id );
		$content = wp_strip_all_tags( do_shortcode( $content ) );
		$words   = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
		$count   = is_array( $words ) ? count( $words ) : 0;

		return max( 1, (int) ceil( $count / $wpm ) );
	}

	/**
	 * Returns true when at least one element from the known list is requested.
	 * This gates rendering of the <ul>.
	 *
	 * @param string[] $elements Requested elements.
	 * @return bool
	 */
	private function has_standard_elements( array $elements ): bool {
		foreach ( $this->known_elements as $known ) {
			if ( in_array( $known, $elements, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the custom fields <ul>.
	 *
	 * @since 1.2.0
	 */
	private function render_custom_fields(): void {
		$keys = get_post_custom_keys();
		if ( ! $keys ) {
			return;
		}

		echo "<ul class='dpm-custom-fields'>\n";

		foreach ( (array) $keys as $key ) {
			if ( 'post_views_count' === $key ) {
				continue;
			}
			$keyt = trim( $key );
			if ( is_protected_meta( $keyt, 'post' ) ) {
				continue;
			}
			$values = array_map( 'trim', get_post_custom_values( $key ) );
			$value  = implode( ', ', $values );

			/**
			 * Filters the HTML output of a custom-field list item.
			 *
			 * @param string $html  Full <li> HTML.
			 * @param string $key   Meta key.
			 * @param string $value Meta value.
			 */
			echo apply_filters(
				'the_meta_key',
				"<li><strong class='post-meta-key'>" . esc_html( $key ) . ":</strong> <span class='meta-value'>" . esc_html( $value ) . "</span></li>\n",
				$key,
				$value
			);
		}

		echo "</ul>\n";
	}

	/**
	 * Build an inline style string of CSS custom property overrides.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string     CSS declarations (without style=""), or empty string.
	 */
	private function build_inline_style( array $atts ): string {
		$map = [
			'text_color'    => '--dpm-text-color',
			'bg_color'      => '--dpm-bg-color',
			'border_color'  => '--dpm-border-color',
			'icon_color'    => '--dpm-icon-color',
		];

		$vars = [];

		foreach ( $map as $att => $prop ) {
			if ( ! empty( $atts[ $att ] ) ) {
				$color = sanitize_hex_color( $atts[ $att ] );
				if ( $color ) {
					$vars[] = $prop . ':' . $color;
				}
			}
		}

		if ( ! empty( $atts['font_size'] ) && intval( $atts['font_size'] ) > 0 ) {
			$vars[] = '--dpm-font-size:' . intval( $atts['font_size'] ) . 'px';
		}
		if ( ! empty( $atts['icon_size'] ) && intval( $atts['icon_size'] ) > 0 ) {
			$vars[] = '--dpm-icon-size:' . intval( $atts['icon_size'] ) . 'px';
		}
		if ( isset( $atts['border_radius'] ) && $atts['border_radius'] !== '' ) {
			$vars[] = '--dpm-border-radius:' . intval( $atts['border_radius'] ) . 'px';
		}

		return implode( ';', $vars );
	}

	// -------------------------------------------------------------------------
	// Classic editor (TinyMCE) integration
	// -------------------------------------------------------------------------

	/**
	 * Hook into admin_head to register TinyMCE filters.
	 */
	public function admin_head(): void {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		if ( 'true' === get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', [ $this, 'mce_external_plugins' ] );
			add_filter( 'mce_buttons', [ $this, 'mce_buttons' ] );
		}
	}

	/** @param array $plugin_array */
	public function mce_external_plugins( array $plugin_array ): array {
		$plugin_array[ $this->shortcode_tag ] = DPM_PLUGIN_URL . 'js/mce-button.js';
		return $plugin_array;
	}

	/** @param array $buttons */
	public function mce_buttons( array $buttons ): array {
		$buttons[] = $this->shortcode_tag;
		return $buttons;
	}

	public function admin_enqueue_scripts(): void {
		wp_enqueue_style( 'dpm-mce-button', DPM_PLUGIN_URL . 'css/mce-button.css', [], DPM_VERSION );
	}

} // end class
