<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('application/libraries/REST_Controller.php');

Class Payment extends REST_Controller {

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
	
	function refundRequest_post()
	{
		try{
			$orderId = $this->post('order_id');
			$sql = 'select * from tbl_order_history where id='.$orderId.' and payment_status="Pending"';
			$query = $this->db->query($sql);
			if(!$query){
				$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
				return;
			}
			else
			{
				$num_rows=$query->num_rows();
				if ($num_rows > 0)
				{
					$array = array(
				
						'payment_status'=>'Refund Requested'
					);
					$this->db->where('id', $orderId);
					$this->db->update('tbl_order_history', $array);
					$i = $this->db->affected_rows();
					if($i>0)
					{
						$this->set_response([
							'status' =>"SUCCESS",
							'message' => 'Refund has been requested successfully.'
						], REST_Controller::HTTP_OK); 
					}
					else
					{
						$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
					}		
				}
				else
				{
					$this->set_response([
						'status' =>"FAILED",
						'error_code' => "ORDER_NOT_FOUND",
						'message' => 'Order not found'
					], REST_Controller::HTTP_OK); 
				}	
			}
		}catch(Exception $e){
			$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
		}
	}
	
	function saveResponse_post()
	{
		try{
			$orderId = $this->post('order_id');
			$isPayOnline = 	$this->post('is_full_payment');
			$transactionId = $this->post('transId');
			//$payableAmount = $this->post('payable_amount');
			$sql = 'select * from tbl_order_history where id='.$orderId;
			$query = $this->db->query($sql);
			if(!$query){
				$this->sendErrorResponse("SERVER_ERROR","Something went wrong, Please try again");
				return;
			}
			else
			{
				$num_rows=$query->num_rows();
				if ($num_rows > 0)
				{	
					$row = $query->row_array();	
					$discount = $row['percentage_discount'];
					$payableamount = ($row['payable_amount']!=0)?$row['payable_amount']:$row['total_amount'];
					if($isPayOnline==0)//Pay at venue
					{
						
						$amount = $payableamount;
						$halfamount = 0.2*$amount;
						$pendingamount = $amount-$halfamount;
					}
					else // Pay online that is full payment
					{
						$amount = ($row['payable_amount']!=0)?$row['payable_amount']:$row['total_amount'];
						$pendingamount = 0;
					}	
					$array = array(
				
						'payment_status'=>'Done',
						'transaction_id'=>$transactionId,
						'percentage_discount'=>$discount,
						'pending_amount'=>$pendingamount,
						'is_pay_online'=>$isPayOnline,
						'is_authorised'=>1
						
					);
					$this->db->where('id', $orderId);
					$this->db->update('tbl_order_history', $array);
					$i = $this->db->affected_rows();
					if($i>0)
					{
								
						$array = array(
				
							'payment_status'=>'Paid',
							'transaction_id'=>$transactionId,
							'amount_to_be_paid'=>$payableamount,
							'pending_amount'=>$pendingamount,
							'half_payment'=>($isPayOnline==0)?"Yes":"No",
							'full_payment'=>($isPayOnline==0)?"No":"Yes"
							
						);
					
						$this->set_response([
								'status' =>'SUCCESS',
								'data' => $array
						], REST_Controller::HTTP_OK); 
					}
					else
					{
						$this->set_response([
							'status' =>"FAILED",
							'error_code' => "SERVER_ERROR",
							'message' => 'There is some error.'
						], REST_Controller::HTTP_OK); 
					}
				}
				else
				{
					$this->set_response([
							'status' =>"FAILED",
							'error_code' => "ORDER_NOT_FOUND",
							'message' => 'Order not found'
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