<?php

/**

Plugin Name: WooCommerce Dish Bundle Builder Widget
Description: Adds a custom Elementor widget to display a WooCommerce dish bundle builder.
Version: 1.5
Author: James Brett-Young
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('DISH_BUNDLE_BUILDER_PATH', plugin_dir_path(__FILE__));
define('DISH_BUNDLE_BUILDER_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once DISH_BUNDLE_BUILDER_PATH . 'include/shortcode.php';

// Load scripts and styles
add_action('wp_enqueue_scripts', 'dish_bundle_builder_enqueue_scripts');
function dish_bundle_builder_enqueue_scripts()
{
    wp_enqueue_style('dish-bundle-builder-style', DISH_BUNDLE_BUILDER_URL . 'assets/style.css');
    wp_enqueue_script('dish-bundle-builder-script', DISH_BUNDLE_BUILDER_URL . 'assets/script.js');
}

///////////Widget Registration///////////

// Load Elementor Widget
function register_dish_bundle_widget()
{

    class Dish_Bundle_Builder_Widget extends \Elementor\Widget_Base
    {

        // Widget Name    
        public function get_name()
        {
            return 'dish_bundle_builder';
        }

        // Widget Title    
        public function get_title()
        {
            return __('Dish Bundle Builder', 'elementor');
        }

        // Widget Icon    
        public function get_icon()
        {
            return 'eicon-product-breadcrumbs';
        }

        // Widget Categories    
        public function get_categories()
        {
            return ['general'];
        }

        // Helper function to get WooCommerce categories    
        private function get_product_categories()
        {
            $args = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            );

            $terms = get_terms($args);
            $categories = array('' => __('All Categories', 'elementor')); // Add "All Categories" option    

            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    // Include parent category information in the array    
                    $categories[$term->slug] = $term->name;
                }
            }

            return $categories;
        }

        ///////////Controls and settings///////////		

        // Register Controls for the Widget    
        protected function register_controls()
        {
            $this->start_controls_section(
        'section_content',
        [
            'label' => __('Settings', 'plugin-name'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]
    );

    // Control to Show/Hide Category Filter
    $this->add_control(
        'show_category_filter',
        [
            'label' => __('Show Category Filter', 'plugin-name'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Show', 'plugin-name'),
            'label_off' => __('Hide', 'plugin-name'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]
    );

    // Control to Show/Hide Tag Filter
    $this->add_control(
        'show_tag_filter',
        [
            'label' => __('Show Tag Filter', 'plugin-name'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Show', 'plugin-name'),
            'label_off' => __('Hide', 'plugin-name'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]
    );

    $this->end_controls_section();
    
            $this->start_controls_section(
                'layout_section',
                [
                    'label' => __('Layout Settings', 'elementor'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            // Columns Control (Desktop)    
            $this->add_responsive_control(
                'columns',
                [
                    'label' => __('Number of Columns', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => 1,
                    'max' => 6,
                    'step' => 1,
                    'default' => 3,
                    'desktop_default' => 3, // Default for desktop    
                    'tablet_default' => 2,  // Default for tablet    
                    'mobile_default' => 1,  // Default for mobile    
                ]
            );

            // Spacing Control (Desktop)    
            $this->add_responsive_control(
                'spacing',
                [
                    'label' => __('Spacing Between Columns (px)', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => 0,
                    'max' => 50,
                    'step' => 1,
                    'default' => 10,
                    'desktop_default' => 10, // Default for desktop    
                    'tablet_default' => 8,   // Default for tablet    
                    'mobile_default' => 5,   // Default for mobile    
                ]
            );

            // Add control to select product type/category    
            $this->add_control(
                'product_category',
                [
                    'label' => __('Product Category', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'options' => $this->get_product_categories(),
                    'default' => '',
                    'multiple' => false,
                    'label_block' => true,
                ]
            );

            // Add control to choose product sorting order    
            $this->add_control(
                'product_order',
                [
                    'label' => __('Order Products By', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'menu_order' => __('Default Sorting', 'elementor'),
                        'date' => __('Date', 'elementor'),
                        'title' => __('Title', 'elementor'),
                        'price' => __('Price', 'elementor'),
                        'popularity' => __('Popularity', 'elementor'),
                        'rating' => __('Rating', 'elementor'),
                    ],
                    'default' => 'menu_order',
                ]
            );

            // Add control to specify order direction    
            $this->add_control(
                'product_order_direction',
                [
                    'label' => __('Order Direction', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'ASC' => __('Ascending', 'elementor'),
                        'DESC' => __('Descending', 'elementor'),
                    ],
                    'default' => 'ASC',
                ]
            );

            $this->add_control(
                'min_dishes',
                [
                    'label' => __('Minimum Number of Dishes', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'default' => 4, // Default minimum number of dishes    
                ]
            );

            // Add control to set maximum number of dishes    
            $this->add_control(
                'max_dishes',
                [
                    'label' => __('Maximum Number of Dishes', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'default' => 20, // Default maximum number of dishes    
                ]
            );

            // Add control to select add to cart mode    
            $this->add_control(
                'add_to_cart_mode',
                [
                    'label' => __('Add to Cart Mode', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'individual' => __('Individual Products', 'elementor'),
                        'bundle' => __('As a Bundle', 'elementor'),
                    ],
                    'default' => 'individual', // Default setting    
                ]
            );

            // Add control for Product ID (initially hidden)    
            $this->add_control(
                'product_id',
                [
                    'label' => __('Product ID', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => '', // Default is blank    
                    'description' => __('Enter the ID of the product to use for the bundle.', 'elementor'),
                    'condition' => [
                        'add_to_cart_mode' => 'bundle', // Only show if 'bundle' is selected    
                    ],
                ]
            );

            // Add Discount Control    
            $this->add_control(
                'discount_percentage',
                [
                    'label' => __('Discount Percentage', 'elementor'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => 0,
                    'max' => 100,
                    'step' => 1,
                    'default' => 0,
                    'description' => __('Enter the discount percentage to apply to the bundle.', 'elementor'),
                    'condition' => [
                        'add_to_cart_mode' => 'bundle', // Only show if 'bundle' is selected    
                    ],
                ]
            );

            $this->end_controls_section();

            // New Style Settings Section    
            $this->start_controls_section(
                'style_section',
                [
                    'label' => __('Style Settings', 'elementor'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            // Product Container    
            $this->add_control(
                'product_name',
                [
                    'label' => __('Product Name', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            // Font Size Control    
            $this->add_responsive_control(
                'product_name_font_size',
                [
                    'label' => __('Font Size', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'range' => [
                        'px' => [
                            'min' => 10,
                            'max' => 50,
                        ],
                        'em' => [
                            'min' => 0.5,
                            'max' => 5,
                        ],
                        'rem' => [
                            'min' => 0.5,
                            'max' => 5,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .product-name' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            // Color Control    
            $this->add_control(
                'product_name_color',
                [
                    'label' => __('Text Color', 'elementor'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .product-name' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'dish_item_padding',
                [
                    'label' => __('Product Padding', 'plugin-domain'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dish-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                    'default' => [
                        'top' => '10',
                        'right' => '10',
                        'bottom' => '10',
                        'left' => '10',
                        'unit' => 'px',
                    ],
                ]
            );

            // Variable Products Container    
            $this->add_control(
                'dish_sizes_heading',
                [
                    'label' => __('Variable Products Container', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'dish_sizes_padding',
                [
                    'label' => __('Padding', 'elementor'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dish-sizes' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'dish_sizes_margin',
                [
                    'label' => __('Margin', 'elementor'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dish-sizes' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );



            $this->add_responsive_control(
                'dish_sizes_gap',
                [
                    'label' => __('Variable Products Gap', 'plugin-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 50,
                            'step' => 1,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 10,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dish-sizes' => 'gap: {{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .dish-size-row' => 'grid-gap: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            // Dish Size Row    
            $this->add_control(
                'dish_size_row_heading',
                [
                    'label' => __('Variable Product Row', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'dish_size_row_padding',
                [
                    'label' => __('Padding', 'elementor'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dish-size-row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'dish_size_row_margin',
                [
                    'label' => __('Margin', 'elementor'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dish-size-row' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            // Variable Name Label    
            $this->add_control(
                'dish_size_label_heading',
                [
                    'label' => __('Variable Name Label', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'dish_size_label_font_size',
                [
                    'label' => __('Font Size', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'range' => [
                        'px' => [
                            'min' => 8,
                            'max' => 50,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dish-size label' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            // Variable Price    
            $this->add_control(
                'size_label_price_heading',
                [
                    'label' => __('Variable Price', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'size_label_price_font_size',
                [
                    'label' => __('Font Size', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'range' => [
                        'px' => [
                            'min' => 8,
                            'max' => 50,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dish-size-price' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            // Quantity Input Controls    
            $this->add_control(
                'quantity_input_heading',
                [
                    'label' => __('Quantity Input Field', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'quantity_input_width',
                [
                    'label' => __('Input Box Width', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range' => [
                        'px' => [
                            'min' => 20,
                            'max' => 100,
                            'step' => 1,
                        ],
                        'em' => [
                            'min' => 1,
                            'max' => 10,
                        ],
                        '%' => [
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 50,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .quantity-buttons input' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'quantity_input_height',
                [
                    'label' => __('Input Box Height', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range' => [
                        'px' => [
                            'min' => 20,
                            'max' => 100,
                            'step' => 1,
                        ],
                        'em' => [
                            'min' => 1,
                            'max' => 10,
                        ],
                        '%' => [
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 35,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .quantity-buttons input' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'dish_quantity_font_size',
                [
                    'label' => __('Font Size', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'range' => [
                        'px' => [
                            'min' => 8,
                            'max' => 50,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dish-quantity' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            // Quantity Button Controls    
            $this->add_control(
                'quantity_button_heading',
                [
                    'label' => __('Quantity Buttons (Plus-Minus)', 'elementor'),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control(
                'quantity_button_width',
                [
                    'label' => __('Button Width', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range' => [
                        'px' => [
                            'min' => 20,
                            'max' => 100,
                            'step' => 1,
                        ],
                        'em' => [
                            'min' => 1,
                            'max' => 10,
                        ],
                        '%' => [
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 35,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .quantity-buttons button[type="button"]' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'quantity_button_height',
                [
                    'label' => __('Button Height', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range' => [
                        'px' => [
                            'min' => 20,
                            'max' => 100,
                            'step' => 1,
                        ],
                        'em' => [
                            'min' => 1,
                            'max' => 10,
                        ],
                        '%' => [
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 36,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .quantity-buttons button[type="button"]' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'quantity_button_font_size',
                [
                    'label' => __('Button Font Size', 'elementor'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'range' => [
                        'px' => [
                            'min' => 8,
                            'max' => 50,
                            'step' => 1,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 16,  // Default font size for plus and minus signs  
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .quantity-buttons button[type="button"]' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();
        }

        ///////////Rendering and shortcode///////////		

        protected function render()
        {
            $settings = $this->get_settings_for_display();
    
            $product_id = $settings['product_id'];

            // Retrieve responsive settings for columns and spacing    
            $columns_desktop = $settings['columns'];
            $columns_tablet = $settings['columns_tablet'];
            $columns_mobile = $settings['columns_mobile'];
            $spacing_desktop = $settings['spacing'];
            $spacing_tablet = $settings['spacing_tablet'];
            $spacing_mobile = $settings['spacing_mobile'];
            $show_category_filter = $settings['show_category_filter'];
            $show_tag_filter = $settings['show_tag_filter'];

            // Inline CSS for grid layout (responsive)    
            echo "<style>    
			.dish-bundle-grid {    
				display: grid;    
				grid-template-columns: repeat({$settings['columns']}, minmax(0, 1fr));    
				gap: {$settings['spacing']}px;    
			}    

			@media (max-width: 1024px) {    
				.dish-bundle-grid {    
					grid-template-columns: repeat({$columns_tablet}, minmax(0, 1fr));    
					gap: {$spacing_tablet}px;    
				}    
			}    

			@media (max-width: 767px) {    
				.dish-bundle-grid {    
					grid-template-columns: repeat({$columns_mobile}, minmax(0, 1fr));    
					gap: {$spacing_mobile}px;    
				}    
			}    

			.dish-bundle-grid-item {    
				width: 100%; /* Ensure each grid item is flexible within the grid */    
			}  
	  
