<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class EventBookingOrderEdit extends REST_Controller {
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
	
	function index_post(){
		try{
			
			//Please add one field in database for order_status and datatype as enum
			//'0' - Received , '1' - On Hold , '2' - Processing , '3' - Incomplete , '4' = Cancel , '5' - Complete

			$id = $this->post('id');

			$data['owner_id'] = $this->post('owner_id');
			$data['bar_id'] = $this->post('bar_id');
			$data['user_id'] = $this->post('user_id');
			$data['event_id'] = $this->post('event_id');
			$data['no_of_persons'] = $this->post('no_of_persons');
			$data['no_of_days'] = $this->post('no_of_days');
			$data['total_amount'] = $this->post('total_amount');
			$data['order_name'] = $this->post('order_name');
			$data['free_event'] = $this->post('free_event');

			$update = $this->db->update('tbl_order_history', $data, array('id'=>$id));

			if (!$update){
				
				$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.'
				], REST_Controller::HTTP_OK); 
			}else{
				
						 $this->set_response([
							'status' =>'SUCCESS',
							'message' => 'Event Order is updated successfully'
						], REST_Controller::HTTP_OK);  
						
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