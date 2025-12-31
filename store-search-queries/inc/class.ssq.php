<?php

class SSQ
{

    private $table_name = "stored_search_queries";
    private $csv_delimiter = ",";

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('parse_query', [$this, 'save_search_query']);
        register_activation_hook(SSQ_PLUGIN_FILE, [$this, 'install']);
    }

    public function add_plugin_page()
    {
        add_submenu_page(
            'tools.php',
            'Download Search Queries',
            'Download Search Queries',
            'administrator',
            'tools.php?download=search_queries.csv'
        );
    }

    public function plugins_loaded()
    {
        global $pagenow;
        if (
            $pagenow == 'tools.php' &&
            isset($_GET['download'])  &&
            $_GET['download'] == 'search_queries.csv'
        ) {
            header("Content-type: application/x-msdownload");
            header("Content-Disposition: attachment; filename=search_queries.csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $this->generate_csv();

            exit();
        }
    }

    public function save_search_query($query_object)
    {
        if ($query_object->is_search()) {

            global $wpdb;

            $query = $query_object->query['s'];

            $wpdb->insert($wpdb->prefix . $this->table_name, array(
                'query' => $query,
                'lang' => apply_filters('wpml_current_language', NULL),
                'exported' => false,
                'created_at' => date("Y-m-d H:i:s")
            ));
        }
    }

    public function install()
    {
        global $wpdb;

        $sql = "CREATE TABLE {$wpdb->prefix}{$this->table_name} (
            id INT NOT NULL AUTO_INCREMENT,
            query VARCHAR(128) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            exported TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY id (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
    }

    private function generate_csv() : string
    {
        $data = $this->get_queries();

        $heading = '';

        if(is_array($data[0])) {
            $heading = implode($this->csv_delimiter, array_keys($data[0]));
        }

        $queries = '';

        foreach($data as $line) {
            if(is_array($line)) {
                foreach($line as $col) {
                    $queries .= $col . $this->csv_delimiter;
                }
            }
            $queries = rtrim($queries, ',') . PHP_EOL;
        }

        return $heading . PHP_EOL . $queries;
    }

    private function get_queries() : array
    {
        global $wpdb;

        $results = $wpdb->get_results( 
            $wpdb->prepare( "SELECT query, lang, created_at FROM {$wpdb->prefix}{$this->table_name} ORDER BY created_at DESC " ),
            ARRAY_A
        );

        return $results;
    }

}

new SSQ();
