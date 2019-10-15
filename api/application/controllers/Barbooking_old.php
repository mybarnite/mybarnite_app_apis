<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Barbooking extends REST_Controller {

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

	function add_post()
	{
		try{
			$barId = $this->post('bar_id');
			$userId = $this->post('user_id');	
			$bookingPurpose = $this->post('booking_purpose');
			$noOfPersons = $this->post('no_of_persons');
			$bookingDate = $this->post('booking_date');
			$startingTime = $this->post('starting_time');
			$endingTime = $this->post('ending_time');
			$isHallBooked = $this->post('is_hall_booked');
			$paymentStatus = "Pending";
			$transactionId = 0;
			$orderCreatedDate = date('Y-m-d H:i:s');
			
			if($userId == ""){
				$this->sendErrorResponse("INVALID_USER","User not found.");
			}	
			elseif($barId == ""){
				$this->sendErrorResponse("INVALID_BAR","Bar details not found.");
			}	
			else
			{
				$sql = "select Business_Name, Owner_id, hall_fee, cost_per_seat from bars_list where id =".$barId;
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
						$orderName = $row['Business_Name'];
						$hallFee = ($isHallBooked==1)?$row['hall_fee']:0;
						$costPerSeat = ($isHallBooked==1)?0:$row['cost_per_seat'];
						$totalCostForBookedSeats = $costPerSeat*$noOfPersons;
						$totalAmount = $hallFee + $totalCostForBookedSeats;
						
						$data = array(
							'Owner_id'=>$ownerId,
							'bar_id'=>$barId,
							'user_id'=>$userId,
							'order_for_category'=>"Bar",
							'no_of_persons'=>$noOfPersons,
							'total_amount'=>$totalAmount,
							'payment_status'=>$paymentStatus,
							'cartId'=>'MB'.time(),
							'transaction_id'=>$transactionId,
							'order_created_at'=>$orderCreatedDate,
							'bar_booking_purpose'=>$bookingPurpose,
							'is_hall_booked'=>$isHallBooked,
							'bar_hall_fee'=>$hallFee,
							'bar_booking_start_date'=>$bookingDate,
							'bar_booking_starts'=>date("H:i:s", strtotime($startingTime)),
							'bar_booking_ends'=>date("H:i:s", strtotime($endingTime)),
							'ordername'=>$orderName
							
						);
						
						$this->db->insert('tbl_order_history', $data);
						$lastInsertedId = $this->db->insert_id();
						
						if($lastInsertedId>0){
							//$this->sendResponse("Data has been added successfully.");
							$array = array(
								'order_id'=>$lastInsertedId,
								'owner_id'=>$ownerId,
								'bar_id'=>$barId,
								'user_id'=>$userId,
								'category'=>"Bar",
								'bar_name'=>$orderName,
								'bar_booking_purpose'=>$bookingPurpose,
								'is_hall_booked'=>($isHallBooked==1)?"Yes":"No",
								'bar_hall_fee'=>$hallFee,
								'no_of_persons'=>$noOfPersons,
								'total_amount'=>$totalAmount,
								'booking_date'=>date("m-d-Y", strtotime($bookingDate)),
								'starting_time'=>date("h:i:s A", strtotime($startingTime)),
								'ending_time'=>date("h:i:s A", strtotime($endingTime)),
								'cart_id'=>'MB'.time()
								
							);
							$this->set_response([
									'status' =>'SUCCESS',
									'message' => 'Data has been added successfully.',
									'data' => $array
							], REST_Controller::HTTP_OK); 		
						}
						else
						{
							$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
							return;
						}	
					}	
					else
					{
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
	
	function checkAvailability_post()
	{
		try{
			$orderId = $this->post('order_id');
			$barId = $this->post('bar_id');
			$bookingDate = $this->post('booking_date');
			$startingTime = date("H:i:s", strtotime($this->post('starting_time')));
			$endingTime= date("H:i:s", strtotime($this->post('ending_time')));
			$hallBooking = $this->post('hall_booking');
			$noOfPersons = $this->post('no_of_persons');
			$data = array();
			
			if($hallBooking==1)
			{
				if($orderId!="")
				{
					$sql = 'select * from tbl_order_history where id!= '.$orderId.' and payment_status = "Done" and bar_id='.$barId.' and bar_booking_start_date = "'.Date("Y-m-d",strtotime($bookingDate)).'" AND ((bar_booking_starts <=  "'.$startingTime.'" AND bar_booking_ends >  "'.$startingTime.'")OR (bar_booking_starts <  "'.$endingTime.'" AND bar_booking_ends >=  "'.$endingTime.'")OR (bar_booking_starts >=  "'.$startingTime.'" AND bar_booking_starts <  "'.$endingTime.'")OR (bar_booking_ends >  "'.$startingTime.'" AND bar_booking_ends <=  "'.$endingTime.'")) and is_hall_booked = "1"';
				}
				else
				{
					$sql = 'select * from tbl_order_history where bar_id='.$barId.' and payment_status = "Done" and bar_booking_start_date = "'.Date("Y-m-d",strtotime($bookingDate)).'" AND ((bar_booking_starts <=  "'.$startingTime.'" AND bar_booking_ends >  "'.$startingTime.'")OR (bar_booking_starts <  "'.$endingTime.'" AND bar_booking_ends >=  "'.$endingTime.'")OR (bar_booking_starts >=  "'.$startingTime.'" AND bar_booking_starts <  "'.$endingTime.'")OR (bar_booking_ends >  "'.$startingTime.'" AND bar_booking_ends <=  "'.$endingTime.'")) and is_hall_booked = "1"';
				}	
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
					//$is_hall_available = 1 that means booking with this order id already exists and not available_for_booking
					$flag_available_for_booking = ($num_rows > 0)?0:1;
					$is_available_for_booking = ($num_rows > 0)?"No":"Yes";
					$data["available_booking_flag"]=$flag_available_for_booking;
					$data["request_for_hall_booking"]="Yes";
					$data["request_for_seat_booking"]="No";
					$data["available_for_booking"]=$is_available_for_booking;
					
					$this->set_response([
						'status' =>'SUCCESS',
						'data' => $data
					], REST_Controller::HTTP_OK); 	
				}	
			}
			elseif($noOfPersons!=""&&$noOfPersons>0)
			{
				$sql1 = 'select seat_for_basic as seats from bars_list where id='.$barId;
				$query1 = $this->db->query($sql1);
				$num_rows=$query1->num_rows();
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
					if($num_rows > 0)
					{
						$row = $query1->row_array();	
						$total_seats = $row['seats'];
					}	
				}	
				if($orderId!="")
				{
					$sql = 'SELECT SUM(no_of_persons) as seats FROM tbl_order_history WHERE id!= '.$orderId.' and payment_status = "Done" and bar_id='.$barId.' and no_of_persons >0 and bar_booking_start_date <= "'.Date("Y-m-d",strtotime($bookingDate)).'" AND ((bar_booking_starts <=  "'.$startingTime.'" AND bar_booking_ends >  "'.$startingTime.'")OR (bar_booking_starts <  "'.$endingTime.'" AND bar_booking_ends >=  "'.$endingTime.'")OR (bar_booking_starts >=  "'.$startingTime.'" AND bar_booking_starts <  "'.$endingTime.'")OR (bar_booking_ends >  "'.$startingTime.'" AND bar_booking_ends <=  "'.$endingTime.'"))';
				}
				else
				{
					$sql = 'SELECT SUM(no_of_persons) as seats FROM tbl_order_history WHERE bar_id='.$barId.' and payment_status = "Done" and no_of_persons >0 and bar_booking_start_date <= "'.Date("Y-m-d",strtotime($startingTime)).'" AND ((bar_booking_starts <=  "'.$startingTime.'" AND bar_booking_ends >  "'.$startingTime.'")OR (bar_booking_starts <  "'.$endingTime.'" AND bar_booking_ends >=  "'.$endingTime.'")OR (bar_booking_starts >=  "'.$startingTime.'" AND bar_booking_starts <  "'.$endingTime.'")OR (bar_booking_ends >  "'.$startingTime.'" AND bar_booking_ends <=  "'.$endingTime.'"))';
					
				}
				$query = $this->db->query($sql);
				$num_rows1 = $query->num_rows();
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
					if($num_rows1 > 0)
					{
						$rows = $query->row_array();	
						$total_booked_seats = $rows['seats'];
						$available_seats = $total_seats - $total_booked_seats;
						if($noOfPersons<=$available_seats)
						{
							$is_available_for_booking = 1;
							$available_seats=$available_seats;
						}
						elseif($noOfPersons>$total_seats)
						{
							$is_available_for_booking = 0;
							$available_seats=0;
						}
						elseif($noOfPersons>=$available_seats)
						{
							$is_available_for_booking = 1;
							$available_seats=$available_seats;
						}
						else
						{
							$is_available_for_booking = 1;
							$available_seats=$available_seats;
						}
						
						$flag_available_for_booking = ($is_available_for_booking == 0)?0:1;
						$data["available_booking_flag"]=$flag_available_for_booking;
						$data["request_for_hall_booking"]="No";
						$data["request_for_seat_booking"]="Yes";
						$data["available_for_booking"]=($is_available_for_booking == 0)?"No":"Yes";
						$data["available_seats_for_booking"]=$available_seats;
						$this->set_response([
							'status' =>'SUCCESS',
							'data' => $data
						], REST_Controller::HTTP_OK); 	
					}
					else
					{
						$this->sendErrorResponse("PARAMETER_NOT_FOUND","Something went wrong.");
					}		
				}
			}	
			else
			{
				$this->sendErrorResponse("PARAMETER_NOT_FOUND","Something went wrong.");
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
?>