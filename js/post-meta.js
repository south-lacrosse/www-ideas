/**
 * Custom post meta field for wide content
 */

// add in App.php
// foreach (['post','page'] as $post_type) {
// 	// starts with underscore to ensure metadata is private
// 	register_post_meta( $post_type, '_semla_wide_content', [
// 		'show_in_rest' => true,
// 		'single' => true,
// 		'type' => 'boolean',
// 		'auth_callback' => function() {
// 			return current_user_can('edit_posts');
// 		}
// 	] );
// }

import { ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';

registerPlugin( 'semla-wide-content-panel', {
	render: WideContentPanel,
} );

function WideContentPanel() {
	const wideContent = useSelect( ( select ) => {
		const store = select( editorStore );
		// only load for specific post types. Note: this cannot be done when
		// registerPlugin is called as currentPostType will be null (even if
		// run in domReady)
		if ( ! [ 'post', 'page' ].includes( store.getCurrentPostType() ) ) {
			return undefined;
		}
		const _wideContent =
			store.getEditedPostAttribute( 'meta' )?._semla_wide_content;
		return _wideContent === undefined ? false : _wideContent;
	} );
	const { editPost } = useDispatch( editorStore );
	if ( wideContent === undefined ) return;
	return (
		<PluginDocumentSettingPanel title="Content Width" initialOpen="true">
			<ToggleControl
				label="Wide Content"
				checked={ wideContent }
				onChange={ ( value ) =>
					editPost( { meta: { _semla_wide_content: value } } )
				}
				help={
					wideContent
						? 'Make page content normal width.'
						: 'Wide page content.'
				}
			/>
		</PluginDocumentSettingPanel>
	);
}
