<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use DB;
use App\Services\Getcsvdataapi;
class GenerateKmlController extends Controller
{

	function __construct(Getcsvdataapi $chfornotif){
        $this->todaypath = 'data'.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $this->Fldrpth = 'contour/';
        $this->pthTmp = 'contour/csvtemplate/';
        $this->chfornotif = $chfornotif;
    }    
	public function getdata($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        while (!feof($file_handle) ) {
            $line_of_text[] = fgetcsv($file_handle, 1024);
        }
        fclose($file_handle);
        return $line_of_text;
    }
    public function deleteKmlFile(){
        $files = glob($this->Fldrpth.'*.kml');
        foreach($files as $file){
            if(is_file($file))
            unlink($file); 
        }
    }
    public function createkmlfile(){
        $provinces = DB::table('tbl_provinces')->get();
        foreach ($provinces as $province) {
            $pname = str_replace(' ', '', $province->name);
            $newfile = $this->Fldrpth.$pname.date("mdyhi").'.kml';
            $handle = fopen($newfile, 'w'); 
        }    		
    }
    public function putKmlheadear(){
        $files = glob($this->Fldrpth.'*.kml');
        $headerfoot = $this->getcsv($this->pthTmp,'headerfooter.csv');
        $headerCnt = $headerfoot[0][0];
        foreach($files as $file){
            $handle = fopen($file, 'w');
            if (fwrite($handle,$headerCnt) === FALSE) {
                echo "Cannot write to file ($file)";
                exit;
            }
        }
    }
    

    public function generateKmlFile(){
        $sensordatas = $this->getDuplicates();

        $municipalities = DB::table('tbl_municipality')->get();
        $provinces = DB::table('tbl_provinces')->get();

        $farray = [];
        $farraycount = 0;

        $perkmlcontents = [];

        foreach ($provinces as $province) {
            foreach ($sensordatas as $sensordata) {
                    $rainAverage = $sensordata['totalsCum'] / $sensordata['count'];
                    if ($sensordata['sProvid'] == $province->id) { //$sensordata for kalinga
                        $pname = str_replace(' ', '', $province->name); 

                        $files = glob($this->Fldrpth.$pname.'/*.csv'); 
                        if (!empty($files)) { 
                            foreach ($municipalities as $municipality) {
                                if($sensordata["sMunid"] == $municipality->id){
                                    foreach($files as $value){
                                        $exp_key = explode('_', $value);
                                        if($exp_key[1] == $sensordata['sMunid']){
                                            $perkmlcontents = $this->getdata($value); 
                                            if($rainAverage <= 100){
                                                //light
                                                $farray[$province->id][$farraycount] = array(
                                                    'sensorid' => $sensordata['sID'],
                                                    'sensorname' => $sensordata['sLoc'],
                                                    'sensorvalue' => $rainAverage,
                                                    'status' => $perkmlcontents[0][0],
                                                    'kmlcoords' => $perkmlcontents[1][0],
                                                    'municipality_id' => $municipality->id
                                                );
                                            }else if(($rainAverage >= 101) && ($rainAverage <= 200)){
                                                //moderate

                                                $farray[$province->id][$farraycount] = array(
                                                    'sensorid' => $sensordata['sID'],
                                                    'sensorname' => $sensordata['sLoc'],
                                                    'sensorvalue' => $rainAverage,
                                                    'status' => $perkmlcontents[0][1],
                                                    'kmlcoords' => $perkmlcontents[1][1],
                                                    'municipality_id' => $municipality->id
                                                );

                                            }else if(($rainAverage >= 201) && ($rainAverage <= 300)){
                                                //heavy

                                                $farray[$province->id][$farraycount] = array(
                                                    'sensorid' => $sensordata['sID'],
                                                    'sensorname' => $sensordata['sLoc'],
                                                    'sensorvalue' => $rainAverage,
                                                    'status' => $perkmlcontents[0][2],
                                                    'kmlcoords' => $perkmlcontents[1][2],
                                                    'municipality_id' => $municipality->id
                                                );


                                            }else if(($rainAverage >= 301) && ($rainAverage <= 400)){
                                                //intense
                                                $farray[$province->id][$farraycount] = array(
                                                    'sensorid' => $sensordata['sID'],
                                                    'sensorname' => $sensordata['sLoc'],
                                                    'sensorvalue' => $rainAverage,
                                                    'status' => $perkmlcontents[0][3],
                                                    'kmlcoords' => $perkmlcontents[1][3],
                                                    'municipality_id' => $municipality->id
                                                );



                                            }else if($rainAverage >= 401){
                                                //torrential
                                                $farray[$province->id][$farraycount] = array(                                                    
                                                    'sensorid' => $sensordata['sID'],
                                                    'sensorname' => $sensordata['sLoc'],
                                                    'sensorvalue' => $rainAverage,
                                                    'status' => $perkmlcontents[0][4],
                                                    'kmlcoords' => $perkmlcontents[1][4],
                                                    'municipality_id' => $municipality->id
                                                );
                                            }     
                                            $farraycount++;   

                                        }                                        
                                    }             


                                }//loop data
                            }
                        }
                    }
              
            }//sensordatas loop            
        }//provincese loop   
        return $farray;
    }

