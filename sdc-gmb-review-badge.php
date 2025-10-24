<?php
/**
 * Plugin Name: SDC GMB Review Badge
 * Plugin URI: https://stokedesign.co
 * Description: Display a Google Business Profile rating badge anywhere on your site via shortcode with configurable styling, caching, and accessible markup.
 * Version: 1.2.0
 * Author: Stoke Design Co
 * Author URI: https://stokedesign.co
 * Text Domain: sdc-gmb-review-badge
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SDC_GMB_REVIEW_BADGE_VERSION' ) ) {
    define( 'SDC_GMB_REVIEW_BADGE_VERSION', '1.2.0' );
}

/**
 * Load plugin text domain.
 */
function sdc_gmb_load_textdomain() {
    load_plugin_textdomain( 'sdc-gmb-review-badge', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'sdc_gmb_load_textdomain' );

/**
 * Retrieve default options.
 *
 * @return array
 */
function sdc_gmb_get_default_options() {
    return array(
        'api_key'       => '',
        'place_id'      => '',
        'stars'         => 5,
        'star_color'    => '#E9966F',
        'accent_color'  => '#1E2A3A',
        'cache_minutes' => 720,
        'min_rating'    => 0.0,
        'reviews_limit' => 6,
        'slides_desktop' => 3,
        'slides_tablet'  => 2,
        'slides_mobile'  => 1,
        'elementor_skin' => 'badge', // TODO: Confirm Elementor skin slug expectation once widget integration is finalised.
    );
}

/**
 * Retrieve saved options merged with defaults.
 *
 * @return array
 */
function sdc_gmb_get_options() {
    $options = get_option( 'sdc_gmb_review_badge_options', array() );

    if ( ! is_array( $options ) || empty( $options ) ) {
        $legacy_keys = array(
            'sdc_gmb_rating_options',
            'stoke_gbp_rating_options',
        );

        foreach ( $legacy_keys as $legacy_key ) {
            $legacy = get_option( $legacy_key );

            if ( is_array( $legacy ) && ! empty( $legacy ) ) {
                $options = $legacy;
                break;
            }
        }
    }

    if ( ! is_array( $options ) ) {
        $options = array();
    }

    return wp_parse_args( $options, sdc_gmb_get_default_options() );
}

/**
 * Register settings and fields.
 */
function sdc_gmb_register_settings() {
    register_setting( 'sdc_gmb_review_badge_options_group', 'sdc_gmb_review_badge_options', 'sdc_gmb_sanitize_options' );

    add_settings_section(
        'sdc_gmb_review_badge_section',
        __( 'Google Business Profile', 'sdc-gmb-review-badge' ),
        '__return_false',
        'sdc_gmb_review_badge'
    );

    add_settings_field(
        'api_key',
        __( 'Google Places API Key', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_api_key_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'place_id',
        __( 'Place ID', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_place_id_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'stars',
        __( 'Number of Stars to Display', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_stars_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'star_color',
        __( 'Star Colour', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_star_color_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'accent_color',
        __( 'Text and Icon Colour', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_accent_color_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'cache_minutes',
        __( 'Cache Duration (minutes)', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_cache_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'min_rating',
        __( 'Minimum Review Rating', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_min_rating_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'reviews_limit',
        __( 'Reviews Limit', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_reviews_limit_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'slides_desktop',
        __( 'Desktop Slides Visible', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_slide_count_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section',
        array(
            'key'         => 'slides_desktop',
            'input_id'    => 'sdc-gmb-slides-desktop',
            'max'         => 5,
            'description' => __( 'Number of review cards to display side-by-side on desktop breakpoints.', 'sdc-gmb-review-badge' ),
        )
    );

    add_settings_field(
        'slides_tablet',
        __( 'Tablet Slides Visible', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_slide_count_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section',
        array(
            'key'         => 'slides_tablet',
            'input_id'    => 'sdc-gmb-slides-tablet',
            'max'         => 4,
            'description' => __( 'Number of review cards to display on medium-width tablet layouts.', 'sdc-gmb-review-badge' ),
        )
    );

    add_settings_field(
        'slides_mobile',
        __( 'Mobile Slides Visible', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_slide_count_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section',
        array(
            'key'         => 'slides_mobile',
            'input_id'    => 'sdc-gmb-slides-mobile',
            'max'         => 3,
            'description' => __( 'Number of review cards to display on small-screen devices.', 'sdc-gmb-review-badge' ),
        )
    );

    add_settings_field(
        'elementor_skin',
        __( 'Elementor Skin', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_elementor_skin_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );
}
add_action( 'admin_init', 'sdc_gmb_register_settings' );

/**
 * Sanitize options before saving.
 *
 * @param array $input Raw input values.
 * @return array
 */
function sdc_gmb_sanitize_options( $input ) {
    $options   = sdc_gmb_get_options();
    $defaults  = sdc_gmb_get_default_options();
    $sanitized = array();

    $sanitized['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : $options['api_key'];
    $sanitized['place_id']      = isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : $options['place_id'];
    $sanitized['stars']         = isset( $input['stars'] ) ? absint( $input['stars'] ) : $options['stars'];
    $sanitized['star_color']    = isset( $input['star_color'] ) ? sanitize_hex_color( $input['star_color'] ) : $options['star_color'];
    $sanitized['accent_color']  = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $options['accent_color'];
    $sanitized['cache_minutes'] = isset( $input['cache_minutes'] ) ? absint( $input['cache_minutes'] ) : $options['cache_minutes'];
    $sanitized['min_rating']    = isset( $input['min_rating'] ) ? floatval( $input['min_rating'] ) : (float) $options['min_rating'];
    $sanitized['reviews_limit'] = isset( $input['reviews_limit'] ) ? absint( $input['reviews_limit'] ) : absint( $options['reviews_limit'] );

    $slide_keys = array(
        'slides_desktop',
        'slides_tablet',
        'slides_mobile',
    );

    foreach ( $slide_keys as $slide_key ) {
        $sanitized[ $slide_key ] = isset( $input[ $slide_key ] ) ? absint( $input[ $slide_key ] ) : absint( $options[ $slide_key ] );
    }

    $sanitized['elementor_skin'] = isset( $input['elementor_skin'] ) ? sanitize_key( $input['elementor_skin'] ) : sanitize_key( $options['elementor_skin'] );

    if ( $sanitized['stars'] < 1 ) {
        $sanitized['stars'] = 1;
    } elseif ( $sanitized['stars'] > 10 ) {
        $sanitized['stars'] = 10;
    }

    if ( empty( $sanitized['star_color'] ) ) {
        $sanitized['star_color'] = $defaults['star_color'];
    }

    if ( empty( $sanitized['accent_color'] ) ) {
        $sanitized['accent_color'] = $defaults['accent_color'];
    }

    if ( $sanitized['min_rating'] < 0 ) {
        $sanitized['min_rating'] = 0.0;
    } elseif ( $sanitized['min_rating'] > 5 ) {
        $sanitized['min_rating'] = 5.0;
    }

    if ( $sanitized['reviews_limit'] < 1 ) {
        $sanitized['reviews_limit'] = $defaults['reviews_limit'];
    } elseif ( $sanitized['reviews_limit'] > 10 ) {
        // TODO: Confirm maximum reviews supported without pagination once API usage requirements are confirmed.
        $sanitized['reviews_limit'] = 10;
    }

    $slide_caps = array(
        'slides_desktop' => 5,
        'slides_tablet'  => 4,
        'slides_mobile'  => 3,
    );

    foreach ( $slide_caps as $slide_key => $max ) {
        if ( $sanitized[ $slide_key ] < 1 ) {
            $sanitized[ $slide_key ] = $defaults[ $slide_key ];
        } elseif ( $sanitized[ $slide_key ] > $max ) {
            $sanitized[ $slide_key ] = $max;
        }
    }

    if ( empty( $sanitized['elementor_skin'] ) ) {
        $sanitized['elementor_skin'] = sanitize_key( $defaults['elementor_skin'] );
    }

    return $sanitized;
}

/**
 * Render API key field.
 */
function sdc_gmb_render_api_key_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[api_key]" id="sdc-gmb-api-key" class="regular-text" value="%s" />',
        esc_attr( $options['api_key'] )
    );

    echo '<p class="description">' . esc_html__( 'Enter the restricted Google Places API key for your Google Business Profile.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render place ID field.
 */
function sdc_gmb_render_place_id_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[place_id]" id="sdc-gmb-place-id" class="regular-text" value="%s" />',
        esc_attr( $options['place_id'] )
    );

    echo '<p class="description">' . esc_html__( 'Paste the Place ID for your Google Business Profile location.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render stars field.
 */
function sdc_gmb_render_stars_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="number" min="1" max="10" name="sdc_gmb_review_badge_options[stars]" id="sdc-gmb-stars" value="%d" />',
        absint( $options['stars'] )
    );

    echo '<p class="description">' . esc_html__( 'Choose how many star icons appear in the badge (between 1 and 10).', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render star colour field.
 */
function sdc_gmb_render_star_color_field() {
    $options = sdc_gmb_get_options();
    $color   = sanitize_hex_color( $options['star_color'] );

    if ( ! $color ) {
        $color = sdc_gmb_get_default_options()['star_color'];
    }

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[star_color]" id="sdc-gmb-star-color" class="sdc-gmb-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_gmb_get_default_options()['star_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the filled portion of the stars.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render accent colour field.
 */
function sdc_gmb_render_accent_color_field() {
    $options = sdc_gmb_get_options();
    $color   = sanitize_hex_color( $options['accent_color'] );

    if ( ! $color ) {
        $color = sdc_gmb_get_default_options()['accent_color'];
    }

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[accent_color]" id="sdc-gmb-accent-color" class="sdc-gmb-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_gmb_get_default_options()['accent_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the text and Google icon.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render cache field.
 */
function sdc_gmb_render_cache_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="number" min="0" name="sdc_gmb_review_badge_options[cache_minutes]" id="sdc-gmb-cache" value="%d" />',
        absint( $options['cache_minutes'] )
    );

    echo '<p class="description">' . esc_html__( 'Cache the Google API response for this many minutes. Set to 0 to disable caching temporarily.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render minimum rating field.
 */
function sdc_gmb_render_min_rating_field() {
    $options     = sdc_gmb_get_options();
    $min_rating  = isset( $options['min_rating'] ) ? (float) $options['min_rating'] : sdc_gmb_get_default_options()['min_rating'];
    $input_value = number_format( $min_rating, 1, '.', '' );

    printf(
        '<input type="number" min="0" max="5" step="0.1" name="sdc_gmb_review_badge_options[min_rating]" id="sdc-gmb-min-rating" value="%s" />',
        esc_attr( $input_value )
    );

    echo '<p class="description">' . esc_html__( 'Ignore reviews below this rating when rendering Elementor carousels or lists.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render reviews limit field.
 */
function sdc_gmb_render_reviews_limit_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="number" min="1" max="10" name="sdc_gmb_review_badge_options[reviews_limit]" id="sdc-gmb-reviews-limit" value="%d" />',
        absint( $options['reviews_limit'] )
    );

    echo '<p class="description">' . esc_html__( 'Cap how many Google reviews to fetch for Elementor-powered layouts.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render slide count field for responsive breakpoints.
 *
 * @param array $args Callback arguments.
 */
function sdc_gmb_render_slide_count_field( $args ) {
    $defaults = array(
        'key'         => '',
        'input_id'    => '',
        'max'         => 3,
        'description' => '',
    );

    $args    = wp_parse_args( $args, $defaults );
    $options = sdc_gmb_get_options();
    $key     = $args['key'];

    if ( '' === $key ) {
        return;
    }

    $value = isset( $options[ $key ] ) ? absint( $options[ $key ] ) : absint( sdc_gmb_get_default_options()[ $key ] );

    printf(
        '<input type="number" min="1" max="%1$d" name="sdc_gmb_review_badge_options[%2$s]" id="%3$s" value="%4$d" />',
        absint( $args['max'] ),
        esc_attr( $key ),
        esc_attr( $args['input_id'] ),
        $value
    );

    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

/**
 * Render Elementor skin field.
 */
function sdc_gmb_render_elementor_skin_field() {
    $options = sdc_gmb_get_options();
    $skin    = isset( $options['elementor_skin'] ) ? sanitize_key( $options['elementor_skin'] ) : sanitize_key( sdc_gmb_get_default_options()['elementor_skin'] );

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[elementor_skin]" id="sdc-gmb-elementor-skin" value="%s" class="regular-text" />',
        esc_attr( $skin )
    );

    echo '<p class="description">' . esc_html__( 'Default Elementor skin slug for the reviews widget. Adjust only if you have custom Elementor templates.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Add settings page.
 */
function sdc_gmb_add_settings_page() {
    add_options_page(
        esc_html__( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ),
        esc_html__( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ),
        'manage_options',
        'sdc-gmb-review-badge',
        'sdc_gmb_render_settings_page'
    );
}
add_action( 'admin_menu', 'sdc_gmb_add_settings_page' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function sdc_gmb_admin_enqueue_assets( $hook ) {
    if ( 'settings_page_sdc-gmb-review-badge' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".sdc-gmb-color-field").wpColorPicker();});' );
}
add_action( 'admin_enqueue_scripts', 'sdc_gmb_admin_enqueue_assets' );

/**
 * Render settings page markup.
 */
function sdc_gmb_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_SPECIAL_CHARS );

    if ( $updated ) {
        add_settings_error( 'sdc_gmb_messages', 'sdc_gmb_message', esc_html__( 'Settings saved.', 'sdc-gmb-review-badge' ), 'updated' );
    }

    settings_errors( 'sdc_gmb_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ); ?></h1>
        <p><?php esc_html_e( 'Display a Google Business Profile reviews badge anywhere via the [sdc_gmb_review_badge] shortcode.', 'sdc-gmb-review-badge' ); ?></p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'sdc_gmb_review_badge_options_group' );
            do_settings_sections( 'sdc_gmb_review_badge' );
            submit_button( __( 'Save Changes', 'sdc-gmb-review-badge' ) );
            ?>
        </form>
        <hr />
        <h2><?php esc_html_e( 'Help', 'sdc-gmb-review-badge' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Create or select a Google Cloud project, enable the Places API, and generate a restricted API key.', 'sdc-gmb-review-badge' ); ?></li>
            <li><?php esc_html_e( 'Use the Google Place ID Finder to look up the Place ID for your business.', 'sdc-gmb-review-badge' ); ?></li>
            <li><?php esc_html_e( 'Enter both values above and save. Then add the shortcode to any page, post, or widget.', 'sdc-gmb-review-badge' ); ?></li>
        </ol>
        <h3><?php esc_html_e( 'Shortcode Examples', 'sdc-gmb-review-badge' ); ?></h3>
        <ul>
            <li><code>[sdc_gmb_review_badge]</code></li>
            <li><code>[sdc_gmb_review_badge stars="5" star_color="#ff9900"]</code></li>
            <li><code>[sdc_gmb_review_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY"]</code></li>
            <li><code>[sdc_gmb_review_badge cache_minutes="0"]</code></li>
        </ul>
        <p><?php esc_html_e( 'Change the cache duration or pass cache_minutes="0" in the shortcode to refresh the data on the next render.', 'sdc-gmb-review-badge' ); ?></p>
    </div>
    <?php
}

/**
 * Fetch rating and review metadata from Google Places API with caching.
 *
 * @param string $place_id      Place ID.
 * @param string $api_key       API key.
 * @param int    $cache_minutes Cache duration in minutes.
 * @return array|WP_Error
 */
function sdc_gmb_get_place_details( $place_id, $api_key, $cache_minutes ) {
    $place_id = trim( $place_id );
    $api_key  = trim( $api_key );

    if ( '' === $place_id || '' === $api_key ) {
        return new WP_Error( 'sdc_gmb_missing_config', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $transient_key = 'sdc_gmb_review_badge_' . md5( $place_id );
    $cache_minutes = absint( $cache_minutes );

    if ( $cache_minutes > 0 ) {
        $cached = get_transient( $transient_key );

        if ( false !== $cached ) {
            if ( is_array( $cached ) ) {
                if ( ! isset( $cached['reviews'] ) || ! is_array( $cached['reviews'] ) ) {
                    $cached['reviews'] = array();
                }

                return $cached;
            }

            delete_transient( $transient_key );
        }
    }

    $url = esc_url_raw(
        add_query_arg(
            array(
                'place_id' => $place_id,
                // TODO: Confirm if specifying sub-fields reduces quota usage for the reviews payload.
                'fields'   => 'rating,user_ratings_total,reviews',
                'key'      => $api_key,
            ),
            'https://maps.googleapis.com/maps/api/place/details/json'
        )
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 15,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code ) {
        return new WP_Error( 'sdc_gmb_http_error', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'sdc_gmb_json_error', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    if ( isset( $data['status'] ) && 'OK' !== $data['status'] ) {
        return new WP_Error( 'sdc_gmb_api_status', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    if ( empty( $data['result']['rating'] ) || ! isset( $data['result']['user_ratings_total'] ) ) {
        return new WP_Error( 'sdc_gmb_missing_fields', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $reviews = array();

    if ( ! empty( $data['result']['reviews'] ) && is_array( $data['result']['reviews'] ) ) {
        foreach ( array_slice( $data['result']['reviews'], 0, 8 ) as $review ) {
            if ( ! is_array( $review ) ) {
                continue;
            }

            $reviews[] = array(
                'author_name' => isset( $review['author_name'] ) ? sanitize_text_field( $review['author_name'] ) : '',
                'rating'      => isset( $review['rating'] ) ? (float) $review['rating'] : 0.0,
                'text'        => isset( $review['text'] ) ? sanitize_textarea_field( $review['text'] ) : '',
                'time'        => isset( $review['time'] ) ? absint( $review['time'] ) : 0,
            );
        }
    }

    $result = array(
        'rating'             => (float) $data['result']['rating'],
        'user_ratings_total' => (int) $data['result']['user_ratings_total'],
        'reviews'            => $reviews,
    );

    if ( $cache_minutes > 0 ) {
        set_transient( $transient_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
    }

    return $result;
}

/**
 * Backwards-compatible helper for existing integrations fetching rating data only.
 *
 * @param string $place_id      Place ID.
 * @param string $api_key       API key.
 * @param int    $cache_minutes Cache duration in minutes.
 * @return array|WP_Error
 */
function sdc_gmb_get_rating_data( $place_id, $api_key, $cache_minutes ) {
    return sdc_gmb_get_place_details( $place_id, $api_key, $cache_minutes );
}

/**
 * Ensure front-end style is registered and enqueued.
 */
function sdc_gmb_enqueue_badge_style() {
    static $style_added = false;
    $handle             = 'sdc-gmb-review-badge';

    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, false, array(), SDC_GMB_REVIEW_BADGE_VERSION );
    }

    if ( ! wp_style_is( $handle, 'enqueued' ) ) {
        wp_enqueue_style( $handle );
    }

    if ( ! $style_added ) {
        $css = '.sdc-gmb-review-badge{display:inline-flex;align-items:center;color:#1E2A3A;border-radius:9999px;padding:6px 12px;font-size:14px;line-height:1.2;font-weight:600;font-family:inherit;gap:10px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-visual{display:inline-flex;align-items:center;gap:8px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-icon-wrap{display:inline-flex;align-items:center;}' .
            '.sdc-gmb-review-badge .sdc-gmb-google-icon{width:22px;height:22px;flex-shrink:0;}' .
            '.sdc-gmb-review-badge .sdc-gmb-google-icon path{fill:currentColor;}' .
            '.sdc-gmb-review-badge .sdc-gmb-stars{display:inline-flex;gap:2px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-star{width:16px;height:16px;display:block;}' .
            '.sdc-gmb-review-badge .sdc-gmb-star-bg{fill:#4d5d72;}' .
            '.sdc-gmb-review-badge .sdc-gmb-text{white-space:nowrap;}' .
            '.sdc-gmb-review-badge .sdc-gmb-text strong{font-weight:700;}';

        wp_add_inline_style( $handle, $css );
        $style_added = true;
    }
}

/**
 * Register front-end assets for the reviews carousel.
 */
function sdc_gmb_register_reviews_carousel_assets() {
    $style_handle  = 'sdc-gmb-reviews-carousel';
    $script_handle = 'sdc-gmb-reviews-carousel';

    if ( ! wp_style_is( $style_handle, 'registered' ) ) {
        wp_register_style(
            $style_handle,
            plugins_url( 'assets/css/review-carousel.css', __FILE__ ),
            array(),
            SDC_GMB_REVIEW_BADGE_VERSION
        );
    }

    if ( ! wp_script_is( $script_handle, 'registered' ) ) {
        wp_register_script(
            $script_handle,
            plugins_url( 'assets/js/review-carousel.js', __FILE__ ),
            array(),
            SDC_GMB_REVIEW_BADGE_VERSION,
            true
        );

        wp_script_add_data( $script_handle, 'strategy', 'defer' );

        wp_localize_script(
            $script_handle,
            'sdcGmbCarouselL10n',
            array(
                'status' => __( 'Showing reviews %1$s–%2$s of %3$s', 'sdc-gmb-review-badge' ),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'sdc_gmb_register_reviews_carousel_assets' );

/**
 * Render the reviews carousel shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function sdc_gmb_render_reviews_carousel_shortcode( $atts ) {
    $options = sdc_gmb_get_options();

    $atts = shortcode_atts(
        array(
            'place_id'      => $options['place_id'],
            'api_key'       => $options['api_key'],
            'cache_minutes' => $options['cache_minutes'],
            'min_rating'    => $options['min_rating'],
            'reviews_limit' => $options['reviews_limit'],
            'slides_desktop'=> $options['slides_desktop'],
            'slides_tablet' => $options['slides_tablet'],
            'slides_mobile' => $options['slides_mobile'],
        ),
        $atts,
        'sdc_gmb_reviews_carousel'
    );

    $place_id      = sanitize_text_field( $atts['place_id'] );
    $api_key       = sanitize_text_field( $atts['api_key'] );
    $cache_minutes = absint( $atts['cache_minutes'] );
    $min_rating    = (float) $atts['min_rating'];
    $reviews_limit = absint( $atts['reviews_limit'] );
    $slides_desktop = max( 1, absint( $atts['slides_desktop'] ) );
    $slides_tablet  = max( 1, absint( $atts['slides_tablet'] ) );
    $slides_mobile  = max( 1, absint( $atts['slides_mobile'] ) );

    if ( $min_rating < 0 ) {
        $min_rating = 0;
    } elseif ( $min_rating > 5 ) {
        $min_rating = 5;
    }

    if ( $reviews_limit < 1 ) {
        $reviews_limit = 1;
    } elseif ( $reviews_limit > 8 ) {
        $reviews_limit = 8;
    }

    wp_enqueue_style( 'sdc-gmb-reviews-carousel' );

    $data = sdc_gmb_get_place_details( $place_id, $api_key, $cache_minutes );

    if ( is_wp_error( $data ) ) {
        return '<div class="sdc-gmb-carousel sdc-gmb-carousel--error"><p>' . esc_html__( 'Reviews unavailable.', 'sdc-gmb-review-badge' ) . '</p></div>';
    }

    $star_color = sanitize_hex_color( $options['star_color'] );

    if ( empty( $star_color ) ) {
        $star_color = sdc_gmb_get_default_options()['star_color'];
    }

    $reviews = array();

    if ( ! empty( $data['reviews'] ) && is_array( $data['reviews'] ) ) {
        foreach ( $data['reviews'] as $review ) {
            if ( ! is_array( $review ) ) {
                continue;
            }

            $rating = isset( $review['rating'] ) ? (float) $review['rating'] : 0.0;

            if ( $rating < $min_rating ) {
                continue;
            }

            $reviews[] = $review;

            if ( count( $reviews ) >= $reviews_limit ) {
                break;
            }
        }
    }

    if ( empty( $reviews ) ) {
        return '<div class="sdc-gmb-carousel sdc-gmb-carousel--empty"><p>' . esc_html__( 'No reviews match the current filters yet. Check back soon!', 'sdc-gmb-review-badge' ) . '</p></div>';
    }

    wp_enqueue_script( 'sdc-gmb-reviews-carousel' );

    $carousel_id = 'sdc-gmb-carousel-' . wp_unique_id();
    $list_id     = $carousel_id . '-list';
    $status_id   = $carousel_id . '-status';
    $total_items = count( $reviews );

    $classes = array(
        'sdc-gmb-carousel',
        'sdc-gmb-carousel--desktop-' . $slides_desktop,
        'sdc-gmb-carousel--tablet-' . $slides_tablet,
        'sdc-gmb-carousel--mobile-' . $slides_mobile,
    );

    $style_attribute = sprintf(
        ' style="--sdc-gmb-slides-desktop:%1$d;--sdc-gmb-slides-tablet:%2$d;--sdc-gmb-slides-mobile:%3$d;"',
        $slides_desktop,
        $slides_tablet,
        $slides_mobile
    );

    $items_markup = '';

    foreach ( $reviews as $index => $review ) {
        $author    = isset( $review['author_name'] ) ? sanitize_text_field( $review['author_name'] ) : '';
        $rating    = isset( $review['rating'] ) ? (float) $review['rating'] : 0.0;
        $text      = isset( $review['text'] ) ? sanitize_textarea_field( $review['text'] ) : '';
        $timestamp = isset( $review['time'] ) ? absint( $review['time'] ) : 0;

        $item_label = sprintf(
            /* translators: 1: review position, 2: total reviews */
            __( 'Review %1$s of %2$s', 'sdc-gmb-review-badge' ),
            number_format_i18n( $index + 1 ),
            number_format_i18n( $total_items )
        );

        $rating_label = sprintf(
            /* translators: %s: star rating value */
            __( 'Rated %s out of 5', 'sdc-gmb-review-badge' ),
            number_format_i18n( round( $rating, 1 ), 1 )
        );

        $date_markup = '';

        if ( $timestamp > 0 ) {
            $date_markup = '<time class="sdc-gmb-review-card__date" datetime="' . esc_attr( gmdate( 'c', $timestamp ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) ) . '</time>';
        }

        $items_markup .= '<li class="sdc-gmb-carousel__item" data-carousel-item role="group" aria-label="' . esc_attr( $item_label ) . '">';
        $items_markup .= '<article class="sdc-gmb-review-card">';
        $items_markup .= '<header class="sdc-gmb-review-card__header">';
        $items_markup .= '<div class="sdc-gmb-review-card__rating" aria-label="' . esc_attr( $rating_label ) . '" role="img">' . sdc_gmb_get_stars_markup( $rating, 5, $star_color ) . '</div>';

        if ( '' !== $author || '' !== $date_markup ) {
            $items_markup .= '<p class="sdc-gmb-review-card__meta">';

            if ( '' !== $author ) {
                $items_markup .= '<span class="sdc-gmb-review-card__author">' . esc_html( $author ) . '</span>';
            }

            if ( '' !== $author && '' !== $date_markup ) {
                $items_markup .= '<span class="sdc-gmb-review-card__separator" aria-hidden="true">&bull;</span>';
            }

            if ( '' !== $date_markup ) {
                $items_markup .= $date_markup;
            }

            $items_markup .= '</p>';
        }

        if ( '' !== $text ) {
            $items_markup .= '<p class="sdc-gmb-review-card__text">' . esc_html( $text ) . '</p>';
        }

        $items_markup .= '</header>';
        $items_markup .= '</article>';
        $items_markup .= '</li>';
    }

    $region_label = __( 'Google reviews carousel', 'sdc-gmb-review-badge' );

    $markup  = '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" role="region" aria-label="' . esc_attr( $region_label ) . '" data-slides-desktop="' . esc_attr( $slides_desktop ) . '" data-slides-tablet="' . esc_attr( $slides_tablet ) . '" data-slides-mobile="' . esc_attr( $slides_mobile ) . '" data-carousel-total="' . esc_attr( $total_items ) . '"' . $style_attribute . '>';
    $markup .= '<div class="sdc-gmb-carousel__controls">';
    $markup .= '<button type="button" class="sdc-gmb-carousel__button sdc-gmb-carousel__button--prev" data-carousel-prev aria-controls="' . esc_attr( $list_id ) . '">' . esc_html__( 'Previous reviews', 'sdc-gmb-review-badge' ) . '</button>';
    $markup .= '<button type="button" class="sdc-gmb-carousel__button sdc-gmb-carousel__button--next" data-carousel-next aria-controls="' . esc_attr( $list_id ) . '">' . esc_html__( 'Next reviews', 'sdc-gmb-review-badge' ) . '</button>';
    $markup .= '</div>';
    $markup .= '<div class="sdc-gmb-carousel__viewport">';
    $markup .= '<ul class="sdc-gmb-carousel__list" id="' . esc_attr( $list_id ) . '" data-carousel-list>' . $items_markup . '</ul>';
    $markup .= '</div>';
    $markup .= '<p id="' . esc_attr( $status_id ) . '" class="sdc-gmb-carousel__status screen-reader-text" aria-live="polite" data-carousel-live>';
    $markup .= sprintf(
        /* translators: 1: starting review index, 2: ending review index, 3: total reviews */
        esc_html__( 'Showing reviews %1$s–%2$s of %3$s', 'sdc-gmb-review-badge' ),
        1,
        min( $slides_mobile, $total_items ),
        $total_items
    );
    $markup .= '</p>';
    $markup .= '</section>';

    return $markup;
}

/**
 * Render stars markup.
 *
 * @param float  $rating     Rating value (0-5).
 * @param int    $star_count Number of stars to display.
 * @param string $star_color Hex colour for filled area.
 * @return string
 */
function sdc_gmb_get_stars_markup( $rating, $star_count, $star_color ) {
    $rating     = max( 0, (float) $rating );
    $star_count = max( 1, (int) $star_count );
    $per_star   = 5 / $star_count;
    $clip_base  = 'sdc-gmb-clip-' . wp_unique_id();
    $star_path  = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z';
    $gray       = '#4d5d72';

    $markup = '';

    for ( $i = 0; $i < $star_count; $i++ ) {
        $star_start = $i * $per_star;
        $progress   = ( $rating - $star_start ) / $per_star;
        $progress   = max( 0, min( 1, $progress ) );
        $clip_id    = $clip_base . '-' . $i;
        $width      = $progress * 100;

        $markup .= '<svg class="sdc-gmb-star" viewBox="0 0 24 24" aria-hidden="true" focusable="false">';
        $markup .= '<defs><clipPath id="' . esc_attr( $clip_id ) . '"><rect x="0" y="0" width="' . esc_attr( $width ) . '%" height="100%" /></clipPath></defs>';
        $markup .= '<path class="sdc-gmb-star-bg" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $gray ) . '" />';
        $markup .= '<path class="sdc-gmb-star-fill" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $star_color ) . '" clip-path="url(#' . esc_attr( $clip_id ) . ')" />';
        $markup .= '</svg>';
    }

    return $markup;
}

/**
 * Shortcode handler.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function sdc_gmb_render_badge_shortcode( $atts ) {
    $options = sdc_gmb_get_options();

    $atts = shortcode_atts(
        array(
            'place_id'      => $options['place_id'],
            'api_key'       => $options['api_key'],
            'stars'         => $options['stars'],
            'star_color'    => $options['star_color'],
            'accent_color'  => $options['accent_color'],
            'cache_minutes' => $options['cache_minutes'],
        ),
        $atts,
        'sdc_gmb_review_badge'
    );

    $place_id      = sanitize_text_field( $atts['place_id'] );
    $api_key       = sanitize_text_field( $atts['api_key'] );
    $star_count    = absint( $atts['stars'] );
    $cache_minutes = absint( $atts['cache_minutes'] );
    $star_color    = sanitize_hex_color( $atts['star_color'] );
    $accent_color  = sanitize_hex_color( $atts['accent_color'] );

    if ( $star_count < 1 ) {
        $star_count = 1;
    } elseif ( $star_count > 10 ) {
        $star_count = 10;
    }

    if ( empty( $star_color ) ) {
        $star_color = sdc_gmb_get_default_options()['star_color'];
    }

    if ( empty( $accent_color ) ) {
        $accent_color = sdc_gmb_get_default_options()['accent_color'];
    }

    $data      = sdc_gmb_get_place_details( $place_id, $api_key, $cache_minutes );
    $has_error = is_wp_error( $data );

    if ( ! $has_error && is_array( $data ) ) {
        $data = wp_parse_args(
            $data,
            array(
                'rating'             => 0,
                'user_ratings_total' => 0,
                'reviews'            => array(),
            )
        );
    }

    $rating_value = $has_error ? 0 : (float) $data['rating'];
    $total        = $has_error ? 0 : (int) $data['user_ratings_total'];

    $aria_label = $has_error
        ? __( 'Google rating: Reviews unavailable', 'sdc-gmb-review-badge' )
        : sprintf(
            /* translators: 1: rating value, 2: total reviews */
            __( 'Google rating: %1$s out of 5 from %2$s reviews', 'sdc-gmb-review-badge' ),
            number_format_i18n( round( $rating_value, 1 ), 1 ),
            number_format_i18n( $total )
        );

    $stars_markup = sdc_gmb_get_stars_markup( $rating_value, $star_count, $star_color );

    sdc_gmb_enqueue_badge_style();

    $icon_svg = '<svg class="sdc-gmb-google-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<path fill="currentColor" d="M21.6 12.227c0-.74-.066-1.45-.19-2.14H12v4.05h5.44a4.65 4.65 0 0 1-2.02 3.05v2.53h3.27c1.92-1.77 3-4.38 3-7.49z" />'
        . '<path fill="currentColor" d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.27-2.53c-.91.61-2.07.97-3.35.97-2.58 0-4.77-1.74-5.55-4.07H2.97v2.56A9.99 9.99 0 0 0 12 22z" />'
        . '<path fill="currentColor" d="M6.45 13.94a6.004 6.004 0 0 1 0-3.88V7.5H2.97a10 10 0 0 0 0 8.99l3.48-2.55z" />'
        . '<path fill="currentColor" d="M12 6.38c1.47 0 2.79.5 3.83 1.47l2.86-2.86C16.96 2.92 14.7 2 12 2a9.99 9.99 0 0 0-9.03 5.5l3.48 2.56C7.23 8.12 9.42 6.38 12 6.38z" />'
        . '</svg>';

    if ( $has_error ) {
        $text_markup = '<span class="sdc-gmb-text">' . esc_html__( 'Reviews unavailable', 'sdc-gmb-review-badge' ) . '</span>';
    } else {
        $formatted_rating = number_format_i18n( round( $rating_value, 1 ), 1 );
        $reviews_text     = sprintf(
            _n( '%s Google review', '%s Google reviews', $total, 'sdc-gmb-review-badge' ),
            number_format_i18n( $total )
        );

        /* translators: %s: human-readable Google review count (e.g. "120 Google reviews"). */
        $summary_text = sprintf( __( 'out of 5 from %s', 'sdc-gmb-review-badge' ), $reviews_text );

        $text_markup = '<span class="sdc-gmb-text"><strong>' . esc_html( $formatted_rating ) . '</strong> ' . esc_html( $summary_text ) . '</span>';
    }

    $style_attr = ' style="color:' . esc_attr( $accent_color ) . ';"';

    $badge  = '<div class="sdc-gmb-review-badge"' . $style_attr . ' role="img" aria-label="' . esc_attr( $aria_label ) . '">';
    $badge .= '<span class="sdc-gmb-visual" aria-hidden="true">';
    $badge .= '<span class="sdc-gmb-icon-wrap">' . $icon_svg . '</span>';
    $badge .= '<span class="sdc-gmb-stars">' . $stars_markup . '</span>';
    $badge .= $text_markup;
    $badge .= '</span>';
    $badge .= '</div>';

    return $badge;
}
add_shortcode( 'sdc_gmb_reviews_carousel', 'sdc_gmb_render_reviews_carousel_shortcode' );
add_shortcode( 'sdc_gmb_review_badge', 'sdc_gmb_render_badge_shortcode' );
add_shortcode( 'sdc_gmb_badge', 'sdc_gmb_render_badge_shortcode' );
add_shortcode( 'stoke_gbp_badge', 'sdc_gmb_render_badge_shortcode' );
