<?php
/**
 * Created by PhpStorm.
 * User: mbikyaw
 * Date: 1/9/15
 * Time: 11:58 AM
 */

require_once __DIR__ . '/../external/google-api-php-client/src/Google/autoload.php';

class Mbinfo_GcsObject
{
    protected $client;
    protected $storageService;
    public $maxResults = '1000';
    const BUCKET = 'mbi-figure';
    const PREFIX = 'figure/';

    /**
     * Mbinfo_GcsObject constructor.
     * @param $gapi_key
     */
    public function __construct($gapi_key)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName("MBInfoFigurePlugin");
        $this->client->setDeveloperKey($gapi_key);
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
        $params = ['prefix' => self::PREFIX, 'maxResults' => $this->maxResults];
        if (!empty($optParams['pageToken'])) {
            $params['pageToken'] = $optParams['pageToken'];
        }
        $list = $this->storageService->objects->listObjects(self::BUCKET, $params);
        return ['items' => $list->getItems(), 'pageToken' => $list->getNextPageToken()];
    }


    /**
     * @param string $name GCS object name
     * @return string figure post name.
     */
    static public function idFromName($name) {
        $id = substr($name, strlen(Mbinfo_GcsObject::PREFIX));
        return preg_replace("/\.\w{2,4}$/", '', $id);
    }
}