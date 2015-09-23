<?php
/**
 * Plugin Name: MBInfo Figure
 * Plugin URI: http://example.com/wordpress-plugins/my-plugin
 * Description: Create a figure content box in the text.
 * Version: 1.1
 * Author: Kyaw Tun
 * Author URI: http://mbinfo.mbi.nus.edu.sg
 * License: MIT
 */



require_once 'includes/GcsObject.php';
require_once 'includes/mbinfo.php';


register_activation_hook(__FILE__, 'mbinfo_figure_install');
add_action( 'wp_enqueue_scripts', 'mbinfo_figure_enqueue_scripts' );



/**
 * Populate figure meta data from GCS objects, if not already in database.
 */
function populate_data() {
    $gapi_key = get_option('mbinfo-figure-gapi-key');
    $gcs = new Mbinfo_GcsObject($gapi_key);
    $mbinfo = new Mbinfo();
    $mbinfo->populate_batch_recursive($gcs, 0, '');
}

function mbinfo_figure_install() {
    global $mbinfo_figure_db_version;
    $mbinfo = new Mbinfo();
    $existing = get_site_option( 'mbinfo_figure_db_version' );
    // error_log('MBInfoFigure: running mbinfo_figure_install ' . $existing . ' to ' . $mbinfo_figure_db_version);

}


function mbinfo_figure_enqueue_scripts() {
    $css_url = plugins_url('css/mbinfo-figure.css', __FILE__ );
    wp_enqueue_style('mbinfo-figure-css', $css_url, false, '1.0.0', 'screen');
}


function make_figure_box($id, $title, $desc, $size, $float)
{
    $bucket = Mbinfo_GcsObject::BUCKET;
    $prefix = '/figure/';
    $key = $prefix . $id . '.jpg';
    $image_origin = '//' . $bucket . '.storage.googleapis.com';
    $src = $image_origin . $key;
    $name = Mbinfo_GcsObject::idFromName($key);
    $figure_url = '/figure/' . $name . '/';

    if ($size == 'large') {
        $width = '600px';
    } else if ($size == 'medium') {
        $width = '400px';
    } else if ($size == 'small') {
        $width = '200px';
    } else if ($size == 'full') {
        $width = '100%';
    }
    if ($float == 'left') {
        $float = 'display: inline; margin: 5px 16px 0 0; float: left; clear: left;';
    } else if ($float == 'right') {
        $float = 'display: inline; margin: 5px 0 0 16px; float: right; clear: right;';
    } else if ($float == 'center') {
        $float = 'display: block; margin: 5px 16px; text-align: center;';
    } else {
        $float = 'display: block;';
    }
    $box_style = '' . $float . '';

    return '<div class="figure-box" style="' . $box_style . '"><a href="' . $figure_url . '"><img border="0" src="' . $src . '" width="' . $width . '" class="figure-img"></a><span style="text-align: left; display: block; width: ' . $width . ';"><span class="figure-title">Figure. ' . $title . '</span><span class="description">: ' . $desc . '</span></span></div>';
}


function mbinfo_figure_error_box($msg) {
    return '<div class="wpcf7-validation-errors">' . $msg . '</div>';
}


/**
 * Register a new shortcode: [figure-box name="123" position="left" size="small"]
 */
add_shortcode('figure-box', 'mbinfo_figure_box');
// The callback function that will replace [book]
function mbinfo_figure_box($attr, $content)
{

    if (! isset($attr['name'])) {
        return mbinfo_figure_error_box('Error: "name" attribute required in figure-box shortcode.');
    }
    $id = esc_attr($attr['name']);


    $size = 'small';


    $mbinfo = new Mbinfo();
    $fig = Mbinfo::get_figure($id);
    if (empty($fig)) {
        return mbinfo_figure_error_box('Error: Figure "' . $id . '"" not found');
    }
    $float = 'left';
    if (isset($attr['position'])) {
        $float = $attr['position'];
        if (! in_array($float, array('left', 'right', 'center'))) {
            return mbinfo_figure_error_box('Error: Invalid "size" attribute "' . $float . '"" in figure-box shortcode.');
        }
        if ($float == 'center') {
            $size = 'original';
        }
    }

    if (isset($attr['size'])) {
        $size = esc_attr($attr['size']);
        if (! in_array($size, array('small', 'medium', 'large'))) {
            return mbinfo_figure_error_box('Error: Invalid "size" attribute "' . $size . '"" in figure-box shortcode.');
        }
    }

    return make_figure_box($id, $fig->post_title, $fig->post_content, $size, $float);
}


add_action( 'init', 'mbinfo_register_figure_page' );


function mbinfo_register_figure_page() {
    register_post_type( 'figure', array(
            'labels' => array( 'name' => 'Figures'
            ),
            'rewrite' => array( 'slug' => 'figure', 'with_front' => false ),
            'public' => true, )
    );
}


/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function mbinfo_figure_meta_box() {

    $screens = array( 'figure' );

    foreach ( $screens as $screen ) {

        add_meta_box(
            'myplugin_sectionid',
            __( 'MBInfo Figure', 'mbinfo_figure_attr_date' ),
            'mbinfo_figure_meta_box_callback',
            $screen
        );
    }
}
add_action( 'add_meta_boxes', 'mbinfo_figure_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function mbinfo_figure_meta_box_callback( $post ) {

    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'mbinfo_figure_save_meta_box_data', 'mbinfo_figure_meta_box_nonce' );

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $value = get_post_meta( $post->ID, Mbinfo::$ATTR_DATE, true );

    echo '<label>Date ' .
        '<input type="text" id="' . Mbinfo::$ATTR_DATE . '" name="' . Mbinfo::$ATTR_DATE . '" value="' . $value . '"/></label>';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function mbinfo_figure_save_meta_box_data( $post_id ) {

    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    // Check if our nonce is set.
    if ( ! isset( $_POST['mbinfo_figure_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['mbinfo_figure_meta_box_nonce'], 'mbinfo_figure_save_meta_box_data' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if ( ! isset( $_POST['mbinfo_figure_attr_date'] ) ) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field( $_POST[Mbinfo::$ATTR_DATE] );

    // Update the meta field in the database.
    update_post_meta( $post_id, Mbinfo::$ATTR_DATE, $my_data );
}
add_action( 'save_post', 'mbinfo_figure_save_meta_box_data' );


if( defined( 'WP_CLI' ) && WP_CLI ) {
    include __DIR__ . '/includes/MbinfoFigureCliRunner.php';
}