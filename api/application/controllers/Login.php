<?php
//require(APPPATH'.libraries/REST_Controller.php');

defined('BASEPATH') OR exit('No direct script access allowed');
require('application/libraries/REST_Controller.php');
class Login extends REST_Controller  {
	//$this->response(array('success' => 'Yes it is working'), 200);

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	 
	 function __construct()
		{
			parent::__construct();
				$this->load->model('Login_model');
                $this->load->helper('url_helper');
				$this->load->library('javascript');
				
				$this->load->helper('form');
				$this->load->helper('url');
				$this->load->helper('html');
				$this->load->database();
				$this->load->library('form_validation');
		}
	 
	 
	function index_post() 
	{
		$r_id = $this->post('r_id');
		$username = $this->post('username');
		$pwd = $this->post('password');
		if(isset($username)&&isset($pwd))
		{
			$sql = "select * from tbl_users where email = '" .$username. "' and password = '" . md5($pwd) . "'  and status = 'Active' ";
			$query = $this->db->query($sql);

			$usr_result=$query->num_rows();

			if ($usr_result > 0) //active user record is present
			{	
					$row = $query->row_array();
					
					//$this->response($row, 200);
					
					$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data' => $row
					], REST_Controller::HTTP_OK); 
			}	
			else
			{
				$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data'	=> '',
						'message' => 'User not found'
					], REST_Controller::HTTP_OK); 
				
			}
			
		} 	
		else
		{
			$this->set_response([
					'status' =>REST_Controller::HTTP_OK,
					'data'	=> '',
					'message' => 'Invalid username and password.'
				], REST_Controller::HTTP_OK); 
			
		}  

        
		
	}
	
	
}			 
   