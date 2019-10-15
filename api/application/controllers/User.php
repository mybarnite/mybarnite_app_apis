<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/libraries/REST_Controller.php');


class User extends REST_Controller
{

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

    function signup_post()
    {
        $userName = $this->post('username');
        $email = $this->post('email');
        $password = $this->post('password');
        $contact = $this->post('contact');

        if ($userName != "" && $email != "" && $password != "" && $contact != "") {

            $sql = "select email from user_register where email = '" . $email . "'";
            $query = $this->db->query($sql);
            if (!$query) {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
                //throw new Exception('error in query');
                //return false;
            } else {

                $usr_result = $query->num_rows();
                if ($usr_result > 0) //active user record is present
                {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "EMAIL_ALREADY_EXISTS",
                        'message' => 'Email Id already exists.'
                    ], REST_Controller::HTTP_OK);

                } else {
                    $data = array(
                        'r_id' => 2,
                        'name' => $userName,
                        'email' => $email,
                        'password' => $password,
                        'contact' => $contact,
                        'status' => 'Active',
                        'is_requestedForClaim' => 0

                    );

                    $this->db->insert('user_register', $data);
                    $insert_id = $this->db->insert_id();
                    if ($insert_id > 0) {
                        $msg = "<html>";
                        $msg .= "<head><title>Mybarnite</title></head>";
                        $msg .= "<body>";
                        $msg .= "Dear $userName<br/><br/>Thank you for joining our website.<br/><br/>your account has been activated, please click on this link:\n\n";
                        $msg .= 'https://mybarnite.com/usersignin.php';
                        $msg .= "<br/><br/>Thank you for using our website<br/><br/>Mybarnite Limited<br/>Email: info@mybarnite.com<br/>URL: mybarnite.com<br/><br/><img src='https://mybarnite.com/images/Picture1.png' width='110'>";
                        $msg .= "</body></html>";
                        $subj = 'Account Confirmation';
                        $to = $email;
                        $from = 'info@mybarnite.com';
                        $appname = 'Mybarnite';
                        $headers = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers .= "From: info@mybarnite.com" . "\r\n" . "";

                        if (mail($to, $subj, $msg, $headers)) {

                            $sql = "select id, name, email, contact, status from user_register where r_id = 2 and email = '" . $email . "' and status = 'Active' ";
                            $query = $this->db->query($sql);
                            if (!$query) {
                                $this->set_response([
                                    'status' => "FAILED",
                                    'error_code' => "SERVER_ERROR",
                                    'message' => 'There is some error.'
                                ], REST_Controller::HTTP_OK);

                            } else {
                                $usr_result = $query->num_rows();

                                if ($usr_result > 0) //active user record is present
                                {
                                    $row = $query->row_array();
                                    $this->set_response([
                                        'status' => "SUCCESS",
                                        'data' => $row
                                    ], REST_Controller::HTTP_OK);
                                } else {
                                    $this->set_response([
                                        'status' => "FAILED",
                                        'error_code' => "USER_NOT_FOUND",
                                        'message' => 'User does not exist.'
                                    ], REST_Controller::HTTP_OK);

                                }
                            }
                            /* $this->set_response([
                                'status' =>"SUCCESS",
                                'userId' => $insert_id
                            ], REST_Controller::HTTP_OK);  */
                        } else {

                            $this->set_response([
                                'status' => "FAILED",
                                'error_code' => "SERVER_ERROR",
                                'message' => 'System is unable to sent email. Please try again later.'
                            ], REST_Controller::HTTP_OK);

                        }
                    } else {
                        $this->set_response([
                            'status' => "FAILED",
                            'error_code' => "SERVER_ERROR",
                            'message' => 'System is unable to process your request. Please try again later.'
                        ], REST_Controller::HTTP_OK);
                    }
                }
            }


        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_PARAMETER",
                'message' => 'Invalid parameters.'
            ], REST_Controller::HTTP_OK);

        }

    }

    function login_post()
    {
        //$r_id = $this->post('r_id');
        $username = $this->post('email');
        $pwd = $this->post('password');
        if (isset($username) && isset($pwd)) {
            $sql = "select id, name, email, contact, status from user_register where r_id = 2 and email = '" . $username . "' and password = '" . $pwd . "'  and status = 'Active' ";
            $query = $this->db->query($sql);
            if (!$query) {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
                //throw new Exception('error in query');
                //return false;
            } else {
                $usr_result = $query->num_rows();

                if ($usr_result > 0) //active user record is present
                {
                    $row = $query->row_array();

                    $sql1 = "select file_name from tbl_user_gallery where user_id = " . $row['id'] . " and logo_image='1'";
                    $query1 = $this->db->query($sql1);
                    if (!$query1) {
                        $this->set_response([
                            'status' => "FAILED",
                            'error_code' => "SERVER_ERROR",
                            'message' => 'There is some error.'
                        ], REST_Controller::HTTP_OK);
                        //throw new Exception('error in query');
                        //return false;
                    } else {
                        //$row1 = $query1->row_array();
                        $res = $query1->row_array();

                        $row['profile_picture_url'] = 'https://mybarnite.com/user_gallery/' . $res['file_name'];
                        $row['socialMediaAccounts'] = $this->findSocialMediaAccountByUserId($row['id']);
                    }
                    $this->set_response([
                        'status' => "SUCCESS",
                        'data' => $row
                    ], REST_Controller::HTTP_OK);
                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "INVALID_CREDENTIALS",
                        'message' => 'Invalid username/password.'
                    ], REST_Controller::HTTP_OK);

                }
            }


        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_CREDENTIALS",
                'message' => 'Invalid username/password.'
            ], REST_Controller::HTTP_OK);

        }


    }

    function forgotpassword_post()
    {
        // $email = $_REQUEST['email'];
        $email = $this->post('email');
        if (isset($email)) {
            $sql = "select id from user_register where r_id = 2 and email = '" . $email . "' and status = 'Active' ";
            $query = $this->db->query($sql);
            $isUser = $query->num_rows();
            if ($isUser > 0) //active user record is present
            {
                $row = $query->row_array();
                //$resetpasskey = md5($row['id']);
                $resetpasskey = $row['id'] . "-" . md5(uniqid(rand(), true));
                $resetpass_timestamp = date("Y-m-d H:i:s");

                $data = array(
                    'resetpasskey' => $resetpasskey,
                    'resetpass_timestamp' => $resetpass_timestamp
                );

                $this->db->where('id', $row['id']);
                $this->db->update('user_register', $data);
                $i = $this->db->affected_rows();
                if ($i > 0) {
                    $msg = "<html>";
                    $msg .= "<head><title>Mybarnite</title></head>";
                    $msg .= "<body>";
                    $msg .= "Dear customer<br/><br/>Thank you for contacting us.<br/>To change password, please click on this link:\n\n";
                    $msg .= '<a href="https://mybarnite.com/resetpassword.php?id=' . $resetpasskey.'"> Reset Password </a>';
                    $msg .= "<br/><br/>Thank you for using our website<br/><br/>Mybarnite Limited<br/>EMail: info@mybarnite.com<br/>URL: mybarnite.com<br/><br/><img src='https://mybarnite.com/images/Picture1.png' alt='Password image' width='50%'>";
                    $msg .= "</body></html>";
                    $subj = 'Mybarnite :Reset Password';
                    $to = $email;
                    $from = 'info@mybarnite.com';
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= "From: info@mybarnite.com" . "\r\n" . "";
                    if (mail($to, $subj, $msg, $headers)) {
                        $this->set_response([
                            'status' => "SUCCESS",
                            'message' => 'Please check your email to reset password.'
                        ], REST_Controller::HTTP_OK);
                    } else {

                        $this->set_response([
                            'status' => "FAILED",
                            'error_code' => "SERVER_ERROR",
                            'message' => 'System is unable to sent email. Please try again later.'
                        ], REST_Controller::HTTP_OK);

                    }

                } else {

                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "SERVER_ERROR",
                        'message' => 'System is unable to process your request. Please try again later.'
                    ], REST_Controller::HTTP_OK);

                }

            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "EMAIL_NOT_FOUND",
                    'message' => 'Email does not exist.'
                ], REST_Controller::HTTP_OK);
            }


        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "EMAIL_NOT_FOUND",
                'message' => 'Invalid email.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function updateDetail_post()
    {
        $id = $this->post('id');
        $userName = $this->post('username');
        $email = $this->post('email');
        //$password = $this->post('password');
        $contact = $this->post('contact');

        if ($id != "") {
            $sql = "update user_register set name='" . $userName . "', email='" . $email . "',contact='" . $contact . "' where id='" . $id . "'";

            $query = $this->db->query($sql);
            if (!$query) {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
            } else {
                $i = $this->db->affected_rows();

                if ($i > 0) {

                    $this->set_response([
                        'status' => "SUCCESS"
                    ], REST_Controller::HTTP_OK);
                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "NO_CHANGES",
                        'message' => 'It seems no changes are there.'
                    ], REST_Controller::HTTP_OK);
                }
            }

        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_PARAMETER",
                'message' => 'Invalid parameters.'
            ], REST_Controller::HTTP_OK);
        }

    }

    function uploadPicture_post()
    {
        $id = $this->post('id');

        if ($id != "") {
            //$upload_dir = 'uploads/';
            if (@$_FILES['userfile'] != "") {
                $file_name = $_FILES['userfile']['name'];
                $file_size = $_FILES['userfile']['size'];
                $file_tmp = $_FILES['userfile']['tmp_name'];
                $file_type = $_FILES['userfile']['type'];
                if ($file_name != "") {
                    $allowed = array('png', 'PNG', 'jpg', 'JPG');
                    $filename = $_FILES['userfile']['name'];
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if (!in_array($ext, $allowed)) {
                        $this->set_response([
                            'status' => "FAILED",
                            'error_code' => "FILE_NOT_FOUND",
                            'message' => 'Invalid file.'
                        ], REST_Controller::HTTP_OK);
                    } else {
                        $folder = "/home/mybarnite/public_html/user_gallery/";
                        $new_filename = time() . '-' . $file_name;
                        //$new_filename = $file_name;
                        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $folder . $new_filename)) {
                            $path = $folder . $new_filename;
                            $sql1 = "UPDATE tbl_user_gallery SET logo_image = '0' WHERE user_id = " . $id;
                            $query1 = $this->db->query($sql1);
                            if (!$query1) {
                                $this->set_response([
                                    'status' => "FAILED",
                                    'error_code' => "SERVER_ERROR",
                                    'message' => 'There is some error.'
                                ], REST_Controller::HTTP_OK);
                            } else {
                                $data = array(
                                    'user_id' => $id,
                                    'file_name' => $new_filename,
                                    'file_path' => 'user_gallery/'.$new_filename,
                                    'status' => 1,
                                    'logo_image' => '1'

                                );

                                $this->db->insert('tbl_user_gallery', $data);
                                $insert_id = $this->db->insert_id();

                                $getProfilePic = "select file_name from tbl_user_gallery where user_id = " . $id . " and logo_image='1'";
                                $exeQuery = $this->db->query($getProfilePic);
                                if (!$exeQuery) {
                                    $this->set_response([
                                        'status' => "FAILED",
                                        'error_code' => "SERVER_ERROR",
                                        'message' => 'There is some error.'
                                    ], REST_Controller::HTTP_OK);

                                } else {
                                    //$row1 = $query1->row_array();
                                    $res = $exeQuery->row_array();

                                    $row['profile_picture_url'] = 'https://mybarnite.com/user_gallery/' . $res['file_name'];

                                }
                                if ($insert_id > 0) {
                                    $this->set_response([
                                        'status' => "SUCCESS",
                                        'data' => $row
                                    ], REST_Controller::HTTP_OK);
                                } else {
                                    $this->set_response([
                                        'status' => "FAILED",
                                        'error_code' => "NO_CHANGES",
                                        'message' => 'It seems no changes are there.'
                                    ], REST_Controller::HTTP_OK);
                                }
                            }

                        } else {
                            $this->set_response([
                                'status' => "FAILED",
                                'error_code' => "FILE_NOT_UPLOADED",
                                'message' => 'File uploading error.'
                            ], REST_Controller::HTTP_OK);
                        }
                    }
                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "FILE_NOT_FOUND",
                        'message' => 'Invalid file.'
                    ], REST_Controller::HTTP_OK);
                }
            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "FILE_NOT_FOUND",
                    'message' => 'Invalid file.'
                ], REST_Controller::HTTP_OK);
            }

        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_PARAMETER",
                'message' => 'Invalid parameters.'
            ], REST_Controller::HTTP_OK);
        }

    }

    function findSocialMediaAccountByUserId($userId)
    {
        $query_str = "SELECT * FROM social_media_user_account where user_id = '" . $userId . "'";
        $result = $this->db->query($query_str);

        if (!$result) {
            throw new Exception();
        }
        $num_rows = $result->num_rows();
        if ($num_rows > 0) {
            return $result->result();
        }
        return null;
    }

    function usergallery_post()
    {
        $id = $this->post('user_id');

        if ($id != "") {
            $sql = "select id,file_name,file_path as image_path from tbl_user_gallery where user_id=" . $id . " order by id";
            $result = $this->db->query($sql);

            if (!$result) {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
            }

            $num_rows = $result->num_rows();
            if ($num_rows > 0) {
                foreach ($result->result() as $row) {
                    $row->image_path = "https://mybarnite.com/" . $row->image_path;
                    $arr[] = $row;
                }
                $this->set_response([
                    'status' => "SUCCESS",
                    'data' => $arr
                ], REST_Controller::HTTP_OK);
            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "NOT_FOUND",
                    'message' => 'Records not found.'
                ], REST_Controller::HTTP_OK);
            }
        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "PARAMETER_NOT_FOUND",
                'message' => 'Parameter not found.'
            ], REST_Controller::HTTP_OK);

        }
    }

    function addUserImage_post()
    {
        $id = $this->post('user_id');

        if ($id != "") {
            //$upload_dir = 'uploads/';
            if (@$_FILES['userfile'] != "") {
                $file_name = $_FILES['userfile']['name'];
                $file_size = $_FILES['userfile']['size'];
                $file_tmp = $_FILES['userfile']['tmp_name'];
                $file_type = $_FILES['userfile']['type'];
                if ($file_name != "") {
                    $allowed = array('png', 'PNG', 'jpg', 'JPG');
                    $filename = $_FILES['userfile']['name'];
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if (!in_array($ext, $allowed)) {
                        $this->set_response([
                            'status' => "FAILED",
                            'error_code' => "FILE_NOT_FOUND",
                            'message' => 'Invalid file.'
                        ], REST_Controller::HTTP_OK);
                    } else {
                        $folder = "/home/mybarnite/public_html/user_gallery/";
                        $new_filename = time() . '-' . $file_name;
                        //$new_filename = $file_name;
                        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $folder . $new_filename)) {
                            $path = 'user_gallery/' . $new_filename;

                            $data = array(
                                'user_id' => $id,
                                'file_name' => $new_filename,
                                'file_path' => $path,
                                'status' => 1,
                                'logo_image' => '0'

                            );
                            $this->db->insert('tbl_user_gallery', $data);
                            $insert_id = $this->db->insert_id();

                            if ($insert_id > 0) {
                                $this->set_response([
                                    'status' => "SUCCESS",
                                    'data' => array('file_name' => $new_filename, 'file_path' => 'https://mybarnite.com/' . $path)
                                ], REST_Controller::HTTP_OK);
                            } else {
                                $this->set_response([
                                    'status' => "FAILED",
                                    'error_code' => "NO_CHANGES",
                                    'message' => 'It seems no changes are there.'
                                ], REST_Controller::HTTP_OK);
                            }

                        } else {
                            $this->set_response([
                                'status' => "FAILED",
                                'error_code' => "FILE_NOT_UPLOADED",
                                'message' => 'File uploading error.'
                            ], REST_Controller::HTTP_OK);
                        }
                    }
                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "FILE_NOT_FOUND",
                        'message' => 'Invalid file.'
                    ], REST_Controller::HTTP_OK);
                }
            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "FILE_NOT_FOUND",
                    'message' => 'Invalid file.'
                ], REST_Controller::HTTP_OK);
            }

        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_PARAMETER",
                'message' => 'Invalid parameters.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function deleteUserImage_post()
    {
        $user_id = $this->post('user_id');
        $image_id = $this->post('image_id');

        if ($user_id != "" && $image_id != "") {
            $sql = "select id,file_path as image_path from tbl_user_gallery where user_id=" . $user_id . " AND id=" . $image_id;
            $result = $this->db->query($sql);
            if ($result->row()) {
                $row = $result->row();
                $data = array(
                    'user_id' => $user_id,
                    'id' => $image_id
                );
                $this->db->delete('tbl_user_gallery', $data);
                if (!$this->db->affected_rows()) {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "IMAGE_NOT_FOUND",
                        'message' => 'Invalid image.'
                    ], REST_Controller::HTTP_OK);
                } else {
                    unlink("/home/mybarnite/public_html/" . $row->image_path);
                    $this->set_response([
                        'status' => "SUCCESS",
                        'message' => 'Image deleted successfully.'
                    ], REST_Controller::HTTP_OK);
                }
            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "IMAGE_NOT_FOUND",
                    'message' => 'Invalid image.'
                ], REST_Controller::HTTP_OK);
            }
        } else {
            $this->set_response([
                'status' => "FAILED",
                'error_code' => "INVALID_PARAMETER",
                'message' => 'Invalid parameters.'
            ], REST_Controller::HTTP_OK);
        }
    }

}

?>