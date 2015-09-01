<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 2:04 PM
 */


require_once dirname( __FILE__ ) . '/../GcsObject.php';

class GcsObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeNegated() {
        $obj = new \GcsObject();
        $meta = $obj->getMetaData('figure/1390276498094.jpg');
        var_dump($meta);
        $this->assertEquals('Mechanobiology Institute', $meta['author']);
    }
}


