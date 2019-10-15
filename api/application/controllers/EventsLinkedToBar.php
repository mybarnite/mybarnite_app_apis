<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

/**
*  Parameters: {"bar_id": "3"}
**/

class EventsLinkedToBar extends REST_Controller {
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
	
	
	function getEventDetails($event_id,$event_name,$event_description,$event_start_date,$event_end_date,$event_start_time,$event_end_time,$image_url,
		$bar_id,$is_free_event,$basic_entry_fee,$vip_entry_fee,$is_promotion_avaialble,$longitude,$latitude) {
	
		return array(
			'eventId' => $event_id,
			'eventName' => $event_name,
			'eventDescription' => $event_description,
			'eventStartDate' => $event_start_date,
			'eventEndDate' => $event_end_date,
			'eventStartTime' => $event_start_time,
			'eventEndTime' => $event_end_time,
			'imageURL' => $image_url,
			'barId' => $bar_id,
			'isFreeEvent' => $is_free_event,
			'basicEntryFee' => $basic_entry_fee,
			'vipEntryFee' => $vip_entry_fee,
			'isPromotionAvailable' => $is_promotion_avaialble,
			'longitude' => $longitude,
			'latitude' => $latitude
		);
	
	}
	
	
	function list_post(){
		try{
			

			$data = json_decode(file_get_contents('php://input'), true);

			$barid = $data['bar_id'];
			$query_str1 = 'SELECT * FROM tbl_events WHERE bar_id = '.$barid.' AND DATE(event_end) > DATE(NOW()) ORDER BY id DESC';	
			$result1 = $this->db->query($query_str1);

	

			if ($result1->num_rows() == 0){
				$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'Record not found.'
				], REST_Controller::HTTP_OK); 
			}else{
					foreach ($result1->result() as $row){
						
		                            $eventId = $row->id;
		                            $query_str = "SELECT g.file_name,g.file_path,g.event_id,g.logo_image,e.*, p.discount,b.id as bar_id, b.Business_name, b.Location_Searched, b.Zipcode,b.Latitude AS latitude,b.Longitude AS longitude FROM tbl_events as e left join bars_list as b on e.bar_id=b.id left join tbl_event_gallery as g on e.id=g.event_id and g.logo_image='1' left join tbl_promotions as p on p.eventId=e.id and p.status='Active'  WHERE e.id=".$eventId."";
		                            
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
		                                $basicEntryFee,
		                                $vipEntryFee,
		                                $isPromotionAvailable,
		                                $row->longitude,
		                                $row->latitude);
		                                
		                                $totalevents[] = $eventDetail;
		                                
		                                $this->set_response([
		                                            'status' =>"SUCCESS",
		                                            'data' => $totalevents
		                                        ], REST_Controller::HTTP_OK);
		
		                                }else{
		                                    $this->set_response([
		                                            'status' =>"FAILED",
		                                            'error_code' => "BAR_NOT_FOUND",
		                                            'message' => 'Bar not found'
		                                        ], REST_Controller::HTTP_OK); 
                                }	
                                
                        }	
                    
	    	}
        }catch(Exception $e){
                $this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
        }

    }
	
	function sendResponse($response){
		$this->set_response([
					'status' =>'SUCCESS',
					'message' => $response
			], REST_Controller::HTTP_OK); 		
	}
	
	function sendErrorResponse($errorCode,$errorMessage){
		$this->set_response([
					'status' =>"FAILED",
					'error_code' => $errorCode,
					'message' => $errorMessage
				], REST_Controller::HTTP_OK);
	}
}
