<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 2:04 PM
 */


require_once dirname( __FILE__ ) . '/../GcsObject.php';
require_once dirname( __FILE__ ) . '/../mbinfo.php';

class MBInfoFigureTest extends \PHPUnit_Framework_TestCase
{
    public function testPopulate() {
        global $wpdb;
        $mbinfo = new Mbinfo();
        $mbinfo->max_call = 2;
        $mbinfo->max_result = '2';
        $mbinfo->update_to_v1();
        $mbinfo->clear_data();
        $gcs = new \Mbinfo_GcsObject();
        $mbinfo->populate_batch_recursive($gcs, 0, '');

        $count = $wpdb->get_var('SELECT count(*) FROM ' . $mbinfo->table_name);
        $this->assertEquals(4, $count);
    }


}


