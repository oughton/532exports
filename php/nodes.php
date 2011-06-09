<?php

class node {
}

if (($handle = fopen("../datasets/nodes.csv", "r"))!== FALSE){
    $count=0;
    while(($data = fgetcsv($handle,0,",")) !== FALSE){
        $count+=1;
        $dst = array();
        $n = new node();
        $n->parent = $data[1];
        for ($i=2; $i < count($data); $i++){
            $dst[] = $data[$i];
        }
        $n->children = $dst;

        if (substr($data[0],0,2) != "\\\\") $output[$data[0]] = $n;
    }
    fclose($handle);
    echo json_encode($output);
}
?>
