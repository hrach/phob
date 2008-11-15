<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.6
 * @link        http://phob.skrasek.com
 * @package     Phob
 */


class Phob
{


	private static $root;
	private static $base;
	private static $media;


	public $lang = array();

	private $action;
	private $path = array();
	private $name;
	private $config = array();
	private $items = array();
	private $vars = array();


	/**
	 * Constructor
	 * @param   array   configuration
	 * @return  void
	 */
	public function __construct($config)
	{
		$this->config = (array) $config;

		if ($this->config['mod_rewrite']) {
			self::$root = self::$media = self::$base = dirname($_SERVER['SCRIPT_NAME']);
			self::$base .= '/';
		} else {
			self::$root = self::$media = '/' . dirname(trim($_SERVER['SCRIPT_NAME'], '/'));
			self::$base = '/' . trim($_SERVER['SCRIPT_NAME'], '/') . '/';
		}

		self::$media .= '/' . $this->config['skins'] . '/' . $this->config['skin'];

		$this->set('siteName', $config['siteName']);
		$this->route();
	}


	/**
	 * Returns gallery render
	 * @return  string
	 */
	public function render()
	{
		switch ($this->action) {
		case 'view':
			$this->scan();
			return $this->view();
		case 'list':
			$this->scan();
			return $this->listDir();
		case 'error':
			return $this->error('Wrong url');
		case 'preview':
			$this->preview();
		}
	}


	/**
	 * Translate key
	 * @param   string  key
	 * @return  string
	 */
	private function __($key)
	{
		if (!empty($this->lang[$key]))
			return $this->lang[$key];
		else
			return $key;
	}


