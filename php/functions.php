<?php
/** Stuff removed from theme, but may be added back later */

// for now remove all WP body classes except for blocks, e.g. 
// class="post-template-default single single-post postid-120 single-format-standard logged-in wp-embed-responsive"
add_filter( 'body_class', function( $classes ) {
    return array_filter( $classes, function($var) {
        return substr($var, 0, 3) === 'wp-';
    } );
} );

/**
 * Load cached menu, or regenerate it
 * 
 * wp_nav_menu executes 4 queries, which return a load of data, so menus are cached here.
 */
function lax_menu($menu) {
	$menuFile = __DIR__ . '/template-parts/menu-' . $menu . '.html';
	if (!@readfile($menuFile)) {
		ob_start(); 
		if ($menu == 'main') {
			require __DIR__ . '/inc/Walker_Main_Menu.php';
			wp_nav_menu([
				'theme_location' => 'main',
				'menu_id' => 'main-menu', // id for generated <ul>
				'menu_class' => 'mu', // class for generated <ul>
				'container' => false,
				'walker' => new \Lax\Walker_Main_Menu(),
				'fallback_cb' => false
			]);
		} else {
			require __DIR__ . '/inc/Walker_Footer_Menu.php';
			wp_nav_menu([
				'theme_location' => 'footer',
				'menu_id' => 'footer',
				'menu_class' => 'nav-list',
				'container' => false,
				'walker' => new \Lax\Walker_Footer_Menu(),
				'fallback_cb' => false
			]);
		}
		$menu = ob_get_contents();
		ob_end_clean();
		$site_url = defined( 'WP_SITEURL' ) ? WP_SITEURL : get_option('siteurl');
		$menu = str_replace($site_url, '', $menu);
		$menu = str_replace('href="http', 'rel="nofollow" href="http', $menu);
		// write to a temp file so another process doesn't try to read
		// a half written file
		$tmpf = tempnam('/tmp','semla_cache');
		$fp = fopen($tmpf,'w');
		fwrite($fp,$menu);
		fclose($fp);
		chmod($tmpf, 0604); // temp files default to 0600
		rename($tmpf, $menuFile);
		echo $menu;
	}
}

/**
 * Prints HTML with for a list suggested pages
 */
function lax_page_suggestions() {
	lax_list_posts('Most Recent Posts', [
		'post_type' => 'post',
		'posts_per_page' => 5,
		'no_found_rows' => true,
		'cache_results'=> false
	]);
?>
<h3>Most Used Categories</h3>
<ul>
<?php
	wp_list_categories([
		'orderby'    => 'count',
		'order'      => 'DESC',
		'show_count' => 1,
		'title_li'   => '',
		'number'     => 10,
	 ]);
?>
</ul>
<?php
}

/**
 * Prints HTML with for a list of posts
 */
function lax_list_posts($section_title, $args) {
	$post_list = new WP_Query($args);
	if ($post_list->have_posts()) {
		echo '<h3>', $section_title, "</h3>\n<ul>\n";
		while ($post_list->have_posts()) {
			$post_list->the_post();
			the_title('<li><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></li>');
		}
		echo "\n</ul>";
	}
	wp_reset_postdata();
}
