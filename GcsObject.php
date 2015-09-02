<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 11:58 AM
 */

require_once 'external/google-api-php-client/src/Google/autoload.php';

class Mbinfo_GcsObject
{
    protected $client;
    protected $storageService;
    const BUCKET = 'mbi-figure';
    const PREFIX = 'figure/';

    /**
     * Mbinfo_GcsObject constructor.
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName("MBInfoFigurePlugin");
        $this->client->setDeveloperKey("AIzaSyDj04_XpKvJbXm5SNVCZvyBnia7jwvY_6w");
        $this->storageService = new Google_Service_Storage($this->client);
    }

    /**
     * Get object meta data. MetaData has the followign fields:
     * 'author', 'created', 'description', 'title'
     * @param $obj_key
     * @return mixed
     */
    public function getMetaData($obj_key) {
        $object = $this->storageService->objects->get(self::BUCKET, $obj_key);
        return $object->getMetadata();
    }

    /**
     * @param array $optParams optional pageToken.
     * @return array of 'items' and 'pageToken'
     */
    public function listObjects($optParams) {
        $params = ['prefix' => self::PREFIX];
        if (!empty($optParams['pageToken'])) {
            $params['pageToken'] = $optParams['pageToken'];
        }
        if (!empty($optParams['maxResults'])) {
            $params['maxResults'] = $optParams['maxResults'];
        }
        $list = $this->storageService->objects->listObjects(self::BUCKET, $params);
        return ['items' => $list->getItems(), 'pageToken' => $list->getNextPageToken()];
    }
}