<?php
/**
 * PhoB - Photo Browser - written in PHP5
 * @author Jan Skrasek <hrach.cz(at)gmail(dot)com>
 * @version 1.0 RC2
 * @copyright Lesser GNU Public License (LGPL)
 */

class phoB {
	public $lang;

	protected $config = null;
	protected $data = null;
	protected $action = '';
	protected $name = '';
	protected $id = '';
	protected $path = '';
	protected $sub_get = '';
	protected $is_login = false;
	protected $scan_error = false;
	protected $header_url = '';
	protected $thumbnail = '';

	static function getUrl() {
		if($_SERVER['HTTPS']) { $url = 'https://'; } else { $url = 'http://'; }
		$url .= $_SERVER['HTTP_HOST'];
		$url .= $_SERVER['SCRIPT_NAME'];
		return dirname($url);
	}

	static function delSlash($string)	{
		if($string == '') return $string;
		$i = 0; $y = strlen($string);
		if($string{0} == '/') $i = 1;
		if($string{$y-1} == '/') $y = -1;
		return substr($string, $i, $y);
	}

	static function isDir($string) {
		if (eregi('.+\..+', $string)) return false;
		return true;
	}

	function isImg($string) {
		$ext = strtolower(substr($string, strrpos($string, '.')+1));
		if (in_array($ext, $this->config['allowed_ext'])) return true;
		return false;
	}

	function getImg($i, $direction) {
		$data = $this->data['list'];
		if(($i+$direction) < 1 || ($i+$direction) > (count($data))) return null;
		if($data[$i+$direction]['dir']) return $this->getimg($i+$direction, $direction);
		return $data[$i+$direction]['name'];
	}

	function __($string) {
		$translate = $this->lang[$string];
		if($translate != '') return $translate;
		return $string;
	}

	function __construct($config)	{
		if (!is_array($config)) Die(__('Špatná konfigurace!'));

		// for css
		$config['url_base'] = phoB::getUrl();
		// for top
		$config['url_main'] = $config['url_base'];
		if($config['main_script_name'] != 'index.php' && !$config['mod_rewrite_on']) 
			$config['url_main'] .= "/" . $config['main_script_name'];
		// for browsing
		$config['url_browse'] = $config['url_main'];
		if(!$config['mod_rewrite_on']) {
			$config['url_browse'] .= "?url=";
		} else {
			$config['url_browse'] .= "/";
		}

		$config['server_path'] = dirname(__FILE__); 

		$this->config = $config;
		$this->parametr = $_GET['url'];
		$this->parseParametr();
	}

	protected function setVar( $name, $value ) {
		$this->vars[$name] = $value;
	}

	protected function fetchTemplate( ) {
		extract($this->vars);
		include($this->config['server_path'].'/'.$this->config['dir_skins'].'/'.$this->config['main_skin'].'/'.$this->action.'.php');
		$return = ob_get_clean();
		return $return;
	}

	protected function parseParametr(){

		if(ereg("^(preview|thumbnail)/(.*)/(.*)$", $this->parametr, $reg)){
			$this->action = $reg[1];
			$this->path = $reg[2];
			$this->name = $reg[3];
			$this->sub_get = '';
		}elseif(ereg("^(preview|thumbnail)/(.*)$", $this->parametr, $reg)){
			$this->action = $reg[1];
			$this->path = '';
			$this->name = $reg[2];
			$this->sub_get = '';
		}elseif(ereg("^random$", $this->parametr, $reg)){
			$this->action = 'random';
			$this->path = '';
			$this->name = '';
			$this->sub_get = '';
		}elseif(ereg("^random/(.*)$", $this->parametr, $reg)){
			$this->action = 'random';
			$this->path = '';
			$this->name = '';
			$this->sub_get = $reg[1];
		}elseif(ereg("^list/(.*)$", $this->parametr, $reg)){
			$this->action = 'list';
			$this->path = $reg[1];
			$this->name = '';
			$this->sub_get = '';
		}elseif(ereg("^admin(/?)(.+)?$", $this->parametr, $reg)){
			$this->action = 'admin';
			$this->path = '';
			$this->name = '';
			$this->sub_get = phoB::delSlash($reg[2]);
		}else{
			$this->action = 'list';
			$this->path = '';
			$this->name = '';
			$this->sub_get = '';
		}
	}

