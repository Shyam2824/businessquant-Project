<?php


if (!defined('ABSPATH')) {
    exit;
}


function fetch_api_data() {
    $api_url = '   ';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data;
}

// Function to store data in the database
function store_api_data_in_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'api_data';

    $data = fetch_api_data();

    if (!empty($data)) {
        foreach ($data as $row) {
            $wpdb->insert($table_name, $row);
        }
    }
}

// Create table on plugin activation
function create_api_data_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'api_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        gp int NOT NULL,
        fcf int NOT NULL,
        capex int NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Fetch and store initial data
    store_api_data_in_db();
}
register_activation_hook(__FILE__, 'create_api_data_table');

// Shortcode to display data in a table
function display_api_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'api_data';

    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    // Start output buffering
    ob_start();
    
    // Display data in a table
    echo '<table border="1">';
    echo '<tr><th>gp</th><th>fcf</th><th>capex</th></tr>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row['gp']) . '</td>';
        echo '<td>' . esc_html($row['fcf']) . '</td>';
        echo '<td>' . esc_html($row['capex']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('api_data_table', 'display_api_data');

// Schedule data fetch every hour
if (!wp_next_scheduled('fetch_api_data_hourly')) {
    wp_schedule_event(time(), 'hourly', 'fetch_api_data_hourly');
}
add_action('fetch_api_data_hourly', 'store_api_data_in_db');

// Clear scheduled event on plugin deactivation
function clear_scheduled_event() {
    $timestamp = wp_next_scheduled('fetch_api_data_hourly');
    wp_unschedule_event($timestamp, 'fetch_api_data_hourly');
}
register_deactivation_hook(__FILE__, 'clear_scheduled_event');
