<?php


defined('BASEPATH') OR exit('No direct script access allowed');

require('application/libraries/REST_Controller.php');



class Resource extends REST_Controller  {
	
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
	
	function AddProdcutResource_post()
	{
			$sku = ($this->post('sku'))?$this->post('sku'):'';
			$title = ($this->post('title'))?$this->post('title'):'';
			$description = ($this->post('description'))?$this->post('description'):'';
			
			if(isset($sku))
			{	
					if(isset($_FILES['files']))
					{	
						$c=0;
						$file_name = $_FILES['files']['name'];
						$file_size =$_FILES['files']['size'];
						$file_tmp =$_FILES['files']['tmp_name'];
						$file_type=$_FILES['files']['type'];
						$sql = "select * from tbl_product_specifications where sku = '" . $sku . "'  and status='Active'";
						$query = $this->db->query($sql);
						$numRows=$query->num_rows();
						if($numRows>0)
						{
								$data = array(
									'sku'=>$sku,
									'title'=>$title,
									'description'=>$description,
									'created_at'=>date('Y-m-d'),
									'status'=>'Active'
								);
								$this->db->insert('tbl_product_resources', $data);
								$insert_id = $this->db->insert_id();
								if($insert_id>0)
								{	
									foreach($_FILES['files']['tmp_name'] as $key => $_FILES['files']['tmp_name'] )
									{
										$file_name = $_FILES['files']['name'][$key];
										$file_size =$_FILES['files']['size'][$key];
										$file_tmp =$_FILES['files']['tmp_name'][$key];
										$file_type=$_FILES['files']['type'][$key];	
										$folder="product_resources/";
										$new_filename = date('d-m-Y-H-i-s').'-'.$file_name;
										
										if (move_uploaded_file($_FILES['files']['tmp_name'], $folder.$new_filename)) 
										{
											//$file_name = $new_filename;
											$file_path = $folder.$new_filename;
											
											if($insert_id>0)
											{
												$data = array(
													
													'sku'=>$sku,
													'resource_id'=>$insert_id,
													'file_name'=>$file_name,
													'file_path'=>$file_path,
													'uploaded_date'=>date('Y-m-d'),
													'status'=>'Active'
												);
												$this->db->insert('tbl_resourcefiles', $data);
												$insert_id1 = $this->db->insert_id();
												if($insert_id1>0)
												{	
													$c++;
												}	
											}	
										}
										if($c>0)
										{
											$path ="";
											$this->set_response([
												'status' =>REST_Controller::HTTP_OK,
												'action' =>'Fileuploadsuccess',
												'message' => 'Data has been added successfully.'
											], REST_Controller::HTTP_OK); 
										}
										else
										{
											$path ="";
											$this->set_response([
												'status' =>REST_Controller::HTTP_OK,
												'action' =>'Fileuploaderror',
												'message' => 'Error in file uploading.'
											], REST_Controller::HTTP_OK); 
										}		
										
									}
								}
								else
								{
									$this->set_response([
										'status' =>REST_Controller::HTTP_OK,
										'action' =>'AddDataError',
										'message' => 'There is an error in adding data.'
									], REST_Controller::HTTP_OK); 
								}
						}
						else
						{
							$this->set_response([
								'status' =>REST_Controller::HTTP_OK,
								'action' =>'Notfound',
								'message' => 'Sku not exists.'
							], REST_Controller::HTTP_OK);
						}	
							
					}
					else
					{
							$sql = "select * from tbl_product_specifications where sku = '" . $sku . "'  and status='Active'";
							$query = $this->db->query($sql);
							$numRows=$query->num_rows();
							if($numRows>0)
							{
								$data = array(
									'sku'=>$sku,
									'title'=>$title,
									'description'=>$description,
									'created_at'=>date('Y-m-d'),
									'status'=>'Active'
								);
								$this->db->insert('tbl_product_resources', $data);
								$insert_id = $this->db->insert_id();
								if($insert_id>0)
								{
									$this->set_response([
										'status' =>REST_Controller::HTTP_OK,
										'action' =>'Success',
										'message' => 'Data has been added successfully.'
									], REST_Controller::HTTP_OK); 
								}
								else
								{
									$this->set_response([
										'status' =>REST_Controller::HTTP_OK,
										'action' =>'Error',
										'message' => 'Error in inserting data.'
									], REST_Controller::HTTP_OK); 
								}	
							}
							else
							{
								$this->set_response([
									'status' =>REST_Controller::HTTP_OK,
									'action' =>'Notfound',
									'message' => 'Sku not exists.'
								], REST_Controller::HTTP_OK);
							}		
									
					}
						
			
			}
			
	}
	
