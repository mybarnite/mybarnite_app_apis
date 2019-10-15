<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/libraries/REST_Controller.php');


class Bar extends REST_Controller
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

    function list_post()
    {


        try {
            $offset = $this->post('offset');
            $limit = $this->post('limit');
            $latitude = $this->post('latitude');
            $longitude = $this->post('longitude');
            if ($latitude == "" && $longitude == "") {
                $query_str = 'SELECT id,Business_Name AS business_name, Category AS category, PhoneNo AS phone_no ,Rating AS rating FROM bars_list WHERE Owner_id != 0 AND Business_Name != "" ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

            } else {
                $query_str = 'SELECT id,Business_Name AS business_name, Category AS category, PhoneNo AS phone_no ,Rating AS rating,
							( 6371 * acos(
							cos(radians(' . $latitude . ')) * cos(radians(Latitude)) * cos(radians(Longitude) - radians(' . $longitude . ')) +
							sin(radians(' . $latitude . ')) * sin(radians(Latitude))
							) ) AS distance
						FROM bars_list WHERE Owner_id != 0 AND Business_Name != ""
						HAVING distance < 100
						ORDER BY distance LIMIT ' . $limit . ' OFFSET ' . $offset;

            }
            $result = $this->db->query($query_str);
            $num_rows = $result->num_rows();


            if (!$result) {

                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
            } else if ($num_rows < 0) {

                $this->set_response([
                    'status' => 'FAILED',
                    'error_code' => "SERVER_ERROR",
                    'message' => "Bad Request."
                ], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                if ($num_rows > 0) //active user record is present
                {
                    foreach ($result->result() as $row) {
                        $sql = "select file_path,file_name,logo_image from tbl_bar_gallary where bar_id=" . $row->id . " and logo_image='1'";
                        $v_reg = $this->db->query($sql);
                        $res = $v_reg->row_array();

                        /* $row->id = $row->id;
                        $row->business_name = $row->Business_Name;
                        $row->category = $row->Category;
                        $row->phone_no = $row->PhoneNo;
                        $row->rating = $row->Rating; */


                        if ($res['file_path'] != "") {
                            $row->logo_image_url = "https://mybarnite.com/business_owner/" . $res['file_path'];
                        } else {
                            $row->logo_image_url = "https://mybarnite.com/images/no_image.png";
                        }

                        $arr[] = $row;

                    }

                    $this->set_response([
                        'status' => 'SUCCESS',
                        'data' => $arr
                    ], REST_Controller::HTTP_OK);

                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "BAR_NOT_FOUND",
                        'message' => 'Bar not found'
                    ], REST_Controller::HTTP_OK);
                }

            }

        } catch (Exception $e) {
            return;
        }
    }

    function getDetails_post()
    {
        $id = $this->post('id');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');

        if ($id != "") {
            $sql = "select b.id,b.Business_Name AS business_name,b.Owner_Name AS owner_name,b.Owner_id as owner_id,b.Category AS category,b.Address AS addresss,b.Locality AS locality,b.Region AS region,b.Zipcode as zipcode,b.PhoneNo AS phone_no,b.description,b.Hours AS hours,b.Price_Range AS price_range,b.Rating AS rating ,b.Latitude AS latitude,b.Longitude AS longitude,b.is_hall_available as is_hall_available,b.seat_for_basic as total_number_of_seat,b.hall_fee as hall_fee,b.hall_capacity as hall_capacity,b.cost_per_seat as cost_per_seat,g.file_path from bars_list as b left join tbl_bar_gallary as g on b.id = g.bar_id and g.logo_image = '1' where b.id = " . $id;
            $query = $this->db->query($sql);
            $num_rows = $query->num_rows();

            if (!$query) {

                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "SERVER_ERROR",
                    'message' => 'There is some error.'
                ], REST_Controller::HTTP_OK);
            } else {
                if ($num_rows > 0) {

                    foreach ($query->result() as $row) {
                        $res = $query->row_array();

                        $point1 = array("lat" => $latitude, "long" => $longitude);
                        $point2 = array("lat" => $res['latitude'], "long" => $res['longitude']);
                        $mi = $this->distanceCalculation(floatval($point1['lat']), floatval($point1['long']), floatval($point2['lat']), floatval($point2['long']), 'mi') . ' Miles';
                        $owner_id = $res['owner_id'];
                        $available = false;
                        $sql1 = "select status from user_register where r_id = 1 and id = " . $owner_id;
                        $query1 = $this->db->query($sql1);
                        $num_rows = $query1->num_rows();
                        if ($num_rows > 0) {
                            $row1 = $query1->row_array();
                            if ($row1['status'] === 'Active') {
                                $available = true;
                            }
                        }

                        if ($res['file_path'] != "") {
                            $row->logo_image_url = "https://mybarnite.com/business_owner/" . $res['file_path'];
                        } else {
                            $row->logo_image_url = "https://mybarnite.com/images/no_image.png";
                        }
                        $row->distance = $mi;
                        $row->latitude = $res['latitude'];
                        $row->longitude = $res['longitude'];
                        $row->available = $available;
                        $menuDetails = $this->getMenuDetails($id);
                        $row->menu = $menuDetails;
                        $row->is_hall_available = ($row->is_hall_available == 1) ? "Available for renting" : "Not available for renting";
                        $row->hall_capacity = $row->hall_capacity . " people";
                        $arr[] = $row;

                    }
                    $this->set_response([
                        'status' => "SUCCESS",
                        'data' => $row
                    ], REST_Controller::HTTP_OK);
                } else {
                    $this->set_response([
                        'status' => "FAILED",
                        'error_code' => "BAR_NOT_FOUND",
                        'message' => 'Bar not found'
                    ], REST_Controller::HTTP_OK);

                }
            }


        }

    }

    function getMenuDetails($barId)
    {
        $sql = "select id as menu_id,file_path as menu_file_path from  tbl_barfoodmenu_uploads where bar_id = " . $barId;
        $result = $this->db->query($sql);

        if (!$result) {
            throw new Exception();
        }

        $num_rows = $result->num_rows();
        if ($num_rows > 0) {
            foreach ($result->result() as $row) {
                $row->menu_file_path = "https://mybarnite.com/business_owner/" . $row->menu_file_path;
                $arr[] = $row;
            }
            return $arr;
        }
        return null;
    }

    function searchList_post()
    {
        $offset = $this->post('offset');
        $limit = $this->post('limit');
        $barName = $this->post('bar_name');
        $barPostcode = $this->post('bar_postcode');
        $sql = "SELECT id,Business_Name AS business_name, Category AS category, PhoneNo AS phone_no ,Rating AS rating FROM bars_list";

        if ($barName != "" && $barPostcode == "") {
            $sql .= " where Business_Name like '%" . $barName . "%'";
        }

        if ($barPostcode != "" && $barName == "") {
            $sql .= " where Zipcode like '" . $barPostcode . "%'";
        }

        if ($barPostcode != "" && $barName != "") {
            $sql .= " where Business_Name like '%" . $barName . "%' and Zipcode like '" . $barPostcode . "%'";
        }
        $limitForList = " LIMIT " . $limit . " OFFSET " . $offset;
        $sql = $sql . $limitForList;

        $query = $this->db->query($sql);
        $num_rows = $query->num_rows();

        if (!$query) {

            $this->set_response([
                'status' => "FAILED",
                'error_code' => "SERVER_ERROR",
                'message' => 'There is some error.'
            ], REST_Controller::HTTP_OK);
        } else {
            if ($num_rows > 0) {

                foreach ($query->result() as $row) {
                    $sql1 = "select file_path,file_name,logo_image from tbl_bar_gallary where bar_id=" . $row->id . " and logo_image='1'";
                    $getLogo = $this->db->query($sql1);
                    $res = $getLogo->row_array();

                    if ($res['file_path'] != "") {
                        $row->logo_image_url = "https://mybarnite.com/business_owner/" . $res['file_path'];
                    } else {
                        $row->logo_image_url = "https://mybarnite.com/images/no_image.png";
                    }

                    $arr[] = $row;

                }
                $this->set_response([
                    'status' => "SUCCESS",
                    'data' => $arr
                ], REST_Controller::HTTP_OK);
            } else {
                $this->set_response([
                    'status' => "FAILED",
                    'error_code' => "BAR_NOT_FOUND",
                    'message' => 'Bar not found'
                ], REST_Controller::HTTP_OK);

            }
        }
    }

    function gallery_post()
    {
        $id = $this->post('barId');

        if ($id != "") {
            $sql = "select id,bar_id,file_path as image_path from tbl_bar_gallary where bar_id=" . $id . " order by id";
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
                    $row->image_path = "https://mybarnite.com/business_owner/" . $row->image_path;
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

    function distanceCalculation($point1_lat, $point1_long, $point2_lat, $point2_long, $unit = 'km', $decimals = 2)
    {
        // Calculate the distance in degrees
        $degrees = rad2deg(acos((sin(deg2rad($point1_lat)) * sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat)) * cos(deg2rad($point2_lat)) * cos(deg2rad($point1_long - $point2_long)))));

        // Convert the distance in degrees to the chosen unit (kilometres, miles or nautical miles)
        switch ($unit) {
            case 'km':
                $distance = $degrees * 111.13384; // 1 degree = 111.13384 km, based on the average diameter of the Earth (12,735 km)
                break;
            case 'mi':
                $distance = $degrees * 69.05482; // 1 degree = 69.05482 miles, based on the average diameter of the Earth (7,913.1 miles)
                break;
            case 'nmi':
                $distance = $degrees * 59.97662; // 1 degree = 59.97662 nautic miles, based on the average diameter of the Earth (6,876.3 nautical miles)
        }
        return round($distance, $decimals);
    }

    function addBarImage_post()
    {
        $id = $this->post('bar_id');

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
                        $folder = "/home/mybarnite/public_html/business_owner/uploaded_files/";
                        $new_filename = time() . '-' . $file_name;
                        //$new_filename = $file_name;
                        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $folder . $new_filename)) {
                            $path = 'uploaded_files/' . $new_filename;

                            $data = array(
                                'bar_id' => $id,
                                'file_name' => $new_filename,
                                'file_path' => $path,
                                'status' => 1,
                                'logo_image' => '0'

                            );
                            $this->db->insert('tbl_bar_gallary', $data);
                            $insert_id = $this->db->insert_id();

                            if ($insert_id > 0) {
                                $this->set_response([
                                    'status' => "SUCCESS",
                                    'data' => array('file_name' => $new_filename, 'file_path' => 'https://mybarnite.com/business-owner/' . $path)
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
}

?>