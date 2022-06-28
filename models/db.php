<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document contains all the integrations with the 
 * app's MySQL database.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

class DataAccess {
    // Set the records per page.
    private const ITEMS_PER_PAGE = 10;

    // Connect to MySQL.
    private static $conn = null;
    private static function connect() {
        if (self::$conn) {
            return self::$conn;
        } else {
            self::$conn = new mysqli($GLOBALS['DB_HOST'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], $GLOBALS['DB_NAME'],  $GLOBALS['DB_PORT'],  $GLOBALS['DB_SOCKET']);
            if(self::$conn->connect_error){
                throw new Exception('Cannot connect to database: ' . self::$conn->connect_error);
            }
            return self::$conn;
        }
    }
 
    /**
     * Query helper.
     *
     * @param string $sql The query SQL.
     * @param array $params The query parameters to bind.
     * @param boolean $return If we're expecting a result.
     * @return mysqli_result|boolean
     */
    private static function query($sql, $params, $return) {
        $connection = self::connect();
        if (empty($params)) {
            $results = $connection->query($sql);
        } else {
            $statement = $connection->prepare($sql);
            if ($statement === false) {
                throw new Exception('Unable to prepare SQL: ' . $connection->error);
            }
            $statement->bind_param(str_repeat('s', count($params)), ...$params);
            $results = $statement->execute();
            if ($return) {
                $results = $statement->get_result();
            }
        }

        return $results;
    }

    /**
     * Get Sites
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_sites($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `sites`';
        $params = array();

        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ' ORDER BY `url`;';

    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    
    }

    /**
     * Get Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value, 'page' => $page) ]
     * @param string page
     */
    public static function get_alerts($filters = [], $page = 1){

        // Set alerts per page.
        $alerts_per_page = self::ITEMS_PER_PAGE;
        $page_offset = ($page-1) * $alerts_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = "SELECT COUNT(*) FROM `alerts`";

        // Create 'content' SQL.
        $content_sql = "SELECT * FROM `alerts`";
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }

        // Add filters and page limit.
        $total_pages_sql.= $filters_sql;
        $content_sql.= $filters_sql.' LIMIT '.$page_offset.', '.$alerts_per_page;

