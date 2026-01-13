# Google Drive Gallery - Usage Examples

## Quick Start Examples

### Example 1: Basic Gallery
Display a simple 3-column gallery from a Google Drive folder:

```
[gdrive_gallery folder_id="1a2b3c4d5e6f7g8h9i"]
```

### Example 2: 4-Column Gallery with Title
```
[gdrive_gallery 
    folder_id="1a2b3c4d5e6f7g8h9i" 
    columns="4" 
    title="My Photo Collection"
]
```

### Example 3: Full-Featured Gallery
```
[gdrive_gallery 
    folder_id="1a2b3c4d5e6f7g8h9i" 
    columns="4" 
    spacing="20" 
    lightbox="true" 
    slideshow="true" 
    show_captions="true" 
    thumbnail_size="large" 
    title="Family Vacation 2024"
]
```

### Example 4: Include Subfolders
Display all images from a folder and its subfolders:

```
[gdrive_gallery 
    folder_id="1a2b3c4d5e6f7g8h9i" 
    include_subfolders="true" 
    columns="3"
]
```

### Example 5: Mobile-Friendly Single Column
```
[gdrive_gallery 
    folder_id="1a2b3c4d5e6f7g8h9i" 
    columns="1" 
    spacing="5" 
    thumbnail_size="large"
]
```

### Example 6: Custom Styled Gallery
```
[gdrive_gallery 
    folder_id="1a2b3c4d5e6f7g8h9i" 
    columns="3" 
    custom_class="my-custom-gallery"
]
```

Then add custom CSS in your theme:
```css
.my-custom-gallery .gdrive-gallery-item {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.my-custom-gallery .gdrive-gallery-image {
    filter: grayscale(50%);
    transition: filter 0.3s;
}

.my-custom-gallery .gdrive-gallery-image:hover {
    filter: grayscale(0%);
}
```

## Use Cases

### Photography Portfolio
```
[gdrive_gallery 
    folder_id="YOUR_PORTFOLIO_FOLDER_ID" 
    columns="3" 
    spacing="15" 
    lightbox="true" 
    show_captions="true" 
    thumbnail_size="large" 
    title="Photography Portfolio"
]
```

### Event Gallery
```
[gdrive_gallery 
    folder_id="YOUR_EVENT_FOLDER_ID" 
    columns="4" 
    spacing="10" 
    lightbox="true" 
    slideshow="true" 
    title="Company Event 2024"
]
```

### Product Showcase
```
[gdrive_gallery 
    folder_id="YOUR_PRODUCTS_FOLDER_ID" 
    columns="5" 
    spacing="20" 
    lightbox="true" 
    show_captions="true" 
    thumbnail_size="medium"
]
```

### Multiple Galleries on One Page
```html
<h2>Indoor Photos</h2>
[gdrive_gallery folder_id="INDOOR_FOLDER_ID" columns="3"]

<h2>Outdoor Photos</h2>
[gdrive_gallery folder_id="OUTDOOR_FOLDER_ID" columns="4"]

<h2>Details</h2>
[gdrive_gallery folder_id="DETAILS_FOLDER_ID" columns="2"]
```

## PHP Usage (for theme developers)

### Display Gallery Programmatically
```php
<?php
// In your theme template
echo do_shortcode('[gdrive_gallery folder_id="1a2b3c4d5e6f7g8h9i" columns="3"]');
?>
```

### Dynamic Folder ID from Custom Field
```php
<?php
$folder_id = get_post_meta(get_the_ID(), 'gdrive_folder_id', true);
if ($folder_id) {
    echo do_shortcode('[gdrive_gallery folder_id="' . esc_attr($folder_id) . '" columns="4"]');
}
?>
```

### Gallery with Custom Attributes from Theme Options
```php
<?php
$gallery_settings = [
    'folder_id' => get_option('my_theme_gallery_folder'),
    'columns' => get_option('my_theme_gallery_columns', 3),
    'lightbox' => 'true',
];

$shortcode = sprintf(
    '[gdrive_gallery folder_id="%s" columns="%d" lightbox="%s"]',
    esc_attr($gallery_settings['folder_id']),
    absint($gallery_settings['columns']),
    $gallery_settings['lightbox']
);

echo do_shortcode($shortcode);
?>
```

## Gutenberg Block Usage

