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

global $mbinfo_figure_db_version;
$mbinfo_figure_db_version = '1.0';


/**
 * Populate figure meta data from GCS objects, if not already in database.
 */
function populate_data() {
    $gcs = new Mbinfo_GcsObject();
    $mbinfo = new Mbinfo();
    $mbinfo->populate_batch_recursive($gcs, 0, '');
}

function mbinfo_figure_install() {
    global $mbinfo_figure_db_version;
    $mbinfo = new Mbinfo();
    $existing = get_site_option( 'mbinfo_figure_db_version' );
    error_log('MBInfoFigure: running mbinfo_figure_install ' . $existing . ' to ' . $mbinfo_figure_db_version);
    if ( $existing != $mbinfo_figure_db_version ) {
        $mbinfo->update_to_v1();
        update_option( 'mbinfo_figure_db_version', $mbinfo_figure_db_version );
    }
    populate_data();
}


function mbinfo_figure_enqueue_scripts() {
    $css_url = plugins_url('css/mbinfo-figure.css', __FILE__ );
    wp_enqueue_style('mbinfo-figure-css', $css_url, false, '1.0.0', 'screen');
}


/**
 * Register a new shortcode: [figure-box id="123"]
 */
add_shortcode('figure-box', 'mbinfo_figure_box');
// The callback function that will replace [book]
function mbinfo_figure_box($attr, $content)
{
    if (! isset($attr['id'])) {
        return 'Error: "id" attribute required in figure-box shortcode.';
    }
    $id = esc_attr($attr['id']);
    $size = 'medium';
    if (isset($attr['size'])) {
        $size = esc_attr($attr['size']);
        if (! in_array(array('small', 'medium', 'large'), $size)) {
            return 'Error: Invalid "size" attribute "' . $size . '"" in figure-box shortcode.';
        }
    }
    $bucket = Mbinfo_GcsObject::BUCKET;
    $prefix = '/figure/';
    $key = $prefix . $id . '.jpg';
    $image_origin = '//' . $bucket . '.storage.googleapis.com';
    $src = $image_origin . $key;
    $figure_url = '/figure/' . $id . '.jpg.html';

    $box_style = 'display: inline; margin: 5px 16px 0 0; float: left; clear: left;';
    return '<div class="figure-box" style="' . $box_style . '"><a href="' . $figure_url . '"><img border="0" src="' . $src . '" width="200" class="figure-img"></a><span style="display: block; width: 200px;"><span class="figure-title">Figure 1. Compartmentalization in cells:</span><span class="description">: Despite the morphological and functional variety of cells from&nbsp;different tissue types and different organisms, all cells share&nbsp;important similarities in their compartmental organization.&nbsp;These fundamental compartments, often referred to as&nbsp;organelles, are summarized in the drawing of the generic animal&nbsp;cell (central cell). Examples of specialized cell types, shown&nbsp;around the generic cell, include neuron, macrophage,&nbsp;intestine epithelial cell, adipocyte, muscle cell and&nbsp;osteoclast.</span></span></div>';
}
