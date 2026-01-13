/**
 * Google Drive Gallery Gutenberg Block
 */

(function(blocks, element, components, blockEditor, i18n) {
    const el = element.createElement;
    const { TextControl, ToggleControl, RangeControl, SelectControl, PanelBody } = components;
    const { InspectorControls } = blockEditor;
    const { __ } = i18n;

    blocks.registerBlockType('gdrive-gallery/gallery', {
        title: __('Google Drive Gallery', 'google-drive-gallery'),
        icon: 'format-gallery',
        category: 'media',
        attributes: {
            folderId: {
                type: 'string',
                default: ''
            },
            columns: {
                type: 'number',
                default: 3
            },
            spacing: {
                type: 'number',
                default: 10
            },
            lightbox: {
                type: 'boolean',
                default: true
            },
            slideshow: {
                type: 'boolean',
                default: false
            },
            showCaptions: {
                type: 'boolean',
                default: false
            },
            includeSubfolders: {
                type: 'boolean',
                default: false
            },
            thumbnailSize: {
                type: 'string',
                default: 'medium'
            },
            title: {
                type: 'string',
                default: ''
            },
            customClass: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                folderId,
                columns,
                spacing,
                lightbox,
                slideshow,
                showCaptions,
                includeSubfolders,
                thumbnailSize,
                title,
                customClass
            } = attributes;

            return el('div', { className: 'gdrive-gallery-block-editor' }, [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Gallery Settings', 'google-drive-gallery'), initialOpen: true },
                        el(TextControl, {
                            label: __('Folder ID', 'google-drive-gallery'),
                            value: folderId,
                            onChange: function(value) {
                                setAttributes({ folderId: value });
                            },
                            help: __('Enter the Google Drive folder ID', 'google-drive-gallery')
                        }),
                        el(TextControl, {
                            label: __('Gallery Title', 'google-drive-gallery'),
                            value: title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Columns', 'google-drive-gallery'),
                            value: columns,
                            onChange: function(value) {
                                setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 6
                        }),
                        el(RangeControl, {
                            label: __('Spacing (px)', 'google-drive-gallery'),
                            value: spacing,
                            onChange: function(value) {
                                setAttributes({ spacing: value });
                            },
                            min: 0,
                            max: 50
                        }),
                        el(SelectControl, {
                            label: __('Thumbnail Size', 'google-drive-gallery'),
                            value: thumbnailSize,
                            options: [
                                { label: __('Small', 'google-drive-gallery'), value: 'small' },
                                { label: __('Medium', 'google-drive-gallery'), value: 'medium' },
                                { label: __('Large', 'google-drive-gallery'), value: 'large' }
                            ],
                            onChange: function(value) {
                                setAttributes({ thumbnailSize: value });
                            }
                        })
                    ),
                    el(PanelBody, { title: __('Display Options', 'google-drive-gallery'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Enable Lightbox', 'google-drive-gallery'),
                            checked: lightbox,
                            onChange: function(value) {
                                setAttributes({ lightbox: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Enable Slideshow', 'google-drive-gallery'),
                            checked: slideshow,
                            onChange: function(value) {
                                setAttributes({ slideshow: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Captions', 'google-drive-gallery'),
                            checked: showCaptions,
                            onChange: function(value) {
                                setAttributes({ showCaptions: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Include Subfolders', 'google-drive-gallery'),
                            checked: includeSubfolders,
                            onChange: function(value) {
                                setAttributes({ includeSubfolders: value });
                            }
                        })
                    ),
                    el(PanelBody, { title: __('Advanced', 'google-drive-gallery'), initialOpen: false },
                        el(TextControl, {
                            label: __('Custom CSS Class', 'google-drive-gallery'),
                            value: customClass,
                            onChange: function(value) {
                                setAttributes({ customClass: value });
                            }
                        })
                    )
                ),

                // Block preview
                el('div', { className: 'gdrive-gallery-block-preview' }, [
                    el('div', { className: 'gdrive-gallery-icon' },
                        el('span', { className: 'dashicons dashicons-format-gallery', style: { fontSize: '48px', color: '#666' } })
                    ),
                    el('h3', {}, __('Google Drive Gallery', 'google-drive-gallery')),
                    folderId ? 
                        el('p', {}, __('Folder ID:', 'google-drive-gallery') + ' ' + folderId) :
                        el('p', { style: { color: '#d63638' } }, __('Please enter a folder ID in the block settings.', 'google-drive-gallery')),
                    title && el('p', {}, __('Title:', 'google-drive-gallery') + ' ' + title),
                    el('p', {}, 
                        __('Columns:', 'google-drive-gallery') + ' ' + columns + ', ' +
                        __('Spacing:', 'google-drive-gallery') + ' ' + spacing + 'px'
                    ),
                    el('p', { style: { fontSize: '12px', color: '#666' } },
                        (lightbox ? '✓ ' + __('Lightbox', 'google-drive-gallery') + ' ' : '') +
                        (slideshow ? '✓ ' + __('Slideshow', 'google-drive-gallery') + ' ' : '') +
                        (showCaptions ? '✓ ' + __('Captions', 'google-drive-gallery') : '')
                    )
                ])
            ]);
        },

        save: function() {
            // Rendered server-side
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor,
    window.wp.i18n
);
