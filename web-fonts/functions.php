<?php
function lax_admin() {
	add_theme_support('editor-styles');
	// TODO: should probably split styles need for editor and not, e.g.
	// all menu styling isn't need for the editor
	add_editor_style('style' . SEMLA_MIN . '.css');
	// editor only styles
	add_editor_style('editor-style' . SEMLA_MIN . '.css');

	// if menu changes get rid of our cached versions
	add_action('wp_update_nav_menu', 'lax_delete_menu_cache', 10, 0);
	add_action('semla_clear_menu_cache', 'lax_delete_menu_cache', 10, 0);
	/**
	 * Add extra assets to the Gutenberg editor, note main styles are loaded in add_editor_style()
	 * which targets the styles to the editor 
	 */
	add_action('enqueue_block_editor_assets', function() {
		wp_enqueue_style( 'open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,400i,700&display=swap' );
	});
}
