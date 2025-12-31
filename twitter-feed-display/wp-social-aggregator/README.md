# WP Social Aggregator

Small plugin to aggregate Twitter, Instagram, Facebook and LinkedIn sources into a single shortcode.

Installation
- Copy the `wp-social-aggregator` folder into your site's `wp-content/plugins/`
- Activate the plugin in WordPress admin
- Go to Settings → Social Aggregator and add sources or paste embed HTML

Usage
- Shortcode: `[social_aggregator]`
- Shortcode supports attributes: `limit` and `cache_minutes` e.g. `[social_aggregator limit="6" cache_minutes="5"]`

Config notes
- For each network you can provide comma-separated source URLs or handles.
- You can also paste full widget/embed HTML into the Embed field for richer output.
- The plugin attempts `wp_oembed_get()` for URLs — if the provider supports oEmbed it will render.
- For unauthenticated API access (Facebook, Instagram, LinkedIn, Twitter) you may need to paste embed HTML or configure API keys (fields exist but provider-specific API code is not implemented).

Extending
- Implement provider-specific API fetches in `includes/providers.php`.
- Public output is produced by the shortcode in `includes/public.php`.