	public function	exe() {
		$this->is_login = $this->login();
		switch($this->action)
		{
			case 'random':
				$random = $this->get_random($this->dir_list($this->sub_get));
				$this->path = $random['path'];
				$this->name = $random['name'];
				break;
			case 'preview':
			case 'list':
				$this->data['list'] = $this->scan();
				break;
			case 'thumbnail':
				$this->thumbnail();
				break;
			case 'admin':
				$this->admin();
				break;
		}
	}

	protected function get_random($a) {
		return $a[array_rand($a)];
	}

	protected function dir_list($path) {
		$this->path = $path;
		$ret = array();
		$files = $this->scan();

		foreach($files as $entry)
		{
			if($entry['name'] == '.' || $entry['name'] == '..') continue;
			if(phoB::isDir($entry['name'])) {
				$this->path = $entry['path'];
				$ret = array_merge($ret, $this->dir_list($entry['path']));
			} else {
				$ret[] = array('name' => $entry['name'], 'path' => phoB::delSlash($path.'/'));
			}
		}
		return $ret;
	}

	protected function saveComment( $path, $id, $comment ) {

		$data = @file( $path );
		$set = false;
		for($i=0;$i<count($data);$i++) {
			$rowId = substr( $data[$i], 0, strpos( $data[$i], '	'));
			$rowCom = substr( $data[$i], strpos( $data[$i], '	'));
			if($rowId == $id) {
				if( empty($comment) ) continue;
				$newFile[$i] = $rowId."	".$comment;
				$set = true;
				break;
			} else {
				$newFile[$i] = $rowId."	".$rowCom;
			}
		}

		if($set == false && !empty($comment) ) {
			$newFile[] = $id."	".$comment;
		}

		$newFile = implode("\n", $newFile);
		file_put_contents( $path, $newFile );

	}

	protected function readComment( $path, $id ) {

		$data = @file( $path );
		for($i=0;$i<count($data);$i++) {
			$rowId = substr( $data[$i], 0, strpos( $data[$i], '	'));
			$rowCom = substr( $data[$i], strpos( $data[$i], '	'));
			if($rowId == $id) {
				return $rowCom;
			}
		}
		return '';

	}

	protected function admin() {

		if($this->sub_get == 'login' && $_POST['nick'] == $this->config['admin_nick'] && $_POST['pass'] == $this->config['admin_pass']) {

			session_start();
			$_SESSION['nick']	= $_POST['nick'];
			$_SESSION['pass']	= md5($_POST['pass']);
			$this->header_url = 'Location: '.$this->config['url_browse'].'admin';

		}elseif($this->sub_get == 'logout'){

			$_SESSION = array();
			session_destroy();
			$this->header_url = 'Location: '.$this->config['url_browse'].'list';

		}elseif(ereg("^save/(.*)/(.*)$", $this->sub_get, $reg)){

			$path = $this->config['dir_data'].'/'.$reg[1].'/'.'comments.phob';
			$this->saveComment( $path, $reg[2], $_POST['label'] );
			$this->header_url = 'Location: '.$this->config['url_browse'].'preview/'.$reg[1].'/'.$reg[2];

		}elseif(ereg("^save/(.*)$", $this->sub_get, $reg)){

			$path = $this->config['dir_data'].'/comments.phob';
			$this->saveComment( $path, $reg[1], $_POST['label'] );
			$this->header_url = 'Location: '.$this->config['url_browse'].'preview/'.$reg[1];

		}
	}

	protected function thumbnail() {
		$thumb_path = $this->config['dir_thumb'].'/'.$this->path.'_'.$this->name;
		$thumb_exist = file_exists($thumb_path);

		header('Content-type: image/jpeg');

		if(!$thumb_exist) {

			$img_path = phoB::delSlash($this->config['dir_data'].'/'.$this->path).'/'.$this->name;
			$thumbnail = exif_thumbnail($img_path);

			if($thumbnail == false) {

				if(exif_imagetype($img_path) !== IMAGETYPE_JPEG) die('The picture is not a jpeg');
				$obsah = file_get_contents($img_path);

				$old = imagecreatefromstring($obsah);
				$old_x = ImageSx($old);
				$old_y = ImageSy($old);

				if($old_y > $old_x){
					$k = $old_y / 120;
					$new_y = 120;
					$new_x = floor($old_x / $k);
				}else{
					$k = $old_x / 160;
					$new_x = 160;
					$new_y = floor($old_y / $k);
				}

				$nahled = imagecreatetruecolor($new_x, $new_y);

				ImageCopyResized($nahled, $old, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);
				imagejpeg($nahled, $thumb_path);
				imagedestroy($nahled);
				$this->thumbnail = file_get_contents($thumb_path);

			}else{
				file_put_contents($thumb_path, $thumbnail);
				$this->thumbnail = $thumbnail;
			}
		} else {
			$this->thumbnail = file_get_contents($thumb_path);
		}
	}

