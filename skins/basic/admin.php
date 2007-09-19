<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="<?php echo $skin_url ?>/style.css" type="text/css" media="screen" />
	<!--[if lt IE 7]>
	<script defer type="text/javascript" src="<?php echo $skin_url ?>/pngfix.js"></script>
	<![endif]-->
	<title><?php echo $site_name ." :: ". $title_path ?> :: by PhoB</title>
</head>
<body>
<div id="container">
	<a href="<?php echo $site_url ?>"><h1><?php echo $site_name ?></h1></a>

	<div class="block_path block_top">
		<?php foreach($path_item as $level) { ?>
			» <a href="<?php echo $level['link'] ?>"><?php echo $level['name'] ?></a>
		<?php } ?>
	</div>

	<div id="main">

	<?php echo $admin ?>

	<br style="clear: left" /><br />
	</div>
</div>
</body>
</html>