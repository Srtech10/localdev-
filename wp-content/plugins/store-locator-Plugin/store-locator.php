<?php
/*
Plugin Name:Store Locator
Description: A plugin to manage store locations. Use the [store_locator] shortcode to display the store locations.
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}


register_activation_hook(__FILE__, 'create_store_category_table');

function create_store_category_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'store_category'; // Adding the prefix to the table name
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category_name varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Including the upgrade.php file to run the dbDelta function
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Shortcode to display the store locator
function my_custom_plugin_display_store_locator()
{
    ob_start();
    include(plugin_dir_path(__FILE__) . 'store-locator-template.php');
    return ob_get_clean();
}
add_shortcode('store_locator', 'my_custom_plugin_display_store_locator');

// Create or update the database table on plugin activation
function my_custom_plugin_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'store_locator';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            address varchar(255) NOT NULL,
            website varchar(255) NOT NULL,
            store_name varchar(255) NOT NULL,
            longitude float NOT NULL,
            latitude float NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            category varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'my_custom_plugin_create_table');

// Add admin menu and submenus
function my_custom_plugin_admin_menu()
{
    // Main menu
    add_menu_page(
        'Store Locator',   // Page title
        'Store Locator',      // Menu title
        'manage_options',     // Capability
        'store-locator',   // Menu slug
        'store_locator_main_page', // Function
        'dashicons-admin-generic',    // Icon URL
        6                     // Position
    );

    // Submenu 1
    add_submenu_page(
        'store-locator',        // Parent slug
        'Add Location',                  // Page title
        'Add Location',                  // Menu title
        'manage_options',          // Capability
        'add-location', // Menu slug
        'my_custom_plugin_menu_1_page' // Function
    );

    add_submenu_page(
        'store-locator',
        'Locations',
        'Locations',
        'manage_options',
        'locations',
        'my_custom_plugin_menu_2_page'
    );
}
add_action('admin_menu', 'my_custom_plugin_admin_menu');

// Main page callback function
// function store_locator_main_page()
// {
//     echo '<h1>Store Locator Main Page</h1>';
//     echo '<p>Welcome to the main page of Store Locator!</p>';
// }

// Submenu 1 callback function
function my_custom_plugin_menu_1_page()
{
    require_once(plugin_dir_path(__FILE__) . 'add-location-form.php');
    my_custom_plugin_display_form();
}

// Submenu 2 callback function
function my_custom_plugin_menu_2_page()
{
    require_once(plugin_dir_path(__FILE__) . 'locations.php');
    my_custom_plugin_display_locations();
}


function store_locator_main_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'store_category';

    // Handle form submission for adding a category
    if (isset($_POST['new_category'])) {
        $new_category = sanitize_text_field($_POST['new_category']);
        $wpdb->insert($table_name, ['category_name' => $new_category]);
        echo '<div class="updated"><p>Category added successfully.</p></div>';
    }

    // Handle deletion of a category
    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);
        $wpdb->delete($table_name, ['id' => $delete_id]);
        echo '<div class="updated"><p>Category deleted successfully.</p></div>';
    }

    // Fetch all categories
    $categories = $wpdb->get_results("SELECT * FROM $table_name");

    // Display the form and the list of categories
    echo '<h1>Manage Categories</h1>';
    echo '<form method="post">';
    echo '<input type="text" name="new_category" placeholder="New Category Name" required />';
    echo '<input type="submit" value="Add Category" class="button button-primary" />';
    echo '</form>';
    
    if (!empty($categories)) {
        echo '<h2>Existing Categories</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Category Name</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        $x=1;
        foreach ($categories as $category) {
            echo '<tr>';
            echo '<td>' . $x . '</td>';
            echo '<td>' . esc_html($category->category_name) . '</td>';
            echo '<td><a href="?page=store-locator&delete_id=' . esc_html($category->id) . '" onclick="return confirm(\'Are you sure you want to delete this category?\');">Delete</a></td>';
            echo '</tr>';
       $x++; }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No categories found.</p>';
    }
}



// Hook to enqueue the stylesheet
add_action('wp_enqueue_scripts', 'enqueue_custom_plugin_styles');

function enqueue_custom_plugin_styles() {
    // Use plugins_url to get the URL of the custom.css file
    wp_enqueue_style('custom-plugin-styles', plugins_url('css/custom.css', __FILE__));
}