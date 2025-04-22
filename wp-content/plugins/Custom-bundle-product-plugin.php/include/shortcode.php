<?php
// Add admin menu under Products submenu
function dish_bundle_register_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Dish Bundle Display Control',
        'Dish Bundle Display',
        'manage_options',
        'dish-bundle-display',
        'dish_bundle_display_page'
    );
}
add_action('admin_menu', 'dish_bundle_register_admin_menu');

// Register settings
function dish_bundle_register_display_settings() {
    register_setting('dish_bundle_display_group', 'dish_bundle_display_categories', [
        'sanitize_callback' => 'dish_bundle_sanitize_array'
    ]);
    register_setting('dish_bundle_display_group', 'dish_bundle_display_tags', [
        'sanitize_callback' => 'dish_bundle_sanitize_array'
    ]);
}
add_action('admin_init', 'dish_bundle_register_display_settings');

// Sanitize array input
function dish_bundle_sanitize_array($input) {
    if (!is_array($input)) {
        return [];
    }
    return array_map('sanitize_text_field', $input);
}

// Admin page render
function dish_bundle_display_page() {
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'exclude' => get_term_by('slug', 'uncategorized', 'product_cat')->term_id
    ]);
    $tags = get_terms([
        'taxonomy' => 'product_tag',
        'hide_empty' => false
    ]);
    
    $selected_categories = get_option('dish_bundle_display_categories', []);
    $selected_tags = get_option('dish_bundle_display_tags', []);
    ?>
    <style>
            .main-select {
    display: flex !important;
    gap: 30px 100px;
    flex-wrap: wrap;
}


    </style>
    <div class="wrap">
        <h1>Dish Bundle Display Control</h1>
        <form method="post" action="options.php">
            <?php settings_fields('dish_bundle_display_group'); ?>
            <div class="main-select">
            <div>
            <h2>Categories to Display</h2>
            <div class="cat-section" >
                <?php foreach ($categories as $category): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" 
                               name="dish_bundle_display_categories[]" 
                               value="<?php echo esc_attr($category->slug); ?>"
                               <?php checked(in_array($category->slug, $selected_categories)); ?>>
                        <?php echo esc_html($category->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            </div>
            <div>

            <h2>Tags to Display</h2>
            <div >
                <?php foreach ($tags as $tag): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" 
                               name="dish_bundle_display_tags[]" 
                               value="<?php echo esc_attr($tag->slug); ?>"
                               <?php checked(in_array($tag->slug, $selected_tags)); ?>>
                        <?php echo esc_html($tag->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            </div>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Modified shortcode function with tag selection
function dish_bundle_builder_shortcode_jby($atts = []) {
    $atts = shortcode_atts([
        'category' => '',
        'tag' => '',                     // Added tag attribute
        'order' => 'menu_order',
        'order_direction' => 'ASC',
        'min_dishes' => 4,
        'max_dishes' => 20,
        'add_to_cart_mode' => 'individual',
        'product_id' => 0,
        'discount_percentage' => 0,
        'show_category_filter' => '',
        'show_tag_filter' => '',
    ], $atts, 'dish_bundle_builder_jby');

    if (!in_array($atts['add_to_cart_mode'], ['individual', 'bundle'])) {
        $atts['add_to_cart_mode'] = 'individual';
    }

    $discount_percentage = floatval($atts['discount_percentage']);
    ob_start();

    $min_dishes = intval($atts['min_dishes']);
    $max_dishes = intval($atts['max_dishes']);
    $add_to_cart_mode = $atts['add_to_cart_mode'];
    $show_category_filter = $atts['show_category_filter'];
    $show_tag_filter = $atts['show_tag_filter'];

    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    switch ($atts['order']) {
        case 'price':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_price';
            break;
        case 'popularity':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'total_sales';
            break;
        case 'rating':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_wc_average_rating';
            break;
        case 'title':
            $args['orderby'] = 'title';
            break;
        case 'date':
            $args['orderby'] = 'date';
            break;
        default:
            $args['orderby'] = 'menu_order';
    }

    $args['order'] = $atts['order_direction'];

    // Handle selected category from URL or shortcode
    $selected_category = isset($_GET['category']) ? array_filter(explode(',', sanitize_text_field($_GET['category']))) 
        : (!empty($atts['category']) ? $atts['category'] : '');

    // Handle selected tag from URL or shortcode
    $selected_tag = isset($_GET['tag']) ? array_filter(explode(',', sanitize_text_field($_GET['tag']))) 
        : (!empty($atts['tag']) ? $atts['tag'] : '');

    // Final query arguments for WC_Product_Query
    $args = [
        'status' => 'publish',
        'limit' => -1,
    ];

    $tax_query = ['relation' => 'AND']; // Changed from AND to OR for outer relation

    if (!empty($selected_category)) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $selected_category,
            'operator' => 'IN' // Changed from AND to IN
        ];
    }

    if (!empty($selected_tag)) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => $selected_tag,
            'operator' => 'IN' // Changed from AND to IN
        ];
    }

    if (!empty($selected_category) || !empty($selected_tag)) {
        $args['tax_query'] = $tax_query;
    }

    $products_query = new WC_Product_Query($args);
    $products = $products_query->get_products();

    // Get selected categories and tags from settings
    $allowed_categories = get_option('dish_bundle_display_categories', []);
    $allowed_tags = get_option('dish_bundle_display_tags', []);

    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'exclude' => get_term_by('slug', 'uncategorized', 'product_cat')->term_id,
        'slug' => !empty($allowed_categories) ? $allowed_categories : null
    ]);
    $selected_categories = isset($_GET['category']) ? array_filter(explode(',', sanitize_text_field($_GET['category']))) : [];

    $tags = get_terms([
        'taxonomy' => 'product_tag',
        'hide_empty' => false,
        'slug' => !empty($allowed_tags) ? $allowed_tags : null
    ]);
    $selected_tags = isset($_GET['tag']) ? array_filter(explode(',', sanitize_text_field($_GET['tag']))) : [];