	function UpdateProdcutResource_post()
	{
			$id = $this->post('id'); 
			$sku = ($this->post('sku'))?$this->post('sku'):'';
			$title = ($this->post('title'))?$this->post('title'):'';
			$description = ($this->post('description'))?$this->post('description'):'';
			
			if(isset($sku))
			{	
					
					if(isset($_FILES['files']))
					{
						$c=0;
						$file_name = $_FILES['files']['name'];
						$file_size =$_FILES['files']['size'];
						$file_tmp =$_FILES['files']['tmp_name'];
						$file_type=$_FILES['files']['type'];
						
						$data = array(
							'sku'=>$sku,
							'title'=>$title,
							'description'=>$description,
							'created_at'=>date('Y-m-d'),
							'status'=>'Active'
						);
						$this->db->where('id', $id);
						$this->db->update('tbl_product_resources', $data);
						$insert_id = $this->db->affected_rows();
						/* if($insert_id>0)
						{ */
							/* $sql = "select * from tbl_resourcefiles where resource_id =".$id." and status = 'Active'";
							$query = $this->db->query($sql);
							$numRows=$query->num_rows();
							if ($numRows > 0) //active user record is present
							{
								$data = array(
									'status'=>'Inactive'
								);
								$this->db->where('sku', $sku);
								$this->db->update('tbl_resourcefiles', $data);
							}	 */
							
							foreach($_FILES['files']['tmp_name'] as $key => $_FILES['files']['tmp_name'] )
							{
								$file_name = $_FILES['files']['name'][$key];
								$file_size =$_FILES['files']['size'][$key];
								$file_tmp =$_FILES['files']['tmp_name'][$key];
								$file_type=$_FILES['files']['type'][$key];	
								$folder="product_resources/";
								$new_filename = date('d-m-Y-H-i-s').'-'.$file_name;
								
								if (move_uploaded_file($_FILES['files']['tmp_name'], $folder.$new_filename)) 
								{
									//$file_name = $new_filename;
									$file_path = $folder.$new_filename;
									
									/* if($insert_id>0)
									{ */
										$data = array(
											
											'sku'=>$sku,
											'resource_id'=>$id,
											'file_name'=>$file_name,
											'file_path'=>$file_path,
											'uploaded_date'=>date('Y-m-d'),
											'status'=>'Active'
										);
										$this->db->insert('tbl_resourcefiles', $data);
										$insert_id1 = $this->db->insert_id();
										if($insert_id1>0)
										{	
											$c++;
										}	
									/* } */	
								}
								if($c>0)
								{
									$path ="";
									$this->set_response([
										'status' =>REST_Controller::HTTP_OK,
										'action' =>'Fileuploadsuccess',
										'message' => 'Data has been added successfully.'
									], REST_Controller::HTTP_OK); 
								}
								else
								{
									$path ="";
									$this->set_response([
										'status' =>REST_Controller::HTTP_OK,
										'action' =>'Fileuploaderror',
										'message' => 'Error in file uploading.'
									], REST_Controller::HTTP_OK); 
								}		
								
							}
								
								
							
						/* }
						else
						{
							$this->set_response([
								'status' =>REST_Controller::HTTP_OK,
								'action' =>'Nochanges',
								'message' => 'It seems no changes are there.'
							], REST_Controller::HTTP_OK); 
						} */
					}
					else
					{
						$data = array(
							'sku'=>$sku,
							'title'=>$title,
							'description'=>$description,
							'created_at'=>date('Y-m-d'),
							'status'=>'Active'
						);
						$this->db->where('sku', $sku);
						$this->db->update('tbl_product_resources', $data);
						$insert_id = $this->db->affected_rows();
						if($insert_id>0)
						{
							$this->set_response([
								'status' =>REST_Controller::HTTP_OK,
								'action' =>'Success',
								'message' => 'Data has been updated successfully.'
							], REST_Controller::HTTP_OK); 
						}
						else
						{
							$this->set_response([
								'status' =>REST_Controller::HTTP_OK,
								'action' =>'Nochanges',
								'message' => 'It seems no changes are there.'
							], REST_Controller::HTTP_OK); 
						}	
					}		
							

			
			}
			
	}
	
