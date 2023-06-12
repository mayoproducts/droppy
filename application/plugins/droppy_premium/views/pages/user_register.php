<?php

require_once dirname(__FILE__) . '/../../autoloader.php';

$clsSettings = new PremiumSettings();

$premium_settings = $clsSettings->getSettings();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set base url -->
    <base href="<?php echo base_url() ?>">

    <title><?php echo $this->config->item('site_title'); ?> - <?php echo lang('premium_go_pro'); ?></title>
    <meta name="description" content="<?php echo lang('premium_go_pro'); ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo $this->config->item('favicon_path') ?>"/>

    <!-- Bootstrap itself -->
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <!-- Icons -->
    <link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700">
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/plugins/droppy_premium/css/styles.css">

    <!--[if lt IE 9]><script src="assets/plugins/droppy_premium/js/html5shiv.js"></script><![endif]-->
</head>

<body>	
<?php
if(isset($_GET['payment'])) {
    if($_GET['payment'] == 'canceled_user') {
        echo '<div class="alert alert-warning" role="alert" style="text-align: center; margin-bottom: 0;">' . lang('premium_canceled_payment') . '</div>';
    }
    if($_GET['payment'] == 'pending') {
        echo '<div class="alert alert-warning" role="alert" style="text-align: center; margin-bottom: 0;">' . lang('premium_pending_payment') . '</div>';
    }
    if($_GET['payment'] == 'reverse') {
        echo '<div class="alert alert-warning" role="alert" style="text-align: center; margin-bottom: 0;">' . lang('premium_reverse_payment') . '</div>';
    }
    if($_GET['payment'] == 'created') {
        echo '<div class="alert alert-warning" role="alert" style="text-align: center; margin-bottom: 0;">' . lang('premium_received_payment') . '</div>';
    }
	 if($_GET['payment'] == 'free') {
        echo '<div class="alert alert-warning" role="alert" style="text-align: center; margin-bottom: 0;">You have successfully registered. Please check your email for further information.</div>';
    }
}
?>
<!-- Header -->
<header class="header">
    <div class="container">
		<div class="row">
            <div class="col-lg-8 col-lg-push-2 text-center"><BR>
               <a href="https://wetr.in/"><img src="assets/img/logo.png" class="main-logo" width="150"></a>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-lg-push-2 text-center">
                <h1>Register</h1>
                <p class="lead">
                    <?php echo lang('premium_intro_text'); ?>
                </p>
            </div>
        </div>
       
    </div>
</header>
<!-- /Header -->

<!-- Content -->
<main class="content">
 
</main>

