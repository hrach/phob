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
	if you use mod_reqrite you must define this absolute url
*/
$config['absolute_url'] = '';

/*
	if you use complicatly integrations of Phob (with blogs etc.), define this
	varibles 'absolute_url' and 'absolute_server_path' must goes to same directory
	use something as this: $_SERVER['DOCUMENT_ROOT'].'you/path'
*/
$config['absolute_server_path'] = ''; 

/*
	string for non-mod_rewrite mod,
	if you use mod_rewrite, this string could be empty
*/
$config['parametr'] = 'phob.php?url=';

/*
	to this variable put the driving string
*/
$config['parametr_data'] = 'random';



// BASIC SETTINGS -------------------------------------
$config['main_site_name'] = 'Developer';			// site name, name of photogalerie
$config['main_skin'] = 'light';						// the name of skin
$config['main_show_dirup'] = true;					// do you want to show item ".." - "dir up"?
$config['main_dba_handler'] = 'inifile';			// handler for working with comments

$config['dir_data'] = 'photos';						// relative path to directory with photos
$config['dir_thumb'] = 'thumbs';					// relative path to directory with thumbnails (set premisions on 0777)
$config['dir_skins'] = 'skins';						// relative path to directory with skins

$config['admin_nick'] = 'admin';					// nick and password to mini-administration
$config['admin_pass'] = '1234';

$config['path_char'] = ' » ';						// separator of folders




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