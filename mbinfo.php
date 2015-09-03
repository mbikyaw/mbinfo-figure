<?php
/**
 * A Helper function.
 * User: mbikyaw
 * Date: 2/9/15
 * Time: 2:22 PM
 */



global $mbinfo_figure_db_version;
$mbinfo_figure_db_version = '1.1';


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


    function update_to_v11() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
          id varchar(255) NOT NULL,
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


    /**
     * Get meta data of an figure id from the database.
     * @param string $id
     * @return object
     */
    function get_meta_data($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . $this->table_name . ' WHERE id = %s', $id));
    }

    function render_figure_copyright($attr, $content)
    {
        $copying = 'Modification, copying and distribution (commercial or non-commercial) of this image is strictly prohibited without written consent. Please contact MBInfo at <b>feedback@mechanobio.info</b> to request permission to use this image.';
        $title = 'Adherens junctions of hepatocytes';

        $created = $attr['created'];

        $id = $attr['id'];
        $has_ext = preg_match("/\.\w{2,4}$/", $id);
        if (!$has_ext) {
            $id = $id . '.jpg';
        }
        $bucket = Mbinfo_GcsObject::BUCKET;
        $prefix = '/figure/';
        $key = $prefix . $id;
        $image_origin = '//' . $bucket . '.storage.googleapis.com';
        $img_src = $image_origin . $key;

        return '<section class="figure" id="section-figure"><div class="copyrighted-figure"><h2>' .$title . '</h2><img src="' . $img_src .'"/><h3>Summary</h3><table cellpadding="2" class="figure-table"><tbody><tr><td>Title</td><td name="title">' . $title . '</td></tr><tr><td>Description</td><td name="description">' . $content . '</td></tr><tr><td>Date</td><td name="created">' . $created . '</td></tr><tr><td>Permission</td><td>' . $copying . '</td></tr></tbody></table><div class="citation-box"><details><summary>How to cite this page?</summary><div class="citation"><span class="author">MBInfo contributors.</span> <span class="title">Adherens junctions of hepatocytes. </span>In <span class="journal-title">MBInfo Wiki</span>, Retrieved 10/21/2014 from http://www.mechanobio.info/figure/figure/1384242402205.jpg.html</div></details></div></div></section>';
    }

}