	protected function login() {
		if(session_id() == "") session_start();
		if(!isset($_SESSION['nick'])) $_SESSION['nick'] = '';
		if(!isset($_SESSION['pass'])) $_SESSION['pass'] = '';
		if(($this->config['admin_nick'] == $_SESSION['nick']) && (md5($this->config['admin_pass']) == $_SESSION['pass']) )	return true;
		return false;
	}

	public function scan() {

		$data = array();
		$path = $this->path;
		$dirs = split('/', $path);

		$dir_now = '';

		foreach($dirs as $dir) {
			$dir_now = phoB::delSlash($dir_now.'/'.$dir);
			$folders[] = array('name' => $dir, 'path' => $dir_now);
		}

		$this->data['folders'] = $folders;
		$scan_dir = $this->config['server_path'].'/'.$this->config['dir_data'].'/'.$path;
		if(!file_exists($scan_dir)) {
			$this->scan_error = true;
		}

		$files = @scandir($scan_dir);
		if(!is_array($files)) $files = array();
		$i = 0;
		foreach($files as $entry)
		{
			if(!phoB::isDir($entry)) continue;
			if( ($entry{0} == '.' && $entry != '..') ||
				($path == '' && $entry == '..') ||
				(!$this->config['main_show_dirup'] && $entry == '..')
				) continue;

			$i++;
			$data[$i]['name']	= $entry;
			$data[$i]['path']	= phoB::delSlash($path.'/'.$entry);
			$data[$i]['dir']		= true;
			$data[$i]['show_name']	= $entry;

			if($data[$i]['name'] == '..') {
				$new_path = null;

				$adr = split('/', $data[$i]['path']);
				for($j=0; $j<count($adr)-2; $j++) {
					$new_path .= '/'. $adr[$j];
				}

				$data[$i]['path'] = phoB::delSlash($new_path);
				$data[$i]['show_name'] = $this->__('Nahoru');
			} else {
				$data[$i]['show_name'] = $data[$i]['name'];
			}
		}

		foreach($files as $entry) {
			if(phoB::isDir($entry) || !phoB::isImg($entry)) continue;

			$i++;
			if($this->name == $entry) $this->id = $i;
			$data[$i]['name']	= $entry;
			$data[$i]['path']	= phoB::delSlash($path.'/'.$entry);
			$data[$i]['dir']	= false;
			$data[$i]['show_name']	= $entry;
		}

		return $data;
	}

	public function parse() {
		if ($this->header_url != '') {
			header($this->header_url);
			return null;
		}elseif($this->thumbnail != '') {
			return $this->thumbnail;
		}else{
			return $this->template();
		}
	}

