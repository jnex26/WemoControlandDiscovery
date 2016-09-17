#!/usr/bin/php
<?php

function WemoDiscovery($WemoName){ 
 exec('java -jar wemocontrol.jar -d -i bond0 -t 20000',$return);
foreach ($return as $Wemos) {
   echo $Wemos . "\r\n";
   if (strpos(strtolower($Wemos), strtolower($WemoName))!== false) { 
   $targetWemo = explode(",",$Wemos)    ; 
}
}

if (!isset($targetWemo[1])) {echo "Java Discovery Failed"; return false;}; 
$URL = $targetWemo[1];
$wemocurl = curl_init();
curl_setopt_array($wemocurl, array(
    CURLOPT_URL => $URL,
    CURLOPT_RETURNTRANSFER => 1
));
$getwemo=curl_exec($wemocurl); 
if ($getwemo == false) { echo "fallback discovery not found\r\n"; }
if (strpos($getwemo, "404" ) !== false ) { echo "fallback discovery this is no wemo\r\n"; } 
$xmlwemo = new SimpleXMLElement($getwemo);
if (strtolower($xmlwemo->device->friendlyName) == strtolower($WemoName) ){
 echo "#Simple Jedi this is the wemo your looking for\r\n";
 $ultiwemo = $URL;
 $tempfile = "/tmp/".str_replace(" ", "_",strtolower($WemoName)) . ".wemo" ;
 echo $tempfile; 
 file_put_contents( $tempfile, $ultiwemo ) ;
}
return $ultiwemo;
}

function DiscoveryMode($WeMoName) { 
 echo "Looks Like I have to hunt for Wemos don't worry I have entire subroutines for this\r\n";
  $DiscoverWemo=WemoDiscovery($WeMoName);
  if ($DiscoverWemo == false) {
      echo "Wemos are not the most coperative let try that again\r\n";
         $DiscoverWemo=WemoDiscovery($WeMoName);
         if ($DiscoverWemo == false) {
            echo "Ok please go check that sucker is plugged in please\r\n";
            exit(2);
         }}
return $DiscoverWemo;
}

function ValidateWemo($WeMoLocation,$WeMoName) {

$wemocurl = curl_init();
curl_setopt_array($wemocurl, array(
    CURLOPT_URL => $WeMoLocation,
    CURLOPT_RETURNTRANSFER => 1
));
$getwemo=curl_exec($wemocurl);
if ($getwemo == false) { 
  echo "fallback discovery not found\r\n"; 
  return DiscoveryMode($WeMoName);
}
if (strpos($getwemo, "404" ) !== false ) { 
echo "fallback discovery this is no wemo\r\n"; 
  return DiscoveryMode($WeMoName);
}
$xmlwemo = new SimpleXMLElement($getwemo);
if (strtolower($xmlwemo->device->friendlyName) == strtolower($WeMoName) ){
 echo "Simple Jedi this is the wemo your looking for\r\n";
 return $WeMoLocation;
} else { 
return DiscoveryMode($WeMoName);
} 
}

//
// functions above main code below 
//

//
//Make the XML We Need Easier to Understand ! 
//


$WeMoOn= <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <s:Body>
    <u:SetBinaryState xmlns:u="urn:Belkin:service:basicevent:1">
      <BinaryState>1</BinaryState>
    </u:SetBinaryState>
  </s:Body>
</s:Envelope> 
EOD;

$WeMoOff= <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <s:Body>
    <u:SetBinaryState xmlns:u="urn:Belkin:service:basicevent:1">
      <BinaryState>0</BinaryState>
    </u:SetBinaryState>
  </s:Body>
</s:Envelope>
EOD;

$WeMoStatus= <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
   <s:Body>
     <u:GetBinaryState xmlns:u="urn:Belkin:service:basicevent:1">
       <BinaryState>1</BinaryState>
     </u:GetBinaryState>
   </s:Body>
</s:Envelope>
EOD;

$WeMoSignal= <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
   <s:Body>
     <u:GetSignalStrength xmlns:u="urn:Belkin:service:basicevent:1">
        <GetSignalStrength>0</GetSignalStrength>
     </u:GetSignalStrength>
   </s:Body>
</s:Envelope>
EOD;

$WeMoHeaderSET='SOAPACTION: "urn:Belkin:service:basicevent:1#SetBinaryState"';
$WeMoHeaderGETSIG='SOAPACTION: "urn:Belkin:service:basicevent:1#GetSignalStrength"';
$WeMoHeaderGET='SOAPACTION: "urn:Belkin:service:basicevent:1#GetBinaryState"';


//
// Nasty Big Varibles set up now ! 
//



$filename =  "/tmp/".str_replace(" ", "_",strtolower($argv[1])) . ".wemo" ;
if (file_exists($filename))  { 
 echo "No Need for Java" ;
  $JediWemo=file_get_contents($filename);
} else {
  $JediWemo=DiscoveryMode($argv[1]) ;
}

// Not Even sure this is the right Wemo yet (unless discovered but doing this anyway ) ! 

$JediWemo=ValidateWemo($JediWemo, $argv[1]);

// Well After This far we now know we are talking to the right WeMo 
// Lets give it something to do ;) 
// but first lets correct hte URL for the WEMO 
$WeMoURL = substr($JediWemo,0,strrpos($JediWemo, "/"))."/upnp/control/basicevent1";

if (!isset($argv[2])) { echo "Valid Commands are as follows ON/OFF/SIGNAL/STATUS\r\n Syntax is wemo_control.php \"Wemo Switch name\" Action" ; exit(2); }  

switch (strtolower($argv[2])) { 
   case "on":
        $WeMoHeader=$WeMoHeaderSET ;
        $WeMoData=$WeMoOn          ;
        break; 
   case "off":
        $WeMoHeader=$WeMoHeaderSET ;
        $WeMoData=$WeMoOff          ;
        break;
   case "status":
        $WeMoHeader=$WeMoHeaderGET ;
        $WeMoData=$WeMoStatus          ;
        break;
   case "signal":
        $WeMoHeader=$WeMoHeaderGETSIG ;
        $WeMoData=$WeMoSignal          ;
        break;
   case "*": 
        echo "Valid Commands are as follows ON/OFF/SIGNAL/STATUS\r\n Syntax is wemo_control.php \"Wemo Switch name\" Action\r\n" ; 
        exit(2);
        break;
}



$WeMocurl = curl_init();
curl_setopt($WeMocurl,CURLOPT_POST,1);
curl_setopt($WeMocurl,CURLOPT_USERAGENT, '');
curl_setopt($WeMocurl,CURLOPT_HTTPHEADER,array('Content-type: text/xml; charset="utf-8"', $WeMoHeader)); 
curl_setopt($WeMocurl,CURLOPT_URL,$WeMoURL);
curl_setopt($WeMocurl,CURLOPT_POSTFIELDS, $WeMoData);
curl_setopt($WeMocurl,CURLOPT_RETURNTRANSFER,1);

$WeMoOutput=curl_exec($WeMocurl);

$xmlwemo = new SimpleXMLElement($WeMoOutput);
##if ($xmlwemo->device->friendlyName) 
echo $xmlwemo->SetBinaryStateResponce->BinaryState;

