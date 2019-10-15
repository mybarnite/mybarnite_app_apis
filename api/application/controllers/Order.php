<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Order extends REST_Controller {

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
	
	function summary_post(){
		try{
			$orderId = $this->post('order_id');
			if($orderId==""){
				$this->sendErrorResponse("INVALID_ORDER","Order not found.");
			}
			else{
				$sql = "SELECT o.* ,u.name as uname , u.email, b.business_name as bar_name, b.address as bar_address,b.locality as locality, b.region as region, b.zipcode as zipcode, b.longitude as longitude, b.latitude as latitude, e.event_name as event_name, e.event_start as event_start_date, e.event_end as event_end_date, e.event_starttimestamp as event_starttimestamp, e.event_endtimestamp as event_endtimestamp, e.free_event as free_event FROM tbl_order_history o left join tbl_events e on o.event_id = e.id left join bars_list b on CASE WHEN order_for_category = 'Bar' THEN o.bar_id = b.id ELSE e.bar_id = b.id END left join user_register u on u.id=o.user_id where o.id =".$orderId;
				$result = $this->db->query($sql);
				if(!$result){
					$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
					return;
				}
				else if($result->num_rows() > 0) {
					foreach ($result->result() as $row) {
						if($row->order_for_category=="Bar")
						{
						    $address = "";
						    if($row->bar_address != ""){
						        $address = $row->bar_address;
						    }
						    if($row->locality != "" && $address != ""){
						        $address = $address.", ".$row->locality;
						    }
						    else if($row->locality != "" && $address == ""){
						        $address = $row->locality;
						    }
						    if($row->region != "" && $address != ""){
						        $address = $address.", ".$row->region;
						    }
						    else if($row->region != "" && $address == ""){
						        $address = $row->region;
						    }
						    if($row->zipcode != "" && $address != ""){
						        $address = $address.", ".$row->zipcode;
						    }
						    else if($row->zipcode != "" && $address == ""){
						        $address = $row->zipcode;
						    }
							$row->no_of_persons = ($row->is_hall_booked==1)?0:$row->no_of_persons;
							//$array = $this->getData($row->id,$row->name,$row->bar_booking_start_date,$row->bar_booking_starts,$row->bar_booking_ends,$row->bar_booking_purpose,$row->is_hall_booked,$row->no_of_persons,$row->total_amount);
							$array = array(
								'order_id' => $row->id,
								'business_name' => $row->bar_name,
								'booking_date' => date("m-d-Y",strtotime($row->bar_booking_start_date)),
								'starting_time' => $row->bar_booking_starts,
								'ending_time' => $row->bar_booking_ends,
								'booking_purpose' => $row->bar_booking_purpose,
								'is_hall_booked' => $row->is_hall_booked,
								'no_of_persons' => $row->no_of_persons,
								'total_amount' => $row->total_amount,
								'payable_amount' => $row->payable_amount,
								'locality' => $row->locality,
								'region' => $row->region,
								'address' => $address,
								'zipcode' => $row->zipcode,
								'longitude' => $row->longitude,
								'latitude' => $row->latitude,
								'logo_image_url' => "https://mybarnite.com/images/no_image.png",
								'starting_time' => $row->bar_booking_starts,
								'ending_time' => $row->bar_booking_ends,
							);
							
						}else{
						    $address = "";
						    if($row->bar_address != ""){
						        $address = $row->bar_address;
						    }
						    if($row->locality != "" && $address != ""){
						        $address = $address.", ".$row->locality;
						    }
						    else if($row->locality != "" && $address == ""){
						        $address = $row->locality;
						    }
						    if($row->region != "" && $address != ""){
						        $address = $address.", ".$row->region;
						    }
						    else if($row->region != "" && $address == ""){
						        $address = $row->region;
						    }
						    if($row->zipcode != "" && $address != ""){
						        $address = $address.", ".$row->zipcode;
						    }
						    else if($row->zipcode != "" && $address == ""){
						        $address = $row->zipcode;
						    }
						    $starting_time = date('H:i:s', $row->event_starttimestamp);
						    $ending_time = date('H:i:s', $row->event_endtimestamp);
							$array = array(
								'order_id' => $row->id,
								'eventName' => $row->event_name,
								'no_of_persons' => $row->no_of_persons,
								'no_of_booked_days' => $row->no_of_days,
								'total_amount' => ($row->free_event!=1)?$row->total_amount:"Free event",
								'payable_amount' => ($row->free_event!=1)?$row->payable_amount:"Free event",
								'business_name' => $row->bar_name,
								'locality' => $row->locality,
								'region' => $row->region,
								'address' => $address,
								'zipcode' => $row->zipcode,
								'longitude' => $row->longitude,
								'latitude' => $row->latitude,
								'type_of_purchase' => ($row->free_event!=1)?$row->type_of_purchase:"-",
								'event_booking_start_date' => $row->event_booking_start_date,
								'event_booking_end_date' => $row->event_booking_end_date,
								'isFreeEvent' => ($row->free_event == '1'?true:false),
								'logo_image_url' => "https://mybarnite.com/images/no_image.png",
								'starting_time' => $starting_time,
								'ending_time' => $ending_time
							);
						}	
						$orders[] = $array;
					}
					$this->set_response([
							'status' =>'SUCCESS',
							'data' => $orders
					], REST_Controller::HTTP_OK); 
				}
				else {
					$this->sendErrorResponse("INVALID_ORDER","Order not found.");
				}	
			}
		}catch(Exception $e){
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again ".$e->getMessage());
		}
		
	}
	
	function refund_post()
	{
	    $orderId = $this->post('order_id');
	    $sql = 'SELECT o.*, u.name as username, u.email FROM tbl_order_history o join user_register u on o.user_id=u.id and o.id='.$orderId;
	    $result = $this->db->query($sql);
	    if($result->num_rows() == 0){
			$this->sendErrorResponse("RECORD_NOT_FOUND","Order not found");
			return;
		}
		else if($result->num_rows() > 0) {
		    $row = $result->row_array();
		    
		    $ordername = $row['ordername'];
        	$username = $row['username'];
        	$amount = $row['payable_amount'];
        	$transaction_id = $row['transaction_id']; 
        	$payment_status = $row['payment_status']; 
        	$email = $row['email']; 
        	$owner_id = $row['owner_id'];
        	
        	$sql1 = 'SELECT email FROM user_register where id ='.$owner_id;
        	$result1 = $this->db->query($sql1);
        	$row1 = $result1->row_array();
        	
    		$to1 = $row1['email'];
    		$subject1 = 'Mybarnite - Refund Request';
    		$from1 = 'info@mybarnite.com';
    		 
    		// To send HTML mail, the Content-type header must be set
    		$headers1  = 'MIME-Version: 1.0' . "\r\n";
    		$headers1 .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    		 
    		// Create email headers
    		$headers1 .= 'From: '.$from1."\r\n".
    			'Reply-To: '.$from1."\r\n" .
    			'X-Mailer: PHP/' . phpversion();
    		 
    		// Compose a simple HTML email message
    		$message1 = "<html>";
    		$message1 .= "<head><title>Mybarnite</title></head>";
    		$message1 .= "<body>";
    		$message1 .= "<p>Dear Admin,</p>";
    		$message1 .= "<p>New refund has been requested for order id ".$orderId."</p><br/><br/>";
    		$message1 .= "<br/><br/>";
    		$message1 .= "<p>Thank you for using our website</p><p>Mybarnite Limited</p><p>Email: info@mybarnite.com</p><p>URL: mybarnite.com</p><p><img src='https://mybarnite.com/images/Picture1.png' width='110'></p>";
    		$message1 .= "</body></html>";
    		
    		if(mail($to1, $subject1, $message1, $headers1)){
    		    $sql2 = 'UPDATE tbl_order_history SET payment_status = "Refund Requested" where id ='.$orderId;
        	    $result2 = $this->db->query($sql2);
        	
        	    $this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data'	=> '',
						'message' => 'Order Refund Request Sent Successfully'
					], REST_Controller::HTTP_OK);   
    		}
    		else{
    		    $this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again2");
    		}
		}
	}
	
	function listById_post()
	{
		try{
			$userId = $this->post('user_id');
			$offset = $this->post('offset');
			$limit = $this->post('limit');
			
			if($userId!=""){
				$sql = "SELECT * , CASE WHEN order_for_category = 'Bar' THEN (SELECT Business_Name FROM bars_list WHERE id = o.bar_id) ELSE (SELECT event_name from tbl_events WHERE id = o.event_id) END as name,
CASE WHEN order_for_category = 'Bar' THEN 0 ELSE (SELECT event_end from tbl_events WHERE id = o.event_id) END as event_end_date, CASE WHEN order_for_category = 'Bar' THEN 0 ELSE (SELECT event_start from tbl_events WHERE id = o.event_id) END as event_start_date FROM tbl_order_history o where o.deleted = 0 AND user_id =".$userId;
				$limitForList = " order by o.id DESC LIMIT ".$limit." OFFSET ".$offset*$limit;
				$sql = $sql.$limitForList;
				$result = $this->db->query($sql);
				if(!$result){
					$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again1");
					return;
				}
				else if($result->num_rows() > 0) {
					foreach ($result->result() as $row) {
						if($row->order_for_category=="Bar")
						{
						    $delete = false;
						    $refund = false;
						    if(strcmp($row->payment_status, "Pending") == 0 || strcmp($row->payment_status, "Canceled") == 0 || strcmp($row->payment_status, "Expired") == 0 || strcmp($row->payment_status, "Refunded") == 0){
						        $delete = true;
						    }
						    else if(strcmp($row->payment_status, "Done") == 0 && $row->bar_booking_start_date != null && strtotime(date('Y-m-d')) > strtotime($row->bar_booking_start_date)){
						        $delete = true;
						    }
						    else if(strcmp($row->payment_status, "Done") == 0 && $row->bar_booking_start_date != null && strtotime(date('Y-m-d')) < strtotime($row->bar_booking_start_date)){
						        $refund = true;
						    }
							$array = array(
								'order_id' => $row->id,
								'bar_id' => $row->bar_id,
								'category' => $row->order_for_category,
								'title' => $row->ordername,
								'booking_purpose' => $row->bar_booking_purpose,
								'booking_date' => ($row->bar_booking_start_date!="0000-00-00")?date("d-m-Y",strtotime($row->bar_booking_start_date)):"00-00-0000",
								'starting_time' => date("h:i:s A",strtotime($row->bar_booking_starts)),
								'ending_time' => date("h:i:s A",strtotime($row->bar_booking_ends)),
								'is_hall_booked' => ($row->is_hall_booked==1)?"Yes":"No",
								'no_of_persons' => ($row->is_hall_booked==1)?0:$row->no_of_persons,
								'payment_status' => $row->payment_status,
								'total_amount' => $row->payable_amount,
								'delete' => $delete,
								'refund' => $refund
							);
						}else{
						    $delete = false;
						    $refund = false;
						    if(strcmp($row->payment_status, "Pending") == 0 || strcmp($row->payment_status, "Canceled") == 0 || strcmp($row->payment_status, "Expired") == 0 || strcmp($row->payment_status, "Refunded") == 0){
						        $delete = true;
						    }
						    else if(strcmp($row->payment_status, "Done") == 0 && $row->event_booking_start_date != null && strtotime(date('Y-m-d')) > strtotime($row->event_booking_end_date)){
						        $delete = true;
						    }
						    else if(strcmp($row->payment_status, "Done") == 0 && $row->event_booking_start_date != null && strtotime(date('Y-m-d')) < strtotime($row->event_booking_start_date)){
						        $refund = true;
						    }
							$array = array(
								'order_id' => $row->id,
								'event_id' => $row->event_id,
								'category' => $row->order_for_category,
								'title' => $row->ordername,
								'event_status' => (strtotime(date('Y-m-d')) >= strtotime($row->event_end_date))?"Expired":"Not Expired",
								'free_event' => ($row->free_event!=1)?"No":"Yes",
								'payment_status' => $row->free_event==1 && $row->payment_status == "Done" ? "Confirmed" : $row->payment_status,
								'total_amount' => ($row->free_event!=1)?$row->total_amount:"Free event",
								'event_booking_start_date' => date("d-m-Y",strtotime($row->event_booking_start_date)),
								'event_booking_end_date' => date("d-m-Y",strtotime($row->event_booking_end_date)),
								'event_start_date' => date("d-m-Y",strtotime($row->event_start_date)),
								'event_end_date' => date("d-m-Y",strtotime($row->event_end_date)),
								'type_of_purchase' => $row->type_of_purchase,
								'no_of_persons' => $row->no_of_persons,		
								'delete' => $delete,
								'refund' => $refund
							);
						}
						$orders[] = $array;
					}
					$this->set_response([
							'status' =>'SUCCESS',
							'data' => $orders
					], REST_Controller::HTTP_OK); 
				}
				else {
					$this->sendErrorResponse("INVALID_ORDER","No Orders found.");
				}
			}else{
				$this->sendErrorResponse("INVALID_USER","User not found.");
			}	
		}catch(Exception $e){
		    echo($e);
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again1");
		}
	}
	
	function getData($id,$barName,$bookingDate,$startingTime,$endingTime,$bookingPurpose,$isHallBooked,$noOfPersons,$totalAmount){
		return array(
			'order_id' => $id,
			'bar_name' => $barName,
			'booking_date' => date("m-d-Y",strtotime($bookingDate)),
			'starting_time' => $startingTime,
			'ending_time' => $endingTime,
			'booking_purpose' => $bookingPurpose,
			'is_hall_booked' => $isHallBooked,
			'no_of_persons' => $noOfPersons,
			'total_amount' => $totalAmount
		);
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
	
	function updatePayment_post()
	{
	    $orderId = $this->post('order_id');
	    $userId = $this->post('user_id');
	    $sql = 'SELECT * FROM tbl_order_history where user_id='.$userId.' and id='.$orderId;
	    $result = $this->db->query($sql);
	    if($result->num_rows() == 0){
			$this->sendErrorResponse("RECORD_NOT_FOUND","Order not found");
			return;
		}
		else if($result->num_rows() > 0) {
		    $row = $result->row_array();
		    
	    	$PayableAmount = $row['payable_amount'];
        	$amount = $this->post('amount');
    		
    		if($amount != '' && $amount != 0){
    		    $amount += $PayableAmount;
    		    $sql2 = 'UPDATE tbl_order_history SET payment_status = "Done",payable_amount='.$amount.' where id ='.$orderId;
        	    $result2 = $this->db->query($sql2);
        	    $array = array('order_id'=>$orderId,'payable_amount'=>$amount);
        	    $data[] = $array;
        	    $this->set_response([
						'status' =>'SUCCESS',
						'data'	=> $data,
						'message' => 'Payment updated Successfully'
					], REST_Controller::HTTP_OK);   
    		}
    		else{
    		    $this->sendErrorResponse("INVALID_AMOUNT","Amount not valid");
    		}
		}
	}
	
	function getclientsecret_post()
    {
	    $amount = $this->post('amount');
    	if($amount != '' && $amount != 0){
    	    require_once('/home/mybarnite/public_html/config.php');
    	    $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount*100,
                'currency' => 'gbp',
                'payment_method_types' => ['card']
            ]);
            $array = array('client_secret'=>$intent->client_secret);
        	$data[] = $array;
        	$this->set_response([
    					'status' =>'SUCCESS',
    					'data'	=> $data
    				], REST_Controller::HTTP_OK);  
    	}
		else{
		    $this->sendErrorResponse("INVALID_AMOUNT","Amount not valid");
		}
	}
}