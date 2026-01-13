# Google Drive Photo Gallery WordPress Plugin

A comprehensive WordPress plugin that integrates with Google Drive to create beautiful photo galleries from selected folders.

## Features

### Authentication
- **OAuth 2.0**: Allow users to authenticate with their own Google accounts
- **Service Account**: Support admin-configured service account for centralized API access
- Automatic token refresh for OAuth
- Secure credential storage

### Gallery Display
- **Responsive Grid Layout**: 1-6 columns with customizable spacing
- **Lightbox View**: Full-screen image viewing with smooth transitions
- **Slideshow Mode**: Auto-play slideshow with manual controls
- **Mobile Responsive**: Looks great on all devices
- **Image Captions**: Display captions from Google Drive descriptions

### Performance
- Built-in caching system using WordPress transients
- Configurable cache duration (default: 1 hour)
- Lazy loading support for images
- Optimized API requests with pagination

### Easy Integration
- Simple shortcode: `[gdrive_gallery folder_id="..."]`
- Gutenberg block with visual editor
- Multiple galleries per page support
- 10+ customization options

## Installation

### 1. Install the Plugin
- Upload the `google-drive-gallery` folder to `/wp-content/plugins/`
- Activate the plugin through the WordPress admin
- Navigate to **Settings > Google Drive Gallery**

### 2. Configure Google API

#### Create a Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Google Drive API**

#### Option A: OAuth 2.0 Setup
1. In Google Cloud Console, go to **APIs & Services > Credentials**
2. Click **Create Credentials > OAuth client ID**
3. Choose **Web application**
4. Add authorized redirect URI: `https://your-site.com/wp-admin/admin.php?page=gdrive-gallery-settings&oauth_callback=1`
5. Copy the **Client ID** and **Client Secret**
6. In WordPress, go to **Settings > Google Drive Gallery > Authentication**
7. Select **OAuth 2.0** as authentication type
8. Enter your Client ID and Client Secret
9. Click **Save Changes**
10. Click **Connect to Google Drive** to authorize

#### Option B: Service Account Setup
1. In Google Cloud Console, go to **IAM & Admin > Service Accounts**
2. Click **Create Service Account**
3. Give it a name and click **Create**
4. Skip role assignment (click **Continue**)
5. Click **Create Key** and choose **JSON**
6. Download the JSON key file
7. In WordPress, go to **Settings > Google Drive Gallery > Authentication**
8. Select **Service Account** as authentication type
9. Open the JSON file and copy its entire contents
10. Paste into the **Service Account JSON** field
11. Click **Save Changes**
12. **Important**: Share your Google Drive folders with the service account email (found in the JSON file, looks like `name@project.iam.gserviceaccount.com`)

### 3. Test Connection
1. Go to **Settings > Google Drive Gallery > Tools**
2. Click **Test Connection** to verify everything is working

## Usage

### Shortcode

Basic usage:
```
[gdrive_gallery folder_id="YOUR_FOLDER_ID"]
```

With all options:
```
[gdrive_gallery 
    folder_id="1a2b3c4d5e" 
    columns="4" 
    spacing="15" 
    lightbox="true" 
    slideshow="true" 
    show_captions="true" 
    include_subfolders="false" 
    thumbnail_size="medium" 
    title="My Photo Gallery" 
    custom_class="my-gallery"
]
```

### Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `folder_id` | string | (required) | Google Drive folder ID |
| `columns` | number | 3 | Number of columns (1-6) |
| `spacing` | number | 10 | Gap between images in pixels |
| `lightbox` | boolean | true | Enable lightbox overlay |
| `slideshow` | boolean | false | Enable slideshow mode |
| `show_captions` | boolean | false | Display image captions |
| `include_subfolders` | boolean | false | Include images from subfolders |
| `thumbnail_size` | string | medium | Thumbnail size (small/medium/large) |
| `title` | string | - | Gallery title |
| `custom_class` | string | - | Custom CSS class |

### Finding Your Folder ID

