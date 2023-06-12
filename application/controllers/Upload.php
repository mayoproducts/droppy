<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property completehandler $completehandler
 */
class Upload extends CI_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('uploads');
        $this->load->model('files');

        $this->load->helper('url');
        $this->load->helper('cookie');

        $this->load->library('Mobile_Detect');
        $this->load->library('session');
    }

    /**
     * Index action
     */
    function index()
    {
        session_write_close();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', dirname(__FILE__) . '/error.log');

        if(!isset($_POST) || empty($_POST)) {
            header('Location: '.$this->config->item('site_url'));
            exit;
        }

        // Get all settings from the DB
        $settings = $this->config->config;

        // Get upload ID from post
        $upload_id      = $this->input->post('upload_id');
        $file_uid       = $this->input->post('file_uid');
        $original_path  = $this->input->post('original_path');

        // Define upload settings
        $config['upload_path']          = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir']           = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir_base']      = $settings['upload_dir'] . 'temp/';
        $config['blocked_file_types']   = (empty($settings['blocked_types']) ? 'empty/empty' : $settings['blocked_types']);
        $config['max_file_size']        = ($settings['max_size'] * 1024 * 1024);
        $config['max_number_of_files']  = $settings['max_files'];
        $config['upload_id']            = $upload_id;
        $config['file_id']              = md5($file_uid);
        $config['original_path']        = md5($original_path);

        // Init the upload library
        $this->load->library("UploadHandler", $config);

        // Process the upload and fetch a response
        $upload_response = $this->uploadhandler->get_response();

        // Store upload in droppy_files table
        if (is_array($upload_response)) {
            // Check each uploaded file
            foreach ($upload_response['files'] as $file) {
                if(isset($file->url) && !empty($file->url)) {
                    // Add file to the database
                    $this->files->add($upload_id, $config['file_id'], $file->name, $original_path, $file->size);
                    $this->logging->log("$upload_id > File $file->name finished uploading and added to database");
                }
            }
        }
        exit;
    }

    /**
     * Register the upload
     */
    function register()
    {
        $this->load->model('receivers');
        $this->load->model('emailverify');

        $post_data = $this->input->post(NULL, TRUE);
        $post_data['language'] = (!empty($this->session->userdata('language')) ? $this->session->userdata('language') : $this->config->item('language'));
        $post_data['file_previews'] = $this->config->item('file_previews');

        $this->logging->log($post_data['upload_id'] . " > Registration request received with data ".json_encode($post_data));

        if(!empty($post_data['password'])) {
            $post_data['password'] = password_hash($post_data['password'], PASSWORD_DEFAULT);
        }

        $error = false;

        // Do some form validation before accepting the new upload
        if(!empty($post_data['share']) && !empty($post_data['destruct']))
        {
            if ($post_data['share'] == 'mail')
            {
                $this->logging->log($post_data['upload_id'] . " > Registering new upload request");

                // Trim email from (sometimes spaces are added by mobile devices)
                $post_data['email_from'] = trim($post_data['email_from']);

                if(!empty($post_data['email_from']) && filter_var($post_data['email_from'], FILTER_VALIDATE_EMAIL) && count($post_data['email_to']) > 0 && !empty($post_data['email_to'][0])) {
                    $receivers = [];

                    // Validate recipient email addresses
                    foreach ($post_data['email_to'] as $email) {
                        // If there are more than 1 recipients and one is empty just skip it
                        if (count($post_data['email_to']) > 1 && empty($email)) {
                            continue;
                        }

                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $this->logging->log($post_data['upload_id'] . " > Added $email as receiver");
                            array_push($receivers, $email);
                        } else {
                            $this->logging->log($post_data['upload_id'] . " > Error! $email is not a valid email");
                            $error = 'email';
                            break;
                        }
                    }

                    // Continue with email verification if there aren't any errors with the given info
                    if (!$error) {
                        if (
                            ($this->config->item('email_verify') == 'once' && $this->emailverify->countVerifiedByEmail($post_data['email_from']) > 0)
                            ||
                            ($this->config->item('email_verify') == 'always' && isset($post_data['verify_code']) && $this->emailverify->countByEmailAndCode($post_data['email_from'], $post_data['verify_code']) > 0)
                            ||
                            ($this->config->item('email_verify') == 'false')
                        ) {
                            // Remove the verify code, we don't need it anymore
                            unset($post_data['verify_code']);

                            if ($this->config->item('enable_sender_cookie') == 'true') {
                                $this->logging->log($post_data['upload_id'] . " > Storing sender email as cookie");
                                set_cookie('sender', $post_data['email_from'], 10518975);
                            }

                            foreach ($receivers as $receiver) {
                                $this->receivers->add($post_data['upload_id'], $receiver, md5(time() . rand() . rand()));
                            }
                        } else {
                            $code = mt_rand(1000, 9999);

                            $this->emailverify->add(array(
                                'email' => $post_data['email_from'],
                                'time' => time(),
                                'code' => $code
                            ));

                            // Load email library
                            $this->load->library('email');

                            // Prepare sender data array to pass to email
                            $email_data = array('upload_id' => $post_data['upload_id'], 'code' => $code);

                            // Send email to uploader
                            $this->email->sendEmail('email_verify', $email_data, array($post_data['email_from']));

                            $this->logging->log($post_data['upload_id'] . " > Sender email is not verified, requesting verification from user..");
                            $error = 'verify_email';
                        }
                    } else {
                        $this->logging->log($post_data['upload_id'] . " > Error! Sender email is incorrect or recipients are not filled");
                        $error = 'email';
                    }
                } else {
                    $error = 'email';
                }
            }

        }
        else
        {
            $this->logging->log($post_data['upload_id'] . " > Error! Not all fields have been entered properly");
            $error = 'fields';
        }

        // Return ok or not ok based on results
        if($error === false)
        {
            $this->uploads->register($post_data);
            echo json_encode(array('response' => 'ok'));
        }
        else
        {
            echo json_encode(array('response' => $error));
        }
    }

    function verify_email()
    {
        $this->load->model('emailverify');

        // Fetch post data
        $post_data = $this->input->post(NULL, TRUE);

        $check = $this->emailverify->countPendingByEmailAndCode($post_data['email_from'], $post_data['code']);

        if($check > 0) {
            $this->emailverify->updatePendingByEmailAndCode($post_data['email_from'], $post_data['code'], array('status' => 'verified'));
            echo json_encode(array('response' => 'ok'));
        } else {
            echo json_encode(array('response' => 'false'));
        }
    }

    /**
     * Upload complete action
     */
    function complete()
    {
        // Load upload complete handler
        $this->load->library('CompleteHandler');

        // Fetch post data
        $post_data = $this->input->post(NULL, TRUE);

        $files = $this->files->getByUploadID($post_data['upload_id']);

        $post_data['total_files'] = 0;
        $post_data['total_size'] = 0;
        foreach ($files as $file) {
            $post_data['total_files']++;
            $post_data['total_size'] += $file['size'];
        }

        $this->logging->log($post_data['upload_id'] . " > Received upload completion request");

        // Complete the upload
        if($this->completehandler->complete($post_data)) {
            $this->uploads->complete($post_data);
            $this->logging->log($post_data['upload_id'] . " > Upload complete and marked as ready");
        }
    }

    /**
     * Delete upload action
     */
    public function delete()
    {
        $this->load->model('language');
        $this->load->model('themes');
        $this->load->model('socials');
        $this->load->model('backgrounds');
        $this->load->model('pages');

        $this->load->helper('language');
        $this->load->helper('url');
        $this->load->helper('number');

        // Load libraries
        $this->load->library('AuthLib');
        $this->load->library('Plugin');
        $this->load->library('Mobile_Detect');
        $this->load->library('session');

        // Get upload id and unique id from URL
        $upload_id  = $this->uri->segment(3, 0);
        $secret  = $this->uri->segment(4, 0);

        $upload = $this->uploads->getByUploadID($upload_id);

        if(!$upload || $upload['status'] != 'ready') {
            redirect('/' . $upload_id . '/' . $secret);
        }

        if(!empty($upload_id) && isset($secret) && isset($_POST) && !empty($_POST)) {
            // Load upload complete handler
            $this->load->library('UploadLib');

            $this->uploadlib->deleteUploadBySecret($upload_id, $secret);
            redirect('/' . $upload_id . '/' . $secret);
        } else {
            $detect = new Mobile_Detect();
            $data = [
                'settings' => $this->config->config,
                'upload_id' => $upload_id,
                'secret' => $secret,
                'language_list' => $this->language->getAll(),
                'unique_id' => $secret,
                'backgrounds' => $this->backgrounds->getAllOrderID(),
                'extra_pages' => $this->pages->getAll(),
                'custom_tabs' => $this->plugin->_tabs,
                'custom_css'    => $this->plugin->_css,
                'mobile' => false,
                'session' => $this->session
            ];

            if (file_exists(FCPATH . 'application/views/themes/' . $this->config->item('theme') . '/_elem/header-mobile.php') && ($detect->isMobile() || $detect->isTablet() || $detect->isAndroidOS())) {
                $data['mobile'] = true;
                $this->load->view('themes/' . $this->config->item('theme') . '/_elem/header-mobile', $data);
            } else {
                $this->load->view('themes/' . $this->config->item('theme') . '/_elem/header', $data);
            }
            $this->load->view('themes/' . $this->config->item('theme') . '/_elem/socials', $data);

            $this->load->view('themes/' . $this->config->item('theme') . '/delete', $data);

            $this->load->view('themes/' . $this->config->item('theme') . '/_elem/footer', $data);
        }
    }

    /**
     * Generates new upload ID
     */
    function genid()
    {
        // Get the upload ID length from the DB
        $len = $this->config->item('upload_id_length');
        // Generate upload ID
        $upload_id = $this->uploads->genUploadID($len);
        // Store the upload ID in a session
        $this->session->set_userdata('upload_id', $upload_id);

        $this->logging->log("$upload_id > Generated new upload ID");

        // Output the upload ID
        echo json_encode(array('upload_id' => $upload_id));
    }

    function file() {
        $filename = $this->input->get('file');
        $file_uid = $this->input->get('uid');

        // Get all settings from the DB
        $settings = $this->config->config;

        // Define upload settings
        $upload_path = FCPATH . $settings['upload_dir'] . 'temp/';
        $file_id = md5($file_uid);
        $file_path = $upload_path . $file_id . '-' . $filename;

        if(file_exists($file_path)) {
            $file = new \stdClass();
            $file->name = $filename;
            $file->size = filesize($file_path);

            echo json_encode(array('file' => $file));
        }
    }
	    function api(){
 
   $files = $this->input->post('files');

 // Get the upload ID length from the DB
        $len = $this->config->item('upload_id_length');
        $upload_id = $this->uploads->genUploadID($len);

$post_data['share'] ='link';
$post_data['destruct'] = 'no';
$post_data['email_to'] = array();
$post_data['email_from'] = '';
$post_data['message'] = '';
$post_data['password'] = '';	
$post_data['upload_id']=$upload_id;
$post_data['language'] = 'english'; 
$this->uploads->register($post_data);
// set password field if set
   if(!empty($this->input->post('password'))) {
            $post_data['password'] = password_hash($this->input->post('password'), PASSWORD_DEFAULT);
   }
   if(!empty($this->input->post('expire'))) {
            $post_data['expire'] = $this->input->post('expire');
   }
 // Get all settings from the DB
        $settings = $this->config->config;
        $file_uid   = rand(1111111111,9999999999);

        // Define upload settings
        $config['upload_path']          = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir']           = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir_base']      = $settings['upload_dir'] . 'temp/';
        $config['blocked_file_types']   = (empty($settings['blocked_types']) ? 'empty/empty' : $settings['blocked_types']);
        $config['max_file_size']        = ($settings['max_size'] * 1024 * 1024);
        $config['max_number_of_files']  = $settings['max_files'];
        $config['upload_id']            = $upload_id;
        $config['file_id']              = md5($file_uid);

        // Init the upload library
        $this->load->library("UploadHandler", $config);

        // Process the upload and fetch a response
        $upload_response = $this->uploadhandler->get_response();

        // Store upload in droppy_files table
        if (is_array($upload_response)) {
            // Check each uploaded file
            foreach ($upload_response['files'] as $file) {
                if(isset($file->url) && !empty($file->url)) {
                    // Add file to the database
                    $this->files->add($upload_id, $config['file_id'], $file->name, $file->size);
                  
                }
            }
        }