</style>";

            // Shortcode parameters with min and max dishes and add to cart mode settings    
            $shortcode_atts = array(
                'category' => esc_attr($settings['product_category']),
                'order' => esc_attr($settings['product_order']),
                'order_direction' => esc_attr($settings['product_order_direction']),
                'min_dishes' => esc_attr($settings['min_dishes']),
                'max_dishes' => esc_attr($settings['max_dishes']),
                'add_to_cart_mode' => esc_attr($settings['add_to_cart_mode']),
                'discount_percentage' => esc_attr($settings['discount_percentage']),
                'product_id' => esc_attr($settings['product_id']),
                'show_category_filter' => esc_attr($settings['show_category_filter']),
                'show_tag_filter' => esc_attr($settings['show_tag_filter']),
            );

            $shortcode = '[dish_bundle_builder_jby';
            foreach ($shortcode_atts as $key => $value) {
                if (!empty($value) || is_numeric($value)) { // Check for numeric values as well    
                    $shortcode .= sprintf(' %s="%s"', $key, $value);
                }
            }
            $shortcode .= ']';

            echo do_shortcode($shortcode);
        }
    }

    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Dish_Bundle_Builder_Widget());
}

add_action('elementor/widgets/widgets_registered', 'register_dish_bundle_widget');

///////////Interaction and AJAX handling///////////