1. **Add the Block:**
   - Click the (+) button in the editor
   - Search for "Google Drive Gallery"
   - Click to add

2. **Configure Settings:**
   - In the right sidebar, enter your Folder ID
   - Adjust columns, spacing, and other options
   - Enable/disable lightbox and slideshow
   - Add a gallery title if desired

3. **Preview:**
   - The block shows a preview in the editor
   - Save and preview the page to see the full gallery

## Finding Your Folder ID

### Method 1: From Google Drive URL
1. Open Google Drive in your browser
2. Navigate to the folder you want to use
3. Look at the URL: `https://drive.google.com/drive/folders/1a2b3c4d5e6f7g8h9i`
4. The Folder ID is: `1a2b3c4d5e6f7g8h9i`

### Method 2: From Share Link
1. Right-click the folder in Google Drive
2. Click "Get link" or "Share"
3. Copy the link
4. Extract the ID from the link

## Common Settings Combinations

### Minimal Gallery
```
[gdrive_gallery folder_id="YOUR_ID"]
```
- Uses all default settings
- 3 columns
- Lightbox enabled
- No slideshow

### Photo Blog
```
[gdrive_gallery folder_id="YOUR_ID" columns="2" spacing="20" show_captions="true"]
```
- 2 columns for focus on images
- Extra spacing
- Captions visible

### Slideshow Presentation
```
[gdrive_gallery folder_id="YOUR_ID" columns="1" slideshow="true" spacing="0"]
```
- Single column
- Slideshow enabled
- No spacing for full-width

### Grid Gallery
```
[gdrive_gallery folder_id="YOUR_ID" columns="6" spacing="5" thumbnail_size="small"]
```
- 6 columns for compact view
- Minimal spacing
- Small thumbnails

## Tips for Best Results

1. **Folder Organization:**
   - Keep related images in the same folder
   - Use descriptive folder names
   - Set image descriptions in Google Drive for captions

2. **Image Optimization:**
   - Upload high-quality images to Google Drive
   - Let the plugin handle thumbnail generation
   - Use appropriate thumbnail size for your layout

3. **Performance:**
   - Enable caching in plugin settings (default: 1 hour)
   - Use smaller thumbnail sizes for galleries with many images
   - Consider pagination for very large galleries (split into multiple folders)

4. **Mobile Responsiveness:**
   - Test on mobile devices
   - Plugin automatically adjusts columns on small screens
   - Use 2-3 columns for best mobile experience

5. **Accessibility:**
   - Add captions to images in Google Drive
   - Use descriptive file names
   - Test keyboard navigation in lightbox

## Troubleshooting Common Issues

### Images Not Showing
- Verify folder ID is correct
- Check folder permissions (OAuth) or sharing (Service Account)
- Test connection in Settings > Tools
- Clear cache and try again

### Lightbox Not Opening
- Check browser console for JavaScript errors
- Ensure jQuery is loaded
- Verify plugin assets are enqueued

### Slow Loading
- Reduce cache duration in settings
- Use smaller thumbnail sizes
- Check Google API quotas
- Consider splitting large galleries

### Layout Issues
- Verify theme CSS isn't conflicting
- Add `custom_class` and override styles
- Check responsive breakpoints
- Test with default WordPress theme

## Advanced Customization

### Custom CSS Examples

#### Rounded Corners
```css
.gdrive-gallery-item {
    border-radius: 15px;
    overflow: hidden;
}
```

#### Hover Effects
```css
.gdrive-gallery-image {
    transition: transform 0.3s ease;
}

.gdrive-gallery-item:hover .gdrive-gallery-image {
    transform: scale(1.05);
}
```

#### Custom Lightbox Styling
```css
.gdrive-lightbox-overlay {
    background: rgba(0, 0, 0, 0.95);
}

.gdrive-lightbox-close {
    background: #ff4444;
    border-radius: 50%;
}
```

#### Caption Styling
```css
.gdrive-gallery-caption {
    font-style: italic;
    color: #333;
    font-size: 13px;
    padding: 15px;
}
```

## Support Resources

- Plugin settings: **Settings > Google Drive Gallery**
- Test connection: **Settings > Google Drive Gallery > Tools**
- Clear cache: **Settings > Google Drive Gallery > Tools**
- Usage guide: **Settings > Google Drive Gallery > Usage**
- Documentation: See README.md in plugin folder
