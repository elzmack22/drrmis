<?php



namespace App\Http\Controllers;



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

use App\Services\Getcsvdataapi;

use File;

use Javascript;



class HydrometController extends Controller

{



	public $arrtotals = [];

	public $filterprovince;

	public $filtercategory;

	public $todaypath;

	public $yesterday;

	public $test = [];

	public $assocfile;

	public $cummulativedate = [];

	public $getcsvdata;



	function __construct(Getcsvdataapi $getcsvdata){

		$this->todaypath = 'data'.'/'.date('Y').'/'.date('m').'/'.date('d').'/';

		$this->yesterday = 'data'.'/'.date('Y/m/d',strtotime("-1 days")).'/';

		$this->lasttwodayspath = 'data'.'/'.date('Y/m/d',strtotime("-2 days")).'/';

		$this->getcsvdata = $getcsvdata;

	}

	public function displayaccumulatedtwodays($path,$csv){

		$yesterdayfile = $this->assocfile."-".date('Ymd',strtotime("-1 days")).".csv";	

		$todayfile = $this->assocfile."-".date('Ymd').".csv";

		$lasttwodaysaccum = $this->getcsvdata->getaccum($path,$csv);					

		$yesterday = $this->getcsvdata->getfullaccum($this->yesterday,$yesterdayfile);

		$today = $this->getcsvdata->getfullaccum($this->todaypath,$todayfile);

		$total = $lasttwodaysaccum + $yesterday + $today;

		return $total;

	}	

	



	public function ajaxHydromet(){

		$sensortype = Input::get('sensortype');

		$pathToFile = '';

		if($sensortype == 'rain'){

			$pathToFile = 'json/hydrometcontent/raingauge.json';			

		}elseif($sensortype == 'stream'){

			$pathToFile = 'json/hydrometcontent/waterlvl.json';			

		}

		return response()->file($pathToFile);		

		

	}

  public function filterdata()

	{	

		return view('pages.viewhydrometdata');	

	}



    public function viewHydrometdata()

	{	

		return view('pages.viewhydrometdata');	

	}

	public function dashboard()

	{	

		$count = 0;

		$categories = DB::table('tbl_categories')->get();      

		$provinces = DB::table('tbl_provinces')->get();  	

		$thresholds = DB::table('tbl_threshold')->get(); 

		$municipalities = DB::table('tbl_municipality')->get();

		$landslides = DB::table('tbl_incidents')->where('incident_type','=','1')->orderBy('date', 'desc')->get();  	

		$floods = DB::table('tbl_incidents')->where('incident_type','=','2')->orderBy('date', 'desc')->get(); 

		$roadnetworks = DB::table('tbl_roadnetworks')->orderBy('date', 'desc')->get();

		$users = DB::table('users')->get();  



		$sensors = DB::table('tbl_sensors')->whereIn('category_id', [1,2,3,4])->get();



		foreach ($sensors as $sensor) {

			$sum1 = 0;

			$fname = $sensor->assoc_file."-".date('Ymd').".csv";	

			$lasttwodaysfile =  $sensor->assoc_file."-".date('Ymd',strtotime("-2 days")).".csv";

			$filestatus = $this->getcsvdata->getstatus($this->todaypath,$fname);		



			if($sensor->category_id != 2){	

				$currentreading = $this->getcsvdata->getcurrentreading($this->todaypath,$fname);	



				$sum = $this->getcsvdata->getaccum($this->todaypath,$sensor->assoc_file);

			

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

			}else{

				

				$this->arrtotals[$count++] = array(

					'id' => $sensor->id,

					'total' => '-', 

					'twoaccum' => '-',		

					'filestatus' => $filestatus,	

					'currentreading' => '-',

					'province_id' => $sensor->province_id,	

		

					);

			}

		}

	

			$user = Auth::user();

			$count20 = 0;

			$x = 0;

			$title ='';

			$arrtotals = $this->arrtotals;

			$mainarray = array();

			$sortArray = array(); 

            foreach($arrtotals as $arrtotal){ 	               

	     		$mainarray[$x++] = array(

	            	'id' => $arrtotal['id'],

					'total' => $arrtotal['total'], 

					'twoaccum' => $arrtotal['twoaccum'],		

					'filestatus' => $arrtotal['filestatus'],	

					'currentreading' => $arrtotal['currentreading'],

					'province_id' => $arrtotal['province_id'],



	            );         

            } 

            foreach ($mainarray as $mr) {

            	foreach($mr as $key=>$value){ 

	                if(!isset($sortArray[$key])){ 

	                    $sortArray[$key] = array(); 

	                } 

	                $sortArray[$key][] = $value; 	                    

		        } 

            }        



	        $orderby = "total";

			array_multisort($sortArray[$orderby],SORT_DESC,$mainarray);	



			JavaScript::put([

	            'mainarray' => $mainarray

	        ]);

	return view('pages.dashboard')->with(['roadnetworks' => $roadnetworks,'floods' => $floods,'landslides' => $landslides,'title' => $title,'users' => $users,'mainarray' => $mainarray,'municipalities' => $municipalities,'thresholds' => $thresholds,'sensors' => $sensors,'provinces' => $provinces,'categories' => $categories]);

	}	

    public function viewperSensor(){

		$sensorid = Input::get('sensorid');

		$sensor = DB::table('tbl_sensors')->where('id','=',$sensorid)->first();

		$categories = DB::table('tbl_categories')->get();

		$provinces = DB::table('tbl_provinces')->get();      

		$municipalities = DB::table('tbl_municipality')->get();

		$todayfile = $this->getcsvdata->gettodaycsv($this->todaypath,$sensor->assoc_file);

		if (!empty($todayfile)) {

			$csvcontents = $todayfile;      		

		}else{

			$csvcontents = $this->getcsvdata->getyesterdaycsv($this->yesterday,$sensor->assoc_file);

		}

		JavaScript::put([

            'csvcontents' => $csvcontents,

        ]);

		return view ('pages.viewpersensor')->with(['municipalities' => $municipalities,'sensor' => $sensor, 'provinces' => $provinces,'categories' => $categories]);

   }

   

   

}

