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
    public static $ATTR_DATE = 'mbinfo_figure_meta_date';

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
     * @param string $name figure page name (slug)
     * @return object
     */
    static function get_figure($name) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'figure' AND post_name = '%s' AND post_status = 'publish' LIMIT %d OFFSET %d", $name);
        return $wpdb->get_row($sql);
    }


    /**
     * Get meta data of an figure id from the database.
     * @param int $limit limit
     * @param int $offset offset
     * @return array
     */
    static function list_figure($limit = 1000, $offset = 0) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'figure' AND post_status = 'publish' LIMIT %d OFFSET %d", $limit, $offset);
        return $wpdb->get_results($sql, ARRAY_A);
    }

    static function list_figure_names() {
        global $wpdb;
        return $wpdb->get_col("SELECT post_name FROM $wpdb->posts WHERE post_type = 'figure'");
    }

<<<<<<< HEAD
    function get_img_src($id) {
=======
    function render_figure_copyright($attr, $content)
    {
        $copying = 'Modification, copying and distribution (commercial or non-commercial) of this image is strictly prohibited without written consent. Please contact MBInfo at <b>feedback@mechanobio.info</b> to request permission to use this image.';
        $title = $attr['title'];

        $created = $attr['created'];

        $id = $attr['name'];
>>>>>>> 2c2032f7677d38f007634670a49efe8092d861fd
        $has_ext = preg_match("/\.\w{2,4}$/", $id);
        if (!$has_ext) {
            $id = $id . '.jpg';
        }
        $bucket = Mbinfo_GcsObject::BUCKET;
        $prefix = '/figure/';
        $key = $prefix . $id;
        $image_origin = '//' . $bucket . '.storage.googleapis.com';
        $img_src = $image_origin . $key;
<<<<<<< HEAD
        return $img_src;
    }

    function render_figure_copyright($attr, $content)
    {
        $copying = 'Modification, copying and distribution (commercial or non-commercial) of this image is strictly prohibited without written consent. Please contact MBInfo at <b>feedback@mechanobio.info</b> to request permission to use this image.';
        $title = $attr['title'];

        $created = $attr['created'];
        $img_src = $this->get_img_src($attr['name']);
=======
>>>>>>> 2c2032f7677d38f007634670a49efe8092d861fd
        $url = 'http://mbinfo.mbi.nus.edu.sg/figure/' . $attr['name'] . '/';

        return '<section class="figure" id="section-figure"><div class="copyrighted-figure"><img src="' . $img_src .'"/><h3>Summary</h3><table cellpadding="2" class="figure-table"><tbody><tr><td>Title</td><td name="title">' . $title . '</td></tr>
        <tr><td>Description</td><td name="description">' . $content . '</td></tr>
        <tr><td>Date</td><td name="created">' . $created . '</td></tr>
        <tr><td>Permission</td><td>' . $copying . '</td>
        </tr></tbody></table><div class="citation-box"><details><summary>How to cite this page?</summary><div class="citation"><span class="author">MBInfo contributors.</span> <span class="title">' . $title . '. </span>In <span class="journal-title">MBInfo Wiki</span>, Retrieved 10/21/2014 from ' . $url . '</div></details></div></div></section>';
    }


    /**
     * Insert figure page from GCS object.
     * @param $item GCS Object.
     * @return string return post id. empty string if fail.
     */
    static public function insert_figure_from_gcs($item) {
        $meta = $item->getMetadata();
        $name = Mbinfo_GcsObject::idFromName($item['name']);
        if (empty($meta)) {
            $meta = ['title' => $name];
        }
        $post = [
            'post_content' => array_key_exists('description', $meta) ? $meta['description'] : '',
            'post_name' => $name,
            'post_title' => array_key_exists('title', $meta) ? $meta['title'] : '',
            'post_status' => 'publish',
            'post_type' => 'figure'
        ];
        $id = wp_insert_post($post, true);
        if (is_wp_error($id)) {
            var_dump($id);
            return '';
        } else {
            if (array_key_exists('created', $meta)) {
                update_post_meta( $id, self::$ATTR_DATE, $meta['created'] );
            }
        }
        return $id;

    }

}
