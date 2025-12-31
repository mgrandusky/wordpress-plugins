Testing the YouTube Feed Display plugin (Quick Start)

This file explains how to spin up a disposable local WordPress instance using Docker Compose and verify the plugin works with the lightbox.

Prerequisites
- Docker and Docker Compose installed on your machine
- Port 8000 available

Steps

1) Start services

```bash
cd /Users/masongrandusky/Downloads/youtube-feed-display
docker-compose up -d
```

2) Open WordPress in your browser and complete the one-time install

- Visit: http://localhost:8000
- Complete the WordPress setup (site title, admin user, password).

3) Activate the plugin

- Login to the admin: http://localhost:8000/wp-admin
- Go to Plugins → Installed Plugins
- You should see "YouTube Feed Display" (the plugin folder is mounted automatically). Click "Activate".

4) Configure API key and Channel ID

- Go to YouTube Feed → YouTube Feed Settings
- Enter your YouTube Data API v3 key and your channel ID. Save settings.

5) Create a test page with the shortcode

- WP Admin → Pages → Add New
- Title: YFD Test
- Content: [youtube_feed]
- Publish

6) Visit the published page (e.g., http://localhost:8000/?p=123 or the page permalink)

- Click a thumbnail: it should open in the lightbox and autoplay.
- Click the title: on desktop it navigates to YouTube unless you changed the Lightbox settings; on mobile or under the configured breakpoint it opens in the lightbox.
- Try a title or thumbnail link with a start time (e.g., add `?t=1m30s` to the link) to verify start-time support.

Optional: Use WP-CLI (if you have it) to automate installation and create the test page

```bash
# Example (run after containers are up). Replace credentials where appropriate.
# Install WordPress
wp core install --url="http://localhost:8000" --title="YFD Test Site" --admin_user="admin" --admin_password="password" --admin_email="admin@example.com"
# Activate plugin
wp plugin activate youtube-feed-display
# Configure options (replace values)
wp option update youtube_api_key "YOUR_API_KEY"
wp option update youtube_channel_id "YOUR_CHANNEL_ID"
# Create test page
wp post create --post_type=page --post_status=publish --post_title='YFD Test' --post_content='[youtube_feed]'
```

Cleaning up

```bash
docker-compose down -v
```

Notes
- This compose file mounts this plugin directory into the container as a plugin so any edits you make locally will be visible inside the container.
- If the plugin doesn't appear in the Plugins list, ensure the folder name is `youtube-feed-display` and you started Compose from this folder.

If you want, I can add an automated `setup` script that runs the WP-CLI commands inside a temporary WP-CLI container once you confirm you want that automated path.
