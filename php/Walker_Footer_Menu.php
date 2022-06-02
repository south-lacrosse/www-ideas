<?php
namespace Lax;
/**
 * A walker to traverse the footer menu, and put appropriate classes etc.
 * The menu should contain just a list, so no parent/child
 * The default WordPress version adds too many classes
 * Will respect classes added on the menu admin page, but they must not contain
 *  item|menu|parent. That's becuase removing all those classes is an easy way to remove
 *  WP classes such as menu-item, menu-item-has-children, current-menu-parent.
 * Makes sure there's no whitespace between <li> elements as that messes up the spacing.
 */
class Walker_Footer_Menu extends \Walker {
	public $db_fields = array('parent' => 'menu_item_parent', 'id' => 'db_id');

	/**
	 * Starts the list before the elements are added.
	 * @see Walker::start_lvl()
	 */
	public function start_lvl(&$output, $depth = 0, $args = []) {
	}

	/**
	 * Ends the list of after the elements are added.
	 * @see Walker::end_lvl()
	 */
	public function end_lvl(&$output, $depth = 0, $args = []) {
	}

 	/**
	 * Starts the element output.
	 * @see Walker::start_el()
	 */
	public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0) {
		// don't make filter_builtin_classes into an anonymous function as that
		// will create a new function every time this method is called

		$url = $item->url;
		$icon = '';
		if ($url == '/feed') {
			$icon = '<i class="icon icon-feed"></i>';
		} elseif (preg_match('/(facebook|instagram|twitter)/', $url, $m) ) {
			$icon = '<i class="icon icon-' . $m[1] . '"></i>';
		}

		$classes = empty($item->classes) ? [] :
			array_filter($item->classes, [$this, 'filter_builtin_classes']);
		if ($classes) {
			$class = ' class="' . esc_attr(join(' ', $classes)) . '"';
		} else {
			$class = '';
		}
		$output .= "\n<li>$icon<a href=\"$url\"$class>$item->title</a>";
	}
	private function filter_builtin_classes($var) {
		// return (FALSE === strpos($var, 'item')) ? $var : '';
		return preg_match('/item|menu|parent/', $var) ? '' : $var;
	}
	
	/**
	 * Ends the element output, if needed.
	 * @see Walker::end_el()
	 */
	public function end_el(&$output, $item, $depth = 0, $args = []) {
		// must no have line feed after end tag
		$output .= '</li>';
	}
}