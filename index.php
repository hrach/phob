<?php

ob_start();

$config['mod_rewrite']			=  false;
// pokud chcete provozovat pho na hostingu ic.cz, updavte soubor. htaccess nasledovne:
// pridejte pred index.php lomitko a cestu z webrootu serveru
/*
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ /cesta/k/phobu/index.php?url=$1 [L]
*/

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