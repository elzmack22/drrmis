<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Http\Requests;

use DB;
use Session;

use App\Models\Category;
use App\Models\Sensors;
use App\Models\Province;
use App\Models\Municipality;
use App\Models\Threshold;
use Illuminate\Support\Facades\Input;
use App\Models\User;
use App\Models\Notifval;
use App\Models\Notification;
use Response;
use Auth;
use File;
class Getcsvdataapi
{   
    public $arrtotals = [];
    public $waterlvltotal = [];
    function __construct(){
    	//replace if uploaded or new domain
       $this->todaypath = 'public/data'.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $this->yesterday = 'public/data'.'/'.date('Y/m/d',strtotime("-1 days")).'/';
        $this->lasttwodayspath = 'public/data'.'/'.date('Y/m/d',strtotime("-2 days")).'/';
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
    public function getfullaccum($path,$csv){
        $csvfile = $this->getcsv($path,$csv);
        $perline = count($csvfile);
        $sum = 0;
        for ($i=0; $i < $perline; $i++) { 
            $sum+= $csvfile[$i]['value'];
        }
        return $sum;
    }   
    public function getaccum($path,$csv){
        $counter = [];
        $count = 0;
        $todayfile = $csv."-".date('Ymd').".csv";  
        $csvfile = $this->getcsv($path,$todayfile);   

        $perline = count($csvfile);

           for ($i=0; $i < $perline; $i++) { 
            $csvtime = explode(":",$csvfile[$i]['time']);  

            if($csvtime[0] >= 8){
                $cat = $csvfile[$i]['category'];
                if($cat == 2){
                    $counter[$count++] = array(
                            'time' =>  $csvfile[$i]['time'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '2',
                            'value' => '0'
                        );
                }else if($cat == 3){
                    $counter[$count++] = array(
                            'time' =>   $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter[$count++] = array(                        
                            'time' =>  $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'category' => '1',
                        );
                }
            }

        }

        $sum = 0;
        $limit = count($counter);
        for ($i=0; $i < $limit; $i++) { 
            $sum+= $counter[$i]['value'];

        }

       return $sum;

    }
    public function getcurrentreading($path,$csv){
        $csvfile = $this->getcsv($path,$csv);
        $limit = count($csvfile);
        $currentreading = 0;    
        for ($i=0; $i < $limit; $i++) { 
            $currentreading = $csvfile[$i]['value'];
        }
        return $currentreading;
    }
    public function getcurrentwaterlevel($path,$csv){
        $csvfile = $this->getcsv($path,$csv);
        $limit = count($csvfile);
        $currentreading = 0;    
        $fnal = 0;
        for ($i=0; $i < $limit; $i++) { 
            if(!empty($csvfile[$i]['waterlvl'])){
               $currentreading = $csvfile[$i]['waterlvl']; 
           }else{
                $currentreading = 0;
           }
            
        }
        $fnal = $currentreading / 100;
        
        return $fnal;
    }
    public function getcurrenttime($path,$csv){
        $csvfile = $this->getcsv($path,$csv);
        $limit = count($csvfile);
        $currentreading = 0;    
        for ($i=0; $i < $limit; $i++) { 
            $currentreading = $csvfile[$i]['time'];
        }
        return $currentreading;
    }
    public function getstatus($path,$csv){
        $filestat = '';
        $csvfile = $this->getcsv($path,$csv);

        $csvfcont = count($csvfile);

        if($csvfcont < 2){
            $filestat = 'no_data';
        }else{
            $currtime = 0;
            $time = 0;
            for ($i=0; $i < $csvfcont; $i++) { 
                $currtime = $csvfile[$i]['time'];
                $currtime = explode(":",$currtime);
                $time = $currtime[0];
            }
            if($time > intval(date('H'))){
                $filestat = 'with_data';
            }else if($time == intval(date('H'))){
                $filestat = 'with_data';
            }else{
                $res = intval(date('H')) - $time;
                if($res >= 2){
                    $filestat = 'with_data';
                }else{
                    $filestat = 'with_data';
                }
            }
        }
        return $filestat;
    }
    public function getcsv($rootpath, $csvfile){
        $counter = [];
        $count = 0;
        $csvdatetime = '';
        $csvtime='';
        $sensors = DB::table('tbl_sensors')->whereIn('category_id', [1,2,3])->get();
        $mycsvFile = $rootpath.$csvfile;

        $waterlvl = '-WATERLEVEL-';
        $tndem = 'WATERLEVEL_';
    
        if(file_exists($mycsvFile)){
            $csv = $this->getdata($mycsvFile);
            for($x=0;$x<=6;$x++){
            unset($csv[$x]);
            }

            $perlines = count($csv)+6;

            for ($i=7; $i < $perlines; $i++) { 
                $csvdatetime = $csv[$i][0]; 
                $csvdatetimearray = explode(" ", $csvdatetime);         
                $csvtime = explode(":", $csvdatetimearray[1]);      
                    
                    if (strpos($mycsvFile,$waterlvl) !== false) {
                        $counter[$count++] = array(
                            'date' =>  $csvdatetimearray[0],
                            'time' =>  $csvdatetimearray[1],
                            'waterlvl' => $csv[$i][1],
                            'category' => '2',
                        );
                    }else if (strpos($mycsvFile,$tndem) !== false){
                        $counter[$count++] = array(
                            'date' =>  $csvdatetimearray[0],
                            'time' =>  $csvdatetimearray[1],
                            'value' => $csv[$i][1],
                            'waterlvl' => $csv[$i][2],
                            'category' => '3',
                        );
                    }else{
                        $counter[$count++] = array(
                            'date' =>  $csvdatetimearray[0],
                            'time' =>  $csvdatetimearray[1],
                            'value' => $csv[$i][1],
                            'category' => '1',
                        );
                    }
                
            }   
        }
        return $counter;
    }
    public function gettodaycsv($path,$assoc){
        $counter = [];
        $count = 0;
        $fname = $assoc."-".date('Ymd').".csv";

        $yestfile = $this->getcsv($path,$fname);
        $csvlimit = count($yestfile);
        for ($i=0; $i < $csvlimit; $i++) { 
            $time = $yestfile[$i]['time'];
            $cat = $yestfile[$i]['category'];
            $csvtime = explode(":", $time); 

            if($csvtime[0] >= 8){
                if($cat == 2){
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'waterlvl' => $yestfile[$i]['waterlvl'],
                            'category' => '2',
                        );
                }else if($cat == 3){
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'value' => $yestfile[$i]['value'],
                            'waterlvl' => $yestfile[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'value' => $yestfile[$i]['value'],
                            'category' => '1',
                        );
                }
            }
        }

        return $counter;
    }
    
    public function getyesterdaycsv($path,$assoc){
        $counter = [];
        $count = 0;
        $count1 = 0;
        $counter1 = [];

        $fnamey = $assoc."-".date('Ymd',strtotime("-1 days")).".csv";   
        $fname = $assoc."-".date('Ymd').".csv";

        $lower8 = $this->getcsv($this->todaypath,$fname);
        $yestfile = $this->getcsv($this->yesterday,$fnamey);
        $csvlimit = count($yestfile);
        $limittotal = count($lower8);

        for ($i=0; $i < $limittotal; $i++) { 
            $time = $lower8[$i]['time'];
            $cat = $lower8[$i]['category'];
            $csvtime = explode(":", $time);         
            if($csvtime[0] <= 7){
                if($cat == 2){
                    $counter1[$count++] = array(
                            'date' =>  $lower8[$i]['date'],
                            'time' =>  $time,
                            'waterlvl' => $lower8[$i]['waterlvl'],
                            'category' => '2',
                        );
                }else if($cat == 3){
                    $counter1[$count++] = array(
                            'date' =>  $lower8[$i]['date'],
                            'time' =>  $time,
                            'value' => $lower8[$i]['value'],
                            'waterlvl' => $lower8[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter1[$count++] = array(
                            'date' =>  $lower8[$i]['date'],
                            'time' =>  $time,
                            'value' => $lower8[$i]['value'],
                            'category' => '1',
                        );
                }
            }
        }

        for ($i=0; $i < $csvlimit; $i++) { 
            $time = $yestfile[$i]['time'];
            $cat = $yestfile[$i]['category'];
            $csvtime = explode(":", $time);         
            if($csvtime[0] >= 8){
                if($cat == 2){
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'waterlvl' => $yestfile[$i]['waterlvl'],
                            'category' => '2',
                        );
                }else if($cat == 3){
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'value' => $yestfile[$i]['value'],
                            'waterlvl' => $yestfile[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter[$count++] = array(
                            'date' =>  $yestfile[$i]['date'],
                            'time' =>  $time,
                            'value' => $yestfile[$i]['value'],
                            'category' => '1',
                        );
                }
            }
        }

        $mrgarray = array_merge($counter,$counter1);        
        return $mrgarray;
    }        
    public function getaccumyesterdayandtoday($path,$csv){
        $counter = [];
        $count = 0;
        $counter1 = [];
        $count1 = 0;

        $todayfile = $csv."-".date('Ymd').".csv";  
        $yesterdayfile =  $csv."-".date('Ymd',strtotime("-1 days")).".csv"; 
        
        $tods = $this->getcsv($this->todaypath,$todayfile);
        $csvfile = $this->getcsv($path,$yesterdayfile);
        $todaypercount = count($tods);
        $perline = count($csvfile);
        
        for ($i=0; $i < $todaypercount; $i++) { 
            $csvtime = explode(":",$tods[$i]['time']);         
            if($csvtime[0] <= 7){
                $cat = $tods[$i]['category'];
                if($cat == 2){
                    $counter1[$count1++] = array(
                            'time' =>  $tods[$i]['time'],
                            'waterlvl' => $tods[$i]['waterlvl'],
                            'value' => 0,
                            'category' => '2',
                        );
                }else if($cat == 3){
                    $counter1[$count1++] = array(
                            'time' =>   $tods[$i]['time'],
                            'value' => $tods[$i]['value'],
                            'waterlvl' => $tods[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter1[$count1++] = array(                        
                            'time' =>  $tods[$i]['time'],
                            'value' => $tods[$i]['value'],
                            'category' => '1',
                        );
                }
            }

        }

        for ($i=0; $i < $perline; $i++) { 
            $csvtime = explode(":",$csvfile[$i]['time']);       
            if($csvtime[0] >= 8){
                $cat = $csvfile[$i]['category'];
                if($cat == 2){
                    $counter[$count++] = array(
                            'time' =>  $csvfile[$i]['time'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '2',
                            'value' => 0,
                        );
                }else if($cat == 3){
                    $counter[$count++] = array(
                            'time' =>   $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter[$count++] = array(                        
                            'time' =>  $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'category' => '1',
                        );
                }
            }
        }

        $sum = 0;
        $sum1 = 0;
        $limit = count($counter);
        $limit1 = count($counter1);

        for ($i=0; $i < $limit; $i++) {
            $sum+= $counter[$i]['value'];
        }

        for ($x=0; $x < $limit1; $x++) { 
            $sum1 += $counter1[$x]['value'];
        }
        $totalyesterday = $sum1 + $sum;

        return $totalyesterday;
    }
    public function getaccumyesterdayonly($path,$csv){
        $counter = [];
        $count = 0;
        $counter1 = [];
        $count1 = 0;
        $yesterdayfile =  $csv."-".date('Ymd',strtotime("-1 days")).".csv"; 
        $csvfile = $this->getcsv($path,$yesterdayfile);

        $perline = count($csvfile);

        for ($i=0; $i < $perline; $i++) { 
            $csvtime = explode(":",$csvfile[$i]['time']);       
            if($csvtime[0] >= 8){
                $cat = $csvfile[$i]['category'];
                if($cat == 2){
                    $counter[$count++] = array(
                            'time' =>  $csvfile[$i]['time'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '2',
                            'value' => 0,
                        );
                }else if($cat == 3){
                    $counter[$count++] = array(
                            'time' =>   $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'waterlvl' => $csvfile[$i]['waterlvl'],
                            'category' => '3',
                        );
                }else{
                    $counter[$count++] = array(
                        
                            'time' =>  $csvfile[$i]['time'],
                            'value' => $csvfile[$i]['value'],
                            'category' => '1',
                        );
                }
            }
        }
        $sum = 0;
        $limit = count($counter);
        for ($i=0; $i < $limit; $i++) {
            $sum+= $counter[$i]['value'];
        }

        return $sum;
    }
    public function getapistocsv(){
        set_time_limit(0);
        ignore_user_abort(true); 
        $sensors = DB::table('tbl_sensors')->get();   
        $username = 'dostcar';
        $password = 'd0str0[car]';
        
        $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
        ));
        
        foreach ($sensors as $sensor) {
            if($sensor->dev_id != 0){

            $url = 'http://weather.asti.dost.gov.ph/web-api/index.php/api/data/'.$sensor->dev_id.'/from/'.date('Y-m-d',strtotime("-1 days")).'/to/'.date('Y-m-d');
            
            try {
                $data = file_get_contents($url, false, $context);
            } catch (Exception $e) {
                throw new Exception( 'Something really gone wrong', 0, $e);
            }



            $mydatas = json_decode($data, true);          
            $counter = 0;            
            if (!empty($mydatas['province'])) { 

                    $finalarray = array(
                        array('region: CAR'),
                        array('province: '.$mydatas['province']),
                        array('posx: '.$mydatas['latitude']),
                        array('posy: '.$mydatas['longitude']),
                        array('imei: '.$mydatas['imei_num']),
                        array('sensor_name: '.$mydatas['type_id']),

                    );
                    
                    $province = strtoupper(str_replace(' ', '_', $mydatas['province']));
                    $location = strtoupper(str_replace(' ', '_', $mydatas['location']));
                    $flocation = str_replace(',','',$location);
                    $finallocation = str_replace('.','',$location);
                    $type = strtoupper(str_replace(' ', '_', $mydatas['type_id']));
                    $ftype = str_replace('&_','', $type);
                    $rootfile = 'drrmis.info/public/data/'.date('Y').'/'.date('m').'/'.date('d').'/';
                    $filename = $province.'-'.$finallocation.'-'.$ftype.'-'.date('Ymd').'.csv';

                    

                    if (is_dir($rootfile) == false)
                    {
                        
             
                        mkdir($rootfile);
                    }

                    $file = fopen($rootfile.$filename,"w");      
                    foreach ($finalarray as $fields) {
                        fputcsv($file, $fields);
                    }

                    $keys = array();        
                    $counter = 0;
                    $counts = 0;
                    

                    if(!empty($mydatas['data'])){
                        foreach ($mydatas['data'][0] as $key => $value) {
                            $keys[$counter++] =  $key;           
                        }
                        $thiskeys = array($keys);
                        foreach ($thiskeys as $fields) {
                            fputcsv($file, $fields);
                        }

                        sort($mydatas['data']);          
                        $date = date('Y-m-d');
                        foreach($mydatas['data'] as $mydata){
                            $csvdatetimearray = explode(" ", $mydata['dateTimeRead']); 
                            if($csvdatetimearray[0] == $date){
                                fputcsv($file, $mydata);
                            }
                        }
                    }                                         

                        fclose($file);
                }
            }
        }
    }
    public function getapistocsvbydate($year,$month,$day){
        set_time_limit(0);
        ignore_user_abort(true); 
        $sensors = DB::table('tbl_sensors')->get();   
        $username = 'dostcar';
        $password = 'd0str0[car]';
        
        $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
        ));

        $date=date_create($year.'-'.$month.'-'.$day);
        date_sub($date,date_interval_create_from_date_string("1 days"));
        $ystmonth = date_format($date,"m");
        $ystyear = date_format($date,"Y");
        $ystday = date_format($date,"d");
        
        foreach ($sensors as $sensor) {
          if($sensor->dev_id != 0){
            $url = 'http://weather.asti.dost.gov.ph/web-api/index.php/api/data/'.$sensor->dev_id.'/from/'.$ystyear.'-'.$ystmonth.'-'.$ystday.'/to/'.$year.'-'.$month.'-'.$day;
            
            try {
                $data = file_get_contents($url, false, $context);
            } catch (Exception $e) {
                throw new Exception( 'Something really gone wrong', 0, $e);
            }


            $mydatas = json_decode($data, true);
            $counter = 0;    
            $finalarray = [];
            $filename = '';
            if (!empty($mydatas['province'])) {

                $finalarray = array(
                    array('region: CAR'),
                    array('province: '.$mydatas['province']),
                    array('posx: '.$mydatas['latitude']),
                    array('posy: '.$mydatas['longitude']),
                    array('imei: '.$mydatas['imei_num']),
                    array('sensor_name: '.$mydatas['type_id']),

                );

                $province = strtoupper(str_replace(' ', '_', $mydatas['province']));
                $location = strtoupper(str_replace(' ', '_', $mydatas['location']));
                $flocation = str_replace(',','',$location);
                $finallocation = str_replace('.','',$location);
                $type = strtoupper(str_replace(' ', '_', $mydatas['type_id']));
                $ftype = str_replace('&_','', $type);
                $rootfile = 'data/'.$year.'/'.$month.'/'.$day.'/';
                $filename = $province.'-'.$finallocation.'-'.$ftype.'-'.$year.$month.$day.'.csv';
            
            

                

                if (is_dir($rootfile) === false)
                {
                    mkdir($rootfile);
                }

                $file = fopen($rootfile.$filename,"w");      
                foreach ($finalarray as $fields) {
                    fputcsv($file, $fields);
                }
                $keys = array();        
                $counter = 0;
                $counts = 0;
                

                if(!empty($mydatas['data'])){
                    foreach ($mydatas['data'][0] as $key => $value) {
                        $keys[$counter++] =  $key;           
                    }
                    $thiskeys = array($keys);
                    foreach ($thiskeys as $fields) {
                        fputcsv($file, $fields);
                    }

                    sort($mydatas['data']);          
                    $date =  $year.'-'.$month.'-'.$day;
                    foreach($mydatas['data'] as $mydata){
                        $csvdatetimearray = explode(" ", $mydata['dateTimeRead']); 
                        if($csvdatetimearray[0] == $date){
                            fputcsv($file, $mydata);
                        }
                    }
                } 
                fclose($file);                        
            }
            
           }

        }//end loop sensor
    }      
    public function displayaccumulatedtwodays($path,$csv){
        $yesterdayfile = $this->assocfile."-".date('Ymd',strtotime("-1 days")).".csv";  

        $todayfile = $this->assocfile."-".date('Ymd').".csv";
        $lasttwodaysaccum = $this->getaccum($path,$csv);                    
        $yesterday = $this->getfullaccum($this->yesterday,$yesterdayfile);

        $today = $this->getfullaccum($this->todaypath,$todayfile);
        $total = $lasttwodaysaccum + $yesterday + $today;
        return $total;
    }   
    public function savetextFile(){
        $sensors = DB::table('tbl_sensors')->whereIn('category_id', [1,2,3,4])->get();
        $categories = DB::table('tbl_categories')->get();
        $thresholds = DB::table('tbl_threshold')->get();
        $disptotal = [];
        $waterlvl = [];
        $dispcount = 0;
        $watercount = 0;

        $wcount = 0;
        $count = 0;        
        
        
        $sensorcategory = '';

        foreach ($sensors as $sensor) {
            $sum1 = 0;
            $fname = $sensor->assoc_file."-".date('Ymd').".csv";    
            $lasttwodaysfile =  $sensor->assoc_file."-".date('Ymd',strtotime("-2 days")).".csv";
            $filestatus = $this->getstatus($this->todaypath,$fname);     

            if(($sensor->category_id == 1) || ($sensor->category_id == 3) || ($sensor->category_id == 4)){
            //for rain and tandem  
                $currentreading = $this->getcurrentreading($this->todaypath,$fname);    

                $sum = $this->getaccum($this->todaypath,$sensor->assoc_file);
            
                $this->assocfile = $sensor->assoc_file;         
                $twoaccum = $this->displayaccumulatedtwodays($this->lasttwodayspath,$lasttwodaysfile);
                $this->arrtotals[$count++] = array(
                    'id' => $sensor->id,
                    'total' => $sum, 
                    'twoaccum' => $twoaccum,        
                    'filestatus' => $filestatus,    
                    'currentreading' => $currentreading,    
                    'province_id' => $sensor->province_id,  

                );          
            }
            if(($sensor->category_id == 2) || ($sensor->category_id == 3)){

                $currentwater = $this->getcurrentwaterlevel($this->todaypath,$fname);  
                //for meter 
        

                $this->waterlvltotal[$wcount++] = array(
                    'id' => $sensor->id,
                    'total' => '-', 
                    'twoaccum' => '-',      
                    'filestatus' => $filestatus,    
                    'currentreading' => $currentwater,
                    'province_id' => $sensor->province_id,          
                );
            }
        }
        $disptotal['columns'] = array(
            ["title" => "ID","data" => "id"],["title" => "Status","data" => "status"], ["title" => "Address","data" => "address"],["title" => "Sensor Type","data" => "sensortype"],["title" => "Current Reading","data" => "current"],["title" => "Past 2 days","data" => "past2days"],["title" => "Cumulative ( 8am )","data" => "cumulative"],["title" => "Remarks","data" => "remarks"]); 

        foreach ($sensors as $sensor) {
            foreach($this->arrtotals as $arrtotal){
                foreach ($categories as $category) {
                    if ($category->id == $sensor->category_id) {
                        $sensorcategory = $category->name;
                    }
                }                
                if($arrtotal['id'] == $sensor->id){                                        
                    $disptotal['data'][$dispcount++] = array(
                            'id' => '<span data-value="'.$sensor->id.'"></span>',
                            'status' => '<span class="stat '.$arrtotal['filestatus'].'">'.$arrtotal['filestatus'].'</span>',                               
                            'address' => $sensor->address,
                            'sensortype' => $sensorcategory,
                            'current' => $arrtotal['currentreading'],
                            'cumulative' => $arrtotal['total'],
                            'past2days' => $arrtotal['twoaccum'],                            
                            'remarks' => $sensor->remarks                               
                    );
                }
            }
        }
        $waterlvl['columns'] = array(
            ["title" => "ID","data" => "id"],["title" => "Status","data" => "status"], ["title" => "Address","data" => "address"],["title" => "Sensor Type","data" => "sensortype"],["title" => "Current Reading (m)","data" => "current"],["title" => "Normal Value","data" => "normal_val"],["title" => "Level 1","data" => "level1_val"],["title" => "Level 2","data" => "level2_val"],["title" => "Critical","data" => "critical_val"],["title" => "Remarks","data" => "remarks"]); 
        foreach ($sensors as $sensor) {           
            foreach ($this->waterlvltotal as $water) {
                foreach ($categories as $category) {
                    if ($category->id == $sensor->category_id) {
                        $sensorcategory = $category->name;
                    }
                }
                foreach ($thresholds as $threshold) {
                    if ($threshold->address_id == $sensor->id) {
                       if($water['id'] == $sensor->id){                        
                            $waterlvl['data'][$watercount++] = array(
                                'id' => '<span data-value="'.$sensor->id.'"></span>',
                                'status' => '<span class="stat '.$water['filestatus'].'">'.$water['filestatus'].'</span>',                               
                                'address' => $sensor->address,
                                'sensortype' => $sensorcategory,
                                'current' => $water['currentreading'],
                                'normal_val' => $threshold->normal_val,
                                'level1_val' => $threshold->level1_val,
                                'level2_val' => $threshold->level2_val,
                                'critical_val' => $threshold->critical_val,
                                'remarks' => $sensor->remarks                             
                            );
                        }
                    }
                }                
            }
        }
        

        $datajson = json_encode($disptotal);
        $datajsonwater = json_encode($waterlvl);

        $dirRaingauge = 'public/json/hydrometcontent/raingauge.json';  
        $dirWater = 'public/json/hydrometcontent/waterlvl.json';       

        File::put($dirRaingauge,$datajson);
        File::put($dirWater,$datajsonwater);
    }
}