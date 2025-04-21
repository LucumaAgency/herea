<?php
/*
Plugin Name: Custom Shortcode Swatches
Description: Adds color and size swatches for WooCommerce variable products.
Version: 1.0.35
Author: Lucuma Agency
License: GPL-2.0+
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Check WooCommerce
register_activation_hook(__FILE__, function() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Lucuma Swatches requires WooCommerce.', 'lucuma-swatches'));
    }
});

// Enqueue jQuery
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
});

// Color Swatches Shortcode
add_shortcode('swatchly_color_swatches', function($atts) {
    $atts = shortcode_atts(['product_id' => get_the_ID()], $atts);
    $product = wc_get_product($atts['product_id']);
    if (!$product || !$product->is_type('variable')) {
        error_log('Swatchly: Color swatches - Product ' . $atts['product_id'] . ' is not a variable product');
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    $attribute_names = ['pa_color', 'Color'];
    $attribute_name = '';
    foreach ($attribute_names as $name) {
        if (isset($attributes[$name]) && !empty($attributes[$name])) {
            $attribute_name = $name;
            break;
        }
    }
    error_log('Swatchly: Color swatches - Product ' . $atts['product_id'] . ' - Attribute name: ' . ($attribute_name ?: 'none') . ', Options: ' . json_encode($attributes[$attribute_name] ?? []));
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();

    if ($attribute_name && !empty($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
        ?>
        <div class="swatchly-color-swatches-wrapper" data-product_id="<?php echo esc_attr($atts['product_id']); ?>">
            <div class="variations swatchly_variation_wrap swatchly_color_swatches" data-variations='<?php echo esc_attr(json_encode($product->get_available_variations())); ?>'>
                <?php 
                $color_values = [];
                foreach ($options as $option) : 
                    $term = get_term_by('slug', sanitize_title($option), $attribute_name);
                    $color_value = '';
                    if ($term) {
                        $meta_keys = ['viwpvs_attribute_color', 'woolentor_attribute_swatch_color', 'product_attribute_color', 'attribute_color', 'swatch_color', 'color'];
                        foreach ($meta_keys as $key) {
                            $color_value = get_term_meta($term->term_id, $key, true);
                            if (!empty($color_value)) break;
                        }
                    }
                    if (empty($color_value)) {
                        $color_value = '#CCCCCC';
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
        </div>
        <style>
            .swatchly-color-swatches-wrapper .swatchly_color_swatches { display: flex; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch { width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid #ccc; transition: border-color 0.3s; flex-shrink: 0; }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.selected { border-color: #000; }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch:hover { border-color: #666; }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.out-of-stock { position: relative; opacity: 0.5; cursor: not-allowed; }
            .swatchly-color-swatches-wrapper .swatchly_color_swatch.out-of-stock::after { content: 'X'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 18px; color: #fff; font-weight: bold; text-shadow: 0 0 2px #000; }
            .swatchly-color-swatches-wrapper .cart-notification { position: fixed; top: 20px; right: 20px; background-color: #ffffff; color: #000000; padding: 10px 20px; border-radius: 5px; border: 1px solid #808080; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
            .swatchly-color-swatches-wrapper .cart-notification.show { opacity: 1; }
        </style>
        <?php
        $output = ob_get_clean();
        error_log('Swatchly: Color swatches - Product ' . $atts['product_id'] . ' - HTML output length: ' . strlen($output));
        return $output;
    } else {
        return 'Atributo de color no encontrado';
    }
});

// Size Swatches Shortcode
add_shortcode('swatchly_size_swatches', function($atts) {
    static $initialized = false;
    $atts = shortcode_atts(['product_id' => get_the_ID()], $atts);
    $product = wc_get_product($atts['product_id']);
    if (!$product || !$product->is_type('variable')) {
        error_log('Swatchly: Size swatches - Product ' . $atts['product_id'] . ' is not a variable product');
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    $attribute_names = ['Size', 'pa_size'];
    $attribute_name = '';
    foreach ($attribute_names as $name) {
        if (isset($attributes[$name]) && !empty($attributes[$name])) {
            $attribute_name = $name;
            break;
        }
    }
    error_log('Swatchly: Size swatches - Product ' . $atts['product_id'] . ' - Attribute name: ' . ($attribute_name ?: 'none') . ', Options: ' . json_encode($attributes[$attribute_name] ?? []));
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();

    if ($attribute_name && !empty($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
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
            .swatchly-size-swatches-wrapper .swatchly_size_swatches { display: flex; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch { width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid #ccc; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 14px; font-weight: bold; background-color: #fff; transition: border-color 0.3s, background-color 0.3s; line-height: 1; position: relative; flex-shrink: 0; }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.selected { border-color: #000; background-color: #f0f0f0; }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch:hover { border-color: #666; }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.out-of-stock { position: relative; opacity: 0.5; cursor: not-allowed; }
            .swatchly-size-swatches-wrapper .swatchly_size_swatch.out-of-stock::after { content: 'X'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 18px; color: #fff; font-weight: bold; text-shadow: 0 0 2px #000; }
            .swatchly-size-swatches-wrapper .cart-notification { position: fixed; top: 20px; right: 20px; background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
            .swatchly-size-swatches-wrapper .cart-notification.show { opacity: 1; }
        </style>
        <?php if (!$initialized) : $initialized = true; ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function showNotification(message) {
                    let notification = $('.swatchly-color-swatches-wrapper .cart-notification, .swatchly-size-swatches-wrapper .cart-notification');
                    if (!notification.length) notification = $('<div class="cart-notification"></div>').appendTo('body');
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
                        success: function(response) {
                            if (response && (response.fragments || response.cart_hash)) {
                                $(document.body).trigger('wc_fragment_refresh');
                                showNotification('Producto añadido al carrito');
                            } else {
                                showNotification('Error al añadir al carrito');
                            }
                        },
                        error: function(xhr, status, error) {
                            showNotification('Error al añadir al carrito');
                        }
                    });
                }

                function findVariation(productId, selectedColor, selectedSize, variations) {
                    let matchedVariation = null;
                    $.each(variations, function(index, variation) {
                        let sizeAttr = String(variation.attributes['attribute_size'] || variation.attributes['attribute_pa_size'] || '');
                        let colorAttr = String(variation.attributes['attribute_pa_color'] || variation.attributes['attribute_color'] || '');
                        if (colorAttr === selectedColor && sizeAttr === selectedSize) {
                            matchedVariation = variation;
                            return false;
                        }
                    });
                    return matchedVariation;
                }

                function findVariationForHover(productId, attribute, value, variations) {
                    let matchedVariation = null;
                    $.each(variations, function(index, variation) {
                        let attrValue = String(variation.attributes[attribute] || '');
                        if (attrValue === value) {
                            matchedVariation = variation;
                            return false;
                        }
                    });
                    return matchedVariation;
                }

                function updateAvailability(productId, selectedColor, selectedSize, variations) {
                    console.log('Swatchly: updateAvailability called', { productId: productId, selectedSize: selectedSize, selectedColor: selectedColor });
                    console.log('Swatchly: Variations data', { productId: productId, variations: variations });
                    if (variations.length > 0) {
                        console.log('Swatchly: Variation attributes', { productId: productId, attributes: Object.keys(variations[0].attributes) });
                    }
                    let $colorSwatches = $('.swatchly-color-swatches-wrapper[data-product_id="' + productId + '"] .swatchly_color_swatch, .swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch');
                    let $sizeSwatches = $('.swatchly-size-swatches-wrapper[data-product_id="' + productId + '"] .swatchly_size_swatch, .swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch');
                    console.log('Swatchly: Color swatches selector', { 
                        productId: productId, 
                        selector: '.swatchly-color-swatches-wrapper[data-product_id="' + productId + '"] .swatchly_color_swatch, .swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch', 
                        count: $colorSwatches.length,
                        wrapperCount: $('.swatchly-color-swatches-wrapper[data-product_id="' + productId + '"]').length
                    });
                    console.log('Swatchly: Size swatches selector', { 
                        productId: productId, 
                        selector: '.swatchly-size-swatches-wrapper[data-product_id="' + productId + '"] .swatchly_size_swatch, .swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch', 
                        count: $sizeSwatches.length,
                        wrapperCount: $('.swatchly-size-swatches-wrapper[data-product_id="' + productId + '"]').length
                    });

                    // Reset out-of-stock classes for both swatches
                    $colorSwatches.removeClass('out-of-stock');
                    $sizeSwatches.removeClass('out-of-stock');
                    console.log('Swatchly: Out-of-stock classes reset', { productId: productId });

                    // Update size swatches availability when a color is selected
                    if (selectedColor) {
                        $sizeSwatches.each(function() {
                            let sizeValue = String($(this).data('value') || '');
                            let isAvailable = false;
                            let variationFound = null;
                            $.each(variations, function(index, variation) {
                                let sizeAttr = String(variation.attributes['attribute_size'] || variation.attributes['attribute_pa_size'] || '');
                                let colorAttr = String(variation.attributes['attribute_pa_color'] || variation.attributes['attribute_color'] || '');
                                if (colorAttr === selectedColor && sizeAttr === sizeValue && variation.is_in_stock) {
                                    isAvailable = true;
                                    variationFound = variation;
                                    return false;
                                }
                            });
                            $(this).toggleClass('out-of-stock', !isAvailable);
                            console.log('Swatchly: Size stock status', {
                                productId: productId,
                                color: selectedColor,
                                size: sizeValue,
                                inStock: isAvailable,
                                variation: variationFound
                            });
                        });
                    }

                    // Update color swatches availability when a size is selected
                    if (selectedSize) {
                        $colorSwatches.each(function() {
                            let colorValue = String($(this).data('value') || '');
                            console.log('Swatchly: Processing color', { productId: productId, size: selectedSize, color: colorValue });
                            let isAvailable = false;
                            let variationFound = null;
                            $.each(variations, function(index, variation) {
                                let sizeAttr = String(variation.attributes['attribute_size'] || variation.attributes['attribute_pa_size'] || '');
                                let colorAttr = String(variation.attributes['attribute_pa_color'] || variation.attributes['attribute_color'] || '');
                                if (sizeAttr === selectedSize && colorAttr === colorValue) {
                                    variationFound = variation;
                                    isAvailable = variation.is_in_stock;
                                    return false;
                                }
                            });
                            $(this).toggleClass('out-of-stock', !isAvailable);
                            console.log('Swatchly: Color stock status', {
                                productId: productId,
                                size: selectedSize,
                                color: colorValue,
                                inStock: isAvailable,
                                variation: variationFound
                            });
                        });
                    }
                }

                $('.swatchly-color-swatches-wrapper').each(function() {
                    let $wrapper = $(this);
                    let productId = $wrapper.data('product_id');
                    let variations = JSON.parse($wrapper.find('.swatchly_color_swatches').attr('data-variations') || '[]');
                    let $colorInput = $('#attribute_pa_color_' + productId + ', #attribute_Color_' + productId);
                    let $productContainer = $wrapper.closest('.e-loop-item-' + productId + ', .post-' + productId + ', .product');
                    let $imageElement = $productContainer.find('.wvs-archive-product-image, .attachment-large');
                    let featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    let currentImage = featuredImage;

                    $wrapper.find('.swatchly_color_swatch').on('mouseenter', function() {
                        let selectedColor = String($(this).data('value') || '');
                        let attribute = $colorInput.attr('id').includes('pa_color') ? 'attribute_pa_color' : 'attribute_color';
                        let matchedVariation = findVariationForHover(productId, attribute, selectedColor, variations);
                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.each(function() {
                                $(this).attr('src', currentImage).attr('srcset', '');
                            });
                        }
                    });

                    $wrapper.find('.swatchly_color_swatch').on('click', function(e) {
                        e.preventDefault();
                        let selectedColor = String($(this).data('value') || '');
                        $wrapper.find('.swatchly_color_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $colorInput.val(selectedColor);

                        let $sizeInput = $('#attribute_Size_' + productId + ', #attribute_pa_size_' + productId);
                        let selectedSize = String($sizeInput.val() || '');
                        let matchedVariation = findVariation(productId, selectedColor, selectedSize, variations);

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.each(function() {
                                $(this).attr('src', currentImage).attr('srcset', '');
                            });
                        }

                        updateAvailability(productId, selectedColor, null, variations);
                        if (selectedSize && matchedVariation && matchedVariation.is_in_stock) {
                            let attributes = { 
                                ['attribute_' + ($colorInput.attr('id').includes('pa_color') ? 'pa_color' : 'color')]: selectedColor, 
                                ['attribute_' + ($sizeInput.attr('id').includes('pa_size') ? 'pa_size' : 'size')]: selectedSize 
                            };
                            addToCart(productId, matchedVariation.variation_id, attributes);
                        }
                    });
                });

                $('.swatchly-size-swatches-wrapper').each(function() {
                    let $wrapper = $(this);
                    let productId = $wrapper.data('product_id');
                    let variations = JSON.parse($wrapper.find('.swatchly_size_swatches').attr('data-variations') || '[]');
                    let $sizeInput = $('#attribute_Size_' + productId + ', #attribute_pa_size_' + productId);
                    let $productContainer = $wrapper.closest('.e-loop-item-' + productId + ', .post-' + productId + ', .product');
                    let $imageElement = $productContainer.find('.wvs-archive-product-image, .attachment-large');
                    let featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    let currentImage = featuredImage;

                    console.log('Swatchly: Size swatches initialized', { productId: productId, variationsCount: variations.length });

                    $wrapper.find('.swatchly_size_swatch').on('click', function(e) {
                        e.preventDefault();
                        let selectedSize = String($(this).data('value') || '');
                        console.log('Swatchly: Size swatch clicked', { productId: productId, selectedSize: selectedSize });
                        $wrapper.find('.swatchly_size_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $sizeInput.val(selectedSize);

                        let $colorInput = $('#attribute_pa_color_' + productId + ', #attribute_Color_' + productId);
                        let selectedColor = String($colorInput.val() || '');
                        let matchedVariation = findVariation(productId, selectedColor, selectedSize, variations);

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.each(function() {
                                $(this).attr('src', currentImage).attr('srcset', '');
                            });
                        }

                        updateAvailability(productId, null, selectedSize, variations);
                        if (selectedColor && matchedVariation && matchedVariation.is_in_stock) {
                            let attributes = { 
                                ['attribute_' + ($colorInput.attr('id').includes('pa_color') ? 'pa_color' : 'color')]: selectedColor, 
                                ['attribute_' + ($sizeInput.attr('id').includes('pa_size') ? 'pa_size' : 'size')]: selectedSize 
                            };
                            addToCart(productId, matchedVariation.variation_id, attributes);
                        }
                    });
                });
            });
        </script>
        <?php endif; ?>
        <?php
        $output = ob_get_clean();
        error_log('Swatchly: Size swatches - Product ' . $atts['product_id'] . ' - HTML output length: ' . strlen($output));
        return $output;
    } else {
        return 'Atributo de talla no encontrado';
    }
});
?>
