<?php

function display_pmd_getPostViews( $postID ){
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return '0 '. __( 'View', 'display-post-metadata');
    }
    return $count . ' ' . __( 'Views', 'display-post-metadata');
}
function display_pmd_setPostViews( $postID ) {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}

// Remove issues with prefetch adding extra views
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);