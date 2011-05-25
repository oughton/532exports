<?php
$filename = $_GET['filename'];

// TODO: very very insecure...
$fh = fopen("../public/" . $filename, 'w') or die("can't open file");

fwrite($fh, json_encode($_POST['nodes']));
fclose($fh);
?>

