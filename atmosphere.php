<?php

function getIpLocation() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $url = "http://ip-api.com/json/{$ip}";
    $response = query($url);
    $location = json_decode($response, true);

    //vérification de la localisation de l'ip
    if(isset($location['city']) && $location['city'] == 'Nancy'){
        $latitude = $location['lat'];
        $longitude = $location['lon'];
        return [$longitude, $latitude];
    } else {
        return getCoordonnatesFromAdresse('IUT nancy charlemagne');
    }
}

function getWeather($latitude, $longitude) {
    $url = "https://www.infoclimat.fr/public-api/gfs/xml?_ll={$latitude},{$longitude}&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";
    return query($url);
}

function transformXmlWithXsl($xmlString, $xslFilePath) {

    $xml = new DOMDocument;
    $xml->loadXML($xmlString);

    $xsl = new DOMDocument;
    $xsl->load($xslFilePath);

    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);

    $result = $proc->transformToXML($xml);
    return $result;
}

function getTrafficData() {
    $url = "https://carto.g-ny.org/data/cifs/cifs_waze_v2.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, 'www-cache:3128');
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return json_decode($response, true);
}

function getAirQuality($ville){
    $url = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=0.0&units=esriSRUnit_Meter&returnGeodetic=false&outFields=*&returnGeometry=true&featureEncoding=esriDefault&multipatchOption=xyFootprint&maxAllowableOffset=&geometryPrecision=&outSR=&datumTransformation=&applyVCSProjection=false&returnIdsOnly=false&returnUniqueIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnQueryGeometry=false&returnDistinctValues=false&cacheHint=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&having=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&returnExceededLimitFeatures=true&quantizationParameters=&sqlFormat=none&f=pjson&token=";
    $response = query($url);
    $data = json_decode($response, true);
    $latestFeature = null;

    $today = (new DateTime())->format('Y-m-d');
    foreach ($data["features"] as $feature) {
        $timestamp = $feature["attributes"]["date_ech"] / 1000;
        $featureDate = (new DateTime("@$timestamp"))->format('Y-m-d');
        if ($feature["attributes"]["lib_zone"] == $ville && $featureDate == $today) {
            $latestFeature = $feature;
            break;
        }
    }

    return $latestFeature['attributes'];
}

function getCoordonnatesFromAdresse($adresse){
    $query = str_replace(' ', '+', $adresse);
    $url = "https://api-adresse.data.gouv.fr/search/?q={$query}";
    $response = query($url);
    return json_decode($response, true)["features"][0]['geometry']['coordinates'];
}

function query($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, 'www-cache:3128');
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $response;
}

// récupération des données de circulation
$trafficData = getTrafficData();
$trafficDataJson = json_encode($trafficData['incidents']);

// récupération des coordonnées de l'ip
$ip = getIpLocation();
$longitude = $ip[0];
$latitude = $ip[1];

// récupération de la météo
$meteo = getWeather($latitude, $longitude);
$html = transformXmlWithXsl($meteo, './meteo.xsl');

// récupération de la qualité de l'air
$air = getAirQuality('Nancy');

// récupération de la position d'une adresse
$coordonnesIUT = getCoordonnatesFromAdresse('IUT nancy charlemagne');
$longitudeIUT = $coordonnesIUT[0];
$latitudeIUT = $coordonnesIUT[1];

// Construction du document HTML
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meteo et circulation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="./css/atmosphere.css">
</head>
<body>
    <div class="weather-container">
        $html
    </div>
    
    <div id="map-container">
        <h2 id="float-map" style="background-color: {$air['coul_qual']}">Qualitée de l'air de Nancy : {$air['lib_qual']}</h2>
        <div id="map"></div>
    </div>
    <footer>
        <h1>Api et liens</h1>
        <p>
            Github : <a href="https://github.com/Aliec-AQ/Interop-atmosphere">https://github.com/Aliec-AQ/Interop-atmosphere</a>
        </p>
        <p>
            Url météo : <a href="https://www.infoclimat.fr/public-api/gfs/xml?_ll={$latitude},{$longitude}&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2">https://www.infoclimat.fr/public-api/gfs/xml?_ll={$latitude},{$longitude}&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1Qy</a>
        </p>
        <p>
            Url traffic : <a href="https://carto.g-ny.org/data/cifs/cifs_waze_v2.json">https://carto.g-ny.org/data/cifs/cifs_waze_v2.json</a>
        </p>
        <p>
            Url qualité air : <a href="https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=0.0&units=esriSRUnit_Meter&returnGeodetic=false&outFields=*&returnGeometry=true&featureEncoding=esriDefault&multipatchOption=xyFootprint&maxAllowableOffset=&geometryPrecision=&outSR=&datumTransformation=&applyVCSProjection=false&returnIdsOnly=false&returnUniqueIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnQueryGeometry=false&returnDistinctValues=false&cacheHint=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&having=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&returnExceededLimitFeatures=true&quantizationParameters=&sqlFormat=none&f=pjson&token=">https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=0.0&units=esriSRUnit_Meter&returnGeodetic=false&outFields=*&returnGeometry=true&featureEncoding=esriDefault&multipatchOption=xyFootprint&maxAllowableOffset=&geometryPrecision=&outSR=&datumTransformation=&applyVCSProjection=false&returnIdsOnly=false&returnUniqueIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnQueryGeometry=false&returnDistinctValues=false&cacheHint=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&having=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&returnExceededLimitFeatures=true&quantizationParameters=&sqlFormat=none&f=pjson&token=</a>
        </p>
        <p>
            Url Ip : <a href="http://ip-api.com/json/{$_SERVER['REMOTE_ADDR']}">http://ip-api.com/json/{$_SERVER['REMOTE_ADDR']}</a>
        </p>
    </footer>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([$latitude, $longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var trafficData = $trafficDataJson;
        
        trafficData.forEach(function(incident) {
            var coordinates = incident.location.polyline.split(' ');
            var latitude = parseFloat(coordinates[0]);
            var longitude = parseFloat(coordinates[1]);

            L.marker([latitude, longitude]).addTo(map)
                .bindPopup('<b>' + incident.short_description + '</b><br>' + incident.location.location_description + '<br> Date de début : ' + incident.starttime + '<br>Date de fin : ' + incident.endtime);
        });

        L.marker([$latitudeIUT, $longitudeIUT]).addTo(map)
                .bindPopup('<b> IUT Nancy Charlemagne</b>');
    </script>
</body>
</html>
HTML;