<?php

class HSLTracker {
    protected $stopid;
    protected $curl;
    protected $trams;
    protected $departures;

        function __construct($stops = "", $chosentrams = "") {
            $this->setStops($stops);
            $this->chosentrams  = explode(';', $chosentrams);
            $this->curl         = $this->setCurlParams();
            $this->trams        = $this->queryTrams();
            $this->departures   = $this->queryDepartures();
        }

        protected function setStops($stops) {
            if(strpos($stops, ";") !== false) {
                $this->stopid = [];
                foreach(explode(";",$stops) as $stop) {
                    array_push($this->stopid, $stop);
                }
            } else {
                $this->stopid = $stops;
            }
        }

        public function getStops() {
            return $this->stopid;
        }

        function setCurlParams() {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://api.digitransit.fi/routing/v1/routers/hsl/index/graphql");
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/graphql', 'Accept: application/json']);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            return $curl;
        }

        protected function parseStopInfo($data) {
            $data = json_decode($data);
            $dataArr = [];
            $dataArr['stopName'] = $data->data->stop->name;
            $routes = [];
            $dataArr['routes'] = [];
        
            foreach($data->data->stop->patterns as $route) {
                array_push($routes, $route);
            }
            $i = 0;
            foreach($routes as $r) {
                $dataArr['routes'][$i]['name'] = $r->route->longName;
                $dataArr['routes'][$i]['number'] = $r->route->shortName;
                $i++;
            }
            return $dataArr;
        }

        protected function parseDepartureInfo($data) {
            $data = json_decode($data);
            $dataArr = [];
            $dataArr['stopname'] = $data->data->stop->name;
            $dataArr['stopcode'] = $data->data->stop->code;
            $dataArr['patterns'] = [];
            $idArr = [];
            foreach($data->data->stop->patterns as $patt) {
                if(! in_array($patt->route->gtfsId, $idArr)) {
                    array_push($dataArr['patterns'], $patt);
                    array_push($idArr, $patt->route->gtfsId);
                }
            }
            foreach($dataArr['patterns'] as $key => $pt) {
                $dataArr['patterns'][$key] = json_decode(json_encode($pt), True); // I'm lazy rn.

            }
            $stopTimes = json_decode(json_encode($data->data->stop->stoptimesForPatterns), True);

            foreach($stopTimes as $key => $st) {
                if(isset($dataArr['patterns'][$key]) && is_array($dataArr['patterns'][$key])) {
                    array_push($dataArr['patterns'][$key], $st);
                }
            }

            return $dataArr;
        }
        


        protected function queryDepartures() {
            $depArr = [];
            if(is_array($this->stopid)) {
                foreach ($this->stopid as $stop) {
                    $postString = "{stop(id: \"HSL:" . $stop . "\") {
                                id
                                name
                                code
                                patterns {
                                    name
                                    route {
                                        gtfsId
                                        shortName
                                        longName
                                    }
                                }
                                stoptimesForPatterns {
                                    stoptimes {
                                        scheduledArrival
                                        realtimeArrival
                                        scheduledDeparture
                                        realtimeDeparture
                                        serviceDay
                                    }
                                }
                            }}";

                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postString);
                    $departures = $this->parseDepartureInfo(curl_exec($this->curl));

                    array_push($depArr, $departures);
                }
            } else {
                $postString = "{stop(id: \"HSL:" . $this->stopid . "\") {
                                id
                                name
                                code
                                patterns {
                                    name
                                    route {
                                        gtfsId
                                        shortName
                                        longName
                                    }
                                }
                                stoptimesForPatterns {
                                    stoptimes {
                                        scheduledArrival
                                        realtimeArrival
                                        scheduledDeparture
                                        realtimeDeparture
                                        serviceDay
                                    }
                                }
                            }}";

                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postString);
                $departures = $this->parseDepartureInfo(curl_exec($this->curl));
                array_push($depArr, $departures);
            }
            
            return $depArr;
        }

        protected function queryTrams() {
            $tramArr = [];
            if(is_array($this->stopid)) {
                foreach ($this->stopid as $stop) {
                    $postString = "{stop(id: \"HSL:" . $stop . "\") {
                    name
                    patterns {
                        id
                        name
                        route {
                            gtfsId
                            shortName
                            longName   
                        }
                        directionId
                    }
                }}";
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postString);
                    $data = curl_exec($this->curl);
                    $info = $this->parseStopInfo($data);

                    foreach($info['routes'] as $tram) {
                        array_push($tramArr, $tram);
                    }
                }
            } else {
                $postString = "{stop(id: \"HSL:" . $this->stopid . "\") {
                    name
                    patterns {
                        id
                        name
                        route {
                            gtfsId
                            shortName
                            longName   
                        }
                        directionId
                    }
                }}";
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postString);
                $data = curl_exec($this->curl);
                $info = $this->parseStopInfo($data);

                foreach($info['routes'] as $tram) {
                    array_push($tramArr, $tram);
                }
            }
            return $tramArr;
        }

        public function getDepartures() {
            return $this->departures;
        }
        public function getTrams(){
            return $this->trams;
        }
}

