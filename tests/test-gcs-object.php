<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 2:04 PM
 */


require_once dirname(__FILE__) . '/../includes/GcsObject.php';


class GcsObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testMetaData() {
        global $MBINFO_TEST_DATA;
        $obj = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);
        $meta = $obj->getMetaData('figure/1390276498094.jpg');
        $this->assertEquals('Mechanobiology Institute', $meta['author']);
    }

    public function testListObject() {
        global $MBINFO_TEST_DATA;
        $obj = new \Mbinfo_GcsObject($MBINFO_TEST_DATA->mbinfoFigureGapiKey);
        $obj->maxResults = 2;
        $out = $obj->listObjects([]);
        $items = $out['items'];
        // var_dump($items);
        $this->assertEquals(2, count($items));
        $this->assertNotEmpty($out['pageToken']);
        $first_id = $items[0]['name'];
        $this->assertNotEmpty($first_id);

        $out2 = $obj->listObjects(['pageToken' => $out['pageToken']]);
        $items = $out2['items'];
        $this->assertEquals(2, count($items));
        $first_id_2 = $items[0]['name'];
        $this->assertNotEquals($first_id, $first_id_2);
    }
}


