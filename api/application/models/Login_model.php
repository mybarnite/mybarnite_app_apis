<?php
class Login_model extends CI_Model {

        public function __construct()
        {
                $this->load->database();
        }
		 function get_user($usr, $pwd)
		 {
			  $sql = "select * from users where u_email = '" . $usr . "' and u_password = '" . md5($pwd) . "' and r_id = 1";
			  $query = $this->db->query($sql);
			  return $query->num_rows();
		 }
		 
}