<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Function to handle deletion of a location
function my_custom_plugin_handle_delete()
{
    if (isset($_GET['delete_location']) && check_admin_referer('my_custom_plugin_delete_action', 'my_custom_plugin_delete_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'store_locator';
        $id = intval($_GET['delete_location']);
        $wpdb->delete($table_name, array('id' => $id));
        echo '<div class="updated"><p>Location deleted successfully!</p></div>';

        // Refresh the page after deletion
        echo '<script type="text/javascript">
                window.location = "' . admin_url('admin.php?page=locations') . '";
              </script>';
        exit;
    }
}

// Function to display the locations in a table
function my_custom_plugin_display_locations()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'store_locator';
    $locations = $wpdb->get_results("SELECT * FROM $table_name");

    my_custom_plugin_handle_delete();
?>
    <div class="wrap">
        <h1>All Locations</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Address</th>
                    <th scope="col">Store name</th>
                    <th scope="col">Website</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Category</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                foreach ($locations as $location) : 
                ?>
                    <tr>
                        <td><?php echo esc_html($counter); ?></td>
                        <td><?php echo esc_html($location->address); ?></td>
                        <td><?php echo esc_html($location->store_name); ?></td>
                        <td><?php echo esc_html($location->website); ?></td>
                        <td><?php echo esc_html($location->email); ?></td>
                        <td><?php echo esc_html($location->phone); ?></td>
                        <td><?php echo esc_html($location->category); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=locations&delete_location=' . $location->id), 'my_custom_plugin_delete_action', 'my_custom_plugin_delete_nonce'); ?>" class="button button-secondary">Delete</a>
                        </td>
                    </tr>
                <?php
                    $counter++;
                endforeach;
                ?>
            </tbody>
        </table>
    </div>
<?php
}
?>