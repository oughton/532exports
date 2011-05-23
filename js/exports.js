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

    function buildJSON(country, callback) {
        var json = {};

        countryData(country, function(data) {
            json.exports = data;
            if (json.exports && json.geo) callback(json);
        });

        geoData(function(data) {
            json.geo = data;
            if (json.exports && json.geo) callback(json);
        });
    }

    return {
        countryData: countryData,
        geoData: geoData,
        buildJSON: buildJSON
    };
})();
