( function( blocks, element, components, editor, serverSideRender ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls || editor.BlockControls;

    registerBlockType( 'youtube-feed/display', {
        title: 'YouTube Feed & Playlists',
        icon: 'video-alt3',
        category: 'widgets',
        keywords: [ 'youtube', 'video', 'playlist', 'feed' ],
        description: 'Display YouTube videos or playlists on your page',
        attributes: {
            type: { type: 'string', default: 'videos' },
            videoCount: { type: 'number', default: 12 },
            columns: { type: 'number', default: 3 },
            channelId: { type: 'string', default: ( typeof window !== 'undefined' && window.yfdBlockConfig && window.yfdBlockConfig.defaultChannelId ) ? window.yfdBlockConfig.defaultChannelId : '' },
            playlistId: { type: 'string', default: '' }
        },

        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return el( 'div', { className: 'youtube-feed-block' },
                el( InspectorControls, {},
                    el( components.PanelBody, { title: 'Content Type', initialOpen: true },
                        el( components.SelectControl, {
                            label: 'Display',
                            value: attributes.type,
                            options: [
                                { label: 'Videos', value: 'videos' },
                                { label: 'Playlists', value: 'playlists' }
                            ],
                            onChange: function( val ) { setAttributes( { type: val } ); }
                        } )
                    ),
                    el( components.PanelBody, { title: 'Layout Settings' },
                        el( components.SelectControl, {
                            label: 'Columns',
                            value: attributes.columns,
                            options: [
                                { label: '2 Columns', value: 2 },
                                { label: '3 Columns', value: 3 },
                                { label: '4 Columns', value: 4 }
                            ],
                            onChange: function( val ) { setAttributes( { columns: parseInt( val ) } ); }
                        } )
                    ),
                    attributes.type === 'videos' ? el( components.PanelBody, { title: 'Video Settings' },
                        el( components.RangeControl, {
                            label: 'Number of Videos',
                            value: attributes.videoCount,
                            onChange: function( val ) { setAttributes( { videoCount: val } ); },
                            min: 1,
                            max: 50
                        } ),
                        el( components.TextControl, {
                            label: 'Channel ID (optional)',
                            value: attributes.channelId,
                            onChange: function( val ) { setAttributes( { channelId: val } ); },
                            placeholder: 'Leave blank to use default channel',
                            help: 'Override the default channel ID'
                        } )
                    ) : el( components.PanelBody, { title: 'Playlist Settings' },
                        el( components.TextControl, {
                            label: 'Playlist ID (optional)',
                            value: attributes.playlistId,
                            onChange: function( val ) { setAttributes( { playlistId: val } ); },
                            placeholder: 'e.g., PLxxxxxxxxxxxxx',
                            help: 'Leave blank to show all playlists from channel'
                        } ),
                        ! attributes.playlistId ? el( components.TextControl, {
                            label: 'Channel ID (optional)',
                            value: attributes.channelId,
                            onChange: function( val ) { setAttributes( { channelId: val } ); },
                            placeholder: 'Leave blank to use default channel',
                            help: 'Only used when displaying all playlists'
                        } ) : null
                    )
                ),
                el( 'div', { className: 'youtube-feed-block-preview' },
                    el( serverSideRender, { block: 'youtube-feed/display', attributes: attributes } )
                )
            );
        },

        save: function() {
            return null; // server-side rendered
        }
    } );

} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor || window.wp.editor, window.wp.serverSideRender );
