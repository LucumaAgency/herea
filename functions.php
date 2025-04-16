<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri) {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('child_theme_configurator_css')):
    function child_theme_configurator_css() {
        wp_enqueue_style('chld_thm_cfg_child', trailingslashit(get_stylesheet_directory_uri()) . 'style.css', array('hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer'));
    }
endif;
add_action('wp_enqueue_scripts', 'child_theme_configurator_css', 10);

// END ENQUEUE PARENT ACTION

add_action('wp_enqueue_scripts', 'enqueue_child_theme_styles');
function enqueue_child_theme_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'), wp_get_theme()->get('Version'));
}

// FUNCTIONS
add_shortcode('swatchly_color_swatches', function($atts) {
    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(),
    ), $atts);

    $product = wc_get_product($atts['product_id']);
    if (!$product || !$product->is_type('variable')) {
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    $attribute_name = 'Color';
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();
    if (isset($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
        ?>
        <div class="variations swatchly_variation_wrap swatchly_color_swatches" data-product_id="<?php echo esc_attr($atts['product_id']); ?>" data-variations='<?php echo esc_attr(json_encode($product->get_available_variations())); ?>'>
            <?php 
            foreach ($options as $option) : 
                // Get the term object for the color option
                $term = get_term_by('slug', sanitize_title($option), 'pa_' . strtolower($attribute_name));
                // Retrieve the hex code from term meta (Variation Swatches for WooCommerce - Pro)
                $color_value = $term ? get_term_meta($term->term_id, 'viwpvs_attribute_color', true) : '#000000';
                // Fallback to black if no hex code is found
                if (empty($color_value)) {
                    $color_value = '#000000';
                }
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
        <style>
            .swatchly_color_swatches {
                display: flex;
                gap: 10px;
                margin: 10px 0;
            }
            .swatchly_color_swatch {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                border: 2px solid #ccc;
                transition: border-color 0.3s;
            }
            .swatchly_color_swatch.selected {
                border-color: #000;
            }
            .swatchly_color_swatch:hover {
                border-color: #666;
            }
            .swatchly_color_swatch.out-of-stock {
                position: relative;
                opacity: 0.5;
                cursor: not-allowed;
            }
            .swatchly_color_swatch.out-of-stock::after {
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
            .cart-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #ffffff!important;
                color: #000000!important;
                padding: 10px 20px;
                border-radius: 5px;
                border: 1px solid #808080!important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .cart-notification.show {
                opacity: 1;
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function showNotification(message) {
                    let notification = $('.cart-notification');
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
                            action: 'add_to_checkout',
                            add_to_checkout: productId,
                            variation_id: variationId,
                            product_type: 'variable',
                            nonce: '<?php echo wp_create_nonce('add_to_checkout'); ?>',
                            ...attributes
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.success) {
                                if (response.data.fragments) {
                                    $.each(response.data.fragments, function(key, value) {
                                        $(key).replaceWith(value);
                                    });
                                    $(document.body).trigger('wc_fragments_refreshed');
                                }
                                showNotification('Product added to cart');
                            }
                        },
                        error: function(xhr, status, error) {
                        }
                    });
                }

                function updateSizeSwatches(productId, selectedColor, variations) {
                    var $sizeSwatches = $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch');
                    $sizeSwatches.removeClass('out-of-stock');

                    $sizeSwatches.each(function() {
                        var sizeValue = String($(this).data('value') || '');
                        var isAvailable = false;

                        $.each(variations, function(index, variation) {
                            var sizeAttr = '';
                            var colorAttr = '';
                            for (var attr in variation.attributes) {
                                if (attr.toLowerCase().includes('size')) {
                                    sizeAttr = String(variation.attributes[attr] || '');
                                }
                                if (attr.toLowerCase().includes('color')) {
                                    colorAttr = String(variation.attributes[attr] || '');
                                }
                            }

                            if (colorAttr === selectedColor && sizeAttr === sizeValue && variation.is_in_stock) {
                                isAvailable = true;
                                return false;
                            }
                        });

                        if (!isAvailable) {
                            $(this).addClass('out-of-stock');
                        }
                    });
                }

                $('.swatchly_color_swatches').each(function() {
                    var $swatchesContainer = $(this);
                    var productId = $swatchesContainer.data('product_id');
                    var variations = JSON.parse($swatchesContainer.attr('data-variations') || '[]');
                    var $imageElement = $swatchesContainer.closest('.e-con-inner').find('.elementor-element-0515b16 img');
                    var $colorInput = $('#attribute_Color_' + productId);
                    var featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    var currentImage = featuredImage;

                    $swatchesContainer.find('.swatchly_color_swatch').on('mouseenter', function() {
                        var selectedColor = String($(this).data('value') || '');
                        var selectedSize = String($('#attribute_Size_' + productId).val() || '');
                        var matchedVariation = null;

                        $.each(variations, function(index, variation) {
                            var sizeAttr = String(variation.attributes['attribute_pa_size'] || variation.attributes['attribute_size'] || '');
                            var colorAttr = String(variation.attributes['attribute_pa_color'] || variation.attributes['attribute_color'] || '');

                            if (colorAttr === selectedColor && (!selectedSize || sizeAttr === selectedSize)) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                            $imageElement.attr('srcset', '');
                        }
                    });

                    $swatchesContainer.on('mouseleave', function() {
                        $imageElement.attr('src', currentImage);
                        $imageElement.attr('srcset', '');
                    });

                    $swatchesContainer.find('.swatchly_color_swatch').on('click', function(e) {
                        e.preventDefault();
                        var selectedColor = String($(this).data('value') || '');
                        $swatchesContainer.find('.swatchly_color_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $colorInput.val(selectedColor);

                        // Update size swatches based on selected color
                        updateSizeSwatches(productId, selectedColor, variations);

                        var selectedSize = String($('#attribute_Size_' + productId).val() || '');
                        var matchedVariation = null;

                        $.each(variations, function(index, variation) {
                            var sizeAttr = String(variation.attributes['attribute_pa_size'] || variation.attributes['attribute_size'] || '');
                            var colorAttr = String(variation.attributes['attribute_pa_color'] || variation.attributes['attribute_color'] || '');

                            if (colorAttr === selectedColor && sizeAttr === selectedSize) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                            $imageElement.attr('srcset', '');
                        }

                        if (selectedSize && matchedVariation) {
                            var attributes = {
                                attribute_color: selectedColor,
                                attribute_size: selectedSize
                            };
                            addToCart(productId, matchedVariation.variation_id, attributes);
                        }
                    });
                });
            });
        </script>
        <?php
    } else {
        return 'Atributo no encontrado: ' . $attribute_name;
    }
    return ob_get_clean();
});

