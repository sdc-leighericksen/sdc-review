# SDC Review Carousel

SDC Review Carousel is a lightweight WordPress plugin from [Stoke Design Co](https://stokedesign.co) that showcases your Google Business Profile reputation. Fetch review data via the Google Places Details API, cache it for performance, and surface it as an autoplaying carousel or compact rating badge—both delivered through accessible, internationalised shortcodes.

## Features

- Pull your live Google rating, total review count, and recent reviews via the Places API.
- Cache API responses to respect quota limits while keeping data fresh.
- Customise star counts, badge/review colours (background, text, meta, dots), horizontal spacing, minimum review rating, slide counts, autoplay delay, and transition speed from the settings page.
- Responsive carousel with dot navigation that appears on hover/focus, keyboard support, a continuous ticker track, and reduced-motion fallbacks.
- Backwards-compatible shortcode aliases for legacy integrations.

## Requirements

- WordPress 6.0 or newer.
- A Google Cloud project with the Places API enabled.
- A Places Details API key with appropriate HTTP referrer restrictions.
- The Google Place ID for the business location you wish to display.

## Installation

1. Download or clone this repository into your WordPress `wp-content/plugins` directory.
2. Ensure the folder name is `sdc-review-carousel` and that the main plugin file is `sdc-review-carousel.php`.
3. Log into the WordPress admin dashboard and activate **SDC Review Carousel** from the Plugins screen.

## Configuration

1. In the WordPress admin area, navigate to **Settings → SDC Review Carousel**.
2. Enter your restricted Google Places API key.
3. Paste the Place ID for the Google Business Profile you want to showcase.
4. Adjust the optional controls:
   - **Number of Stars to Display**, **Star Colour**, and **Text and Icon Colour** for the badge output.
   - **Review Background/Text/Meta Colours**, **Review Horizontal Gap**, and **Dot Navigation Colour** for the carousel cards.
   - **Cache Duration**, **Minimum Review Rating**, and **Reviews Limit** for data freshness and filtering.
   - **Slides Visible** options for desktop, tablet, and mobile breakpoints.
   - **Autoplay Delay (ms)** and **Transition Duration (ms)** for the carousel.
5. Click **Save Changes**.

> 💡 You can find your Place ID with the [Place ID Finder](https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder) and create your API key inside the [Google Cloud Console](https://console.cloud.google.com/).

## Shortcode usage

Add the badge or carousel anywhere shortcodes are supported (pages, posts, widgets, block editor shortcode block, etc.).

### Badge shortcode

Use `[sdc_review_badge]` to output the compact rating badge.

| Attribute       | Description                                                                 | Default (from settings) |
|-----------------|-----------------------------------------------------------------------------|-------------------------|
| `place_id`      | Override the configured Place ID for a single badge instance.               | Saved Place ID          |
| `api_key`       | Override the configured API key (use cautiously, the value is public).      | Saved API key           |
| `stars`         | Number of star icons to display (1–10).                                     | Saved star count        |
| `star_color`    | Hex colour used for the filled portion of the stars.                        | Saved star colour       |
| `accent_color`  | Hex colour used for the text and Google icon.                               | Saved accent colour     |
| `cache_minutes` | Minutes to cache the API response. Use `0` to bypass the cache temporarily. | Saved cache duration    |

**Examples**

```text
[sdc_review_badge]
[sdc_review_badge stars="5" star_color="#ff9900"]
[sdc_review_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY" cache_minutes="60"]
```

Legacy aliases `[sdc_gmb_review_badge]`, `[sdc_gmb_badge]`, and `[stoke_gbp_badge]` continue to work for existing embeds.

### Reviews carousel shortcode

Use `[sdc_review_carousel]` to display a responsive slider of recent Google reviews. The carousel auto-advances (unless disabled), shows hover/focus dot navigation, and announces visible ranges for assistive technology.

| Attribute              | Description                                                                                 | Default (from settings) |
|------------------------|---------------------------------------------------------------------------------------------|-------------------------|
| `place_id`             | Override the configured Place ID.                                                           | Saved Place ID          |
| `api_key`              | Override the configured API key.                                                            | Saved API key           |
| `cache_minutes`        | Minutes to cache the Places API response.                                                   | Saved cache duration    |
| `min_rating`           | Filter out reviews below this rating (0–5, decimal friendly).                               | Saved minimum rating    |
| `reviews_limit`        | Maximum number of reviews to render (1–20; Google returns up to 20 recent reviews when available). | Saved reviews limit     |
| `slides_desktop`       | Number of cards visible on desktop breakpoints (~960px and up).                              | Saved desktop slides    |
| `slides_tablet`        | Number of cards visible on tablet breakpoints (~600px and up).                               | Saved tablet slides     |
| `slides_mobile`        | Number of cards visible below 600px.                                                         | Saved mobile slides     |
| `autoplay_delay`       | Milliseconds to wait before advancing. Set to `0` to disable autoplay.                       | Saved autoplay delay    |
| `transition_duration`  | Milliseconds used for the scroll animation between slides.                                   | Saved transition speed  |
| `review_background_color` | Hex colour applied to the review card background.                                          | Saved review background colour |
| `review_text_color`       | Hex colour used for the review text.                                                        | Saved review text colour |
| `review_meta_color`       | Hex colour used for the reviewer name/date line.                                            | Saved review meta colour |
| `review_gap`              | Horizontal gap between review cards in pixels.                                              | Saved review gap         |
| `review_dot_color`        | Hex colour applied to the dot navigation (active, hover, focus).                            | Saved dot navigation colour |

**Examples**

```text
[sdc_review_carousel]
[sdc_review_carousel autoplay_delay="7000" transition_duration="400"]
[sdc_review_carousel slides_desktop="2" slides_tablet="2" slides_mobile="1" min_rating="4"]
```

Legacy alias `[sdc_gmb_reviews_carousel]` still maps to the updated carousel output.

## Elementor integration

1. Add the **Shortcode** widget to your Elementor layout.
2. Paste either `[sdc_review_badge]` or `[sdc_review_carousel]` into the widget content.
3. Adjust the widget width/padding as needed—responsive sizing is controlled via the shortcode attributes and plugin settings.
4. Publish or update the page and clear any caches so the Places API data can refresh.

## Styling tips

- Badge and carousel colours (background, text, meta, dots) and spacing are managed via the settings page or shortcode attributes.
- Target the `.sdc-review-carousel` wrapper or `.sdc-review-card` elements in your theme/custom CSS to tweak spacing, typography, or layout.
- Use `.sdc-review-carousel__track` (or the existing `.sdc-review-carousel__list`) when you need to control the ticker transform—both host the flex layout for the sliding cards.
- Dot navigation buttons use the `.sdc-review-carousel__dot` class; feel free to adjust size or colour to suit your brand.
- The badge uses inline SVG icons, so colours inherit from the accent colour you set.

## Caching & API usage

- Each shortcode request caches the Places API response in a WordPress transient. This prevents repeated API calls and helps you respect Google quota limits.
- Adjust the cache duration globally in the settings screen or per-shortcode using the `cache_minutes` attribute.
- Set `cache_minutes="0"` to skip caching for the next render (useful after receiving a new review).

## Troubleshooting

- **Reviews unavailable message** – confirm that the API key is valid, the Places API is enabled, and the Place ID is correct.
- **No stars displayed** – ensure the star colour is a valid hex value and that no aggressive caching plugin is stripping inline styles.
- **Carousel not advancing** – verify autoplay is enabled (delay above 0) and that the visitor hasn’t enabled “reduce motion” at the OS/browser level.

## Testing

- **Jest** – mock `requestAnimationFrame` to assert that the ticker autoplay pauses on `mouseenter`, resumes on `mouseleave`, and keeps `scrollLeft` within a single loop width after calling `reset()`.
- **Playwright** – render the shortcode, hover the carousel to confirm the ticker stops, then tab-focus a dot to resume and observe a seamless wrap from the final logical review back to the first without a visible jump.

## Support

This is an open-source project maintained by [Stoke Design Co](https://stokedesign.co). For customisations or support packages, please get in touch via our website.

## Change log

See [CHANGELOG.md](CHANGELOG.md) for a full history of updates.
