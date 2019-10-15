<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Coupon extends REST_Controller {

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
	function isValid_post(){
		try{
			$orderId = $this->post('order_id');
			$amount = $this->post('amount');
			$code = $this->post('code');
			$barId = $this->post('bar_id');
			$eventId = $this->post('event_id');

			if($barId!=""&&$barId!=0)
			{
				$sql = 'select * from tbl_promotions where status = "Active" and couponcode ="'.$code.'" and barId='.$barId;
			}
			elseif($eventId!=""&&$eventId!=0)	
			{
				$sql = 'select p.*,e.* from tbl_promotions as p left join tbl_events as e on p.eventId=e.id where p.couponcode ="'.$code.'" and p.eventId='.$eventId;
			}
			$query = $this->db->query($sql);
			if(!$query){
				$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
				return;
			}
			else{
				$num_rows = $query->num_rows();
				if($num_rows > 0){
					$rows = $query->row_array();	
					$current_date = strtotime(date("Y-m-d"));
					$startsat = strtotime($rows['startsat']. " -1 day");
					$endsat = strtotime($rows['endsat']. " +1 day");
					$data = array();
					if(($current_date>$startsat)&&($current_date<$endsat))
					{
						$isValid=1;
						$discount = ($rows['discount']/100)*$amount;
						$payableamount = $amount - $discount;
						$rows['isActive']="Active";	
					}
					else
					{
						$isValid=0;
						$rows['discount']=0;
						$payableamount=$amount;
						$rows['isActive']="Expired";
					} 
					$data['is_valid_coupon'] = $isValid;
					$data['percentage_discount'] = $rows['discount'];
					$data['payable_amount'] = $payableamount;
					$data['coupon_status'] = $rows['isActive'];
					
					if($eventId!=""&&$eventId!=0)
					{
						$event_starting_date = strtotime($rows['event_start']);
						$event_ending_date = strtotime($rows['event_end']);
						$event_status = (($current_date>$event_starting_date)&&($current_date<$event_ending_date))?"Active":"Expired";
						$is_event_available_for_booking = $rows['is_availableForBooking'];
						if($rows['isActive']=="Expired"&&$is_event_available_for_booking=="Booked"&&$event_status=="Expired"){
							$this->sendErrorResponse("NOT_EXISTS","Coupon does not exist, event does not exist.");
						}elseif($rows['isActive']=="Expired"&&$is_event_available_for_booking=="Booked"&&$event_status=="Active"){
							$this->sendErrorResponse("NOT_EXISTS","Coupon does not exist, event exists but is fully booked.");
						}elseif($rows['isActive']=="Expired"&&$is_event_available_for_booking=="Available"&&$event_status=="Active"){
							$this->sendErrorResponse("NOT_EXISTS","Coupon does not exist, event exists and available for booking.");
						}elseif($rows['isActive']=="Active"&&$is_event_available_for_booking=="Booked"&&$event_status=="Expired"){
							$this->sendErrorResponse("NOT_EXISTS","Coupon exists, event does not exist.");
						}elseif($rows['isActive']=="Active"&&$is_event_available_for_booking=="Available"&&$event_status=="Expired"){
							$this->sendErrorResponse("NOT_EXISTS","Coupon exists, event does not exist.");
						}else{
							$data['event_status'] = "Active";
							$data['is_available_for_booking'] = "Available";
							
							$array = array(
								'percentage_discount'=>$rows['discount'],
								'payable_amount'=>$payableamount
								
							);
							$sql1 = "update tbl_order_history set percentage_discount=".$rows['discount'].", payable_amount='".$payableamount."' where id=".$orderId;
							$query1 = $this->db->query($sql1);
							if (!$query1)
							{
								$this->set_response([
									'status' =>"FAILED",
									'error_code' => "SERVER_ERROR",
									'message' => 'There is some error.'
								], REST_Controller::HTTP_OK); 
							}
							else
							{
								$i = $this->db->affected_rows();
								
								if($i>0)
								{ 
									$this->set_response([
											'status' =>'SUCCESS',
											'data' => $data
									], REST_Controller::HTTP_OK); 
								}
								else
								{
									$this->sendErrorResponse("INVALID_ORDER","Order Not Found.");
								}		
							} 	 
						}
					}
					else{
					
						$array = array(
							'percentage_discount'=>$rows['discount'],
							'payable_amount'=>$payableamount
							
						);
						$sql1 = "update tbl_order_history set percentage_discount=".$rows['discount'].", payable_amount='".$payableamount."' where id=".$orderId;
						$query1 = $this->db->query($sql1);
						if (!$query1)
						{
							$this->set_response([
								'status' =>"FAILED",
								'error_code' => "SERVER_ERROR",
								'message' => 'There is some error.'
							], REST_Controller::HTTP_OK); 
						}
						else
						{
							$i = $this->db->affected_rows();
							
							if($i>0)
							{
								
								$this->set_response([
										'status' =>'SUCCESS',
										'data' => $data
								], REST_Controller::HTTP_OK); 
							}
							else
							{
								$this->sendErrorResponse("INVALID_ORDER","Order Not Found.");
							}		
						}	 
					}
				}
				else{
					$this->sendErrorResponse("COUPON_NOT_FOUND","No Coupon Found.");
					return;
					
				}
			}
				
		
		}
		catch(Exception $e){
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