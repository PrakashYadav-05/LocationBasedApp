<?php

// header("Access-Control-Allow-Origin: *");

// header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}
// Handle your API logic here

// Send a response
// echo json_encode(["message" => "API response"]);

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Vendor_api extends CI_Controller
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
    }

    private function hash_password($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
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

    //  private function send_otp($mobile, $otp, $name) {
    //      $key = "H7lZolRhDszwfEw4";
    //      $senderid = "EONLNE";
    //      $message_content = urlencode("Dear {#$mobile#} {#$name#} is your OTP {#$otp#} {#QuixiCare#} -AdServs");

    //      $url = "http://sms.adservs.co.in/vb/apikey.php?apikey=$key&senderid=$senderid&number=$mobile&message=$message_content";

    //      $output = file_get_contents($url);

    //      // Return the output for debugging
    //      return $output;
    //     }

    private function send_otp($mobile, $otp, $name)
    {
        $key = "H7lZolRhDszwfEw4";
        $senderid = "iONLNE";
        $message_content = urlencode("Dear $name, $otp is your OTP for logging in to QuixiCare Home Services Partner. -AdServs");

        $url = "http://sms.adservs.co.in/vb/apikey.php?apikey=$key&senderid=$senderid&number=$mobile&message=$message_content";

        $output = file_get_contents($url);

        // Return the output for debugging
        return $output;
    }

    public function vendor_signup()
    {
        $name = $this->input->post('name');
        $mobile = $this->input->post('mobile');
        $email_id = $this->input->post('email_id');
        $business_name = $this->input->post('business_name');
        $category_id = $this->input->post('category_id');
        $open_time = $this->input->post('open_time');
        $close_time = $this->input->post('close_time');
        $lat = $this->input->post('lat');
        $long = $this->input->post('long');
        $address = $this->input->post('address');
        $about = $this->input->post('about');
        $experience = $this->input->post('experience');
        $fcm_id = $this->input->post('fcm_id');

        if ($name && $mobile && $email_id && $lat && $long && $address && $business_name && $category_id && $open_time && $close_time && $about && $experience && $fcm_id) {
            $existing_mobile = $this->db
                ->select('id, mobile')
                ->where('mobile', $mobile)
                ->get('vendor')
                ->row();

            if ($existing_mobile) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Mobile Number already exists. Please try another number.';
                echo json_encode($json);
                exit();
            }

            $existing_email = $this->db
                ->select('id, email_id')
                ->where('email_id', $email_id)
                ->get('vendor')
                ->row();

            if ($existing_email) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Email already exists. Please try another email.';
                echo json_encode($json);
                exit();
            }

            $profile_pic = $this->upload_and_resize_image('profile_pic');
            $gove_id_proof = $this->upload_and_resize_image('gove_id_proof');
            $logo = $this->upload_and_resize_image('logo');
            $service_image = $this->upload_and_resize_image('service_image');

            $data = [
                'name' => $name,
                'mobile' => $mobile,
                'email_id' => $email_id,
                'business_name' => $business_name,
                'category_id' => $category_id,
                'open_time' => $open_time,
                'close_time' => $close_time,
                'about' => $about,
                'lat' => $lat,
                'long' => $long,
                'address' => $address,
                'experience' => $experience,
                'fcm_id' => $fcm_id,
                'profile_pic' => $profile_pic,
                'gove_id_proof' => $gove_id_proof,
                'logo' => $logo,
                'service_image' => $service_image,
                'page_status' => '1',
            ];

            $this->db->insert('vendor', $data);
            $last_id = $this->db->insert_id();

            $fetched_vendor = $this->db
                ->where('id', $last_id)
                ->select('*')
                ->get('vendor')
                ->row();

            if ($fetched_vendor) {
                $json['result'] = 'true';
                $json['msg'] = 'Vendor Registration Successful';
                $json['data'] = $fetched_vendor;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Something went wrong';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameters required: name, mobile, email_id, business_name, category_id, open_time, close_time, about, lat, long, address,experience, fcm_id, service_image,profile_pic, gove_id_proof, logo';
        }

        echo json_encode($json);
    }

    private function upload_and_resize_image($field_name)
    {
        $config['upload_path'] = './assets/uploads/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($field_name)) {
            return '';
        } else {
            $this->load->library('image_lib');
            $image_data = $this->upload->data();

            $config_resize = [
                'image_library' => 'gd2',
                'source_image' => $image_data['full_path'],
                'maintain_ratio' => true,
                'quality' => '50%',
                'width' => 2000,
                'height' => $image_data['file_size'] <= 2400 ? null : 2500,
                'master_dim' => 'width',
            ];

            $this->image_lib->clear();
            $this->image_lib->initialize($config_resize);

            if ($this->image_lib->resize()) {
                return $image_data['file_name'];
            } else {
                return '';
            }
        }
    }

    private function upload_and_resize($field_name)
    {
        $config = [
            'upload_path' => './assets/uploads/',
            'allowed_types' => 'gif|jpg|png|jpeg',
        ];

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($field_name)) {
            return '';
        }

        $this->load->library('image_lib');
        $image_data = $this->upload->data();

        $config_resize = [
            'image_library' => 'gd2',
            'source_image' => $image_data['full_path'],
            'maintain_ratio' => true,
            'master_dim' => 'width',
            'quality' => '50%',
            'width' => 2000,
            'height' => $image_data['file_size'] <= 2400 ? null : 2500,
        ];

        $this->image_lib->clear();
        $this->image_lib->initialize($config_resize);

        return $this->image_lib->resize() ? $image_data['file_name'] : '';
    }

    public function contact_us()
    {
        $fatch_faq = $this->db
            ->select('*')
            ->get('customer_service')
            ->result();
        if ($fatch_faq) {
            $json['result'] = 'true';
            $json['msg'] = 'All Data';
            $json['data'] = $fatch_faq;
        } else {
            $json['result'] = 'false';
            $json['msg'] = 'No Data';
        }

        echo json_encode($json);
    }

    public function vendor_profile()
    {
        $vendor_id = $this->input->post('vendor_id');

        if (isset($vendor_id)) {
            if (empty($vendor_id)) {
                $json['result'] = 'false';
                $json['msg'] = 'Vendor Id cannot be empty';
                echo json_encode($json);
                exit();
            }

            $fetched_data = $this->db
                ->where('id', $vendor_id)
                ->select('*')
                ->get('vendor')
                ->row();

            if (!$fetched_data) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Invalid Vendor Id';
                echo json_encode($json);
                exit();
            }

            $fetched_vendor = $this->db
                ->select('id, name, email_id, address, mobile, about,open_time,close_time,experience,business_name,profile_pic, gove_id_proof,logo,service_image')
                ->where('id', $vendor_id)
                ->get('vendor')
                ->row();

            if ($fetched_vendor) {
                $json['result'] = 'true';
                $json['msg'] = 'Vendor Profile';
                $json['path'] = base_url() . 'assets/uploads/';
                $json['data'] = $fetched_vendor;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'No Vendor Profile';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: vendor_id';
        }

        echo json_encode($json);
    }

    public function vendor_privacy_policy()
    {
        $type = $this->input->post('type');
        if (isset($type)) {
            $fatch_faq = $this->db
                ->select('*')
                ->where('type', 'Vendors')
                ->get('privacy')
                ->result();
            if ($fatch_faq) {
                $json['result'] = 'true';
                $json['msg'] = 'All Data';
                $json['data'] = $fatch_faq;
            } else {
                $this->output->set_status_header(400);
                $json['result'] = 'false';
                $json['msg'] = 'No Data';
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'perameter required type';
        }

        echo json_encode($json);
    }

    public function vendor_terms_condition()
    {
        $type = $this->input->post('type');
        if (isset($type)) {
            $fatch_faq = $this->db
                ->select('*')
                ->where('type', 'Vendors')
                ->get('terms_conditions')
                ->result();
            if ($fatch_faq) {
                $json['result'] = 'true';
                $json['msg'] = 'All Data';
                $json['data'] = $fatch_faq;
            } else {
                $this->output->set_status_header(400);
                $json['result'] = 'false';
                $json['msg'] = 'No Data';
            }
        } else {
            $this->output->set_status_header(400);
            $json['result'] = 'false';
            $json['msg'] = 'perameter required type';
        }

        echo json_encode($json);
    }

    public function vendor_login()
    {
        $mobile = $this->input->post('mobile');
        $fcm_id = $this->input->post('fcm_id');

        if (isset($mobile)) {
            $existing_user = $this->db
                ->select('id, mobile')
                ->where('mobile', $mobile)
                ->get('vendor')
                ->row();

            if (!$existing_user) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Invalid mobile number';
                echo json_encode($json);
                exit();
            }

            $existing_user = $this->db
                ->select('id, is_kyc_approved')
                ->where('mobile', $mobile)
                ->where('is_kyc_approved', '1')
                ->get('vendor')
                ->row();

            if (!$existing_user) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'KYC not approved by admin';
                echo json_encode($json);
                exit();
            }

            $otp = rand(100000, 999999);

            $data = [
                'otp' => $otp,
            ];

            if (!empty($fcm_id)) {
                $data['fcm_id'] = $fcm_id;
            }

            $this->db->where('mobile', $mobile);
            $this->db->update('vendor', $data);

            $new_user = $this->db
                ->select('id, mobile,name, otp,fcm_id,category_id as category_id')
                ->where('mobile', $mobile)
                ->get('vendor')
                ->row();

            //      if ($new_user) {
            //          $mobile = $new_user->mobile;
            //          $otp = $otp;
            //          $name = $new_user->name;

            //          $sendotp = $this->send_otp($mobile, $otp, $name);
            //       // print_r($sendotp); die;
            //      }

            $json['result'] = 'true';
            $json['msg'] = 'Vendor login successful';
            $json['data'] = $new_user;
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: mobile optional( fcm_id )';
        }

        echo json_encode($json);
    }

    public function get_upcoming_booking()
    {
        $vendor_id = $this->input->post('vendor_id');
        $json = [];

        if (isset($vendor_id) && !empty($vendor_id)) {
            $appointment_info = $this->db
                ->select(
                    '
            tbl_appointment.id, 
            tbl_appointment.appoint_ID, 
            tbl_appointment.address_id, 
            tbl_appointment.time_id, 
            tbl_appointment.user_id, 
            tbl_appointment.vendor_id, 
            tbl_appointment.vendor_id, 
            tbl_appointment.category_id, 
            tbl_appointment.date, 
            tbl_appointment.status, 
            time_slots_hours.time, 
            users.full_name, 
            users.mobile, 
            address.address, 
         
            category.category_name, 
            vendor.service_image AS category_image
        '
                )
                ->from('tbl_appointment')
                ->join('time_slots_hours', 'tbl_appointment.time_id = time_slots_hours.id', 'left')
                ->join('users', 'tbl_appointment.vendor_id = users.id', 'left')
                ->join('address', 'tbl_appointment.address_id = address.id', 'left')
                ->join('category', 'tbl_appointment.category_id = category.id', 'left')
                ->join('vendor', 'tbl_appointment.vendor_id = vendor.id', 'left')
                ->where('tbl_appointment.vendor_id', $vendor_id)
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
            $json['msg'] = 'Parameter required: vendor_id';
        }

        echo json_encode($json);
    }

    public function vendor_update_profile()
    {
        $vendor_id = $this->input->post('vendor_id');
        $name = $this->input->post('name');
        $email_id = $this->input->post('email_id');
        $address = $this->input->post('address');
        $mobile = $this->input->post('mobile');
        $business_name = $this->input->post('business_name');
        $about = $this->input->post('about');
        $experience = $this->input->post('experience');

        $postdata = [];

        if (isset($vendor_id)) {
            if (!empty($name)) {
                $postdata['name'] = $name;
            }

            if (!empty($email_id)) {
                $postdata['email_id'] = $email_id;
            }

            if (!empty($address)) {
                $postdata['address'] = $address;
            }

            if (!empty($mobile)) {
                $postdata['mobile'] = $mobile;
            }

            if (!empty($business_name)) {
                $postdata['business_name'] = $business_name;
            }

            if (!empty($about)) {
                $postdata['about'] = $about;
            }

            if (!empty($experience)) {
                $postdata['experience'] = $experience;
            }

            if (!empty($_FILES['profile_pic']['name'])) {
                $profile_pic = $this->imageUpload('profile_pic', 'assets/uploads/');
                $postdata['profile_pic'] = $profile_pic;
            }

            // Update the vendor profile
            $this->db->where('id', $vendor_id);
            $this->db->update('vendor', $postdata);

            // Fetch the updated vendor data
            $fetched_update = $this->db
                ->where('id', $vendor_id)
                ->get('vendor')
                ->row();

            if ($fetched_update) {
                $json['result'] = 'true';
                $json['msg'] = 'Vendor Profile Updated Successfully';
                $json['data'] = $fetched_update;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Vendor Id Not Valid';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameters required: vendor_id, optional (name, email_id, address, mobile, business_name, about,experience, profile_pic)';
        }

        echo json_encode($json);
    }

    public function vendor_logout()
    {
        $vendor_id = $this->input->post('vendor_id');
        if (isset($vendor_id)) {
            extract($_POST);

            if (empty($vendor_id)) {
                $json['result'] = 'false';
                $json['msg'] = 'vendor_id not empty';
                echo json_encode($json);
                exit();
            }

            $wheredata = [
                'id' => $vendor_id,
            ];

            $data = [
                'fcm_id' => "",
            ];

            $result = $this->Api_model->update($wheredata, 'vendor', $data);

            if ($result) {
                $json['result'] = 'true';
                $json['msg'] = 'Successfully logout.';
            } else {
                $json['result'] = 'false';
                $json['msg'] = 'something went wrong';
            }
        } else {
            $json['result'] = "false";
            $json['msg'] = "parameter required vendor_id";
        }

        echo json_encode($json);
    }

    public function verify_otp()
    {
        $vendor_id = $this->input->post('vendor_id');
        $otp = $this->input->post('otp');

        if (isset($vendor_id) && isset($otp)) {
            $existing_user = $this->db
                ->select('id')
                ->where('id', $vendor_id)
                ->get('vendor')
                ->row();

            if (!$existing_user) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Vendor id Invalid.';
                echo json_encode($json);
                exit();
            }

            $user_otp = $this->db
                ->select('id, otp')
                ->where('id', $vendor_id)
                ->where('otp', $otp)
                ->get('vendor')
                ->row();

            if (!$user_otp) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'OTP Invalid.';
                echo json_encode($json);
                exit();
            }

            $data = [
                'verify_otp' => '1',
            ];

            $this->db->where('id', $vendor_id);
            $this->db->where('otp', $otp);
            $this->db->update('vendor', $data);

            $fetched_data = $this->db
                ->where('id', $vendor_id)
                ->select('id,otp,verify_otp')
                ->get('vendor')
                ->row();

            if ($fetched_data) {
                $json['result'] = 'true';
                $json['msg'] = 'OTP Verified Successfully';
                $json['data'] = $fetched_data;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Something went wrong';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameters required: vendor_id,otp';
        }

        echo json_encode($json);
    }

    public function resend_otp()
    {
        $vendor_id = $this->input->post('vendor_id');

        if (isset($vendor_id)) {
            $existing_user = $this->db
                ->select('id')
                ->where('id', $vendor_id)
                ->get('vendor')
                ->row();

            if (!$existing_user) {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'User id Invalid.';
                echo json_encode($json);
                exit();
            }

            $otp = rand(100000, 999999);

            $data = [
                'otp' => $otp,
            ];

            $this->db->where('id', $vendor_id);
            $this->db->update('vendor', $data);

            $fetched_data = $this->db
                ->where('id', $vendor_id)
                ->select('id, otp')
                ->get('vendor')
                ->row();

            if ($fetched_data) {
                $json['result'] = 'true';
                $json['msg'] = 'Resend OTP Successfully';
                $json['data'] = $fetched_data;
            } else {
                http_response_code(400);
                $json['result'] = 'false';
                $json['msg'] = 'Something went wrong';
            }
        } else {
            http_response_code(400);
            $json['result'] = 'false';
            $json['msg'] = 'Parameter required: vendor_id';
        }

        echo json_encode($json);
    }

    //...............................................................//
}

?>
