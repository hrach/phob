<?php
/**
 * PhoB - Photo Browser - written in PHP5
 * @author Jan Skrasek <hrach.cz(at)gmail(dot)com>
 * @version 0.2.0
 * @copyright Lesser GNU Public License (LGPL)
 */

class photoBrowser {
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

	static function noQuery($s) {
		return substr($s, 0, strpos($s, '?')-1);
	}

	static function getUrl($show_port = false) {

		if($_SERVER['HTTPS']) {
			$my_url = 'https://';
		} else {
			$my_url = 'http://';
		}
		$my_url .= $_SERVER['HTTP_HOST'];
		if($show_port) {
			$my_url .= ':' . $_SERVER['SERVER_PORT'];
		}
		$my_url .= $_SERVER['SCRIPT_NAME'];

		return dirname($my_url).'/';
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

	protected function isImg($string) {
		$ext = strtolower(substr($string, strrpos($string, '.')+1));
		if (in_array($ext, $this->config['allowed_ext'])) return true;
		return false;
	}

	protected function getimg($i, $direction) {
		$data = $this->data['list'];
		if(($i+$direction) < 1 || ($i+$direction) > (count($data))) return null;
		if($data[$i+$direction]['dir']) return $this->getimg($i+$direction, $direction);
		return $data[$i+$direction]['name'];
	}

	protected function __($string) {
		$translate = $this->lang[$string];
		if($translate != '') return $translate;
		return $string;
	}

	function __construct($config)	{
		include_once('btemplate.class.php');

		if (!is_array($config)) Die(__('Špatná konfigurace!'));
		
		$config['absolute_url'] = photoBrowser::getUrl();
		if(photoBrowser::noQuery(basename($GLOBALS['PHP_SELF'])) != 'index.php')
			$config['absolute_url_base'] .= photoBrowser::noQuery(basename($GLOBALS['PHP_SELF']));
		$config['absolute_server_path'] = dirname(__FILE__); 

		if(strlen($this->config['parametr_data'])<1) $this->config['parametr_data'] = 'list';
		if($this->config['absolute_url'] !== '') $this->config['absolute_url'] = photoBrowser::delSlash($this->config['absolute_url']).'/';
		
		$this->config = $config;
		$this->parametr = $this->config['parametr_data'];
		$this->parseParametr();
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
			$this->sub_get = photoBrowser::delSlash($reg[2]);
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
			if(photoBrowser::isDir($entry['name'])) {
				$this->path = $entry['path'];
				$ret = array_merge($ret, $this->dir_list($entry['path']));
			} else {
				$ret[] = array('name' => $entry['name'], 'path' => photoBrowser::delSlash($path.'/'));
			}
		}
		return $ret;
	}

	protected function admin() {

		if($this->sub_get == 'login' && $_POST['nick'] == $this->config['admin_nick'] && $_POST['pass'] == $this->config['admin_pass']) {

			session_start();
			$_SESSION['nick']	= $_POST['nick'];
			$_SESSION['pass']	= md5($_POST['pass']);
			$this->header_url = 'Location: '.$this->config['absolute_url'].$this->config['parametr'].'admin';

		}elseif($this->sub_get == 'logout'){

			$_SESSION = array();
			session_destroy();
			$this->header_url = 'Location: '.$this->config['absolute_url'].$this->config['parametr'].'list';

		}elseif(ereg("^save/(.*)/(.*)$", $this->sub_get, $reg)){

			if(!function_exists('dba_open')) {
				$error = $this->__('Hosting nepodporuje rozšíření DBA, návrat <a href="'.$this->config['absolute_url'].$this->config['parametr'].'preview/'.$reg[1].'/'.$reg[2].'">zpět</a>.');
				die($error);
			}
			$db_id = dba_open(photoBrowser::delSlash($this->config['dir_data'].'/'.$reg[1]).'/'.'info.db', "c", $this->config['main_dba_handler']);
			dba_replace($reg[2], $_POST['label'], $db_id);
			dba_close($db_id);
			$this->header_url = 'Location: '.$this->config['absolute_url'].$this->config['parametr'].'preview/'.$reg[1].'/'.$reg[2];

		}elseif(ereg("^save/(.*)$", $this->sub_get, $reg)){

			if(!function_exists('dba_open')) {
				$error = $this->__('Hosting nepodporuje rozšíření DBA, návrat <a href="'.$this->config['absolute_url'].$this->config['parametr'].'preview/'.$reg[1].'/'.$reg[2].'">zpět</a>.');
				die($error);
			}
			$db_id = dba_open($this->config['dir_data'].'/info.db', "c", $this->config['main_dba_handler']);
			dba_replace($reg[1], $_POST['label'], $db_id);
			dba_close($db_id);
			$this->header_url = 'Location: '.$this->config['absolute_url'].$this->config['parametr'].'preview/'.$reg[1];

		}
	}