	function deleteResourceFiles_get($d_id=null)
	{
		if($d_id!="")
		{
			$this->db->delete('tbl_resourcefiles', array('id' => $d_id));
			$j = $this->db->affected_rows();
			if($i>0)
			{
				$this->set_response([
					'status' =>REST_Controller::HTTP_OK,
					'action' => 'success',
					'message' => 'Data has been deleted successfully'
				], REST_Controller::HTTP_OK); 
			}
		}
		else
		{
			$this->set_response([
					'status' =>REST_Controller::HTTP_OK,
					'message' => 'Data not found'
				], REST_Controller::HTTP_OK); 
		}	
	}
	
	function deleteResource_get($d_id=null)
	{	if($d_id!="")
		{	
			$data = array(
			   'status' => 'Inactive'
			);

			$this->db->where('id', $d_id);
			$this->db->update('tbl_product_resources', $data);
			$i = $this->db->affected_rows();
			if($i>0)
			{
				$data = array(
				   'status' => 'Inactive'
				);

				$this->db->where('resource_id', $d_id);
				$this->db->update('tbl_resourcefiles', $data);
				$j = $this->db->affected_rows();
				if($i>0)
				{
					$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'action' => 'success',
						'message' => 'Data has been deleted successfully'
					], REST_Controller::HTTP_OK); 
				}
				else
				{
					$this->set_response([
							'status' =>REST_Controller::HTTP_OK,
							'action' => 'Nochanges',
							'message' => 'It seems no changes are there.'
						], REST_Controller::HTTP_OK); 
				}	
			}
			else
			{
				$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'message' => 'It seems no changes are there.'
					], REST_Controller::HTTP_OK); 
			}	
			
		}
		else		
		{
				$this->set_response([
					'status' =>REST_Controller::HTTP_OK,
					'message' => 'Data not found'
				], REST_Controller::HTTP_OK); 
		}	
		
	}
	
	function deleteMultiple_post()
	{
		$ids = $this->post('ids');//offset
		$arr = explode( ';' , $ids );

		$c = 0;	
		foreach ( $arr as $id ){
			$data = array(
			   'status' => 'Inactive'
			);
			$this->db->where('id', $id);
			$this->db->update('tbl_product_resources', $data);
			$i = $this->db->affected_rows();
			if($i>0)
			{
				$data = array(
				   'status' => 'Inactive'
				);
				$this->db->where('resource_id', $id);
				$this->db->update('tbl_resourcefiles', $data);
				$j = $this->db->affected_rows();
				if($j>0)
				{	
					$c++;
				}
				$c++;
			}	
		}
		
		
		if($c>0)
		{
			
			$this->set_response([
				'status' =>REST_Controller::HTTP_OK,
				'action' => 'success',
				'message' => 'Data has been deleted successfully'
			], REST_Controller::HTTP_OK); 
		}
		else
		{
			$this->set_response([
					'status' =>REST_Controller::HTTP_OK,
					'message' => 'It seems no changes are there.'
				], REST_Controller::HTTP_OK); 
		}	
		
	}
	
	
	function getAllResources_post()
	{
		$offset = $this->post('iDisplayStart');//offset
		$limit = $this->post('iDisplayLength');
		$search = $this->post('sSearch');
		$sql = "select * from tbl_product_resources where status='Active'";
		$query = $this->db->query($sql);
		$cnt = $query->num_rows();
		
		$iSortingCols = $this->post('iSortingCols');
		$sSortDir_0 = $this->post('sSortDir_0');
		$iSortCol_0 = $this->post('iSortCol_0');
		
		if($iSortingCols==1)
		{
			if ($sSortDir_0 == "asc" && $iSortCol_0 == 1)
			{
				$orderBy = "order by sku asc";
			}
			if ($sSortDir_0 == "desc" && $iSortCol_0 == 1)
			{
				$orderBy = "order by sku desc";
			}
			if ($sSortDir_0 == "asc" && $iSortCol_0 == 2)
			{
				$orderBy = "order by title asc";
			}
			if ($sSortDir_0 == "desc" && $iSortCol_0 == 2)
			{
				$orderBy = "order by title desc";
			}	
			 
		}
		else
		{
			
			$orderBy = "order by id DESC";
		}	
		
		if($search!="")
		{
			   // Space not found.
			$sql = "select * from tbl_product_resources where (sku='".$search."' or title like '%".$search."%' ) and status = 'Active' ".$orderBy." LIMIT ".$limit." OFFSET ".$offset;
			$query = $this->db->query($sql);
			$numRows=$query->num_rows();

			if ($numRows > 0) //active user record is present
			{	
					foreach ($query->result() as $row)
					{
						$row->created_at = date('d-m-Y',strtotime($row->created_at));
						$arr[]=$row;	
					}
					
					 $this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'iTotalRecords'=>$cnt,
						'iTotalDisplayRecords'=>$cnt,
						'aaData' => $arr
					], REST_Controller::HTTP_OK);  
					
			}	
			else
			{
				$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data'	=> '',
						'iTotalRecords'=>$cnt,
						'iTotalDisplayRecords'=>$cnt,
						'message' => 'Product resources not found'
					], REST_Controller::HTTP_OK); 
				
			}
		}
		else
		{
			$sql = "select * from tbl_product_resources where status = 'Active' ".$orderBy." LIMIT ".$limit." OFFSET ".$offset;
			$query = $this->db->query($sql);
			$numRows=$query->num_rows();

			if ($numRows > 0) //active user record is present
			{	
					foreach ($query->result() as $row)
					{
						$row->created_at = date('d-m-Y',strtotime($row->created_at));
						$arr[]=$row;	
					}
					
					 $this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'iTotalRecords'=>$cnt,
						'iTotalDisplayRecords'=>$cnt,
						'aaData' => $arr
					], REST_Controller::HTTP_OK);  
					
			}	
			else
			{
				$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data'	=> '',
						'iTotalRecords'=>$cnt,
						'iTotalDisplayRecords'=>$cnt,
						'message' => 'Product resources not found'
					], REST_Controller::HTTP_OK); 
				
			}
		}	
	}
	
	function getResourceFiles_get($e_id=null)
	{
		if($e_id!="")
		{
			$sql = "select * from tbl_resourcefiles where resource_id =".$e_id." and status = 'Active' order by id DESC";
			$query = $this->db->query($sql);
			$numRows=$query->num_rows();
			if ($numRows > 0) //active user record is present
			{	
					foreach ($query->result() as $row)
					{
						
						$arr[]=$row;	
					}
					
					 $this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data' => $arr
					], REST_Controller::HTTP_OK);  
					
			}	
			else
			{
				$this->set_response([
						'status' =>REST_Controller::HTTP_OK,
						'data'	=> '',
						'message' => 'Product resources not found'
					], REST_Controller::HTTP_OK); 
				
			}
		}	
	}
	
	function getResource_get($e_id=null) 
	{
		
		if($e_id!="")
		{
			$row = $this->db->get_where('tbl_product_resources', array('id' => $e_id))->row();
			//$row = $this->db->select('*')->from('tbl_product_resources')->join('tbl_resourcefiles', 'tbl_product_resources.id = tbl_resourcefiles.resource_id  and tbl_resourcefiles.status="Active"','left')->where(array('tbl_product_resources.id' => $e_id,'tbl_product_resources.status' => 'Active'))->get()->row();

			if (count($row) > 0) //active user record is present
			{	
					$row->created_at = date('d-m-Y',strtotime($row->created_at));
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
						'message' => 'Product resources not found.'
					], REST_Controller::HTTP_OK); 
				
			}
			
		}	
		
	}
	
}
?>	