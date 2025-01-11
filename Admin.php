<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Admin_model');

        $this->load->model('User_Model');

        $this->load->library('form_validation');
        date_default_timezone_set('Asia/Calcutta');
        // $this->load->library('session');

        error_reporting();
    }

    public function index()
    {
        $this->load->view('admin/index');
    }

    public function login()
    {
        extract($_POST);

        $data['title'] = 'Super Admin | Apexquote';

        $this->load->view('admin/header', $data);

        $this->load->library('form_validation');

        $this->form_validation->set_rules('email', 'Email', 'trim|required');

        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        // $this->form_validation->set_rules('rem', 'checkbox', 'required');

        if ($this->input->post("rem")) {
            setcookie("email", $this->input->post("email"), time() + 86400 * 30, "/");

            setcookie("password", $this->input->post("password"), time() + 86400 * 30, "/");

            setcookie("rem", $this->input->post("rem"), time() + 86400 * 30, "/");
        } else {
            setcookie("email", "", time() - 100, "/");

            setcookie("password", "", time() - 100, "/");

            setcookie("rem", "", time() - 100, "/");
        }

        if ($this->form_validation->run() == false) {
            if ($this->form_validation->error_string() != "") {
                $data["error"] =
                    '<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>



				<strong>Warning!</strong> ' .
                    $this->form_validation->error_string() .
                    '



				</div>';
            }
        } else {
            $q = $this->db->query("Select * from `admin` where (`admin_email`='" . $this->input->post("email") . "') and admin_password='" . $this->input->post("password") . "'  Limit 1");

            // print_r($q);exit();

            if ($q->num_rows() > 0) {
                $row = $q->row();

                $newdata = [
                    'admin_name' => $row->admin_name,

                    'admin_email' => $row->admin_email,

                    'logged_in' => true,

                    'boricua_id' => $row->boricua_id,

                    'admin_image' => $row->admin_image,
                ];

                $this->session->set_userdata($newdata);

                redirect('admin/dashboard');
            } else {
                $data["error"] = '<div class="alert alert-danger alert-dismissible" role="alert">

				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

				<strong>Warning!</strong> Invalid User and password. </div>';
            }
        }

        $this->load->view("admin/index", $data);
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect();
    }

    public function dashboard()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $data['title'] = "CARE | Dashboard";

        $data['services'] = 'user';
        $where['type'] = 'user';
        $data['users'] = $this->Admin_model->countwhere('users', 'id desc', $where);

        $data['appointment'] = $this->Admin_model->count('tbl_appointment', 'id desc');
        $wheredata['type'] = 'vendor';
        $data['vendor'] = $this->Admin_model->countwhere('vendor', 'id desc', $wheredata);

        $data['orders'] = '';

        $this->load->view("admin/dashboard", $data);
    }

    //.........................users 01-04-24.......................//
    public function users()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE | Users";
        $wheredata = [];
        $data['users'] = $this->User_Model->selectWhere('users', $wheredata);
        // $data['users']=$this->Admin_Model->selectWhere('users',$wheredata);
        $this->load->view("admin/all_users", $data);
    }

    public function delete_user($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->db->query('DELETE FROM `users` WHERE `id`=' . $id . '');

        $this->session->set_flashdata(
            "success_req",
            '<div class="alert alert-danger alert-dismissible" role="alert">

		<i class="fa fa-check"></i>

		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

		<strong>Success!</strong>User deleted..

		</div>'
        );

        redirect('Admin/users');
    }

    public function delete_vendor($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->db->query('DELETE FROM `vendor` WHERE `id`=' . $id . '');

        $this->session->set_flashdata(
            "success_req",
            '<div class="alert alert-danger alert-dismissible" role="alert">

		<i class="fa fa-check"></i>

		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

		<strong>Success!</strong>Vendor deleted..

		</div>'
        );

        redirect('Admin/vendors');
    }

    public function services_delete($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->db->query('DELETE FROM `vendor` WHERE `id`=' . $id . '');

        $this->session->set_flashdata(
            "success_req",
            '<div class="alert alert-danger alert-dismissible" role="alert">

		<i class="fa fa-check"></i>

		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

		<strong>Success!</strong>Service deleted..

		</div>'
        );

        redirect('Admin/services');
    }

    public function categorylist()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        // $data['category']=$this->db->select('*')->order_by('id','desc')->get('category')->result();
        $data['category'] = $this->Admin_model->categoryList();

        $data['page_title'] = " Category List";

        $data['title'] = " Category List";

        $this->load->view("admin/categorylist", $data);
    }

    public function Addcate()
    {
        // die('add');

        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'name', 'trim|required');

        // $this->form_validation->set_rules('service','service','trim|required');

        if ($this->form_validation->run() == false) {
            if ($this->form_validation->error_string() !== "") {
                $data['error'] = '<div class="alert alert-success alert-dismissible" role="alert">

					<i class="fa fa-check"></i>

					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

					<strong>Success!</strong> category  name,image is required ...

					</div>';
            }
        } else {
            $data['category_name'] = $this->input->post('name');

            if ($_FILES["file"]["size"] > 0) {
                // $config['upload_path']          = './uploads/';
                $config['upload_path'] = './assets/uploads/';

                $config['allowed_types'] = 'gif|jpg|png|jpeg';

                $this->load->library('upload', $config);

                $this->upload->initialize($config);

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('file')) {
                    $error = ['error' => $this->upload->display_errors()];

                    print_r($error);
                    die();
                } else {
                    $img_data = $this->upload->data();

                    $data["category_image"] = $img_data['file_name'];
                }
            }

            $this->db->insert('category', $data);

            $this->session->set_flashdata(
                "success_req",
                '<div class="alert alert-success alert-dismissible" role="alert">

				<i class="fa fa-check"></i>

				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

				<strong>Success!</strong> category list is added..

				</div>'
            );

            redirect('Admin/CategoryList');
        }

        $data['title'] = "Add category | ";

        // $data['country']=$this->db->order_by('id','desc')->get('country')->result();

        $this->load->view("admin/addcategory", $data);
    }

    //.........................01 August.......................//
    public function approve_vendor($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $result = $this->db->query("Update  `vendor` SET is_kyc_approved='1' where id = '" . $id . "'");

        //...................... mail .......................//

        //..........................................//

        if ($result) {
            $this->session->set_flashdata("message", 'Vendor Discarded Sucessfully.');

            redirect("Admin/vendors");
        } else {
            $this->session->set_flashdata("message", 'Something went wrong.');

            redirect("Admin/vendors");
        }
    }
    //..................................................//
    public function pending_vendor($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $result = $this->db->query("Update  `vendor` SET is_kyc_approved='0' where id = '" . $id . "'");

        if ($result) {
            $this->session->set_flashdata("message", 'Vendor Approved Sucessfully.');

            redirect("Admin/vendors");
        } else {
            $this->session->set_flashdata("message", 'Something went wrong.');

            redirect("Admin/vendors");
        }
    }

    public function active_category($id)
    {
        if (!$this->session->userdata('role') == "admin") {
            redirect('Admin');
        }

        $result = $this->db->query("Update  `category` SET status='1' where id = '" . $id . "'");

        if ($result) {
            $this->session->set_flashdata("message", 'category Discarded Sucessfully.');

            redirect("Admin/categoryList");
        } else {
            $this->session->set_flashdata("message", 'Something went wrong.');

            redirect("Admin/categoryList");
        }
    }

    public function updateprofile()
    {
        if (!$this->session->userdata('boricua_id')) {
            redirect('Admin');
        }

        $adminIDS = $this->session->userdata('boricua_id');

        $this->form_validation->set_rules('name', 'name', 'trim|required');

        $this->form_validation->set_rules('email', 'email', 'trim|required');

        $this->form_validation->set_rules('mobile', 'mobile', 'trim|required');

        if ($this->form_validation->run() == false) {
            if ($this->form_validation->error_string() !== "") {
                $data['error'] =
                    '<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>



			<strong>Warning!</strong> ' .
                    $this->form_validation->error_string() .
                    '



			</div>';
            }
        } else {
            $image = "icon.png";

            if (!empty($_FILES['admin_image']['name'])) {
                $config['upload_path'] = 'assets/uploads/';

                $config['allowed_types'] = '*';

                //Load upload library and initialize configuration

                $this->load->library('upload', $config);

                $this->upload->initialize($config);

                if ($this->upload->do_upload('admin_image')) {
                    $uploadData = $this->upload->data();

                    $image = $uploadData['file_name'];
                } else {
                    $image = '0';
                }
            }

            $this->db->where('boricua_id', $adminIDS);

            $this->db->update('admin', ['admin_name' => $this->input->post('name'), 'admin_image' => $image, 'admin_email' => $this->input->post('email'), 'admin_phone' => $this->input->post('mobile')]);

            $this->session->set_flashdata(
                "success_req",
                '<div class="alert alert-success alert-dismissible" role="alert">

			<i class="fa fa-check"></i>

			<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

			<strong>Success!</strong> profile update successfully...

			</div>'
            );

            redirect('Admin/updateprofile');
        }

        $data['userdata'] = $this->db->query('select * from `admin` where boricua_id= ' . $adminIDS . ' ')->row();

        $data['title'] = "Admin | Detail ";

        $this->load->view("admin/updateprofile", $data);
    }

    //.........................vendor............................//
    public function vendors()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $data['title'] = "CARE | Vendors";

        $wheredata = ['type' => 'vendor'];

        $data['users'] = $this->User_Model->selectWhere('vendor', $wheredata);

        $this->load->view("admin/all_vendors", $data);
    }

    public function approve_categorylist($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $result = $this->db->query("Update  `category` SET approve='1' where id = '" . $id . "'");

        //...................... mail .......................//

        //..........................................//

        if ($result) {
            $this->session->set_flashdata("message", 'category Discarded Sucessfully.');

            redirect("Admin/categorylist");
        } else {
            $this->session->set_flashdata("message", 'Something went wrong.');

            redirect("Admin/categorylist");
        }
    }

    //..................update category....................//
    public function update_category($id)
    {
        extract($_POST);

        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'name', 'trim|required');

        if ($this->form_validation->run() == false) {
            if ($this->form_validation->error_string() !== "") {
                $data['error'] = '<div class="alert alert-success alert-dismissible" role="alert">

					<i class="fa fa-check"></i>

					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

					<strong>Success!</strong> category  name and image is required ...

					</div>';
            }
        } else {
            $name = $this->input->post('name');

            $postdata = [
                'category_name' => $name,
            ];

            if ($_FILES["file"]["size"] > 0) {
                // $config['upload_path']          = './uploads/';
                $config['upload_path'] = './assets/uploads/';

                $config['allowed_types'] = '*';

                $this->load->library('upload', $config);

                $this->upload->initialize($config);

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('file')) {
                    $error = ['error' => $this->upload->display_errors()];
                } else {
                    $img_data = $this->upload->data();

                    $postdata["category_image"] = $img_data['file_name'];
                }
            }

            $this->Admin_model->updateData('category', $postdata, $id);

            $this->session->set_flashdata(
                "success_req",
                '<div class="alert alert-success alert-dismissible" role="alert">

				<i class="fa fa-check"></i>

				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

				<strong>Success!</strong> category list is Updated..

				</div>'
            );
            redirect('Admin/categorylist');
        }

        $data['title'] = "update category | CARE";

        $data['datas'] = $this->db->query('select * from category where id=' . $id . ' ')->row();

        $this->load->view("admin/edit_category", $data);
    }

    public function about_us()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE | About";
        $wheredata = [];
        $data['about'] = $this->Admin_model->selectWhere('about_us', $wheredata);
        // echo '<pre>';	print_r($data['about']); die;
        $this->load->view("admin/about_us", $data);
    }

    public function edit_about($id)
    {
        $data['title'] = 'about_us';
        $data['about'] = $this->db->query('select * from about_us where id=' . $id . '')->row();
        $this->load->view("admin/updateabout", $data);
    }

    public function update_about($id)
    {
        $type = $this->input->post('type');
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $data['type'] = $type;
        $data['title'] = $title;
        $data['description'] = $description;

        $newdata = $this->Admin_model->about_update($id, $data);

        if ($newdata == true) {
            $messge = ['message' => 'Terms Condition updated successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
        } else {
            $messge = ['message' => 'Terms Condition not updated', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);

        redirect('Admin/about_us', 'refresh');
    }

    public function FAQ()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE | FAQ";
        $wheredata = [];
        $data['faq'] = $this->Admin_model->selectWhere('faq', $wheredata);
        $this->load->view("admin/faq", $data);
    }
    public function edit_faq($id)
    {
        $data['title'] = 'Update Faq';
        $data['faq'] = $this->db->query('select * from faq where id=' . $id . '')->row();
        $this->load->view("admin/updatefaq", $data);
    }

    public function updatefaq($id)
    {
        $type = $this->input->post('type');
        $question = $this->input->post('question');
        $answer = $this->input->post('answer');
        $data['type'] = $type;
        $data['question'] = $question;
        $data['answer'] = $answer;

        $newdata = $this->Admin_model->faq_update($id, $data);

        if ($newdata == true) {
            $messge = ['message' => 'FAQ updated successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
        } else {
            $messge = ['message' => 'FAQ not updated', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);

        redirect('Admin/FAQ', 'refresh');
    }

    public function Privacy()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE | Privacy";
        $wheredata = [];
        $data['privacy'] = $this->Admin_model->selectWhere('privacy', $wheredata);
        $this->load->view("admin/privacy", $data);
    }

    public function edit_privacy($id)
    {
        $data['title'] = "Update Privacy";
        $data['privacy'] = $this->db->query('select * from privacy where id=' . $id . '')->row();
        $this->load->view("admin/updateprivacy", $data);
    }

    public function updateprivacy($id)
    {
        $type = $this->input->post('type');
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $data['type'] = $type;
        $data['title'] = $title;
        $data['description'] = $description;

        $newdata = $this->Admin_model->privacy_update($id, $data);

        if ($newdata == true) {
            $messge = ['message' => 'Privacy updated successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
        } else {
            $messge = ['message' => 'Privacy not updated', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);

        redirect('Admin/Privacy', 'refresh');
    }

    //..............Privacy End..................//

    //..............Terms And Condition..................//

    public function terms_condition()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE | Terms&Condition";
        $wheredata = [];
        $data['terms'] = $this->Admin_model->selectWhere('terms_conditions', $wheredata);
        $this->load->view("admin/terms_condition", $data);
    }

    public function edit_terms_condition($id)
    {
        $data['title'] = "CARE | Update";
        $data['terms'] = $this->db->query('select * from terms_conditions where id=' . $id . '')->row();
        // 	echo '<pre>'; print_r($data['terms']); die;
        $this->load->view("admin/updateterms", $data);
    }

    public function update_terms_condition($id)
    {
        $type = $this->input->post('type');
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $data['type'] = $type;
        $data['title'] = $title;
        $data['description'] = $description;

        $newdata = $this->Admin_model->terms_update($id, $data);

        if ($newdata == true) {
            $messge = ['message' => 'Terms Condition updated successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
        } else {
            $messge = ['message' => 'Terms Condition not updated', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);

        redirect('Admin/terms_condition', 'refresh');
    }

    public function services()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $query = $this->db
            ->select('vendor.*')
            ->select('category.category_name')
            ->from('vendor')
            ->join('category', 'category.id = vendor.category_id')
            ->order_by('vendor.id', 'desc')
            ->get();

        if ($query->num_rows() > 0) {
            $data['service'] = $query->result();
        } else {
            $data['service'] = [];
        }

        // echo '<pre>'; print_r($data['service']); die;
        $data['page_title'] = " All Services";

        $data['title'] = "All Services";

        $this->load->view("admin/services", $data);
    }

    public function services_details($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
            $query = $this->db
                ->select('vendor.*')
                ->select('category.category_name')
                ->from('vendor')
                ->join('category', 'category.id = vendor.category_id')
                ->where('vendor.id', $id)
                ->order_by('vendor.id', 'desc')
                ->get();

            if ($query->num_rows() > 0) {
                $data['service'] = $query->row();
            } else {
                $data['service'] = [];
            }
            // echo '<pre>'; print_r( $data['service']); die();
            $data['page_title'] = "Services Detail";
            $data['title'] = "Services Detail";

            $this->load->view("admin/services_details", $data);
        } else {
            redirect('Admin');
        }
    }

    public function pending_category($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $result = $this->db->query("Update  `category` SET approve='0' where id = '" . $id . "'");

        if ($result) {
            $this->session->set_flashdata("message", 'Category Approved Sucessfully.');

            redirect("Admin/categorylist");
        } else {
            $this->session->set_flashdata("message", 'Something went wrong.');

            redirect("Admin/categorylist");
        }
    }

    public function delete_category($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->db->query('DELETE FROM `category` WHERE `id`=' . $id . '');

        $this->session->set_flashdata(
            "success_req",
            '<div class="alert alert-danger alert-dismissible" role="alert">

		<i class="fa fa-check"></i>

		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

		<strong>Success!</strong>Category deleted..

		</div>'
        );

        redirect('Admin/categorylist');
    }

    public function Banner()
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }
        $data['title'] = "CARE";
        $wheredata = [];
        $data['banner'] = $this->Admin_model->category_banner();
        // echo '<pre>'; print_r($data['banner']); die;
        $this->load->view("admin/banner_list", $data);
    }

    public function Add_banner()
    {
        // die('add');

        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $data['title'] = "Add Banner | ";

        $data['category'] = $this->db
            ->order_by('id', 'desc')
            ->get('category')
            ->result();

        $this->load->view("admin/add_banner", $data);
    }

    public function banner_registration()
    {
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $category_id = $this->input->post('category_id');

        $formarray = [];
        if (!empty($_FILES['banner_image']['name'])) {
            $config['upload_path'] = './assets/uploads/';
            $config['allowed_types'] = '*';
            $config['max_size'] = 10000000;
            $config['file_name'] = $_FILES['banner_image']['name'];

            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if ($this->upload->do_upload('banner_image')) {
                $uploadData = $this->upload->data();
                $banner_image = $uploadData['file_name'];
            }
        }

        $banner_image = $_FILES['banner_image']['name'];

        $formarray['banner_image'] = $banner_image;
        $formarray['title'] = $title;
        $formarray['description'] = $description;
        $formarray['category_id'] = $category_id;

        // echo "<pre>"; print_r( $config['file_name']); die;
        $rest = $this->Admin_model->add_banner($formarray);

        if (!empty($rest)) {
            $messge = ['message' => 'Banner Added successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
            redirect(base_url('Admin/Banner'));
        } else {
            $messge = ['message' => 'Banner Not Added  successfully', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);
        redirect(base_url('Admin/Banner'));
    }

    public function edit_banner($id)
    {
        $data['title'] = "CARE";
        $data['category'] = $this->db
            ->order_by('id', 'desc')
            ->get('category')
            ->result();
        $data['banner'] = $this->db->query('select * from banner where id=' . $id . '')->row();
        $this->load->view("admin/edit_banner", $data);
    }

    public function update_banner($id)
    {
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $category_id = $this->input->post('category_id');

        if (!empty($_FILES['banner_image']['name'])) {
            $config['upload_path'] = './assets/uploads/';
            $config['allowed_types'] = '*';
            $config['max_size'] = 10000000;
            $config['file_name'] = $_FILES['banner_image']['name'];

            //Load upload library and initialize configuration
            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if ($this->upload->do_upload('banner_image')) {
                $uploadData = $this->upload->data();
                $banner_image = $uploadData['file_name'];
            } else {
                $banner_image = '';
                $error = ['error' => $this->upload->display_errors()];
                echo "<script>alert('JPG, JPEG, PNG and GIF type of file only is allowed and atleast 10MB of size');window.location = '" . base_url("") . "';</script>";
            }
        } else {
            $banner_image = '';
        }
        $data = [];

        $data['title'] = $title;
        $data['description'] = $description;
        $data['category_id'] = $category_id;
        if (!empty($banner_image)) {
            $data['banner_image'] = $banner_image;
        }

        // print_r($data['image']); die;

        $newdata = $this->Admin_model->banner_update($id, $data);

        if ($newdata == true) {
            $messge = ['message' => 'Banner Image updated successfully', 'class' => 'alert alert-success in'];
            $this->session->set_flashdata('item', $messge);
            redirect('Admin/Banner', 'refresh');
        } else {
            $messge = ['message' => 'Banner Image not updated', 'class' => 'alert alert-danger in'];
            $this->session->set_flashdata('item', $messge);
        }
        $this->session->keep_flashdata('item', $messge);

        redirect('Admin/Banner', 'refresh');
    }

    public function delete_banner($id)
    {
        if (!empty($this->session->userdata('boricua_id'))) {
        } else {
            redirect('Admin');
        }

        $this->db->query('DELETE FROM `banner` WHERE `id`=' . $id . '');

        $this->session->set_flashdata(
            "success_req",
            '<div class="alert alert-danger alert-dismissible" role="alert">

		<i class="fa fa-check"></i>

		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

		<strong>Success!</strong>Banner deleted..

		</div>'
        );

        redirect('Admin/Banner');
    }

    public function add_service()
    {
        $data['title'] = "Add Service | CARE";
        $data['category'] = $this->db
            ->order_by('id', 'desc')
            ->get('category')
            ->result();
        $this->load->view("admin/add_service", $data);
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

    public function save_service()
    {
        $profile_pic = $this->upload_and_resize('profile_pic');
        $gove_id_proof = $this->upload_and_resize('gove_id_proof');
        $logo = $this->upload_and_resize('logo');
        $service_image = $this->upload_and_resize('service_image');

        $open_hour = $this->input->post('open_time');
        $open_minute = $this->input->post('open_minute');
        $open_ampm = $this->input->post('open_ampm');
        $open_time = sprintf('%02d:%02d %s', $open_hour, $open_minute, $open_ampm);

        $close_hour = $this->input->post('close_time');
        $close_minute = $this->input->post('close_minute');
        $close_ampm = $this->input->post('close_ampm');
        $close_time = sprintf('%02d:%02d %s', $close_hour, $close_minute, $close_ampm);

        $data = [
            'category_id' => $this->input->post('category_id'),
            'name' => $this->input->post('name'),
            'mobile' => $this->input->post('mobile'),
            'email_id' => $this->input->post('email_id'),
            'business_name' => $this->input->post('business_name'),
            'open_time' => $open_time,
            'close_time' => $close_time,
            'lat' => $this->input->post('lat'),
            'long' => $this->input->post('long'),
            'address' => $this->input->post('address'),
            'about' => $this->input->post('about'),
            'experience' => $this->input->post('experience'),
            'profile_pic' => $profile_pic,
            'gove_id_proof' => $gove_id_proof,
            'logo' => $logo,
            'service_image' => $service_image,
        ];

        // echo '<pre>'; print_r($data); die();

        if ($this->db->insert('vendor', $data)) {
            $this->session->set_flashdata('item', [
                'message' => 'Services added successfully',
                'class' => 'alert alert-success',
            ]);
        } else {
            $this->session->set_flashdata('item', [
                'message' => 'Services could not be added',
                'class' => 'alert alert-danger',
            ]);
        }

        // Redirect to Services Page
        redirect('Admin/services');
    }
}
?>