    public function viewpageGenerate(){    	
    	return view('pages.generatekml');
    }
    public function getcsv($path, $csvfile){
	    $csvfile = $path.$csvfile;
        $csv = '';
        if(file_exists($csvfile)){
            $csv = $this->getdata($csvfile);           
        }
        return $csv;
    }    
    public function checkmncipltyKml(){
        $sensors = DB::table('tbl_sensors')->whereIn('category_id', [1,3,4])->orderBy('municipality_id', 'asc')->get();
        $sensorData = [];
        $x = 0;
        $result = [];
        foreach ($sensors as $sensor) {            
            $sensordata[$x++] = [
                'sProvid' => $sensor->province_id,
                'sMunid' => $sensor->municipality_id,
                'sCum' => $this->chfornotif->getaccum($this->todaypath,$sensor->assoc_file),
                'sID' => $sensor->id,
                'sLoc' => $sensor->address,
                'totalsCum' => 0,
                'count' => 0
            ];
        }        
        return $sensordata;
    }
    public function getDuplicates(){
        $sensordatas = $this->checkmncipltyKml();
     
        $provinces = DB::table('tbl_provinces')->get();
        $municipalities = DB::table('tbl_municipality')->get();

        $sdata = count($sensordatas);
        $dupps = [];
        $duppCnt = 0;
        $arrTotal = array();
        $arrTotalcNt = 0;

        $sdata = count($sensordatas);

        foreach ($sensordatas as $key => $sensordata) {
            $x = 1;
           for ($sDtCnt=0; $sDtCnt < $sdata; $sDtCnt++) {
                $total = 0;
                if (($sensordata['sMunid'] == $sensordatas[$sDtCnt]['sMunid']) && ($sensordata['sID'] != $sensordatas[$sDtCnt]['sID'])) {
                    $total = $sensordata['sCum'] += $sensordatas[$sDtCnt]['sCum'];             
                    $arrTotal[$key] = [
                        "sProvid" => $sensordata['sProvid'],
                        "sMunid" => $sensordata['sMunid'],
                        "sCum" => $sensordata['sCum'],
                        "sID" => $sensordata['sID'],
                        "sLoc" => $sensordata['sLoc'],
                        "totalsCum" => $total,
                        "count" => $sensordata['count']
                      ];  
                }else{
                    $arrTotal[$key] = [
                        "sProvid" => $sensordata['sProvid'],
                        "sMunid" => $sensordata['sMunid'],
                        "sCum" => $sensordata['sCum'],
                        "sID" => $sensordata['sID'],
                        "sLoc" => $sensordata['sLoc'],
                        "totalsCum" => $sensordata['sCum'],
                        "count" => $sensordata['count']
                      ];  
                }                         
            }
        }

        foreach ($arrTotal as $key => $subarr) {
          // Add to the current group count if it exists
          if (isset($dupps[$subarr['sMunid']])) {
            
            $dupps[$subarr['sMunid']]['count']++;
            $dupps[$subarr['sMunid']]['sCum'] += $dupps[$subarr['sMunid']]['sCum'];
          }

          else{ 
            $dupps[$subarr['sMunid']] = $subarr;
            $dupps[$subarr['sMunid']]['count'] = 1;
            $dupps[$subarr['sMunid']]['sCum'] = $dupps[$subarr['sMunid']]['sCum'];
           // isset($dupps[$subarr['sMunid']]) ? $dupps[$subarr['sMunid']]['sCum'] + $dupps[$subarr['sMunid']]['sCum'] : 1;
          }
        }

        return $dupps;

        
    }
    public function postGenerate(Request $request){
        $this->deleteKmlFile();
        $this->createkmlfile();
        $kmlcontents = $this->generateKmlFile();     
        $this->putKmlContent($kmlcontents);        
        return view('pages.generatekml');       
    }
    public function putKmlContent($kmlcontents){
        $files = glob($this->Fldrpth.'*.*');
        $headerfoot = $this->getcsv($this->pthTmp,'headerfooter.csv');
        $headerCnt = $headerfoot[0][0];
        $footerCnt = $headerfoot[1][0];
        $styles = $this->getcsv($this->pthTmp,'kmlstyles.csv');
              
        foreach ($kmlcontents as $key => $kmlcontent) {

            $fLoc = intval($key) - 1; 
            $kmlfile = fopen($files[$fLoc], 'w');

            if (fwrite($kmlfile,$headerCnt) === FALSE) {
                echo "Cannot write to file ($file)";
                exit;
            }
            if (fwrite($kmlfile,$styles[0][0]) === FALSE) {
                echo "Cannot write to file ($file)";
                exit;
            }
            if (fwrite($kmlfile,'<Folder>') === FALSE) {
                echo "Cannot write to file ($file)";
                exit;
            }
            foreach ($kmlcontent as $munCoords) {
                if (fwrite($kmlfile,$munCoords['kmlcoords']) === FALSE) {
                    echo "Cannot write to file ($file)";
                    exit;
                }
            }

            if (fwrite($kmlfile,$footerCnt) === FALSE) {
                echo "Cannot write to file ($file)";
                exit;
            }

            fclose($kmlfile);

        }

            
        
        

    }
}	