// Function to create bundle data with specific product ID
function create_bundle_data($dishes, $shipping_class = '', $product_id)
{
    // Retrieve the base product object using the provided product ID
    $bundle = wc_get_product($product_id);

    // Check if the base product exists
    if (!$bundle) {
        wp_send_json_error('Product with ID ' . $product_id . ' not found.'); // Send an error response if the product is not found
    }

    $total_price = 0; // Initialize the total price of the bundle
    $bundle_items = array(); // Array to store details of each item in the bundle
    $item_names = array(); // Array to store names of items for the bundle description

    // Loop through each dish in the $dishes array
    foreach ($dishes as $variation_id => $quantity) {
        $variation = wc_get_product($variation_id); // Get the variation product object

        // Check if the variation product exists
        if ($variation) {
            $parent_product = wc_get_product($variation->get_parent_id()); // Get the parent product of the variation
            $item_price = $variation->get_price() * $quantity; // Calculate the total price for this variation
            $total_price += $item_price; // Add the item price to the total price

            // Add the variation details to the bundle items array
            $bundle_items[] = array(
                'product_id' => $variation->get_parent_id(), // Parent product ID
                'variation_id' => $variation_id, // Variation ID
                'quantity' => $quantity, // Quantity of this variation
                'price' => $variation->get_price(), // Price of the variation
                'name' => $parent_product->get_name(), // Name of the parent product
                'attributes' => $variation->get_variation_attributes() // Attributes of the variation
            );

            // Add the item name and quantity to the item names array for the bundle description
            $item_names[] = sprintf('%s x%d', $parent_product->get_name(), $quantity);
        }
    }

    // Use the existing name of the bundle product
    $bundle_name = $bundle->get_name();

    // Update the bundle product properties
    $bundle->set_name($bundle_name); // Set the name of the bundle product
    $bundle->set_status('private'); // Set the product status to private
    $bundle->set_catalog_visibility('hidden'); // Hide the product from the catalog
    $bundle->set_price($total_price); // Set the price of the bundle
    $bundle->set_regular_price($total_price); // Set the regular price of the bundle
    $bundle->set_description('Bundle contains: ' . implode(', ', $item_names)); // Set the description of the bundle

    // Set the shipping class ID if provided
    if (!empty($shipping_class)) {
        $bundle->set_shipping_class_id($shipping_class);
    }

    // Save the updated bundle product and get its ID
    $bundle_id = $bundle->save();

    // Return the bundle data as an array
    return array(
        'bundle_id' => $bundle_id, // ID of the created/updated bundle product
        'items' => $bundle_items, // Array of items included in the bundle
        'total_price' => $total_price // Total price of the bundle
    );
}

