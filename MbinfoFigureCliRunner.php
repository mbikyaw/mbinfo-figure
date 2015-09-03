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
        $gcs->maxResults = '2';
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
            $ok = $mbinfo->check_item_in_db($item, ['create' => false]);
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

}




WP_CLI::add_command( 'mbi-figure', 'MbinfoFigureCliRunner' );