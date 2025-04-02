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
    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(), // Por defecto, usa el ID del producto actual
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
                            console.log('Error al añadir al carrito:', error);
                        }
                    });
                }

                // Manejar hover y click para cada instancia de swatches
                $('.swatchly_color_swatches').each(function() {
                    var $swatchesContainer = $(this);
                    var productId = $swatchesContainer.data('product_id');
                    var variations = JSON.parse($swatchesContainer.attr('data-variations')); // Variaciones específicas del producto
                    var $imageElement = $swatchesContainer.closest('.e-con-inner').find('.elementor-element-0515b16 img');
                    var $colorInput = $('#attribute_Color_' + productId);
                    var featuredImage = '<?php echo esc_js($featured_image_url); ?>'; // Imagen destacada por defecto
                    var currentImage = featuredImage; // Imagen actual que persiste

                    console.log('Product ID:', productId, 'Image element found:', $imageElement.length > 0 ? 'Yes' : 'No');
                    console.log('Variations for product', productId, ':', variations);

                    // Evento hover: cambiar imagen solo del producto actual
                    $swatchesContainer.find('.swatchly_color_swatch').on('mouseenter', function() {
                        var selectedColor = $(this).data('value');
                        var selectedSize = $('#attribute_Size_' + productId).val() || '';
                        var matchedVariation = null;

                        console.log('Hover on color:', selectedColor, 'Product ID:', productId);

                        $.each(variations, function(index, variation) {
                            if (variation.attributes.attribute_color === selectedColor && 
                                (!selectedSize || variation.attributes.attribute_size === selectedSize)) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                            $imageElement.attr('srcset', ''); // Limpiar srcset para forzar el cambio
                            console.log('Image updated to:', currentImage, 'Product ID:', productId);
                        } else {
                            console.log('No matching variation found for color:', selectedColor, 'Product ID:', productId);
                        }
                    });

                    // Mantener la última imagen al salir del contenedor de swatches
                    $swatchesContainer.on('mouseleave', function() {
                        $imageElement.attr('src', currentImage); // Mantener la última imagen seleccionada
                        $imageElement.attr('srcset', '');
                        console.log('Mouse left, image persists:', currentImage, 'Product ID:', productId);
                    });

                    // Evento click: mantener funcionalidad original
                    $swatchesContainer.find('.swatchly_color_swatch').on('click', function(e) {
                        e.preventDefault();
                        var selectedColor = $(this).data('value');
                        $swatchesContainer.find('.swatchly_color_swatch').removeClass('selected');
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
        'product_id' => get_the_ID(), // Por defecto, usa el ID del producto actual
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
                            console.log('Error al añadir al carrito:', error);
                        }
                    });
                }

                // Manejar hover y click para cada instancia de swatches
                $('.swatchly_size_swatches').each(function() {
                    var $swatchesContainer = $(this);
                    var productId = $swatchesContainer.data('product_id');
                    var variations = JSON.parse($swatchesContainer.attr('data-variations')); // Variaciones específicas del producto
                    var $imageElement = $swatchesContainer.closest('.e-con-inner').find('.elementor-element-0515b16 img');
                    var $sizeInput = $('#attribute_Size_' + productId);
                    var featuredImage = '<?php echo esc_js($featured_image_url); ?>'; // Imagen destacada por defecto
                    var currentImage = featuredImage; // Imagen actual que persiste

                    console.log('Product ID:', productId, 'Image element found:', $imageElement.length > 0 ? 'Yes' : 'No');
                    console.log('Variations for product', productId, ':', variations);

                    // Evento hover: cambiar imagen solo del producto actual
                    $swatchesContainer.find('.swatchly_size_swatch').on('mouseenter', function() {
                        var selectedSize = $(this).data('value');
                        var selectedColor = $('#attribute_Color_' + productId).val() || '';
                        var matchedVariation = null;

                        console.log('Hover on size:', selectedSize, 'Product ID:', productId);

                        $.each(variations, function(index, variation) {
                            if (variation.attributes.attribute_size === selectedSize && 
                                (!selectedColor || variation.attributes.attribute_color === selectedColor)) {
                                matchedVariation = variation;
                                return false;
                            }
                        });

                        if (matchedVariation && matchedVariation.image && matchedVariation.image.src) {
                            currentImage = matchedVariation.image.src;
                            $imageElement.attr('src', currentImage);
                            console.log('Image updated to:', currentImage, 'Product ID:', productId);
                        } else {
                            console.log('No matching variation found for size:', selectedSize, 'Product ID:', productId);
                        }
                    });

                    // Mantener la última imagen al salir del contenedor de swatches
                    $swatchesContainer.on('mouseleave', function() {
                        $imageElement.attr('src', currentImage); // Mantener la última imagen seleccionada
                        console.log('Mouse left, image persists:', currentImage, 'Product ID:', productId);
                    });

                    // Evento click: mantener funcionalidad original
                    $swatchesContainer.find('.swatchly_size_swatch').on('click', function(e) {
                        e.preventDefault();
                        var selectedSize = $(this).data('value');
                        $swatchesContainer.find('.swatchly_size_swatch').removeClass('selected');
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
