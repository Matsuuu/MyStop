<?php
require_once('hsltracker.class.php');
$stop       = isset($_GET['stop']) ? $_GET['stop'] : "";
$trams      = isset($_GET['trams']) ? $_GET['trams'] : "";
$background = isset($_GET['bground']) ? $_GET['bground'] : "";
if(! empty($stop)) {
    $tracker = new HSLTracker($stop, $trams);
    $departures = $tracker->getDepartures();
    $departures = (sortDeparturesByTime($departures, $tracker));
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>My Stop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://use.fontawesome.com/6ad6091793.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" type="text/css" media="screen" href="css/styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/colorpicker.css" />
</head>
<body <?php if(!empty($background)) echo "id=" . $background; ?>>

<div class="backgroundtoggler">
    <div class="colorpicker" id="white">
        
    </div>
    
    <div class="colorpicker" id="black">

    </div>
</div>

<?php
    foreach($departures as $dep) {
        printDepartureFromTemplate($dep);
    }
?>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                location.reload();
            }, 30000);
        }
    </script>

<script src="js/colorpicker.js" type="text/javascript"></script>
</body>
</html>
