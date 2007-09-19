<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="<?php echo $skin_url ?>/style.css" type="text/css" media="screen" />
	<!--[if lt IE 7]>
	<style type="text/css" media="screen">a.thumb {background: none;cursor:hand;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $skin_url ?>/item_white.png');}</style>
	<![endif]-->
	<title><?php echo $site_name ." :: ". $title_path ?> :: by PhoB</title>
</head>
<body>
<div id="container">
	<a href="<?php echo $site_url ?>"><h1><?php echo $site_name ?></h1></a>

	<div class="set_label"><?php echo $set_label ?></div>
	<div class="block_path block_top">
		<?php foreach($path_item as $level) { ?>
			» <a href="<?php echo $level['link'] ?>"><?php echo $level['name'] ?></a>
		<?php } ?>
	</div>

	<div id="main">
	<?php if($exists) { ?>
		<?php if($left_thumb) { ?>
		<a class="thumb" href="<?php echo $lt_link ?>" style="left: 0;">
			<img src="<?php echo $lt_img_link ?>" alt="náhled"/>
		</a>
		<?php } ?>

		<?php if($right_thumb) { ?>
		<a class="thumb" href="<?php echo $rt_link ?>" style="right: 0;">
			<img src="<?php echo $rt_img_link ?>" alt="náhled"/>
		</a>
		<?php } ?>

		<a href="<?php echo $link ?>">
			<img id="main_img" src="<?php echo $link ?>"/>
		</a>

		<?php if($setLabel) { ?>
			<div class="img_label"><?php echo $label ?></div>
		<?php } ?>
	<?php } else { ?>
		Hledaná fotka neexistuje!
	<?php } ?>
		<br style="clear: both;"/>
	</div>

	<br style="clear: left" /><br />
	</div>
</div>
</body>
</html>