1. Open Google Drive and navigate to your folder
2. Look at the URL in your browser
3. The folder ID is after `/folders/`
4. Example: `https://drive.google.com/drive/folders/ABC123xyz`
5. Your folder ID is: `ABC123xyz`

### Gutenberg Block

1. In the block editor, click the **+** button
2. Search for **Google Drive Gallery**
3. Click to add the block
4. Configure settings in the right sidebar
5. Enter your folder ID
6. Customize display options

## File Structure

```
google-drive-gallery/
├── google-drive-gallery.php       # Main plugin file
├── readme.txt                     # WordPress.org readme
├── includes/
│   ├── class-gdrive-auth.php      # Authentication handler
│   ├── class-gdrive-api.php       # Google Drive API wrapper
│   ├── class-gdrive-gallery.php   # Gallery renderer
│   ├── class-gdrive-cache.php     # Cache handler
│   └── class-gdrive-admin.php     # Admin interface
├── admin/
│   ├── settings-page.php          # Settings page template
│   ├── css/admin-styles.css       # Admin styles
│   └── js/admin-scripts.js        # Admin scripts
├── public/
│   ├── css/gallery-styles.css     # Frontend gallery styles
│   └── js/gallery-scripts.js      # Lightbox & slideshow JS
└── blocks/
    └── gdrive-gallery-block/
        ├── block.js               # Gutenberg block
        └── editor.css             # Block editor styles
```

## API Integration

### Google Drive API v3
- Uses Google Drive API v3 for all operations
- Fetches file metadata and thumbnails
- Supports pagination for large folders
- Filters for image files only (JPG, PNG, GIF, WebP)

### Caching
- Caches folder contents to reduce API calls
- Default cache duration: 1 hour (configurable)
- Manual cache clearing available in admin
- Cache statistics displayed in Tools tab

### Security
- All inputs sanitized and validated
- WordPress nonces for admin actions
- Secure credential storage in WordPress options
- No data sent to external servers (except Google API)

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Active Google Cloud project with Drive API enabled
- OAuth 2.0 credentials OR Service Account credentials

## Supported Image Formats

- JPEG / JPG
- PNG
- GIF
- WebP

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### "Authentication not configured"
- Verify you've entered OAuth credentials or Service Account JSON
- Click "Test Connection" to diagnose the issue

### "No images found in folder"
- Check the folder ID is correct
- Ensure the folder contains image files
- For Service Account: verify the folder is shared with the service account email

### "API request failed"
- Verify Google Drive API is enabled in Google Cloud Console
- Check API quotas haven't been exceeded
- Try clearing the cache

### Images not loading
- Check browser console for errors
- Verify images are accessible in Google Drive
- Try regenerating thumbnails by clearing cache

## Performance Tips

1. **Use appropriate thumbnail sizes**: Smaller thumbnails load faster
2. **Enable caching**: Reduces API calls significantly
3. **Limit folder size**: Large folders may take longer to load initially
4. **Use pagination**: Consider splitting large galleries across multiple pages
5. **Optimize cache duration**: Balance freshness vs. performance

## Privacy

This plugin:
- Connects to Google Drive API to fetch images
- Stores credentials in WordPress options table
- Caches image data in WordPress transients
- Does not send data to any third-party services (except Google)
- Does not track users

## Support

For issues, questions, or feature requests:
- Check the FAQ in readme.txt
- Review the troubleshooting section above
- Test your connection in Settings > Tools
- Check browser console for JavaScript errors

## License

GPL v2 or later - see LICENSE file for details

## Credits

Developed by Mason Grandusky

## Changelog

### 1.0.0 (2026-01-13)
- Initial release
- OAuth 2.0 and Service Account authentication
- Responsive gallery grid layout
- Lightbox with keyboard navigation
- Slideshow mode with controls
- Shortcode and Gutenberg block support
- Caching system with management tools
- Mobile responsive design
- WordPress 5.8+ and PHP 7.4+ compatible