	protected function thumbnail() {
		$thumb_path = $this->config['dir_thumb'].'/'.$this->path.'_'.$this->name;
		$thumb_exist = file_exists($thumb_path);

		header('Content-type: image/jpeg');

		if(!$thumb_exist) {

			$img_path = photoBrowser::delSlash($this->config['dir_data'].'/'.$this->path).'/'.$this->name;
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
		if(!isset($_SESSION['nick'])) $_SESSION['pass'] = '';
		if(($this->config['admin_nick'] == $_SESSION['nick']) && (md5($this->config['admin_pass']) == $_SESSION['pass']) )	return true;
		return false;
	}

	public function scan()
	{
		$data = array();
		$path = $this->path;
		$dirs = split('/', $path);

		$dir_now = '';

		foreach($dirs as $dir)
		{
			$dir_now = photoBrowser::delSlash($dir_now.'/'.$dir);
			$folders[] = array('name' => $dir, 'path' => $dir_now);
		}

		$this->data['folders'] = $folders;

		if(($files = @scandir($this->config['absolute_server_path'].$this->config['dir_data'].'/'.$path)) == false)
			$this->scan_error = true;
		if(!is_array($files)) $files = array();

		$i = 0;
		foreach($files as $entry)
		{
			if(!photoBrowser::isDir($entry)) continue;
			if($entry == '.' || ($path == '' && $entry == '..') || (!$this->config['main_show_dirup'] && $entry == '..')) continue;

			$i++;
			$data[$i]['name']	= $entry;
			$data[$i]['path']	= photoBrowser::delSlash($path.'/'.$entry);
			$data[$i]['dir']		= true;
			$data[$i]['show_name']	= $entry;

			if($data[$i]['name']=='..')
			{

				$new_path = null;

				$adr = split('/', $data[$i]['path']);
				for($j=0; $j<count($adr)-2; $j++)
				{
					$new_path .= '/'. $adr[$j];
				}

				$data[$i]['path'] = photoBrowser::delSlash($new_path);
				$data[$i]['show_name'] = $this->__('Nahoru');
			}
			else
			{
				$data[$i]['show_name'] = $data[$i]['name'];
			}
		}

		foreach($files as $entry)
		{
			if(photoBrowser::isDir($entry) || !photoBrowser::isImg($entry)) continue;

			$i++;
			if($this->name == $entry) $this->id = $i;
			$data[$i]['name']	= $entry;
			$data[$i]['path']	= photoBrowser::delSlash($path.'/'.$entry);
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

	protected function template()	{
		
		$tpl = new bTemplate();
		
		$skin_url = $this->config['absolute_url'].$this->config['dir_skins'].'/'.$this->config['main_skin'];
		$tpl->set('skin_url', $skin_url);
		$tpl->set('site_name', $this->config['main_site_name']);
		$tpl->set('site_url', $this->config['absolute_url_base']);

		$path_item[] = array('link' => $this->config['absolute_url'].$this->config['parametr']."list/", 'name' => $this->__('kořenový adresář'));
		$title_path = $this->__('kořenový adresář').$this->config['path_char'];

		if($this->path != '' && $this->scan_error == false)
			foreach ($this->data['folders'] as $dir_array)
			{
				$path_item[] = array('link' => $this->config['absolute_url'].$this->config['parametr']."list/".$dir_array['path'], 'name' => $dir_array['name']);
				$title_path .= $dir_array['name'].$this->config['path_char'];
			}

		switch($this->action) {
		case 'preview':

			$label = '';
			$title_path .= $this->name;
			$link = photoBrowser::delSlash($this->config['absolute_url'].$this->config['dir_data'].'/'.$this->path).'/'.$this->name;
			if(file_exists($link)) {

				if(function_exists('dba_open') && file_exists(photoBrowser::delSlash($this->config['dir_data'].'/'.$this->path).'/info.db')) {
					$db_id = dba_open(photoBrowser::delSlash($this->config['dir_data'].'/'.$this->path).'/info.db', "r", $this->config['main_dba_handler']);
					$label = dba_fetch($this->name, $db_id);
					dba_close($db_id);
					
					if($label <> ''){
						$tpl->set('is_label', true, true);
					}else{
						$tpl->set('is_label', false, true);
					}
				}

				if($this->is_login){
					$tpl->set('set_label', "<form action=\"".photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."admin/save/".$this->path)."/".$this->name."\" method=\"post\">"
											."<input type=\"text\" name=\"label\" id=\"label\" value=\"$label\"/><input type=\"submit\" value=\"".$this->__('Uložit popis')."\" /></form>");
				}

				if($this->getimg($this->id, -1) != ''){
					$tpl->set('left_thumb', true, true);
					$tpl->set('lt_link', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."preview/".$this->path)."/".$this->getimg($this->id, -1));
					$tpl->set('lt_name', $this->__('Předchozí fotka'));
					$tpl->set('lt_img_link', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."thumbnail/".$this->path)."/".$this->getimg($this->id, -1));
				}else{
					$tpl->set('left_thumb', false, true);
				}

				if($this->getimg($this->id, 1) != ''){
					$tpl->set('right_thumb', true, true);
					$tpl->set('rt_link', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."preview/".$this->path)."/".$this->getimg($this->id, 1));
					$tpl->set('rt_name', $this->__('Následující fotka'));
					$tpl->set('rt_img_link', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."thumbnail/".$this->path)."/".$this->getimg($this->id, 1));
				}else{
					$tpl->set('right_thumb', false, true);
				}

				$tpl->set('link', $link);
				$tpl->set('label', $this->__('Komentář: ').$label);
				$tpl->set('exists', true, true);
			}else{
				$tpl->set('exists', false, true);
			}
		break;
		case 'list':
			$items = array();
			foreach($this->data['list'] as $record)
			{
				if($record['dir'])
				{
					$items[] = array( 'link' => $this->config['absolute_url'].$this->config['parametr']."list/".$record['path'],
									  'type' => 'folder',
									  'name' => $record['show_name'],
									  'img_link' => $skin_url."/folder.png");
				}
				else
				{
					$items[] = array( 'link' => photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."preview/".$this->path)."/".$record['name'],
									  'type' => 'photo',
									  'name' => '',
									  'img_link' => photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."thumbnail/".$this->path)."/".$record['name']);
				}
			}
			if($this->scan_error){
				$tpl->set('exists', false, true);
			}else{
				$tpl->set('exists', true, true);
			}
			$tpl->set('items', $items);
		break;
		case 'admin':
			$tpl->set('title_path', $this->__('Administrace'));
			if($this->is_login) {
				$tpl->set('admin', $this->__('Jste přihlášeni - je povolena editace popisků fotek.').'<br />'.
											$this->__('Pokračujte na').' <a href="'.$this->config['absolute_url'].$this->config['parametr'].'list">'.
											$this->__('fotogalerii').'</a>.<br />'.
											$this->__('Po dokončení editace popisků se').' <a href="'.$this->config['absolute_url'].$this->config['parametr'].'admin/logout">'.
											$this->__('odhlašte').'</a>.');
			}else{
				$tpl->set('admin', "<form action=\"".$this->config['absolute_url'].$this->config['parametr']."admin/login\" method=\"post\">".
											"<input type=\"text\" name=\"nick\" /><br />".
											"<input type=\"password\" name=\"pass\"><br /><input type=\"submit\" value=\"".$this->__('Přihlásit')."\"/></form>");
			}
		break;
		case 'random':
			$tpl->set('url', photoBrowser::delSlash($this->config['absolute_url'].$this->config['dir_data'].'/'.$this->path).'/'.$this->name);
			$tpl->set('preview_url', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."preview/".$this->path)."/".$this->name);
			$tpl->set('thumbnail_url', photoBrowser::delSlash($this->config['absolute_url'].$this->config['parametr']."thumbnail/".$this->path)."/".$this->name);
		break;
		}
		
		$tpl->set('path_item', $path_item);
		$tpl->set('title_path', $title_path);
		return $tpl->fetch($this->config['absolute_server_path'].$this->config['dir_skins'].'/'.$this->config['main_skin'].'/'.$this->action.'.tpl.htm');

	}
}
?>