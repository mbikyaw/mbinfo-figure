<?php

/**
 * Plugin Name: MbInfo Figure CLI Runner
 * Version: 1.0
 * Description: Load image file from Google Cloud Storage as WordPress figure page.
 * Author: Kyaw Tun
 * Author URI: http://mbinfo.mbi.nus.edu.sg
 */



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

    }


}

WP_CLI::add_command( 'mbi-figure', 'MbinfoFigureCliRunner' );