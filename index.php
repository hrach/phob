<?php

ob_start();

$config['mod_rewrite']			= false;
$config['path']['photos'] 		= 'photos';
$config['path']['thumbs'] 		= 'thumbs';
$config['path']['skins'] 		= 'skins';

$config['main']['site_name']	= 'PhotoBrowser';
$config['main']['skin']			= 'default';
$config['main']['show_dirup']	= true;


// =====[PHOB]==============================

if (version_compare(PHP_VERSION, '5.0', '<')) {
	die('PhoB needs PHP 5 or above!!!');
}

require_once dirname(__FILE__) . '/phob.class.php';
$phob = new PhoB($config);
echo $phob->render();