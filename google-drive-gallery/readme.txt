=== Google Drive Photo Gallery ===
Contributors: mgrandusky
Tags: google drive, gallery, photos, images, lightbox
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display beautiful photo galleries from your Google Drive folders with lightbox, slideshow, and responsive design.

== Description ==

Google Drive Photo Gallery is a comprehensive WordPress plugin that seamlessly integrates with Google Drive to create stunning photo galleries from your selected folders.

**Key Features:**

* **Multiple Authentication Options:** Support for both OAuth 2.0 and Service Account authentication
* **Beautiful Gallery Display:** Responsive grid layout with customizable columns and spacing
* **Lightbox View:** Click images to view in a beautiful, full-screen lightbox overlay
* **Slideshow Mode:** Optional auto-play slideshow functionality
* **Mobile Responsive:** Galleries look great on all devices
* **Folder Selection:** Easy folder selection via Google Drive folder ID
* **Subfolder Support:** Option to include images from subfolders
* **Image Filtering:** Automatically filters to show only image files (JPG, PNG, GIF, WebP)
* **Caching:** Built-in caching for improved performance
* **Shortcode Support:** Easy-to-use shortcode with multiple customization options
* **Gutenberg Block:** Custom block for the WordPress block editor
* **Caption Display:** Show image captions from Google Drive descriptions

**Authentication Methods:**

1. **OAuth 2.0:** Users can authenticate with their own Google accounts
2. **Service Account:** Admins can configure a service account for centralized API access

**Shortcode Usage:**

`[gdrive_gallery folder_id="YOUR_FOLDER_ID" columns="3" lightbox="true" slideshow="false"]`

**Shortcode Attributes:**

* `folder_id` (required) - Google Drive folder ID
* `columns` - Number of columns (1-6, default: 3)
* `spacing` - Gap between images in pixels (default: 10)
* `lightbox` - Enable lightbox (true/false, default: true)
* `slideshow` - Enable slideshow (true/false, default: false)
* `show_captions` - Display image captions (true/false, default: false)
* `include_subfolders` - Include subfolder images (true/false, default: false)
* `thumbnail_size` - Thumbnail size (small/medium/large, default: medium)
* `title` - Gallery title (optional)
* `custom_class` - Custom CSS class (optional)

**Example:**

`[gdrive_gallery folder_id="1a2b3c4d5e" columns="4" spacing="15" lightbox="true" slideshow="true" title="My Photo Gallery"]`

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/google-drive-gallery` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > Google Drive Gallery to configure the plugin.
4. Choose your authentication method (OAuth 2.0 or Service Account).
5. For OAuth 2.0:
   - Create a project in Google Cloud Console
   - Enable Google Drive API
   - Create OAuth 2.0 credentials
   - Enter Client ID and Client Secret in plugin settings
   - Click "Connect to Google Drive" to authorize
6. For Service Account:
   - Create a service account in Google Cloud Console
   - Generate and download JSON key file
   - Paste the JSON contents in plugin settings
   - Share your Google Drive folders with the service account email
7. Use the shortcode or Gutenberg block to add galleries to your pages/posts.

== Frequently Asked Questions ==

= How do I find my Google Drive folder ID? =

1. Open Google Drive and navigate to the folder you want to display
2. Look at the URL in your browser
3. The folder ID is the string after `/folders/`
4. Example: `https://drive.google.com/drive/folders/ABC123xyz` - the ID is `ABC123xyz`

= What authentication method should I use? =

* **OAuth 2.0:** Best for personal sites where you manage your own Google Drive
* **Service Account:** Better for client sites or when you want centralized management

= How do I share folders with a service account? =

1. Copy the service account email from your JSON key file (it looks like `name@project-id.iam.gserviceaccount.com`)
2. In Google Drive, right-click the folder and select "Share"
3. Paste the service account email and give it "Viewer" permissions

= Can I display multiple galleries on the same page? =

Yes! Each gallery is independent and can have different settings.

= How do I clear the cache? =

Go to Settings > Google Drive Gallery > Tools tab and click "Clear Cache"

= What image formats are supported? =

JPG, JPEG, PNG, GIF, and WebP images are automatically detected and displayed.

= Can I customize the gallery appearance? =

Yes! Use the `custom_class` attribute to add your own CSS class, then style it in your theme's CSS.

== Screenshots ==

1. Admin settings page - Authentication
2. Admin settings page - General settings
3. Gallery display with grid layout
4. Lightbox view
5. Gutenberg block settings

== Changelog ==

= 1.0.0 =
* Initial release
* OAuth 2.0 and Service Account authentication
* Responsive grid gallery layout
* Lightbox functionality
* Slideshow mode
* Shortcode and Gutenberg block support
* Caching system
* Mobile responsive design

== Upgrade Notice ==

= 1.0.0 =
Initial release of Google Drive Photo Gallery plugin.

== Additional Information ==

**Google API Setup:**

To use this plugin, you need to:

1. Create a project in [Google Cloud Console](https://console.cloud.google.com/)
2. Enable the Google Drive API
3. Create credentials (OAuth 2.0 or Service Account)
4. Configure the credentials in plugin settings

**Privacy:**

This plugin connects to Google Drive API to fetch images from your folders. No data is stored on external servers except for caching on your WordPress site.

**Support:**

For support and feature requests, please visit the plugin support forum.
