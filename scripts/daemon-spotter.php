#!/usr/bin/php
<?php
/**
* This script is used to retrieve message from SBS source like Dump1090, Radarcape,.. or from phpvms, wazzup files,...
* This script can be used as cron job with $globalDaemon = FALSE
*/

require_once(dirname(__FILE__).'/../require/class.SpotterImport.php');
require_once(dirname(__FILE__).'/../require/class.SpotterServer.php');
//require_once(dirname(__FILE__).'/../require/class.APRS.php');
require_once(dirname(__FILE__).'/../require/class.ATC.php');
require_once(dirname(__FILE__).'/../require/class.SBS.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');

if (!isset($globalDebug)) $globalDebug = FALSE;

// Check if schema is at latest version
$Connection = new Connection();
if ($Connection->latest() === false) {
    echo "You MUST update to latest schema. Run install/index.php";
    exit();
}


// This is to be compatible with old version of settings.php
if (isset($globalSource)) {
    
} else {
    if (isset($globalSBS1Hosts)) {
	$hosts = $globalSBS1Hosts;
    } else {
	if (!isset($globalSBS1Host)) {
	    echo '$globalSBS1Host MUST be defined !';
	    die;
	}
	$hosts = array($globalSBS1Host.':'.$globalSBS1Port);
    }
}
$options = getopt('s::',array('source::','server','idsource::'));
if (isset($options['s'])) $hosts = array($options['s']);
elseif (isset($options['source'])) $hosts = array($options['source']);
if (isset($options['server'])) $globalServer = TRUE;
if (isset($options['idsource'])) $id_source = $options['idsource'];
else $id_source = 1;
if (isset($globalServer) && $globalServer) {
    if ($globalDebug) echo "Using Server Mode\n";
    $SI=new SpotterServer();
} else $SI=new SpotterImport($Connection->db);
//$APRS=new APRS($Connection->db);
$SBS=new SBS($Connection->db);
$Common=new Common();
date_default_timezone_set('UTC');
//$servertz = system('date +%Z');
// signal handler - playing nice with sockets and dump1090
if (function_exists('pcntl_fork')) {
    pcntl_signal(SIGINT,  function($signo) {
        global $sockets;
        echo "\n\nctrl-c or kill signal received. Tidying up ... ";
        die("Bye!\n");
    });
    pcntl_signal_dispatch();
}

// let's try and connect
if ($globalDebug) echo "Connecting...\n";
$use_aprs = false;

function create_socket($host, $port, &$errno, &$errstr) {
    $ip = gethostbyname($host);
    $s = socket_create(AF_INET, SOCK_STREAM, 0);
    if (socket_set_nonblock($s)) {
        $r = @socket_connect($s, $ip, $port);
        if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
            return $s;
        }
    }
    $errno = socket_last_error($s);
    $errstr = socket_strerror($errno);
    socket_close($s);
    return false;
}

