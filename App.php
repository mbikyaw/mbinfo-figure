<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 3:35 PM
 */

require_once 'GcsObject.php';

$gcs = new GcsObject();
$meta = $gcs->getMetaData('figure/1390276498094.jpg');
var_dump($meta);
echo $meta['description'];