<?php
if(isset($_GET['manage']) && isset($_SESSION['droppy_premium'])) :
    $sub_id = $_GET['manage'];
    $session_id = $_SESSION['droppy_premium'];

    $clsUser = new PremiumUser();

    $get_info = $clsUser->getBySubIDAndID($session_id, $sub_id);

    if($get_info !== false):
    ?>
        <footer id="footer" class="jumbotron">
            <section class="container">
                <div class="row">
                    <div class="registerDiv">
                        <div id="errors"></div>
                      
                        <form role="form" method="POST" id="addSubFrom" class="registerForm">
                            <input type="hidden" name="action" value="add_sub">
                            <input type="hidden" name="rd" value="<?php echo base_url(); ?>">
                            <input type="hidden" name="sub_id" value="<?php echo $sub_id; ?>">
                            <div class="form-group">
                                <input type="text" class="form-control input-lg registerInput" value="<?php echo $get_info['email']; ?>" readonly>
                                <br>
                                <h3><?php echo lang('premium_pers_details'); ?>:</h3>
                                <input type="text" class="form-control input-lg registerInput" name="name" id="name" placeholder="<?php echo lang('premium_fullname'); ?>" required="required">
                                <input type="text" class="form-control input-lg registerInput" name="company" id="company" placeholder="<?php echo lang('premium_company'); ?>">
                                  <input type="text" class="form-control input-lg registerInput" name="voucher" id="voucher" placeholder="<?php echo lang('premium_voucher'); ?>">
                                <div class="termsAgree">
                                    <input type="checkbox" name="terms" id="terms" value="true" required="required"> <?php echo lang('premium_agree_terms'); ?>
                                </div>
                            </div>
                            <div class="registerSubmit">
                                <button type="button" id="submitAddSub" class="btn btn-lg btn-info"><?php echo lang('premium_checkout'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </footer>
    <?php
    endif;
else:
?>
    <footer id="footer" class="jumbotron">
        <section class="container">
            <div class="row">
                <div class="registerDiv">
                    <div id="errors"></div>
                    <form role="form" method="POST" id="paymentForm" class="registerForm">
                        <input type="hidden" name="action" value="freeregister">
                        <input type="hidden" name="rd" value="<?php echo base_url(); ?>">
                        <div class="form-group">
                            <h3><?php echo lang('premium_login_details'); ?>:</h3>
                            <input type="text" class="form-control input-lg registerInput" name="email" id="email" placeholder="<?php echo lang('premium_your_email'); ?>" required="required">
                            <input type="password" class="form-control input-lg registerInput" name="password" id="password" placeholder="<?php echo lang('premium_password'); ?>" required="required">
                            <input type="password" class="form-control input-lg registerInput" name="re_password" id="re_password" placeholder="<?php echo lang('premium_password_re'); ?>" required="required">
                            <br>
                            <h3><?php echo lang('premium_pers_details'); ?>:</h3>
                            <input type="text" class="form-control input-lg registerInput" name="name" id="name" placeholder="<?php echo lang('premium_fullname'); ?>" required="required">
                            <input type="text" class="form-control input-lg registerInput" name="company" id="company" placeholder="<?php echo lang('premium_company'); ?>">
							  <input type="text" style="display:none;" class="form-control input-lg registerInput" name="voucher" id="voucher" placeholder="<?php echo lang('premium_voucher'); ?>">

                            <div class="termsAgree">
                                <input type="checkbox" name="terms" id="terms" value="true" required="required"> <?php echo lang('premium_agree_terms'); ?>
                            </div>
                        </div>
                        <div id="paymentFooter">
                                 <button type="button" id="submitPayment" class="btn btn-lg btn-info" style="float: right;"><?php echo lang('premium_register'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </footer>
<?php
endif;
?>

<p class="small text-muted text-center">Copyright &copy; <?php echo date("Y"); ?></p>
<br>
<!-- JavaScript libs are placed at the end of the document so the pages load faster -->
<script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/plugins/droppy_premium/js/template.js"></script>
<script>
    "use strict"
    //Validating and submitting form data
    $(document).ready( function() {
        $('body').on('click', '#submitPayment', function() {
            $('#submitPayment').text('<?php echo lang('premium_processing'); ?>');
            var email 	= $('#email').val();
            var pass 	= $('#password').val();
            var re_pass = $('#re_password').val();
            var name	= $('#name').val();
            var terms 	= $('#terms');
            var voucher = $('#voucher').val();

            if(email == '' || email == null || pass == '' || pass == null || re_pass == '' || re_pass == null || name == '' || name == null || !terms.is(':checked'))
            {
                $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_fill_fields'); ?></div>';
            }
            else
            {
                //Data that will be post to the php file to check if the email already exists in the database
                var dataString = 'action=check_email&email='+email;

                //Ajax post
                $.ajax({
                    type: "POST",
                    url: "",
                    data: dataString,
                    success: function(return_data) {
                        console.log(return_data);

                        //When the email does not exists
                        if(return_data == 1)
                        {
                            if(voucher == '') {
                                //If the password is shorter than 6 characters
                                if (pass.length < 6) {
                                    $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                                    document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_password_longer'); ?></div>';
                                }
                                else {
                                    //If the passwords are the same
                                    if (pass == re_pass) {
                                        //Submit form
                                        document.getElementById('paymentForm').submit();
                                    }
                                    else {
                                        $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                                        document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_password_match'); ?></div>';
                                    }
                                }
                            }
                            else if(voucher != '') {
                                var dataString = 'action=check_voucher&voucher='+voucher;
                                $.ajax({
                                    type: "POST",
                                    url: "",
                                    data: dataString,
                                    success: function (return_data) {
                                        //When the email does not exists
                                        if(return_data == 1) {
                                            //If the password is shorter than 6 characters
                                            if (pass.length < 6) {
                                                $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                                                document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_password_longer'); ?></div>';
                                            }
                                            else {
                                                //If the passwords are the same
                                                if (pass == re_pass) {
                                                    //Submit form
                                                    document.getElementById('paymentForm').submit();
                                                }
                                                else {
                                                    $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                                                    document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_password_match'); ?></div>';
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                                            document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_invalid_voucher'); ?></div>';
                                        }
                                    }
                                });
                            }
                        }
                        if(return_data == 2)
                        {
                            $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                            document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_email_exists'); ?></div>';
                        }
                        if(return_data == 3)
                        {
                            $('#submitPayment').text('<?php echo lang('premium_register'); ?>');
                            document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_invalid_email'); ?></div>';
                        }
                    }
                });
            }
        });
    });
    $(document).ready( function() {
        $('body').on('click', '#submitAddSub', function() {
            $('#submitAddSub').text('<?php echo lang('premium_processing'); ?>');
            var name	= $('#name').val();
            var terms 	= $('#terms');

            if(name == '' || name == null || !terms.is(':checked'))
            {
                $('#submitAddSub').text('<?php echo lang('premium_checkout'); ?>');
                document.getElementById('errors').innerHTML = '<div class="alert alert-warning" role="alert" style="text-align: center;"><?php echo lang('premium_fill_fields'); ?></div>';
            }
            else
            {
                document.getElementById('addSubFrom').submit();
            }
        });
    });

    $("a[href='#footer']").on('click', function() {
      $("html, body").animate({ scrollTop: $(document).height() }, "slow");
      return false;
    });
</script>

</body>
</html>