function connect_all($hosts) {
    global $sockets, $formats, $globalDebug,$aprs_connect,$last_exec, $globalSourcesRights, $use_aprs;
    foreach ($hosts as $id => $host) {
	// Here we check type of source(s)
	if (filter_var($host,FILTER_VALIDATE_URL)) {
            if (preg_match('/deltadb.txt$/i',$host)) {
        	$formats[$id] = 'deltadbtxt';
        	$last_exec['deltadbtxt'] = 0;
        	if ($globalDebug) echo "Connect to deltadb source (".$host.")...\n";
            } else if (preg_match('/vatsim-data.txt$/i',$host)) {
        	$formats[$id] = 'vatsimtxt';
        	$last_exec['vatsimtxt'] = 0;
        	if ($globalDebug) echo "Connect to vatsim source (".$host.")...\n";
    	    } else if (preg_match('/aircraftlist.json$/i',$host)) {
        	$formats[$id] = 'aircraftlistjson';
        	$last_exec['aircraftlistjson'] = 0;
        	if ($globalDebug) echo "Connect to aircraftlist.json source (".$host.")...\n";
    	    } else if (preg_match('/radarvirtuel.com\/file.json$/i',$host)) {
        	$formats[$id] = 'radarvirtueljson';
        	$last_exec['radarvirtueljson'] = 0;
        	if ($globalDebug) echo "Connect to radarvirtuel.com/file.json source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
    	    } else if (preg_match('/planeUpdateFAA.php$/i',$host)) {
        	$formats[$id] = 'planeupdatefaa';
        	$last_exec['planeupdatefaa'] = 0;
        	if ($globalDebug) echo "Connect to planeUpdateFAA.php source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
            } else if (preg_match('/\/action.php\/acars\/data$/i',$host)) {
        	$formats[$id] = 'phpvmacars';
        	$last_exec['phpvmacars'] = 0;
        	if ($globalDebug) echo "Connect to phpvmacars source (".$host.")...\n";
            } else if (preg_match('/whazzup/i',$host)) {
        	$formats[$id] = 'whazzup';
        	$last_exec['whazzup'] = 0;
        	if ($globalDebug) echo "Connect to whazzup source (".$host.")...\n";
            } else if (preg_match('/recentpireps/i',$host)) {
        	$formats[$id] = 'pirepsjson';
        	$last_exec['pirepsjson'] = 0;
        	if ($globalDebug) echo "Connect to pirepsjson source (".$host.")...\n";
            } else if (preg_match(':data.fr24.com/zones/fcgi/feed.js:i',$host)) {
        	$formats[$id] = 'fr24json';
        	$last_exec['fr24json'] = 0;
        	if ($globalDebug) echo "Connect to fr24 source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
            } else if (preg_match('/10001/',$host)) {
        	$formats[$id] = 'tsv';
        	if ($globalDebug) echo "Connect to tsv source (".$host.")...\n";
            }
        } else {
	    $hostport = explode(':',$host);
    	    $s = create_socket($hostport[0],$hostport[1], $errno, $errstr);
	    if ($s) {
    	        $sockets[$id] = $s;
		if (preg_match('/aprs/',$hostport[0])) {
			$formats[$id] = 'aprs';
			$aprs_connect = 0;
			$use_aprs = true;
    	        } elseif ($hostport[1] == '10001') {
        	    $formats[$id] = 'tsv';
		} elseif ($hostport[1] == '30002') {
        	    $formats[$id] = 'raw';
		} elseif ($hostport[1] == '30005') {
		    // Not yet supported
        	    $formats[$id] = 'beast';
		} else $formats[$id] = 'sbs';
		if ($globalDebug) echo 'Connection in progress to '.$host.'('.$formats[$id].')....'."\n";
            } else {
		if ($globalDebug) echo 'Connection failed to '.$host.' : '.$errno.' '.$errstr."\n";
    	    }
        }
    }
}
/*
// This is to be compatible with old version of settings.php
if (isset($globalSource)) {
    
} else {
    if (isset($globalSBS1Hosts)) {
	$hosts = $globalSBS1Hosts;
    } else {
	if (!isset($globalSBS1Host)) {
	    echo '$globalSBS1Host MUST be defined !';
	    die;
	}
	$hosts = array($globalSBS1Host.':'.$globalSBS1Port);
    }
}
if (isset($globalServer) && $globalServer) {
    $options = getopt('s::',array('source::'));
    if (isset($options['s'])) $hosts = array($options['s']);
    elseif (isset($options['source'])) $hosts = array($options['source']);
}
*/
if (!isset($globalMinFetch)) $globalMinFetch = 0;

// Initialize all
$status = array();
$sockets = array();
$formats = array();
$last_exec = array();
$time = time();
$timeout = $globalSBS1TimeOut;
$errno = '';
$errstr='';

$_ = $_SERVER['_'];
if (!isset($globalDaemon)) $globalDaemon = TRUE;
/* Initiate connections to all the hosts simultaneously */
connect_all($hosts);

// APRS Configuration
if ($use_aprs) {
	require_once(dirname(__FILE__).'/../require/class.APRS.php');
	$APRS=new APRS($Connection->db);
	$aprs_connect = 0;
	$aprs_keep = 240;
	$aprs_last_tx = time();
	if (isset($globalAPRSversion)) $aprs_version = $globalAPRSversion;
	else $aprs_version = $globalName.' using FlightAirMap';
	//else $aprs_version = 'Perl Example App';
	if (isset($globalAPRSssid)) $aprs_ssid = $globalAPRSssid;
	else $aprs_ssid = 'FAM';
	//else $aprs_ssid = 'PerlEx';
	if (isset($globalAPRSfilter)) $aprs_filter = $globalAPRSfilter;
	else $aprs_filter =  'r/'.$globalCenterLatitude.'/'.$globalCenterLongitude.'/250.0';
	if ($aprs_filter != '') $aprs_login = "user {$aprs_ssid} appid {$aprs_version} filter {$aprs_filter}\n";
	else $aprs_login = "user {$aprs_ssid} appid {$aprs_version}\n";
}

