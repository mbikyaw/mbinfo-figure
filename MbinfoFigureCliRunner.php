<?php

/**
 * Plugin Name: MbInfo Figure CLI Runner
 * Version: 1.0
 * Description: Load image file from Google Cloud Storage as WordPress figure page.
 * Author: Kyaw Tun
 * Author URI: http://mbinfo.mbi.nus.edu.sg
 */

require_once 'GcsObject.php';
require_once 'mbinfo.php';

global $MBINFO_TEST_DATA;
$json = file_get_contents(__DIR__ . "/credentials.json");
$MBINFO_TEST_DATA = json_decode($json);


class MbinfoFigureCliRunner extends WP_CLI_Command
{
    /**
     * Prints figure statistic.
     *
     *
     * ## EXAMPLES
     *
     *     wp mbi-figure statistic
     *
     * @synopsis
     */
    function statistic( $args, $assoc_args ) {
        global $MBINFO_TEST_DATA;
        $gcs = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);

        $out = $gcs->listObjects([]);
        $items = $out['items'];
        $cnt = count($items);
        $bucket = Mbinfo_GcsObject::BUCKET;
        WP_CLI::line( "$cnt images in GCS bucket $bucket" );
        $mbinfo = new Mbinfo();
        $error = 0;
        $count = 0;
        foreach($items as $item) {
            $ok = $mbinfo->check_item_in_db($item, []);
            if ($ok == 'ok') {
                $count++;
            } else {
                WP_CLI::line( "Figure " . $item['name'] . ' ' . $ok);
                $error++;
            }
        }

        if ($error > 0) {
            WP_CLI::line( $error . ' not in figure pages.');
        }
        WP_CLI::success( "Done!" );
    }

    /**
     * Load images files meta data to wordpress.
     *
     * ## OPTIONS
     *
     * <create>
     * : Create a new figure page if not exists.
     *
     * ## EXAMPLES
     *
     *     wp mbi-figure load
     *
     * @synopsis [--create]
     */
    function load( $args, $assoc_args ) {

        global $MBINFO_TEST_DATA;
        $gcs = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);

        $out = $gcs->listObjects([]);
        $items = $out['items'];
        $cnt = count($items);
        $bucket = Mbinfo_GcsObject::BUCKET;
        WP_CLI::line( "$cnt images in GCS bucket $bucket" );
        $mbinfo = new Mbinfo();
        $error = 0;
        $count = 0;
        $created = 0;
        foreach($items as $item) {
            $ok = $mbinfo->check_item_in_db($item, ['create' => true]);
            if ($ok == 'created') {
                $created++;
            } else if ($ok == 'ok') {
                $count++;
            } else {
                WP_CLI::line( "Figure " . $item['name'] . ' ' . $ok);
                $error++;
            }
        }
        if ($created > 0) {
            WP_CLI::line( $created . ' figure pages created.');
        }
        if ($error > 0) {
            WP_CLI::line( $error . ' not in figure pages.');
        }
        WP_CLI::success( "Done!" );
    }


    /**
     * Clean figure pages.
     *
     * ## OPTIONS
     *
     * <dry-run>
     * : Dry run instead of actual deleting figure pages.
     *
     * ## EXAMPLES
     *
     *     wp mbi-figure clean
     *
     * @synopsis [--dry-run]
     */
    function clean( $args, $assoc_args ) {
        global $wpdb;
        global $MBINFO_TEST_DATA;
        $dry_run = $assoc_args['dry-run'];
        $gcs = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);
        $gcs->maxResults = '1000';
        $out = $gcs->listObjects([]);
        $items = $out['items'];
        $cnt = count($items);
        $bucket = Mbinfo_GcsObject::BUCKET;
        WP_CLI::line( "$cnt images in GCS bucket $bucket" );
        $ids = array_map(function($item) {
            return Mbinfo_GcsObject::idFromName($item['name']);
        }, $items);

        $figures = $wpdb->get_results("SELECT id, post_name FROM $wpdb->posts WHERE post_type = 'figure'", ARRAY_A);
        $cnt = count($figures);
        WP_CLI::line( "$cnt figure pages" );

        $deleted = 0;
        foreach ($figures as $fig) {
            $name = $fig['post_name'];
            $id = $fig['id'];
            if (!in_array($name, $ids)) {
                if ($dry_run) {
                    WP_CLI::line( "To delete figure $id: $name " );
                } else {
                    WP_CLI::line( "Deleting figure $id: $name " );
                    wp_delete_post($id);
                }
                $deleted++;
            }
        }
        if ($deleted) {
            WP_CLI::line( "$deleted figures deleted." );
        }
        WP_CLI::success( "Done!" );
    }

}




WP_CLI::add_command( 'mbi-figure', 'MbinfoFigureCliRunner' );