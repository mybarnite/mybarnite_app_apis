<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

class EditFeedback extends REST_Controller {
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
	
	function edit_post(){
		try{

			$id = $this->post('id');

			$data['user_id'] = $this->post('user_id');
			$data['subject'] = $this->post('subject');
			$data['message'] = $this->post('message');
			
			if( $id ==" " || empty( $id ) ){
				
				$this->set_response([
						'status' =>"FAILED",
						'message' => 'Please provide feedback id.'
				], REST_Controller::HTTP_OK);
			
			
			}else{

				$update = $this->db->update('tbl_app_feedback', $data, array('id'=>$id));

				if (!$update){
					
					$this->set_response([
						'status' =>"FAILED",
						'error_code' => "SERVER_ERROR",
						'message' => 'There is some error.'
					], REST_Controller::HTTP_OK); 
				}else{
					
							 $this->set_response([
								'status' =>'SUCCESS',
								'message' => 'Feedback is updated successfully'
							], REST_Controller::HTTP_OK);  
							
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