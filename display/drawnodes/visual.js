$(document).ready(function() {
    var _render = function(origin, nodes) {
        map = _createMap(origin);

        node_lookup = {};

        for (var i = 0; i < nodes.length; i++) {
            node = nodes[i];
            _drawNode(map, node);
            node_lookup[node.node] = node;
        };

        for (var i = 0; i < nodes.length; i++) {
            var from_node = nodes[i];

            for (var j = 0; j < from_node.edges.length; j++) {
                var edge = from_node.edges[j];
                var to_node = node_lookup[edge]

                if (to_node)
                    _drawLine(map, from_node.lat, from_node.lon, to_node.lat, to_node.lon);
            };
        };
    };

    var _drawNode = function(map, node) {
        var options = {
            strokeWeight: 0,
            fillColor: '#FF0000',
            fillOpacity: 1,
            map: map,
            center: _toMapLatLng(node.lat, node.lon),
            radius: 50000
        };

        var circle = new google.maps.Circle(options);

        google.maps.event.addListener(circle, 'click', function(event) {
            console.log(node.node);
        });
    };

    var _drawLine = function(map, from_lat, from_lon, to_lat, to_lon) {
        var coords = [
            {lat: from_lat, lng: from_lon},
            {lat: to_lat, lng: to_lon}
        ];

        var path = new google.maps.Polyline({
            path: coords,
            geodesic: true,
            strokeColor: '#FF0000',
            strokeOpacity: 1.0,
            strokeWeight: 2
          });

          path.setMap(map);
    };

    var _createMap = function(mapLatLon) {
        var mapOptions = {
            center: mapLatLon,
            zoom: 2
        };

        elem = document.getElementById('map-canvas')
        var map = new google.maps.Map(elem, mapOptions);

        var bounds = new google.maps.LatLngBounds();
        bounds.extend(mapLatLon);

        return map;
    };

    var _toMapLatLng = function(lat, lon) {
        return new google.maps.LatLng(lat, lon);
    };

    var _run = function(data) {
    	origin = _toMapLatLng(-41.291212, 174.781897)
    	_render(origin, data)
    }

    //var path = 'shippingroutes.json';
    var path = 'paths.json'

    $.getJSON(path, function(data) {
        _run(data.nodes);
    }.bind(this));
});