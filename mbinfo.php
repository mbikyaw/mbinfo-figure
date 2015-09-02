<?php
/**
 * A Helper function.
 * User: mbikyaw
 * Date: 2/9/15
 * Time: 2:22 PM
 */

class Mbinfo {

    public $table_name;
    /**
     * @var int maximun number of recurssive call to GCS request during object listing.
     */
    public $max_call = 10;
    public $max_result = '1000';

    /**
     * Mbinfo constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mbinfofigure';
    }


    function update_to_v1() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
          id varchar(24) NOT NULL,
          updated mediumint(14),
          created text DEFAULT '',
          title tinytext NOT NULL,
          description text DEFAULT '',
          author text DEFAULT '',
          UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    function clear_data() {
        global $wpdb;
        $wpdb->query('Truncate table ' . $this->table_name);
    }


    /**
     * Insert GCS meta data into database, if not already exist.
     * @param array $items
     */
    function insert_meta_data($items) {
        global $wpdb;
        foreach($items as $item) {
            $id = substr($item['name'], strlen(Mbinfo_GcsObject::PREFIX));
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE id = %s", $id));

            if (!empty($existing)) {
                continue;
            }
            $meta = $item->getMetadata();
            $wpdb->insert(
                $this->table_name,
                [
                    'id' => $id,
                    'updated' => round(microtime(true)*1000),
                    'created' => $meta['created'],
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'author' => $meta['author']
                ]
            );
        }
    }


    /**
     * @param Mbinfo_GcsObject $gcs
     * @param number $count
     * @param string $pageToken
     */
    function populate_batch_recursive($gcs, $count, $pageToken) {
        $out = $gcs->listObjects(['pageToken' => $pageToken,
            'maxResults' => $this->max_result]);
        $this->insert_meta_data($out['items']);
        $count++;
        if ($count < $this->max_call && !empty($out['pageToken'])) {
            $this->populate_batch_recursive($gcs, $count, $out['pageToken']);
        }
    }

}
