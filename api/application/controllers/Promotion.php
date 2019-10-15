<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Promotion extends REST_Controller {

function __construct()
	{
		parent::__construct();
			
			$this->load->helper('url_helper');
			$this->load->library('javascript');
			//$this->load->library('session');
			$this->load->helper('url');
			$this->load->helper('html');
			$this->load->database();
			$this->load->helper('string');			
	}
	
function get_post(){
	try{
		
		/* $query_str = "SELECT p.*, case when eventId=0 THEN (SELECT Business_Name FROM bars_list WHERE id = p.barId) ELSE (SELECT event_name from tbl_events WHERE id = p.eventId) END as name from tbl_promotions as p where p.endsat>=CURDATE()"; */
		
		$promotionType = $this->post('promotionType');
		$latitude = $this->post('latitude');
		$longitude = $this->post('longitude');
		$searchText = $this->post('byLocationOrPostcode');
		$offset = $this->post('offset');
		$limit = $this->post('limit');
		
		if($promotionType=="Bar")
		{
			if($latitude!=""&&$longitude!=""&&$searchText=="")
			{
				$query_str = "SELECT p.*, b.Business_Name as name, b.Latitude as latitude, b.Longitude as longitude,( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) +	sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance from tbl_promotions as p join bars_list as b on b.id=p.barId where  p.eventId=0 and p.endsat>=CURDATE() HAVING distance < 100";		
			}	
			elseif($latitude!=""&&$longitude!=""&&$searchText!="")
			{
				$query_str = "SELECT p.*, b.Business_Name as name,b.Latitude as latitude, b.Longitude as longitude, ( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) +	sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance from tbl_promotions as p join bars_list as b on b.id=p.barId where  p.eventId=0 and (b.Zipcode like '".$searchText."%' or b.Location_Searched like '%".$searchText."%' or b.Address like '%".$searchText."%' or b.Region like '%".$searchText."%' ) and p.endsat>=CURDATE() HAVING distance < 100";		
			}	
			elseif($latitude==""&&$longitude==""&&$searchText!="")
			{
				$query_str = "SELECT p.*, b.Business_Name as name,b.Latitude as latitude, b.Longitude as longitude from tbl_promotions as p join bars_list as b on b.id=p.barId where  p.eventId=0 and (b.Zipcode like '".$searchText."%' or b.Location_Searched like '%".$searchText."%' or b.Address like '%".$searchText."%' or b.Region like '%".$searchText."%' ) and p.endsat>=CURDATE()";	
			}
			else
			{
				$query_str = "SELECT p.*, b.Business_Name as name,b.Latitude as latitude, b.Longitude as longitude from tbl_promotions as p join bars_list as b on b.id=p.barId where  p.eventId=0 and p.endsat>=CURDATE()";	
			}	
			
		}	
		elseif($promotionType=="Event")
		{
			if($latitude!=""&&$longitude!=""&&$searchText=="")
			{
				$query_str = "SELECT p.*, e.event_name as name,b.Latitude as latitude, b.Longitude as longitude, ( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) + sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance from tbl_promotions as p join tbl_events as e on e.id=p.eventId left join bars_list as b on e.bar_id=b.id where p.eventId!=0 and p.endsat>=CURDATE() HAVING distance < 100";			
			}
			elseif($latitude!=""&&$longitude!=""&&$searchText!="")
			{
				$query_str = "SELECT p.*, e.event_name as name, b.Latitude as latitude, b.Longitude as longitude, ( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) + sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance from tbl_promotions as p join tbl_events as e on e.id=p.eventId left join bars_list as b on e.bar_id=b.id where p.eventId!=0 and (b.Zipcode like '".$searchText."%' or b.Location_Searched like '%".$searchText."%' or b.Address like '%".$searchText."%' or b.Region like '%".$searchText."%' ) and p.endsat>=CURDATE() HAVING distance < 100";			
			}
			elseif($latitude==""&&$longitude==""&&$searchText!="")
			{
				$query_str = "SELECT p.*, e.event_name as name,b.Latitude as latitude, b.Longitude as longitude from tbl_promotions as p join tbl_events as e on e.id=p.eventId left join bars_list as b on e.bar_id=b.id where p.eventId!=0 and (b.Zipcode like '".$searchText."%' or b.Location_Searched like '%".$searchText."%' or b.Address like '%".$searchText."%' or b.Region like '%".$searchText."%' ) and p.endsat>=CURDATE()";	
			}	
			else
			{
				$query_str = "SELECT p.*, e.event_name as name,b.Latitude as latitude, b.Longitude as longitude from tbl_promotions as p join tbl_events as e on e.id=p.eventId left join bars_list as b on e.bar_id=b.id where p.eventId!=0 and p.endsat>=CURDATE()";		
			}
			
		}	
		else
		{
			$query_str = "SELECT p.*,b.Longitude as longitude, b.Latitude as latitude, case when eventId=0 THEN (SELECT Business_Name FROM bars_list WHERE id = p.barId) ELSE (SELECT event_name from tbl_events WHERE id = p.eventId) END as name from tbl_promotions as p left join bars_list as b on b.id=p.barId where p.endsat>=CURDATE()";
		}	
		
		$limitForList = " order by p.startsat DESC LIMIT ".$offset." , ".$limit;
		$query_str = $query_str.$limitForList;
		
		$result = $this->db->query($query_str);
		//$result = $this->db->query($query_str);
		$this->prepareAndSendResponse($result);
	
	} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
}

function getByBar_post(){
	try{
		
		$barId = $this->post('barId');
		$query_str = "SELECT p.*,b.Business_Name as name,b.Latitude as latitude, b.Longitude as longitude from tbl_promotions as p, bars_list as b where p.eventId = 0 and p.barId = b.id and p.barId = ".$barId." and p.startsat <= CURDATE() AND p.endsat>=CURDATE()";
		$result = $this->db->query($query_str);
		$this->prepareAndSendResponse($result);

	} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
}

function getByEvent_post(){
	try{
		
		$eventId = $this->post('eventId');
		$query_str = "SELECT p.*,e.event_name as name from tbl_promotions as p, tbl_events as e where p.eventId = e.id and p.eventId = ".$eventId." and p.startsat <= CURDATE() AND p.endsat>=CURDATE()";
		$result = $this->db->query($query_str);
		$this->prepareAndSendResponse($result);

	} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
}

function prepareAndSendResponse($result){
	$num_rows = $result->num_rows();
	if(!$result){
	$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.'
				], REST_Controller::HTTP_OK); 
			} 
	else if($num_rows > 0){
		foreach ($result->result() as $row) {
			
			$promotion = $this->getPromotion($row->id,$row->name,$row->barId,$row->eventId,$row->discount,$row->startsat,$row->endsat,$row->couponcode,$row->longitude,$row->latitude);
			$arr[] = $promotion;
		}
		$this->set_response([
					'status' =>'SUCCESS',
					'data' => $arr
			], REST_Controller::HTTP_OK);  
	}
	else {
		$this->set_response([
					'status' =>"FAILED",
					'error_code' => "PROMOTION_NOT_FOUND",
					'message' => 'No Promotions Found'
				], REST_Controller::HTTP_OK);
	}
}


function getPromotion($promotionId,$name,$barId,$eventId,$discount,$validFrom,$validTill,$couponCode,$longitude,$latitude){
		return array(
			'prmotionId' => $promotionId,
			'name' => $name,
			'barId' => $barId,
			'eventId' => $eventId,
			'discount' => $discount,
			'couponCode' => $couponCode,
			'validFrom' => $validFrom,
			'validTill' => $validTill,
			'longitude' => $longitude,
			'latitude' => $latitude
		);
	}
}
?>