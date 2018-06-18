<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>My Stop | Create my Stop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="css/styles.css" />
    <script src="https://use.fontawesome.com/6ad6091793.js"></script>
</head>
<body>
    
    <form action="createMyStop.php" method="POST">
        <label>Stops</label><input type="text" name="stopnum[]">
        <br>
        <label>Transport nums</label><input type="text" name="transportnumnum[]">

        <input type="submit">
    </form>

</body>
</html>

<?php
if($_SERVER['REQUEST_METHOD'] == "POST") createStop($_POST);

    function createStop($postparams) {
        $stops = parseStopNums($postparams['stopnum']);
        $baselink = $_SERVER['SERVER_NAME'] . '/hslapi/?stop=' . implode(';',$stops) . '&trams=' . implode(';',$postparams['transportnumnum']);

        echo "Here's your unique link: " . $baselink;
    }

    function parseStopNums($stopnums) {
        $stops = [];
        $curl = curl_init();
        $curl = setCurlSettings($curl);

        foreach($stopnums as $stop) {
            $stopnumber = $stop;
            var_dump($stopnumber);

            $postString = '{stops{code vehicleType gtfsId}}';
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);

            $output = curl_exec($curl);
            $output = json_decode($output, true);
            $stoparr = $output['data']['stops'];
            $stop = array_search($stopnumber, array_column($output['data']['stops'], 'code'));
            array_push($stops, $stoparr[$stop]);
        }
        curl_close($curl);
        return $stop;
    }

    function setCurlSettings($curl) {
        curl_setopt($curl, CURLOPT_URL, "https://api.digitransit.fi/routing/v1/routers/hsl/index/graphql");
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/graphql', 'Accept: application/json']);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        return $curl;
    }
?>