?>

<!-- Frontend HTML -->
<div class="dish-bundle-widget">
    <?php if ($atts['show_category_filter'] === 'yes' && !empty($categories)): ?>
        <div class="filter-section">
            <h4>Filter by type of dish</h4>
            <div class="filter-buttons">
                <?php foreach ($categories as $category): ?>
                    <button type="button" 
                            class="filter-btn category-btn <?php echo in_array($category->slug, $selected_categories) ? 'active' : ''; ?>"
                            data-type="category"
                            data-value="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html(ucfirst($category->name)); ?>
                        <span class="cross" <?php echo in_array($category->slug, $selected_categories) ? '' : 'style="display: none;"'; ?>>×</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($atts['show_tag_filter'] === 'yes' && !empty($tags)): ?>
        <div class="filter-section">
            <h4>Filter by type of protein</h4>
            <div class="filter-buttons">
                <?php foreach ($tags as $tag): ?>
                    <button type="button" 
                            class="filter-btn tag-btn <?php echo in_array($tag->slug, $selected_tags) ? 'active' : ''; ?>"
                            data-type="tag"
                            data-value="<?php echo esc_attr($tag->slug); ?>">
                        <?php echo esc_html(ucfirst($tag->name)); ?>
                        <span class="cross" <?php echo in_array($tag->slug, $selected_tags) ? '' : 'style="display: none;"'; ?>>×</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Rest of the HTML/PHP -->
<form id="dish-bundle-builder" class="dish-bundle-form">
    <div class="full-page-loader" id="full-page-loader">
        <div class="loader-spinner"></div>
        <span class="loader-text">Loading Products...</span>
    </div>

    <div class="dish-bundle-grid">
    <?php 
    // Check if there are any products at all
    if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <div class="dish-item" style="position: relative;">
                <div class="dish-image"><?= $product->get_image(); ?></div>
                <span class="product-name"><?= esc_html($product->get_name()); ?></span>
                <?= do_shortcode('[woosq id="' . $product->get_id() . '" type="icon"]'); ?>

                <div class="dish-sizes">
                    <?php
                    // For all products, show their price and quantity selector
                    $price = wc_price($product->get_price());
                    $product_id = esc_attr($product->get_id());
                    $columns = get_option('dish_size_columns', 2);
                    ?>
                    <div class="dish-size-row" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
                        <div class="dish-size" style="grid-column: span <?php echo $columns > 1 ? '1' : '2'; ?>;">
                            <div class="size-label-price">
                                <label for="dish-<?= $product_id; ?>">Price:</label>
                                <span class="dish-size-price"><?= $price; ?></span>
                            </div>
                            <div class="quantity-buttons">
                                <button type="button" class="minus" data-target="#dish-<?= $product_id; ?>">-</button>
                                <input type="number" 
                                       id="dish-<?= $product_id; ?>" 
                                       name="dish[<?= $product_id; ?>]" 
                                       value="0" 
                                       min="0" 
                                       class="dish-quantity" 
                                       data-price="<?= esc_attr($product->get_price()); ?>">
                                <button type="button" class="plus" data-target="#dish-<?= $product_id; ?>">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-products-found">
            <p>No products found.</p>
        </div>
    <?php endif; ?>
