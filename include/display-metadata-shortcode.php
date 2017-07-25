<?php
/**
 * Display_Metadata_Shortcode
 */
class Display_Metadata_Shortcode{
    /**
     * $shortcode_tag
     * holds the name of the shortcode tag
     * @var string
     */
    public $shortcode_tag = 'metadata';

    /**
     * __construct
     * class constructor will set the needed filter and action hooks
     *
     * @param array $args
     */
    function __construct($args = array()){

        //add shortcode
        add_shortcode( $this->shortcode_tag, array( $this, 'shortcode_handler' ) );

        if ( is_admin() ){
            add_action('admin_head', array( $this, 'admin_head') );
            add_action( 'admin_enqueue_scripts', array($this , 'admin_enqueue_scripts' ) );
        }
    }

    /**
     * shortcode_handler
     * @param  array  $atts shortcode attributes
     * @param  string $content shortcode content
     * @return string
     */
    function shortcode_handler( $atts , $content = null ) {

        ob_start();

        $elements  = explode( ',', $atts['element'] );

        echo '<div id="dpm-wrap">';

        if ( $this->meta_element_exists( $elements ) ) {

            echo '<ul class="display-post-metadata">';

            foreach( $elements as $element ) {

                switch( $element ) {
                    case 'date':
                        echo '<li class="date-meta"><img src="'. plugin_dir_url( dirname( __FILE__ ) ) .'svg/date.svg" alt="date"><span>'. get_the_date() .'</span></li>';
                        break;
                    case 'author':
                        echo '<li class="author-meta"><img src="'. plugin_dir_url( dirname( __FILE__ ) ) .'svg/user.svg" alt="user"><span>'. get_the_author() .'</span></li>';
                        break;
                    case 'sticky':
                        if ( is_sticky() ) { echo '<li class="sticky-meta"><img src="'. plugin_dir_url( dirname( __FILE__ ) ) .'svg/sticky.svg" alt="sticky"><span>'. __( 'Sticky', 'display-post-metadata') .'</span></li>';  }
                        break;
                    case 'views':
                        display_pmd_setPostViews( get_the_ID() );
                        echo '<li class="views-meta"><img src="'. plugin_dir_url( dirname( __FILE__ ) ) .'svg/eye.svg" alt="view"><span>'. display_pmd_getPostViews( get_the_ID() ) .'</span></li>';
                        break;
                    case 'comments':
                        echo '<li class="comment-meta"><img src="'. plugin_dir_url( dirname( __FILE__ ) ) .'svg/comment.svg" alt="comment"><span>';
                        comments_number( __( 'No Comments', 'display-post-metadata'), __( 'one Comment', 'display-post-metadata'), '% ' . __( 'Comments', 'display-post-metadata') );
                        echo '</span></li>';
                        break;
                }
            }

            echo '</ul>';
        }

        ( in_array( 'custom_fields', $elements ) ) ? $this->custom_fields() : '';

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Returns bool whether metadata element exists or not
     *
     * @since 1.2.0
     *
     * @param array
     * @return bool
     */
    public function meta_element_exists( $elements ) {

        $defaults = array( 'date', 'author', 'sticky', 'views', 'comments' );

        foreach ( $defaults as $default ) {

            if ( in_array( $default, $elements ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Display list of post custom fields.
     *
     * @since 1.2.0
     *
     * @internal This will probably change at some point...
     */
    public function custom_fields() {
        if ( $keys = get_post_custom_keys() ) {
            echo "<ul class='dpm-custom-fields'>\n";
            foreach ( (array) $keys as $key ) {

                if ( 'post_views_count' == $key )
                    continue;

                $keyt = trim($key);
                if ( is_protected_meta( $keyt, 'post' ) )
                    continue;
                $values = array_map('trim', get_post_custom_values( $key ) );
                $value = implode($values,', ');

                /**
                 * Filters the HTML output of the li element in the post custom fields list.
                 *
                 * @param string $html  The HTML output for the li element.
                 * @param string $key   Meta key.
                 * @param string $value Meta value.
                 */
                echo apply_filters( 'the_meta_key', "<li><strong class='post-meta-key'>$key:</strong> <span class='meta-value'>$value</span> </li>\n", $key, $value );
            }
            echo "</ul>\n";
        }
    }

    /**
     * admin_head
     * calls your functions into the correct filters
     * @return mixed
     */
    function admin_head() {
        // check user permissions
        if ( ! current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
            return;
        }

        // check if WYSIWYG is enabled
        if ( 'true' == get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
            add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
        }
    }

    /**
     * mce_external_plugins
     * Adds our tinymce plugin
     * @param  array $plugin_array
     * @return array
     */
    function mce_external_plugins( $plugin_array ) {
        $plugin_array[$this->shortcode_tag] = plugins_url( 'js/mce-button.js' , dirname( __FILE__ ) );
        return $plugin_array;
    }

    /**
     * mce_buttons
     * Adds our tinymce button
     * @param  array $buttons
     * @return array
     */
    function mce_buttons( $buttons ) {
        array_push( $buttons, $this->shortcode_tag );
        return $buttons;
    }

    /**
     * admin_enqueue_scripts
     * Used to enqueue custom styles
     * @return void
     */
    function admin_enqueue_scripts(){
        wp_enqueue_style('display_metadata_shortcode', plugins_url( 'css/mce-button.css' , dirname( __FILE__ ) ) );
    }

}//end class
