<?php

// Load the autoloader
use Stripe\StripeClient;

require_once dirname(__FILE__) . '/autoloader.php';

$clsUser     = new PremiumUser();
$clsForgot   = new PremiumForgot();
$clsSettings = new PremiumSettings();
$clsSubs     = new PremiumSubs();
$clsVoucher  = new PremiumVoucher();

$premium_settings = $clsSettings->getSettings();
$droppy_settings = $clsSettings->getDroppySettings();

$subscription_price = $premium_settings['sub_price'];
$_SESSION['Payment_Amount'] = $subscription_price;

// Getting the paypal functions
require_once (dirname(__FILE__) . '/gateway/paypal/paypalfunctions.php');

// Loading stripe
require_once(dirname(__FILE__) . '/gateway/stripe/init.php');

$premiumJsonConfig = file_get_contents(dirname(__FILE__) . '/config.json');
$premium_config = json_decode($premiumJsonConfig, true)['premium'];

$clsPaypal = new Paypal($premium_config, $premium_settings);

//Check if there is an action
if(isset($_POST['action']))
{
    //Check if the register function is called and validates password and terms
    if($_POST['action'] == 'register' && $_POST['password'] == $_POST['re_password'] && $_POST['terms'] == 'true')
    {
        //Getting variables from the form
        $email = $_POST['email'];
        $password = hash('sha512', $_POST['password']);
        $fullname = $_POST['name'];
        $company = $_POST['company'];
        $payment = $premium_settings['payment_gateway'];
        $date = date("Y-m-d H:i:s");
        $time = time();
        $user_ip = ($droppy_settings['disable_ip_logging'] == 'true' ? '' : $_SERVER['REMOTE_ADDR']);
        $_SESSION["original_url"] = $_POST['rd'];
        $_SESSION["subscription_id"] = md5(time() . rand());

        $get_user = $clsUser->getByEmail($email);

        // Check if voucher has been given
        if(isset($_POST['voucher']) && !empty($_POST['voucher'])) {
            // Search voucher in DB
            $voucher = $clsVoucher->getByCode($_POST['voucher']);

            // Check if exists
            if (count($voucher) > 0) {
                if($voucher['discount_type'] == 'percentage') {
                    // Calculate the discount price
                    $percentage = ($subscription_price * ($voucher['discount_percentage'] / 100));
                    $subscription_price = ($subscription_price - $percentage);
                }
                elseif($voucher['discount_type'] == 'value') {
                    // Calculate the discount price
                    $subscription_price = ($subscription_price - $voucher['discount_value']);
                }

                if($subscription_price <= 0) {
                    $subscription_price = 0;
                    $payment = 'free';
                }
            }
        }

        // Checks if the user with $email doesn't already exists
        if($get_user === false) {
            $sub_id = $_SESSION['subscription_id'];

            // When the payment method is paypal
            if($payment == 'paypal')
            {
                // The price of the subscription
                $_SESSION["Payment_Amount"] = $subscription_price;

                // The payment type
                $paymentType = 'Sale';

                // Redirection URL when the payment has been successfully completed
                $returnURL = $this->config->item('site_url') . 'page/premium?action=payment_review';

                // When the payment is canceled by the user itself
                $cancelURL = $this->config->item('site_url') . 'page/premium?action=paypal_payment_canceled';

                // Redirection URL when the payment has been successfully completed
                $successUrl = $this->config->item('site_url') . 'page/premium?payment=created';

                $_SESSION['success_url'] = $successUrl;

                // Logo image path of paypal page
                $logoPath = $premium_settings['logo_url'];

                // Sending information
                $resArray = $clsPaypal->CallShortcutExpressCheckout ($_SESSION['Payment_Amount'], $premium_settings['currency'], $paymentType, $returnURL, $cancelURL, $logoPath);
                $ack = strtoupper($resArray["ACK"]);

                // When the payment information is correct
                if(strtoupper($ack)=="SUCCESS" || strtoupper($ack)=="SUCCESSWITHWARNING")
                {
                    $token = $resArray["TOKEN"];

                    $subs_data = array(
                        'sub_id'        => $sub_id,
                        'email'         => $email,
                        'name'          => $fullname,
                        'company'       => $company,
                        'payment'       => 'paypal',
                        'time'          => $time,
                        'status'        => 'validating',
                        'paypal_token'  => $token
                    );

                    if($premium_settings['enable_vat'] == 'true') {
                        $extra_data = array('vat_number' => $_POST['vat']);
                        $subs_data = array_merge($subs_data, $extra_data);
                        unset($extra_data);
                    }

                    if($premium_settings['enable_address'] == 'true') {
                        $extra_data = array(
                            'address_street' => $_POST['address_street'],
                            'address_zip' => $_POST['address_zip'],
                            'address_city' => $_POST['address_city'],
                            'address_country' => $_POST['address_country']
                        );
                        $subs_data = array_merge($subs_data, $extra_data);
                    }

                    // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
                    $clsSubs->insert($subs_data);

                    $users_data = array(
                        'email'     => $email,
                        'password'  => $password,
                        'ip'        => $user_ip,
                        'sub_id'    => $sub_id,
                        'status'    => 'ready'
                    );

                    $clsUser->insert($users_data);

                    $clsPaypal->RedirectToPayPal ( $token );
                }
                else
                {
                    // Display a user friendly Error on the page using any of the following error information returned by PayPal
                    $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                    $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);

                    error_log($ErrorCode);
                    error_log($ErrorLongMsg);
                }
            }
            elseif($payment == 'stripe') {
                $stripe = new StripeClient(
                    $premium_settings['stripe_key']
                );
                $customer = $stripe->customers->create([
                    'email' => $email,
                    'name' => $fullname
                ]);
                $checkout = $stripe->checkout->sessions->create([
                    'success_url' => $this->config->item('site_url') . 'page/premium?payment=created',
                    'cancel_url' => $this->config->item('site_url') . 'page/premium?action=payment_canceled',
                    'payment_method_types' => ['card'],
                    'customer' => $customer['id'],
                    'client_reference_id' => $_SESSION["subscription_id"],
                    'line_items' => [
                        [
                            'quantity' => 1,
                            'price_data' => [
                                'currency' => $premium_settings['currency'],
                                'product' => $premium_settings['stripe_product'],
                                'recurring' => [
                                    'interval' => strtolower($premium_settings['recur_time']),
                                    'interval_count' => $premium_settings['recur_freq']
                                ],
                                'unit_amount' => (str_replace(',', '.', $premium_settings['sub_price']) * 100)
                            ]
                        ],
                    ],
                    'mode' => 'subscription',
                ]);

                $subs_data = array(
                    'sub_id'        => $sub_id,
                    'email'         => $email,
                    'name'          => $fullname,
                    'company'       => $company,
                    'payment'       => 'stripe',
                    'time'          => $time,
                    'status'        => 'validating'
                );

                if($premium_settings['enable_vat'] == 'true') {
                    $extra_data = array('vat_number' => $_POST['vat']);
                    $subs_data = array_merge($subs_data, $extra_data);
                    unset($extra_data);
                }

                if($premium_settings['enable_address'] == 'true') {
                    $extra_data = array(
                        'address_street' => $_POST['address_street'],
                        'address_zip' => $_POST['address_zip'],
                        'address_city' => $_POST['address_city'],
                        'address_country' => $_POST['address_country']
                    );
                    $subs_data = array_merge($subs_data, $extra_data);
                }

                // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
                $clsSubs->insert($subs_data);

                $users_data = array(
                    'email'     => $email,
                    'password'  => $password,
                    'ip'        => $user_ip,
                    'sub_id'    => $sub_id,
                    'status'    => 'ready'
                );

                $clsUser->insert($users_data);

                header('Location: '.$checkout['url']);
            }
            elseif($payment == 'free') {
                $subs_data = array(
                    'sub_id'        => $sub_id,
                    'email'         => $email,
                    'name'          => $fullname,
                    'company'       => $company,
                    'payment'       => 'free',
                    'time'          => $time,
                    'status'        => 'active',
                    'paypal_token'  => '',
                    'paypal_id'     => $sub_id
                );

                if($premium_settings['enable_vat'] == 'true') {
                    $extra_data = array('vat_number' => $_POST['vat']);
                    $subs_data = array_merge($subs_data, $extra_data);
                    unset($extra_data);
                }

                if($premium_settings['enable_address'] == 'true') {
                    $extra_data = array(
                        'address_street' => $_POST['address_street'],
                        'address_zip' => $_POST['address_zip'],
                        'address_city' => $_POST['address_city'],
                        'address_country' => $_POST['address_country']
                    );
                    $subs_data = array_merge($subs_data, $extra_data);
                }

                // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
                $clsSubs->insert($subs_data);

                $users_data = array(
                    'email'     => $email,
                    'password'  => $password,
                    'ip'        => $user_ip,
                    'sub_id'    => $sub_id,
                    'status'    => 'ready'
                );

                $clsUser->insert($users_data);

                // Email shortcodes replacements
                $sub_info = $clsSubs->getBySubID($sub_id);
                if($sub_info !== false) {
                    $tokens = array(
                        'next_date'     => (!empty($sub_info['next_date']) ? date("Y-m-d", $sub_info['next_date']) : ''),
                        'paypal_id'     => $sub_info['paypal_id'],
                        'last_date'     => (!empty($sub_info['last_date']) ? date("Y-m-d", $sub_info['last_date']) : ''),
                        'name'          => $sub_info['name'],
                        'status'        => $sub_info['status'],
                        'company'       => $sub_info['company'],
                        'sub_id'        => $sub_info['sub_id'],
                        'manage_page'   => $droppy_settings['site_url'] . '?goto=custom_account',
                        'email' 		=> $sub_info['email'],
                        'payment' 		=> $sub_info['payment'],
                        'amount' 		=> $subscription_price
                    );

                    $pattern = '{%s}';

                    $map = array();
                    foreach($tokens as $var => $value)
                    {
                        $map[sprintf($pattern, $var)] = $value;
                    }

                    $email_message = strtr($premium_settings['new_sub_email'], $map);
                    $this->email->sendEmailClean($email_message, $premium_settings['new_sub_subject'], array($email));

                    header('Location: ' . $this->config->item('site_url') . 'page/premium?payment=created');
                }
            }
        }
    }
    if($_POST['action'] == 'check_email')
    {
        $postemail = $_POST['email'];

        if (!filter_var($postemail, FILTER_VALIDATE_EMAIL) === false) {
            $check_email = $clsUser->getByEmail($postemail);
            if(!$check_email)
            {
                echo 1;
            }
            else
            {
                echo 2;
            }
        }
        else
        {
            echo 3;
        }
    }
    if($_POST['action'] == 'check_voucher')
    {
        $clsVoucher = new PremiumVoucher();

        $voucher = $clsVoucher->getByCode($_POST['voucher']);
        if(!$voucher)
        {
            echo 0;
        }
        else
        {
            echo 1;
        }
    }
    if($_POST['action'] == 'login')
    {
        $email 		= $_POST['email'];
        $password 	= hash('sha512', $_POST['password']);

        $user = $clsUser->getByEmail($email);
        if($user !== false)
        {
            if($user['password'] == $password) {
                if($user['status'] == 'ready') {
                    $_SESSION['droppy_premium']         = $user['id'];
                    $_SESSION['droppy_premium_email']   = $user['email'];
                    echo 1;
                }
                if($user['status'] == 'suspended_reversal') {
                    $_SESSION['droppy_premium_suspend'] = $user['id'];
                    $_SESSION['droppy_premium_email']   = $user['email'];

                    echo 2;
                }
            }
            else
            {
                echo 0;
            }
        }
        else
        {
            echo 0;
        }
    }
    if($_POST['action'] == 'forgot')
    {
        $user = $clsUser->getByEmail($_POST['email']);

        if($user !== false)
        {
            $reset_code = hash('sha512', md5(rand() . time() . rand()));

            $db_data = array(
                'email' => $_POST['email'],
                'reset' => $reset_code
            );

            $clsForgot->insert($db_data);

            $tokens = array(
                'reset_url'    	=> $droppy_settings['site_url'] . '?goto=custom_account&reset=' . $reset_code
            );

            $pattern = '{%s}';

            $map = array();
            foreach($tokens as $var => $value)
            {
                $map[sprintf($pattern, $var)] = $value;
            }

            $email_message = strtr($premium_settings['forgot_pass_email'], $map);

            $this->email->sendEmailClean($email_message, $premium_settings['forgot_pass_subject'], array($user['email']));

            echo 1;
        }
        else
        {
            echo 0;
        }
    }
    if($_POST['action'] == 'reset_pass')
    {
        $pass1 		= $_POST['pass1'];
        $pass2 		= $_POST['pass2'];

        $res = $clsForgot->getByResetCode($_POST['reset']);
        if($res !== false)
        {
            $email = $res['email'];
            $new_pass = hash('sha512', $pass1);

            $update = array('password' => $new_pass);
            $clsUser->updateByEmail($update, $email);

            $clsForgot->deleteByResetCode($_POST['reset']);

            echo 1;
        }
        else
        {
            echo 0;
        }
    }
    if($_POST['action'] == 'change_details')
    {
        $email 		= $_POST['email'];
        $password 	= hash('sha512', $_POST['password']);
        $name 		= $_POST['name'];
        $company 	= $_POST['company'];
        $sub_id 	= $_POST['sub_id'];

        $sub_info = $clsSubs->getBySubID($sub_id);

        if($sub_info !== false) {
            if(!empty($email) || !empty($name) || !empty($sub_id)) {
                if(($email != $sub_info['email'] && $clsUser->getByEmail($email) === false) || $email == $sub_info['email'])
                {
                    $sub_data = array(
                        'email'     => $email,
                        'company'   => $company,
                        'name'      => $name
                    );

                    if($premium_settings['enable_vat'] == 'true') {
                        $extra_data = array('vat_number' => $_POST['vat_number']);
                        $sub_data = array_merge($sub_data, $extra_data);
                        unset($extra_data);
                    }

                    if($premium_settings['enable_address'] == 'true') {
                        $extra_data = array(
                            'address_street' => $_POST['address_street'],
                            'address_zip' => $_POST['address_zip'],
                            'address_city' => $_POST['address_city'],
                            'address_country' => $_POST['address_country']
                        );
                        $sub_data = array_merge($sub_data, $extra_data);
                    }

                    $clsSubs->updateBySubID($sub_data, $sub_id);

                    if(empty($_POST['password'])) {
                        $clsUser->updateBySubID(array('email' => $email), $sub_id);

                        echo 1;
                    }
                    else
                    {
                        $clsUser->updateBySubID(array('email' => $email, 'password' => $password), $sub_id);

                        echo 1;
                    }
                }
                else
                {
                    echo 2;
                }
            }
            else
            {
                echo 3;
            }
        }
    }
    if($_POST['action'] == 'add_sub') {
        $payment = $premium_settings['payment_gateway'];
        $old_sub_id = $_POST['sub_id'];
        $fullname = $_POST['name'];
        $company = $_POST['company'];
        $date = date("Y-m-d H:i:s");
        $time = time();
        $user_ip = ($droppy_settings['disable_ip_logging'] == 'true' ? '' : $_SERVER['REMOTE_ADDR']);
        $_SESSION["original_url"] = $this->config->item('site_url') . '?goto=custom_account';

        $session_id = $_SESSION['droppy_premium'];
        $_SESSION["subscription_id"] = md5(time() . rand());
        $sub_id = $_SESSION["subscription_id"];

        $get_user_details = $clsUser->getByID($session_id);

        $email = $get_user_details['email'];

        if(isset($_POST['voucher']) && !empty($_POST['voucher'])) {
            $clsVoucher = new PremiumVoucher();

            // Search voucher in DB
            $voucher = $clsVoucher->getByCode($_POST['voucher']);

            // Check if exists
            if (count($voucher) > 0) {
                if($voucher['discount_type'] == 'percentage') {
                    // Calculate the discount price
                    $percentage = ($subscription_price * ($voucher['discount_percentage'] / 100));
                    $subscription_price = ($subscription_price - $percentage);
                }
                elseif($voucher['discount_type'] == 'value') {
                    // Calculate the discount price
                    $subscription_price = ($subscription_price - $voucher['discount_value']);
                }

                if($subscription_price <= 0) {
                    $subscription_price = 0;
                    $payment = 'free';
                }
            }
        }

        if($payment == 'paypal')
        {
            // The price of the subscription
            $_SESSION["Payment_Amount"] = $subscription_price;

            // The payment type
            $paymentType = 'Sale';

            // Redirection URL when the payment has been successfully completed
            $returnURL = $this->config->item('site_url') . 'page/premium?action=payment_review';

            // When the payment is canceled by the user itself
            $cancelURL = $this->config->item('site_url') . 'page/premium?action=paypal_payment_canceled';

            // Redirection URL when the payment has been successfully completed
            $successUrl = $this->config->item('site_url') . 'page/premium?payment=created';

            $_SESSION['success_url'] = $successUrl;

            // Logo image path of paypal page
            $logoPath = $premium_settings['logo_url'];

            // Sending information
            $resArray = $clsPaypal->CallShortcutExpressCheckout ($_SESSION['Payment_Amount'], $premium_settings['currency'], $paymentType, $returnURL, $cancelURL, $logoPath);
            $ack = strtoupper($resArray["ACK"]);

            // When the payment information is correct
            if(strtoupper($ack)=="SUCCESS" || strtoupper($ack)=="SUCCESSWITHWARNING")
            {
                $token = $resArray["TOKEN"];

                $subs_data = array(
                    'sub_id'        => $sub_id,
                    'email'         => $email,
                    'name'          => $fullname,
                    'company'       => $company,
                    'payment'       => 'paypal',
                    'time'          => $time,
                    'status'        => 'validating',
                    'paypal_token'  => $token
                );

                // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
                $clsSubs->insert($subs_data);

                $clsUser->updateBySubID(array('sub_id' => $sub_id), $old_sub_id);

                $clsPaypal->RedirectToPayPal ( $token );
            }
            else
            {
                // Display a user friendly Error on the page using any of the following error information returned by PayPal
                $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);

                error_log($ErrorCode);
                error_log($ErrorLongMsg);
            }
        }
        elseif($payment == 'stripe') {
            $stripe = new StripeClient(
                $premium_settings['stripe_key']
            );
            $customer = $stripe->customers->create([
                'email' => $email,
                'name' => $fullname
            ]);
            $checkout = $stripe->checkout->sessions->create([
                'success_url' => $this->config->item('site_url') . 'page/premium?payment=created',
                'cancel_url' => $this->config->item('site_url') . 'page/premium?action=payment_canceled',
                'payment_method_types' => ['card'],
                'customer' => $customer['id'],
                'client_reference_id' => $_SESSION["subscription_id"],
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => $premium_settings['currency'],
                            'product' => $premium_settings['stripe_product'],
                            'recurring' => [
                                'interval' => strtolower($premium_settings['recur_time']),
                                'interval_count' => $premium_settings['recur_freq']
                            ],
                            'unit_amount' => (str_replace(',', '.', $premium_settings['sub_price']) * 100)
                        ]
                    ],
                ],
                'mode' => 'subscription',
            ]);

            $subs_data = array(
                'sub_id'        => $sub_id,
                'email'         => $email,
                'name'          => $fullname,
                'company'       => $company,
                'payment'       => 'stripe',
                'time'          => $time,
                'status'        => 'validating'
            );

            // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
            $clsSubs->insert($subs_data);

            $clsUser->updateBySubID(array('sub_id' => $sub_id), $old_sub_id);

            header('Location: '.$checkout['url']);
        }
        elseif($payment == 'free') {
            $subs_data = array(
                'sub_id'        => $sub_id,
                'email'         => $email,
                'name'          => $fullname,
                'company'       => $company,
                'payment'       => 'free',
                'time'          => $time,
                'status'        => 'active',
                'paypal_token'  => '',
                'paypal_id'     => $sub_id
            );

            // Inserting the info into the database (This information is not validated yet so the user can not login yet) the review.php file will set the status of the users to ready
            $clsSubs->insert($subs_data);

            $clsUser->updateBySubID(array('sub_id' => $sub_id), $old_sub_id);

            // Email shortcodes replacements
            $sub_info = $clsSubs->getBySubID($sub_id);
            if($sub_info !== false) {
                $tokens = array(
                    'next_date'     => (!empty($sub_info['next_date']) ? date("Y-m-d", $sub_info['next_date']) : ''),
                    'paypal_id'     => $sub_info['paypal_id'],
                    'last_date'     => (!empty($sub_info['last_date']) ? date("Y-m-d", $sub_info['last_date']) : ''),
                    'name'          => $sub_info['name'],
                    'status'        => $sub_info['status'],
                    'company'       => $sub_info['company'],
                    'sub_id'        => $sub_info['sub_id'],
                    'manage_page'   => $droppy_settings['site_url'] . '?goto=custom_account',
                    'email' 		=> $sub_info['email'],
                    'payment' 		=> $sub_info['payment'],
                    'amount' 		=> $subscription_price
                );

                $pattern = '{%s}';

                $map = array();
                foreach($tokens as $var => $value)
                {
                    $map[sprintf($pattern, $var)] = $value;
                }

                $email_message = strtr($premium_settings['new_sub_email'], $map);
                $this->email->sendEmailClean($email_message, $premium_settings['new_sub_subject'], array($email));

                header('Location: ' . $this->config->item('site_url') . 'page/premium?payment=created');
            }
        }
    }
    if($_POST['action'] == 'add_sub_user') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if(!empty($email) && !empty($password)) {
            // Check if email does not exist
            if(!$clsUser->getByEmail($email)) {
                $cur_user_sub = $clsUser->getByID($_SESSION['droppy_premium'])['sub_id'];

                if(!empty($cur_user_sub)) {
                    $clsUser->insert(array(
                        'parent_id' => $_SESSION['droppy_premium'],
                        'email' => $email,
                        'password' => hash('sha512', $password),
                        'ip' => '',
                        'sub_id' => $cur_user_sub,
                        'status' => 'ready'
                    ));

                    echo 'success';
                }
            }
            else
            {
                echo 'email';
            }
        }
        else
        {
            echo 'fields';
        }
    }

    if($_POST['action'] == 'settings_general' && $this->session->userdata('admin')) {
        if(empty($_POST['expire_time'])) {
            $expire = 0;
        }
        elseif(count($_POST['expire_time']) > 1) {
            $expire = implode(',', $_POST['expire_time']);
        } else {
            $expire = $_POST['expire_time'][0];
        }

        $settings = array(
            'item_name' => $_POST['item_name'],
            'subscription_desc' => $_POST['subscription_description'],
            'currency' => $_POST['currency'],
            'sub_price' => $_POST['price'],
            'recur_time' => $_POST['recurring_time'],
            'recur_freq' => $_POST['recurring_freq'],
            'max_fails' => $_POST['max_fails'],
            'max_size' => $_POST['max_size'],
            'password_enabled' => $_POST['password_enabled'],
            'expire_time' => $expire,
            'ad_enabled' => $_POST['ad_enabled'],
            'logo_url' => $_POST['logo_paypal'],
            'sub_cancel_n_email' => $_POST['sub_cancel_n_email'],
            'sub_cancel_n_subject' => $_POST['sub_cancel_n_subject'],
            'sub_cancel_e_subject' => $_POST['sub_cancel_e_subject'],
            'sub_cancel_e_email' => $_POST['sub_cancel_e_email'],
            'new_sub_subject' => $_POST['new_sub_subject'],
            'new_sub_email' => $_POST['new_sub_email'],
            'sus_email_sub' => $_POST['sus_email_sub'],
            'sus_email' => $_POST['sus_email_sub'],
            'payment_failed_sub' => $_POST['payment_failed_sub'],
            'payment_failed_email' => $_POST['payment_failed_email'],
            'forgot_pass_subject' => $_POST['forgot_pass_sub'],
            'stripe_key' => $_POST['stripe_key'],
            'payment_gateway' => $_POST['payment_gateway'],
            'enable_vat' => $_POST['enable_vat'],
            'enable_address' =>  $_POST['enable_address'],
            'enable_multi_user' =>  $_POST['enable_multi_user']
        );

        if($_POST['payment_gateway'] == 'stripe') {
            $stripe = new StripeClient(
                $_POST['stripe_key']
            );

            if(empty($premium_settings['stripe_product'])) {
                $response = $stripe->products->create([
                    'name' => $_POST['item_name'],
                    'description' => $_POST['subscription_description']
                ]);
                $settings['stripe_product'] = $response['id'];
            }
            elseif($_POST['item_name'] != $premium_settings['item_name'] || $_POST['subscription_description'] != $premium_settings['subscription_desc'])
            {
                $stripe->products->update(
                    $premium_settings['stripe_product'],
                    [
                        'name' => $_POST['item_name'],
                        'description' => $_POST['subscription_description']
                    ]
                );
            }

            if(empty($premium_settings['stripe_webhook'])) {
                $webhook = $stripe->webhookEndpoints->create([
                    'url' => $this->config->item('site_url') . 'page/stripe',
                    'enabled_events' => [
                        'checkout.session.completed',
                        'charge.failed',
                        'charge.succeeded',
                        'customer.subscription.created',
                        'customer.subscription.deleted',
                        'customer.subscription.updated'
                    ],
                ]);

                $settings['stripe_webhook'] = $webhook['id'];
            }

            /*if(empty($premium_settings['stripe_price'])) {
                $unit_amount = $_POST['price'] * 100;
                $response = $stripe->prices->create([
                    'unit_amount' => $unit_amount,
                    'currency' => $_POST['currency'],
                    'recurring' => ['interval' => strtolower($_POST['recurring_time'])],
                    'product' => $settings['stripe_product'],
                ]);
                $settings['stripe_price'] = $response['id'];
            }
            elseif(array_diff(array($_POST['price'],$_POST['currency'],$_POST['recurring_time']), array($premium_settings['sub_price'],$premium_settings['currency'],$premium_settings['recur_time'])))
            {
                $response = $stripe->prices->update(
                    $premium_settings['stripe_price'],
                    ['active' => false]
                );

                $unit_amount = (str_replace(',', '.', $_POST['price']) * 100);
                $response = $stripe->prices->create(
                    [
                        'unit_amount' => $unit_amount,
                        'currency' => $_POST['currency'],
                        'recurring' => ['interval' => strtolower($_POST['recurring_time'])],
                        'product' => $premium_settings['stripe_product'],
                    ]
                );
                $settings['stripe_price'] = $response['id'];
            }*/
        }
        else
        {
            $paypal_settings = [
                'username_api' => $_POST['username_api'],
                'password_api' => $_POST['password_api'],
                'signature_api' => $_POST['signature_api']
            ];
            $settings = array_merge($settings, $paypal_settings);
        }

        $clsSettings->update($settings);

        header('Location: '. $_POST['goback']);
    }
    if($_POST['action'] == 'delete_user' && $this->session->userdata('admin')) {
        $id = $_POST['id'];
        $return = $_POST['return'];

        $clsUser->deleteByID($id);

        header('Location: ' . $return);
    }
    if($_POST['action'] == 'cancel_subscription' && $this->session->userdata('admin')) {
        require_once dirname(__FILE__) . '/gateway/paypal/cancel.php';

        $id = $_POST['id'];
        $return = $_POST['return'];

        $sub_info = $clsSubs->getBySubID($id);
        if($sub_info !== false) {
            if($sub_info['payment'] == 'free') {
                $clsSubs->updateByID(array('status' => 'canceled_end'), $sub_info['id']);
            }
            else
            {
                $clsPaypal->change_subscription_status($sub_info['paypal_id'], 'Cancel');
            }
        }

        header('Location: ' . $return);
    }
    if($_POST['action'] == 'activate_sub' && $this->session->userdata('admin')) {
        $id = $_POST['id'];
        $return = $_POST['return'];

        $info = $clsSubs->getBySubID($id);
        if($info !== false) {
            $clsSubs->updateBySubID(array('status' => 'active'), $id);
            $clsPaypal->change_subscription_status($info['paypal_id'], 'Reactivate');
        }

        header('Location: ' . $return);
    }
    if($_POST['action'] == 'add_usersub' && $this->session->userdata('admin')) {
        //Get post data
        $email          = $_POST['email'];
        $fullname       = $_POST['fullname'];
        $company_name   = $_POST['company'];
        $password       = hash('sha512', $_POST['password']);
        $next_date      = strtotime($_POST['expiry']);
        $sub_id         = uniqid(); //Create unique id
        $date           = time(); //Get the current time.

        $clsUser->insert(array(
            'email' => $email,
            'password' => $password,
            'ip' => '0.0.0.0',
            'sub_id' => $sub_id,
            'status' => 'ready'
        ));

        $clsSubs->insert(array(
            'sub_id' => $sub_id,
            'email' => $email,
            'name' => $fullname,
            'company' => $company_name,
            'payment' => 'free',
            'last_date' => '',
            'next_date' => $next_date,
            'time' => $date,
            'status' => 'active',
            'paypal_id' => '',
            'paypal_payerid' => $sub_id,
            'paypal_email' => $email,
            'paypal_status' => 'verified',
            'paypal_name' => $fullname,
            'paypal_country' => 'US',
            'paypal_phone' => '',
            'paypal_ordertime' => ''
        ));

        header('Location: '.$_POST['goback'] . '&p=subs');
    }
    if($_POST['action'] == 'add_voucher' && $this->session->userdata('admin')) {
        //Get post data
        $code           = strtoupper($_POST['code']);
        $discount       = $_POST['discount'];
        $discount_perc  = $_POST['discount_percentage'];

        if(!empty($discount)) {
            //Discount value is set
            $discount_type = 'value';

            $clsVoucher->insert(array(
                'code' => $code,
                'discount_type' => $discount_type,
                'discount_value' => $discount
            ));
        }
        elseif(!empty($discount_perc)) {
            //Discount percentage is set
            $discount_type = 'percentage';

            $clsVoucher->insert(array(
                'code' => $code,
                'discount_type' => $discount_type,
                'discount_percentage' => $discount_perc
            ));
        }

        header('Location: '.$_POST['goback']);
    }
}

