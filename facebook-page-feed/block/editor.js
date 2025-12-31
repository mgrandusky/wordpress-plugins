( function( blocks, element, components, editor, serverSideRender ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;

    registerBlockType( 'fbpf/feed', {
        title: 'Facebook Page Feed',
        icon: 'admin-post',
        category: 'widgets',
        attributes: {
            pageId: { type: 'string', default: ( typeof window !== 'undefined' && window.fbpfConfig && window.fbpfConfig.defaultPageId ) ? window.fbpfConfig.defaultPageId : '' },
            limit: { type: 'number', default: 5 }
        },

        edit: function( props ) {
            var attrs = props.attributes;
            var set = props.setAttributes;

            return el( 'div', { className: 'fbpf-block' },
                el( editor.InspectorControls, {},
                    el( components.PanelBody, { title: 'Feed Settings', initialOpen: true },
                        el( components.TextControl, {
                            label: 'Page ID',
                            value: attrs.pageId,
                            onChange: function(v){ set( { pageId: v } ); },
                            placeholder: 'Leave blank to use default from settings'
                        } ),
                        el( components.RangeControl, {
                            label: 'Posts to show',
                            value: attrs.limit,
                            onChange: function(v){ set( { limit: v } ); },
                            min: 1,
                            max: 20
                        } )
                    )
                ),
                el( 'div', { className: 'fbpf-block-preview' },
                    el( serverSideRender, { block: 'fbpf/feed', attributes: attrs } )
                )
            );
        },

        save: function(){ return null; }
    } );

} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor || window.wp.editor, window.wp.serverSideRender );
