<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.6.5
 * @link        http://phob.skrasek.com
 */


ob_start();
require_once dirname(__FILE__) . '/phob.class.php';


# pokud chcete provozovat pho na hostingu ic.cz, updavte soubor. htaccess nasledovne:
# pridejte pred index.php lomitko a cestu z webrootu serveru
/*
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ /cesta/k/phobu/index.php?url=$1 [L]
*/


$phob = new Phob();
$phob->skins = 'skins';
$phob->photos = 'photos';
$phob->thumbs = 'thumbs';

$phob->config = array(
	'siteName' => 'PhotoBrowser',
	'skinName' => 'default',
	'showDirup' => true
);

echo $phob->render();