/**
 * Function to handle adding a bundle or individual items to the WooCommerce cart via AJAX.
 */
function add_bundle_to_cart_jby()
{
    // Verify the AJAX request's nonce for security
    check_ajax_referer('add_bundle_to_cart_nonce', 'nonce');

    // Validate input data
    if (!isset($_POST['dishes']) || !is_array($_POST['dishes']) || empty($_POST['dishes'])) {
        wp_send_json_error('Invalid dish data.'); // Send an error response if dishes data is invalid
    }

    $dishes = $_POST['dishes']; // Array of dishes with variation IDs and quantities
    $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'individual'; // Mode: 'bundle' or 'individual'
    $discount_percentage = isset($_POST['discount_percentage']) ? floatval($_POST['discount_percentage']) : 0; // Discount percentage
    $discount_percentage = max(0, min($discount_percentage, 100)); // Ensure discount is between 0 and 100

    // Validate product ID only if in bundle mode
    if ($mode === 'bundle') {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0; // Product ID for the bundle
        if (!$product_id) {
            wp_send_json_error('Product ID is required for bundle mode.'); // Send an error if product ID is missing
            return;
        }

        // Verify the product exists and is valid
        $bundle_product = wc_get_product($product_id);
        if (!$bundle_product) {
            wp_send_json_error('Invalid product ID or product not found.'); // Send an error if the product is invalid
            return;
        }
    }

    // Start a database transaction to ensure atomicity
    global $wpdb;
    $wpdb->query('START TRANSACTION');

    try {
        // Initialize variables for total price and selected dishes
        $total_price = 0;
        $selected_dishes = [];

        if ($mode === 'bundle') {
            // Validate dishes and calculate the total price for the bundle
            foreach ($dishes as $variation_id => $quantity) {
                if ($quantity > 0) {
                    $variation = wc_get_product($variation_id); // Get the variation product
                    if (!$variation) {
                        throw new Exception('Invalid variation ID: ' . $variation_id); // Throw an error if the variation is invalid
                    }

                    $total_price += $variation->get_price() * $quantity; // Add the price of this variation to the total
                    $selected_dishes[$variation_id] = $quantity; // Store the variation ID and quantity
                }
            }

            // Apply the discount if applicable
            if ($discount_percentage > 0) {
                $total_price -= ($total_price * ($discount_percentage / 100)); // Calculate discounted price
            }

            // Add the bundle product to the cart
            $bundle_cart_key = WC()->cart->add_to_cart(
                $product_id, // Product ID of the bundle
                1, // Quantity (always 1 for the bundle)
                0, // Variation ID (not applicable for bundles)
                [], // Variation attributes (not applicable for bundles)
                [
                    'selected_dishes' => $selected_dishes, // Selected dishes in the bundle
                    'bundled_price' => $total_price, // Total price of the bundle
                    '_bundle_parent' => true, // Mark this as a bundle parent
                    'discount_percentage' => $discount_percentage, // Discount percentage applied
                ]
            );

            if (!$bundle_cart_key) {
                throw new Exception('Failed to add bundle to cart.'); // Throw an error if adding the bundle fails
            }

            // Add individual items as children of the bundle
            foreach ($dishes as $variation_id => $quantity) {
                if ($quantity > 0) {
                    $variation = wc_get_product($variation_id); // Get the variation product
                    if (!$variation) {
                        throw new Exception('Invalid variation ID: ' . $variation_id); // Throw an error if the variation is invalid
                    }

                    $parent_id = $variation->get_parent_id(); // Get the parent product ID of the variation
                    $added = WC()->cart->add_to_cart(
                        $parent_id, // Parent product ID
                        $quantity, // Quantity of the variation
                        $variation_id, // Variation ID
                        [], // Variation attributes
                        [
                            '_bundle_key' => $bundle_cart_key, // Link this item to the bundle
                            '_price' => $variation->get_price(), // Price of the variation
                            '_regular_price' => $variation->get_regular_price(), // Regular price of the variation
                            '_bundled_item' => true // Mark this as a bundled item
                        ]
                    );

                    if (!$added) {
                        throw new Exception('Failed to add bundled item to cart for variation ID: ' . $variation_id); // Throw an error if adding the item fails
                    }
                }
            }
        } else {
            // Individual mode: Add products separately to the cart
            foreach ($dishes as $variation_id => $quantity) {
                if ($quantity > 0) {
                    $variation = wc_get_product($variation_id); // Get the variation product
                    if (!$variation) {
                        throw new Exception('Invalid variation ID: ' . $variation_id); // Throw an error if the variation is invalid
                    }

                    $parent_id = $variation->get_parent_id(); // Get the parent product ID of the variation
                    $added = WC()->cart->add_to_cart(
                        $parent_id, // Parent product ID
                        $quantity, // Quantity of the variation
                        $variation_id // Variation ID
                    );

                    if (!$added) {
                        throw new Exception('Failed to add item to cart for variation ID: ' . $variation_id); // Throw an error if adding the item fails
                    }
                }
            }
        }

        // Commit the transaction if everything succeeds
        $wpdb->query('COMMIT');

        // Send a success response
        wp_send_json_success([
            'message' => $mode === 'bundle' ? 'Bundle added to cart.' : 'Items added to cart individually.',
            'mode' => $mode
        ]);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $wpdb->query('ROLLBACK');

        // Log the error for debugging purposes
        error_log('Add bundle to cart error: ' . $e->getMessage());

        // Send an error response
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

add_action('wp_ajax_add_bundle_to_cart_jby', 'add_bundle_to_cart_jby');
add_action('wp_ajax_nopriv_add_bundle_to_cart_jby', 'add_bundle_to_cart_jby');

///////////WooCommerce cart integration///////////

// Display the bundled price in the cart
add_filter('woocommerce_cart_item_price', 'set_bundle_item_price', 10, 3);
function set_bundle_item_price($price, $cart_item, $cart_item_key)
{
    if (isset($cart_item['bundled_price'])) {
        $discount_note = '';
        if (isset($cart_item['discount_percentage']) && $cart_item['discount_percentage'] > 0) {
            // Calculate the original price before discount
            $original_price = $cart_item['bundled_price'] / (1 - ($cart_item['discount_percentage'] / 100));
            // Create the discount note with strikethrough for the original price    
            $discount_note = sprintf(
                '<br><small>(- %d%%: <span style="text-decoration: line-through;">%s</span>)</small>',
                $cart_item['discount_percentage'],
                wc_price($original_price)
            );
        }
        // Display the bundled price with the discount note    
        return wc_price($cart_item['bundled_price']) . $discount_note;
    }
    return $price;
}

// Disable quantity inputs for bundled items and bundle parent
add_filter('woocommerce_cart_item_quantity', 'disable_bundle_and_parent_quantity', 10, 3);
function disable_bundle_and_parent_quantity($quantity_html, $cart_item_key, $cart_item)
{
    if (isset($cart_item['_bundled_item']) || isset($cart_item['_bundle_parent'])) {
        return sprintf('<span class="quantity">%d</span>', $cart_item['quantity']);
    }
    return $quantity_html;
}


// Remove remove/quantity controls for bundled items
add_filter('woocommerce_cart_item_remove_link', 'remove_bundled_item_remove_link', 10, 2);
function remove_bundled_item_remove_link($link, $cart_item_key)
{
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    if (isset($cart_item['_bundled_item'])) {
        return '';
    }
    return $link;
}

// Handle bundle parent removal
add_action('woocommerce_cart_item_removed', 'remove_bundled_items_with_parent', 10, 2);
function remove_bundled_items_with_parent($cart_item_key, $cart)
{
    $removed_item = $cart->removed_cart_contents[$cart_item_key];
    // Check if the removed item was a bundle parent    
    if (isset($removed_item['_bundle_parent'])) {
        foreach ($cart->get_cart() as $key => $cart_item) {
            // Remove all bundled items associated with this parent    
            if (
                isset($cart_item['_bundled_item']) &&
                isset($cart_item['_bundle_key']) &&
                $cart_item['_bundle_key'] === $cart_item_key
            ) {
                $cart->remove_cart_item($key);
            }
        }
    }
}

// Ensure bundle quantities stay in sync and update cart totals
add_action('woocommerce_cart_item_set_quantity', 'sync_bundle_quantities', 10, 2);
function sync_bundle_quantities($cart_item_key, $quantity)
{
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    // Only process bundle parents    
    if (isset($cart_item['_bundle_parent'])) {
        $new_quantity = $cart_item['quantity'];

        foreach (WC()->cart->get_cart() as $key => $item) {
            if (
                isset($item['_bundled_item']) &&
                isset($item['_bundle_key']) &&
                $item['_bundle_key'] === $cart_item_key
            ) {
                $item['_original_quantity'] = $new_quantity; // Store original quantity for syncing    
                $item['quantity'] = $new_quantity; // Update quantity    
            }
        }

        // Force WooCommerce to recalculate cart totals    
        WC()->cart->calculate_totals();
    }
}

// Store original quantities for bundled items
add_filter('woocommerce_add_cart_item', 'store_original_bundled_quantity', 10, 2);
function store_original_bundled_quantity($cart_item_data, $cart_item_key)
{
    if (isset($cart_item_data['_bundled_item'])) {
        $cart_item_data['_original_quantity'] = $cart_item_data['quantity'];
    }
    return $cart_item_data;
}

// Update the subtotal display for bundle items
add_filter('woocommerce_cart_item_subtotal', 'set_bundle_item_subtotal', 10, 3);
function set_bundle_item_subtotal($subtotal, $cart_item, $cart_item_key)
{
    // For bundle parent items
    if (isset($cart_item['_bundle_parent']) && isset($cart_item['bundled_price'])) {
        return wc_price($cart_item['bundled_price'] * $cart_item['quantity']);
    }
    // For bundled items
    elseif (isset($cart_item['_bundled_item'])) {
        return ''; // Don't show subtotal for bundled items
    }
    return $subtotal;
}

// Adjust cart totals calculation to set bundled item prices to zero
add_action('woocommerce_before_calculate_totals', 'set_bundled_items_to_zero', 10, 1);
function set_bundled_items_to_zero($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['_bundled_item'])) {
            $cart_item['data']->set_price(0); // Set price to zero for bundled items
        }
    }
}