// connected - lets do some work
if ($globalDebug) echo "Connected!\n";
sleep(1);
if ($globalDebug) echo "SCAN MODE \n\n";
if (!isset($globalCronEnd)) $globalCronEnd = 60;
$endtime = time()+$globalCronEnd;
$i = 1;
$tt = 0;

// Delete all ATC
if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
	$ATC=new ATC($Connection->db);
}
if (!$globalDaemon && ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
	$ATC->deleteAll();
}

// Infinite loop if daemon, else work for time defined in $globalCronEnd or only one time.
while ($i > 0) {
    if (!$globalDaemon) $i = $endtime-time();
    // Delete old ATC
    if ($globalDaemon && ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
        $ATC->deleteOldATC();
    }
    foreach ($formats as $id => $value) {
	if ($value == 'deltadbtxt' && (time() - $last_exec['deltadbtxt'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id]);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(',', $line);
	            $data = array();
	            $data['hex'] = $line[1]; // hex
	            $data['ident'] = $line[2]; // ident
	            $data['altitude'] = $line[3]; // altitude
	            $data['speed'] = $line[4]; // speed
	            $data['heading'] = $line[5]; // heading
	            $data['latitude'] = $line[6]; // lat
	            $data['longitude'] = $line[7]; // long
	            $data['verticalrate'] = ''; // vertical rate
	            $data['squawk'] = ''; // squawk
	            $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s');
		    $data['format_source'] = 'deltadbtxt';
    		    $data['id_source'] = $id_source;
    		    $SI->add($data);
		    unset($data);
    		}
    	    }
    	    $last_exec['deltadbtxt'] = time();
	} elseif (($value == 'whazzup' && (time() - $last_exec['whazzup'] > $globalMinFetch)) || ($value == 'vatsimtxt' && (time() - $last_exec['vatsimtxt'] > $globalMinFetch))) {
	    $buffer = $Common->getData($hosts[$id]);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(':', $line);
    		    if (count($line) > 40 && $line[0] != 'callsign') {
			$data = array();
			$data['id'] = $line[1].'-'.$line[0];
			$data['pilot_id'] = $line[1];
			$data['pilot_name'] = $line[2];
			$data['hex'] = str_pad(dechex($line[1]),6,'000000',STR_PAD_LEFT);
			$data['ident'] = $line[0]; // ident
			if ($line[7] != '' && $line[7] != 0) $data['altitude'] = $line[7]; // altitude
			$data['speed'] = $line[8]; // speed
			if (isset($line[45])) $data['heading'] = $line[45]; // heading
			elseif (isset($line[38])) $data['heading'] = $line[38]; // heading
			$data['latitude'] = $line[5]; // lat
	        	$data['longitude'] = $line[6]; // long
	        	$data['verticalrate'] = ''; // vertical rate
	        	$data['squawk'] = ''; // squawk
	        	$data['emergency'] = ''; // emergency
	        	$data['waypoints'] = $line[30];
			//$data['datetime'] = date('Y-m-d h:i:s');
			$data['datetime'] = date('Y-m-d H:i:s',strtotime($line[37]));
		        $data['departure_airport_icao'] = $line[11];
		        $data['departure_airport_time'] = $line[22]; // FIXME put a :
		        $data['arrival_airport_icao'] = $line[13];
			$data['frequency'] = $line[4];
			$data['type'] = $line[18];
			$data['range'] = $line[19];
			$data['info'] = $line[35];
    			$data['id_source'] = $id_source;
	    		//$data['arrival_airport_time'] = ;
	    		if ($line[9] != '') {
	    		    $aircraft_data = explode('/',$line[9]);
	    		    if (isset($aircraft_data[1])) {
	    			$data['aircraft_icao'] = $aircraft_data[1];
	    		    }
        		}
	    		if ($value == 'whazzup') $data['format_source'] = 'whazzup';
	    		elseif ($value == 'vatsimtxt') $data['format_source'] = 'vatsimtxt';
    			if ($line[3] == 'PILOT') $SI->add($data);
			elseif ($line[3] == 'ATC') {
				//print_r($data);
				$data['info'] = str_replace('^&sect;','<br />',$data['info']);
				$data['info'] = str_replace('&amp;sect;','',$data['info']);
				$typec = substr($data['ident'],-3);
				if ($typec == 'APP') $data['type'] = 'Approach';
				elseif ($typec == 'TWR') $data['type'] = 'Tower';
				elseif ($typec == 'OBS') $data['type'] = 'Observer';
				elseif ($typec == 'GND') $data['type'] = 'Ground';
				elseif ($typec == 'DEL') $data['type'] = 'Delivery';
				elseif ($typec == 'DEP') $data['type'] = 'Departure';
				elseif ($typec == 'FSS') $data['type'] = 'Flight Service Station';
				elseif ($typec == 'CTR') $data['type'] = 'Control Radar or Centre';
				elseif ($data['type'] == '') $data['type'] = 'Observer';
				
				echo $ATC->add($data['ident'],$data['frequency'],$data['latitude'],$data['longitude'],$data['range'],$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name']);
			}
    			unset($data);
    		    }
    		}
    	    }
    	    if ($value == 'whazzup') $last_exec['whazzup'] = time();
    	    elseif ($value == 'vatsimtxt') $last_exec['vatsimtxt'] = time();
    	} elseif ($value == 'aircraftlistjson' && (time() - $last_exec['aircraftlistjson'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id],'get','','','','','20');
	    if ($buffer != '') {
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['acList'])) {
		foreach ($all_data['acList'] as $line) {
		    $data = array();
		    $data['hex'] = $line['Icao']; // hex
		    if (isset($line['Call'])) $data['ident'] = $line['Call']; // ident
		    if (isset($line['Alt'])) $data['altitude'] = $line['Alt']; // altitude
		    if (isset($line['Spd'])) $data['speed'] = $line['Spd']; // speed
		    if (isset($line['Trak'])) $data['heading'] = $line['Trak']; // heading
		    if (isset($line['Lat'])) $data['latitude'] = $line['Lat']; // lat
		    if (isset($line['Long'])) $data['longitude'] = $line['Long']; // long
		    //$data['verticalrate'] = $line['']; // verticale rate
		    if (isset($line['Sqk'])) $data['squawk'] = $line['Sqk']; // squawk
		    $data['emergency'] = ''; // emergency
		    if (isset($line['Reg'])) $data['registration'] = $line['Reg'];
		    if (isset($line['PosTime'])) $data['datetime'] = date('Y-m-d H:i:s',$line['PosTime']/1000);
		    else $data['datetime'] = date('Y-m-d H:i:s');
		    if (isset($line['Type'])) $data['aircraft_icao'] = $line['Type'];
	    	    $data['format_source'] = 'aircraftlistjson';
		    $data['id_source'] = $id_source;
		    if (isset($data['datetime'])) $SI->add($data);
		    unset($data);
		}
	    } else {
		foreach ($all_data as $line) {
		    $data = array();
		    $data['hex'] = $line['hex']; // hex
		    $data['ident'] = $line['flight']; // ident
		    $data['altitude'] = $line['altitude']; // altitude
		    $data['speed'] = $line['speed']; // speed
		    $data['heading'] = $line['track']; // heading
		    $data['latitude'] = $line['lat']; // lat
		    $data['longitude'] = $line['lon']; // long
		    $data['verticalrate'] = $line['vrt']; // verticale rate
		    $data['squawk'] = $line['squawk']; // squawk
		    $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s');
	    	    $data['format_source'] = 'aircraftlistjson';
    		    $data['id_source'] = $id_source;
		    $SI->add($data);
		    unset($data);
		}
	    }
	    }
    	    $last_exec['aircraftlistjson'] = time();
    	} elseif ($value == 'planeupdatefaa' && (time() - $last_exec['planeupdatefaa'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['planes'])) {
		foreach ($all_data['planes'] as $key => $line) {
		    $data = array();
		    $data['hex'] = $key; // hex
		    $data['ident'] = $line[3]; // ident
		    $data['altitude'] = $line[6]; // altitude
		    $data['speed'] = $line[8]; // speed
		    $data['heading'] = $line[7]; // heading
		    $data['latitude'] = $line[4]; // lat
		    $data['longitude'] = $line[5]; // long
		    //$data['verticalrate'] = $line[]; // verticale rate
		    $data['squawk'] = $line[10]; // squawk
		    $data['emergency'] = ''; // emergency
		    $data['registration'] = $line[2];
		    $data['aircraft_icao'] = $line[0];
		    $deparr = explode('-',$line[1]);
		    if (count($deparr) == 2) {
			$data['departure_airport_icao'] = $deparr[0];
			$data['arrival_airport_icao'] = $deparr[1];
		    }
		    $data['datetime'] = date('Y-m-d H:i:s',$line[9]);
	    	    $data['format_source'] = 'planeupdatefaa';
    		    $data['id_source'] = $id_source;
		    $SI->add($data);
		    unset($data);
		}
	    }
    	    $last_exec['planeupdatefaa'] = time();
    	} elseif ($value == 'fr24json' && (time() - $last_exec['fr24json'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $key => $line) {
		if ($key != 'full_count' && $key != 'version' && $key != 'stats') {
		    $data = array();
		    $data['hex'] = $line[0];
		    $data['ident'] = $line[16]; //$line[13]
	    	    $data['altitude'] = $line[4]; // altitude
	    	    $data['speed'] = $line[5]; // speed
	    	    $data['heading'] = $line[3]; // heading
	    	    $data['latitude'] = $line[1]; // lat
	    	    $data['longitude'] = $line[2]; // long
	    	    $data['verticalrate'] = $line[15]; // verticale rate
	    	    $data['squawk'] = $line[6]; // squawk
	    	    $data['aircraft_icao'] = $line[8];
	    	    $data['registration'] = $line[9];
		    $data['departure_airport_iata'] = $line[11];
		    $data['arrival_airport_iata'] = $line[12];
	    	    $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s'); //$line[10]
	    	    $data['format_source'] = 'fr24json';
    		    $data['id_source'] = $id_source;
		    $SI->add($data);
		    unset($data);
		}
	    }
    	    $last_exec['fr24json'] = time();
    	} elseif ($value == 'radarvirtueljson' && (time() - $last_exec['radarvirtueljson'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id],'get','','','','','150');
	    //echo $buffer;
	    $buffer = str_replace(array("\n","\r"),"",$buffer);
	    $buffer = preg_replace('/,"num":(.+)/','}',$buffer);
	    $all_data = json_decode($buffer,true);
	    if (json_last_error() != JSON_ERROR_NONE) {
		die(json_last_error_msg());
	    }
	    if (isset($all_data['mrkrs'])) {
		foreach ($all_data['mrkrs'] as $key => $line) {
		    if (isset($line['inf'])) {
			$data = array();
			$data['hex'] = $line['inf']['ia'];
			if (isset($line['inf']['cs'])) $data['ident'] = $line['inf']['cs']; //$line[13]
	    		$data['altitude'] = round($line['inf']['al']*3.28084); // altitude
	    		if (isset($line['inf']['gs'])) $data['speed'] = round($line['inf']['gs']*0.539957); // speed
	    		if (isset($line['inf']['tr'])) $data['heading'] = $line['inf']['tr']; // heading
	    		$data['latitude'] = $line['pt'][0]; // lat
	    		$data['longitude'] = $line['pt'][1]; // long
	    		//if (isset($line['inf']['vs'])) $data['verticalrate'] = $line['inf']['vs']; // verticale rate
	    		if (isset($line['inf']['sq'])) $data['squawk'] = $line['inf']['sq']; // squawk
	    		//$data['aircraft_icao'] = $line[8];
	    		if (isset($line['inf']['rc'])) $data['registration'] = $line['inf']['rc'];
			//$data['departure_airport_iata'] = $line[11];
			//$data['arrival_airport_iata'] = $line[12];
	    		//$data['emergency'] = ''; // emergency
			$data['datetime'] = date('Y-m-d H:i:s',$line['inf']['dt']); //$line[10]
	    		$data['format_source'] = 'radarvirtueljson';
    			$data['id_source'] = $id_source;
			$SI->add($data);
			unset($data);
		    }
		}
	    }
    	    $last_exec['radarvirtueljson'] = time();
    	} elseif ($value == 'pirepsjson' && (time() - $last_exec['pirepsjson'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id]);
	    $all_data = json_decode(utf8_encode($buffer),true);
	    
	    if (isset($all_data['pireps'])) {
	        foreach ($all_data['pireps'] as $line) {
		    $data = array();
		    $data['hex'] = str_pad(dechex($line['id']),6,'000000',STR_PAD_LEFT);
		    $data['ident'] = $line['callsign']; // ident
		    if (isset($line['pilotid'])) $data['pilot_id'] = $line['pilotid']; // pilot id
		    if (isset($line['name'])) $data['pilot_name'] = $line['name']; // pilot name
		    if (isset($line['alt'])) $data['altitude'] = $line['alt']; // altitude
		    if (isset($line['gs'])) $data['speed'] = $line['gs']; // speed
		    if (isset($line['heading'])) $data['heading'] = $line['heading']; // heading
		    if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
		    $data['latitude'] = $line['lat']; // lat
		    $data['longitude'] = $line['lon']; // long
		    //$data['verticalrate'] = $line['vrt']; // verticale rate
		    //$data['squawk'] = $line['squawk']; // squawk
		    //$data['emergency'] = ''; // emergency
		    if (isset($line['depicao'])) $data['departure_airport_icao'] = $line['depicao'];
		    if (isset($line['deptime'])) $data['departure_airport_time'] = $line['deptime'];
		    if (isset($line['arricao'])) $data['arrival_airport_icao'] = $line['arricao'];
		    //$data['arrival_airport_time'] = $line['arrtime'];
		    if (isset($line['aircraft'])) $data['aircraft_icao'] = $line['aircraft'];
		    if (isset($line['transponder'])) $data['squawk'] = $line['transponder'];
		    if (isset($line['atis'])) $data['info'] = $line['atis'];
		    else $data['info'] = '';
		    $data['format_source'] = 'pireps';
    		    $data['id_source'] = $id_source;
		    $data['datetime'] = date('Y-m-d H:i:s');
		    if ($line['icon'] == 'plane') {
			$SI->add($data);
		    //    print_r($data);
    		    } elseif ($line['icon'] == 'ct') {
			$data['info'] = str_replace('^&sect;','<br />',$data['info']);
			$data['info'] = str_replace('&amp;sect;','',$data['info']);
			$typec = substr($data['ident'],-3);
			$data['type'] = '';
			if ($typec == 'APP') $data['type'] = 'Approach';
			elseif ($typec == 'TWR') $data['type'] = 'Tower';
			elseif ($typec == 'OBS') $data['type'] = 'Observer';
			elseif ($typec == 'GND') $data['type'] = 'Ground';
			elseif ($typec == 'DEL') $data['type'] = 'Delivery';
			elseif ($typec == 'DEP') $data['type'] = 'Departure';
			elseif ($typec == 'FSS') $data['type'] = 'Flight Service Station';
			elseif ($typec == 'CTR') $data['type'] = 'Control Radar or Centre';
			else $data['type'] = 'Observer';
			echo $ATC->add($data['ident'],'',$data['latitude'],$data['longitude'],'0',$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name']);
		    }
		    unset($data);
		}
	    }
    	    $last_exec['pirepsjson'] = time();
    	} elseif ($value == 'phpvmacars' && (time() - $last_exec['phpvmacars'] > $globalMinFetch)) {
	    $buffer = $Common->getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $line) {
	        $data = array();
	        //$data['id'] = $line['id']; // id not usable
	        $data['hex'] = substr(str_pad(bin2hex($line['flightnum']),6,'000000',STR_PAD_LEFT),-6); // hex
	        if (isset($line['pilotname'])) $data['pilot_name'] = $line['pilotname'];
	        if (isset($line['pilotid'])) $data['pilot_id'] = $line['pilotid'];
	        $data['ident'] = $line['flightnum']; // ident
	        $data['altitude'] = $line['alt']; // altitude
	        $data['speed'] = $line['gs']; // speed
	        $data['heading'] = $line['heading']; // heading
	        $data['latitude'] = $line['lat']; // lat
	        $data['longitude'] = $line['lng']; // long
	        $data['verticalrate'] = ''; // verticale rate
	        $data['squawk'] = ''; // squawk
	        $data['emergency'] = ''; // emergency
	        $data['datetime'] = $line['lastupdate'];
	        $data['departure_airport_icao'] = $line['depicao'];
	        $data['departure_airport_time'] = $line['deptime'];
	        $data['arrival_airport_icao'] = $line['arricao'];
    		$data['arrival_airport_time'] = $line['arrtime'];
    		$data['registration'] = $line['aircraft'];
		if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
		if (isset($line['aircraftname'])) {
		    $line['aircraftname'] = strtoupper($line['aircraftname']);
		    $line['aircraftname'] = str_replace('BOEING ','B',$line['aircraftname']);
	    	    $aircraft_data = explode('-',$line['aircraftname']);
	    	    if (isset($aircraft_data[1]) && strlen($aircraft_data[0]) < 5) $data['aircraft_icao'] = $aircraft_data[0];
	    	    elseif (isset($aircraft_data[1]) && strlen($aircraft_data[1]) < 5) $data['aircraft_icao'] = $aircraft_data[1];
	    	    else {
	    		$aircraft_data = explode(' ',$line['aircraftname']);
	    		if (isset($aircraft_data[1])) $data['aircraft_icao'] = $aircraft_data[1];
	    		else $data['aircraft_icao'] = $line['aircraftname'];
	    	    }
	    	}
    		if (isset($line['route'])) $data['waypoints'] = $line['route'];
    		$data['id_source'] = $id_source;
	        $data['format_source'] = 'phpvmacars';
		$SI->add($data);
		unset($data);
	    }
    	    $last_exec['phpvmacars'] = time();
	} elseif ($value == 'sbs' || $value == 'tsv' || $value == 'raw' || $value == 'aprs' || $value == 'beast') {
	    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();

	    //$read = array( $sockets[$id] );
	    $read = $sockets;
	    $n = @socket_select($read, $write = NULL, $e = NULL, $globalSBS1TimeOut);
	    if ($n > 0) {
		foreach ($read as $nb => $r) {
			$value = $formats[$nb];
        	    $buffer = socket_read($r, 3000,PHP_NORMAL_READ);
		    // lets play nice and handle signals such as ctrl-c/kill properly
		    //if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
		    $dataFound = false;
		    $error = false;
		    //$SI::del();
		    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
		    // SBS format is CSV format
		    if ($buffer != '') {
			$tt = 0;
			if ($value == 'raw') {
			    // AVR format
			    $data = $SBS->parse($buffer);
			    if (is_array($data)) {
				$data['datetime'] = date('Y-m-d H:i:s');
				$data['format_source'] = 'raw';
                                $SI->add($data);
                            }
			} elseif ($value == 'beast') {
			    echo 'Beast Binary format not yet supported. Beast AVR format is supported in alpha state'."\n";
			    die;
			} elseif ($value == 'tsv' || substr($buffer,0,4) == 'clock') {
			    $line = explode("\t", $buffer);
			    for($k = 0; $k < count($line); $k=$k+2) {
				$key = $line[$k];
			        $lined[$key] = $line[$k+1];
			    }
    			    if (count($lined) > 3) {
    				$data['hex'] = $lined['hexid'];
    				//$data['datetime'] = date('Y-m-d H:i:s',strtotime($lined['clock']));;
    				$data['datetime'] = date('Y-m-d H:i:s');;
    				if (isset($lined['ident'])) $data['ident'] = $lined['ident'];
    				if (isset($lined['lat']))$data['latitude'] = $lined['lat'];
    				if (isset($lined['lon']))$data['longitude'] = $lined['lon'];
    				if (isset($lined['speed']))$data['speed'] = $lined['speed'];
    				if (isset($lined['squawk']))$data['squawk'] = $lined['squawk'];
    				if (isset($lined['alt']))$data['altitude'] = $lined['alt'];
    				if (isset($lined['heading']))$data['heading'] = $lined['heading'];
    				$data['id_source'] = $id_source;
    				$data['format_source'] = 'tsv';
    				$SI->add($data);
    				unset($lined);
    				unset($data);
    			    } else $error = true;
			} elseif ($value == 'aprs' && $use_aprs) {
			    if ($aprs_connect == 0) {
				$send = @ socket_send( $r  , $aprs_login , strlen($aprs_login) , 0 );
				$aprs_connect = 1;
			    }
			    if ( $aprs_keep>60 && time() - $aprs_last_tx > $aprs_keep ) {
				$aprs_last_tx = time();
				$data_aprs = "# Keep alive";
				$send = @ socket_send( $r  , $data_aprs , strlen($data_aprs) , 0 );
			    }
			    //echo 'Connect : '.$aprs_connect.' '.$buffer."\n";
			    if (substr($buffer,0,1) != '#') {
				$line = $APRS->parse($buffer);
				if (is_array($line) && isset($line['address']) && $line['address'] != '' && isset($line['ident'])) {
				    $data = array();
				    $data['hex'] = $line['address'];
				    $data['datetime'] = date('Y-m-d H:i:s',$line['timestamp']);
				    $data['ident'] = $line['ident'];
				    $data['latitude'] = $line['latitude'];
				    $data['longitude'] = $line['longitude'];
				    //$data['verticalrate'] = $line[16];
				    if (isset($line['speed'])) $data['speed'] = $line['speed'];
				    else $data['speed'] = 0;
				    $data['altitude'] = $line['altitude'];
				    if (isset($line['course'])) $data['heading'] = $line['course'];
				    else $data['heading'] = 0;
				    $data['aircraft_type'] = $line['stealth'];
				    $data['noarchive'] = true;
    				    $data['id_source'] = $id_source;
				    $data['format_source'] = 'aprs';
				    //print_r($data);
				    if ($line['stealth'] == 0) $send = $SI->add($data);
				    unset($data);
				} 
				//elseif ($line == false && $globalDebug) echo 'Ignored ('.$buffer.")\n";
				elseif ($line == true && $globalDebug) echo '!! Failed : '.$buffer."!!\n";
			    }
			} else {
			    $line = explode(',', $buffer);
    			    if (count($line) > 20) {
    			    	$data['hex'] = $line[4];
    				/*
    				$data['datetime'] = $line[6].' '.$line[7];
    					date_default_timezone_set($globalTimezone);
    					$datetime = new DateTime($data['datetime']);
    					$datetime->setTimezone(new DateTimeZone('UTC'));
    					$data['datetime'] = $datetime->format('Y-m-d H:i:s');
    					date_default_timezone_set('UTC');
    				*/
    				// Force datetime to current UTC datetime
    				$data['datetime'] = date('Y-m-d H:i:s');
    				$data['ident'] = trim($line[10]);
    				$data['latitude'] = $line[14];
    				$data['longitude'] = $line[15];
    				$data['verticalrate'] = $line[16];
    				$data['emergency'] = $line[20];
    				$data['speed'] = $line[12];
    				$data['squawk'] = $line[17];
    				$data['altitude'] = $line[11];
    				$data['heading'] = $line[13];
    				$data['ground'] = $line[21];
    				$data['emergency'] = $line[19];
    				$data['format_source'] = 'sbs';
    				$data['id_source'] = $id_source;
    				$send = $SI->add($data);
    				unset($data);
    			    } else $error = true;
			    if ($error) {
				if (count($line) > 1 && ($line[0] == 'STA' || $line[0] == 'AIR' || $line[0] == 'SEL' || $line[0] == 'ID' || $line[0] == 'CLK')) { 
					if ($globalDebug) echo "Not a message. Ignoring... \n";
				} else {
					if ($globalDebug) echo "Wrong line format. Ignoring... \n";
					if ($globalDebug) {
						echo $buffer;
						print_r($line);
					}
					socket_close($r);
					if ($globalDebug) echo "Reconnect after an error...\n";
					connect_all($hosts);
				}
			    }
			}
			// Sleep for xxx microseconds
			if (isset($globalSBSSleep)) usleep($globalSBSSleep);
		    } else {
			$tt++;
			if ($tt > 5) {
			    if ($globalDebug)echo "ERROR : Reconnect...";
			    @socket_close($r);
			    sleep(2);
			    connect_all($hosts);
			    break;
			    $tt = 0;
			}
		    }
		}
	    } else {
		$error = socket_strerror(socket_last_error());
		if ($globalDebug) echo "ERROR : socket_select give this error ".$error . "\n";
		if (($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) || time() - $time >= $timeout) {
			if (isset($globalDebug)) echo "Restarting...\n";
			// Restart the script if possible
			if (is_array($sockets)) {
			    if ($globalDebug) echo "Shutdown all sockets...";
			    foreach ($sockets as $sock) {
				@socket_shutdown($sock,2);
				@socket_close($sock);
			    }
			}
			    if ($globalDebug) echo "Restart all connections...";
			    sleep(2);
			    $time = time();
			    connect_all($hosts);

		}
	    }
	}
	if ($globalDaemon == false) {
	    $SI->checkAll();
	}
    }
}

?>
