<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <hrach.cz@gmail.com>
 * @copyright   Copyright (c) 2008 - 2009, Jan Skrasek
 * @version     0.7 $Id$
 * @link        http://phob.skrasek.com
 * @package     Phob
 */


error_reporting(0);
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


# moznost prekladu
/*
	$phob->lang = array(
		'Nahoru [..]' => 'Up [..]',
		'Kořenový adresář' => 'Root',
		'Fotografie neexistuje' => 'Photo dosne\' exists.'
	);
*/


echo $phob->render();