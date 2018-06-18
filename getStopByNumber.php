<?php
$stopnumber = $_GET['stopnum'];
$postString = '{stops{code vehicleType gtfsId}}';
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://api.digitransit.fi/routing/v1/routers/hsl/index/graphql");
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/graphql', 'Accept: application/json']);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($curl);
$output = json_decode($output, true);
$stoparr = $output['data']['stops'];
$stop = array_search($stopnumber, array_column($output['data']['stops'], 'code'));
print_r($stoparr[$stop]);
curl_close($curl);
?>