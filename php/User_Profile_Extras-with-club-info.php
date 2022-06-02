<?php
namespace Semla\Admin;
use Semla\Data_Access\Club_Gateway;

/**
 * Handle extra user profile fields. As of version 1.0.0 of the plugin this
 * isn't used, but kept in case it may be useful
 */
class User_Profile_Extras {

	/**
	 * Set up all the hooks
	 */
	public function init() {
		// display on own profile and for updating other users
		add_action('show_user_profile', [$this, 'show_fields']);
		add_action('edit_user_profile', [$this, 'show_fields']);

		// and again for the update part
		add_action( 'personal_options_update',  [$this, 'save_fields'] ); 
		add_action( 'edit_user_profile_update',  [$this, 'save_fields'] );
	}
	
	/**
	 * Hook to display our extra information
	 */
	public function show_fields(\WP_User $user) {
		$user_club = get_user_meta($user->ID, 'semla_club', true);
		?>
<h2>SEMLA Club Information</h2>
<table class="form-table">
<tbody>
<tr>
<th>
<label for="semla_club">Club</label>
</th>
<td><?php
		if (current_user_can( 'edit_users' )) {
			echo '<select name="semla_club" id="semla_club">';
			echo '<option' . ($user_club ? '' : ' selected') . ' value="">No club assigned</option>';
			$gateway = new Club_Gateway();
			$clubs = $gateway->get_club_names();
			if ($clubs) {
				foreach ( $clubs as $club ) {
					echo '<option' . ( $club == $user_club ? ' selected' : '' )
						. '>' . $club . '</option>';
				}
			}
			echo '</select>';
		} else {
			echo $user_club ? $user_club : 'None assigned';
		}
		?>
</td>
</tbody>
</table>
<?php		
		return;
	}
	
	public function save_fields( $user_id ) {
		global $wpdb;

		if ( ! current_user_can( 'edit_users' )
		|| ! isset( $_POST['semla_club'] )) {
			return;
		}
		$user_club = sanitize_text_field( $_POST['semla_club']);
		if (!$user_club) {
			delete_user_meta($user_id, 'semla_club');
		}

		// sanity check
		$club_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT count(*) FROM $wpdb->posts
			WHERE post_title=%s and post_type='clubs'",$user_club));
		if ($club_exists > 0) {
			update_user_meta( $user_id, 'semla_club', $user_club );
		}
		return;
	}
}