if(isset($_GET['action']))
{
    if($_GET['action'] == 'payment_confirm') {
        require_once dirname(__FILE__) . '/gateway/paypal/order_confirm.php';
    }

    if($_GET['action'] == 'payment_review') {
        require_once dirname(__FILE__) . '/gateway/paypal/review.php';
    }

    //Checks if the checkout has been canceld
    if($_GET['action'] == 'paypal_payment_canceled')
    {
        $token = $_GET['token'];

        $info = $clsSubs->getByToken($token);
        if($info !== false)
        {
            $subid = $info['sub_id'];
            $user_email = $info['email'];

            $clsSubs->updateBySubID(array('status' => 'canceled'), $subid);
            $clsUser->deleteBySubID($subid);
        }

        // Redirect to cancel page
        header('Location: ' . $droppy_settings['site_url'] . 'page/premium?payment=canceled_user');
    }
    if($_GET['action'] == 'cancel')
    {
        $id = $_GET['id'];
        $type = $_GET['type'];

        $sub_info = $clsSubs->getBySubID($id);
        if($sub_info !== false) {
            if ($sub_info['payment'] == 'free') {
                $clsSubs->updateByID(array('status' => 'canceled_end'), $sub_info['id']);
            } elseif ($sub_info['payment'] == 'paypal') {
                $clsPaypal->change_subscription_status($sub_info['paypal_id'], 'Cancel');
            } elseif ($sub_info['payment'] == 'stripe') {
                $stripe = new StripeClient(
                    $premium_settings['stripe_key']
                );
                $stripe->subscriptions->cancel($sub_info['stripe_id']);
            }
        }
        header('Location: '.$droppy_settings['site_url'].'?goto=custom_account');
    }
    if($_GET['action'] == 'delete_voucher' && $this->session->userdata('admin')) {
        $clsVoucher->deleteByID($_GET['id']);

        header('Location: '.$_SESSION['goback']);
    }
    if($_GET['action'] == 'logout')
    {
        unset($_SESSION['droppy_premium']);
        unset($_SESSION['droppy_premium_email']);

        header('Location: '.$droppy_settings['site_url'].'?goto=custom_account');
    }
    if($_GET['action'] == 'delete_sub_user')
    {
        $id = $_GET['user'];

        $user = $clsUser->getByID($id);

        if($user !== false) {
            if($user['parent_id'] == $_SESSION['droppy_premium']) {
                $clsUser->deleteByID($id);
                header('Location: '.$droppy_settings['site_url'].'?goto=custom_account&tab=users');
            }
        }
    }
    if($_GET['action'] == 'uploads')
    {
        require_once dirname(__FILE__) . '/views/themes/modern/tabs/uploads.php';
        exit;
    }
}