///////////Order processing///////////

// Track individual product sales when order is completed - applies to both modes
add_action('woocommerce_order_status_completed', 'track_bundled_items_sales', 10, 1);
function track_bundled_items_sales($order_id)
{
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item) {
        $bundle_data = $item->get_meta('_bundle_data');

        if ($bundle_data) {
            foreach ($bundle_data as $bundled_item) {
                // Update sales count for individual products  
                $product_id = $bundled_item['product_id'];
                $variation_id = $bundled_item['variation_id'];
                $quantity = $bundled_item['quantity'];

                // Update product sales count  
                $product = wc_get_product($variation_id ? $variation_id : $product_id);
                if ($product) {
                    $current_sales = (int)get_post_meta($product->get_id(), 'total_sales', true);
                    update_post_meta($product->get_id(), 'total_sales', $current_sales + $quantity);
                }
            }
        }
    }
}

// Sort cart items - only apply to bundle mode
add_filter('woocommerce_cart_sorted_cart_items', 'sort_bundled_cart_items', 10, 1);
function sort_bundled_cart_items($cart_items)
{
    // Only sort if we have bundle items
    $has_bundles = false;
    foreach ($cart_items as $cart_item) {
        if (isset($cart_item['_bundle_parent']) || isset($cart_item['_bundled_item'])) {
            $has_bundles = true;
            break;
        }
    }
    if (!$has_bundles) {
        return $cart_items;
    }

    $sorted_items = array();
    $bundled_items = array();

    // First pass: separate bundle parents and their children  
    foreach ($cart_items as $cart_item_key => $cart_item) {
        if (isset($cart_item['_bundle_parent'])) {
            $sorted_items[$cart_item_key] = $cart_item;
        } elseif (isset($cart_item['_bundled_item'])) {
            $bundle_key = $cart_item['_bundle_key'];
            if (!isset($bundled_items[$bundle_key])) {
                $bundled_items[$bundle_key] = array();
            }
            $bundled_items[$bundle_key][$cart_item_key] = $cart_item;
        } else {
            $sorted_items[$cart_item_key] = $cart_item;
        }
    }

    // Second pass: insert bundled items after their parents  
    $final_items = array();
    foreach ($sorted_items as $cart_item_key => $cart_item) {
        $final_items[$cart_item_key] = $cart_item;
        if (isset($cart_item['_bundle_parent']) && isset($bundled_items[$cart_item_key])) {
            foreach ($bundled_items[$cart_item_key] as $bundled_key => $bundled_item) {
                $final_items[$bundled_key] = $bundled_item;
            }
        }
    }

    return $final_items;
}

