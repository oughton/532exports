$exports = (function() {
    function geoData(callback) {
        $.getJSON('php/geo.php', function(response) {
            callback(response);
        });
    }

    function countryData(country, callback) {
        // TODO: currently only returns NZ

        $.getJSON('php/exports.php', function(response) {
            callback(response);
        });
    }
    
    function nodes(callback){
        $.getJSON('php/nodes.php', function(response){
            callback(response);
        });
    }

    function buildJSON(country, callback) {
        var json = {};

        countryData(country, function(data) {
            json.exports = data;
            if (json.exports && json.geo && json.nodes) callback(json);
        });

        geoData(function(data) {
            json.geo = data;
            if (json.exports && json.geo && json.nodes) callback(json);
        });

        nodes(function(data){
            json.nodes = data;
            if (json.exports && json.geo && json.nodes) callback(json);
        });
    }

    return {
        countryData: countryData,
        geoData: geoData,
        nodes: nodes,
        buildJSON: buildJSON
    };
})();
