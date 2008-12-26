<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.6.5
 * @link        http://phob.skrasek.com
 * @package     Phob
 */


class Phob
{


	/** @var array */
	public $lang = array();

	/** @var string */
	public $skins;

	/** @var string */
	public $photos;

	/** @var string */
	public $thumbs;

	/** @var array */
	public $config = array(
		'siteName' => 'PhotoBrowser',
		'siteSkin' => 'default',
		'showDirup' => true
	);

	/** @var string */
	protected $root;

	/** @var array */
	protected $router = array(
		'action' => 'list',
		'path' => '',
		'name' => ''
	);

	/** @var array */
	protected $items = array();



	/**
	 * Constructor
	 * @return  void
	 */
	public function __construct()
	{
		$root = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
		if (empty($root))
			$this->root = '/';
		else
			$this->root = "/$root/";
	}


	/**
	 * Returns gallery output
	 * @return  string
	 */
	public function render()
	{
		$this->photos = trim($this->photos, '/');
		$action = $this->route();
		if ($action == 'preview') {
			$this->preview();
		} else {
			$this->scan();
			if ($action == 'list')
				return $this->index();
			else
				return $this->view();
		}
	}


	/**
	 * Routes applicaiton
	 * @return  string    action
	 */
	private function route()
	{
		$url = (!empty($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '/') ? $_SERVER['PATH_INFO'] : 'list';
		$url = explode('/', trim($url, '/'));
		foreach ($url as & $u)
			$u = urldecode($u);

		if (in_array($url[0], array('view', 'preview', 'list')))
			$this->router['action'] = array_shift($url);
		else
			return $this->error('Directory doesn\'t exists.');

		if (in_array($this->router['action'], array('view', 'preview')))
			$this->router['name'] = array_pop($url);

		$this->router['path'] = $url;
		$this->router['path_full'] = implode('/', $url);
		return $this->router['action'];
	}


	/**
	 * Scans direcotry
	 * @return  void
	 */
	private function scan()
	{
		$scan = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->photos}/{$this->router[path_full]}";
		if (!is_dir($scan))
			return $this->items = false;

		$dirs = array();
		$photos = array();
		$folder = new DirectoryIterator($scan);

		$alias = array();
		if ($this->router['action'] == 'list' && is_file($scan . 'alias.txt'))
			$alias = $this->readData($scan . 'alias.txt');

		foreach ($folder as $file) {
			$name = $file->getFileName();

			if ($name == '.' || ($name === '..' && (empty($this->router['path']) || !$this->config['showDirup'])))
				continue;

			if ($file->isDir()) {
				if ($name == '..') {
					$upPath = $this->router['path'];
					array_pop($upPath);
					$upPath = implode('/', $upPath);

					$dirs[$name] = array(
						'type' => 'dir',
						'name' => $this->__('Nahoru [..]'),
						'path' => "{$this->root}list/$upPath"
					);
				} else {
					$dirs[$name] = array(
						'type' => 'dir',
						'name' => !empty($alias[$name]) ? $alias[$name] : $name,
						'path' => "{$this->root}list/" . trim("{$this->router[path_full]}/$name", '/')
					);
				}
			} elseif (preg_match("#\.jpe?g$#i", $name)) {
				$photos[$name] = array(
					'type' => 'photo',
					'name' => $name,
					'path' => "{$this->root}view/" . trim("{$this->router[path_full]}/$name", '/'),
					'thumb' => "{$this->root}preview/" . trim("{$this->router[path_full]}/$name", '/')
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
	private function index()
	{
		$this->setTree();

		if ($this->items !== false)
			$this->set('photos', $this->items);
		else
			return $this->error('Directory doesn\'t exists.');
		return $this->renderTemplate('list');
	}


	/**
	 * Renders photo template
	 * @return  string
	 */
	private function view()
	{
		$this->setTree();

		$image = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->photos}/{$this->router[path_full]}/{$this->router[name]}";
		if (!file_exists($image))
			return $this->error('Photo doesn\'t exists.');

		$photoUrl = "{$this->root}{$this->photos}/" . trim("{$this->router[path_full]}/{$this->router[name]}", '/');
		$this->set('photoUrl', $photoUrl);

		$comment = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->photos}/{$this->router[path_full]}/comments.txt";
		if (file_exists($comment))
			$data = $this->readData($comment);
		else
			$data = array();

		$this->set('data', $data);
		if (isset($data[$this->router['name']]))
			$this->set('label', $data[$this->router['name']]);

		$this->set('next', $this->getPhoto());
		$this->set('prev', $this->getPhoto(false));
		return $this->renderTemplate('view');
	}


	/**
	 * Sends redirect header
	 * @return  string
	 */
	private function error($var)
	{
		header('HTTP/1.1 404 Not Found');
		die("<h1>Error: Page wasn't found</h1>$var");
		exit;
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
		while ($this->router['name'] != key($this->items))
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
			'path' => "{$this->root}list"
		));

		$path = '';
		foreach ($this->router['path'] as $dir) {
			$path .= "$dir/";
			$dirTree[] = array(
				'name' => $dir,
				'path' => "{$this->root}list/$path"
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

		$thumb = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->thumbs}/" . md5($this->router['path_full'] . $this->router['name']) . ".jpeg";
		if (file_exists($thumb)) {
			header('Content-type: image/jpeg');
			readfile($thumb);
			exit;
		}


		$img = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->photos}/{$this->router[path_full]}/{$this->router[name]}";
		if (!file_exists($img))
			die($this->__('Fotografie neexistuje!'));

		$thumbnail = false;
		if (function_exists('exif_thumbnail')) {
			header('Content-type: image/jpeg');
			$thumbnail = exif_thumbnail($img);
			file_put_contents($thumb, $thumbnail);
			echo $thumbnail;
			exit;
		}

		if ($thumbnail === false) {
			if (class_exists('Imagick')) {
				$im = new Imagick($img);
				$thumbnail = $im->clone();
				$thumbnail->thumbnailImage(160, 120, true);
				$thumbnail->writeImage($thumb);
			} elseif (function_exists('imagecreatefromjpeg')) {
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
			} else {
				die('Neni k dispozici zadna funkce pro generovani nahledu. (GD, Imagick, Exif)');
			}
			header('Content-type: image/jpeg');
			readfile($thumb);
			exit;
		}
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
		$this->set('siteName', $this->config['siteName']);
		$this->set('css', "{$this->root}{$this->skins}/{$this->config[skinName]}/");
		extract($this->vars);
		$template = dirname($_SERVER['SCRIPT_FILENAME']) . "/{$this->skins}/{$this->config[skinName]}/$name.phtml";

		if (file_exists($template))
			require $template;
		else
			echo "Missing template '$template'!";

		return ob_get_clean();
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

}