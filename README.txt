PHOB
version 0.2.0

author Jan Skrasek
email hrach.cz@gmail.com
web http://hrach.netuje.cz

licenced by LGPL, read license.txt

Instalation, how to use
=============================================

1) unzip to new dir
2) you have this tree

  /.htaccess
  /btemplate.class.php
  /phob.class.php
  /phob.php
  /random.php
  /readme.txt
  /license.txt
  /skins/
	  ....
  /thumbs/
      ....
  /photos/
      ....
	 
3) you can delete folder "expales" and files "readme.txt" and "license.txt"
4) set the permisons for folder "thumbs" on 0777
5) your administration is on url /index.php?url=admin or /admin (<-if you use mod_rewite)
6) at this moment phob is ready for use. If you want "nice path" countinue reading:
	
	phob generate "friendly url", so you can use mod_rewrite for nice path as is
	   http://example.com/photos/preview/yourdir/yourphoto.jpeg
	   
	instead
	   http://example.com/photos/phob.php?q=preview/yourdir/yourphoto.jpeg
	   
	if you want use this, you must exactly configurate phob in file phob.php
	use the comments for help