function sortDeparturesByTime($departures, $tracker) {
    $deptimes = [];
    $i = 0;
    foreach($departures as $deps) {
        foreach($deps['patterns'] as $pats) {
            foreach($pats[0]['stoptimes'] as $stoptime) {
                if($tracker->chosentrams == "" || in_array($pats['route']['shortName'], $tracker->chosentrams)) {
                    $depinfo = [
                        'tramname'      => $pats['route']['longName'],
                        'tramnum'       => $pats['route']['shortName'],
                        'stopname'      => $deps['stopname'],
                        'stopcode'      => $deps['stopcode'],
                        'arrival'       => $stoptime['scheduledArrival'],
                        'realarrival'   => $stoptime['realtimeArrival'],
                        'deptime'       => $stoptime['scheduledDeparture'],
                        'realdeptime'   => $stoptime['realtimeDeparture'],
                        'serviceDay'    => $stoptime['serviceDay']
                    ];
                    $depinfo['arrival']     = date("H:i:s", $depinfo['arrival'] - 3600);
                    $depinfo['realarrival'] = date("H:i:s", $depinfo['realarrival'] - 3600); 
                    $depinfo['deptime']     = date("H:i:s", $depinfo['deptime'] - 3600);
                    $depinfo['realdeptime'] = date("H:i:s", $depinfo['realdeptime'] - 3600);
                    $depinfo['serviceDay']  = date('d', $depinfo['serviceDay'] - 3600);
                    array_push($deptimes, $depinfo);
                }
            }
        }
        $i++;
    }
    if(!empty($deptimes)) {
        foreach($deptimes as $key => $row) {
            $arrival[$key] = $row['arrival'];
            $serviceDay[$key] = $row['serviceDay'];
        }
        array_multisort($serviceDay, SORT_ASC, $arrival, SORT_ASC, $deptimes);
    }
    $deptimes = array_slice($deptimes, 0, 5, true);
    return $deptimes;
}

function printDepartureFromTemplate($dep) {
    $type = "tram";
    //$template = $dep['arrival'] < date("H:i:s", time() + 3900) ? "<div class='departurecontainer toolate'>" : "<div class='departurecontainer'>";

    /*    $template .= "<div class='depinfo'>";

            $template .= "<div class='transporttitleholder'>";
                $template .= "<h2>". $dep['tramnum'] ." - ". $dep['tramname'] ."</h2>";
            $template .= "</div>";


            $template .= departureLogoCreator($type);

            $template .= "<div class='departuredetails'>";
                $template .= "<p>Scheduled arrival: "  .$dep['arrival'] . "</p>";
                $template .= "<p>Real arrival: " . $dep['realarrival'] . "</p>";
            $template .= "</div>";

        $template .= "</div>";
    $template .= "</div>";*/

    $template = $dep['arrival'] < date("H:i:s", time() + 3900) ? "<table class='departurecontainer toolate'>" : "<table class='departurecontainer'>";

    $template .= "<tr>";
    $template .= "<td width='30%'>" . departureLogoCreator($type) . "</td>";
    $template .= "<td><h2>" .$dep['tramname'] . "</h2>";
    $template .= "</tr>";

    $template .= "<tr>";
    $template .= "<td><p class='transportnum'>" . $dep['tramnum'] . "</p></td>";
    $template .= "<td class='arrivals'><p>Scheduled arrival: " . $dep['arrival'] . "</p><br>";
    $template .= "<p>Real arrival: " . $dep['realarrival'] . "</p></td>";
    $template .= "</tr>";

    $template .= "</table>";

    echo $template;
}

function departureLogoCreator($type) {
    if($type == "tram") {
        $logo = '<i class="fa fa-train departureslogo" aria-hidden="true"></i>';
    }
    //Other public transport to be added
    //return "<div class='departurelogocontainer'>" . $logo . "</div>";
    return $logo;
}


?>