add_action('wp_ajax_update_dish_bundle', 'update_dish_bundle_callback');
add_action('wp_ajax_nopriv_update_dish_bundle', 'update_dish_bundle_callback');

function update_dish_bundle_callback() {
    $categories = isset($_POST['category']) ? array_filter(explode(',', sanitize_text_field($_POST['category']))) : [];
    $tags = isset($_POST['tag']) ? array_filter(explode(',', sanitize_text_field($_POST['tag']))) : [];

    $args = [
        'status' => 'publish',
        'limit' => -1,
    ];

    $tax_query = ['relation' => 'AND']; // Changed from AND to OR for outer relation

    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $categories,
            'operator' => 'IN' // Changed from AND to IN
        ];
    }

    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => $tags,
            'operator' => 'IN' // Changed from AND to IN
        ];
    }

    if (!empty($categories) || !empty($tags)) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WC_Product_Query($args);
    $products = $query->get_products();

    if (!empty($products)) {
        ob_start();
        
        foreach ($products as $product) {
            ?>
            <div class="dish-item" style="position: relative;">
                <div class="dish-image"><?= $product->get_image(); ?></div>
                <span class="product-name"><?= esc_html($product->get_name()); ?></span>
                <div class="dish-price"><?= wc_price($product->get_price()); ?></div>
                <?= do_shortcode('[woosq id="' . $product->get_id() . '" type="icon"]'); ?>
                <div class="quantity-buttons">
                    <button type="button" class="minus" data-target="#dish-<?= $product->get_id(); ?>">-</button>
                    <input type="number" 
                           id="dish-<?= $product->get_id(); ?>" 
                           name="dish[<?= $product->get_id(); ?>]" 
                           value="0" 
                           min="0" 
                           class="dish-quantity" 
                           data-price="<?= esc_attr($product->get_price()); ?>">
                    <button type="button" class="plus" data-target="#dish-<?= $product->get_id(); ?>">+</button>
                </div>
            </div>
            <?php
        }
        
        $output = ob_get_clean();
        wp_reset_postdata();
        
        echo $output;
    } else {
        echo '<p>No products found.</p>';
    }

    wp_die();
}