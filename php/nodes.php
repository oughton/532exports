<?php
if (($handle = fopen("../datasets/nodes.csv", "r"))!== FALSE){
    $count=0;
    while(($data = fgetcsv($handle,1000,",")) !== FALSE){
        $count+=1;
        $dst = array();
        for ($i=1; $i < count($data); $i++){
            $dst[] = $data[$i];
        }   

        if (substr($data[0],0,2) != "\\\\") $output[$data[0]] = $dst;
    }
    fclose($handle);
    echo json_encode($output);
}
?>
