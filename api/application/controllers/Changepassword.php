<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class ChangePassword extends REST_Controller {
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
			$currentPassword = $this->post('currentPassword');
			$newPassword = $this->post('newPassword');
			$userId = $this->post('id');	
			
			if($currentPassword == null){
				$this->sendErrorResponse("INVALID_CURRENT_PASSWORD","Invalid current password..");
			}	
			else if($currentPassword != null){
				$isCurrentPasswordExists = $this->checkCurrentPasswordExixts($currentPassword,$userId);
			
				if($isCurrentPasswordExists != null){
					$this->updatePassword($newPassword, $userId);
				}
				else
				{
					$this->sendErrorResponse("INVALID_CURRENT_PASSWORD","Invalid current password.");
					return;
				}	
			}
			
			
			
		}catch(Exception $e){
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
		}
	}
	
	
	function checkCurrentPasswordExixts($currentPassword, $userId){
		$query_str = "SELECT * FROM user_register where id = ".$userId." AND password='".$currentPassword."'" ;
		$result = $this->db->query($query_str);
		
		if(!$result){
			throw new Exception();
		}
		$num_rows = $result->num_rows();
		if($num_rows > 0){
			return $result->result()[0];
		}
		return null;
	}
	
	function updatePassword($newPassword, $userId){
		$query_str = "update user_register set password = '".$newPassword."' where id=".$userId;		
		$result = $this->db->query($query_str);
		
		if(!$result){
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
		}
		else
		{
			$this->sendResponse("Password has been changed successfully.");
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