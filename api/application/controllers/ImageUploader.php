<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

/**
*  Parameters: {"bar_id": "3"}
**/

class ImageUploader extends REST_Controller {
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

	function get_post(){
		try{			
			$data = json_decode(file_get_contents('php://input'), true);
			$userid = $data['user_id'];
			
			$getProfilePic = "select path from user_image where user_id = ".$userid;
			$exeQuery = $this->db->query($getProfilePic);
			$res = $exeQuery->row_array();
			if($res['path'])
			{
				$this->set_response([
					'status' =>'SUCCESS',
					'data' => $res['path'],
				], REST_Controller::HTTP_OK); 	
			}else{
				$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.',
				], REST_Controller::HTTP_OK); 	
			}
			
        }catch(Exception $e){
            
            $this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");

        }
    }

	function index_post(){
		try{
			
			$data = json_decode(file_get_contents('php://input'), true);
			$userid = $data['user_id'];
			$imgarray = $data['imgarray'];
			$ext = $data['ext'];
			
			$path = "./../user_gallary";
			$vpath = $path.'/'.$userid.'.'.$ext;
			$ipath = 'https://mybarnite.com/user_gallary/'.$userid.'.'.$ext;

			$fileBin = base64_decode($imgarray);
			if(file_put_contents($vpath,$fileBin)){

				$getProfilePic = "select file_path from tbl_user_gallery where logo_image=1 AND user_id = ".$userid;
				$exeQuery = $this->db->query($getProfilePic);
				$res = $exeQuery->row_array();
				if ($res['file_path'])
				{
					$this->set_response([
						'status' =>'SUCCESS',
						'data' => 'https://mybarnite.com/'.$res['file_path'],
					], REST_Controller::HTTP_OK); 		
				}else{
					$data = array('path' => $ipath,'user_id' => $userid);
					$this->db->insert('tbl_user_gallery', $data);
					$insert_id = $this->db->insert_id();
					if($insert_id){
						$this->set_response([
							'status' =>'SUCCESS',
							'data' => $ipath,
						], REST_Controller::HTTP_OK); 		
					}else{
						$this->set_response([
							'status' =>'FAILED',
							'error_code' => "SERVER_ERROR",
							'message' => 'There is some error.',
						], REST_Controller::HTTP_OK); 	
					}
				}

			}else{
				$this->set_response([
					'status' =>'FAILED',
					'error_code' => "SERVER_ERROR",
					'message' => 'There is some error.',
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
