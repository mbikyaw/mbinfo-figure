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
        global $MBINFO_TEST_DATA;

        $mbinfo = new Mbinfo();
        $mbinfo->max_call = 2;
        $mbinfo->max_result = '2';
        $mbinfo->update_to_v11();
        $mbinfo->clear_data();
        $gcs = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);
        $mbinfo->populate_batch_recursive($gcs, 0, '');

        $count = $wpdb->get_var('SELECT count(*) FROM ' . $mbinfo->table_name);
        $this->assertEquals(4, $count);

        $id = '1379301497033.jpg';
        $meta = $mbinfo->get_meta_data($id);
        $this->assertEquals('1379301497033.jpg', $id);
        $this->assertEquals('Kinesin schematic', $meta->title);
    }


}