</div>

    <div id="total-dishes">Total Dishes: <span>0</span></div>
    <div id="total-price">Total Price: <span>฿0</span></div>
    <div id="discount-note">(Discount applied at Checkout)</div>
    <div id="minimum-warning" style="display:none; color:red;">Minimum <?php echo $min_dishes; ?> and maximum <?php echo $max_dishes; ?> dishes required</div>

    <?php wp_nonce_field('add_bundle_to_cart_nonce', 'add_bundle_to_cart_nonce_field'); ?>
    <input type="hidden" name="discount_percentage" value="<?php echo esc_attr($discount_percentage); ?>">
    <input type="submit" value="Add to Basket" id="add-to-cart-button" disabled>

    <div id="loading-indicator" style="display:none;">
        Processing...
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
    </div>

    <div id="redirect-text" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:9999;">
        <span style="color:white; font-size:24px; font-weight:bold;">Redirecting...</span>
    </div>
</form>

<!-- JavaScript -->
<script>
jQuery(document).ready(function($) {
    const $form = $('#dish-bundle-builder');
    const $totalDishes = $('#total-dishes span');
    const $totalPrice = $('#total-price span');
    const $minWarning = $('#minimum-warning');
    const $addToCartButton = $('#add-to-cart-button');
    const $loadingIndicator = $('#loading-indicator');

    const minDishes = <?php echo $min_dishes; ?>;
    const maxDishes = <?php echo $max_dishes; ?>;
    const addToCartMode = '<?php echo esc_js($atts['add_to_cart_mode']); ?>';
    const productId = '<?php echo esc_js($atts['product_id']); ?>';

    function resetQuantities() {
        $('.dish-quantity').val(0);
        updateTotal();
    }

    $(window).on('pageshow', function(event) {
        resetQuantities();
    });

    function updateTotal() {
        let total = 0;
        let totalPrice = 0;

        $('.dish-quantity').each(function() {
            const quantity = parseInt($(this).val()) || 0;
            const price = parseFloat($(this).data('price')) || 0;
            total += quantity;
            totalPrice += quantity * price;
        });

        $totalDishes.text(total);
        $totalPrice.text('฿' + totalPrice.toFixed(2));

        const discountPercentage = <?php echo esc_js($discount_percentage); ?>;
        const discountAmount = totalPrice * (discountPercentage / 100);
        $('#hidden-discount').val(discountAmount.toFixed(2));

        if (total < minDishes || total > maxDishes) {
            $minWarning.show();
            $addToCartButton.prop('disabled', true);
        } else {
            $minWarning.hide();
            $addToCartButton.prop('disabled', false);
        }
    }

    $(document).on('click', '.plus', function() {
        const target = $(this).data('target');
        const $input = $(target);
        let val = parseInt($input.val()) || 0;
        $input.val(val + 1).trigger('input');
    });

    $(document).on('click', '.minus', function() {
        const target = $(this).data('target');
        const $input = $(target);
        let val = parseInt($input.val()) || 0;
        if (val > 0) {
            $input.val(val - 1).trigger('input');
        }
    });

    $(document).on('input', '.dish-quantity', updateTotal);

    $form.on('submit', function(e) {
        e.preventDefault();

        if (!$('#add_bundle_to_cart_nonce_field').val()) {
            alert('Security check failed.');
            return;
        }

        const formData = {
            action: 'add_bundle_to_cart_jby',
            dishes: {},
            mode: addToCartMode,
            discount_percentage: <?php echo esc_js($discount_percentage); ?>,
            product_id: productId,
            nonce: $('#add_bundle_to_cart_nonce_field').val()
        };

        $('.dish-quantity').each(function() {
            const qty = parseInt($(this).val());
            if (qty > 0) {
                const variationID = this.id.replace('dish-', '');
                formData.dishes[variationID] = qty;
            }
        });

        if ($.isEmptyObject(formData.dishes)) {
            alert('Please select at least one dish.');
            return;
        }

        $loadingIndicator.show();
        $('.progress-bar').animate({ width: '100%' }, 1000);
        $('#add-to-cart-button').prop('disabled', true);

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#redirect-text').css('display', 'flex');
                    $(document.body).trigger('wc_fragment_refresh');
                    window.location.replace('/staging/1802/cart-2/');
                } else {
                    alert(response.data || 'Failed to add items to cart.');
                }
            },
            error: function() {
                alert('Failed to process the request.');
            },
            complete: function() {
                $loadingIndicator.hide();
                $('#add-to-cart-button').prop('disabled', false);
            }
        });
    });

    function updateDishBundle() {
        const selectedCategories = $('.category-btn.active').map(function() {
            return $(this).data('value');
        }).get();
        const selectedTags = $('.tag-btn.active').map(function() {
            return $(this).data('value');
        }).get();

        let url = new URL(window.location.href);
        if (selectedCategories.length) {
            url.searchParams.set('category', selectedCategories.join(','));
        } else {
            url.searchParams.delete('category');
        }
        if (selectedTags.length) {
            url.searchParams.set('tag', selectedTags.join(','));
        } else {
            url.searchParams.delete('tag');
        }
        window.history.pushState({}, '', url);

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'update_dish_bundle',
                category: selectedCategories.join(','),
                tag: selectedTags.join(',')
            },
            beforeSend: function() {
                $('#full-page-loader').addClass('active');
                setTimeout(function() {
                    $('.dish-bundle-grid').css('opacity', '0.3');
                }, 100);
            },
            success: function(response) {
                $('.dish-bundle-grid').html(response).css('opacity', '1');
                updateTotal();
            },
            complete: function() {
                $('#full-page-loader').removeClass('active');
            },
            error: function() {
                $('.dish-bundle-grid').html('<p>Error loading products. Please try again.</p>').css('opacity', '1');
            }
        });
    }

    $('.filter-btn').on('click', function() {
        $(this).toggleClass('active');
        const $cross = $(this).find('.cross');
        if ($(this).hasClass('active')) {
            $cross.show();
        } else {
            $cross.hide();
        }
        updateDishBundle();
    });

    let urlParams = new URLSearchParams(window.location.search);
    let initialCategories = urlParams.get('category')?.split(',') || [];
    let initialTags = urlParams.get('tag')?.split(',') || [];

    $('.category-btn').each(function() {
        if (initialCategories.includes($(this).data('value'))) {
            $(this).addClass('active');
            $(this).find('.cross').show();
        }
    });
    $('.tag-btn').each(function() {
        if (initialTags.includes($(this).data('value'))) {
            $(this).addClass('active');
            $(this).find('.cross').show();
        }
    });
});
</script>

