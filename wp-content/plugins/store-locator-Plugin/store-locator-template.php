<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to get a cookie value by name
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        // Function to set a cookie
        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = `${name}=${value}; ${expires}; path=/`;
        }

        // Check if location cookie exists
        const locationCookie = getCookie('location');

        if (!locationCookie) {
            // Request location access
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        setCookie('location', `${lat},${lon}`, 365);
                    },
                    function(error) {
                        console.error(error);
                        // Handle error, set cookie to indicate location access was denied
                        setCookie('location', 'denied', 365);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
                // Set cookie to indicate location access was denied
                setCookie('location', 'denied', 365);
            }
        }
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to get a cookie value by name
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        // Function to set a cookie
        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = `${name}=${value}; ${expires}; path=/`;
        }

        // Check if location cookie exists and if not, request location
        const locationCookie = getCookie('location');
        if (!locationCookie) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        setCookie('location', `${lat},${lon}`, 365);
                    },
                    function(error) {
                        console.error(error);
                        setCookie('location', 'denied', 365);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
                setCookie('location', 'denied', 365);
            }
        }

        // Add click event to buttons
        document.querySelectorAll('.location-button').forEach(function(button) {
            button.addEventListener('click', function() {
                const userLocation = getCookie('location');
                if (userLocation && userLocation !== 'denied') {
                    const [userLat, userLon] = userLocation.split(',');
                    const destinationLat = this.getAttribute('data-lat');
                    const destinationLon = this.getAttribute('data-lon');
                    const url =
                        `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLon}&destination=${destinationLat},${destinationLon}`;
                    window.open(url, '_blank');
                } else {
                    const destinationLat = this.getAttribute('data-lat');
                    const destinationLon = this.getAttribute('data-lon');
                    const url =
                        `https://www.google.com/maps/dir/?api=1&origin=&destination=${destinationLat},${destinationLon}`;
                    window.open(url, '_blank');
                }
            });
        });
    });
</script>



<?php
global $wpdb;
$table_name = $wpdb->prefix . 'store_category';

$categories = $wpdb->get_results("SELECT * FROM $table_name"); ?>

<?php
$table_name = $wpdb->prefix . 'store_locator';

$store_locators = $wpdb->get_results("SELECT * FROM $table_name");
?>




