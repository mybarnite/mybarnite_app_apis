<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class SocialMedia extends REST_Controller {
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
	
	function signup_post(){
		try{
			$loginType = $this->post('loginType');
			$socialMediaAccountId = $this->post('userId');
			$name = $this->post('name');	
			$email = $this->post('email');
			$imageURL = $this->post('imageURL');
			
			$userId = null;
			$isSignupRequest = false;
			$socialMediaAccount = $this->findSocialMediaAccountByIdAndType($socialMediaAccountId,$loginType);
			if($socialMediaAccount != null){
				$this->updateSocialMediaAccount($socialMediaAccount->id,$email, $name, $imageURL);
				$userAccount = $this->findUserAccountById($socialMediaAccount->user_id);
				$userId = $userAccount->id;
				
				if($userAccount->signup_channel == '1'){
					$this->updateUserAccount($userId,$email, $name);
				}
			} else {
				$socialMediaAccount = $this->findSocialMediaAccountByEmail($email);
				if($socialMediaAccount != null) {
					$userAccount = $this->findUserAccountById($socialMediaAccount->user_id);
					$userId = $userAccount->id;
					
					$this->createSocialMediaAccount($socialMediaAccountId, $userId,$loginType, $name, $email, $imageURL);
					
					if($userAccount->signup_channel == '1'){
						$this->updateUserAccount($userAccount->id,$email, $name);
					}
					
				} else {
					$userAccount = $this->findUserAccountByEmail($email);
					if($userAccount != null){
						$userId = $userAccount->id;
						$this->createSocialMediaAccount($socialMediaAccountId, $userId, $loginType, $name, $email, $imageURL);
					} else {
						$userId = $this->createUserAccount($name,$email);
						$this->createSocialMediaAccount($socialMediaAccountId, $userId, $loginType, $name, $email, $imageURL);
						$isSignupRequest = true;
					}
				}
				
			}
			$response = $this->prepareResponse($userId,$isSignupRequest);
			$this->sendResponse($response);
			
		}catch(Exception $e){
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
		}
	}
	
	function link_post(){
		try {
			$userId = $this->post('userId');
			$loginType = $this->post('loginType');
			$socialMediaAccountId = $this->post('socialMediaUserId');
			$name = $this->post('name');	
			$email = $this->post('email');
			$imageURL = $this->post('imageURL');
			
			$socialMediaAccount = $this->findSocialMediaAccountByIdAndTypeAndUserId($socialMediaAccountId,$loginType,$userId);
			
			if($socialMediaAccount != null){
				$this->sendErrorResponse("ACCOUNT_ALREADY_LINKED","ACCOUNT_ALREADY_LINKED");
				return;
			}
			
			$socialMediaAccount = $this->findSocialMediaAccountByUserIdAndType($userId,$loginType);
			if($socialMediaAccount != null){
				$this->sendErrorResponse("ANOTHER_ACCOUNT_ALREADY_LINKED","ANOTHER_ACCOUNT_ALREADY_LINKED");
				return;
			} 
			$socialMediaAccount = $this->findSocialMediaAccountByIdAndType($socialMediaAccountId,$loginType);
			if($socialMediaAccount != null){
				$this->sendErrorResponse("ACCOUNT_LINKED_WITH_ANOTHER_USER","ACCOUNT_LINKED_WITH_ANOTHER_USER");
				return;
			} 
			
			$this->createSocialMediaAccount($socialMediaAccountId, $userId, $loginType, $name, $email, $imageURL);
			
			
			$response = $this->prepareResponse($userId,false);
			$this->sendResponse($response);	
		} catch(Exception $e) {
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try agian");
		}
	}
	
	function unlink_post(){
		try {
			$userId = $this->post('userId');
			$loginType = $this->post('loginType');
			$socialMediaAccountId = $this->post('socialMediaUserId');
			
			$socialMediaAccounts = $this->findSocialMediaAccountByUserId($userId);
			$userAccount = $this->findUserAccountById($userId);
			
			if($socialMediaAccounts == null){
				$this->sendErrorResponse("NO_ACCOUNTS_LINKED","NO_ACCOUNTS_LINKED");
				return;
			} 
			
			if(count($socialMediaAccounts) == 1 && $userAccount->signup_channel == '1') {
				$this->sendErrorResponse("CANNOT_UNLINK_ACCOUNT","CANNOT_UNLINK_ACCOUNT");
				return;
			}
			
			$this->deleteSocialMediaAccount($socialMediaAccountId,$loginType,$userId);
			$response = $this->prepareResponse($userId,false);
			$this->sendResponse($response);	
		} catch(Exception $e) {
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try agian");
		}
	}
	
	function findSocialMediaAccountByIdAndType($socialMediaAccountId, $loginType){
		$query_str = "SELECT * FROM social_media_user_account where id = '".$socialMediaAccountId."' AND type='".$loginType."'" ;
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
	
	function findSocialMediaAccountByIdAndTypeAndUserId($socialMediaAccountId, $loginType,$userId){
		$query_str = "SELECT * FROM social_media_user_account where id = '".$socialMediaAccountId."' AND type='".$loginType."' AND user_id = ".$userId."" ;
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
	
	function findSocialMediaAccountByUserIdAndType($userId,$loginType){
		$query_str = "SELECT * FROM social_media_user_account where user_id = '".$userId."' AND type='".$loginType."'";
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
	
	function updateSocialMediaAccount($socialMediaUserId, $email, $name, $profilePicture){
		$data = array(
			'email' => $email,
			'name' => $name,
			'profile_picture' => $profilePicture
		);
		$this->db->where('id', $socialMediaUserId);
		$this->db->update('social_media_user_account', $data);
	}
	
	function updateSocialMediaAccountByUserIdandLoginType($userId, $loginType, $email, $name, $profilePicture){
		$data = array(
			'email' => $email,
			'name' => $name,
			'profile_picture' => $profilePicture
		);
		$this->db->where('user_id', $socialMediaUserId);
		$this->db->where('type', $loginType);
		$this->db->update('social_media_user_account', $data);
	}
	
	function findUserAccountById($userId){
		$sql = "select id, name, email,signup_channel from user_register where r_id = 2 and id = '" .$userId. "' and status = 'Active' ";
		$result = $this->db->query($sql);
			
		if(!$result){
			throw new Exception();
		}
		
		$num_rows = $result->num_rows();
		if($num_rows > 0){
			return $result->result()[0];
		}
		return null;
	}
	
	function updateUserAccount($userId,$email,$name){
		$data = array(
			'email' => $email,
			'name' => $name,
		);
		$this->db->where('id', $userId);
		$this->db->update('user_register', $data);
	}
	
	function findSocialMediaAccountByEmail($email){
		$query_str = "SELECT * FROM social_media_user_account where email = '".$email."'";
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
	
	function createSocialMediaAccount($socialMediaUserId, $userId, $type, $name, $email, $profilePicture){
		$socaialMediaUserAccount = array(
			'id' => $socialMediaUserId,
			'user_id' => $userId,
			'type' => $type,
			'name' => $name,
			'email' => $email,
			'profile_picture' => $profilePicture
		);
		
		$this->db->insert('social_media_user_account', $socaialMediaUserAccount);
	}
	
	function findUserAccountByEmail($email){
		$sql = "select id, name, email from user_register where r_id = 2 and email = '" .$email. "' and status = 'Active' ";
		$result = $this->db->query($sql);
			
		if(!$result){
			throw new Exception();
		}
		
		$num_rows = $result->num_rows();
		if($num_rows > 0){
			return $result->result()[0];
		}
		return null;
	}
	
	function createUserAccount($name,$email){
		$userAccount = array(
			'r_id' => 2,
			'name' => $name,
			'email' => $email,
			'status' => 'Active',
			'signup_channel' => '1'
		);
		
		$this->db->insert('user_register', $userAccount);
		$userId = $this->db->insert_id();
		
		return $userId;
	}
	
	function findSocialMediaAccountByUserId($userId){
		$query_str = "SELECT * FROM social_media_user_account where user_id = '".$userId."'";
		
		$result = $this->db->query($query_str);
		
		if(!$result){
			throw new Exception();
		}
		$num_rows = $result->num_rows();
		if($num_rows > 0){
			return $result->result();
		}
		return null;
	}
	
	function deleteSocialMediaAccount($socialMediaAccountId, $loginType, $userId){
		$this->db->where('id', $socialMediaAccountId);
		$this->db->where('type',$loginType);
		$this->db->where('user_id',$userId);
		
		$this->db->delete('social_media_user_account');		
	}
	
	function prepareResponse($userId,$isSignupRequest){
		$userAccount = $this->findUserAccountById($userId);
		$socialMediaAccounts = $this->findSocialMediaAccountByUserId($userId);
		
		return array (
			'id' => $userAccount->id,
			'name' => $userAccount->name,
			'email'=> $userAccount->email,
			'isSignupRequest' => $isSignupRequest,
			'socialMediaAccounts'=>$socialMediaAccounts
		);
		
	}
	
	function sendResponse($response){
		$this->set_response([
					'status' =>'SUCCESS',
					'data' => $response
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