<!-- Styles -->
<style>
    :root .filter-buttons {
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .filter-btn {
        padding: 8px 12px;
        background: #fff;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        font-size: 16px;
        border-radius: 10px;
        border: 2px solid #077187;
        color: #077187;
        min-width: 105px;
        text-align: center;
        font-family: Manrope, sans-serif;
        font-weight: 400;
    }

    .filter-section {
        margin-bottom: 20px;
    }

    .filter-section h4 {
        margin-bottom: 10px;
    }

    .cross {
        margin-left: 5px;
        font-size: 24px;
        vertical-align: middle;
        float: right;
        height: 14px;
        display: flex;
        align-items: center;
        margin-top: 4px;
    }

    :where(.wp-site-blocks *:focus) {
        outline: 0px !important;
    }

    .full-page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
    }

    .full-page-loader.active {
        opacity: 1;
        visibility: visible;
    }

    .loader-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #077187;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 20px;
    }

    .loader-text {
        color: #333;
        font-size: 18px;
        font-weight: 500;
    }
    
    [type=button]:focus, [type=button]:hover, [type=submit]:focus, [type=submit]:hover, button:hover {
        color: #fff !important;
        background-color: #077187 !important;
        text-decoration: none;
    }
    
    [type=button]:focus, button:focus {
        color: #077187 !important;
        background-color: white !important;
        text-decoration: none;
    }
    
    .filter-btn.active {
        background: #077187 !important;
        color: white !important;
        border-color: #077187 !important;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media screen and (max-width:767px) {
        button.filter-btn.category-btn, button.filter-btn.tag-btn {
            width: 31%;
            background: inherit !important;
        border: none !important;
        padding: 5px !important;
        color: black;
        }
        .filter-btn {

    font-size: 14px !important;

}
.cross {
    font-size: 20px !important;
    margin-top: 4px !important;
    margin-left: 0px !important;
}
.filter-section h4 {
    font-size: 20px !important;
    padding-left: 10px !important;
}

button.filter-btn.category-btn.active, button.filter-btn.tag-btn.active {
    color: #077187 !important;
}
    }
    
</style>

<?php
    return ob_get_clean();
}

add_shortcode('dish_bundle_builder_jby', 'dish_bundle_builder_shortcode_jby');