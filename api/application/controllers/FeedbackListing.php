<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

//require(APPPATH . '/libraries/REST_Controller.php');

Class FeedbackListing extends REST_Controller {


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
		
			$offset = ($_POST['offset']) ? $_POST['offset'] : 0;
			$limit = ($_POST['limit']) ? $_POST['limit'] : 10;
			$user_id = ($_POST['user_id']) ? $_POST['user_id'] : '';
			
			$query_str = 'SELECT * FROM tbl_app_feedback WHERE user_id = '.$user_id; // ORDER BY id DESC LIMIT '. $offset.' , '. $limit;	
			$result = $this->db->query($query_str);
			if (!$result){
				
				$this->set_response([
					'status' =>"FAILED",
					'error_code' => "SERVER_ERROR",
					'message' => 'Please pass user_id.'
				], REST_Controller::HTTP_OK); 
			}else{
				
				foreach ($result->result() as $row){

					 $finalresult['id'] = $row->id;
					 $finalresult['user_id'] = $row->user_id;
					 $finalresult['subject'] = $row->subject;
					 $finalresult['message'] = $row->message;
					 $arr[]=$finalresult;

				 }
			 	 
				 $this->set_response([
					'status' =>'SUCCESS',
					'data' => $arr
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