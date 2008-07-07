<?php

/**
 * PHOB - photo browser
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5.5
 * @link        http://phob.php5.cz
 * @package     PhoB
 */



if (version_compare(PHP_VERSION, '5.0', '<')) {
    die('PhoB needs PHP 5 or above!!!');
}


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
    private $num = -1;


    /**
     * Konstruktor
     * @param   array   konfigurace
     * @return  void
     */
    public function __construct($config)
    {
        $this->config = $config;

        if ($config['mod_rewrite']) {
            self::$root = self::$media = self::$base = dirname($_SERVER['PHP_SELF']);
        } else {
            self::$root = self::$media = '/' . dirname(trim($_SERVER['PHP_SELF'], '/'));
            self::$base = '/' . trim($_SERVER['PHP_SELF'], '/');
        }

        self::$media .= '/' . $this->config['skins'] . '/' . $this->config['skin'];

        $this->set('siteName', $config['siteName']);
        $this->route();
    }


    /**
     * Vrati vystup galerie
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
            return $this->error("Špatný tvar url");
        case 'preview':
            $this->preview();
        }
    }


    /**
     * Prelozi klic
     * @param   string  klic
     * @return  string
     */
    private function __($key)
    {
        if (isset($this->lang[$key])) {
            return $this->lang[$key];
        } else {
            return $key;
        }
    }


    /**
     * Provede routing
     * @return  void
     */
    private function route()
    {
        $url = trim(str_replace('/..', '', !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'list'), '/');
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
     * Proslenuje adresar
     */
    private function scan()
    {
        $path = implode('/', $this->path);
        $scan = $this->factory('scan', $path);

        if (!file_exists($scan)) {
            $this->items = false;
            return;
        }


        $dirs   = array();
        $photos = array();
        $folder = new DirectoryIterator($scan);

        foreach ($folder as $file) {
            $name = $file->getFileName();

            if ($name == '.' || ($name === '..' && (empty($this->path) || !$this->config['dirup'])))
                continue;


            if ($file->isDir()) {
                if ($name == '..') {
                    $upPath = $this->path;
                    array_pop($upPath);
                    $upPath = implode('/', $upPath);

                    $dirs[] = array(
                        'type' => 'dir',
                        'name' => $this->__('Nahoru [..]'),
                        'path' => self::$base . "?list/$upPath"
                    );
                } else {
                    $path   = implode('/', $this->path);
                    $dirs[] = array(
                        'type' => 'dir',
                        'name' => $name,
                        'path' => $this->factory('dir', "$path/$name")
                    );
                }
            } elseif (preg_match("#\.jpe?g$#i", $name)) {
                $photos[] = array(
                    'type' => 'photo',
                    'name' => $name,
                    'path' => $this->factory('image', "$path/$name"),
                    'thumb' => $this->factory('thumb', "$path/$name"),
                );
            }
        }


        $this->items = array_merge($dirs, $photos);

        foreach ($this->items as $i => $item) {
            if ($this->name == $item['name']) {
                $this->num = $i;
                break;
            }
        }
    }


    /**
     * Vygenruej view sablonu seznamu
     * @return  string
     */
    private function listDir()
    {
        $this->setTree();

        if ($this->items !== false)
            $this->set('photos', $this->items);
        else
            return $this->error("Požadovaný adresář neexistuje!");

        return $this->renderTemplate('list');
    }

    
    /**
     * Vygenruje view sablonu fotky
     * @return  string
     */
    private function view()
    {
        $this->setTree();

        $image = $this->factory('server-image');

        if (file_exists($image)) {
            $this->set('photoUrl', $this->factory('image-view'));

            $comment = $this->factory('comment');
            if (file_exists($comment))
                $data = $this->readData($comment);
            else
                $data = array();

            $this->set('data', $data);
            if (isset($data[$this->name]))
                $this->set('label', $data[$this->name]);

            $this->set('next', $this->getPhoto($this->num, +1));
            $this->set('prev', $this->getPhoto($this->num, -1));
        } else {
            return $this->error('Požadovaná fotografie neexistuje!');
        }

        return $this->renderTemplate('view');
    }



    /**
     * Vyrenderuje chybonu sablonu
     * @param    string    chybova zprava
     * @return    string
     */
    private function error($message)
    {
        header('HTTP/1.1 404 Not Found');

        $this->set('message', $this->__($message));
        return $this->renderTemplate('error');
    }



    /**
     * Vrati prechozi/nasledujici fotku
     * @param   int     cislo aktualni fotky
     * @param   int     smer postupu hledani
     * @return  mixed
     */
    private function getPhoto($i, $direction)
    {
        if (!isset($this->items[$i + $direction]))
            return false;

        if ($this->items[$i + $direction]['type'] == 'dir')
            return $this->getPhoto($i + $direction, $direction);

        return $this->items[$i + $direction];
    }


    /**
     * Ulozi pole s stromovou cestou
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
     * Vypise na vystup nahled fotky
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
     * Preparsuje yaml soubor (jen primitivni syntaxe!)
     * @param   string  cesta k souboru
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
     * Ulozi promennou do sablony
     * @param   string  jmeno promenne
     * @param   mixed   hodnota
     * @return  void
     */
    private function set($var, $val)
    {
        $this->vars[$var] = $val;
    }


    /**
     * Vyrenderuje sablonu
     * @param   string  jmeno sablony
     * @return  string
     */
    private function renderTemplate($name)
    {
        extract($this->vars);
        $template = $this->factory('template', $name);

        if (file_exists($template))
            require $template;
        else
            echo "Chybi sablona $template!";

        return ob_get_clean();
    }


    /**
     * Vytvori url
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
            $url = self::$base . "?list/$url";
        break;
        case 'image':
            $url = self::$base . "?view/$url";
        break;
        case 'image-view':
            $url = self::$root . '/' . $this->config['photos'] . '/' . implode('/', $this->path) . '/' . $this->name;
        break;
        case 'thumb':
            $url = self::$base . "?preview/$url";
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