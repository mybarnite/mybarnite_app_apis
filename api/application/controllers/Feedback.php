<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class FeedBack extends REST_Controller {
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
			$subject = $this->post('subject');
			$message = $this->post('message');
			$userId = $this->post('user_id');	
			
			if($userId == ""){
				$this->sendErrorResponse("INVALID_USER","User not found.");
			}	
			else{
				$data = array(
					'user_id' => $userId,
					'subject' => $subject,
					'message' => $message
				);
				
				$this->db->insert('tbl_app_feedback', $data);
				$lastInsertedId = $this->db->insert_id();
				
				if($lastInsertedId>0){
					$this->sendResponse("Data has been added successfully.");
				}
				else
				{
					$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
					return;
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