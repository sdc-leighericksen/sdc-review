<?php
/**
 * Plugin Name: SDC Review Carousel
 * Plugin URI: https://stokedesign.co
 * Description: Showcase Google Business Profile reviews with an autoplaying carousel and rating badge via accessible shortcodes with configurable styling and caching.
 * Version: 1.4.0
 * Author: Stoke Design Co
 * Author URI: https://stokedesign.co
 * Text Domain: sdc-review-carousel
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SDC_REVIEW_CAROUSEL_VERSION' ) ) {
    define( 'SDC_REVIEW_CAROUSEL_VERSION', '1.4.0' );
}

/**
 * Load plugin text domain.
 */
function sdc_rc_load_textdomain() {
    load_plugin_textdomain( 'sdc-review-carousel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'sdc_rc_load_textdomain' );

/**
 * Retrieve default options.
 *
 * @return array
 */
function sdc_rc_get_default_options() {
    return array(
        'api_key'       => '',
        'place_id'      => '',
        'stars'         => 5,
        'star_color'    => '#E9966F',
        'accent_color'  => '#1E2A3A',
        'review_background_color' => '#F4F6F9',
        'review_text_color'       => '#1E2A3A',
        'review_meta_color'       => '#4D5D72',
        'review_gap'              => 20,
        'review_dot_color'        => '#1E2A3A',
        'cache_minutes' => 720,
        'min_rating'    => 0.0,
        'reviews_limit'        => 6,
        'slides_desktop'       => 3,
        'slides_tablet'        => 2,
        'slides_mobile'        => 1,
        'autoplay_delay'       => 5000,
        'transition_duration'  => 300,
        'elementor_skin'       => 'badge', // TODO: Confirm Elementor skin slug expectation once widget integration is finalised.
    );
}

/**
 * Retrieve saved options merged with defaults.
 *
 * @return array
 */
function sdc_rc_get_options() {
    $options = get_option( 'sdc_review_carousel_options', array() );

    if ( ! is_array( $options ) || empty( $options ) ) {
        $legacy_keys = array(
            'sdc_gmb_review_badge_options',
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

    return wp_parse_args( $options, sdc_rc_get_default_options() );
}

/**
 * Register settings and fields.
 */
function sdc_rc_register_settings() {
    register_setting( 'sdc_review_carousel_options_group', 'sdc_review_carousel_options', 'sdc_rc_sanitize_options' );

    add_settings_section(
        'sdc_review_carousel_section',
        __( 'Google Business Profile', 'sdc-review-carousel' ),
        '__return_false',
        'sdc_review_carousel'
    );

    add_settings_field(
        'api_key',
        __( 'Google Places API Key', 'sdc-review-carousel' ),
        'sdc_rc_render_api_key_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'place_id',
        __( 'Place ID', 'sdc-review-carousel' ),
        'sdc_rc_render_place_id_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'stars',
        __( 'Number of Stars to Display', 'sdc-review-carousel' ),
        'sdc_rc_render_stars_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'star_color',
        __( 'Star Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_star_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'accent_color',
        __( 'Text and Icon Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_accent_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'review_background_color',
        __( 'Review Background Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_review_background_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'review_text_color',
        __( 'Review Text Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_review_text_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'review_meta_color',
        __( 'Review Meta Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_review_meta_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'review_gap',
        __( 'Review Horizontal Gap (px)', 'sdc-review-carousel' ),
        'sdc_rc_render_review_gap_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'review_dot_color',
        __( 'Dot Navigation Colour', 'sdc-review-carousel' ),
        'sdc_rc_render_review_dot_color_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'cache_minutes',
        __( 'Cache Duration (minutes)', 'sdc-review-carousel' ),
        'sdc_rc_render_cache_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'min_rating',
        __( 'Minimum Review Rating', 'sdc-review-carousel' ),
        'sdc_rc_render_min_rating_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'reviews_limit',
        __( 'Reviews Limit', 'sdc-review-carousel' ),
        'sdc_rc_render_reviews_limit_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'slides_desktop',
        __( 'Desktop Slides Visible', 'sdc-review-carousel' ),
        'sdc_rc_render_slide_count_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section',
        array(
            'key'         => 'slides_desktop',
            'input_id'    => 'sdc-review-slides-desktop',
            'max'         => 5,
            'description' => __( 'Number of review cards to display side-by-side on desktop breakpoints.', 'sdc-review-carousel' ),
        )
    );

    add_settings_field(
        'slides_tablet',
        __( 'Tablet Slides Visible', 'sdc-review-carousel' ),
        'sdc_rc_render_slide_count_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section',
        array(
            'key'         => 'slides_tablet',
            'input_id'    => 'sdc-review-slides-tablet',
            'max'         => 4,
            'description' => __( 'Number of review cards to display on medium-width tablet layouts.', 'sdc-review-carousel' ),
        )
    );

    add_settings_field(
        'slides_mobile',
        __( 'Mobile Slides Visible', 'sdc-review-carousel' ),
        'sdc_rc_render_slide_count_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section',
        array(
            'key'         => 'slides_mobile',
            'input_id'    => 'sdc-review-slides-mobile',
            'max'         => 3,
            'description' => __( 'Number of review cards to display on small-screen devices.', 'sdc-review-carousel' ),
        )
    );

    add_settings_field(
        'autoplay_delay',
        __( 'Autoplay Delay (ms)', 'sdc-review-carousel' ),
        'sdc_rc_render_autoplay_delay_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'transition_duration',
        __( 'Transition Duration (ms)', 'sdc-review-carousel' ),
        'sdc_rc_render_transition_duration_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );

    add_settings_field(
        'elementor_skin',
        __( 'Elementor Skin', 'sdc-review-carousel' ),
        'sdc_rc_render_elementor_skin_field',
        'sdc_review_carousel',
        'sdc_review_carousel_section'
    );
}
add_action( 'admin_init', 'sdc_rc_register_settings' );

/**
 * Sanitize options before saving.
 *
 * @param array $input Raw input values.
 * @return array
 */
function sdc_rc_sanitize_options( $input ) {
    $options   = sdc_rc_get_options();
    $defaults  = sdc_rc_get_default_options();
    $sanitized = array();

    $sanitized['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : $options['api_key'];
    $sanitized['place_id']      = isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : $options['place_id'];
    $sanitized['stars']         = isset( $input['stars'] ) ? absint( $input['stars'] ) : $options['stars'];
    $sanitized['star_color']    = isset( $input['star_color'] ) ? sanitize_hex_color( $input['star_color'] ) : $options['star_color'];
    $sanitized['accent_color']  = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $options['accent_color'];
    $sanitized['review_background_color'] = isset( $input['review_background_color'] ) ? sanitize_hex_color( $input['review_background_color'] ) : $options['review_background_color'];
    $sanitized['review_text_color']       = isset( $input['review_text_color'] ) ? sanitize_hex_color( $input['review_text_color'] ) : $options['review_text_color'];
    $sanitized['review_meta_color']       = isset( $input['review_meta_color'] ) ? sanitize_hex_color( $input['review_meta_color'] ) : $options['review_meta_color'];
    $sanitized['review_dot_color']        = isset( $input['review_dot_color'] ) ? sanitize_hex_color( $input['review_dot_color'] ) : $options['review_dot_color'];
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

    $review_gap = isset( $input['review_gap'] ) ? absint( $input['review_gap'] ) : absint( $options['review_gap'] );

    if ( $review_gap > 200 ) {
        $review_gap = 200;
    }

    $sanitized['review_gap'] = $review_gap;

    $sanitized['autoplay_delay']      = isset( $input['autoplay_delay'] ) ? absint( $input['autoplay_delay'] ) : absint( $options['autoplay_delay'] );
    $sanitized['transition_duration'] = isset( $input['transition_duration'] ) ? absint( $input['transition_duration'] ) : absint( $options['transition_duration'] );

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

    if ( empty( $sanitized['review_background_color'] ) ) {
        $sanitized['review_background_color'] = $defaults['review_background_color'];
    }

    if ( empty( $sanitized['review_text_color'] ) ) {
        $sanitized['review_text_color'] = $defaults['review_text_color'];
    }

    if ( empty( $sanitized['review_meta_color'] ) ) {
        $sanitized['review_meta_color'] = $defaults['review_meta_color'];
    }

    if ( empty( $sanitized['review_dot_color'] ) ) {
        $sanitized['review_dot_color'] = $defaults['review_dot_color'];
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

    if ( $sanitized['autoplay_delay'] < 0 ) {
        $sanitized['autoplay_delay'] = 0;
    }

    if ( $sanitized['transition_duration'] < 0 ) {
        $sanitized['transition_duration'] = 0;
    } elseif ( $sanitized['transition_duration'] > 2000 ) {
        $sanitized['transition_duration'] = 2000;
    }

    if ( empty( $sanitized['elementor_skin'] ) ) {
        $sanitized['elementor_skin'] = sanitize_key( $defaults['elementor_skin'] );
    }

    return $sanitized;
}

/**
 * Render API key field.
 */
function sdc_rc_render_api_key_field() {
    $options = sdc_rc_get_options();

    printf(
        '<input type="text" name="sdc_review_carousel_options[api_key]" id="sdc-review-api-key" class="regular-text" value="%s" />',
        esc_attr( $options['api_key'] )
    );

    echo '<p class="description">' . esc_html__( 'Enter the restricted Google Places API key for your Google Business Profile.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render place ID field.
 */
function sdc_rc_render_place_id_field() {
    $options = sdc_rc_get_options();

    printf(
        '<input type="text" name="sdc_review_carousel_options[place_id]" id="sdc-review-place-id" class="regular-text" value="%s" />',
        esc_attr( $options['place_id'] )
    );

    echo '<p class="description">' . esc_html__( 'Paste the Place ID for your Google Business Profile location.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render stars field.
 */
function sdc_rc_render_stars_field() {
    $options = sdc_rc_get_options();

    printf(
        '<input type="number" min="1" max="10" name="sdc_review_carousel_options[stars]" id="sdc-review-stars" value="%d" />',
        absint( $options['stars'] )
    );

    echo '<p class="description">' . esc_html__( 'Choose how many star icons appear in the badge (between 1 and 10).', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render star colour field.
 */
function sdc_rc_render_star_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['star_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['star_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[star_color]" id="sdc-review-star-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['star_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the filled portion of the stars.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render accent colour field.
 */
function sdc_rc_render_accent_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['accent_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['accent_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[accent_color]" id="sdc-review-accent-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['accent_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the text and Google icon.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render review background colour field.
 */
function sdc_rc_render_review_background_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['review_background_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['review_background_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[review_background_color]" id="sdc-review-background-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['review_background_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Colour applied to each review card background.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render review text colour field.
 */
function sdc_rc_render_review_text_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['review_text_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['review_text_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[review_text_color]" id="sdc-review-text-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['review_text_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Primary body text colour for review content.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render review meta colour field.
 */
function sdc_rc_render_review_meta_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['review_meta_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['review_meta_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[review_meta_color]" id="sdc-review-meta-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['review_meta_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Colour used for reviewer names, dates, and separators.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render review gap field.
 */
function sdc_rc_render_review_gap_field() {
    $options = sdc_rc_get_options();
    $value   = isset( $options['review_gap'] ) ? absint( $options['review_gap'] ) : absint( sdc_rc_get_default_options()['review_gap'] );

    printf(
        '<input type="number" min="0" max="200" step="1" name="sdc_review_carousel_options[review_gap]" id="sdc-review-gap" value="%d" />',
        $value
    );

    echo '<p class="description">' . esc_html__( 'Horizontal spacing between review cards in pixels. Set to 0 for a flush layout.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render review dot colour field.
 */
function sdc_rc_render_review_dot_color_field() {
    $options = sdc_rc_get_options();
    $color   = sanitize_hex_color( $options['review_dot_color'] );

    if ( ! $color ) {
        $color = sdc_rc_get_default_options()['review_dot_color'];
    }

    printf(
        '<input type="text" name="sdc_review_carousel_options[review_dot_color]" id="sdc-review-dot-color" class="sdc-review-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_rc_get_default_options()['review_dot_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Accent colour for the dot navigation (active, hover, and focus states).', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render cache field.
 */
function sdc_rc_render_cache_field() {
    $options = sdc_rc_get_options();

    printf(
        '<input type="number" min="0" name="sdc_review_carousel_options[cache_minutes]" id="sdc-review-cache" value="%d" />',
        absint( $options['cache_minutes'] )
    );

    echo '<p class="description">' . esc_html__( 'Cache the Google API response for this many minutes. Set to 0 to disable caching temporarily.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render minimum rating field.
 */
function sdc_rc_render_min_rating_field() {
    $options     = sdc_rc_get_options();
    $min_rating  = isset( $options['min_rating'] ) ? (float) $options['min_rating'] : sdc_rc_get_default_options()['min_rating'];
    $input_value = number_format( $min_rating, 1, '.', '' );

    printf(
        '<input type="number" min="0" max="5" step="0.1" name="sdc_review_carousel_options[min_rating]" id="sdc-review-min-rating" value="%s" />',
        esc_attr( $input_value )
    );

    echo '<p class="description">' . esc_html__( 'Ignore reviews below this rating when rendering Elementor carousels or lists.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render reviews limit field.
 */
function sdc_rc_render_reviews_limit_field() {
    $options = sdc_rc_get_options();

    printf(
        '<input type="number" min="1" max="10" name="sdc_review_carousel_options[reviews_limit]" id="sdc-review-reviews-limit" value="%d" />',
        absint( $options['reviews_limit'] )
    );

    echo '<p class="description">' . esc_html__( 'Cap how many Google reviews to fetch for Elementor-powered layouts.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render slide count field for responsive breakpoints.
 *
 * @param array $args Callback arguments.
 */
function sdc_rc_render_slide_count_field( $args ) {
    $defaults = array(
        'key'         => '',
        'input_id'    => '',
        'max'         => 3,
        'description' => '',
    );

    $args    = wp_parse_args( $args, $defaults );
    $options = sdc_rc_get_options();
    $key     = $args['key'];

    if ( '' === $key ) {
        return;
    }

    $value = isset( $options[ $key ] ) ? absint( $options[ $key ] ) : absint( sdc_rc_get_default_options()[ $key ] );

    printf(
        '<input type="number" min="1" max="%1$d" name="sdc_review_carousel_options[%2$s]" id="%3$s" value="%4$d" />',
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
 * Render autoplay delay field.
 */
function sdc_rc_render_autoplay_delay_field() {
    $options = sdc_rc_get_options();
    $value   = isset( $options['autoplay_delay'] ) ? absint( $options['autoplay_delay'] ) : absint( sdc_rc_get_default_options()['autoplay_delay'] );

    printf(
        '<input type="number" min="0" step="100" name="sdc_review_carousel_options[autoplay_delay]" id="sdc-review-autoplay-delay" value="%d" />',
        $value
    );

    echo '<p class="description">' . esc_html__( 'Milliseconds to wait before advancing to the next set of reviews. Set to 0 to disable automatic sliding.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render transition duration field.
 */
function sdc_rc_render_transition_duration_field() {
    $options = sdc_rc_get_options();
    $value   = isset( $options['transition_duration'] ) ? absint( $options['transition_duration'] ) : absint( sdc_rc_get_default_options()['transition_duration'] );

    printf(
        '<input type="number" min="0" max="2000" step="50" name="sdc_review_carousel_options[transition_duration]" id="sdc-review-transition-duration" value="%d" />',
        $value
    );

    echo '<p class="description">' . esc_html__( 'Milliseconds used for animated scrolling when changing slides.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Render Elementor skin field.
 */
function sdc_rc_render_elementor_skin_field() {
    $options = sdc_rc_get_options();
    $skin    = isset( $options['elementor_skin'] ) ? sanitize_key( $options['elementor_skin'] ) : sanitize_key( sdc_rc_get_default_options()['elementor_skin'] );

    printf(
        '<input type="text" name="sdc_review_carousel_options[elementor_skin]" id="sdc-review-elementor-skin" value="%s" class="regular-text" />',
        esc_attr( $skin )
    );

    echo '<p class="description">' . esc_html__( 'Default Elementor skin slug for the reviews widget. Adjust only if you have custom Elementor templates.', 'sdc-review-carousel' ) . '</p>';
}

/**
 * Add settings page.
 */
function sdc_rc_add_settings_page() {
    add_options_page(
        esc_html__( 'SDC Review Carousel', 'sdc-review-carousel' ),
        esc_html__( 'SDC Review Carousel', 'sdc-review-carousel' ),
        'manage_options',
        'sdc-review-carousel',
        'sdc_rc_render_settings_page'
    );
}
add_action( 'admin_menu', 'sdc_rc_add_settings_page' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function sdc_rc_admin_enqueue_assets( $hook ) {
    if ( 'settings_page_sdc-review-carousel' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".sdc-review-color-field").wpColorPicker();});' );
}
add_action( 'admin_enqueue_scripts', 'sdc_rc_admin_enqueue_assets' );

/**
 * Render settings page markup.
 */
function sdc_rc_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_SPECIAL_CHARS );

    if ( $updated ) {
        add_settings_error( 'sdc_rc_messages', 'sdc_rc_message', esc_html__( 'Settings saved.', 'sdc-review-carousel' ), 'updated' );
    }

    settings_errors( 'sdc_rc_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'SDC Review Carousel', 'sdc-review-carousel' ); ?></h1>
        <p><?php esc_html_e( 'Display Google Business Profile reviews as a sliding carousel or a compact badge using the available shortcodes.', 'sdc-review-carousel' ); ?></p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'sdc_review_carousel_options_group' );
            do_settings_sections( 'sdc_review_carousel' );
            submit_button( __( 'Save Changes', 'sdc-review-carousel' ) );
            ?>
        </form>
        <hr />
        <h2><?php esc_html_e( 'Help', 'sdc-review-carousel' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Create or select a Google Cloud project, enable the Places API, and generate a restricted API key.', 'sdc-review-carousel' ); ?></li>
            <li><?php esc_html_e( 'Use the Google Place ID Finder to look up the Place ID for your business.', 'sdc-review-carousel' ); ?></li>
            <li><?php esc_html_e( 'Enter both values above and save. Then add the shortcode to any page, post, or widget.', 'sdc-review-carousel' ); ?></li>
        </ol>
        <h3><?php esc_html_e( 'Shortcode Examples', 'sdc-review-carousel' ); ?></h3>
        <ul>
            <li><code>[sdc_review_carousel]</code></li>
            <li><code>[sdc_review_carousel autoplay_delay="7000" transition_duration="400"]</code></li>
            <li><code>[sdc_review_carousel slides_desktop="2" slides_tablet="2" slides_mobile="1"]</code></li>
            <li><code>[sdc_review_badge]</code></li>
        </ul>
        <p><?php esc_html_e( 'Change the cache duration or pass cache_minutes="0" in either shortcode to refresh the data on the next render.', 'sdc-review-carousel' ); ?></p>
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
function sdc_rc_get_place_details( $place_id, $api_key, $cache_minutes ) {
    $place_id = trim( $place_id );
    $api_key  = trim( $api_key );

    if ( '' === $place_id || '' === $api_key ) {
        return new WP_Error( 'sdc_rc_missing_config', __( 'Reviews unavailable', 'sdc-review-carousel' ) );
    }

    $transient_key = 'sdc_review_carousel_' . md5( $place_id );
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
        return new WP_Error( 'sdc_rc_http_error', __( 'Reviews unavailable', 'sdc-review-carousel' ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'sdc_rc_json_error', __( 'Reviews unavailable', 'sdc-review-carousel' ) );
    }

    if ( isset( $data['status'] ) && 'OK' !== $data['status'] ) {
        return new WP_Error( 'sdc_rc_api_status', __( 'Reviews unavailable', 'sdc-review-carousel' ) );
    }

    if ( empty( $data['result']['rating'] ) || ! isset( $data['result']['user_ratings_total'] ) ) {
        return new WP_Error( 'sdc_rc_missing_fields', __( 'Reviews unavailable', 'sdc-review-carousel' ) );
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
function sdc_rc_get_rating_data( $place_id, $api_key, $cache_minutes ) {
    return sdc_rc_get_place_details( $place_id, $api_key, $cache_minutes );
}

/**
 * Ensure front-end style is registered and enqueued.
 */
function sdc_rc_enqueue_badge_style() {
    static $style_added = false;
    $handle             = 'sdc-review-badge';

    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, false, array(), SDC_REVIEW_CAROUSEL_VERSION );
    }

    if ( ! wp_style_is( $handle, 'enqueued' ) ) {
        wp_enqueue_style( $handle );
    }

    if ( ! $style_added ) {
        $css = '.sdc-review-badge{display:inline-flex;align-items:center;color:#1E2A3A;border-radius:9999px;padding:6px 12px;font-size:14px;line-height:1.2;font-weight:600;font-family:inherit;gap:10px;}' .
            '.sdc-review-badge__visual{display:inline-flex;align-items:center;gap:8px;}' .
            '.sdc-review-badge__icon{display:inline-flex;align-items:center;}' .
            '.sdc-review-badge__icon .sdc-review-google-icon{width:22px;height:22px;flex-shrink:0;}' .
            '.sdc-review-badge__icon .sdc-review-google-icon path{fill:currentColor;}' .
            '.sdc-review-badge__stars{display:inline-flex;gap:2px;}' .
            '.sdc-review-badge .sdc-review-star{width:16px;height:16px;display:block;}' .
            '.sdc-review-badge .sdc-review-star-bg{fill:#4d5d72;}' .
            '.sdc-review-badge__text{white-space:nowrap;}' .
            '.sdc-review-badge__text strong{font-weight:700;}';

        wp_add_inline_style( $handle, $css );
        $style_added = true;
    }
}

/**
 * Register front-end assets for the reviews carousel.
 */
function sdc_rc_register_reviews_carousel_assets() {
    $style_handle = 'sdc-review-carousel';

    if ( ! wp_style_is( $style_handle, 'registered' ) ) {
        wp_register_style(
            $style_handle,
            plugins_url( 'assets/css/review-carousel.css', __FILE__ ),
            array(),
            SDC_REVIEW_CAROUSEL_VERSION
        );
    }

}
add_action( 'wp_enqueue_scripts', 'sdc_rc_register_reviews_carousel_assets' );

/**
 * Render the reviews carousel shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function sdc_rc_render_reviews_carousel_shortcode( $atts ) {
    $options  = sdc_rc_get_options();
    $defaults = sdc_rc_get_default_options();

    $atts = shortcode_atts(
        array(
            'place_id'             => $options['place_id'],
            'api_key'              => $options['api_key'],
            'cache_minutes'        => $options['cache_minutes'],
            'min_rating'           => $options['min_rating'],
            'reviews_limit'        => $options['reviews_limit'],
            'slides_desktop'       => $options['slides_desktop'],
            'slides_tablet'        => $options['slides_tablet'],
            'slides_mobile'        => $options['slides_mobile'],
            'autoplay_delay'       => $options['autoplay_delay'],
            'transition_duration'  => $options['transition_duration'],
            'review_background_color' => $options['review_background_color'],
            'review_text_color'       => $options['review_text_color'],
            'review_meta_color'       => $options['review_meta_color'],
            'review_gap'              => $options['review_gap'],
            'review_dot_color'        => $options['review_dot_color'],
        ),
        $atts,
        'sdc_review_badge'
    );

    $place_id             = sanitize_text_field( $atts['place_id'] );
    $api_key              = sanitize_text_field( $atts['api_key'] );
    $cache_minutes        = absint( $atts['cache_minutes'] );
    $min_rating           = (float) $atts['min_rating'];
    $reviews_limit        = absint( $atts['reviews_limit'] );
    $slides_desktop       = max( 1, absint( $atts['slides_desktop'] ) );
    $slides_tablet        = max( 1, absint( $atts['slides_tablet'] ) );
    $slides_mobile        = max( 1, absint( $atts['slides_mobile'] ) );
    $autoplay_delay       = absint( $atts['autoplay_delay'] );
    $transition_duration  = absint( $atts['transition_duration'] );
    $review_background_color = sanitize_hex_color( $atts['review_background_color'] );
    $review_text_color       = sanitize_hex_color( $atts['review_text_color'] );
    $review_meta_color       = sanitize_hex_color( $atts['review_meta_color'] );
    $review_dot_color        = sanitize_hex_color( $atts['review_dot_color'] );
    $review_gap              = absint( $atts['review_gap'] );

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

    if ( empty( $review_background_color ) ) {
        $review_background_color = $defaults['review_background_color'];
    }

    if ( empty( $review_text_color ) ) {
        $review_text_color = $defaults['review_text_color'];
    }

    if ( empty( $review_meta_color ) ) {
        $review_meta_color = $defaults['review_meta_color'];
    }

    if ( empty( $review_dot_color ) ) {
        $review_dot_color = $defaults['review_dot_color'];
    }

    if ( $review_gap > 200 ) {
        $review_gap = 200;
    }

    $style_rules = array(
        '--sdc-review-slides-desktop:' . $slides_desktop,
        '--sdc-review-slides-tablet:' . $slides_tablet,
        '--sdc-review-slides-mobile:' . $slides_mobile,
        '--sdc-review-card-background:' . $review_background_color,
        '--sdc-review-card-text:' . $review_text_color,
        '--sdc-review-card-meta:' . $review_meta_color,
        '--sdc-review-gap:' . $review_gap . 'px',
    );

    $style_attribute = ' style="' . esc_attr( implode( ';', $style_rules ) ) . ';"';

    wp_enqueue_style( 'sdc-review-carousel' );

    $data = sdc_rc_get_place_details( $place_id, $api_key, $cache_minutes );

    if ( is_wp_error( $data ) ) {
        return '<div class="sdc-review-carousel sdc-review-carousel--error"' . $style_attribute . '><p>' . esc_html__( 'Reviews unavailable.', 'sdc-review-carousel' ) . '</p></div>';
    }

    $star_color = sanitize_hex_color( $options['star_color'] );

    if ( empty( $star_color ) ) {
        $star_color = $defaults['star_color'];
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
        }
    }

    if ( empty( $reviews ) ) {
        return '<div class="sdc-review-carousel sdc-review-carousel--empty"' . $style_attribute . '><p>' . esc_html__( 'No reviews match the current filters yet. Check back soon!', 'sdc-review-carousel' ) . '</p></div>';
    }

    $max_display = min( $reviews_limit, count( $reviews ) );

    if ( $max_display < 1 ) {
        $max_display = 1;
    }

    $display_count = ( $max_display > 1 ) ? wp_rand( 1, $max_display ) : 1;

    if ( count( $reviews ) > 1 ) {
        shuffle( $reviews );
    }

    $selected_reviews = array_slice( $reviews, 0, $display_count );
    $total_items      = count( $selected_reviews );

    $classes = array(
        'sdc-review-carousel',
    );

    $items_markup = '';

    foreach ( $selected_reviews as $index => $review ) {
        $author    = isset( $review['author_name'] ) ? sanitize_text_field( $review['author_name'] ) : '';
        $rating    = isset( $review['rating'] ) ? (float) $review['rating'] : 0.0;
        $text      = isset( $review['text'] ) ? sanitize_textarea_field( $review['text'] ) : '';
        $timestamp = isset( $review['time'] ) ? absint( $review['time'] ) : 0;

        $rating_label = sprintf(
            /* translators: %s: star rating value */
            __( 'Rated %s out of 5', 'sdc-review-carousel' ),
            number_format_i18n( round( $rating, 1 ), 1 )
        );

        $date_markup = '';

        if ( $timestamp > 0 ) {
            $date_markup = '<time class="sdc-review-card__date" datetime="' . esc_attr( gmdate( 'c', $timestamp ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) ) . '</time>';
        }

        $items_markup .= '<li class="sdc-review-carousel__item">';
        $items_markup .= '<article class="sdc-review-card">';
        $items_markup .= '<header class="sdc-review-card__header">';
        $items_markup .= '<div class="sdc-review-card__rating" aria-label="' . esc_attr( $rating_label ) . '" role="img">' . sdc_rc_get_stars_markup( $rating, 5, $star_color ) . '</div>';

        if ( '' !== $author || '' !== $date_markup ) {
            $items_markup .= '<p class="sdc-review-card__meta">';

            if ( '' !== $author ) {
                $items_markup .= '<span class="sdc-review-card__author">' . esc_html( $author ) . '</span>';
            }

            if ( '' !== $author && '' !== $date_markup ) {
                $items_markup .= '<span class="sdc-review-card__separator" aria-hidden="true">&bull;</span>';
            }

            if ( '' !== $date_markup ) {
                $items_markup .= $date_markup;
            }

            $items_markup .= '</p>';
        }

        if ( '' !== $text ) {
            $items_markup .= '<p class="sdc-review-card__text">' . esc_html( $text ) . '</p>';
        }

        $items_markup .= '</header>';
        $items_markup .= '</article>';
        $items_markup .= '</li>';
    }

    $region_label = __( 'Google reviews', 'sdc-review-carousel' );

    $markup  = '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" role="region" aria-label="' . esc_attr( $region_label ) . '"' . $style_attribute . '>';
    $markup .= '<ul class="sdc-review-carousel__list">' . $items_markup . '</ul>';
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
function sdc_rc_get_stars_markup( $rating, $star_count, $star_color ) {
    $rating     = max( 0, (float) $rating );
    $star_count = max( 1, (int) $star_count );
    $per_star   = 5 / $star_count;
    $clip_base  = 'sdc-review-clip-' . wp_unique_id();
    $star_path  = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z';
    $gray       = '#4d5d72';

    $markup = '';

    for ( $i = 0; $i < $star_count; $i++ ) {
        $star_start = $i * $per_star;
        $progress   = ( $rating - $star_start ) / $per_star;
        $progress   = max( 0, min( 1, $progress ) );
        $clip_id    = $clip_base . '-' . $i;
        $width      = $progress * 100;

        $markup .= '<svg class="sdc-review-star" viewBox="0 0 24 24" aria-hidden="true" focusable="false">';
        $markup .= '<defs><clipPath id="' . esc_attr( $clip_id ) . '"><rect x="0" y="0" width="' . esc_attr( $width ) . '%" height="100%" /></clipPath></defs>';
        $markup .= '<path class="sdc-review-star-bg" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $gray ) . '" />';
        $markup .= '<path class="sdc-review-star-fill" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $star_color ) . '" clip-path="url(#' . esc_attr( $clip_id ) . ')" />';
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
function sdc_rc_render_badge_shortcode( $atts ) {
    $options = sdc_rc_get_options();

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
        'sdc_review_carousel'
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
        $star_color = sdc_rc_get_default_options()['star_color'];
    }

    if ( empty( $accent_color ) ) {
        $accent_color = sdc_rc_get_default_options()['accent_color'];
    }

    $data      = sdc_rc_get_place_details( $place_id, $api_key, $cache_minutes );
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
        ? __( 'Google rating: Reviews unavailable', 'sdc-review-carousel' )
        : sprintf(
            /* translators: 1: rating value, 2: total reviews */
            __( 'Google rating: %1$s out of 5 from %2$s reviews', 'sdc-review-carousel' ),
            number_format_i18n( round( $rating_value, 1 ), 1 ),
            number_format_i18n( $total )
        );

    $stars_markup = sdc_rc_get_stars_markup( $rating_value, $star_count, $star_color );

    sdc_rc_enqueue_badge_style();

    $icon_svg = '<svg class="sdc-review-google-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<path fill="currentColor" d="M21.6 12.227c0-.74-.066-1.45-.19-2.14H12v4.05h5.44a4.65 4.65 0 0 1-2.02 3.05v2.53h3.27c1.92-1.77 3-4.38 3-7.49z" />'
        . '<path fill="currentColor" d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.27-2.53c-.91.61-2.07.97-3.35.97-2.58 0-4.77-1.74-5.55-4.07H2.97v2.56A9.99 9.99 0 0 0 12 22z" />'
        . '<path fill="currentColor" d="M6.45 13.94a6.004 6.004 0 0 1 0-3.88V7.5H2.97a10 10 0 0 0 0 8.99l3.48-2.55z" />'
        . '<path fill="currentColor" d="M12 6.38c1.47 0 2.79.5 3.83 1.47l2.86-2.86C16.96 2.92 14.7 2 12 2a9.99 9.99 0 0 0-9.03 5.5l3.48 2.56C7.23 8.12 9.42 6.38 12 6.38z" />'
        . '</svg>';

    if ( $has_error ) {
        $text_markup = esc_html__( 'Reviews unavailable', 'sdc-review-carousel' );
    } else {
        $formatted_rating = number_format_i18n( round( $rating_value, 1 ), 1 );
        $reviews_text     = sprintf(
            _n( '%s Google review', '%s Google reviews', $total, 'sdc-review-carousel' ),
            number_format_i18n( $total )
        );

        /* translators: %s: human-readable Google review count (e.g. "120 Google reviews"). */
        $summary_text = sprintf( __( 'out of 5 from %s', 'sdc-review-carousel' ), $reviews_text );

        $text_markup = '<strong>' . esc_html( $formatted_rating ) . '</strong> ' . esc_html( $summary_text );
    }

    $style_attr = ' style="color:' . esc_attr( $accent_color ) . ';"';

    $badge  = '<div class="sdc-review-badge"' . $style_attr . ' role="img" aria-label="' . esc_attr( $aria_label ) . '">';
    $badge .= '<span class="sdc-review-badge__visual" aria-hidden="true">';
    $badge .= '<span class="sdc-review-badge__icon">' . $icon_svg . '</span>';
    $badge .= '<span class="sdc-review-badge__stars">' . $stars_markup . '</span>';
    $badge .= '</span>';
    $badge .= '<span class="sdc-review-badge__text">' . $text_markup . '</span>';
    $badge .= '</div>';

    return $badge;
}
add_shortcode( 'sdc_review_carousel', 'sdc_rc_render_reviews_carousel_shortcode' );
add_shortcode( 'sdc_gmb_reviews_carousel', 'sdc_rc_render_reviews_carousel_shortcode' );
add_shortcode( 'sdc_review_badge', 'sdc_rc_render_badge_shortcode' );
add_shortcode( 'sdc_gmb_review_badge', 'sdc_rc_render_badge_shortcode' );
add_shortcode( 'sdc_gmb_badge', 'sdc_rc_render_badge_shortcode' );
add_shortcode( 'stoke_gbp_badge', 'sdc_rc_render_badge_shortcode' );