        // Run 'total_pages' SQL.
        $total_pages_result = self::query($total_pages_sql, $params, true);
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $alerts_per_page);
    
        // Run 'content' SQL
        $content_results = self::query($content_sql, $params, true);
        $content = [];
        if($content_results->num_rows > 0){
            while($row = $content_results->fetch_object()){
                $content[] = $row;
            }
        }
    
        // Create and return data.
        $data = [
            'total_alerts' => $total_pages_rows,
            'total_pages' => $total_pages,
            'content' => $content
        ];
        return $data;

    }

    /**
     * Count Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value, 'page' => $page) ]
     * @param string page
     */
    public static function count_alerts($filters = []){

        // SQL
        $sql = 'SELECT COUNT(*) AS TOTAL FROM `alerts`';
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }
        $sql.= $filters_sql;


        // Query
        $results = self::query($sql, $params, true);

        // Result
        $data = $results->fetch_object()->TOTAL;
        return $data;

    }

    /**
     * Get Alerts By Site
     */
    public static function get_alerts_by_site($site){
    
        // SQL
        $sql = 'SELECT * FROM `alerts` WHERE `site` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;

        // Close
        $results->close();
    
    }

    /**
     * Get Meta
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_meta($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `meta`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    }
    
    /**
     * Get Meta Value
     */
    public static function get_meta_value($meta_name){

        // SQL
        $sql = 'SELECT `meta_value` FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $results = self::query($sql, $params, true);

        // Returns meta_value.
        $data = $results->fetch_object();
        if(empty($data)){
            return false;
        }else{
            return $data->meta_value;
        }

    }
    
    /**
     * Get Site Status
     */
    public static function get_site_status($site){
    
        // SQL
        $sql = 'SELECT `status` FROM `sites` WHERE `url` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->status;
        return $data;
        
    }

    /**
     * Get Site Type
     */
    public static function get_site_type($url){
    
        // SQL
        $sql = 'SELECT `type` FROM `sites` WHERE `url` = ?';
        $params = array($url);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->type;
        return $data;
        
    }
    
    /**
     * Get Column Names
     */
    public static function get_column_names($table){
    
        // SQL
        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ';
        $sql.= 'WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?';
        $params = array($table, $GLOBALS['DB_NAME']);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    
    }
    
    
    /**
     * Is Unique Site
     */
    public static function is_unique_site($url){
    
        // Require unique URL
        $sql = 'SELECT * FROM `sites` WHERE `url` = ?';
    
        // We don't consider a page with a '/' a unique url
        // so we will also search for them.
        // Possible injection point:
        // INSERT INTO `equalify`.`meta` (`usage`, `wave_key`) VALUES ('1', 'c');
        $sql.= ' OR `url` = ?';
    
        $params = array($url, $url . '/');
        $results = self::query($sql, $params, true);
        if($results->num_rows > 0){
            return false;
        }else{
            return true;
        }
    
    }
    
    /**
     * Add Site
     */
    public static function add_site($url, $type, $status){
    
        // SQL
        $sql = 'INSERT INTO `sites` (`url`, `type`, `status`) VALUES';
        $sql.= '(?, ?, ?, ?)';
        $params = array($url, $type, $status);
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page with values "'.$url.',"'.$url.',"'.$type.',"'.$status.'"');
        
        // Complete Query
        return $result;
    }
    

    /**
     * Update Page Site Status 
     */
    public static function update_site_status($url, $new_status){
    
        // SQL
        $sql = 'UPDATE `sites` SET `status` = ? WHERE `url` = ?';
        $params = array($new_status, $url);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
    }
    
    /**
     * Add DB Column
     */
    public static function add_db_column($table, $column_name, $column_type){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'ADD COLUMN '.$column_name.' '.$column_type.';';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot add "'.$column_name.'" to "'.$table.'" with type "'.$column_type.'"');
    
    }
    
    /**
     * Add DB Column
     */
    public static function delete_db_column($table, $column_name){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'DROP COLUMN '.$column_name.';';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot drop "'.$column_name.'" to "'.$table.'"');
    
    }
    
    /**
     * DB Column Exists
     */
    public static function db_column_exists($table, $column_name){
    
        // SQL.
        $sql = 'SELECT '.$column_name.' FROM `'.$table.'` ';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result){
            return false;   
        }else{
            return true;
        }
    
    }
    
    /**
     * Delete Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function delete_alerts($filters = []){
    
        // SQL
        $sql = 'DELETE FROM `alerts`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
    }
    
    /**
     * Add Alert
     */
    public static function add_alert($source, $url, $integration_uri = NULL, $type = 'error', $message = NULL, $meta = NULL){
        
        // Sanitize items.
        $message = htmlspecialchars($message, ENT_NOQUOTES);
        if(is_array($meta)){
            $meta = htmlspecialchars(serialize($meta), ENT_NOQUOTES);
        }

        // Require certain alert types.
        $allowed_types = array('error', 'warning', 'notice');
        if(!in_array($type, $allowed_types))
            throw new Exception('Alert type, "'.$type.'," is not allowed');

        // Require certain alert sources.
        $allowed_sources = array('system', 'page');
        if(!in_array($source, $allowed_sources))
            throw new Exception('Alert source, "'.$source.'," is not allowed');

        // SQL
        $sql = 'INSERT INTO `alerts` (`source`, `url`, `integration_uri`, `type`, `message`, `meta`) VALUES';
        $sql.= '(?, ?, ?, ?, ?, ?)';
        $params = array($source, $url, $integration_uri, $type, $message, $meta);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert alert with the variables "'.$source.'", "'.$url.'",  "'.$integration_uri.'", "'.$type.'", "'.$message.'", and "'.$meta.'"');
        
        // Complete Query
        return $result;
        
    }

    /**
     * Add Meta
     */
    public static function add_meta($meta_name, $meta_value = NULL){
    
        // Serialize meta_value.
        if(is_array($meta_value)){
            $meta_value = serialize($meta_value);
        }
    
        // SQL
        $sql = 'INSERT INTO `meta` (`meta_name`, `meta_value`) VALUES (?, ?)';
        $params = array($meta_name, $meta_value);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot add meta field "'.$meta_name.'" and value "'.$meta_value.'"');
        
        // Complete Query
        return $result;
    }

    /**
     * Delete Meta
     */
    public static function delete_meta($meta_name){
    
        // SQL
        $sql = 'DELETE FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Update Meta 
     */
    public static function update_meta_value($meta_name, $meta_value){

        // SQL
        $sql = "UPDATE `meta` SET `meta_value` = ? WHERE `meta_name` = ?";
        $params = array($meta_value, $meta_name);

        // Query
        $result = self::query($sql, $params, false);

        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$meta_name.'" with value "'.$meta_value.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Update Site Data 
     */
    public static function update_site_data($url, $column, $data){

        // SQL
        $sql = 'UPDATE `sites` SET `'.$column.'` = ? WHERE `url` = ?';
        $params = array($data, $url);

        // Query
        $result = self::query($sql, $params, false);

        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$url.'" with "'.$data.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Create Alerts Table
     */
    public static function create_alerts_table(){
    
        // SQL
        $sql = 
            'CREATE TABLE `alerts` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `type` varchar(200) NOT NULL,
                `source` varchar(200) NOT NULL,
                `url` text,
                `site` text,
                `integration_uri` varchar(200) DEFAULT NULL,
                `message` text,
                `meta` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }

    /**
     * Create Meta Table
     */
    public static function create_meta_table(){
    
        // First, create the meta table
        $sql_1 = 
        'CREATE TABLE `meta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `meta_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT "",
            `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
            PRIMARY KEY (`meta_id`),
            UNIQUE KEY `option_name` (`meta_name`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
        ';
        $params = array();

        // Query 1
        $result_1 = self::query($sql_1, $params, false);

        // Fallback
        if(!$result_1)
            throw new Exception('Error creating table: "'.$result_1->error.'"');

        // Now, create the content in the meta table.
        $sql_2 = '
            INSERT INTO `meta` (meta_name, meta_value)
            VALUES 
            ("active_integrations", ?),
            ("alert_tabs", ?),
            ("scan_process", ?),
            ("scanable_pages", ?),
            ("integrations_processing", ?),
            ("sites_processing", ?),
            ("last_scan_time", ?);

        ';
        $default_active_integrations = serialize(array('little_forest'));
        $default_alert_tabs = serialize(array(
            'current_tab' => 1,
            'tabs'  => array(
                1 => array(
                    'id'        => 1,
                    'name'      => 'My Alerts',
                    'filters'   => array()
                )
            )
        ));
        $default_scan_process = '';
        $default_scanable_pages = serialize(array());
        $default_integrations_processing = serialize(array());
        $default_sites_processing = serialize(array());
        $default_last_scan_time = '';
        $params = array(
            $default_active_integrations, $default_alert_tabs,
            $default_scan_process, $default_scanable_pages,
            $default_integrations_processing,
            $default_sites_processing,
            $default_last_scan_time
        );

        // Query 2
        $result_2 = self::query($sql_2, $params, false);

        // Fallback
        if(!$result_2)
            throw new Exception('Error creating table: "'.$result_2->error.'"');
        
    }
    
    /**
     * Create Sites Table
     */
    public static function create_sites_table(){
    
        // SQL
        $sql = 
            'CREATE TABLE `sites` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `url` text COLLATE utf8mb4_bin NOT NULL,
                `type` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "static",
                `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "active",
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating sites table: "'.$result->error.'"');
        
    }
    
    /**
     * Table Exists
     */
    public static function table_exists($table_name){
    
        // SQL
        $db_name = $GLOBALS['DB_NAME'];
        $sql = "
            SELECT * 
            FROM information_schema.tables
            WHERE table_schema = '$db_name' 
                AND table_name = '$table_name'
            LIMIT 1;
        ";
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Results
        if($result && $result->num_rows === 1){
           return true;
        }else{
            return false;
        }
    
    }

}
