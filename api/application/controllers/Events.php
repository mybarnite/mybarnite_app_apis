<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

class Events extends REST_Controller {
	
	//$_SESSION['latitude']= "51.4859582";
//$_SESSION['longitude']= "-0.1849112";
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
	
	function list_post(){
		try {
			$eventType = $this->post('eventType');
			$offset = $this->post('offset');
			$limit = $this->post('limit');
			$latitude = $this->post('latitude');
			$longitude = $this->post('longitude');
			$eventName = $this->post('eventName');
			$postcode = $this->post('postcode');		
			
			if($eventType!="")
			{
				$whereEventType ="e.eventtype = '".$eventType."' and ";
			}
			else
			{
				$whereEventType ="";
			}		
			
			if($eventName!=""&&$postcode=="")
			{
				//$query_str .= " where Business_Name like '%".$eventName."%'";
				$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where $whereEventType e.event_name like '%".$eventName."%' and e.event_end > CURDATE( )";
			}	

			elseif($postcode!=""&&$eventName=="")
			{
				//$query_str .= " where Zipcode like '".$postcode."%'";
				$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where $whereEventType b.Zipcode like '".$postcode."%' and e.event_end > CURDATE( )";
			}
				
			elseif($postcode!=""&&$eventName!="")
			{
				//$query_str .= " where Business_Name like '%".$eventName."%' and Zipcode like '".$postcode."%'";
				$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where $whereEventType e.event_name like '%".$eventName."%' and b.Zipcode like '".$postcode."%' and e.event_end > CURDATE( )";
			}
			elseif($latitude!=""&&$longitude!="")
			{
				$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount, ( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) +		sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where $whereEventType e.event_end > CURDATE( ) HAVING distance < 100";
			}
			else
			{
				$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where $whereEventType e.event_end > CURDATE( )";
			}	
			
			$limitForList = " order by e.event_start DESC LIMIT ".$offset." , ".$limit;
			$query_str = $query_str.$limitForList;
		
			
			/* $query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end,e.eventtype, b.id as barId,p.discount, ( 6371 * acos(cos(radians(".$latitude.")) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(".$longitude.")) +			sin(radians(".$latitude.")) * sin(radians(Latitude))) ) AS distance FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where e.eventtype = '".$eventType."' and e.event_end > CURDATE( ) HAVING distance < 100 order by e.id DESC LIMIT ".$limit." OFFSET ".$offset."";
			 */
			
			$result = $this->db->query($query_str);
			
			if(!$result){
				$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.'
				], REST_Controller::HTTP_OK); 
			}
			else if($result->num_rows() > 0) {
				foreach ($result->result() as $row) {
					$isPromotionAvailable = ($row->discount == ""?false:true);
					$imageURL =  "https://mybarnite.com/business_owner/".$row->file_path;
					
					$event = $this->getEvent($row->id,$row->event_name,$row->event_start,$row->event_end,$imageURL,$row->barId,$isPromotionAvailable,$row->eventtype,$row->is_availableForBooking);
					$arr[] = $event;
				}
				$this->set_response([
							'status' =>'SUCCESS',
							'data' => $arr
					], REST_Controller::HTTP_OK);  
					
				}
				
