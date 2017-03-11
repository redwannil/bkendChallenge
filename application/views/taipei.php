<?php

$status_code = -3;
$spot1=array();
$spot2=array();

// $now_lat = 25.034153;
// $now_lng = 121.468509;

$now_lat =  $this->input->get('lat');
$now_lng =  $this->input->get('lng');

$url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=".$now_lat.",".$now_lng;
$response  = get_web_page($url);
$json = json_decode($response,true);
//-1: invalid latitude or longitude
if($json["status"] != "OK"){
	$status_code = -1;
	print_json($status_code,$spot1,$spot2);
	exit;
}

//-2: given location not in Taipei City 
if (strpos($json["results"][0]["formatted_address"], 'Taipei City') != true) {
    $status_code = -2;
	print_json($status_code,$spot1,$spot2);
	exit;
}else{
	$status_code = 0;
}



//ubkike api
// $link = "http://data.taipei/opendata/datalist/apiAccess?scope=resourceAquire&rid=ddb80380-f1b3-4f8e-8016-7ed9cba571d5";
$link = "http://data.taipei/youbike";
$response  = gzdecode (file_get_contents($link));

// echo $response;
$ubike_json = json_decode($response,true);
$ubike_info = $ubike_json["retVal"];

$near1_data = array();
$near1_dist = 99999999;
$near2_data = array();
$near2_dist = 99999999;
foreach ($ubike_info as $key => $value) {
	if($value["sbi"] > 0){
		$dis_temp = getDis($now_lat, (float)$value['lat'] ,$now_lng, (float)$value['lng'] );
		if($near1_dist > $dis_temp){
			$near2_data = $near1_data;
			$near2_dist = $near1_dist;
			$near1_dist = $dis_temp;
			$near1_data = array("station"=>$value["sna"],"num_ubike"=>$value["sbi"]);
		}else if($near2_dist > $dis_temp){
			$near2_dist = $dis_temp;
			$near2_data = array("station"=>$value["sna"],"num_ubike"=>$value["sbi"]);
		}
		
	}
	
	
}
if($near1_dist == 99999999 && $near2_dist == 99999999 ){
	$status_code = 1;
	print_json($status_code,$spot1,$spot2);
	exit;
}

// 0: OK  -3: system error
if($status_code == 0){
	($near2_dist == 99999999)?print_json($status_code,$near1_data,$spot2):print_json($status_code,$near1_data,$near2_data);
}else{
	$status_code = -3;
	print_json($status_code,$spot1,$spot2);
	exit;
}

function print_json($status,$spot1,$spot2){
	if($status < 0){
		echo json_encode(array("code"=>$status,"result"=> array()));
	}else{
		echo json_encode(array("code"=>$status,"result"=> array($spot1,$spot2)),JSON_UNESCAPED_UNICODE);	
	}
	
}

function get_web_page($url) {
    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => false,  // don't return headers
        CURLOPT_FOLLOWLOCATION => true,   // follow redirects
        CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
        CURLOPT_ENCODING       => "",     // handle compressed
        CURLOPT_USERAGENT      => "test", // name of client
        CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
        CURLOPT_TIMEOUT        => 120,    // time-out on response
    ); 

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);

    $content  = curl_exec($ch);

    curl_close($ch);

    return $content;
}


function getDis($lat1,$lat2,$lng1,$lng2)
{
 $radLat1 = deg2rad($lat1);
 $radLat2 = deg2rad($lat2);

 $radLng1 = deg2rad($lng1);
 $radLng2 = deg2rad($lng2);


 $a = $radLat1 - $radLat2;
 $b = $radLng1 - $radLng2;

 $s = 2*asin(sqrt( pow(sin($a*0.5),2) + cos($radLat1)*cos($radLat2)*pow(sin($b*0.5),2) ));

 $s = $s*6378137;

 return $s;
}


function getLatLong($address){
	if (!is_string($address))die("All Addresses must be passed as a string");
$_url = sprintf('http://maps.google.com/maps?output=js&q;=%s',rawurlencode($address));
$_result = false;
if($_result = file_get_contents($_url)) {
if(strpos($_result,'errortips') > 1 || strpos($_result,'Did you mean:') !== false) return false;
preg_match('!center:\s*{lat:\s*(-?\d+\.\d+),lng:\s*(-?\d+\.\d+)}!U', $_result, $_match);
$_coords['lat'] = $_match[1];
$_coords['long'] = $_match[2];
}
return $_coords;
}
?>