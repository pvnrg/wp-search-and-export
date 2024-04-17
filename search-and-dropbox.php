<?php
/**
* Plugin Name: Wp Search and Dropbox
* Plugin URI: http://example.com
* Description: wp search and dropbox.
* Version: 0.1
* Author: Pavan
* Author URI: http://example.com
**/
define( 'PVN__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PVN_DROPBOX_API_KEY', '0u36zvxcpy73sat' );
define( 'PVN_DROPBOX_SECRET_KEY', 'e095sj6ujshevhi' );
define( 'PVN_DROPBOX_REDIRECT_URL', 'https://reviewnprep.com/imp/' );

// Enqueue necessary scripts and styles
function search_and_dropbox_plugin_enqueue_scripts($hook) {
    if ($hook == 'toplevel_page_search-and-dropbox') {
    	wp_enqueue_style('fontawesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
        wp_enqueue_script('search-and-dropbox-plugin-scripts', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '1.0', true);
        wp_enqueue_script( 'search-and-dropbox-custom-script', plugin_dir_url( __FILE__ ) . 'js/custom.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style('search-and-dropbox-plugin-styles', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
        wp_enqueue_style('custom-plugin-styles', plugin_dir_url(__FILE__) . 'styles.css');
    
         wp_localize_script( 'search-and-dropbox-custom-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }
}
add_action('admin_enqueue_scripts', 'search_and_dropbox_plugin_enqueue_scripts');

if ( is_admin() ) {
	require_once( PVN__PLUGIN_DIR . 'admin-page.php' );
} else {
	require_once( PVN__PLUGIN_DIR . 'dropbox-functions.php' );
}

