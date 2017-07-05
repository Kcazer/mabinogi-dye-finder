<?php

function ParseIntColor($color)
{
    // Extract aRGB components
    $rgbA = (($color >> 24) & 0xFF);
    $rgbR = (($color >> 16) & 0xFF);
    $rgbG = (($color >> 8) & 0xFF);
    $rgbB = ($color & 0xFF);
    // Preprocess RGB
    $_r = $rgbR / 255;
    $_g = $rgbG / 255;
    $_b = $rgbB / 255;
    $_r = 100 * (($_r > 0.04045) ? pow((($_r + 0.055) / 1.055), 2.4) : $_r / 12.92);
    $_g = 100 * (($_g > 0.04045) ? pow((($_g + 0.055) / 1.055), 2.4) : $_g / 12.92);
    $_b = 100 * (($_b > 0.04045) ? pow((($_b + 0.055) / 1.055), 2.4) : $_b / 12.92);
    // Convert to XYZ
    $xyzX = $_r * 0.4124 + $_g * 0.3576 + $_b * 0.1805;
    $xyzY = $_r * 0.2126 + $_g * 0.7152 + $_b * 0.0722;
    $xyzZ = $_r * 0.0193 + $_g * 0.1192 + $_b * 0.9505;
    // Preprocess XYZ
    $_x = $xyzX / 95.047;
    $_y = $xyzY / 100.000;
    $_z = $xyzZ / 108.883;
    $_x = ($_x > 0.008856) ? pow($_x, 1 / 3) : (7.787 * $_x) + (16 / 116);
    $_y = ($_y > 0.008856) ? pow($_y, 1 / 3) : (7.787 * $_y) + (16 / 116);
    $_z = ($_z > 0.008856) ? pow($_z, 1 / 3) : (7.787 * $_z) + (16 / 116);
    // Convert to Lab
    $labL = (116 * $_y) - 16;
    $labA = 500 * ($_x - $_y);
    $labB = 200 * ($_y - $_z);
    // Convert to LCH(ab)
    $lchL = $labL;
    $lchC = sqrt($labA * $labA + $labB * $labB);
    $lchH = fmod((atan2($labB, $labA) * 180 / M_PI + 360), 360);
    // Return
    return [
        'rgb' => [$rgbR, $rgbG, $rgbB, $rgbA],
        'xyz' => [$xyzX, $xyzY, $xyzZ],
        'lab' => [$labL, $labA, $labB],
        'lch' => [$lchL, $lchC, $lchH],
    ];
}

function ComputeDeltaE76($col1, $col2)
{
    // Constants
    $lab1 = $col1['lab'];
    $lab2 = $col2['lab'];
    // Intermediate variables
    $dL = $lab2[0] - $lab1[0];
    $dA = $lab2[1] - $lab1[1];
    $dB = $lab2[2] - $lab1[2];
    // Return result
    return sqrt($dL * $dL + $dA * $dA + $dB * $dB);
}

function ComputeDeltaE94($col1, $col2)
{
    // Constants
    $lab1 = $col1['lab'];
    $lab2 = $col2['lab'];
    // Intermediate variables
    $dL = $lab2[0] - $lab1[0];
    $dA = $lab2[1] - $lab1[1];
    $dB = $lab2[2] - $lab1[2];
    $c1 = sqrt($lab1[1] * $lab1[1] + $lab1[2] * $lab1[2]);
    $c2 = sqrt($lab2[1] * $lab2[1] + $lab2[2] * $lab2[2]);
    $dC = $c2 - $c1;
    $dH = sqrt(round($dA * $dA + $dB * $dB - $dC * $dC, 8));
    $sL = 1;
    $sC = 1 + $c1 * 0.045;
    $sH = 1 + $c1 * 0.015;
    $tL = $dL / $sL;
    $tC = $dC / $sC;
    $tH = $dH / $sH;
    // Return result
    return sqrt($tL * $tL + $tC * $tC + $tH * $tH);
}

function GetCssColor($col)
{
    list($r, $g, $b, $a) = $col['rgb'];
    $r = substr('0' . dechex($r), -2);
    $g = substr('0' . dechex($g), -2);
    $b = substr('0' . dechex($b), -2);
    return '#' . $r . $g . $b;
}

function GetHousingData($housing, $server, $item, $cacheLimit = 600)
{
    // Check cache
    $file = 'cache-' . $server . '-' . $item . '.js';
    $time = file_exists($file) ? filemtime($file) : 0;
    if (time() - $time > $cacheLimit) $data = FetchHousingData($housing, $server, $item);
    // Update data / cache
    if (isset($data) && !is_null($data)) file_put_contents($file, json_encode($data));
    else if ($time) $data = json_decode(file_get_contents($file), true);
    else $data = [];
    // Return loaded data
    return $data;
}

function FetchHousingData($housing, $server, $item)
{
    // Prepare URL
    $url = $housing;
    $url .= '?CharacterId=0000000000000001';    // Default player ID
    $url .= '&SearchType=4';                    // Search by item name
    $url .= '&SortType=';                       // Sort is not relevant
    $url .= '&SortOption=';                     // -
    $url .= '&Row=1000';                        // Fetch 1000 results at once
    $url .= '&Page=1';                          // First page of results
    $url .= '&Name_Server=' . $server;
    $url .= '&SearchWord=' . $item;
    // Fetch XML
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $xml = curl_exec($ch);
    curl_close($ch);
    // Process XML
    $xml = str_replace('&', '&amp;', $xml);
    $xml = simplexml_load_string($xml);
    if (is_object($xml)) {
        // Load data
        $data = [];
        foreach ($xml->ItemDesc as $temp) $data[] = [
            'user' => strval($temp['Char_Name']),
            'price' => intval($temp['Item_Price']),
            'color' => ParseIntColor(intval($temp['Item_Color1'])),
        ];
        // Filter out flashy dyes
        $data = array_filter($data, function ($v) {
            return !$v['color']['rgb'][3];
        });
    }
    // Return processed data
    return isset($data) ? $data : null;
}

function ComputeDyeMatching($data, $color)
{
    foreach ($data as &$dye) {
        $dye['deltaE76'] = ComputeDeltaE76($color, $dye['color']);
        $dye['deltaE94'] = ComputeDeltaE94($color, $dye['color']);
    }
    return $data;
}

function SortDyeList($data, $sortKey)
{
    usort($data, function ($v1, $v2) use ($sortKey) {
        if ($v1[$sortKey] < $v2[$sortKey]) return -1;
        if ($v1[$sortKey] > $v2[$sortKey]) return 1;
        if ($v1['price'] < $v2['price']) return -1;
        if ($v1['price'] > $v2['price']) return 1;
        return 0;
    });
    return $data;
}
