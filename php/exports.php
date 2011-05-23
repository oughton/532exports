<?php
$row = 0;

if (($handle = fopen("newzealand_exports.csv", "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);

        if ($row == 0) {
            $header = $data;
            $row += 1;
            continue;
        }
        
        for ($c=1; $c < $num; $c++) {
            //echo $data[$c] . "<br />\n";
            $output["years"][$header[$c]][$data[0]] = array("exports" => $data[$c]);
        }

        $row += 1;
    }

    fclose($handle);

    echo json_encode($output);
}
?>
