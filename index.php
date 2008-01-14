<?php
ob_start();

$config['mod_rewrite']			= false;
$config['path']['photos'] 		= 'photos';
$config['path']['thumbs'] 		= 'thumbs';
$config['path']['skins'] 		= 'skins';

$config['main']['site_name']	= 'Tento nadpis zmen v index.php ;)';
$config['main']['skin']			= 'basic';
$config['main']['show_dirup']	= true;

//$config['admin_nick'] = 'admin';
//$config['admin_pass'] = '1234';


if (version_compare(PHP_VERSION, '5.0', '<')) {
	die('PhoB needs PHP 5 or above!!!');
}

require_once('phob.class.php');
$phob = new PhoB($config);
echo $phob->render();

ob_end_flush();