// Load upload complete handler
        $this->load->library('CompleteHandler');

        // Fetch post data
       // log_message('error', 'in complete message'.print_r($post_data, TRUE));
        $files = $this->files->getByUploadID($post_data['upload_id']);

        $post_data['total_files'] = 0;
        $post_data['total_size'] = 0;
        foreach ($files as $file) {
            $post_data['total_files']++;
            $post_data['total_size'] += $file['size'];
        }

      //  $this->logging->log($post_data['upload_id'] . " > Received upload completion request");

        // Complete the upload
        if($this->completehandler->complete($post_data)) {
            $this->uploads->complete($post_data);
          //  $this->logging->log($post_data['upload_id'] . " > Upload complete and marked as ready");
        }

$response = json_encode(array('url'=>'https://droppy.onlinecaclasses.co.in/'.$upload_id));
echo $response; 
    } 
	
function uploadfilesapi(){
 
   $files = $this->input->post('files');
	if(!empty($this->input->post('share'))) {
            $post_data['share'] = $this->input->post('share');
        }else{
	    $post_data['share'] ='link';
        }
			
        // Get the upload ID length from the DB
        $len = $this->config->item('upload_id_length');
        $upload_id = $this->uploads->genUploadID($len);
$post_data['file_previews'] = $this->config->item('file_previews');
//set default post data
$post_data['destruct'] = 'no';
$post_data['email_to'] = array();
$post_data['email_from'] = '';
$post_data['message'] = '';
$post_data['password'] = '';	
$post_data['upload_id']=$upload_id;
$post_data['language'] = 'english';

// set password field if set
   if(!empty($this->input->post('expiry_at'))) {
            $post_data['expire'] = $this->input->post('expire');
   }
 if(!empty($this->input->post('password'))) {
            $post_data['password'] = password_hash($this->input->post('password'), PASSWORD_DEFAULT);
   }	
	
	

//code if share type is mail		
if ($post_data['share'] == 'mail'){
	 $this->load->model('receivers');
        $this->load->model('emailverify');
	  $post_data['email_to'] = explode(',',$this->input->post('email_to'));
      $post_data['email_from'] = $this->input->post('email_from');
	  $post_data['verify_code'] = $this->input->post('verify_code');
  $error = false;

                if(!empty($post_data['email_from']) && filter_var($post_data['email_from'], FILTER_VALIDATE_EMAIL) && count($post_data['email_to']) > 0 && !empty($post_data['email_to'][0]))
                {
                    //var_dump($this->emailverify->countByEmailAndCode($post_data['email_from'], $post_data['verify_code']), $post_data['email_from'], $post_data['verify_code']);

                    if(
                        ($this->config->item('email_verify') == 'once' && $this->emailverify->countVerifiedByEmail($post_data['email_from']) > 0)
                        ||
                        ($this->config->item('email_verify') == 'always' && isset($post_data['verify_code']) && $this->emailverify->countByEmailAndCode($post_data['email_from'], $post_data['verify_code']) > 0)
                        ||
                        ($this->config->item('email_verify') == 'false')
                    ) {
                        // Remove the verify code, we don't need it anymore
                        unset($post_data['verify_code']);

                        foreach ($post_data['email_to'] as $email) {
                            // If there are more than 1 recipients and one is empty just skip it
                            if (count($post_data['email_to']) > 1 && empty($email)) {
                                continue;
                            }

                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $this->logging->log($post_data['upload_id'] . " > Added $email as receiver");
                                $this->receivers->add($post_data['upload_id'], $email, md5(time() . rand() . rand()));
                            } else {
                                $this->logging->log($post_data['upload_id'] . " > Error! $email is not a valid email");
                                $error = 'email is not valid';
                                break;
                            }
                        }
                    } else {
                        $code = mt_rand(1000,9999);

                        $this->emailverify->add(array(
                            'email' => $post_data['email_from'],
                            'time' => time(),
                            'code' => $code
                        ));

                        // Load email library
                        $this->load->library('email');

                        // Prepare sender data array to pass to email
                        $email_data = array('upload_id' => $post_data['upload_id'], 'code' => $code);

                        // Send email to uploader
                        $this->email->sendEmail('email_verify', $email_data, array($post_data['email_from']));

                        $this->logging->log($post_data['upload_id'] . " > Sender email is not verified, requesting verification from user..");
                        $error = 'verify_email';
                    }
                }
                else
                {
                    $this->logging->log($post_data['upload_id'] . " > Error! Sender email is incorrect or recipients are not filled");
                    $error = 'email';
                }
				
				// Return ok or not ok based on results
        if($error === false)
        {
            $this->uploads->register($post_data);
			
			 // Get all settings from the DB
        $settings = $this->config->config;
        $file_uid   = rand(1111111111,9999999999);

        // Define upload settings
        $config['upload_path']          = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir']           = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir_base']      = $settings['upload_dir'] . 'temp/';
        $config['blocked_file_types']   = (empty($settings['blocked_types']) ? 'empty/empty' : $settings['blocked_types']);
        $config['max_file_size']        = ($settings['max_size'] * 1024 * 1024);
        $config['max_number_of_files']  = $settings['max_files'];
        $config['upload_id']            = $upload_id;
        $config['file_id']              = md5($file_uid);

        // Init the upload library
        $this->load->library("UploadHandler", $config);

        // Process the upload and fetch a response
        $upload_response = $this->uploadhandler->get_response();
		$original_path ='';
        // Store upload in droppy_files table
        if (is_array($upload_response)) {
            // Check each uploaded file
            foreach ($upload_response['files'] as $file) {
                if(isset($file->url) && !empty($file->url)) {
                    // Add file to the database
                    //$this->files->add($upload_id, $config['file_id'], $file->name, $file->size);
                    $this->files->add($upload_id, $config['file_id'], $file->name, $original_path, $file->size);
                }
            }
        }
// Load upload complete handler
        $this->load->library('CompleteHandler');

        // Fetch post data
       // log_message('error', 'in complete message'.print_r($post_data, TRUE));
        $files = $this->files->getByUploadID($post_data['upload_id']);

        $post_data['total_files'] = 0;
        $post_data['total_size'] = 0;
        foreach ($files as $file) {
            $post_data['total_files']++;
            $post_data['total_size'] += $file['size'];
        }

      //  $this->logging->log($post_data['upload_id'] . " > Received upload completion request");

        // Complete the upload
        if($this->completehandler->complete($post_data)) {
            $this->uploads->complete($post_data);
          //  $this->logging->log($post_data['upload_id'] . " > Upload complete and marked as ready");
        }
            echo json_encode(array('response' => 'ok'));
        }
        else
        {
            echo json_encode(array('response' => $error));
        }
		
            }//end of if for mail type
		
	else{		//code for link type
 	$this->uploads->register($post_data);
 // Get all settings from the DB
        $settings = $this->config->config;
        $file_uid   = rand(1111111111,9999999999);

        // Define upload settings
        $config['upload_path']          = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir']           = FCPATH . $settings['upload_dir'] . 'temp/';
        $config['upload_dir_base']      = $settings['upload_dir'] . 'temp/';
        $config['blocked_file_types']   = (empty($settings['blocked_types']) ? 'empty/empty' : $settings['blocked_types']);
        $config['max_file_size']        = ($settings['max_size'] * 1024 * 1024);
        $config['max_number_of_files']  = $settings['max_files'];
        $config['upload_id']            = $upload_id;
        $config['file_id']              = md5($file_uid);

        // Init the upload library
        $this->load->library("UploadHandler", $config);

        // Process the upload and fetch a response
        $upload_response = $this->uploadhandler->get_response();
        $original_path ='';
        // Store upload in droppy_files table
        if (is_array($upload_response)) {
            // Check each uploaded file
            foreach ($upload_response['files'] as $file) {
                if(isset($file->url) && !empty($file->url)) {
                    // Add file to the database
                   // $this->files->add($upload_id, $config['file_id'], $file->name, $file->size);
                     $this->files->add($upload_id, $config['file_id'], $file->name, $original_path, $file->size);
                }
            }
        }
// Load upload complete handler
        $this->load->library('CompleteHandler');

        // Fetch post data
       // log_message('error', 'in complete message'.print_r($post_data, TRUE));
        $files = $this->files->getByUploadID($post_data['upload_id']);

        $post_data['total_files'] = 0;
        $post_data['total_size'] = 0;
        foreach ($files as $file) {
            $post_data['total_files']++;
            $post_data['total_size'] += $file['size'];
        }

      //  $this->logging->log($post_data['upload_id'] . " > Received upload completion request");

        // Complete the upload
        if($this->completehandler->complete($post_data)) {
            $this->uploads->complete($post_data);
          //  $this->logging->log($post_data['upload_id'] . " > Upload complete and marked as ready");
        }
		$download_link = base_url().'/'.$upload_id;
		$response ='';
 $response = json_encode(array('url'=>$download_link));
echo $response; 		
	}//end of code fro link type		


 //$response = json_encode(array('url'=>'https://droppy.onlinecaclasses.co.in/'.$upload_id));

    } 	

	 function verifyemailapi()
    {
        $this->load->model('emailverify');

        // Fetch post data
        $post_data = $this->input->post(NULL, TRUE);

        $check = $this->emailverify->countPendingByEmailAndCode($post_data['email_from'], $post_data['code']);

        if($check > 0) {
            $this->emailverify->updatePendingByEmailAndCode($post_data['email_from'], $post_data['code'], array('status' => 'verified'));
            echo json_encode(array('response' => 'ok'));
        } else {
            echo json_encode(array('response' => 'false'));
        }
    }

	 function resendverifycodeapi()
    {
		 
        $this->load->model('emailverify');
		   // Load email library
                        $this->load->library('email');
		 $response=array();
	    $post_data = $this->input->post(NULL, TRUE);
		 $data = $this->emailverify->getPendingByEmail($post_data['email_from']);
	     if(isset($data['status'])){
		 if($data['status']=='verified'){
			 $response['status'] = "verified";
			 $response['msg'] = "already verified";
		 }else{
			 $response['status'] = "pending";
			  // Prepare sender data array to pass to email
              $email_data = array('upload_id' => '', 'code' => $data['code']);

                        // Send email to uploader
              $this->email->sendEmail('email_verify', $email_data, array($post_data['email_from']));
			 $response['msg'] = "verification code resend to your email";
			 
		 }}else{
			 $response['status'] = "no file uploaded recently";
		 }
		
         echo json_encode(array('response' => $response));
		 }
}