			else {
				$this->set_response([
							'status' =>"FAILED",
							'error_code' => "EVENT_NOT_FOUND",
							'message' => 'No Events Found'
						], REST_Controller::HTTP_OK); 
			}			
			
		} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
	}
	
	function getEventsByBar_post(){
		try {
			
			$barId = $this->post('barId');
			$query_str = "SELECT g.file_path,e.id,e.event_name, e.event_start, e.event_end, e.eventtype, b.id as barId,p.discount,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active' where b.id = ".$barId." and e.event_end > CURDATE( )";
			
			$result = $this->db->query($query_str);
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
					$isPromotionAvailable = ($row->discount == ""?false:true);
					$imageURL =  "https://mybarnite.com/business_owner/".$row->file_path;
					
					$event = $this->getEvent($row->id,$row->event_name,$row->event_start,$row->event_end,$imageURL,$row->barId,$isPromotionAvailable,$row->eventtype,$row->is_availableForBooking);
					$arr[] = $event;
				}
				$this->set_response([
							'status' =>'SUCCESS',
							'data' => $arr
					], REST_Controller::HTTP_OK);  
				
			} 
			else {
				$this->set_response([
							'status' =>"FAILED",
							'error_code' => "EVENT_NOT_FOUND",
							'message' => 'No Events Found'
						], REST_Controller::HTTP_OK);
			}
			
			
			
		} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
	}
	
	function getDetails_post(){
		try{
			$eventId = $this->post('eventId');
			$query_str = "SELECT g.file_name,g.file_path,g.event_id,g.logo_image,e.*, p.discount,b.id as bar_id, b.Business_name, b.Location_Searched, b.rating, b.Address, b.Locality, b.Region, b.Zipcode,b.Latitude AS latitude,b.Longitude AS longitude,e.is_availableForBooking FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active'  WHERE e.id=".$eventId."";
			
			$result = $this->db->query($query_str);
			$dbRecord = $result->result();
			if(!$result){
				$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.'
				], REST_Controller::HTTP_OK); 
			} 
			
			else if(!empty($dbRecord)) {
				
				$row = $dbRecord[0];
				$isFreeEvent = ($row->free_event == '1'?true:false);
				$basicEntryFee = '0.00';
				$vipEntryFee = '0.00';
				
				if($isFreeEvent == false){
					$basicEntryFee = number_format($row->event_price_basic,2);
					$vipEntryFee = number_format($row->event_price_vip,2);
				}
				
				$isPromotionAvailable = ($row->discount == ""?false:true);
				$imageURL =  "https://mybarnite.com/business_owner/".$row->file_path;
				
				$eventDetail = $this->getEventDetails(
				$row->id,
				$row->event_name,
				$row->event_description,
				$row->event_start,
				$row->event_end,
				$row->start_time,
				$row->end_time,
				$imageURL,
				$row->bar_id,
				$isFreeEvent,
				$row->is_availableForBooking,
				$basicEntryFee,
				$vipEntryFee,
				$isPromotionAvailable,
				$row->longitude,
				$row->latitude,
				$row->Business_name,
				$row->rating,
				$row->Address,
				$row->Locality,
				$row->Region,
				$row->Zipcode
				);
				
				$this->set_response([
							'status' =>"SUCCESS",
							'data' => $eventDetail
						], REST_Controller::HTTP_OK);
				
				
			}
			else {
				$this->set_response([
							'status' =>"FAILED",
							'error_code' => "EVENT_NOT_FOUND",
							'message' => 'No Events Found'
						], REST_Controller::HTTP_OK);	
			}
			
		} catch(Exception $e){
			$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => "Server Error"
				], REST_Controller::HTTP_OK); 
		}
	}
	
	function getEvent($eventId,$eventName,$eventStartDate,$eventEndDate,$imageURL,$barId,$isPromotionAvailable,$eventType,$is_availableForBooking){
		return array(
			'eventId' => $eventId,
			'eventName' => $eventName,
			'eventStartDate' => $eventStartDate,
			'eventEndDate' => $eventEndDate,
			'imageURL' => $imageURL,
			'barId' => $barId,
			'isPromotionAvailable' => $isPromotionAvailable,
			'eventType' => $eventType,
			'fullyBooked' => $is_availableForBooking == 'Booked' ? true:false,
		);
	}
	
	
	
	function getEventDetails($event_id,$event_name,$event_description,$event_start_date,$event_end_date,$event_start_time,$event_end_time,$image_url,
		$bar_id,$is_free_event,$is_availableForBooking,$basic_entry_fee,$vip_entry_fee,$is_promotion_avaialble,$longitude,$latitude,$barName,$rating,$address,$locality,$region,$zipcode) {
	
		return array(
			'eventId' => $event_id,
			'eventName' => $event_name,
			'eventDescription' => $event_description,
			'eventStartDate' => date("d-m-Y", strtotime($event_start_date)),
			'eventEndDate' => date("d-m-Y", strtotime($event_end_date)),
			'eventStartTime' => $event_start_time,
			'eventEndTime' => $event_end_time,
			'imageURL' => $image_url,
			'barId' => $bar_id,
			'isFreeEvent' => $is_free_event,
			'fullyBooked' => $is_availableForBooking == 'Booked' ? true:false,
			'basicEntryFee' => $basic_entry_fee,
			'vipEntryFee' => $vip_entry_fee,
			'isPromotionAvailable' => $is_promotion_avaialble,
			'longitude' => $longitude,
			'latitude' => $latitude,
			'barName' => $barName,
			'rating' => $rating,
			'address' => $address,
			'locality' => $locality,
			'region' => $region,
			'zipcode' => $zipcode
		);
	
	}

}
?>