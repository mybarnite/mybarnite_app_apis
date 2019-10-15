<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Eventbooking extends REST_Controller {

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
	function add_post(){
		try{            
			$order_id = $this->post('order_id');
			$eventId = $this->post('event_id');
			$userId = $this->post('user_id');	
			$isFreeEvent = $this->post('free_event');
			$basicOrVip = $this->post('basic_or_vip');
			$noOfPersons = $this->post('no_of_persons');
			$bookingStrtDate = $this->post('booking_start_date');
			$bookingEnDate = $this->post('booking_end_date');
			$bookingStartDate = date("Y-m-d", strtotime($bookingStrtDate));
			$bookingEndDate = date("Y-m-d", strtotime($bookingEnDate));						
			$daysBetween = round(abs(strtotime($bookingStartDate)-strtotime($bookingEndDate))/86400);
			$noOfDays = $daysBetween+1;			
			$paymentStatus = $this->post('payment_status')=='Done' ? $this->post('payment_status') : "Pending";
			$transactionId = 0;
			$orderCreatedDate = date('Y-m-d H:i:s');
			
			if($userId == ""){
				$this->sendErrorResponse("INVALID_USER","User not found.");
			}	
			elseif($eventId == ""){
				$this->sendErrorResponse("INVALID_EVENT","Event details not found.");
			}	
			else
			{
				$sql = "select e.event_name, e.event_price_basic, e.event_price_vip, e.bar_id, e.free_event, b.Owner_id from tbl_events as e join bars_list as b on e.bar_id = b.id where e.id =".$eventId;
				$query = $this->db->query($sql);
				$num_rows=$query->num_rows();
				if (!$query)
				{
					
					$this->set_response([
						'status' =>"FAILED",
						'error_code' => "SERVER_ERROR",
						'message' => 'There is some error.'
					], REST_Controller::HTTP_OK); 
				}
				else
				{
					if ($num_rows > 0)
					{	
							
						$row = $query->row_array();	
						$ownerId = $row['Owner_id'];
						$barId = $row['bar_id'];
						$orderName = $row['event_name'];
						
						if($isFreeEvent==1)//Free event that means free booking
						{
							$basicEventAmount = $row['event_price_basic'];
							$vipEventAmount = $row['event_price_vip'];
							$totalAmount = 0;
							
						}else{//Event is not free so there will be charges according to VIP / BASIC
							$basicEventAmount = $row['event_price_basic'];
							$vipEventAmount = $row['event_price_vip'];
							$fees = ($basicOrVip=="Basic")?$basicEventAmount:$vipEventAmount;
							$amount  = $fees*$noOfPersons;
							$totalAmount = $amount*$noOfDays;
						
						}							
						$basicEventAmount = $row['event_price_basic'];
						$vipEventAmount = $row['event_price_vip'];
						$fees = ($basicOrVip=="Basic")?$basicEventAmount:$vipEventAmount;
						$amount  = $fees*$noOfPersons;
						$totalAmount = $amount*$noOfDays;
						/* $hallFee = ($isHallBooked==1)?$row['hall_fee']:0;
						$costPerSeat = ($isHallBooked==1)?0:$row['cost_per_seat'];
						$totalCostForBookedSeats = $costPerSeat*$noOfPersons;
						$totalAmount = $hallFee + $totalCostForBookedSeats; */
						if($order_id === NULL){
                            $eventData = array(
                                'order_for_category'=>'Event',
                                'Owner_id'=>$ownerId,
                                'user_id'=>$userId,
                                'event_id'=>$eventId,
                                'no_of_persons'=>$noOfPersons,
                                'no_of_days'=>$noOfDays,
                                'total_amount'=>$totalAmount,
                                'payment_status'=>$paymentStatus,
                                'transaction_id'=>$transactionId,
                                'cartId'=>'MB'.time(),
                                'order_created_at'=>$orderCreatedDate,
                                'type_of_purchase'=>$basicOrVip,
                                'ordername'=>$orderName,
                                'free_event'=>$isFreeEvent,
                                'event_booking_start_date' =>$bookingStartDate,
                                'event_booking_end_date' =>$bookingEndDate
                            );
                            
                            $this->db->insert('tbl_order_history', $eventData);
                            $lastInsertedId = $this->db->insert_id();
                            
                            if($lastInsertedId>0){
                                //$this->sendResponse("Data has been added successfully.");
                                $response = array(
                                    'order_id'=>$lastInsertedId,
                                    'bar_owner_id'=>$ownerId,
                                    'bar_id'=>$barId,
                                    'event_id'=>$eventId,
                                    'user_id'=>$userId,
                                    'category'=>"Event",
                                    'event_name'=>$orderName,
                                    'type_of_purchase'=>($isFreeEvent==0)?$basicOrVip:"-",
                                    'no_of_persons'=>$noOfPersons,
                                    'no_of_days'=>$noOfDays,
                                    'total_amount'=>$totalAmount,
                                    'payment_status'=>$paymentStatus,
                                    'transaction_id'=>$transactionId,
                                    'order_created_at'=>$orderCreatedDate,
                                    'free_event'=>$isFreeEvent,
                                    'cart_id'=>'MB'.time(),
                                    'event_booking_start_date' =>date("d-m-Y", strtotime($bookingStartDate)),
                                    'event_booking_end_date' =>date("d-m-Y", strtotime($bookingEndDate))
                                    
                                );
                                $this->set_response([
                                        'status' =>'SUCCESS',
                                        'message' => 'Data has been added successfully.',
                                        'data' => $response
                                ], REST_Controller::HTTP_OK); 		
                            }
                            else
                            {
                                /* $this->set_response([
                                        'status' =>'SUCCESS',
                                        'message' => 'Data has been added successfully.',
                                        'data' => $eventData
                                ], REST_Controller::HTTP_OK); 		
                                */
                                $this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again later.");
                                return; 
                            }	
                        }
                        else{
                            $eventData = array(								
								'order_for_category'=>'Event',
								'Owner_id'=>$ownerId,
								'user_id'=>$userId,
								'event_id'=>$eventId,
								'no_of_persons'=>$noOfPersons,
								'no_of_days'=>$noOfDays,
								'total_amount'=>$totalAmount,
								'payment_status'=>$paymentStatus,
								'transaction_id'=>$transactionId,
								'cartId'=>'MB'.time(),
								'order_created_at'=>$orderCreatedDate,
								'type_of_purchase'=>$basicOrVip,
								'ordername'=>$orderName,
								'free_event'=>$isFreeEvent,
								'event_booking_start_date' =>$bookingStartDate,
                                				'event_booking_end_date' =>$bookingEndDate
                            );
                            
							$this->db->where('id', $order_id);
                            $this->db->update('tbl_order_history', $eventData);
                            
                            $response = array(
                                'order_id'=>$order_id,
                                'bar_owner_id'=>$ownerId,
                                'bar_id'=>$barId,
                                'event_id'=>$eventId,
                                'user_id'=>$userId,
                                'category'=>"Event",
                                'event_name'=>$orderName,
                                'type_of_purchase'=>($isFreeEvent==0)?$basicOrVip:"-",
                                'no_of_persons'=>$noOfPersons,
                                'no_of_days'=>$noOfDays,
                                'total_amount'=>$totalAmount,
                                'payment_status'=>$paymentStatus,
                                'transaction_id'=>$transactionId,
                                'order_created_at'=>$orderCreatedDate,
                                'free_event'=>$isFreeEvent,
                                'cart_id'=>'MB'.time(),
                                'event_booking_start_date' =>date("d-m-Y", strtotime($bookingStartDate)),
                                'event_booking_end_date' =>date("d-m-Y", strtotime($bookingEndDate))
                                
                            );
                            $this->set_response([
                                    'status' =>'SUCCESS',
                                    'message' => 'Data has been added successfully.',
                                    'data' => $response
                            ], REST_Controller::HTTP_OK); 
                        }
					}	
					else
					{
						$this->sendErrorResponse("INVALID_EVENT","Event details not found.");
						
					}
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