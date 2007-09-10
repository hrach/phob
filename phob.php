<?php
/**
 * PhoB - Photo Browser - written in PHP5
 * @author Jan Skrasek <hrach.cz(at)gmail(dot)com>
 * @version 0.2.0
 * @copyright Lesser GNU Public License (LGPL)
 */


/*
	some webhostings are require this
*/
ob_start();


/*
	string for non-mod_rewrite mod,
	if you use mod_rewrite, this string could be empty
*/
$config['parametr'] = 'phob.php?url=';

/*
	to this variable put the driving string
*/
@$config['parametr_data'] = $_GET['url'];



// BASIC SETTINGS -------------------------------------
$config['main_site_name'] = 'Developer';			// site name, name of photogalery
$config['main_skin'] = 'basic';						// the name of skin
$config['main_show_dirup'] = true;					// do you want to show item ".." - "dir up"?
$config['main_dba_handler'] = 'inifile';			// handler for working with comments

$config['dir_data'] = './photos';					// relative path (from here) to directory with photos 
$config['dir_thumb'] = './thumbs';					// relative path (from here) to directory with thumbnails (set premisions on 0777)
$config['dir_skins'] = './skins';					// relative path (from here) to directory with skins

$config['admin_nick'] = 'admin';					// nick and password to mini-administration
$config['admin_pass'] = '1234';

$config['allowed_ext'] = array('jpeg', 'jpg');




// MAIN SCRIPT ----------------------------------------
$ver = explode( '.', PHP_VERSION );
if ( $ver[0].$ver[1].$ver[2] < 500 )
	die ( 'PhoB needs PHP 5 or above!!!' );

require_once('phob.class.php');
$phox = new photoBrowser($config);
$phox->exe();
echo $phox->parse();

/*
	some webhostings are require this
*/
ob_end_flush();
?>