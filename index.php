<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Export Vis</title>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>

<script language="javascript" type="text/javascript" src="js/jquery-1.5.2.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script language="javascript" type="text/javascript" src="js/exports.js"></script>
<script language="javascript" type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
</head>
<body>
<script type="text/javascript">

<?php
$saveFile = "nodesfile";
if (array_key_exists("s",$_GET)){
    $saveFile = $_GET["s"];
}
?>

$(document).ready(function() {
    var geocoder = new google.maps.Geocoder(),
        source = "New Zealand",
        sourceGeo,
        map,
        exports,
        totalExports,
        data = {},
        locations,
        selectedPivot = null,
        pivotPoint = null,
        pivotPoints = [],
        polyLines = [],
        lineWidth = 1,
        year = 2000,
        debug = true;

    $exports.buildJSON(source, function(d) {
        data = d;
        build_geo_table(data);

        $.getJSON('<?php echo "public/$saveFile"; ?>',function(loaded){
            data.nodes = loaded;
            fixData(data);

            var geocoder = new google.maps.Geocoder();
            
            geocoder.geocode({ address: source }, function(geo) {
                sourceGeo = geo[0].geometry.location;

                var opts = {
                  zoom: 2,
                  center: geo[0].geometry.location,
                  mapTypeId: google.maps.MapTypeId.ROADMAP
                }

                map = new google.maps.Map(document.getElementById("map_canvas"), opts);
                google.maps.event.addListener(map, 'rightclick', function(event){
                    if (selectedPivot != null){
                        // have a selected pivot move it.
                        movePivot(selectedPivot,nodeOf(event.latLng),data.nodes);
                        selectedPivot =null;
                    } else {
                        pivotPoint = addMarker(map, event.latLng);
                    }
                });

                var multiP1,multiP2;
                google.maps.event.addListener(map, 'click', function(event){
                    if (multiP1 != null){
                        var multiP2 = event.latLng;
                        alert('multi point 2');
                        console.log('selection line',nodeOf(multiP1),nodeOf(multiP2));

                        var tomove = Array();
                        traverse_nodes(nodeOf(geo[0].geometry.location),null, data.nodes,function(n1,n2){
                            // Dont change the data structure while crawling it!!! can make things vanish.
                            if (pivotPoint != null){
                                if (n2==undefined | n1 == undefined) console.log(n2,n1);
                                if (intersect(multiP1,multiP2,geoOfNode(n1),geoOfNode(n2))){
                                    tomove.push([n1,n2]);
                                    console.log('intersect',n2, n1, locations[n1]);
                                }
                            }
                        });
                        $.each(tomove, function(key,value){
                            addToPivot(nodeOf(pivotPoint.getPosition()),value[0]);
                        });


                        redraw();
                        multiP1 = null;
                        multiP2 = null;
                    } else {
                        multiP1 = event.latLng;
                        alert('multi point 1');
                    }
                });

                draw();
            });
        });
        
    });

    function draw() {
        exports = data.exports.years[year];
        totalExports = exports['(Total)'].exports;

        //from source node.
        var sourceNode = nodeOf(sourceGeo);
        var topQueue = data.nodes[sourceNode].children;
        $.each(topQueue, function(index, value){
            drawFromNode(value);
        });

        function drawFromNode(node){
            //returns the weight of what its done.
            var queue = data.nodes[node].children;
            var pathExports =0; //sum of value for weight.
            if (node in locations) {
                //draw
                var name = locations[node];
                pathExports = new Number(exports[name].exports);
            } else {
                $.each(queue, function(key,value){
                    pathExports += drawFromNode(value);
                });
            }

            var exportPath = new google.maps.Polyline({
                path: [geoOfNode(data.nodes[node].parent), geoOfNode(node)],
                strokeColor: "#FF0000",
                strokeOpacity: 1,
                strokeWeight: 0.1 + (pathExports / totalExports) * lineWidth,
                map: map
            });

            polyLines.push(exportPath);

            // drop markers again
            if (!locations[node]) {
                addMarker(map, geoOfNode(node));
            }

             google.maps.event.addListener(exportPath, 'click', function(event){
                  if (pivotPoint!=null){
                      //Making a pivot
                      var path = exportPath.getPath();
                      var n1 = nodeOf(path.getArray()[0]);
                      var n2 = nodeOf(path.getArray()[1]);

                      addToPivot(nodeOf(pivotPoint.getPosition()),n2); //hope this is further away from source.
                      redraw();
                  }
             });
            return pathExports;
          }
    }

    function movePivot(source, dest, nodes){
        nodes[dest] = nodes[source];
        var par = nodes[source].parent;
        delete nodes[source];

        //update pivots parent to point to parent.
        nodes[par].children.splice(nodes[par].children.indexOf(source),1);
        nodes[par].children.push(dest);

        //update pivots children to point to new location.
        $.each(nodes[dest].children,function(key,value){
            nodes[value].parent = dest;
        });

        redraw();
    }

    function addToPivot(pivot, node){
        if(node == undefined | pivot == undefined){
            //this could introduce errors in the data structure.
            console.log('undefined in addToPivot',node,pivot);
            return;
        }
        //create pivot if it doesnt exist.
        // the pivots parent.
        var parent = data.nodes[node].parent;
        if (!(pivot in data.nodes)){
            data.nodes[pivot] = {'parent':parent,'children':[]}; // create
            data.nodes[parent].children.push(pivot); // give parent
        }
        data.nodes[node].parent = pivot; // add pivot to node as new parent.
        data.nodes[pivot].children.push(node); // add pivot to node



        data.nodes[parent].children.splice(data.nodes[parent].children.indexOf(node),1); // remove node from parent;
    }

    function addMarker(map, latlng) {
        var m = new google.maps.Marker({
            position: latlng, 
            map: map,
            animation: google.maps.Animation.DROP,
            title: 'pivot'
        });
        
        m.setVisible(debug);
        pivotPoints.push(m);
        
        google.maps.event.addListener(m, 'click', function(){
            selectedPivot = nodeOf(m.getPosition());
            if (m.getAnimation() != null) {
                m.setAnimation(null);
            } else {
                m.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(function() {
                    m.setAnimation(null);
                }, 1000);
            }

            pivotPoint = m;
        });

        return m;
    }

    function removeMarker(latlng) {
        var i;

        $.each(pivotPoints, function(index, pivot) {
            if (pivot.getPosition().equals(latlng)) {
                pivot.setMap(null);
                i = index;
            }
        });

        pivotPoints.splice(i, 1);
    }

    function mergeNodes(n1, n2) {
        var pivot, nodes = [], parent, n2i;

        // delete n2
        $.each(pivotPoints, function(index, p) {
            if (geoOfNode(n2).equals(p.getPosition())) {
                pivot = p;
            }
        });

        if (!pivot) {
            console.log('failed to find pivot');
            return;
        }
        
        // remove the marker
        pivot.setMap(null);
        pivotPoints.splice(pivotPoints.indexOf(pivot), 1);

        // if any of n2s children point back to n2
        $.each(data.nodes[n2], function(index, n) {
            // no link backs to n2 - so add to kept node array
            if (n != n2) {
                nodes.push(n);
                
            // link back to n2 - so add to link back to n1 instead
            } else {
                data.nodes[n].push(n1);
                // TODO: what is the node already links back to n1?
            }
        });

        // make n1 point to all of np2s children
        data.nodes[n1].concat(nodes);

        // get n2's parent
        parent = data.nodes[n2][0];

        // remove n2 from its parent
        $.each(data.nodes[parent], function(index, n) {
            if (n == n2) {
                n2i = index;
                return false;
            }
        });
        data.nodes[parent].splice(n2i, 1);
    }

    function deleteNode(node) {
        // just remove pivot marker if that is all that exists
        if (!data.nodes[node]) {
            removeMarker(geoOfNode(node));
            return;
        }

        var parent = data.nodes[node].parent;

        // loop through all children
        //      attach the children to the source node and source node to the children
        $.each(data.nodes[node].children, function(index, n) {
            data.nodes[n].parent = nodeOf(sourceGeo);           // set chilren parent to be source
            data.nodes[nodeOf(sourceGeo)].children.push(n);     // attach children to source
        });

        // delete reference to node in parent
        data.nodes[parent].children.splice(data.nodes[parent].children.indexOf(node), 1);

        // delete the node
        delete data.nodes[node];

        redraw();
    }

    function nodeOf(object){
        //this accuracy might be bad.

        if (typeof(object.lat) != "function" & typeof(object.lng) != "function"){
            return object.lat+"|"+object.lng;
        }
        return ""+object.lat()+"|"+object.lng();
    }

    function geoOfNode(node){
        var latLng = node.split("|");
        return new google.maps.LatLng(parseFloat(latLng[0]), parseFloat(latLng[1]));
    }

    function build_geo_table(data){
        locations = {};
        for (g in data.geo){
            locations[nodeOf(data.geo[g])] = g;
        }
    }

    function traverse_nodes(node, parent, nodes, callback){
        var queue = nodes[node].children;
        if (parent != null) {callback(node,nodes[node].parent);}
        $.each(queue, function (key,value){
            if (value != parent){
                traverse_nodes(value,node,nodes,callback);
            }
        });
    }

    function intersect(p1,p2,p3,p4){
        // why cant i find a library with this !!!!
        // http://www.topcoder.com/tc?module=Static&d1=tutorials&d2=geometry2
        // does line p1-p2 intersect p3-p4

        //Shit, the coordinates in the map wrap backward and stuff.,,, na surely this will work
        
        //lat is Y, lng is X

        var x1 = p1.lng();
        var y1 = p1.lat();
        var x2 = p2.lng();
        var y2 = p2.lat();
        var x3 = p3.lng();
        var y3 = p3.lat();
        var x4 = p4.lng();
        var y4 = p4.lat();

        var A1 = y2-y1;
        var B1 = x1-x2;
        var C1 = (A1*x1) + (B1*y1);

        var A2 = y4-y3;
        var B2 = x3-x4;
        var C2 = (A2*x3) + (B2*y3);

        var det = (A1*B2) - (A2*B1);
        
        if (det==0){
            //lines are parallel.
            return false; // could be same line
        } else {
            var x = (B2*C1 - B1*C2) / det;
            var y = (A1*C2 - A2*C1) / det;

            if (Math.min(x1,x2) <= x && x <= Math.max(x1,x2) && 
                Math.min(y1,y2) <= y && y <= Math.max(y1,y2) &&
                Math.min(x3,x4) <= x && x <= Math.max(x3,x4) && 
                Math.min(y3,y4) <= y && y <= Math.max(y3,y4)){
                    return true;
                }
        }
        return false;
    } 


    function save(){
        var s_save = data.nodes;
        $.post('<?php echo "php/save.php?filename=$saveFile"; ?>', {nodes: data.nodes });
        alert('save');
    }

    function clear() {
        $.each(polyLines, function(index, line) {
            line.setMap(null);
        });
        
        $.each(pivotPoints, function(index, pivot) {
            pivot.setMap(null);
        });

        pivotPoint = null;
        selectedPivot = null;
        polyLines = [];
        pivotPoints = [];
    }

    function redraw() {
        clear();
        draw(); 
    }

    // add handler to debug button
    $('#btnDebug').click(function() {
        traverse_nodes("-40.900557|174.88597100000004", null, data.nodes, function(n1,n2){
            if (n1==undefined | n2 == undefined){
                console.log('undifined',n1,n2);
            }
        });
    });

    // add handler to save button
    $('#btnSave').click(function() {
        save();
    });
    
    // add handler to redraw button
    $('#btnRedraw').click(function() {
        redraw();
    });

    // add handler to redraw button
    $('#btnDeletePivot').click(function() {
        if (pivotPoint) {
            deleteNode(nodeOf(pivotPoint.getPosition())); 
        }
    });

    $("#sliderYear").slider({ min: 2000, max: 2011 });

    $("#sliderYear").bind( "slide", function(event, ui) {
        year = ui.value;
        $("#year").html(ui.value);
        redraw();
    });
    
    $("#sliderLineWidth").slider({ min: 40, max: 100});

    $("#sliderLineWidth").bind( "slide", function(event, ui) {
        lineWidth = ui.value;
        $("#lineWidth").html(ui.value);
        redraw();
    });

    $('#checkPivots').click(function(e) {
        debug = !$(this).is(':checked');
        $.each(pivotPoints, function(index, pivot) {
            pivot.setVisible(!pivot.getVisible());
        });
    });
    
    $("#lineWidth").html($("#sliderLineWidth").slider('value'));
    lineWidth = $("#sliderLineWidth").slider('value');
    $("#year").html($("#sliderYear").slider('value'));
    year = $("#sliderYear").slider('value');

    function fixData(data){
        $.each(data.nodes, function(key,value){
            if (value.children == "") value.children = [];
        });
    }
});

</script>

<div id="map_canvas" style="height:500px;width:1000px"></div> 
Year:
<div id="sliderYear" style="width:1000px;"></div>
<div id="year">2000</div>
<br />
Line Width Multiplier:
<div id="sliderLineWidth" style="width:1000px;"></div>
<div id="lineWidth">1</div>

<input id="btnSave" type="button" value="save" />
<input id="btnDebug" type="button" value="debug" />
<input id="btnRedraw" type="button" value="redraw" />
<input id="btnDeletePivot" type="button" value="delete pivot" />
<input id="checkPivots" type="checkbox" value="toggle pivots" /> toggle pivots
</body>
</html>
