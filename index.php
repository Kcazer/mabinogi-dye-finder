<?php

// Configuration
require 'config.php';
require 'functions.php';

// Variables
$server = isset($_GET['server']) ? $_GET['server'] : null;
$color = isset($_GET['color']) ? $_GET['color'] : null;
$item = isset($_GET['item']) ? $_GET['item'] : null;

// Checks
if (!isset($servers[$server])) $server = null;
if (!isset($items[$item])) $item = null;
if (!preg_match('/^#?[0-9A-F]{6}$/i', $color)) $color = null;

// Process
if ($server && $item && $color) {
    // Parameters
    $_server = $servers[$server]['value'];
    $_color = ParseIntColor(hexdec($color));
    $_item = $items[$item]['value'];
    // Get housing data, compute color matching, and sort
    $data = GetHousingData($housing, $_server, $_item);
    $data = ComputeDyeMatching($data, $_color);
    $data = SortDyeList($data, $sortKey);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="description" content="Did you ever need a specific dye color, but couldn't bother to check housing? Look no further, here is the tool you need.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Mabinogi Dye Finder</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<form>
    <div>
        <label for="_server">Server :</label>
        <select name="server" id="_server">
            <?php foreach ($servers as $k => $v) : ?>
                <option value="<?= $k ?>" <?= $k == $server ? 'selected' : '' ?>><?= $v['label'] ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label for="_item">Item :</label>
        <select name="item" id="_item">
            <?php foreach ($items as $k => $v) : ?>
                <option value="<?= $k ?>" <?= $k == $item ? 'selected' : '' ?>><?= $v['label'] ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label for="_color">Color :</label>
        <input name="color" type="text" value="<?= $color ?: '#000000' ?>" class="monospace"/><!--
        --><input type="color" value="<?= $color ?: '#000000' ?>" id="_color"/>
    </div>
    <button type="submit">Search</button>
</form>

<?php if (isset($data)) : ?>
    <table>
        <thead>
        <tr>
            <th style="width:3em" colspan="2"></th>
            <th style="width:5em">Code</th>
            <th style="width:6em">Price</th>
            <th style="width:7em">Seller</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (array_slice($data, 0, $resultLimit) as $i => $dye) : ?>
            <?php $delta = number_format($dye[$sortKey], 2, '.', '') ?>
            <tr title="<?= $sortKey ?> = <?= $delta ?>">
                <td><div style="background:<?= GetCssColor($_color) ?>"></div></td>
                <td><div style="background:<?= GetCssColor($dye['color']) ?>"></div></td>
                <td class="align-center monospace"><?= GetCssColor($dye['color']) ?></td>
                <td class="align-right"><?= number_format($dye['price']) ?></td>
                <td class="align-center"><?= htmlspecialchars($dye['user']) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>

<script src="script.js"></script>
</body>
</html>