<?php
$markers = [];
if ($store_locators) {
    foreach ($store_locators as $category) {
        $categoriess = explode(',', $category->category); // Split comma-separated categories
        $markers[] = [
            'id' => $category->id,
            'address' => urldecode($category->address),
            'store_name' => $category->store_name,
            'website' => $category->website,
            'longitude' => $category->longitude,
            'latitude' => $category->latitude,
            'email' => $category->email,
            'phone' => $category->phone,
            'categories' => $categoriess, // Store as an array
        ];
    }
}
?>
<section>
    <div class="container">
        <div class="main-outer-section">
            <div class="category-class">
                <h2><label>STORE LOCATOR</label></h2>
                <div class="category-inner">
                    <?php foreach ($categories as $category): ?>
                        <label>
                            <input type="checkbox" class="category-checkbox" value="<?php echo $category->category_name; ?>">
                            <?php echo $category->category_name; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="inner-section">
                <div id="info">
                    <div id="info-text">
                        <div class="info-title">
                            <h3>Find a store near you</h3>
                        </div>
                        <div class="search-info">
                            <input id="search-input" type="text" placeholder="Enter a location"
                                style="width: 100%; padding: 5px;">
                        </div>
                        <div class="info-inner">
                            <?php foreach ($markers as $marker): ?>
                                <div class="info-content" data-categories="<?php echo implode(',', $marker['categories']); ?>">
                                    <div class="address-outer" id="info-<?php echo $marker['id']; ?>"
                                        data-lat="<?php echo $marker['latitude']; ?>"
                                        data-lon="<?php echo $marker['longitude']; ?>">
                                        <div class="store_name">
                                            <h2><?php echo $marker['store_name']; ?></h2>
                                        </div>
                                        <div class="addresstext">
                                            <h4><?php echo $marker['address']; ?></h4>
                                        </div>


                                        <div class="info-redirection">
                                            <div class="categoriestext">
                                                <h4><?php $categories_string = implode(', ', $marker['categories']); // Output the result
                                                    echo $categories_string; ?></h4>
                                            </div>
                                            <div class="other-content">
                                                <p><a href="<?php echo $marker['website']; ?>" target="_blank">
                                                        <div class="custom-icon"><svg fill="#000000" width="20px" height="38px" viewBox="0 0 512 512" id="_x30_1" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                                <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M418.275,146h-46.667  c-5.365-22.513-12.324-43.213-20.587-61.514c15.786,8.776,30.449,19.797,43.572,32.921C403.463,126.277,411.367,135.854,418.275,146  z M452,256c0,17.108-2.191,33.877-6.414,50h-64.034c1.601-16.172,2.448-32.887,2.448-50s-0.847-33.828-2.448-50h64.034  C449.809,222.123,452,238.892,452,256z M256,452c-5.2,0-21.048-10.221-36.844-41.813c-6.543-13.087-12.158-27.994-16.752-44.187  h107.191c-4.594,16.192-10.208,31.1-16.752,44.187C277.048,441.779,261.2,452,256,452z M190.813,306  c-1.847-16.247-2.813-33.029-2.813-50s0.966-33.753,2.813-50h130.374c1.847,16.247,2.813,33.029,2.813,50s-0.966,33.753-2.813,50  H190.813z M60,256c0-17.108,2.191-33.877,6.414-50h64.034c-1.601,16.172-2.448,32.887-2.448,50s0.847,33.828,2.448,50H66.414  C62.191,289.877,60,273.108,60,256z M256,60c5.2,0,21.048,10.221,36.844,41.813c6.543,13.087,12.158,27.994,16.752,44.187H202.404  c4.594-16.192,10.208-31.1,16.752-44.187C234.952,70.221,250.8,60,256,60z M160.979,84.486c-8.264,18.301-15.222,39-20.587,61.514  H93.725c6.909-10.146,14.812-19.723,23.682-28.593C130.531,104.283,145.193,93.262,160.979,84.486z M93.725,366h46.667  c5.365,22.513,12.324,43.213,20.587,61.514c-15.786-8.776-30.449-19.797-43.572-32.921C108.537,385.723,100.633,376.146,93.725,366z   M351.021,427.514c8.264-18.301,15.222-39,20.587-61.514h46.667c-6.909,10.146-14.812,19.723-23.682,28.593  C381.469,407.717,366.807,418.738,351.021,427.514z" />
                                                            </svg></div>
                                                    </a></p>
                                                <p><a href="tel:<?php echo $marker['phone']; ?>">
                                                        <div class="custom-icon"><svg width="35px" height="38px" viewBox="0 0 76 76"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full"
                                                                enable-background="new 0 0 76.00 76.00" xml:space="preserve">
                                                                <path fill="#000000" fill-opacity="1" stroke-width="0.2"
                                                                    stroke-linejoin="round"
                                                                    d="M 50.9225,54.2329C 51.037,54.5029 51.0943,54.7769 51.0943,55.0551C 51.0943,55.4396 50.992,55.7986 50.7875,56.132C 50.583,56.4654 50.2966,56.72 49.9285,56.8959L 48.1981,57.7059C 47.5231,58.0127 46.8471,58.2356 46.1701,58.3747C 45.4931,58.5138 44.809,58.5833 44.1176,58.5833C 43.005,58.5833 41.9056,58.3982 40.8195,58.028C 39.7334,57.6578 38.6944,57.1281 37.7024,56.4388C 36.7104,55.7495 35.7798,54.916 34.9105,53.9384C 34.0412,52.9607 33.263,51.8705 32.5757,50.6678C 32.138,50.0011 31.6778,49.2402 31.1951,48.3852C 30.7861,47.6571 30.3187,46.7848 29.7931,45.7682C 29.2674,44.7517 28.7366,43.6237 28.2008,42.3842C 27.6526,41.1447 27.218,40.0617 26.8968,39.1352C 26.5757,38.2086 26.3272,37.4386 26.1513,36.825C 25.9468,36.1091 25.8057,35.4955 25.7279,34.9841C 25.4457,34.0351 25.2381,33.0912 25.1051,32.1524C 24.9722,31.2136 24.9057,30.2839 24.9057,29.3635C 24.9057,28.1609 25.0264,27.0032 25.2677,25.8905C 25.5091,24.7779 25.8793,23.7429 26.3783,22.7857C 26.8774,21.8285 27.5104,20.9725 28.2775,20.2178C 29.0445,19.463 29.9516,18.8484 30.9988,18.3739L 32.7046,17.5885C 32.9746,17.4739 33.2446,17.4167 33.5146,17.4167C 33.895,17.4167 34.2488,17.522 34.5761,17.7327C 34.9033,17.9433 35.1488,18.2348 35.3124,18.6071L 38.5952,26.0623C 38.7097,26.3323 38.767,26.6023 38.767,26.8723C 38.767,27.2691 38.6647,27.6362 38.4602,27.9737C 38.2557,28.3112 37.9673,28.5638 37.595,28.7319L 34.2202,30.2839C 33.7743,30.4885 33.4327,30.7891 33.1955,31.1859C 32.9582,31.5827 32.8396,32.0102 32.8396,32.4684C 32.8396,32.8161 32.905,33.1433 33.0359,33.4501L 38.5768,46.1026C 38.7936,46.5894 39.0932,46.9474 39.4757,47.1764C 39.8582,47.4055 40.2744,47.5201 40.7244,47.5201C 41.0312,47.5201 41.3441,47.4505 41.6632,47.3114L 45.038,45.759C 45.3039,45.6445 45.5719,45.5872 45.8419,45.5872C 46.2141,45.5872 46.5659,45.6925 46.8973,45.9032C 47.2286,46.1139 47.4781,46.4033 47.6458,46.7715L 50.9225,54.2329 Z " />
                                                            </svg></div>
                                                    </a></p>
                                                <button class="location-button" data-lat="<?php echo $marker['latitude']; ?>"
                                                    data-lon="<?php echo $marker['longitude']; ?>"><span
                                                        class="custom-icon direction-icon"><svg width="30px" height="38px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M18.9762 5.5914L14.6089 18.6932C14.4726 19.1023 13.8939 19.1023 13.7575 18.6932L11.7868 12.7808C11.6974 12.5129 11.4871 12.3026 11.2192 12.2132L5.30683 10.2425C4.89772 10.1061 4.89772 9.52743 5.30683 9.39106L18.4086 5.0238C18.7594 4.90687 19.0931 5.24061 18.9762 5.5914Z" stroke="#000" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </span></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div id="map"></div>
            </div>
        </div>
