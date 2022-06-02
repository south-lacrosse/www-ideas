<?php
namespace Semla;
/**
 * Version of Post_Type with custom capabilities, which isn't currently used. Kept here
 * in case we need this in the future.
 * 
 * Note: You don't need this unless you want custom meta caps, that is to be able to
 * decide in code if a role can access a CPT. e.g. this class allows a user to edit/read
 * a club if they created it, or have read/edit permissions AND have the club id set on
 * their user profile.
 * 
 * Custom post type.
 */
class Post_Type {
	private $edit_cap;
	private $read_cap;
	private $create_cap;
	private $edit_posts_cap;
	private $call_count = 0; // times map_meta_cap called -see method map_meta_cap

	/**
	 * @param string $post_type the post type to create
	 * @param mixed $cap_type capability type. Can be a string, in which case plural add 's',
	 * 		or array of singular and plural
	 * @param mixed $name name to use in labels, so 'New Name'. Can be a string, in which case plural add 's',
	 * 		or array of singular and plural
	 * @param array $args arguments. Can pass them all in, or use defaults for all args and labels
	 */
	public function __construct(string $post_type, $cap_type, $name, array $args) {
		if ( ! is_array( $cap_type ) )
			$cap_type = array( $cap_type, $cap_type . 's' );
		list( $single_cap, $plural_cap ) = $cap_type;

		if ( is_array( $name ) ) {
			list( $single_name, $plural_name ) = $name;
		} else {
			$single_name = $name;
			$plural_name = $name . 's';
		}

		// default args
		$args = array_merge([
			'public' => true,
			'has_archive' => false,
			'rewrite' => [
				'paged' => false,
				'feed' => false
			],
			'capability_type' => $cap_type,
			'capabilities' => [
				// Meta capabilities
				'edit_post'          => 'edit_'         . $single_cap,
				'read_post'          => 'read_'         . $single_cap,
				// 'delete_post'        => 'delete_'       . $single_cap,
				// No meta delete caps, so just go straight to delete_clubs primitve
				'delete_post'        => 'delete_'       . $plural_cap,
				// Primitive capabilities used outside of map_meta_cap():
				'edit_posts'         => 'edit_'         . $plural_cap,
				'edit_others_posts'  => 'edit_others_'  . $plural_cap,
				'publish_posts'      => 'publish_'      . $plural_cap,
				'read_private_posts' => 'read_private_' . $plural_cap,
		 		'delete_posts'       => 'delete_'       . $plural_cap,
				// need to map create_posts capability otherwise we can't block creating new clubs
				'create_posts'       => "create_$plural_cap",
				
				// Primitive capabilities used within WordPress map_meta_cap()

				// Just here for documentation in case we want this in the future.
				// We don't currently use these as we don't need to be this
				// fine grained. e.g. clubs may be owners of their club page, but they
				// shouldn't be able to delete it, or any other, so we don't need
				// delete_others_clubs - only the admin will be able to delete anything!

				//  'read'                   => 'read',
				// 'delete_private_posts'   => 'delete_private_'   . $plural_cap,
				// 'delete_published_posts' => 'delete_published_' . $plural_cap,
				// 'delete_others_posts'    => 'delete_others_'    . $plural_cap,
				// 'edit_private_posts'     => 'edit_private_'     . $plural_cap,
				// 'edit_published_posts'   => 'edit_published_'   . $plural_cap,
			],
			'map_meta_cap' => false,
			// need for Gutenberg
			'show_in_rest' => true,
			'supports' => [
				'title',
				'editor',
				// 'author',
				'revisions'
			]
		], $args);
		
		// might as well always do this, as WP will set these anyway
		if (!isset($args['labels'])) {
			$args['labels'] = [
				'name' => $plural_name,
				'singular_name' => $single_name,
				'add_new' => 'Add New',
				'add_new_item' => "Add a New $single_name",
				'edit_item' => "Edit $single_name",
				'new_item' => "New $single_name",
				'view_item' => "View $single_name",
				'view_items' => "View $plural_name",
				'search_items' => "Search $plural_name",
				'not_found' => "No $plural_name Found",
				'not_found_in_trash' => "No $plural_name Found In Trash",
				'parent_item_colon' => "Parent $single_name",
				'all_items' => "All $plural_name",
				'archives' => "All $plural_name",
    			'attributes' => "$single_name Attributes",
				'insert_into_item' => "Insert into $single_name",
            	'uploaded_to_this_item' => "Uploaded to this $single_name",
            	'filter_items_list' => "Filter $plural_name list",
            	'items_list_navigation' => "$plural_name list navigation",
            	'items_list' =>  "$plural_name list",
				'menu_name' => $plural_name,
				'item_published' => "$single_name published.",
				'item_published_privately' => "$single_name published privately.",
				'item_reverted_to_draft' => "$single_name reverted to draft.",
				'item_scheduled' => "$single_name scheduled.",
				'item_updated' => "$single_name updated.",
				'name_admin_bar' => $single_name,
			];
		}
		register_post_type($post_type, $args);
		
		if (is_admin()) {
			// change "Enter Post name here" placeholder
			$placeholder = isset($args['placeholder']) ?
				$args['placeholder'] : "Enter $single_name name here";
			add_filter('enter_title_here', function(string $title) use ($post_type, $placeholder): string {
				if  (get_current_screen()->post_type == $post_type) {
					return $placeholder;
				}
				return $title;
			});
		}

		// Don't check is_admin() here! This needs to have map_meta_cap when REST requests are
		// made too, but we won't know that until after the init hook this code is called from.
		// No real harm in always doing this anyway.
		$this->edit_cap = $args['capabilities']['edit_post'];
		$this->read_cap = $args['capabilities']['read_post'];
		$this->create_cap = $args['capabilities']['create_posts'];
		$this->edit_posts_cap = $args['capabilities']['edit_posts'];
		add_filter( 'map_meta_cap', [$this, 'map_meta_cap'], 10, 4);
	}

