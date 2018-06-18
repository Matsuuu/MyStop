<?php
$postString = "{stops{gtfsId name }}";
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://api.digitransit.fi/routing/v1/routers/hsl/index/graphql");
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/graphql', 'Accept: application/json']);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
$output = curl_exec($curl);
curl_close($curl);
?>