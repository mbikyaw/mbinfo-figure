<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 11:58 AM
 */

require_once 'external/google-api-php-client/src/Google/autoload.php';

class GcsObject
{
    protected $client;
    protected $storageService;
    const BUCKET = 'mbi-figure';

    /**
     * GcsObject constructor.
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName("MBInfoFigurePlugin");
        $this->client->setDeveloperKey("AIzaSyDj04_XpKvJbXm5SNVCZvyBnia7jwvY_6w");
        $this->storageService = new Google_Service_Storage($this->client);
    }

    /**
     * Get object meta data.
     * @param $obj_key
     * @return mixed
     */
    public function getMetaData($obj_key) {
        $object = $this->storageService->objects->get(self::BUCKET, $obj_key);
        return $object->getMetadata();
    }

    public function getAmount()
    {
        return '10';
    }
}