	/**
	 * Routing
	 * @return  void
	 */
	private function route()
	{
		$url = (!empty($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '/') ? $_SERVER['PATH_INFO'] : 'list';
		$url = urldecode(trim(str_replace('/..', '', $url), '/'));
		$url = explode('/', $url);

		if (in_array($url[0], array('view', 'preview', 'list')))
			$this->action = array_shift($url);
		else
			$this->action = 'error';

		if (in_array($this->action, array('view', 'preview')))
			$this->name = array_pop($url);

		$this->path = $url;
	}


	/**
	 * Scan direcotry
	 * @return  void
	 */
	private function scan()
	{
		$path = implode('/', $this->path);
		$scan = $this->factory('scan', $path);

		if (!file_exists($scan)) {
			$this->items = false;
			return;
		}



		$dirs = array();
		$photos = array();
		$folder = new DirectoryIterator($scan);

		$alias = array();
		if ($this->action == 'list' && is_file($scan . 'alias.txt'))
			$alias = $this->readData($scan . 'alias.txt');

		foreach ($folder as $file) {
			$name = $file->getFileName();

			if ($name == '.' || ($name === '..' && (empty($this->path) || !$this->config['dirup'])))
				continue;

			if ($file->isDir()) {
				if ($name == '..') {
					$upPath = $this->path;
					array_pop($upPath);
					$upPath = implode('/', $upPath);

					$dirs[$name] = array(
						'type' => 'dir',
						'name' => $this->__('Nahoru [..]'),
						'path' => self::$base . "?list/$upPath"
					);
				} else {
					$path   = implode('/', $this->path);
					$dirs[$name] = array(
						'type' => 'dir',
						'name' => !empty($alias[$name]) ? $alias[$name] : $name,
						'path' => $this->factory('dir', "$path/$name")
					);
				}
			} elseif (preg_match("#\.jpe?g$#i", $name)) {
				$photos[$name] = array(
					'type' => 'photo',
					'name' => $name,
					'path' => $this->factory('image', "$path/$name"),
					'thumb' => $this->factory('thumb', "$path/$name"),
				);
			}
		}

		ksort($dirs);
		ksort($photos);
		$this->items = array_merge($dirs, $photos);
	}


	/**
	 * Renders list template
	 * @return  string
	 */
	private function listDir()
	{
		$this->setTree();

		if ($this->items !== false)
			$this->set('photos', $this->items);
		else
			return $this->error("This directory doesn't exists.");

		return $this->renderTemplate('list');
	}


	/**
	 * Renders photo template
	 * @return  string
	 */
	private function view()
	{
		$this->setTree();

		$image = $this->factory('server-image');

		if (!file_exists($image))
			return $this->error('Požadovaná fotografie neexistuje!');

		$this->set('photoUrl', $this->factory('image-view'));

		$comment = $this->factory('comment');
		if (file_exists($comment))
			$data = $this->readData($comment);
		else
			$data = array();

		$this->set('data', $data);
		if (isset($data[$this->name]))
			$this->set('label', $data[$this->name]);

		$this->set('next', $this->getPhoto());
		$this->set('prev', $this->getPhoto(false));
		return $this->renderTemplate('view');
	}


	/**
	 * Renders error template
	 * @param   string     error message
	 * @return  string
	 */
	private function error($message)
	{
		header('HTTP/1.1 404 Not Found');

		$this->set('message', $this->__($message));
		return $this->renderTemplate('error');
	}


	/**
	 * Returs next/previou photo
	 * @param   bool      next
	 * @return  mixed
	 */
	private function getPhoto($next = true)
	{
		$item = null;
		reset($this->items);
		while ($this->name != key($this->items))
			next($this->items);

		if ($next) {
			do {
				$item = next($this->items);
			} while ($item['type'] == 'dir' && is_array($item));
		} else {
			do {
				$item = prev($this->items);
			} while ($item['type'] == 'dir' && is_array($item));
		}

		if (is_array($item))
			return $item;
		else
			return false;
	}


	/**
	 * Saves dir tree for template
	 * @return  void
	 */
	private function setTree()
	{
		$dirTree = array(array(
			'name' => $this->__('Kořenový adresář'),
			'path' => $this->factory('dir', '')
		));

		$path = '';
		foreach ($this->path as $dir) {
			$path .= "$dir/";
			$dirTree[] = array(
				'name' => $dir,
				'path' => $this->factory('dir', $path)
			);
		}

		$this->set('dirTree', $dirTree);
	}


	/**
	 * Renders photo thumbnail
	 * @return  void
	 */
	private function preview()
	{
		header('Content-type: image/jpeg');

		$path = implode('/', $this->path);
		$thumb = $this->factory('server-thumb');

		if (!file_exists($thumb)) {
			$img = $this->factory('server-image');

			$thumbnail = exif_thumbnail($img);

			if ($thumbnail == false) {
				if (class_exists('Imagick')) {
					$im = new Imagick($img);
					$thumbnail = $im->clone();
					$thumbnail->thumbnailImage(160, 120, true);
					$thumbnail->writeImage($thumb);

				} else {

					$old = imagecreatefromjpeg($img);
					$old_x = imagesx($old);
					$old_y = imagesy($old);
					if ($old_y > $old_x) {
						$k = $old_y / 120;
						$new_y = 120;
						$new_x = floor($old_x / $k);
					} else {
						$k = $old_x / 160;
						$new_x = 160;
						$new_y = floor($old_y / $k);
					}

					$nahled = imagecreatetruecolor($new_x, $new_y);
					imagecopyresized($nahled, $old, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);
					imagejpeg($nahled, $thumb);
					imagedestroy($nahled);

				}
				readfile($thumb);
			} else {
				file_put_contents($thumb, $thumbnail);
				echo $thumbnail;
			}
		} else {
			readfile($thumb);
		}
		exit;
	}


	/**
	 * Parses configs files
	 * @param   string  file path
	 * @return  array
	 */
	public static function readData($file)
	{
		$array = array();
		$data = file($file);

		foreach ($data as $line) {
			if (preg_match('#^(.+)(?::\s|\t)(.+)$#', $line, $match)) {
				$array[$match[1]] = $match[2];
			}
		}

		return $array;
	}



	/**
	 * Saves param for template
	 * @param   string  var name
	 * @param   mixed   value
	 * @return  void
	 */
	private function set($var, $val)
	{
		$this->vars[$var] = $val;
	}


	/**
	 * Renders template
	 * @param   string  template name
	 * @return  string
	 */
	private function renderTemplate($name)
	{
		extract($this->vars);
		$template = $this->factory('template', $name);

		if (file_exists($template))
			require $template;
		else
			echo "Missing template '$template'!";

		return ob_get_clean();
	}


	/**
	 * Creates url/path
	 * @param   string  type url
	 * @param   string  url
	 * @return  string
	 */
	private function factory($type, $url = null)
	{
		switch ($type) {
		case 'scan':
			$url = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->config['photos'] . "/$url";
		break;
		case 'dir':
			$url = self::$base . "list/$url";
		break;
		case 'image':
			$url = self::$base . "view/$url";
		break;
		case 'image-view':
			$url = self::$root . '/' . $this->config['photos'] . '/' . implode('/', $this->path) . '/' . $this->name;
		break;
		case 'thumb':
			$url = self::$base . "preview/$url";
		break;
		case 'template':
			$url = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->config['skins'] . '/' . $this->config['skin'] . "/$url.phtml";
		break;
		case 'server-thumb':
			$url = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->config['thumbs'] . '/' . md5(implode('/', $this->path) . '/' . $this->name) . '.jpeg';
		break;
		case 'server-image':
			$url = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->config['photos'] . '/' . implode('/', $this->path) . '/' . $this->name;
		break;
		case 'comment':
			$url = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->config['photos'] . '/' . implode('/', $this->path) . '/comments.txt';
		break;
		}

		return preg_replace('#\/+#', '/', $url);
	}


}