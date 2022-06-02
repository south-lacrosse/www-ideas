<?php
namespace Semla;

use Semla\Data_Access\Table_Gateway;

/**
 * Public view part of the shortcodes for our app.
 * NB: remember, shortcodes should return their markup!
 */
class Shortcodes {

	public function __construct() {
		add_shortcode('semla_data', [$this, 'data']);
	}
	
	/**
	 * Add data to page
	 */
	public function data(array  $atts, string $content): string {
		if (isset($atts['src'])) {
			$src = $atts['src'];
			if ($src == 'mini-tables') {
				return (new Table_Gateway())->get_mini_tables();
			}
		}
		return '';
	}
}