	/**
	 * See WP function map_meta_cap() in capabilities.php for examples on how to code this
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		// workaround WP bug 22895. If Clubs has no submenus AND it's own create_posts
		// capbility then the user cannot access the edit page - even though the item
		// is on the menu. This fix keeps the Add New item on the menu the first time round,
		// then it gets removed the 2nd - but the edit submenu option does not get removed.

		// This does mean there is a submenu option for "All Clubs" which isn't really needed now

		// Alternatively there is a patch in the WordPress trac, but that would mean applying that to
		// every new release of WordPress.
		if ( $this->create_cap == $cap ) {
			global $pagenow;
			$this->call_count++;
			if ( $pagenow == "edit.php" && $this->call_count == 1) {
				return [ $this->edit_posts_cap ];
			}
			return $caps;
		}

		// only want to monitor our read_post and edit_post meta capabilities
		if ( $this->edit_cap != $cap && $this->read_cap !== $cap ) {
			return $caps;
		}

		// Get the post and post type object to determine if the user is allowed access
		$post = get_post( $args[0] );
		if ( ! $post ) {
			return [ 'do_not_allow' ];
		}
		if ( 'revision' == $post->post_type ) {
			$post = get_post( $post->post_parent );
			if ( ! $post ) {
				return [ 'do_not_allow' ];
			}
		}

		$post_type = get_post_type_object( $post->post_type );
		
		if ( $this->read_cap == $cap ) {
			if ( 'private' != $post->post_status || $user_id == $post->post_author ) {
				$caps[] = 'read';
			} else {
				$user_club = get_user_meta($user_id, 'semla_club', true);
				if ( $post->post_title == $user_club ) {
					$caps[] = 'read';
				} else {
					$caps[] = $post_type->cap->read_private_posts;
				}
			}
		}
		// must be edit cap
		if ( $user_id == $post->post_author ) {
			return [ $post_type->cap->edit_posts ];
		}
		$user_club = get_user_meta($user_id, 'semla_club', true);
		if ( $post->post_title == $user_club ) {
			return [ $post_type->cap->edit_posts ];
		}
		return [ $post_type->cap->edit_others_posts ];
	}
}
