<?php
/**
 * Google Drive Gallery Renderer
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GDrive_Gallery
 * Handles gallery rendering
 */
class GDrive_Gallery {

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Gallery HTML
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'folder_id' => '',
            'columns' => 3,
            'spacing' => 10,
            'lightbox' => 'true',
            'slideshow' => 'false',
            'show_captions' => 'false',
            'include_subfolders' => 'false',
            'thumbnail_size' => 'medium',
            'title' => '',
            'custom_class' => '',
            'display_mode' => 'auto',
        ], $atts, 'gdrive_gallery' );

        // Convert string booleans to actual booleans
        $atts['lightbox'] = filter_var( $atts['lightbox'], FILTER_VALIDATE_BOOLEAN );
        $atts['slideshow'] = filter_var( $atts['slideshow'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_captions'] = filter_var( $atts['show_captions'], FILTER_VALIDATE_BOOLEAN );
        $atts['include_subfolders'] = filter_var( $atts['include_subfolders'], FILTER_VALIDATE_BOOLEAN );

        return $this->render_gallery( $atts );
    }

    /**
     * Render block
     *
     * @param array $attributes Block attributes
     * @return string Gallery HTML
     */
    public function render_block( $attributes ) {
        $atts = [
            'folder_id' => $attributes['folderId'] ?? '',
            'columns' => $attributes['columns'] ?? 3,
            'spacing' => $attributes['spacing'] ?? 10,
            'lightbox' => $attributes['lightbox'] ?? true,
            'slideshow' => $attributes['slideshow'] ?? false,
            'show_captions' => $attributes['showCaptions'] ?? false,
            'include_subfolders' => $attributes['includeSubfolders'] ?? false,
            'thumbnail_size' => $attributes['thumbnailSize'] ?? 'medium',
            'title' => $attributes['title'] ?? '',
            'custom_class' => $attributes['customClass'] ?? '',
        ];

        return $this->render_gallery( $atts );
    }

    /**
     * Render gallery HTML
     *
     * @param array $atts Gallery attributes
     * @return string Gallery HTML
     */
    private function render_gallery( $atts ) {
        // Validate folder ID
        if ( empty( $atts['folder_id'] ) ) {
            return $this->render_error( __( 'No folder ID specified', 'google-drive-gallery' ) );
        }

        // Check authentication
        if ( ! GDrive_Auth::is_authenticated() ) {
            return $this->render_error( __( 'Google Drive authentication not configured. Please configure in plugin settings.', 'google-drive-gallery' ) );
        }

        $display_mode = sanitize_text_field( $atts['display_mode'] );
        
        // Auto-detect: Check if folder has subfolders
        if ( 'auto' === $display_mode ) {
            $subfolders = GDrive_API::get_subfolders_with_preview( $atts['folder_id'] );
            
            if ( ! is_wp_error( $subfolders ) && ! empty( $subfolders ) ) {
                // Has subfolders, use folder view
                return $this->render_folder_gallery( $atts, $subfolders );
            }
            // No subfolders, fall through to regular image gallery
        } elseif ( 'folders' === $display_mode ) {
            // Force folder view
            $subfolders = GDrive_API::get_subfolders_with_preview( $atts['folder_id'] );
            
            if ( is_wp_error( $subfolders ) ) {
                return $this->render_error( $subfolders->get_error_message() );
            }
            
            if ( empty( $subfolders ) ) {
                return $this->render_error( __( 'No subfolders found in this folder', 'google-drive-gallery' ) );
            }
            
            return $this->render_folder_gallery( $atts, $subfolders );
        }
        // 'flat' mode or auto with no subfolders - use regular gallery

        // Get files from Google Drive
        $files = GDrive_API::get_folder_files( $atts['folder_id'], $atts['include_subfolders'] );

        if ( is_wp_error( $files ) ) {
            return $this->render_error( $files->get_error_message() );
        }

        if ( empty( $files ) ) {
            return $this->render_error( __( 'No images found in the specified folder', 'google-drive-gallery' ) );
        }

        // Generate unique gallery ID
        $gallery_id = 'gdrive-gallery-' . uniqid();

        // Build gallery HTML
        $html = $this->build_gallery_html( $gallery_id, $files, $atts );

        return $html;
    }

    /**
     * Build gallery HTML
     *
     * @param string $gallery_id Unique gallery ID
     * @param array $files Array of file data
     * @param array $atts Gallery attributes
     * @return string Gallery HTML
     */
    private function build_gallery_html( $gallery_id, $files, $atts ) {
        $columns = absint( $atts['columns'] );
        $spacing = absint( $atts['spacing'] );
        $custom_class = sanitize_html_class( $atts['custom_class'] );

        $classes = [ 'gdrive-gallery' ];
        if ( $custom_class ) {
            $classes[] = $custom_class;
        }
        if ( $atts['lightbox'] ) {
            $classes[] = 'has-lightbox';
        }
        if ( $atts['slideshow'] ) {
            $classes[] = 'has-slideshow';
        }

        $html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . esc_attr( $gallery_id ) . '" data-columns="' . esc_attr( $columns ) . '" data-spacing="' . esc_attr( $spacing ) . '" data-lightbox="' . esc_attr( $atts['lightbox'] ? '1' : '0' ) . '" data-slideshow="' . esc_attr( $atts['slideshow'] ? '1' : '0' ) . '">';

        // Gallery title
        if ( ! empty( $atts['title'] ) ) {
            $html .= '<h2 class="gdrive-gallery-title">' . esc_html( $atts['title'] ) . '</h2>';
        }

        // Gallery grid
        $html .= '<div class="gdrive-gallery-grid" style="grid-template-columns: repeat(' . esc_attr( $columns ) . ', 1fr); gap: ' . esc_attr( $spacing ) . 'px;">';

        foreach ( $files as $index => $file ) {
            $html .= $this->render_gallery_item( $file, $index, $atts );
        }

        $html .= '</div>'; // .gdrive-gallery-grid

        // Slideshow controls
        if ( $atts['slideshow'] ) {
            $html .= $this->render_slideshow_controls();
        }

        $html .= '</div>'; // .gdrive-gallery

        // Lightbox modal
        if ( $atts['lightbox'] ) {
            $html .= $this->render_lightbox_modal( $gallery_id );
        }

        return $html;
    }

    /**
     * Render a single gallery item
     *
     * @param array $file File data
     * @param int $index Item index
     * @param array $atts Gallery attributes
     * @return string Item HTML
     */
    private function render_gallery_item( $file, $index, $atts ) {
        $thumbnail_url = GDrive_API::get_thumbnail_url( $file, $atts['thumbnail_size'] );
        $full_url = GDrive_API::get_image_url( $file );
        $name = esc_html( $file['name'] ?? 'Untitled' );
        $description = isset( $file['description'] ) ? esc_html( $file['description'] ) : '';

        $html = '<div class="gdrive-gallery-item" data-index="' . esc_attr( $index ) . '">';
        
        if ( $atts['lightbox'] ) {
            $html .= '<a href="' . esc_url( $full_url ) . '" class="gdrive-gallery-link" data-caption="' . esc_attr( $description ) . '" data-title="' . esc_attr( $name ) . '">';
        }

        $html .= '<img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $name ) . '" class="gdrive-gallery-image" loading="lazy" />';

        if ( $atts['lightbox'] ) {
            $html .= '</a>';
        }

        if ( $atts['show_captions'] && ! empty( $description ) ) {
            $html .= '<div class="gdrive-gallery-caption">' . esc_html( $description ) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render slideshow controls
     *
     * @return string Controls HTML
     */
    private function render_slideshow_controls() {
        $html = '<div class="gdrive-slideshow-controls">';
        $html .= '<button class="gdrive-slideshow-prev" aria-label="' . esc_attr__( 'Previous', 'google-drive-gallery' ) . '">&#10094;</button>';
        $html .= '<button class="gdrive-slideshow-play" aria-label="' . esc_attr__( 'Play/Pause', 'google-drive-gallery' ) . '">&#9658;</button>';
        $html .= '<button class="gdrive-slideshow-next" aria-label="' . esc_attr__( 'Next', 'google-drive-gallery' ) . '">&#10095;</button>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render lightbox modal
     *
     * @param string $gallery_id Gallery ID
     * @return string Modal HTML
     */
    private function render_lightbox_modal( $gallery_id ) {
        $html = '<div class="gdrive-lightbox" id="lightbox-' . esc_attr( $gallery_id ) . '" style="display: none;">';
        $html .= '<div class="gdrive-lightbox-overlay"></div>';
        $html .= '<div class="gdrive-lightbox-content">';
        $html .= '<button class="gdrive-lightbox-close" aria-label="' . esc_attr__( 'Close', 'google-drive-gallery' ) . '">&times;</button>';
        $html .= '<button class="gdrive-lightbox-prev" aria-label="' . esc_attr__( 'Previous', 'google-drive-gallery' ) . '">&#10094;</button>';
        $html .= '<button class="gdrive-lightbox-next" aria-label="' . esc_attr__( 'Next', 'google-drive-gallery' ) . '">&#10095;</button>';
        $html .= '<img src="" alt="" class="gdrive-lightbox-image" />';
        $html .= '<div class="gdrive-lightbox-caption"></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error( $message ) {
        return '<div class="gdrive-gallery-error"><p>' . esc_html( $message ) . '</p></div>';
    }

    /**
     * Render folder card gallery
     *
     * @param array $atts Gallery attributes
     * @param array $subfolders Array of subfolder data
     * @return string Gallery HTML
     */
    private function render_folder_gallery( $atts, $subfolders ) {
        $gallery_id = 'gdrive-folder-gallery-' . uniqid();
        $columns = absint( $atts['columns'] );
        $spacing = absint( $atts['spacing'] );
        $custom_class = sanitize_html_class( $atts['custom_class'] );

        $classes = [ 'gdrive-gallery', 'gdrive-folder-gallery' ];
        if ( $custom_class ) {
            $classes[] = $custom_class;
        }

        $html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . esc_attr( $gallery_id ) . '" data-columns="' . esc_attr( $columns ) . '" data-spacing="' . esc_attr( $spacing ) . '">';

        // Add title if specified
        if ( ! empty( $atts['title'] ) ) {
            $html .= '<h2 class="gdrive-gallery-title">' . esc_html( $atts['title'] ) . '</h2>';
        }

        $html .= '<div class="gdrive-folder-grid" style="display: grid; grid-template-columns: repeat(' . esc_attr( $columns ) . ', 1fr); gap: ' . esc_attr( $spacing ) . 'px;">';

        foreach ( $subfolders as $folder ) {
            $thumbnail_url = GDrive_API::get_thumbnail_url( $folder['preview_image'], $atts['thumbnail_size'] );
            $folder_name = $folder['name'];
            $image_count = absint( $folder['image_count'] );
            $images_json = wp_json_encode( $folder['images'] );
            
            $html .= '<div class="gdrive-folder-card" data-folder-id="' . esc_attr( $folder['id'] ) . '" data-folder-name="' . esc_attr( $folder_name ) . '" data-images="' . esc_attr( $images_json ) . '">';
            $html .= '<div class="gdrive-folder-thumbnail" style="position: relative; overflow: hidden; cursor: pointer; border-radius: 8px;">';
            $html .= '<img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $folder_name ) . '" loading="lazy" style="width: 100%; height: 200px; object-fit: cover;" />';
            $html .= '<div class="gdrive-folder-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 15px;">';
            $html .= '<div class="gdrive-folder-icon" style="font-size: 24px; margin-bottom: 5px;" aria-hidden="true">📁</div>';
            $html .= '<h3 class="gdrive-folder-name" style="margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">' . esc_html( $folder_name ) . '</h3>';
            $html .= '<p class="gdrive-folder-count" style="margin: 0; font-size: 14px; opacity: 0.9;">' . sprintf( _n( '%d photo', '%d photos', $image_count, 'google-drive-gallery' ), $image_count ) . '</p>';
            $html .= '</div>'; // overlay
            $html .= '</div>'; // thumbnail
            $html .= '</div>'; // card
        }

        $html .= '</div>'; // grid
        
        // Add lightbox container
        if ( $atts['lightbox'] ) {
            $html .= '<div id="' . esc_attr( $gallery_id ) . '-lightbox" class="gdrive-lightbox" style="display:none;"></div>';
        }
        
        $html .= '</div>'; // gallery

        return $html;
    }
}
