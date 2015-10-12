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
$json = file_get_contents(__DIR__ . "/../credentials.json");
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
        $ids = array_map(function($item) {
            return Mbinfo_GcsObject::idFromName($item['name']);
        }, $items);
        WP_CLI::line( "$cnt images in GCS bucket $bucket" );

        $error = 0;
        $count = 0;
        $no_img = 0;
        foreach($items as $item) {
            $fig_name = Mbinfo_GcsObject::idFromName($item['name']);
            $fig = Mbinfo::get_figure($fig_name);
            if ($fig) {
                $count++;
            } else {
                WP_CLI::line( "Figure for GCS object " . $item['name'] . ' missing.');
                $error++;
            }
        }

        $fig_names = Mbinfo::list_figure_names();
        foreach ($fig_names as $name) {
            if (! in_array($name, $ids)) {
                WP_CLI::line( "Figure " . $name . ' does not have image in GCS.');
                $no_img++;
            }
        }

        if ($error > 0) {
            WP_CLI::line( $error . ' images not in figure pages.');
        }
        if ($no_img > 0) {
            WP_CLI::line( $no_img . ' invalid figure pages.');
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
            $fig_name = Mbinfo_GcsObject::idFromName($item['name']);
            $fig = Mbinfo::get_figure($fig_name);
            if (empty($fig)) {
                $fig_id = Mbinfo::insert_figure_from_gcs($item);
                if (empty($fig_id)) {
                    WP_CLI::line( "Inserting Figure " . $fig_name. ' fail.');
                    $error++;
                } else {
                    $created++;
                }
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
     * Query info.
     *
     * ## OPTIONS
     *
     * <figure>
     * : Figure page slug.
     *
     * ## EXAMPLES
     *
     *     wp mbinfo-figure info referred --figure=12232
     *
     * @synopsis <method> [--figure]
     */
    function info( $args, $assoc_args ) {
        $mth = $args[0];
        if ($mth == 'referred') {
            $figure = $assoc_args['figure'];
            if (empty($figure)) {
                WP_CLI::error( "figure required.");
                return;
            }
            $mbinfo = new Mbinfo();
            $fig = $mbinfo->get_figure($figure);
            if (empty($fig)) {
                WP_CLI::error( "figure " . $figure . " not found.");
                return;
            }
            $list = $mbinfo->list_referred_page($figure);
            var_dump($list);
            WP_CLI::success(count($list) . " referred pages found for " . $fig->post_title);
        } else {
            WP_CLI::error( "unknown method ");
        }
    }


    /**
     * Clean figure pages.
     *
     * ## OPTIONS
     *
     * <dry-run>
     * : Dry run instead of actual deleting figure pages.
     *
     * <purge-all>
     * : Purge all figure pages.
     *
     * ## EXAMPLES
     *
     *     wp mbi-figure clean
     *
     * @synopsis [--dry-run] [--purge-all]
     */
    function clean( $args, $assoc_args ) {
        global $wpdb;
        global $MBINFO_TEST_DATA;
        $dry_run = $assoc_args['dry-run'];
        $purge_all = $assoc_args['purge-all'];
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
            if ($purge_all) {
                WP_CLI::line( "Deleting figure $id: $name " );
                wp_delete_post($id);
                $deleted++;
            } else if (!in_array($name, $ids)) {
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


WP_CLI::add_command( 'mbinfo-figure', 'MbinfoFigureCliRunner' );