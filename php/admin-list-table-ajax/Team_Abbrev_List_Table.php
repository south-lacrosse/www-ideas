<?php
namespace Semla\Admin;

/**
 * Ajaxified version of the team abbrev admin list table
 *
 * That means only the actual list table will reload if the user moves to the
 * next page, without a complete page reload.
 *
 * This is probably overkill for our needs, but saved so we can use it if
 * needed. You probably want to use the previous commit of this file.
 *
 * Issues are:
 *
 * 1. The URL isn't updated to fit the new page state, i.e. &paged=1 never
 *    changes to paged=2, so reloads always go to the first page.
 * 2. The javascript isn't generic - the custom values should be created in
 *    javascript in this class, and then we can have one script for all ajax
 *    list tables
 */

use Semla\Data_Access\Team_Abbrev_Gateway;

if ( ! class_exists ( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class
 */
class Team_Abbrev_List_Table extends \WP_List_Table {
	private $nonce;

	 public function __construct() {
		parent::__construct([
			'singular' => 'team_abbrev',
			'plural'   => 'team_abbrevs',
			'ajax'     => true
		] );
		wp_enqueue_script( 'semla-team-abbrev',
			plugins_url('/js/team-abbrev' . SEMLA_MIN . '.js', dirname(__DIR__)),
			[], '1.0', ['in_footer' => true, 'strategy' => 'async'] );
		$this->nonce = '&_wpnonce=' . wp_create_nonce('semla_team_abbrev');
	}

	 public function get_table_classes() {
		return [ 'widefat', 'fixed', 'striped', $this->_args['plural'] ];
	}

	 public function no_items() {
		echo  'No abbreviations found';
	}

	 public function column_default( $item, $column_name ) {
		return $item->$column_name;
	}

	 public function get_columns() {
		return [
			'cb'      => '<input type="checkbox" />',
			'team'    => 'Team',
			'abbrev'  => 'Abbreviation',
		];
	}

	 public function column_team( $item ) {
		$team = urlencode($item->team);
		return '<a href="?page=semla_team_abbrev&action=edit&team=' . $team
			. '"><strong>' . $item->team .  '</strong></a>';
	}

	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}
		$team = urlencode($item->team);
		$actions = [
			'edit' => '<a href="?page=semla_team_abbrev&action=edit&team=' . $team
				. '" data-team="' . $team . '" title="Edit">Edit</a>',
			'delete' => '<a href="?page=semla_team_abbrev&action=delete&team=' . $team . $this->nonce
				. '" class="submitdelete" data-team="' . $team . '" title="Delete">Delete</a>',
		];
		return $this->row_actions( $actions ) ;
	}

	public function get_sortable_columns() {
		return ['team' => [ 'team', true ]];
	}

	 public function get_bulk_actions() {
		return ['bulk_delete'  => 'Delete'];
	}

	 public function column_cb( $item ) {
		return '<input type="checkbox" name="teams[]" value="' . urlencode($item->team). '" />';
	}

	 public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$per_page = 25;
		$current_page = $this->get_pagenum();
		$args = [
			'offset' => ( $current_page -1 ) * $per_page,
			'number' => $per_page,
		];
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order']   = $_REQUEST['order'] ;
		}
		if ( isset($_REQUEST['s']) && strlen( $_REQUEST['s'] )) {
			$args['search'] = $_REQUEST['s'];
		}
		$this->items = Team_Abbrev_Gateway::get_all( $args );

		$this->set_pagination_args( [
			'total_items' => Team_Abbrev_Gateway::get_count( $args ),
			'per_page'	=> $per_page
		] );
	}

	public function display() {
		wp_nonce_field( 'ajax-team-abbrev-list-nonce', '_ajax_team_abbrev_list_nonce' );
		parent::display();
	}

	public function ajax_response() {

		check_ajax_referer( 'ajax-team-abbrev-list-nonce', '_ajax_team_abbrev_list_nonce' );

		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers'] = $headers;

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}
}