add_shortcode('swatchly_size_swatches', function($atts) {
    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(),
    ), $atts);

    $product = wc_get_product($atts['product_id']);
    if (!$product || !$product->is_type('variable')) {
        return 'No es un producto variable';
    }

    $attributes = $product->get_variation_attributes();
    $attribute_name = 'Size';
    $featured_image_id = $product->get_image_id();
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    ob_start();
    if (isset($attributes[$attribute_name])) {
        $options = $attributes[$attribute_name];
        ?>
        <div class="variations swatchly_variation_wrap swatchly_size_swatches" data-product_id="<?php echo esc_attr($atts['product_id']); ?>" data-variations='<?php echo esc_attr(json_encode($product->get_available_variations())); ?>'>
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
        <style>
            .swatchly_size_swatches {
                display: flex;
                gap: 10px;
                margin: 10px 0;
            }
            .swatchly_size_swatch {
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
            }
            .swatchly_size_swatch.selected {
                border-color: #000;
                background-color: #f0f0f0;
            }
            .swatchly_size_swatch:hover {
                border-color: #666;
            }
            .swatchly_size_swatch.out-of-stock {
                position: relative;
                opacity: 0.5;
                cursor: not-allowed;
            }
            .swatchly_size_swatch.out-of-stock::after {
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
            .cart-notification {
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
            .cart-notification.show {
                opacity: 1;
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function showNotification(message) {
                    let notification = $('.cart-notification');
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
                            action: 'add_to_checkout',
                            add_to_checkout: productId,
                            variation_id: variationId,
                            product_type: 'variable',
                            nonce: '<?php echo wp_create_nonce('add_to_checkout'); ?>',
                            ...attributes
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.success) {
                                if (response.data.fragments) {
                                    $.each(response.data.fragments, function(key, value) {
                                        $(key).replaceWith(value);
                                    });
                                    $(document.body).trigger('wc_fragments_refreshed');
                                }
                                showNotification('Producto añadido al carrito');
                            }
                        },
                        error: function(xhr, status, error) {
                        }
                    });
                }

                function updateColorSwatches(productId, selectedSize, variations) {
                    var $colorSwatches = $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch');
                    $colorSwatches.removeClass('out-of-stock');

                    $colorSwatches.each(function() {
                        var colorValue = String($(this).data('value') || '');
                        var isAvailable = false;

                        $.each(variations, function(index, variation) {
                            var sizeAttr = '';
                            var colorAttr = '';
                            for (var attr in variation.attributes) {
                                if (attr.toLowerCase().includes('size')) {
                                    sizeAttr = String(variation.attributes[attr] || '');
                                }
                                if (attr.toLowerCase().includes('color')) {
                                    colorAttr = String(variation.attributes[attr] || '');
                                }
                            }

                            if (sizeAttr === selectedSize && colorAttr === colorValue && variation.is_in_stock) {
                                isAvailable = true;
                                return false;
                            }
                        });

                        if (!isAvailable) {
                            $(this).addClass('out-of-stock');
                        }
                    });
                }

                $('.swatchly_size_swatches').each(function() {
                    var $swatchesContainer = $(this);
                    var productId = $swatchesContainer.data('product_id');
                    var variations = JSON.parse($swatchesContainer.attr('data-variations') || '[]');
                    var $imageElement = $swatchesContainer.closest('.e-con-inner').find('.elementor-element-0515b16 img');
                    var $sizeInput = $('#attribute_Size_' + productId);
                    var featuredImage = '<?php echo esc_js($featured_image_url); ?>';
                    var currentImage = featuredImage;

                    $swatchesContainer.find('.swatchly_size_swatch').on('mouseenter', function() {
                        var selectedSize = String($(this).data('value') || '');
                        var selectedColor = String($('#attribute_Color_' + productId).val() || '');
                        var matchedVariation = null;
                        var stockByColor = {};

                        if (!variations || variations.length === 0) {
                            return;
                        }

                        $.each(variations, function(index, variation) {
                            var sizeAttr = '';
                            var colorAttr = '';
                            for (var attr in variation.attributes) {
                                if (attr.toLowerCase().includes('size')) {
                                    sizeAttr = String(variation.attributes[attr] || '');
                                }
                                if (attr.toLowerCase().includes('color')) {
                                    colorAttr = String(variation.attributes[attr] || '');
                                }
                            }

                            if (sizeAttr === selectedSize) {
                                stockByColor[colorAttr] = {
                                    stock: variation.is_in_stock ? (variation.stock_quantity !== null && variation.stock_quantity !== undefined ? variation.stock_quantity : 'In Stock') : 'Out of Stock',
                                    variation_id: variation.variation_id,
                                    is_in_stock: variation.is_in_stock,
                                    manage_stock: variation.manage_stock
                                };
                            }

                            if (sizeAttr === selectedSize && (!selectedColor || colorAttr === selectedColor)) {
                                matchedVariation = variation;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                        }
                    });

                    $swatchesContainer.on('mouseleave', function() {
                        $imageElement.attr('src', currentImage);
                    });

                    $swatchesContainer.find('.swatchly_size_swatch').on('click', function(e) {
                        e.preventDefault();
                        var selectedSize = String($(this).data('value') || '');
                        $swatchesContainer.find('.swatchly_size_swatch').removeClass('selected');
                        $(this).addClass('selected');
                        $sizeInput.val(selectedSize);

                        var selectedColor = String($('#attribute_Color_' + productId).val() || '');
                        var matchedVariation = null;

                        // Actualizar los swatches de color según el tamaño seleccionado
                        updateColorSwatches(productId, selectedSize, variations);

                        $.each(variations, function(index, variation) {
                            var sizeAttr = '';
                            var colorAttr = '';
                            for (var attr in variation.attributes) {
                                if (attr.toLowerCase().includes('size')) {
                                    sizeAttr = String(variation.attributes[attr] || '');
                                }
                                if (attr.toLowerCase().includes('color')) {
                                    colorAttr = String(variation.attributes[attr] || '');
                                }
                            }

                            if (sizeAttr === selectedSize && colorAttr === selectedColor) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                        }

                        if (selectedColor && matchedVariation) {
                            var attributes = {
                                attribute_color: selectedColor,
                                attribute_size: selectedSize
                            };
                            addToCart(productId, matchedVariation.variation_id, attributes);
                        }
                    });
                });
            });
        </script>
        <?php
    } else {
        return 'Atributo no encontrado: ' . $attribute_name;
    }
    return ob_get_clean();
});

add_action('wp_enqueue_scripts', function() {
    if (function_exists('swatchly_scripts')) {
        swatchly_scripts();
    }
});

// LIMITAR PRODUCTOS ARCHIVE A 16
add_action('woocommerce_product_query', 'change_posts_per_page', 999);
function change_posts_per_page($query) {
    if (is_admin())
        return;
    $query->set('posts_per_page', 16);
}
?>
