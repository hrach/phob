<?php

/**
 * PHOB - PHOTO BROWSER
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.2
 * @package    PhoB
 */


class Phob
{

	/** @var array $lang	*/
	public $lang = array();

	/** @var string $action */
	private $action;
	/** @var array $path */
	private $path = array();
	/** @var string $name */
	private $name;
	/** @var string $photoDir */
	private $photoDir;
	/** @var string $skinsDir */
	private $skinsDir;
	/** @var array $config */
	private $config = array();
	/** @var array $dirItems */
	private $dirItems = array();
	/** @var array $vars */
	private $vars = array();
	/** @var  integer $photoNum */
	private $photoNum = -1;


	public function __construct($config)
	{
		if (!is_array($config)) {
			die($this->__('Špatná konfigurace.'));
		}
		
		$this->photoDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $config['path']['photos'];
		$this->skinsDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $config['path']['skins'] . '/' . $config['main']['skin'];
		$this->route();

		$this->set('skinPath', dirname($_SERVER['PHP_SELF']) . '/' . $config['path']['skins'] . '/' . $config['main']['skin']);
		$this->set('siteName', $config['main']['site_name']);
		$this->set('siteUrl', dirname($_SERVER['PHP_SELF']));
		$this->config = $config;
	}

	public function render()
	{
		switch ($this->action) {
			case 'view':
				$this->scan();
				return $this->view();
				break;
			case 'list':
				$this->scan();
				return $this->listDir();
				break;
			case 'preview':
				$this->preview();
				break;
			case 'random':
				$random = $this->get_random($this->dir_list($this->sub_get));
				$this->path = $random['path'];
				$this->name = $random['name'];
				break;
		}
	}
	
	private function getBase()
	{
		if ($this->config['mod_rewrite']) {
			return dirname($_SERVER['PHP_SELF']) . '/';
		} else {
			if (basename($_SERVER['PHP_SELF']) === 'index.php') {
				return dirname($_SERVER['PHP_SELF']) . '/?url=';
			} else {
				return $_SERVER['PHP_SELF'] . '?url=';
			}
		}
	}

	private function __($key)
	{
		if (isset($this->lang[$key])) {
			return $this->lang[$key];
		} else {
			return $key;
		}
	}

	private function route()
	{
		$url = str_replace('/..', '', isset($_GET['url']) ? $_GET['url'] : '');
		$url = trim($url, '/');
		if (empty($url)) {
			$url = 'list';
		}

		$url = explode('/', $url);
		$allowed = array('view', 'preview', 'list', 'random');

		if (in_array($url[0], $allowed)) {
			$this->action = array_shift($url);
		}

		$withName = array('view', 'preview');
		if (in_array($this->action, $withName)) {
			$this->name = array_pop($url);
		}

		$this->path = $url;
	}

	private function scan()
	{
		$path = implode('/', $this->path);
		$scanPath = $this->photoDir . '/' . $path;
		if (!file_exists($scanPath)) {
			$this->dirItems = false;
			return;
		}

		$folder = new DirectoryIterator($scanPath);
		$photos = array();
		$dirs = array();
		$i = 0;

		foreach ($folder as $file) {
			$fileName = $file->getFileName();
			if ($file->isDir()) {
				if ($fileName === '.'
				|| ($fileName === '..' && !$this->config['main']['show_dirup'])
				|| ($fileName === '..' && empty($path))
				) {
					continue;
				}

				if ($fileName === '..') {
					$dPath = $this->path;
					array_pop($dPath);
					$dPath = implode('/', $dPath);

					$dirs[] = array(
						'type' => 'dir',
						'name' => $this->__('Nahoru [..]'),
						'path' => $this->getBase() . trim('list/' . $dPath)
					);
				} else {
					$dirs[] = array(
						'type' => 'dir',
						'name' => $fileName,
						'path' => $this->getBase() . trim('list/' . $path, '/') . '/' . $fileName
					);
				}
			} elseif(preg_match("/.jpe?g$/", strtolower($fileName))) {
				++$i;
				if ($this->name === $fileName) {
					$this->photoNum = $i;
				}

				$photos[] = array(
					'type' => 'photo',
					'name' => $fileName,
					'path' => $this->getBase() . trim('view/' . $path, '/') . '/' . $fileName,
					'thumb' => $this->getBase() . trim('preview/' . $path, '/') . '/' . $fileName,
				);
			}
		}
		
		$this->photosSum = $i;
		$this->dirItems = array_merge($dirs, $photos);
	}

	private function listDir()
	{
		$this->setTree();
		if ($this->dirItems !== false) {
			$this->set('exists', true);
			$this->set('photos', $this->dirItems);
		} else {
			$this->set('exists', false);
		}

		return $this->renderTemplate('list');
	}

	private function view()
	{
		$this->setTree();
		$path = $this->config['path']['photos'] . '/' . implode('/', $this->path) . '/' . $this->name;

		if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $path)) {
			$this->set('exists', true);
			$this->set('photoUrl', $path);

			$commentPath = $this->config['path']['photos'] . '/' . implode('/', $this->path) . '/comments.txt';
			if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $commentPath)) {
				$this->set('label', $this->readComment($commentPath, $this->name));
			}

			$this->set('next', $this->getPhoto($this->photoNum, +1));
			$this->set('prev', $this->getPhoto($this->photoNum, -1));
		} else {
			$this->set('exists', false);
		}

		return $this->renderTemplate('view');
	}

	private function getPhoto($i, $direction)
	{
		if (($i + $direction) < 1 || ($i + $direction) > $this->photosSum) {
			return false;
		}

		if ($this->dirItems[$i + $direction]['type'] === 'dir') {
			return $this->getPhoto($i + $direction, $direction);
		}
		return $this->dirItems[$i + $direction];
	}

	private function setTree()
	{
		$dirTree = array(
			array(
				'name' => $this->__('Kořenový adresář'),
				'path' => $this->getBase() . 'list/',
			)
		);

		$cache = 'list/';
		foreach ($this->path as $dir) {
			$cache .= $dir . '/';
			$dirTree[] = array(
				'name' => $dir,
				'path' => $this->getBase() . $cache,
			);
		}
		$this->set('dirTree', $dirTree);
	}

	private function preview()
	{
		header('Content-type: image/jpeg');

		$thumb_path = $this->config['path']['thumbs'] . '/' . md5(implode('/', $this->path) . '_' . $this->name) . '.jpeg';
		if (!file_exists($thumb_path)) {

			$img_path = $this->config['path']['photos'] . '/' . implode('/', $this->path) . '/' . $this->name;
			$thumbnail = exif_thumbnail($img_path);

			if ($thumbnail == false) {
				$old = imagecreatefromjpeg($img_path);

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
				imagejpeg($nahled, $thumb_path);
				imagedestroy($nahled);
				
				readfile($thumb_path);
				exit;
			}else{
				file_put_contents($thumb_path, $thumbnail);
				echo $thumbnail;
				exit;
			}
		} else {
			readfile($thumb_path);
			exit;
		}
	}

	private function readComment($path, $name)
	{
		$file = file($path);
		foreach ($file as $line => $data) {
			$rowName = substr($data, 0, strpos($data, '	'));
			if ($rowName == $name) {
				return substr($data, strpos($data, '	'));
			}
		}

		return '';
	}

	private function set($var, $val)
	{
		$this->vars[$var] = $val;
	}

	private function renderTemplate($name)
	{
		extract($this->vars);
		require $this->skinsDir . '/' . $name . '.phtml';
		return ob_get_clean();
	}

}