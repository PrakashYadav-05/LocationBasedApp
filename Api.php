<?php

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        //  $this->load->library('cors');
        // $this->cors->init(); // Initialize CORS
        date_default_timezone_set('Asia/Calcutta');
        $this->load->model('Api_model');
        $this->load->helper('custom_helper');
        $this->load->library('form_validation');
        $this->load->library('email');
    }

    private function hash_password($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    function uniqid_function()
    {
        var_dump(time());
    }
    function generateRandomString($length = 4)
    {
        $characters = substr(str_shuffle(str_repeat(MD5(microtime()), ceil($length / 32))), 0, $length);
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function imageUpload($image_name, $image_path)
    {
        $f_name = $_FILES[$image_name]['name'];
        $f_tmp = $_FILES[$image_name]['tmp_name'];
        $f_extension = explode('.', $f_name); //To breaks the string into array
        $f_extension = strtolower(end($f_extension)); //end() is used to retrun a last element to the array
        $f_newfile = "";
        if ($f_name) {
            $f_newfile = uniqid() . '.' . $f_extension; // It`s use to stop overriding if the image will be same then uniqid() will generate the unique name
            $store = "$image_path" . $f_newfile;
            $file1 = move_uploaded_file($f_tmp, $store);
        }
        return $f_newfile;
    }

    // 	private function send_otp($mobile, $otp, $name) {
    //         $key = "H7lZolRhDszwfEw4";
    //         $senderid = "EONLNE";
    //         $message_content = urlencode("Dear {#$mobile#} {#$name#} is your OTP {#$otp#} {#QuixiCare#} -AdServs");

    //         $url = "http://sms.adservs.co.in/vb/apikey.php?apikey=$key&senderid=$senderid&number=$mobile&message=$message_content";

    //         $output = file_get_contents($url);

    //         // Return the output for debugging
    //         return $output;
    //     }

    private function send_otp($mobile, $otp, $name)
    {
        $key = "H7lZolRhDszwfEw4";
        $senderid = "iONLNE";
        $message_content = urlencode("Dear $name, $otp is your OTP for logging in to QuixiCare Home Services. -AdServs");

        $url = "http://sms.adservs.co.in/vb/apikey.php?apikey=$key&senderid=$senderid&number=$mobile&message=$message_content";

        $output = file_get_contents($url);

        // Return the output for debugging
        return $output;
    }

    //............................. New Apin  Start ...................//

    public function login_signup()
    {
        $mobile = $this->input->post('mobile');
        $country_code = $this->input->post('country_code');
        $fcm_id = $this->input->post('fcm_id');
        $deviceToken = $this->input->post('deviceToken');

        if (isset($mobile)) {
            // Check if the mobile number already exists in the database
            $existing_user = $this->db
                ->select('id, mobile, otp')
                ->where('mobile', $mobile)
                ->get('users')
                ->row();

            if ($existing_user) {
                // Mobile number already exists, update the OTP
                $otp = rand(1000, 9999);

                $data = [
                    'otp' => $otp,
                    'fcm_id' => $fcm_id,
                    'country_code' => $country_code,
                    'deviceToken' => $deviceToken,
                    'status_login' => '1',
                    'date' => date('d-m-Y'),
                    'time' => date('H:i:s'),
                ];

                $this->db->where('id', $existing_user->id)->update('users', $data);

                // Retrieve updated user data
                $updated_user = $this->db
                    ->select('id,full_name,mobile,otp,fcm_id,date,time,deviceToken,country_code')
                    ->where('id', $existing_user->id)
                    ->get('users')
                    ->row();

                //     if ($updated_user) {
                //     $mobile = $updated_user->mobile;
                //     $otp = $otp;
                //     $name = $updated_user->full_name;

                //     $user_id = $updated_user->id;

                //     $sendotp = $this->send_otp($mobile, $otp, $name);

                // }

                $json['result'] = 'true';
                $json['msg'] = 'User OTP updated';
                $json['data'] = $updated_user;
            } else {
                // Mobile number doesn't exist, insert new user data
                $otp = rand(1000, 9999);

                $data = [
                    'mobile' => $mobile,
                    'country_code' => $country_code,
                    'otp' => $otp,
                    'fcm_id' => $fcm_id,
                    'deviceToken' => $deviceToken,
                    'status_login' => '0',
                    'date' => date('d-m-Y'),
                    'time' => date('H:i:s'),
                ];

                $this->db->insert('users', $data);

                // Retrieve newly registered user data
                $new_user = $this->db
                    ->select('id,full_name,otp,mobile,date,time,deviceToken,country_code')
                    ->where('mobile', $mobile)
                    ->get('users')
                    ->row();

                //      if ($new_user) {
                //     $mobile = $new_user->mobile;
                //     $otp = $otp;
                //     $name = $new_user->full_name;

                //     $user_id = $new_user->id;

                //     $sendotp = $this->send_otp($mobile, $otp, $name);

                // }

                $json['result'] = 'true';
                $json['msg'] = 'User registered successfully';
                $json['data'] = $new_user;
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameters required: mobile,country_code,fcm_id,deviceToken';
        }

        echo json_encode($json);
    }
    
    public function user_save_details()
{
	$user_id  = $this->input->post('user_id');
	$name     = $this->input->post('name');
	$email    = $this->input->post('email');
	
	if($user_id && $name && $email){

		$existing_user = $this->db->select('id')
								   ->where('id', $user_id)
								   ->get('users')
								   ->row();

		if(!$existing_user){
			$json['result'] = 'false';
			$json['msg'] = 'User id Invalid.';
			echo json_encode($json);
			exit;
		}

		$data = array(
			'full_name' => $name,
			'email' => $email,
			'status' => 1,
		);

		$this->db->where('id', $user_id);
		$this->db->update('users', $data);

		$fetched_data = $this->db->where('id', $user_id)
								 ->select('id, full_name, mobile, email,status')
								 ->get('users')
								 ->row();

		if($fetched_data){
			$json['result'] = 'true';
			$json['msg'] = 'User Details Saved Successfully';
			$json['data'] = $fetched_data;
		} else {
		    $this->output->set_status_header(400);
			$json['result'] = 'false';
			$json['msg'] = 'Something went wrong';
		}
	} else {
	    $this->output->set_status_header(400);
		$json['result'] = 'false';
		$json['msg'] = 'Parameters required: user_id, name, email';
	}

	echo json_encode($json);
}

    public function get_address()
    {
        $user_id = $this->input->post('user_id');

        if (isset($user_id) && !empty($user_id)) {
            $user_data = $this->db
                ->select('*')
                ->where('user_id', $user_id)
                ->get('address')
                ->row();

            if ($user_data) {
                $json['result'] = 'true';
                $json['msg'] = 'User Address';
                $json['data'] = $user_data;
            } else {
                $json['result'] = 'false';
                $json['msg'] = 'Invalid user_id or user not found.';
            }
        } else {
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: user_id';
        }

        echo json_encode($json);
    }

    public function get_category()
    {
        $fetched_categories = $this->db
            ->select('id, category_name, category_image, approve')
            ->where('category.approve', '1')
            ->order_by('category.id', 'asc')
            ->get('category')
            ->result();

        if (!empty($fetched_categories)) {
            $response = [
                'result' => 'true',
                'msg' => 'All Categories',
                'path' => base_url() . 'assets/uploads/',
                'data' => $fetched_categories,
            ];
        } else {
            $response = [
                'result' => 'false',
                'msg' => 'No Categories',
            ];
        }

        echo json_encode($response);
    }

    public function get_user_profile()
    {
        $user_id = $this->input->post('user_id');

        if (isset($user_id) && !empty($user_id)) {
            $user_data = $this->db
                ->select('*')
                ->where('id', $user_id)
                ->get('users')
                ->row();

            if ($user_data) {
                $json['result'] = 'true';
                $json['msg'] = 'User Profile';
                $json['path'] = base_url() . 'assets/uploads/';
                $json['data'] = $user_data;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Invalid user_id or user not found.';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: user_id';
        }

        echo json_encode($json);
    }

    public function get_terms_condition()
    {
        $fatch_faq = $this->db
            ->select('*')
            ->get('terms_conditions')
            ->result();
        if ($fatch_faq) {
            $json['result'] = 'true';
            $json['msg'] = 'All Data';
            $json['data'] = $fatch_faq;
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'No Data';
        }

        echo json_encode($json);
    }

    public function get_faq()
    {
        $fatch_faq = $this->db
            ->select('id,question,answer')
            ->get('faq')
            ->result();
        if ($fatch_faq) {
            $json['result'] = 'true';
            $json['msg'] = 'All Data';
            $json['data'] = $fatch_faq;
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'No Data';
        }

        echo json_encode($json);
    }

    public function user_update_profile()
    {
        $user_id = $this->input->post('user_id');
        $full_name = $this->input->post('full_name');
        $email = $this->input->post('email');
        $gender = $this->input->post('gender');

        $postdata = [];
        if (isset($user_id)) {
            if (!empty($full_name)) {
                $postdata['full_name'] = $full_name;
            }

            if (!empty($email)) {
                $postdata['email'] = $email;
            }

            if (!empty($gender)) {
                $postdata['gender'] = $gender;
            }

            if (!empty($_FILES['image']['name'])) {
                $image = $this->imageUpload('image', 'assets/uploads/');

                $postdata['image'] = $image;
            }

            $postdata['date'] = date('Y-m-d');
            $postdata['time'] = date('H:i:s');

            // print_r($postdata); die;

            $this->db->where('users.id', $user_id);
            $this->db->update('users', $postdata);

            $fatch_update = $this->db
                ->select('*')
                ->where('id', $user_id)
                ->get('users')
                ->row();
            if ($fatch_update) {
                $json['result'] = 'true';
                $json['msg'] = 'User Profile Updated Successfullly';
                $json['data'] = $fatch_update;
            } else {
                $json['result'] = 'false';
                $json['msg'] = 'User Id Not Valid';
            }
        } else {
            $json['result'] = 'false';
            $json['msg'] = 'parameter required user_id,optional (full_name,email,gender,image)';
        }

        echo json_encode($json);
    }

    public function get_upcoming_booking()
    {
        $user_id = $this->input->post('user_id');
        $json = [];

        if (isset($user_id) && !empty($user_id)) {
            // Fetch appointment data with correct joins
            $appointment_info = $this->db
                ->select(
                    '
            tbl_appointment.id, 
            tbl_appointment.appoint_ID, 
            tbl_appointment.address_id, 
            tbl_appointment.time_id, 
            tbl_appointment.user_id, 
            tbl_appointment.vendor_id, 
            tbl_appointment.category_id, 
            tbl_appointment.date, 
            tbl_appointment.status, 
            time_slots_hours.time, 
            users.full_name, 
            address.address, 
         
            category.category_name, 
            vendor.service_image AS category_image
        '
                )
                ->from('tbl_appointment')
                ->join('time_slots_hours', 'tbl_appointment.time_id = time_slots_hours.id', 'left')
                ->join('users', 'tbl_appointment.user_id = users.id', 'left')
                ->join('address', 'tbl_appointment.address_id = address.id', 'left')
                ->join('category', 'tbl_appointment.category_id = category.id', 'left')
                ->join('vendor', 'tbl_appointment.vendor_id = vendor.id', 'left')
                ->where('tbl_appointment.user_id', $user_id)
                ->order_by('tbl_appointment.id', 'desc')
                ->get()
                ->result();

            if ($appointment_info) {
                $json['result'] = 'true';
                $json['msg'] = 'Upcoming and In-progress bookings found';
                $json['path'] = base_url() . 'assets/uploads/';
                $json['data'] = $appointment_info;
            } else {
                $this->output->set_status_header(400);
                $json['result'] = 'false';
                $json['msg'] = 'No upcoming or in-progress bookings found for the user';
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: user_id';
        }

        echo json_encode($json);
    }

    public function verify_otp()
    {
        // require 'sendNotification.php';
        $user_id = $this->input->post('user_id');
        $otp = $this->input->post('otp');

        if (isset($user_id) && isset($otp)) {
            $existing_user = $this->db
                ->select('id')
                ->where('id', $user_id)
                ->get('users')
                ->row();

            if (!$existing_user) {
                $json['result'] = 'false';
                $json['msg'] = 'User id Invalid.';
                echo json_encode($json);
                exit();
            }

            $user_otp = $this->db
                ->select('id, otp')
                ->where('id', $user_id)
                ->where('otp', $otp)
                ->get('users')
                ->row();

            if (!$user_otp) {
                $json['result'] = 'false';
                $json['msg'] = 'OTP Invalid.';
                echo json_encode($json);
                exit();
            }

            $data = [
                'verify_otp' => '1',
            ];

            $this->db->where('id', $user_id);
            $this->db->where('otp', $otp);
            $this->db->update('users', $data);

            $fetched_data = $this->db
                ->where('id', $user_id)
                ->select('id,otp,status_login,deviceToken,fcm_id')
                ->get('users')
                ->row();

            // 		if($fetched_data){
            // 			$deviceToken = $fetched_data->deviceToken;
            // 		}

            // 	$title = 'Welcome to Care!';
            // $body = "Thank you for logging in to Care!";

            // if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 	$notification = [
            // 	'title' => $title,
            // 	'body' => $body
            // ];

            // 	$deviceToken = $deviceToken;

            // 	$result = sendNotification($notification, $deviceToken, $messaging);
            // 	// print_r($result); die();
            // 	$datamessage = array(
            // 	'title' => $title,
            // 	'message' => $body,
            // 	'user_id' => $user_id,
            // 	'date' => date('d-m-Y'),
            // 	'time' => date('H:i:s')
            // );

            // $this->db->insert('notifications', $datamessage);

            // }

            if ($fetched_data) {
                $json['result'] = 'true';
                $json['msg'] = 'OTP Verified Successfully';
                $json['data'] = $fetched_data;
            } else {
                $this->output->set_status_header(400);
                $json['result'] = 'false';
                $json['msg'] = 'Something went wrong';
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameters required: user_id,otp';
        }

        echo json_encode($json);
    }

    public function resend_otp()
    {
        $user_id = $this->input->post('user_id');

        if (isset($user_id)) {
            $existing_user = $this->db
                ->select('id')
                ->where('id', $user_id)
                ->get('users')
                ->row();

            if (!$existing_user) {
                $json['result'] = 'false';
                $json['msg'] = 'User id Invalid.';
                echo json_encode($json);
                exit();
            }

            $otp = rand(1000, 9999);

            $data = [
                'otp' => $otp,
            ];

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);

            $fetched_data = $this->db
                ->where('id', $user_id)
                ->select('mobile,otp,full_name as name')
                ->get('users')
                ->row();

            $sendotp = $this->send_otp($fetched_data->mobile, $otp, $fetched_data->name);

            if ($fetched_data) {
                $json['result'] = 'true';
                $json['msg'] = 'Resend OTP Successfully';
                $json['data'] = $fetched_data;
            } else {
                $this->output->set_status_header(400);
                $json['result'] = 'false';
                $json['msg'] = 'Something went wrong';
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: user_id';
        }

        echo json_encode($json);
    }
    
    public function time_list()
	{
		$fatch_time = $this->db->select('*')
		->get('time_slots_hours')
		->result();
		if($fatch_time){
			$json['result'] = 'true';
			$json['msg'] = 'All Time';
			$json['data'] = $fatch_time;
		}else{
			$json['result'] = 'false';
			$json['msg'] = 'No Data';
		}

		echo json_encode($json);                        
	}
	
	public function get_nearby_all_vendor()
{
    $user_lat       =  $this->input->post('user_lat');
    $user_long      =  $this->input->post('user_long');
    $category_id    =  $this->input->post('category_id');


    if (empty($user_lat) || empty($user_long) || empty($category_id)) {
        http_response_code(400);
        $data['result'] = 'false';
        $data['msg'] = 'Please enter all required values: user_lat, user_long, category_id';
    } else {
        $radius = 6371; 

        $this->db->select("v.id, v.name, v.profile_pic, v.gove_id_proof, v.logo, v.service_image, v.category_id, v.about, v.experience, v.lat, v.long, v.open_time, v.close_time,v.rating,v.business_name,v.address,v.mobile, 
         c.id AS category_id, c.category_name, 
         ROUND( 
            ( $radius * acos( cos( radians($user_lat) ) * cos( radians( v.lat ) ) * cos( radians( v.long ) - radians($user_long) ) + sin( radians($user_lat) ) * sin( radians( v.lat ) ) ) ), 
            2 
         ) AS distance");

        
        $this->db->from('vendor v');
        $this->db->join('category c', 'v.category_id = c.id', 'left');

        $this->db->where('v.category_id', $category_id);
        $this->db->having('distance <=', 100); 
        $fatch_vendor = $this->db->get()->result();

        if ($fatch_vendor) {
            $data['result'] = 'true';
            $data['msg'] = 'All Nearby Data Show';
            $data['path'] = base_url() . "assets/uploads";
            $data['vendors'] = $fatch_vendor; 
        } else {
            http_response_code(400);
            $data['result'] = 'false';
            $data['msg'] = 'There are no services available within 100 km of your location.';
        }
    }
    
    echo json_encode($data);
}

public function get_nearby_all_services() {
	$user_lat = $this->input->post('latitude');
	$user_lng = $this->input->post('longitude');

	if (empty($user_lat) || empty($user_lng)) {
		http_response_code(400); 
		$json['result'] = 'false';
		$json['msg'] = 'Parameters required: latitude, longitude';
		echo json_encode($json);
		exit;
	}

	$radius = 100;
	$haversine_formula = "
	ROUND(6371 * acos(
	cos(radians($user_lat)) * cos(radians(v.lat)) * cos(radians(v.long) - radians($user_lng)) + 
	sin(radians($user_lat)) * sin(radians(v.lat))
	), 2) AS distance
	";


	$this->db->select('v.id, v.category_id, v.name, v.lat, v.long, v.mobile, v.address,v.about, v.service_image, v.business_name, v.open_time, v.close_time, ' . $haversine_formula);
	$this->db->from('vendor v');
	$this->db->having('distance <=', $radius); 
	$this->db->order_by('distance', 'ASC');


	$query = $this->db->get();

	if ($query->num_rows() > 0) {
		$json['result'] = 'true';
		$json['msg'] = 'All Nearby Services';
		$json['data'] = $query->result_array(); 
	} else {
		
		http_response_code(400); 
		$json['result'] = 'false';
		$json['msg'] = 'No Nearby Services';
	}

	echo json_encode($json);
}

public function service_details()
{
    $service_id = $this->input->post('service_id');
    $category_id = $this->input->post('category_id');

    if (!empty($service_id) && !empty($category_id)) {

        $service = $this->db->select('
                            vendor.id as service_id,
                            vendor.id as vendor_id,
                            vendor.name as vendor_name,
                            vendor.business_name,
                            vendor.experience,
                            vendor.about,
                            vendor.service_image,
                            vendor.open_time,
                            vendor.close_time,
                            vendor.address,
                            category.id as category_id,
                            category.category_name
                        ')
                    ->from('vendor')
                    ->join('category', 'category.id = vendor.category_id', 'left')
                    ->where('vendor.id', $service_id)
                    ->where('vendor.category_id', $category_id)
                    ->get()
                    ->row();

        if ($service) {
 
            $open_time = new DateTime($service->open_time);
            $close_time = new DateTime($service->close_time);
            $formatted_time = $open_time->format('h:i A') . ' - ' . $close_time->format('h:i A');

            $service->working_hours = $formatted_time;
            unset($service->open_time, $service->close_time);

            $service_image_path = !empty($service->service_image) ? $service->service_image : 'default_image.png';
            $json['path'] = base_url() . "assets/uploads/" . $service_image_path;

            $json['result'] = 'true';
            $json['msg'] = 'Service details fetched successfully.';
            $json['data'] = $service;
        } else {
             http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Invalid Service ID or Category ID.';
        }
    } else {
         http_response_code(400);
        $json['result'] = 'false';
        $json['msg'] = 'Parameters service_id and category_id are required.';
    }

    echo json_encode($json);
}

public function save_address()
{
	$user_id   = $this->input->post('user_id'); 
	$address   = $this->input->post('address'); 
	$street    = $this->input->post('street'); 
	$pin_code  = $this->input->post('pin_code'); 
	$type      = $this->input->post('type');
	$house_no  = $this->input->post('house_no');
	$lat       = $this->input->post('lat');
	$lang      = $this->input->post('lang');
	$city      = $this->input->post('city');
	$state      = $this->input->post('state');

	// Check if user_id is provided
	if(isset($user_id)) {

		// Check if the user exists
		$user = $this->db->where('id', $user_id)
						 ->get('users')
						 ->row();

		if(!$user) {
			$json['result'] = 'false';
			$json['msg'] = 'Invalid user id.';
			echo json_encode($json);
			return; // Stop further execution
		}
		
		
								
		$data = array(
			'user_id' => $user_id,
		);

		// Add optional fields to data array if provided
		if(isset($address)) {
			$data['address'] = $address;
		}

		if(isset($street)) {
			$data['street'] = $street;
		}
 
		if(isset($pin_code)) {
			$data['pin_code'] = $pin_code;
		}
		
		if(isset($house_no)) {
			$data['house_no'] = $house_no;
		}

		if(isset($type)) {
			$data['type'] = $type;
		}

		if(isset($lat)) {
			$data['lat'] = $lat;
		}

		if(isset($lang)) {
			$data['lang'] = $lang;
		}

		if(isset($address_new)) {
			$data['address_new'] = $address_new;
		}

		if(isset($state)) {
			$data['state'] = $state;
		}

		if(isset($city)) {
			$data['city'] = $city;
		}

		// Check if address already exists for the user
		$existing_address = $this->db->where('user_id', $user_id)->get('address')->row();

		if($existing_address) {
			// Update the existing address for the user
			$this->db->where('user_id', $user_id);
			$save_data = $this->db->update('address', $data);
		} else {
			// Insert a new address for the user
			$save_data = $this->db->insert('address', $data);
		}

		if($save_data) {
			$json['result'] = 'true';
			$json['msg'] = 'Address details saved successfully';
		} else {
		    $this->output->set_status_header(400);
			$json['result'] = 'false';
			$json['msg'] = 'Failed to save address details';
		}

	} else {
	    $this->output->set_status_header(400);
		$json['result'] = 'false';
		$json['msg'] = 'parameter is required user_id,optional,(address,street,pin_code,house_no,lat,lang,type,(Home/Other),address_new,city,state)';
	}

	echo json_encode($json);
}

public function book_appointment()
{
     require 'sendNotification.php';
    $user_id     =  $this->input->post('user_id');
    $vendor_id   =  $this->input->post('vendor_id');
    $category_id =  $this->input->post('category_id');
    $address_id  =  $this->input->post('address_id');
    $time_id     =  $this->input->post('time_id');
    $name        =  $this->input->post('name');
    $age         =  $this->input->post('age');
    $diagnosis   =  $this->input->post('diagnosis');
    $gender      =  $this->input->post('gender');
    $description =  $this->input->post('description');
    $date        =  $this->input->post('date');

    if (isset($user_id) && isset($vendor_id) && isset($category_id) && isset($time_id) && isset($name) && isset($age) && isset($diagnosis) && isset($gender) && isset($description) && isset($address_id) && isset($date)) {
      
        $appointment_id = $this->generate_appointment_id();

        $data = array(
            'appoint_ID' => $appointment_id,
            'user_id'        => $user_id,
            'vendor_id'      => $vendor_id,
            'category_id'    => $category_id,
            'time_id'        => $time_id,
            'address_id'        => $address_id,
            'name'           => $name,
            'age'            => $age,
            'diagnosis'      => $diagnosis,
            'gender'         => $gender,
            'description'    => $description,
            'date'           => $date
        );

        $this->db->insert('tbl_appointment', $data);
        
        $last_id = $this->db->insert_id();

        $vendor = $this->db->select('fcm_id')
                           ->where('id', $vendor_id)
                           ->get('vendor')
                           ->row();

        if ($vendor) {
            $deviceToken = $vendor->fcm_id;
// print_r($deviceToken); die();
            $title = 'New Appointment Booked!';
            $body = "Hello! A new appointment has been booked by $name.";

            $notification = [
                'title' => $title,
                'body'  => $body,
            ];

            $result = sendNotification($notification, $deviceToken, $messaging);
// print_r($result); die();
            $datamessage = [
                'title'     => $title,
                'message'   => $body,
                'user_id'   => $user_id,
                'vendor_id' => $vendor_id,
                'date'      => date('Y-m-d'),
                'time'      => date('H:i:s'),
            ];

            $this->db->insert('notifications', $datamessage);
        }

        $json['result'] = 'true';
        $json['msg'] = 'Appointment booked successfully.';
        $json['appointment_id'] = $appointment_id;
    } else {
        http_response_code(400);
        $json['result'] = 'false';
        $json['msg'] = 'All parameters are user_id,vendor_id,category_id,time_id,address_id,age,name,diagnosis,gender,description,date,required.';
    }

    echo json_encode($json);
}

private function generate_appointment_id()
{
    $this->load->helper('string');
    $unique_id = random_string('numeric', 6); 
    return 'DR' . $unique_id;
}
    //...............................................................//
}

?>
