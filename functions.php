<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

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
    $product = wc_get_product(get_the_ID());
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
        <div class="variations swatchly_variation_wrap swatchly_color_swatches" data-product_id="<?php echo get_the_ID(); ?>">
            <?php foreach ($options as $option) : 
                $color_map = [
                    'Green' => '#00FF00',
                    'Yellow' => '#FFFF00',
                    'Black' => '#000000',
                    'Beige' => '#F5F5DC',
                    'Brown' => '#8B4513',
                    'Gold' => '#FFD700'
                ];
                $color_value = isset($color_map[$option]) ? $color_map[$option] : '#000000';
            ?>
                <span class="swatchly_swatch swatchly_color_swatch" 
                      data-value="<?php echo esc_attr($option); ?>" 
                      style="background-color: <?php echo esc_attr($color_value); ?>;" 
                      title="<?php echo esc_attr($option); ?>"></span>
            <?php endforeach; ?>
            <input type="hidden" id="attribute_<?php echo esc_attr($attribute_name); ?>_<?php echo get_the_ID(); ?>" 
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
            .cart-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #ffffff!important; /* Fondo blanco */
                color: #000000!important; /* Texto negro para contraste */
                padding: 10px 20px;
                border-radius: 5px;
                border: 1px solid #808080!important; /* Borde plomo */
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
                var productId = '<?php echo get_the_ID(); ?>';
                var variations = <?php echo json_encode($product->get_available_variations()); ?>;
                var $imageContainer = $('.e-loop-item-' + productId + ' .elementor-element-3ca9209');
                var $colorInput = $('#attribute_<?php echo esc_attr($attribute_name); ?>_' + productId);
                var featuredImage = '<?php echo esc_js($featured_image_url); ?>'; // Imagen destacada
                var originalImage = featuredImage; // Usar featured image como base
                var nonce = '<?php echo wp_create_nonce('add_to_checkout'); ?>';

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
                            nonce: nonce,
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
                            console.log('Error al añadir al carrito:', error);
                        }
                    });
                }

                // Evento hover: cambiar imagen al pasar el cursor
                $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch').on('mouseenter', function() {
                    var selectedColor = $(this).data('value');
                    var selectedSize = $('#attribute_Size_' + productId).val() || '';
                    var matchedVariation = null;

                    $.each(variations, function(index, variation) {
                        if (variation.attributes.attribute_color === selectedColor && 
                            (!selectedSize || variation.attributes.attribute_size === selectedSize)) {
                            matchedVariation = variation;
                            return false;
                        }
                    });

                    if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                        $imageContainer.css('background-image', 'url(' + matchedVariation.image.src + ')');
                    }
                });

                // Evento hover: restaurar imagen destacada al salir
                $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch').on('mouseleave', function() {
                    if ($imageContainer.length && featuredImage) {
                        $imageContainer.css('background-image', 'url(' + featuredImage + ')');
                    }
                });

                // Evento click: mantener funcionalidad original
                $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch').on('click', function(e) {
                    e.preventDefault();
                    var selectedColor = $(this).data('value');
                    $('.swatchly_color_swatches[data-product_id="' + productId + '"] .swatchly_color_swatch').removeClass('selected');
                    $(this).addClass('selected');
                    $colorInput.val(selectedColor);

                    var selectedSize = $('#attribute_Size_' + productId).val();
                    var matchedVariation = null;

                    $.each(variations, function(index, variation) {
                        if (variation.attributes.attribute_color === selectedColor && 
                            variation.attributes.attribute_size === selectedSize) {
                            matchedVariation = variation;
                            return false;
                        }
                    });

                    if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                        $imageContainer.css('background-image', 'url(' + matchedVariation.image.src + ')');
                        originalImage = $imageContainer.css('background-image'); // Actualizar tras click
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
        </script>
        <?php
    } else {
        return 'Atributo no encontrado: ' . $attribute_name;
    }
    return ob_get_clean();
});

add_shortcode('swatchly_size_swatches', function($atts) {
    $product = wc_get_product(get_the_ID());
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
        <div class="variations swatchly_variation_wrap swatchly_size_swatches" data-product_id="<?php echo get_the_ID(); ?>">
            <?php foreach ($options as $option) : ?>
                <span class="swatchly_swatch swatchly_size_swatch" 
                      data-value="<?php echo esc_attr($option); ?>">
                    <?php echo esc_html($option); ?>
                </span>
            <?php endforeach; ?>
            <input type="hidden" id="attribute_<?php echo esc_attr($attribute_name); ?>_<?php echo get_the_ID(); ?>" 
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
            }
            .swatchly_size_swatch.selected {
                border-color: #000;
                background-color: #f0f0f0;
            }
            .swatchly_size_swatch:hover {
                border-color: #666;
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
                var productId = '<?php echo get_the_ID(); ?>';
                var variations = <?php echo json_encode($product->get_available_variations()); ?>;
                var $imageContainer = $('.e-loop-item-' + productId + ' .elementor-element-3ca9209');
                var $sizeInput = $('#attribute_<?php echo esc_attr($attribute_name); ?>_' + productId);
                var featuredImage = '<?php echo esc_js($featured_image_url); ?>'; // Imagen destacada
                var originalImage = featuredImage; // Usar featured image como base
                var nonce = '<?php echo wp_create_nonce('add_to_checkout'); ?>';

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
                            nonce: nonce,
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
                            console.log('Error al añadir al carrito:', error);
                        }
                    });
                }

                // Evento hover: cambiar imagen al pasar el cursor
                $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch').on('mouseenter', function() {
                    var selectedSize = $(this).data('value');
                    var selectedColor = $('#attribute_Color_' + productId).val() || '';
                    var matchedVariation = null;

                    $.each(variations, function(index, variation) {
                        if (variation.attributes.attribute_size === selectedSize && 
                            (!selectedColor || variation.attributes.attribute_color === selectedColor)) {
                            matchedVariation = variation;
                            return false;
                        }
                    });

                    if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                        $imageContainer.css('background-image', 'url(' + matchedVariation.image.src + ')');
                    }
                });

                // Evento hover: restaurar imagen destacada al salir
                $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch').on('mouseleave', function() {
                    if ($imageContainer.length && featuredImage) {
                        $imageContainer.css('background-image', 'url(' + featuredImage + ')');
                    }
                });

                // Evento click: mantener funcionalidad original
                $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch').on('click', function(e) {
                    e.preventDefault();
                    var selectedSize = $(this).data('value');
                    $('.swatchly_size_swatches[data-product_id="' + productId + '"] .swatchly_size_swatch').removeClass('selected');
                    $(this).addClass('selected');
                    $sizeInput.val(selectedSize);

                    var selectedColor = $('#attribute_Color_' + productId).val();
                    var matchedVariation = null;

                    $.each(variations, function(index, variation) {
                        if (variation.attributes.attribute_size === selectedSize && 
                            variation.attributes.attribute_color === selectedColor) {
                            matchedVariation = variation;
                            return false;
                        }
                    });

                    if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                        $imageContainer.css('background-image', 'url(' + matchedVariation.image.src + ')');
                        originalImage = $imageContainer.css('background-image');
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