</section>
</div>

<script type="text/javascript">
    var map;
    var markers = <?php echo json_encode($markers); ?>;
    var currentInfoWindow = null;
    var currentInfoContent = null;
    var mapMarkers = []; // Array to store Google Maps markers
    var searchMarker = null; // Variable to store the search marker

    function initMap() {
        var mapOptions = {
            zoom: 1.8, // Set initial zoom level to 3
            center: {
                lat: 33.9221,
                lng: 18.4231
            }, // Set default center coordinates here
            zoomControl: true,
            disableDefaultUI: true, // Disable default UI components
            minZoom: 1.6, // Optionally, set a minimum zoom level
            styles: [{
                    elementType: 'geometry',
                    stylers: [{
                        color: '#212121'
                    }]
                },
                {
                    elementType: 'labels.text.stroke',
                    stylers: [{
                        color: '#212121'
                    }]
                },
                {
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#ffffff'
                    }]
                },
                {
                    featureType: 'administrative',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#757575'
                    }]
                },
                {
                    featureType: 'poi',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#ffffff'
                    }]
                },
                {
                    featureType: 'poi.park',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#181818'
                    }]
                },
                {
                    featureType: 'poi.park',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#ffffff'
                    }]
                },
                {
                    featureType: 'road',
                    elementType: 'geometry.fill',
                    stylers: [{
                        color: '#2c2c2c'
                    }]
                },
                {
                    featureType: 'road',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#8a8a8a'
                    }]
                },
                {
                    featureType: 'road.arterial',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#373737'
                    }]
                },
                {
                    featureType: 'road.highway',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#3c3c3c'
                    }]
                },
                {
                    featureType: 'road.highway.controlled_access',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#4e4e4e'
                    }]
                },
                {
                    featureType: 'road.local',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#ffffff'
                    }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#ffffff'
                    }]
                },
                {
                    featureType: 'water',
                    elementType: 'geometry',
                    stylers: [{
                        color: '#000000'
                    }]
                },
                {
                    featureType: 'water',
                    elementType: 'labels.text.fill',
                    stylers: [{
                        color: '#3d3d3d'
                    }]
                }
            ]
        };

        map = new google.maps.Map(document.getElementById('map'), mapOptions);

        var markerColor = {
            url: 'https://b2b.sempre.be/wp-content/uploads/2024/07/illust58-4246-01-removebg-1.png',
            scaledSize: new google.maps.Size(60, 60) // Set custom size here
        };

        // Add markers to the map
        for (var i = 0; i < markers.length; i++) {
            (function(markerData) {
                var marker = new google.maps.Marker({
                    position: {
                        lat: parseFloat(markerData.latitude),
                        lng: parseFloat(markerData.longitude)
                    },
                    map: map,
                    title: markerData.address,
                    icon: markerColor,
                    markerId: markerData.id, // Custom property to store marker ID
                    categories: markerData.categories // Custom property to store categories array
                });

                // Create info window content
                var contentString = '<div style="background-color: #000; color: white; padding: 10px; font-size: 14px;"><h5 style="margin: 0 0 5px 0; font-weight: bold; color: #fff; text-transform: capitalize;">' + markerData.store_name + '</h5><h6 style="margin: 0 0 1px 0; color: #fff;">' + markerData.address + '</h6><p style="margin: 0;">Email: ' + markerData.email +
                    '<br>Phone: ' + markerData.phone + '</p></div>';

                // Create info window for each marker
                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });

                // Add click event listener to marker
                marker.addListener('click', function() {
                    // Close the info window if it is currently open
                    if (currentInfoWindow) {
                        currentInfoWindow.close();
                        currentInfoWindow = null;
                    }
                    // Remove 'active' class from current info content if open
                    if (currentInfoContent) {
                        currentInfoContent.classList.remove('active');
                        currentInfoContent = null;
                    }

                    // Open new info window and highlight corresponding info content
                    // infowindow.open(map, this);
                    // currentInfoWindow = infowindow;

                    var markerId = this.markerId;
                    var selectedInfo = document.getElementById('info-' + markerId);
                    selectedInfo.classList.add('active');
                    currentInfoContent = selectedInfo;

                    // Scroll to the active info panel
                    selectedInfo.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center' // Ensure the info panel is centered within the viewport
                    });

                    // Get current zoom level
                    var currentZoom = map.getZoom();

                    // Set zoom level to 8 only if current zoom level is less than 8
                    if (currentZoom < 4) {
                        map.setZoom(4);
                    }

                    // Center map to the clicked marker position
                    map.setCenter(this.getPosition());
                });

                // Store marker in array
                mapMarkers.push(marker);
            })(markers[i]);
        }

        // Close the info window when clicking anywhere on the map
        google.maps.event.addListener(map, 'click', function() {
            $(".address-outer").removeClass('active');
            if (currentInfoWindow) {
                currentInfoWindow.close();
                currentInfoWindow = null;
            }
            if (currentInfoContent) {
                currentInfoContent.classList.remove('active');
                currentInfoContent = null;
            }
        });

        // Initialize Places API for search autocomplete
        var input = document.getElementById('search-input');
        var autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode']
        });

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                console.error("No details available for input: '" + place.name + "'");
                return;
            }

            // Clear previous search marker
            if (searchMarker) {
                searchMarker.setMap(null);
            }

            // Create a marker for the selected place
            searchMarker = new google.maps.Marker({
                map: map,
                position: place.geometry.location,
                title: place.name
            });

            // Zoom to the selected marker
            map.setCenter(searchMarker.getPosition());
            map.setZoom(6); // Adjust zoom level as needed
        });
    }

    // Function to filter markers based on selected categories
    function filterMarkers() {
        var selectedCategories = [];
        var checkboxes = document.querySelectorAll('.category-checkbox:checked');
        checkboxes.forEach(function(checkbox) {
            selectedCategories.push(checkbox.value);
        });

        mapMarkers.forEach(function(marker) {
            var showMarker = false;
            marker.categories.forEach(function(cat) {
                if (selectedCategories.length === 0 || selectedCategories.indexOf(cat) !== -1) {
                    showMarker = true;
                }
            });
            if (showMarker) {
                marker.setMap(map);
            } else {
                marker.setMap(null);
            }
        });

        var shownInfoContent = document.querySelectorAll('.info-content');
        shownInfoContent.forEach(function(content) {
            var contentCategories = content.dataset.categories.split(',');
            var showContent = selectedCategories.length === 0 || selectedCategories.some(function(cat) {
                return contentCategories.indexOf(cat) !== -1;
            });
            content.style.display = showContent ? 'block' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for the category checkboxes
        var categoryCheckboxes = document.querySelectorAll('.category-checkbox');
        categoryCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', filterMarkers);
        });

        // Add event listeners for the address divs
        var addressDivs = document.querySelectorAll('.address-outer');
        addressDivs.forEach(function(div) {
            div.addEventListener('click', function() {
                var lat = parseFloat(div.dataset.lat);
                var lon = parseFloat(div.dataset.lon);

                // Define the target position and zoom level
                var target = {
                    lat: lat,
                    lng: lon
                };
                var zoomLevel = 14;

                // Animate map movement with animation effect
                map.panTo(target, 1000); // 1000ms animation duration
                map.setZoom(zoomLevel, {
                    animate: true
                });
            });
        });

        // Initialize the map
        initMap();
    });
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCBABGTb1mX0085zACpwXhfuAiEP45BIaI"></script>



<!-- Replace YOUR_API_KEY with your actual Google Maps API key -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCBABGTb1mX0085zACpwXhfuAiEP45BIaI&libraries=places&callback=initMap"
    async defer></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        $('.info-content').click(function() {
            // Check if any child div has the class 'active'
            var hasActive = $(this).find('div').hasClass('active');
            // Remove active class from all child divs
            $(".address-outer").removeClass('active');

            // Add active class to the first child div if none had it before
            if (!hasActive) {
                $(this).children('div:first-child').addClass('active');
            }
        });
    });
</script>

<style>
    .directorist-content-active #map {
        width: 70% !important;
        height: 599px;
    }

    .category-class h2 label {
        color: #000;
        font-size: 35px;
    }

    :root .info-title h3 {
        font-size: 23px;
        margin-bottom: 12px !important;
    }

    .category-inner label {
        color: #000;
        text-transform: capitalize;
    }

    :root span.direction-icon {
        border: none;
    }

    .categoriestext h4 {
        font-size: 14px;
        text-transform: capitalize;
    }
</style>