	protected function template() {

		$skin_url = $this->config['url_base'].'/'.$this->config['dir_skins'].'/'.$this->config['main_skin'];

		$this->setVar('skin_url', $skin_url);
		$this->setVar('site_url', $this->config['url_main']);
		$this->setVar('site_name', $this->config['main_site_name']);

		$path_item[] = array('link' => $this->config['url_browse']."list/", 'name' => $this->__('kořenový adresář'));
		$title_path = $this->__('kořenový adresář')." » ";

		if($this->path != '' && $this->scan_error == false)
			foreach ($this->data['folders'] as $dir_array)
			{
				$path_item[] = array('link' => $this->config['url_browse']."list/".$dir_array['path'], 'name' => $dir_array['name']);
				$title_path .= $dir_array['name']." » ";
			}

		switch($this->action) {
		case 'preview':

			$label = '';
			$title_path .= $this->name;
			$link = phoB::delSlash($this->config['server_path'].'/'.$this->config['dir_data'].'/'.$this->path).'/'.$this->name;
			$url_link = phoB::delSlash($this->config['url_base'].'/'.$this->config['dir_data'].'/'.$this->path).'/'.$this->name;

			if(file_exists($link)) {

				$path = phoB::delSlash($this->config['dir_data'].'/'.$this->path).'/comments.phob';
				if( file_exists($path) ) {
					$label = $this->readComment( $path, $this->name );
					$this->setVar('label', $this->__('Komentář: ').$label);
				}
				$this->setVar('setLabel', !empty($label));

				if($this->is_login){
					$this->setVar('set_label', "
					<form action=\"".phoB::delSlash($this->config['url_browse']."admin/save/".$this->path)."/".$this->name."\" method=\"post\">
					<input type=\"text\" name=\"label\" id=\"label\" value=\"$label\"/>
					<input type=\"submit\" value=\"".$this->__('Uložit popis')."\" />
					</form>");
				}

				if($this->getimg($this->id, -1) != ''){
					$this->setVar('left_thumb', true);
					$this->setVar('lt_link', phoB::delSlash($this->config['url_browse']."preview/".$this->path)."/".$this->getImg($this->id, -1));
					$this->setVar('lt_name', $this->__('Předchozí fotka'));
					$this->setVar('lt_img_link', phoB::delSlash($this->config['url_browse']."thumbnail/".$this->path)."/".$this->getImg($this->id, -1));
				}else{
					$this->setVar('left_thumb', false);
				}

				if($this->getimg($this->id, 1) != ''){
					$this->setVar('right_thumb', true);
					$this->setVar('rt_link', phoB::delSlash($this->config['url_browse']."preview/".$this->path)."/".$this->getImg($this->id, 1));
					$this->setVar('rt_name', $this->__('Následující fotka'));
					$this->setVar('rt_img_link', phoB::delSlash($this->config['url_browse']."thumbnail/".$this->path)."/".$this->getImg($this->id, 1));
				}else{
					$this->setVar('right_thumb', false);
				}

				$this->setVar('link', $url_link);
				$this->setVar('exists', true);
			}else{
				$this->setVar('exists', false);
			}
		break;
		case 'list':
			$items = array();
			foreach($this->data['list'] as $record) {
				if($record['dir']) {
					$items[] = array( 'link' => $this->config['url_browse']."list/".$record['path'],
									  'type' => 'folder',
									  'name' => $record['show_name'],
									  'img_link' => $skin_url."/folder.png");
				} else {
					$items[] = array( 'link' => phoB::delSlash($this->config['url_browse']."preview/".$this->path)."/".$record['name'],
									  'type' => 'photo',
									  'name' => '',
									  'img_link' => phoB::delSlash($this->config['url_browse']."thumbnail/".$this->path)."/".$record['name']);
				}
			}
			if($this->scan_error){
				$this->setVar('exists', false);
			}else{
				$this->setVar('exists', true);
			}
			$this->setVar('items', $items);
		break;
		case 'admin':
			$this->setVar('title_path', $this->__('Administrace'));
			if($this->is_login) {
				$this->setVar('admin', $this->__('Jste přihlášeni - je povolena editace popisků fotek.').'<br />'.
											$this->__('Pokračujte na').' <a href="'.$this->config['url_browse'].'list">'.
											$this->__('fotogalerii').'</a>.<br />'.
											$this->__('Po dokončení editace popisků se').' <a href="'.$this->config['url_browse'].'admin/logout">'.
											$this->__('odhlašte').'</a>.');
			}else{
				$this->setVar('admin', "<form action=\"".$this->config['url_browse']."admin/login\" method=\"post\">".
											"<input type=\"text\" name=\"nick\" /><br />".
											"<input type=\"password\" name=\"pass\"><br /><input type=\"submit\" value=\"".$this->__('Přihlásit')."\"/></form>");
			}
		break;
		case 'random':
			$this->setVar('url', phoB::delSlash($this->config['absolute_url'].$this->config['dir_data'].'/'.$this->path).'/'.$this->name);
			$this->setVar('preview_url', phoB::delSlash($this->config['absolute_url'].$this->config['parametr']."preview/".$this->path)."/".$this->name);
			$this->setVar('thumbnail_url', phoB::delSlash($this->config['absolute_url'].$this->config['parametr']."thumbnail/".$this->path)."/".$this->name);
		break;
		}

		$this->setVar('path_item', $path_item);
		$this->setVar('title_path', $title_path);

		return $this->fetchTemplate();

	}
}
?>