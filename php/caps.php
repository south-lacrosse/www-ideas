<?php
/**
 * Sample to add / remove capabilities.
 * If custom capabilities are used then stuff like this should probably go in
 * the activate/deavtivate section of the plugin.
 * Note: This can also be done using WP-CLI
 */

define('WP_USE_THEMES', false);
// get www directory which is stored in npm config file
$ini = parse_ini_file(dirname(__DIR__,2) . '/.npmrc');
require($ini['www'] . '/wp-load.php');
//error_reporting(E_ALL);
//ini_set('display_errors',1);

//remove_caps();
add_caps();
echo "Done";
exit(0);
function add_caps() {
    // useful http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types

    $caps = [
        'edit_clubs',
        'edit_others_clubs',

        'publish_clubs',

        'read_private_clubs',
    ];
    $admin_caps = [
        'create_clubs',
        'delete_clubs',
        'semla_admin'
    ];
        
    // add required capabilities to the administrator
    $role = get_role('administrator');
    if ($role) {
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
        foreach ($admin_caps as $cap) {
            $role->add_cap($cap);
        }
    }
    $role = get_role('editor');
    if ($role) {
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
    // foreach (self::$club_capabilities as $cap) {
    // 	$league_manager_caps[$cap] = true;
    // }
    
    // $editor = get_role('editor');
    // $role = add_role('semla_administrator', 'SEMLA Administrator',
    // 		array_merge($league_manager_caps, $editor->capabilities));

    // // make an editor so he can post requests for refs etc.
    // $role = add_role('refs_administrator', 'Referees Administrator',
    // 		array_merge(['manage_referees' => true], $editor->capabilities));
    
    // $role = add_role('league_manager', 'League Manager', $league_manager_caps);
    
    remove_role('club_admin');
    $role = add_role('club_admin', 'Club Adminstrator', [
        'read' => true,
        'edit_clubs' => true,
        // 'edit_others_clubs' => true,
        // 'edit_private_clubs' => true,
        'edit_published_clubs' => true,

        'publish_clubs' => true,
    ]);
            
}
function remove_caps() {
    $caps = [
        'edit_clubs',
        'edit_others_clubs',
        'edit_private_clubs',
        'edit_published_clubs',

        'publish_clubs',

        'read_private_clubs',

        'delete_clubs',
        'delete_private_clubs',
        'delete_published_clubs',
        'delete_others_clubs',
    ];

    // remove capabilities from the administrator
    $role = get_role('administrator');
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
    $role = get_role('editor');
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
    $roles = ['semla_administrator', 'refs_administrator',
    		'league_manager', 'club_admin', 'player',
    		'player_author'];
    // $roles = ['club_admin'];
    foreach ($roles as $role) {
        remove_role($role);
    }
}

