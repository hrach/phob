<?php

ob_start();

$config['mod_rewrite']	= false;

// pokud chcete provozovat pho na hostingu ic.cz, updavte soubor. htaccess nasledovne:
// pridejte pred index.php lomitko a cestu z webrootu serveru
/*
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ /cesta/k/phobu/index.php?url=$1 [L]
*/

$config['photos'] 		= 'photos';
$config['thumbs'] 		= 'thumbs';
$config['skins'] 		= 'skins';

$config['siteName']		= 'PhotoBrowser';
$config['skin']			= 'default';
$config['dirup']		= true;


// =====[PHOB]==============================

require_once dirname(__FILE__) . '/phob.class.php';
$phob = new PhoB($config);
echo $phob->render();