<?php

$row = 0;

if (($handle = fopen("geodata.csv", "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $output[$data[0]] = array("lat" => $data[1], "lng" => $data[2]);
    }

    fclose($handle);

    echo json_encode($output);
}
?>
