<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Index des vignettes de SPIP</title>
	<link rel="up" href="../">
</head>
<body style="background: #fff; text-align: center;">
<h1>Index des vignettes de SPIP</h1>
<?php
$files = array_merge(glob(__DIR__ . '/*.svg'), glob(__DIR__ . '/*.png'));
sort($files);
?>
<h2><?=count($files) ?> Icones au format SVG</h2>
<div class="thumbnails">
	<?php
	foreach ($files as $file) {
		$file = substr($file, strlen(__DIR__) + 1);
		$extension = substr($file, -3);
		$r = "\n\t<figure class='ext-$extension'><img src='$file' alt='$file' /><figcaption>$file</figcaption></figure>";
		echo $r;
	}
	?>
</div>
<style type="text/css">
	.thumbnails {
		display: flex;
		flex-wrap: wrap;
	}
	.thumbnails figure {
		width: 6rem;
		margin: 0.5rem;
		padding: 0.5rem;
		border: 1px solid #eee;
		overflow: hidden;
	}
	.ext-png {
		background: #888;
		opacity: 0.5;
	}
	.thumbnails figcaption {
		margin-top: 0.5rem;
		font-weight: bold;
	}
	.thumbnails figure img {
		width:52px;
		max-width: 100%;
		height: auto;
	}
	.thumbnails figure:hover,
	.thumbnails figure:focus {
		background: #f4f4f4;
		overflow: visible;
		opacity: 1;
	}

</style>
</body>
</html>