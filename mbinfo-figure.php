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



require_once 'GcsObject.php';
require_once 'mbinfo.php';


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
    error_log('MBInfoFigure: running mbinfo_figure_install ' . $existing . ' to ' . $mbinfo_figure_db_version);

    populate_data();
}


function mbinfo_figure_enqueue_scripts() {
    $css_url = plugins_url('css/mbinfo-figure.css', __FILE__ );
    wp_enqueue_style('mbinfo-figure-css', $css_url, false, '1.0.0', 'screen');
}


function make_figure_box($id, $title, $desc, $size, $float)
{
    $bucket = Mbinfo_GcsObject::BUCKET;
    $prefix = '/figure/';
    $key = $prefix . $id;
    $image_origin = '//' . $bucket . '.storage.googleapis.com';
    $src = $image_origin . $key;
    $name = Mbinfo_GcsObject::idFromName($key);
    $figure_url = '/figure/' . $name . '/';

    $width = $size == 'large' ? '600px' : $size == 'medium' ? '400px' : '200px';
    if ($float == 'left') {
        $float = 'margin: 5px 16px 0 0; float: left;';
    } else if ($float == 'right') {
        $float = 'margin: 5px 0 0 16px; float: right;';
    } else {
        $float = '';
    }

    $box_style = 'display: inline; ' . $float . 'clear: left;';
    return '<div class="figure-box" style="' . $box_style . '"><a href="' . $figure_url . '"><img border="0" src="' . $src . '" width="' . $width . '" class="figure-img"></a><span style="display: block; width: ' . $width . ';"><span class="figure-title">Figure. ' . $title . '</span><span class="description">: ' . $desc . '</span></span></div>';
}


function mbinfo_figure_error_box($msg) {
    return '<div class="wpcf7-validation-errors">' . $msg . '</div>';
}


/**
 * Register a new shortcode: [figure-box name="123"]
 */
add_shortcode('figure-box', 'mbinfo_figure_box');
// The callback function that will replace [book]
function mbinfo_figure_box($attr, $content)
{

    if (! isset($attr['name'])) {
        return mbinfo_figure_error_box('Error: "name" attribute required in figure-box shortcode.');
    }
    $id = esc_attr($attr['name']);
    $has_ext = preg_match("/\.\w{2,4}$/", $id);
    if (!$has_ext) {
        $id = $id . '.jpg'; // append default extension
    }

    $size = 'small';
    if (isset($attr['size'])) {
        $size = esc_attr($attr['size']);
        if (! in_array($size, array('small', 'medium', 'large'))) {
            return mbinfo_figure_error_box('Error: Invalid "size" attribute "' . $size . '"" in figure-box shortcode.');
        }
    }

    $mbinfo = new Mbinfo();
    $meta = $mbinfo->get_meta_data($id);
    if (empty($meta)) {
        return 'Error: Figure "' . $id . '"" not found';
    }
    $float = 'left';
    if (isset($attr['float'])) {
        $float = $attr['float'];
        if (! in_array($float, array('left', 'right'))) {
            return mbinfo_figure_error_box('Error: Invalid "size" attribute "' . $float . '"" in figure-box shortcode.');
        }
    }

    return make_figure_box($id, $meta->title, $meta->description, $size, $float);
}

if( defined( 'WP_CLI' ) && WP_CLI ) {
    include __DIR__ . '/MbinfoFigureCliRunner.php';
}