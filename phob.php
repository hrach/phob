<?php
/**
 * PhoB - Photo Browser - written in PHP5
 * @author Jan Skrasek <hrach.cz(at)gmail(dot)com>
 * @version 0.2.5
 * @copyright Lesser GNU Public License (LGPL)
 */

ob_start();

$config['main_script_name'] = basename(__FILE__);				// for example phob.php
$config['mod_rewrite_on'] = false;								// true = mod_rewrite on;

// BASIC SETTINGS 

$config['main_site_name'] = 'Developer';			// site name, name of photogalery
$config['main_skin'] = 'basic';						// the name of skin
$config['main_show_dirup'] = true;					// do you want to show item ".." - "dir up"?

$config['dir_data'] = 'photos';						// relative path (from here) to directory with photos 
$config['dir_thumb'] = 'thumbs';					// relative path (from here) to directory with thumbnails (set premisions on 0777)
$config['dir_skins'] = 'skins';						// relative path (from here) to directory with skins

$config['admin_nick'] = 'admin';					// nick and password to mini-administration
$config['admin_pass'] = '1234';

$config['main_dba_handler'] = 'inifile';			// handler for working with comments
$config['allowed_ext'] = array('jpeg', 'jpg');		// allowed extensionis, please, for thid moment do not change



// MAIN SCRIPT ----------------------------------------
$ver = explode( '.', PHP_VERSION );
if ( $ver[0].$ver[1].$ver[2] < 500 )
	die ( 'PhoB needs PHP 5 or above!!!' );

require_once('phob.class.php');
$phox = new phoB($config);
$phox->exe();
echo $phox->parse();
ob_end_flush();
?>