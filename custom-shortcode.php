<?php
/*
Plugin Name: Custom Shortcode Swatches
Description: Adds color and size swatches for WooCommerce variable products with shortcodes [swatchly_color_swatches] and [swatchly_size_swatches].
Version: 1.0.7
Author: Lucuma Agency
Author URI: https://lucumaagency.com/
License: GPL-2.0+
Text Domain: lucuma-swatches
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Check for WooCommerce dependency
register_activation_hook(__FILE__, 'lucuma_swatches_check_requirements');
function lucuma_swatches_check_requirements() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Lucuma Swatches requires WooCommerce to be installed and active.', 'lucuma-swatches'));
    }
}

// Enqueue jQuery
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
});

// Color Swatches Shortcode
add_shortcode('swatchly_color_swatches', function($atts) {
    error_log('swatchly_color_swatches executed for product ID: ' . (isset($atts['product_id']) ? $atts['product_id'] : 'unknown'));

    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(),
    ), $atts);

    $product = wc_get_product($atts['product_id']);
    if (!$product) {
        error_log('No product found for ID: ' . $atts['product_id']);
        return 'No se encontró el producto';
    }
    if (!$product->is_type('variable')) {
        error_log('Product ID ' . $atts['product_id'] . ' is not variable');
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    error_log('Attributes for product ID ' . $atts['product_id'] . ': ' . print_r($attributes, true));

    $attribute_name = 'pa_colors';
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();

    if (isset($attributes[$attribute_name]) && !empty($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
        error_log('Color options for product ID ' . $atts['product_id'] . ': ' . print_r($options, true));
        ?>
        <div class="swatchly-color-swatches-wrapper" data-product_id="<?php echo esc_attr($atts['product_id']); ?>">
            <div class="variations swatchly_variation_wrap swatchly_color_swatches" data-variations='<?php echo esc_attr(json_encode($product->get_available_variations())); ?>'>
                <?php 
                $color_values = [];
                foreach ($options as $option) : 
                    $term = get_term_by('slug', sanitize_title($option), 'pa_colors');
                    $color_value = '';

                    $color_map = [
                        'yellow' => '#FFFF00',
                        'red' => '#FF0000',
                    ];
                    $color_value = isset($color_map[strtolower($option)]) ? $color_map[strtolower($option)] : '';

                    if (empty($color_value) && $term) {
                        $meta_keys = ['viwpvs_attribute_color', 'swatch_color', 'product_swatch_color', 'color'];
                        foreach ($meta_keys as $key) {
                            $value = get_term_meta($term->term_id, $key, true);
                            if ($value) {
                                $color_value = $value;
                                break;
                            }
                        }
                    }

                    if (empty($color_value)) {
                        $color_value = '#000000';
                    }

                    $color_values[$option] = $color_value;
                ?>
                    <span class="swatchly_swatch swatchly_color_swatch" 
                          data-value="<?php echo esc_attr($option); ?>" 
                          style="background-color: <?php echo esc_attr($color_value); ?>;" 
                          title="<?php echo esc_attr($option); ?>"></span>
                <?php endforeach; ?>
                <input type="hidden" id="attribute_<?php echo esc_attr($attribute_name); ?>_<?php echo esc_attr($atts['product_id']); ?>" 
                       name="attribute_<?php echo esc_attr($attribute_name); ?>" 
                       data-attribute_name="attribute_<?php echo esc_attr($attribute_name); ?>">
            </div>
            <script>
                console.log('Swatchly color swatches loaded for product ID <?php echo esc_js($atts['product_id']); ?>', {
                    colors: <?php echo json_encode($color_values); ?>
                });
            </script>
        </div>
        <style>
            .swatchly-color-swatches-wrapper .swatchly_color_swatches {
                display: flex;
                gap: 10px;
                margin: 10px 0;
                flex-wrap: wrap;
            }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                border: 2px solid #ccc;
                transition: border-color 0.3s;
                flex-shrink: 0;
            }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.selected {
                border-color: #000;
            }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch:hover {
                border-color: #666;
            }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.out-of-stock {
                position: relative;
                opacity: 0.5;
                cursor: not-allowed;
            }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.out-of-stock::after {
                content: 'X';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 18px;
                color: #fff;
                font-weight: bold;
                text-shadow: 0 0 2px #000;
            }
            .swatchly-color-swatches-wrapper .cart-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #ffffff;
                color: #000000;
                padding: 10px 20px;
                border-radius: 5px;
                border: 1px solid #808080;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .swatchly-color-swatches-wrapper .cart-notification.show {
                opacity: 1;
            }
        </style>
        <?php
    } else {
        error_log('Color attribute pa_colors not found for product ID ' . $atts['product_id']);
        return 'Atributo no encontrado: ' . $attribute_name;
    }
    $output = ob_get_clean();
    error_log('swatchly_color_swatches output for product ID ' . $atts['product_id'] . ': ' . (empty($output) ? 'empty' : 'generated'));
    return $output;
});

// Size Swatches Shortcode
add_shortcode('swatchly_size_swatches', function($atts) {
    static $initialized = false;
    error_log('swatchly_size_swatches executed for product ID: ' . (isset($atts['product_id']) ? $atts['product_id'] : 'unknown'));

    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(),
    ), $atts);

    $product = wc_get_product($atts['product_id']);
    if (!$product) {
        error_log('No product found for ID: ' . $atts['product_id']);
        return 'No se encontró el producto';
    }
    if (!$product->is_type('variable')) {
        error_log('Product ID ' . $atts['product_id'] . ' is not variable');
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    error_log('Attributes for product ID ' . $atts['product_id'] . ': ' . print_r($attributes, true));

    $attribute_name = 'Size';
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();
    if (isset($attributes[$attribute_name]) && !empty($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
        error_log('Size options for product ID ' . $atts['product_id'] . ': ' . print_r($options, true));
        ?>
        <div class="swatchly-size-swatches-wrapper" data-product_id="<?php echo esc_attr($atts['product_id']); ?>">
            <div class="variations swatchly_variation_wrap swatchly_size_swatches" data-variations='<?php echo esc_attr(json_encode($product->get_available_variations())); ?>'>
                <?php foreach ($options as $option) : ?>
                    <span class="swatchly_swatch swatchly_size_swatch" 
                          data-value="<?php echo esc_attr($option); ?>">
                        <?php echo esc_html($option); ?>
                    </span>
                <?php endforeach; ?>
                <input type="hidden" id="attribute_<?php echo esc_attr($attribute_name); ?>_<?php echo esc_attr($atts['product_id']); ?>" 
                       name="attribute_<?php echo esc_attr($attribute_name); ?>" 
                       data-attribute_name="attribute_<?php echo esc_attr($attribute_name); ?>">
            </div>
        </div>
        <style>
            .swatchly-size-swatches-wrapper .swatchly_size_swatches {
                display: flex;
                gap: 10px;
                margin: 10px 0;
                flex-wrap: wrap;
            }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                border: 2px solid #ccc;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                font-size: 14px;
                font-weight: bold;
                background-color: #fff;
                transition: border-color 0.3s, background-color 0.3s;
                line-height: 1;
                position: relative;
                flex-shrink: 0;
            }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.selected {
                border-color: #000;
                background-color: #f0f0f0;
            }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch:hover {
                border-color: #666;
            }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.out-of-stock {
                position: relative;
                opacity: 0.5;
                cursor: not-allowed;
            }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.out-of-stock::after {
                content: 'X';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 18px;
                color: #fff;
                font-weight: bold;
                text-shadow: 0 0 2px #000;
            }
            .swatchly-size-swatches-wrapper .cart-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #28a745;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .swatchly-size-swatches-wrapper .cart-notification.show {
                opacity: 1;
            }
        </style>
        <?php if (!$initialized) : $initialized = true; ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('Swatchly size JS initialized');

                function showNotification(message) {
                    let notification = $('.swatchly-color-swatches-wrapper .cart-notification, .swatchly-size-swatches-wrapper .cart-notification');
                    if (!notification.length) {
                        notification = $('<div class="cart-notification"></div>').appendTo('body');
                    }
                    notification.text(message).addClass('show');
                    setTimeout(() => notification.removeClass('show'), 3000);
                }

                function addToCart(productId, variationId, attributes) {
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: {
                            action: 'woocommerce_add_to_cart',
                            product_id: productId,
                            variation_id: variationId,
                            quantity: 1,
                            nonce: '<?php echo wp_create_nonce('woocommerce-add-to-cart'); ?>',
                            ...attributes
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && (response.fragments || response.cart_hash)) {
                                $(document.body).trigger('wc_fragment_refresh');
                                showNotification('Producto añadido al carrito');
                            } else {
                                console.log('Add to cart failed:', response);
                                showNotification('Error al añadir al carrito');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Add to cart error:', error);
                            showNotification('Error al añadir al carrito');
                        }
                    });
                }

                function findVariation(productId, selectedColor, selectedSize, variations) {
                    let matchedVariation = null;
                    $.each(variations, function(index, variation) {
                        let sizeAttr = String(variation.attributes['attribute_size'] || '');
                        let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                        if (colorAttr === selectedColor && sizeAttr === selectedSize) {
                            matchedVariation = variation;
                            return false;
                        }
                    });
                    return matchedVariation;
                }

                function checkAndAddToCart(productId, variations) {
                    let selectedColor = String($('#attribute_pa_colors_' + productId).val() || '');
                    let selectedSize = String($('#attribute_Size_' + productId).val() || '');
                    if (selectedColor && selectedSize) {
                        let matchedVariation = findVariation(productId, selectedColor, selectedSize, variations);
                        if (matchedVariation && matchedVariation.is_in_stock) {
                            let attributes = {
                                attribute_pa_colors: selectedColor,
                                attribute_size: selectedSize
                            };
                            addToCart(productId, matchedVariation.variation_id, attributes);
                        } else {
                            console.log('No matching variation or out of stock:', {color: selectedColor, size: selectedSize});
                        }
                    }
                }

                function updateAvailability(productId, selectedColor, selectedSize, variations) {
                    let $colorSwatches = $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch');
                    let $sizeSwatches = $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch');

                    if (selectedColor) {
                        $sizeSwatches.each(function() {
                            let sizeValue = String($(this).data('value') || '');
                            let isAvailable = false;
                            $.each(variations, function(index, variation) {
                                let sizeAttr = String(variation.attributes['attribute_size'] || '');
                                let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                                if (colorAttr === selectedColor && sizeAttr === sizeValue && variation.is_in_stock) {
                                    isAvailable = true;
                                    return false;
                                }
                            });
                            $(this).toggleClass('out-of-stock', !isAvailable);
                        });
                    }

                    if (selectedSize) {
                        $colorSwatches.each(function() {
                            let colorValue = String($(this).data('value') || '');
                            let isAvailable = false;
                            $.each(variations, function(index, variation) {
                                let sizeAttr = String(variation.attributes['attribute_size'] || '');
                                let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                                if (sizeAttr === selectedSize && colorAttr === colorValue && variation.is_in_stock) {
                                    isAvailable = true;
                                    return false;
                                }
                            });
                            $(this).toggleClass('out-of-stock', !isAvailable);
                        });
                    }
                }

                // Color Swatches
                $('.swatchly-color-swatches-wrapper').each(function() {
                    let $wrapper = $(this);
                    let productId = $wrapper.data('product_id');
                    let variations = JSON.parse($wrapper.find('.swatchly_color_swatches').attr('data-variations') || '[]');
                    let $colorInput = $('#attribute_pa_colors_' + productId);
                    let featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    let currentImage = featuredImage;

                    // Find the closest product container and its image
                    let $productContainer = $wrapper.closest('.product, .post, .type-product');
                    let $imageElement = $productContainer.find('.elementor-element-0515b16 img.wvs-archive-product-image');

                    // Fallback selectors if primary fails
                    if (!$imageElement.length) {
                        console.warn('Primary image selector failed for product ID ' + productId);
                        $imageElement = $productContainer.find('.wp-post-image');
                        if (!$imageElement.length) {
                            $imageElement = $productContainer.find('.woocommerce-product-gallery__image img');
                        }
                        if (!$imageElement.length) {
                            console.error('No image element found for product ID ' + productId);
                        } else {
                            console.log('Fallback image element found for product ID ' + productId, $imageElement);
                        }
                    } else {
                        console.log('Image element found for product ID ' + productId, $imageElement);
                    }

                    // Log variations for debugging
                    console.log('Variations for product ID ' + productId, variations);

                    $wrapper.find('.swatchly_color_swatch').on('mouseenter', function() {
                        let selectedColor = String($(this).data('value') || '');
                        let selectedSize = String($('#attribute_Size_' + productId).val() || '');
                        let matchedVariation = null;

                        console.log('Hover on color:', selectedColor, 'Product ID:', productId, 'Selected Size:', selectedSize);

                        $.each(variations, function(index, variation) {
                            let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                            let sizeAttr = String(variation.attributes['attribute_size'] || '');
                            if (colorAttr === selectedColor && (!selectedSize || sizeAttr === selectedSize)) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            if ($imageElement.length) {
                                $imageElement.attr('src', currentImage).attr('srcset', '');
                                console.log('Image updated to:', currentImage, 'for product ID:', productId);
                            } else {
                                console.error('Image element not available to update for product ID:', productId);
                            }
                        } else {
                            console.log('No matching variation or image found for color:', selectedColor, 'Product ID:', productId, 'Variation:', matchedVariation);
                            if ($imageElement.length) {
                                $imageElement.attr('src', featuredImage).attr('srcset', '');
                            }
                        }
                    });

                    $wrapper.on('mouseleave', '.swatchly_color_swatches', function() {
                        console.log('Color mouseleave, restoring image:', currentImage, 'Product ID:', productId);
                        if ($imageElement.length) {
                            $imageElement.attr('src', currentImage).attr('srcset', '');
                        }
                    });

                    $wrapper.find('.swatchly_color_swatch').on('click', function(e) {
                        e.preventDefault();
                        let selectedColor = String($(this).data('value') || '');
                        $wrapper.find('.swatchly_color_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $colorInput.val(selectedColor);

                        let selectedSize = String($('#attribute_Size_' + productId).val() || '');
                        let matchedVariation = null;

                        $.each(variations, function(index, variation) {
                            let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                            let sizeAttr = String(variation.attributes['attribute_size'] || '');
                            if (colorAttr === selectedColor && sizeAttr === selectedSize) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            if ($imageElement.length) {
                                $imageElement.attr('src', currentImage).attr('srcset', '');
                                console.log('Image updated on click to:', currentImage, 'for product ID:', productId);
                            }
                        }

                        updateAvailability(productId, selectedColor, null, variations);
                        checkAndAddToCart(productId, variations);
                    });
                });

                // Size Swatches
                $('.swatchly-size-swatches-wrapper').each(function() {
                    let $wrapper = $(this);
                    let productId = $wrapper.data('product_id');
                    let variations = JSON.parse($wrapper.find('.swatchly_size_swatches').attr('data-variations') || '[]');
                    let $sizeInput = $('#attribute_Size_' + productId);
                    let featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    let currentImage = featuredImage;

                    // Find the closest product container and its image
                    let $productContainer = $wrapper.closest('.product, .post, .type-product');
                    let $imageElement = $productContainer.find('.elementor-element-0515b16 img.wvs-archive-product-image');

                    // Fallback selectors if primary fails
                    if (!$imageElement.length) {
                        console.warn('Primary image selector failed for product ID ' + productId);
                        $imageElement = $productContainer.find('.wp-post-image');
                        if (!$imageElement.length) {
                            $imageElement = $productContainer.find('.woocommerce-product-gallery__image img');
                        }
                        if (!$imageElement.length) {
                            console.error('No image element found for product ID ' + productId);
                        } else {
                            console.log('Fallback image element found for product ID ' + productId, $imageElement);
                        }
                    } else {
                        console.log('Image element found for product ID ' + productId, $imageElement);
                    }

                    $wrapper.find('.swatchly_size_swatch').on('mouseenter', function() {
                        let selectedSize = String($(this).data('value') || '');
                        let selectedColor = String($('#attribute_pa_colors_' + productId).val() || '');
                        let matchedVariation = null;

                        console.log('Hover on size:', selectedSize, 'Product ID:', productId, 'Selected Color:', selectedColor);

                        $.each(variations, function(index, variation) {
                            let sizeAttr = String(variation.attributes['attribute_size'] || '');
                            let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                            if (sizeAttr === selectedSize && (!selectedColor || colorAttr === selectedColor)) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            if ($imageElement.length) {
                                $imageElement.attr('src', currentImage).attr('srcset', '');
                                console.log('Image updated to:', currentImage, 'for product ID:', productId);
                            } else {
                                console.error('Image element not available to update for product ID:', productId);
                            }
                        } else {
                            console.log('No matching variation or image found for size:', selectedSize, 'Product ID:', productId, 'Variation:', matchedVariation);
                            if ($imageElement.length) {
                                $imageElement.attr('src', featuredImage).attr('srcset', '');
                            }
                        }
                    });

                    $wrapper.on('mouseleave', '.swatchly_size_swatches', function() {
                        console.log('Size mouseleave, restoring image:', currentImage, 'Product ID:', productId);
                        if ($imageElement.length) {
                            $imageElement.attr('src', currentImage).attr('srcset', '');
                        }
                    });

                    $wrapper.find('.swatchly_size_swatch').on('click', function(e) {
                        e.preventDefault();
                        let selectedSize = String($(this).data('value') || '');
                        $wrapper.find('.swatchly_size_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $sizeInput.val(selectedSize);

                        let selectedColor = String($('#attribute_pa_colors_' + productId).val() || '');
                        let matchedVariation = null;

                        $.each(variations, function(index, variation) {
                            let sizeAttr = String(variation.attributes['attribute_size'] || '');
                            let colorAttr = String(variation.attributes['attribute_pa_colors'] || '');
                            if (sizeAttr === selectedSize && colorAttr === selectedColor) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            if ($imageElement.length) {
                                $imageElement.attr('src', currentImage).attr('srcset', '');
                                console.log('Image updated on click to:', currentImage, 'for product ID:', productId);
                            }
                        }

                        updateAvailability(productId, null, selectedSize, variations);
                        checkAndAddToCart(productId, variations);
                    });
                });
            });
        </script>
        <?php endif; ?>
        <?php
    } else {
        error_log('Size attribute not found for product ID ' . $atts['product_id']);
        return 'Atributo no encontrado: ' . $attribute_name;
    }
    $output = ob_get_clean();
    error_log('swatchly_size_swatches output for product ID ' . $atts['product_id'] . ': ' . (empty($output) ? 'empty' : 'generated'));
    return $output;
});
?>
