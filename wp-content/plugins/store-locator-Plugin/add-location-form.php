<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<!-- Chosen CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css">
<!-- jQuery library (if not already included) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Chosen JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>

<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Function to handle the form submission
function my_custom_plugin_handle_form_submission()
{
    if (isset($_POST['my_custom_plugin_form_submit'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'store_locator';
        // Sanitize and validate form input
        $address = sanitize_text_field($_POST['address']);

        // Replace YOUR_API_KEY with your actual Google Maps API key
        $apiKey = 'AIzaSyAhE2MuX_VDjdX5XYz_hne1RszIkMUU7eE';

        // URL encode the address
        $address = urlencode($address);

        // Google Maps Geocoding API URL
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        // Send a GET request to the API URL
        $response = file_get_contents($url);

        // Decode the JSON response
        $responseData = json_decode($response, true);
        // Check if the response contains the desired data
        if ($responseData['status'] == 'OK') {
            // Extract latitude and longitude
            $latitude = $responseData['results'][0]['geometry']['location']['lat'];
            $longitude = $responseData['results'][0]['geometry']['location']['lng'];
        }
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $categories = isset($_POST['category']) ? array_map('sanitize_text_field', $_POST['category']) : [];
        $category = implode(',', $categories);
        // Insert data into the database
        $wpdb->insert(
            $table_name,
            array(
                'address' => $_POST['address'],
                'store_name' => $_POST['store_name'],
                'website' => $_POST['website'],
                'longitude' => $longitude,
                'latitude' => $latitude,
                'email' => $email,
                'phone' => $phone,
                'category' => $category
            )
        );
        echo '<div class="updated"><p>Form submitted successfully!</p></div>';
    }
}

// Function to display the form
function my_custom_plugin_display_form()
{
    my_custom_plugin_handle_form_submission();
?>
    <div class="wrap">
        <h1>Add Location</h1>
        <form method="post" action="">
            <?php wp_nonce_field('my_custom_plugin_form_action', 'my_custom_plugin_form_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="store_name">Store name</label></th>
                    <td><input type="text" class="form-control" id="store_name" name="store_name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="address">Address</label></th>
                    <td><input type="text" class="form-control" id="address" name="address" required /></td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><label for="website ">Website </label></th>
                    <td><input type="text" class="form-control" id="website" name="website" required /></td>
                </tr>
                <tr valign="top" style="display: none;">
                    <th scope="row"><label for="longitude">Longitude</label></th>
                    <td><input type="hidden" id="longitude" name="longitude" required /></td>
                </tr>
                <tr valign="top" style="display: none;">
                    <th scope="row"><label for="latitude">Latitude</label></th>
                    <td><input type="text" class="form-control" id="latitude" name="latitude" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="email">Email</label></th>
                    <td><input type="email" class="form-control" id="email" name="email" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="phone">Phone Number</label></th>
                    <td><input type="text" class="form-control" id="phone" name="phone" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="category">Category</label></th>
                    <td>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'store_category';

                        $categories = $wpdb->get_results("SELECT * FROM $table_name"); ?>
                        <select id="category" name="category[]" multiple class="form-control chosen-select" required>

                            <?php
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_html($category->category_name) . '">' . esc_html($category->category_name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="my_custom_plugin_form_submit" class="button-primary" value="Submit" />
            </p>
        </form>
    </div>
<?php
}
?>

<script>
    jQuery(document).ready(function($) {
        $('.chosen-select').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Categories'
        });
    });
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCBABGTb1mX0085zACpwXhfuAiEP45BIaI&libraries=places&callback=initAutocomplete" async defer></script>
<script>
    function initAutocomplete() {
        new google.maps.places.Autocomplete(
            (document.getElementById('address')), {
                types: ['geocode']
            }
        );
    }

    function initAutocomplete() {
        var input = document.getElementById('address');
        var autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode']
        });
        // Listener to capture place change event
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                console.error("No details available for input: '" + place.name + "'");
                return;
            }
            // Get latitude and longitude
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            console.log("Latitude: " + lat + ", Longitude: " + lng);
            // You can store these values in hidden input fields or do further processing
        });
    }
</script>