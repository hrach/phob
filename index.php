<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <hrach.cz@gmail.com>
 * @copyright   Copyright (c) 2008 - 2009, Jan Skrasek
 * @version     0.8 $Id$
 * @link        http://phob.skrasek.com
 * @package     Phob
 */


error_reporting(0);
ob_start();
require_once dirname(__FILE__) . '/phob.class.php';


# pokud chcete provozovat phob na hostingu ic.cz, updavte soubor. htaccess nasledovne:
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

# zapne automaticke generovani comments.txt s nazvy fotografii ve slozce
$phob->renderCommentsFile = true;
# zapne automaticke pregenerovani comments.txt - doplni chybejici nazvvy fotografii
# doporucujeme defaultne vypnout
$phob->updateCommentsFile = false;


$phob->config = array(
	'siteName' => 'PhotoBrowser',
	'skinName' => 'default',
	'showDirup' => true,
	'showExif' => true
);

/* moznost prekladu
	$phob->lang = array(
		'dirup' => 'Nahoru [..]',
		'root_dir' => 'Kořenový adresář',
		'no_photo' => 'Fotografie neexistuje!',

		'exif_Model' => 'Fotoaparát',
		'exif_ExposureTime' => 'Expozice',
		'exif_FNumber' => 'Clona',
		'exif_ISOSpeedRatings' => 'Citlivost',
		'exif_FocalLength' => 'Ohnisková vzdálenost',
		'exif_DateTime' => 'Datum',
	);
*/


echo $phob->render();