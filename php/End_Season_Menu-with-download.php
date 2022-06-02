<?php
namespace Semla\Admin;

use FilesystemIterator;
use RegexIterator;
use Semla\Cache;

/**
 * Close out at the end of a season. Loads all info into historical tables.
 */
class End_Season_Menu {
	private static $backup_dir;
	
	public static function menu_page(): void {
		if (!current_user_can('manage_semla'))  {
			wp_die('You do not have sufficient permissions to access this page.');
		}
		self::$backup_dir = dirname(__DIR__,2) . '/bin/backups';
		if (isset( $_GET[ 'action' ] )) {
			switch ($_GET[ 'action' ]) {
				case 'backup':
					self::backup();
					break;
				case 'dir':
					self::dir_backups();
					break;
				case 'run':
					Admin_Menu::validate_nonce('semla_run_end_season');
					Admin_Menu::dismissible_error_message('Not implemented yet!');
					break;
				case  'delete':
					self::delete();
					break;
				case 'clear_cache':
					Admin_Menu::validate_nonce('semla_clear_hist_cache');
					Cache::clear_cache();
					Admin_Menu::dismissible_success_message('The cache has been successfully cleared');
					break;
			}
		}
		?>
<div class="wrap">
<h1>SEMLA End Of Season Processing</h1>
<style>.semla-bb{border-bottom: 1px solid #eee}</style>
<div id="poststuff">
<div class="notice notice-warning inline">
	<p>Before running the end of season processing:</p>
	<ol>
	<li>Make sure all results have been loaded in to the fixtures spreadsheet, and the site has been updated.</li>
	<li>Check the current tables and fixtures to make sure they look OK.</li>
	<li>Backup the historical tables (and make sure it worked!) - that way if it messes up you can go back.</li>
	</ol>
</div>
<div class="postbox">
	<h2 class="semla-bb">Backing Up The Historical Tables</h2>
	<div class="inside">
		<p>There are many ways to backup the tables. Probably the best is to use PHPMyAdmin to create
			a backup, and download it. You will need to backup all tables prefixed <i>semla_</i>.
			Alternatively you can use the button below to create a backup
			in the <code><?= self::$backup_dir ?></code> directory on the server.</p>
		<p><a class="button-secondary" href="<?= wp_nonce_url('?page=semla_end_season&action=backup','semla_backup') ?>">Backup Historical Tables</a>
		<a class="button-secondary" href="?page=semla_end_season&action=dir">List backup directory</a></p>
		<p>To restore from a backup connect to the server and go to the backup dir</p>
		<pre><code>gunzip backup.sql.gz</code></pre>
		<pre><code>cd ..</code></pre>
		<pre><code>run-sql.sh backups/backup.sql</code></pre>
    </div>
</div>
<p><a class="button-primary" href="<?= wp_nonce_url('?page=semla_end_season&action=run', 'semla_run_end_season') ?>">Run End Of Season Processing</a></p>
<div class="postbox">
	<div class="inside">
		<p>All history pages are cached internally, and the cache is cleared once the end of season processing is run.
			If needed, you can clear the cache using the button below.</p>
	</div>
</div>
<p><a class="button-secondary" href="<?= wp_nonce_url('?page=semla_end_season&action=clear_cache', 'semla_clear_hist_cache') ?>">Clear cache of history pages</a></p>
</div>
</div>
<?php
    }

	private static function backup() {
		global $table_prefix;
		Admin_Menu::validate_nonce('semla_backup');
		$cmd = dirname(__DIR__,2) . '/bin/backup-semla-db.';
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$cmd .= "bat $table_prefix 2>&1";
			$result = shell_exec($cmd);
		} else {
			$cmd .= "sh $table_prefix";
			$result = shell_exec($cmd);
		}
		if (!$result) {
			Admin_Menu::dismissible_error_message('Backup failed to run.');
		} else { ?>
<div class="notice notice-success is-dismissible">
<p>Results from backup request:</p>
<pre><?= $result ?></pre>
</div>
<?php	}
	}

	private static function dir_backups() {
		?>
<div class="notice notice-success is-dismissible">
<p>List of backups:</p>
<?php
		$filelist = [];
		if (is_dir(self::$backup_dir)) {
			$iterator = new RegexIterator(new FilesystemIterator(self::$backup_dir), '![/\\\]db-semla-[^/\\\]*$!');
			foreach($iterator as $entry) {
				$filelist[] = '<tr><td>' . $entry->getFilename() . ' (' .self::humanFileSize($entry->getSize())
					. '}</td><td>'
					. '<a class="button-secondary" href="'
					.  wp_nonce_url('?page=semla_end_season&download=' . esc_attr($entry->getFilename()), 'semla_download'). '">Download</a>'
					. '<a class="button-secondary" href="'
					. wp_nonce_url('?page=semla_end_season&action=delete&file=' . esc_attr($entry->getFilename()), 'semla_delete'). '">Delete</a>'
					. '</td></tr>';
			}
		}
		if ($filelist) {
			echo '<table><tbody>' . implode("\n",$filelist) . '</tbody></table>';
		} else {
			echo '<p>None found.</p>';
		}
?>
</div>
<?php
	}

	private static function humanFileSize($size,$unit='') {
		if( (!$unit && $size >= 1<<30) || $unit == "GB")
			return number_format($size/(1<<30),2)."GB";
		if( (!$unit && $size >= 1<<20) || $unit == "MB")
			return number_format($size/(1<<20),2)."MB";
		if( (!$unit && $size >= 1<<10) || $unit == "KB")
			return number_format($size/(1<<10),2)."KB";
		return number_format($size)." bytes";
	 }

	 /**
	  * Called from load-End_Season_Menu hook
	  */
	public static function check_download() {
		if ( empty( $_GET['download'] ) )
			return;
		Admin_Menu::validate_nonce('semla_download');
		$file = $_GET['download'];
		if (!preg_match('/^[\w\.-]*$/', $file)) {
			wp_die('Invalid file selected:' . esc_html($file));
		}
		$diskfile = dirname(__DIR__,2) . '/bin/backups/' . $file;
		if (!file_exists($diskfile)) {
			wp_die('Unknown file selected:' . esc_html($file));
		}
		if ( ! headers_sent() ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Length: ' . filesize( $diskfile ) );
			header( 'Content-Disposition: attachment; filename=' . $file );
			readfile( $diskfile );
			exit;
		} else {
			$last_error = error_get_last();
			$msg        = isset( $last_error['message'] ) ? '<p>Error: ' . $last_error['message'] . '</p>' : '';
			wp_die( '<h3>Output prevented download.</h3>' . $msg );
		}
	}

	private static function delete() {
		Admin_Menu::validate_nonce('semla_delete');
		if ( empty( $_GET['file'] ) )
			return;
		$file = $_GET['file'];
		if (!preg_match('/^[\w\.-]*$/', $file)) {
			Admin_Menu::dismissible_error_message('Invalid file selected:' . esc_html($file));
			return;
		}
		$diskfile = self::$backup_dir . '/' . $file;
		if (!file_exists($diskfile)) {
			Admin_Menu::dismissible_error_message('Unknown file selected:' . esc_html($file));
			return;
		}
		if ( $_GET[ 'action' ] == 'delete' ) {
			unlink($diskfile);
			Admin_Menu::dismissible_success_message('File ' . esc_html($file) . ' deleted');
		}
	}
}
