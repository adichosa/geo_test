<?php
$url = "https://drive.google.com/uc?export=download&id=10_Aa1D5NcgRD8bDBvd_SOnvHPnk27in3";

$ctr=1;
if (($handle = fopen($url, "r")) !== FALSE) {
    $clean=[];
    while (($data = fgetcsv($handle, 3151, ",")) !== FALSE) {
        if (isValidCoordinates($data[1],$data[2]) && isValidTimestamp($data[3])){
        
            array_push($clean,$data);
            
        }else{
            
        }

        
    }
    fclose($handle);

    usort($clean, function($a, $b) {
        return strtotime($a[3]) <=> strtotime($b[3]);
    });
    

    $trips=[];
    $ctr=1;
    $maxSpeed = 0;

    for ($i = 0; $i < count($clean) - 1; $i++) {
        $lat1 = $clean[$i][1];
        $lon1 = $clean[$i][2];
        $lat2 = $clean[$i+1][1];
        $lon2 = $clean[$i+1][2];

        $distance = haversineDistance($lat1, $lon1, $lat2, $lon2);

        $t1 = strtotime($clean[$i][3]);
        $t2 = strtotime($clean[$i + 1][3]);
        $diffMinutes = abs($t2 - $t1) / 60; // convert seconds to minutes
        
        if ($distance > 2 || $diffMinutes > 25){
           

            $durationHours = ($t2 - $t1) / 3600; // seconds → hours
            $averageSpeed = $durationHours > 0 ? $distance / $durationHours : 0; // km/h

            if ($durationHours > 0) {
                $speed = $distance / $durationHours;
                if ($speed > $maxSpeed) {
                    $maxSpeed = $speed;
                }
            }

            $trips[]=[
                "no" => "trip_".$ctr++,
                "distance" => round($distance, 4), //in km
                "duration"  => $diffMinutes,       //in mins  
                "average_speed" => $averageSpeed,  //in km/h
                "max_speed" => $maxSpeed,          //in km/h
            ];
        }


    }

    // Build FeatureCollection
    $features = [];
    foreach ($trips as $trip) {
        $features[] = [
            "type" => "Feature",
            "geometry" => [
                "type" => "LineString",
                "trips" => $trip
            ],
            "properties" => [
                "color" => randomColor()
            ]
        ];
    }

    $geojson = [
        "type" => "FeatureCollection",
        "features" => $features
    ];

    // Output as JSON
    header('Content-Type: application/json');
    echo json_encode($geojson, JSON_PRETTY_PRINT);
    
} else {
    echo "Error: Cannot open file.";
}


function isValidCoordinates($lat, $lng) {
    // Check if both are numeric
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }
    // Latitude range check
    if ($lat < -90 || $lat > 90) {
        return false;
    }
    // Longitude range check
    if ($lng < -180 || $lng > 180) {
        return false;
    }
    return true;
}

function isValidTimestamp($datetime, $format = 'Y-m-d\TH:i:s') {
    $dt = DateTime::createFromFormat($format, $datetime);
    return $dt && $dt->format($format) === $datetime;
}

function diffBetweenDates($data){


    for ($i = 0; $i < count($data) - 1; $i++) {
        $t1 = strtotime($data[$i][3]);
        $t2 = strtotime($data[$i + 1][3]);
        $diffMinutes = abs($t2 - $t1) / 60; // convert seconds to minutes
        
        if ($diffMinutes > 25) {
            echo "Row $i and Row " . ($i+1) . " differ by $diffMinutes minutes (greater than 25)\n";
        } 
    }
}

function diffDistance($data){
    for ($i = 0; $i < count($data) - 1; $i++) {
        $lat1 = $data[$i][1];
        $lon1 = $data[$i][2];
        $lat2 = $data[$i+1][1];
        $lon2 = $data[$i+1][2];

        $distance = haversineDistance($lat1, $lon1, $lat2, $lon2);

        if ($distance > 2){
            echo "Distance from Row $i to Row " . ($i+1) . ": " . round($distance, 4) . " km\n";
        }
    }
}

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in km
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $latDiff = $lat2 - $lat1;
    $lonDiff = $lon2 - $lon1;

    $a = sin($latDiff / 2) ** 2 +
         cos($lat1) * cos($lat2) * sin($lonDiff / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function randomColor() {
    return sprintf("#%06X", mt_rand(0, 0xFFFFFF));
}
?>
