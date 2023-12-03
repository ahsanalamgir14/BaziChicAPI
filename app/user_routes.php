<?php
$app->post('/login_controller', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    $email = "";
    if (null !== $request->getParam('email')) {
        $email = $request->getParam('email');
    }
    $password = "";
    if (null !== $request->getParam('password')) {
        $password = $request->getParam('password');
    }

    $output = array();
    $output['error'] = true;
    $output['message'] = 'We are authenticating your access.';

    if (empty($email)) {
        $output['error'] = true;
        $output['message'] = 'Please enter e-mail address.';
        echoRespnse(200, $output);
        exit;
    }

    if (empty($password)) {
        $output['error'] = true;
        $output['message'] = 'Please enter password.';
        echoRespnse(200, $output);
        exit;
    }

    if (strlen($password) < 6) {
        $output['error'] = true;
        $output['message'] = 'Password must be at least 6 characters.';
        echoRespnse(200, $output);
        exit;
    }

    $userCRUD = new UserCRUD(getConnection());
    $date_created = date('Y-m-d H:i:s');
    // check for correct email and password
    if ($userCRUD->checkLogin($email, $password)) {
        $userData = $userCRUD->getUserByEmail($email);
        if ($userData !== null) {
            $userData = $userCRUD->getUserByEmail($email);
            $current_user = $userData["id"];
            $userCRUD->updateLastActive($current_user, $date_created);
            $output['userData'] = getUserBasicDetails($current_user);
            /*********************************/
            $output['error'] = false;
            $output['message'] = 'Welcome back ' . $userData['first_name'] . '! Please wait while we are taking you to your dashboard.';
            $output['redirection'] = "";
            $output['active'] = false;
            $numActivePlans = $membershipCRUD->getNumMyActivePlan($userData["id"]);
            if ($numActivePlans > 0) {
                $output['active'] = true;
                if (true) {
                    $output['redirection'] = "";
                }
            } else {
                if ($userData["role_id"] != 1) {
                    $output['redirection'] = "subscription-plans";
                } else {
                    $output['redirection'] = "dashboard";
                }
            }
        } else {
            // unexpected.
            $output['error'] = true;
            $output['message'] = 'Unable to authenticate this account. Please try again.';
        }
    } else {
        // user credentials are wrong
        $output['error'] = true;
        $output['message'] = 'Either email or password you entered is incorrect.';
    }
    echoRespnse(200, $output);
});

/*********************** RESET PASSWORD *********************/
$app->post('/users/reset_password', function ($request, $respo, $args) use ($app) {
    $output = array();
    require_once ("dbmodels/user.crud.php");
    require_once 'dbmodels/PassHash.php';
    require_once 'dbmodels/utils.crud.php';
    $utilCRUD = new UtilCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $helper = new Helper();
    $email = $request->getParam('email');
    if (!$userCRUD->isEmailInDatabase($email)) {
        $output["error"] = true;
        $output["message"] = "This email is not registered. Please check and try again.";
        echoRespnse(200, $output);
        return;
    }
    $thisUser = $userCRUD->getUserByEmail($email);
    if ($thisUser != null) {
        $user_id = $thisUser["id"];
        $password = $utilCRUD->createNewUsername(8);
        $name = $userCRUD->getNameByID($user_id);

        $password_hash = PassHash::hash($password);
        $res = $userCRUD->updatePassword($user_id, $password_hash);
        if ($res) {
            $output["error"] = false;
            $output["message"] = "We have reset the password for your account. Please check your email for further instuctions. Also check your SPAM folder before you reset again.";

            $message = '<html><head><title>Hello ' . $name . '</title></head><body><h4>Hello ' . $name . '</h4><h4>We have reset the password for your account. Your new password is ' . $password . '. Use this password to access your account.</h4>';

            $message .= '<br><br>';
            $message .= '<h4><a href="https://bazichic.com/login">Click here to Sign In</a></h4>.';
            $message .= '<br><br>';
            $message .= '<a href="https://bazichic.com" class="logo">
					<img src="https://bazichic.com/images/logo.png" style="height:60px;" alt="Bazichic">
				</a>';
            $message .= '<br><br>';
            $message .= '<h4>For more details, Visit our official site at <a href="http://bazichic.com">www.bazichic.com</a></h4>.';
            $message .= '</body></html>';
            try {
                $to = 'customer_support@bazichic.com';
                $subject = 'Bazichic Account Password Recovery';
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . $to . '' . "\r\n" .
                'Reply-To: ' . $to . '' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
                $emailResult = $helper->sendEmail($email, $subject, $message);
                /*
            if(!$emailResult["error"]){
            $output["message"] .= " We have sent further instructions to your registered email address.";
            }else{
            $output["message"] .= " Please check your email in a moment.";
            }*/
            } catch (Exception $e) {
                $output["message"] .= "Exception with Mailer: " . $e->getMessage();
            }

            //Also try util sender

        } else {
            $output["error"] = true;
            $output["message"] = "Oops! An error occurred updating Password. Try again.";
        }
    } else {
        $output["error"] = true;
        $output["message"] = "If you are registered with this email you will shortly receive further instructions.";
        echoRespnse(200, $output);
        return;
    }
    // echo json response
    echoRespnse(200, $output);
});

/********************  REGISTER A NEW USER **********************/
$app->post('/registration', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once 'dbmodels/PassHash.php';
    require_once 'dbmodels/utils.crud.php';
    require_once ("dbmodels/referral.crud.php");
    require_once ("dbmodels/notification.crud.php");
    require_once ("dbmodels/activity.crud.php");
    require_once ("dbmodels/user_membership.crud.php");
    require_once ("dbmodels/reward_points.crud.php");
    $helper = new Helper();
    $membershipCRUD = new MembershipCRUD(getConnection());
    $referralCRUD = new ReferralCRUD(getConnection());
    $notiCRUD = new NotificationCRUD(getConnection());
    $activityCRUD = new ActivityCRUD(getConnection());
    $referralCRUD = new ReferralCRUD(getConnection());
    $rewardPointCRUD = new RewardPointCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $output = array();
    $output["note"] = "";
    // reading post parameters
	$adminMode = false;
    $first_name = "";
    $last_name = "";
    $email = "";
    $dob = "";
    $country = "";
    $referral_code = "";
	$ref_user_id = 0;
	$type = "Customer";
    $source = "";
	if (null !== $request->getParam('first_name')) {
        $first_name = $request->getParam('first_name');
    }
	if (null !== $request->getParam('last_name')) {
        $last_name = $request->getParam('last_name');
    }
	if (null !== $request->getParam('email')) {
        $email = $request->getParam('email');
    }
	if (null !== $request->getParam('dob')) {
        $dob = $request->getParam('dob');
    }
	if (null !== $request->getParam('country')) {
        $country = $request->getParam('country');
    }
    if (null !== $request->getParam('referral_code')) {
        $referral_code = $request->getParam('referral_code');
    }
   
    if (!empty($referral_code)) {
        $ref_user_id = $referralCRUD->getUserID($referral_code);
    }
    if (null !== $request->getParam('type')) {
        $type = $request->getParam('type');
    }
	if (null !== $request->getParam('adminMode')) {
        $adminMode = true;
    }

    if (empty($first_name)) {
        $output["error"] = true;
        $output["message"] = "First name can not be empty.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($last_name)) {
        $output["error"] = true;
        $output["message"] = "Please enter a last name.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($email)) {
        $output["error"] = true;
        $output["message"] = "Let us know your e-mail address.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($dob)) {
        $output["error"] = true;
        $output["message"] = "Let us know your birth date.";
        echoRespnse(200, $output);
        exit;
    }
	if(!isDateFormatValid($dob)){
		$output["error"] = true;
		$output["dob"] = $dob;
		$output["test1"] = isDateTimeFormatValid($dob);
		$output["test2"] = isDateFormatValid($dob);
		$output["message"] = "Please select valid date of birth.";
		echoRespnse(200, $output);
		exit;
	}

    if (empty($country)) {
        $output["error"] = true;
        $output["message"] = "Select the country of your residence.";
        echoRespnse(200, $output);
        exit;
    }
    if (null !== $request->getParam('source')) {
        $source = $request->getParam('source');
    }
    if (null !== $request->getParam('autogen_pass') && $request->getParam('autogen_pass') == true) {
        $password = $utilCRUD->createNewUsername(8);
    } else {
        $password = "";
        $confirmPassword = "";
        $password = $request->getParam('password');
        $confirmPassword = $request->getParam('confirmPassword');
        if (empty($password)) {
            $output['error'] = true;
            $output['message'] = 'Please set a password for this account.';
            echoRespnse(200, $output);
            return;
        }
        if (strlen($password) < 6) {
            $output['error'] = true;
            $output['message'] = 'Your password must be at least 6 characters. Please enter a strong password.';
            echoRespnse(200, $output);
            return;
        }
        if (empty($confirmPassword)) {
            $output['error'] = true;
            $output['message'] = 'Please repeat your password.';
            echoRespnse(200, $output);
            return;
        }
        if (strlen($confirmPassword) < 6) {
            $output['error'] = true;
            $output['message'] = 'Your password must be at least 6 characters. Please review the repeated password.';
            echoRespnse(200, $output);
            return;
        }
        if ($password !== $confirmPassword) {
            $output['error'] = true;
            $output['message'] = 'Your password did not match.';
            echoRespnse(200, $output);
            return;
        }
    }
    $phone = "";
    $status = "Pending";
    $date_created = date('Y-m-d H:i:s');
    $role_id = 2;
    $description = "";
    $password_hash = PassHash::hash($password);
    $api_key = $utilCRUD->generateApiKey();
    $user_name = $utilCRUD->createNewUsername(8);
    $res = $userCRUD->register($first_name, $last_name, $user_name, $type, $phone, $email, $password_hash, $dob, $country, $description, $date_created, $status, $role_id, $ref_user_id, $referral_code, $api_key);
    if ($res["code"] == INSERT_SUCCESS) {
        $output["error"] = false;
		if($adminMode){
			$output["message"] = "New account for ".$first_name." ".$last_name." has been created successfully.";
		}else{
			$output["message"] = "Your new account has been created successfully.";
		}
        if(!empty($source)){
            $userCRUD->updateRegSource($source);
        }
        $user_id = $res["id"];
        $output["id"] = $user_id;
        $output["user_name"] = $user_name;

        /********* FOR WEB ***********/
        $userData = getUserBasicDetails($user_id);
        $output['userData'] = $userData;
        /********* Notify now and send email ********/
        try {
            $title = 'Welcome to BaziChic';
            $message = 'Hi ' . $first_name . '! Thanks for registering your account with BaziChic.';
            $noti_res = $notiCRUD->create(1, $user_id, $title, $message, $user_name, $data_title = "WelcomeSelf", $status = "Pending", $date_created);
            if ($noti_res["code"] == INSERT_SUCCESS) {
                $output["note"] .= " Notified successfully.";
            }

            /********* EMAIL NOTIFICATION **********/
            if (null !== $request->getParam('notify_account')) {
                $subject = "Welcome to BaziChic";
                $body = '<h4>Welcome  ' . $first_name . '!</h4>';
                if (null !== $request->getParam('moderator')) {
                    $body .= '<h4>A new account has been registered for you at BaziChic.</h4>';
                } else {
                    $body .= '<h4>Your new BaziChic account has been created successfully. Thanks for registering an account with us.</h4>';
                }
                $body .= '<h4>Use the autogenerated password - ' . $password . ' to access your account. Please update your password to keep the account safe once you verify your registered e-mail. </h4>';
                $body .= '<br>';
                $body .= getEmailFooter();
                $emailResult = $helper->sendEmail($email, $subject, $body);
                if (!$emailResult["error"]) {
                    $output["message"] .= " We have sent further instructions to your registered email address.";
                } else {
                    //$output["message"] = "";
                }
            }
            /********* EMAIL SENT **********/

        } catch (Exception $e) {
            $output["note"] .= " Error notifying." . $e->getMessage();
        }
        /********* Notify Done ********/

        /**** Log Activity ********/
        try {
            $title = "New Registration - " . $first_name . " " . $last_name;
            $activity = $first_name . " " . $last_name . " from " . $country . " registered an account.";
            $activity_res = $activityCRUD->create(1, $title, $activity, $user_name, $data_title = "Registration", 0, $date_created);
            if ($activity_res["code"] == INSERT_SUCCESS) {
                $output["note"] .= " Logged new accunt activity.";
            }} catch (Exception $e) {
            $output["note"] .= "Error logging activity. " . $e->getMessage();
        }
        /**** Log Activity ********/

        /******* Send OTP *******/
        try{
			$newOtp = $utilCRUD->generateNewOTP();	
			$emailToName = $first_name." ".$last_name;
			notifyUserWithVerificationCode($email, $emailToName, $newOtp, 0);
		}catch(Exception $e)
        {
		   $output["verifier"] = "Error sending verification e-mail: " . $e->getMessage();
        }
        /**************/

        /********** PROCESS REFERRAL ***********/
        try {
            if (is_numeric($ref_user_id) && $ref_user_id > 0) {
                if ($referralCRUD->isReferralCodeExist($referral_code)) {
                    //Update Referral
                    $ref_user_id = $referralCRUD->getUserID($referral_code);
                    $date_updated = date('Y-m-d H:i:s');
                    $referralCRUD->updateReferral($referral_code, $status = "Used", $date_updated);
                    $userCRUD->updateFather($user_id, $ref_user_id);
                    $referredUser = $userCRUD->getNameByID($user_id);
                    $referringUser = $userCRUD->getNameByID($ref_user_id);
                    $output['message'] .= ' The Referral Code has been applied.';
                    $output["note"] .= "Referrals Updated.";
                    $ref_message = "Your referral code " . $referral_code . " from " . $referringUser . " has been applied successfully.";
                    //Notify New User
                    if (!empty($referredUser)) {
                        $notiCRUD->create($ref_user_id, $user_id, "Referral Code Applied", $ref_message, $user_id, $data_title = "ReferralApplied", $status = "Pending", $date_created);
                        $output["note"] .= " Referred user Notified. ";
                    }
                    //Notify Referrering User
                    if (!empty($referringUser)) {
                        $con_message = "Congrats! You have a new connection. " . $referredUser . " just used your referral code to register an account.";
                        $notiCRUD->create($user_id, $ref_user_id, "New Connection", $con_message, $user_id, $data_title = "ReferralApplied", $status = "Pending", $date_created);
                        $output["note"] .= " Referring User notified.";
                    }
                    //Process Reward Points
                    $note = $referredUser . " registered a new account using code " . $referral_code . ".";
                    $rewarding_res = $rewardPointCRUD->create($ref_user_id, $points = 5, "Refferal Bonus", $note, "Complete", $date_created);
                    if ($rewarding_res["code"] == INSERT_SUCCESS) {
                        $output["note"] .= " Reward point processed successfully.";
                    } else {
                        $output["note"] .= " Failed to process Reward point. " . $rewarding_res["msg"];
                    }
                } else {
                    //$output['error'] = true;
                    $output['message'] .= 'The Referral Code you applied was invalid.';
                    $output["note"] .= "Invalid Referral Code.";
                }
            }
        } catch (Exception $e) {
            $output["note"] .= " ERROR DURING REFERRAL PROCESS: " . $e->getMessage();
        }
        /********** REFERRAL PROCESSED ***********/

    } else if ($res["code"] == INSERT_FAILURE) {
        $output["error"] = true;
        $output["message"] = "Oops! An error occurred while registering user";
    } else if ($res["code"] == ALREADY_EXIST) {
        $output["error"] = true;
        $output["message"] = "The email is already registered.";
    }
    // echo json response
    echoRespnse(200, $output);
});

/**************** GET PROFILE API ******************/
$app->get('/profile[/{username}]', function ($request, $output, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/document_save.crud.php");
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    require_once ("dbmodels/document_reviews.crud.php");
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once ("dbmodels/document_likes.crud.php");
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    $notificationCRUD = new DocumentSaveCRUD(getConnection());
	require_once ("dbmodels/free_trials.crud.php");
    $trialsCRUD = new FreeTrialsCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    $output["message"] = "";
    $output["adminMode"] = false;
    /********** REQUEST AUTH CHECK  ***********/
    $callerInfo = getRequestingAgent($request);
    if (!$callerInfo["error"]) {
        $agentRole = $callerInfo["user_info"]["role_id"];
        if($agentRole == 1){
            $output["adminMode"] = true;
        }
        $output["callerInfo"] = true;
    } else {
        $output['error'] = true;
        $output['message'] = 'Invalid request signature.';
        echoRespnse(200, $output);
    }
    /********** REQUEST AUTH CHECK  ***********/

    $username = $request->getAttribute('username');
    if (empty($username)) {
        $output["error"] = true;
        $output["message"] = "Invalid request params. Please try again.";
        echoRespnse(200, $output);
        exit;
    }
    $requestedUser = $userCRUD->getByUsername($username);
    if ($requestedUser !== null && $requestedUser["id"] > 0) {
        $output["error"] = false;
        $output['item'] = getUserFullDetails($requestedUser["id"]);
        //$output['item']['notifications'] = array();
        //$output['item']['notifications'] = $notificationCRUD->getNumAllMySaves($requestedUser["id"]);

        $currentPlan = $membershipCRUD->getMyActivePlan($requestedUser["id"]);
        if ($currentPlan !== null) {
            $output['item']['currentPlan'] = getSubscriptionItemDetail($currentPlan["id"]);
        }

        $output['item']['subscriptions'] = array();
        $subscriptionsHistory = $membershipCRUD->getMySubscriptionHistory($requestedUser["id"]);
        //Format the data values
        if (count($subscriptionsHistory) > 0) {
            foreach ($subscriptionsHistory as $thisrow) {
                $tmp = getSubscriptionItemDetail($thisrow["id"]);
                array_push($output['item']['subscriptions'], $tmp);
            }
        }

		//Free Trial
		// if ($trialsCRUD->getNumMyActivePlan($requestedUser["id"]) > 0) {
		// 	$activeTrial = $trialsCRUD->getMyActivePlan($requestedUser["id"]);
		// 	$output['item']["activeTrial"] = getTrialDetails($activeTrial["id"]);
		// }
		$output['item']['hasActiveTrial'] = $trialsCRUD->getNumMyActivePlan($requestedUser["id"]) > 0;
		$output['item']['freeTrials'] = array();
		$allFreeTrials = $trialsCRUD->getMySubscriptionHistory($requestedUser["id"]);
		//$allFreeTrials = $trialsCRUD->getAllPlans();
		if (count($allFreeTrials) > 0) {
			foreach ($allFreeTrials as $trial) {
				$tmp = getTrialDetails($trial["id"]);
				array_push($output['item']['freeTrials'], $tmp);
			}
		}

        $output['item']['saves'] = array();
        $output['item']['saves'] = $notificationCRUD->getAllMySaves($requestedUser["id"]);
    } else {
        $output["error"] = true;
        $output["message"] = "User account is not found.";
        echoRespnse(200, $output);
        exit;
    }
    echoRespnse(200, $output);
})->add($authenticate);

/****** UPDATE USER API *****/
$app->post('/users/update', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    $id = $request->getParam('user_id');
    $first_name = $request->getParam('first_name');
    $last_name = $request->getParam('last_name');
    $email = $request->getParam('email');
    $description = $request->getParam('description');
    $country = $request->getParam('country');
    $dob = $request->getParam('dob');
    $latitude = "";
    $longitude = "";
    $image = "";
    $paypal = "";
    $date_updated = date('Y-m-d H:i:s');

    if (empty($first_name)) {
        $output["error"] = true;
        $output["message"] = "First name can not be empty.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($last_name)) {
        $output["error"] = true;
        $output["message"] = "Last name can not be empty.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($email)) {
        $output["error"] = true;
        $output["message"] = "Let us know your email.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($dob)) {
        $output["error"] = true;
        $output["message"] = "Let us know your birth date.";
        echoRespnse(200, $output);
        exit;
    }

    if (empty($country)) {
        $output["error"] = true;
        $output["message"] = "Select your country of residence.";
        echoRespnse(200, $output);
        exit;
    }

    $admin_mode = 0;
    $admin_mode = $request->getParam('admin_mode');
    if (empty($admin_mode)) {
        $admin_mode = 0;
    }
    if ($admin_mode == 1) {
        /******** VERIFY THE REQUESTING AGENT IN VIEW **********/
        if (isset($_SESSION["api_key"])) {
            $user_info = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
            if ($user_info !== null && $user_info["role_id"] == 1) {
                //Good to go
            } else {
                $output['error'] = true;
                $output['message'] = 'You are not authorized to perform this action';
                echoRespnse(200, $output);
                return;
            }
        } else {
            $output['error'] = true;
            $output['message'] = 'You are not authorized to perform this action';
            echoRespnse(200, $output);
            return;
        }
        /******** VERIFY THE REQUESTING AGENT **********/
    }

    /********* START PROFILE PIC UPLOAD **********/
    $file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $files = $request->getUploadedFiles();
    if (!empty($files['profile_image'])) {
        try {
            $newfile = $files['profile_image'];
            $cover_file_type = "Unknown";
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $uploadCoverName = $newfile->getClientFilename();
                $uploadCoverName = explode(".", $uploadCoverName);
                $ext = array_pop($uploadCoverName);
                $ext = strtolower($ext);
                $uploadCoverName = $id . "." . $ext;

                $file_size = $newfile->getSize();
                $cover_file_type = $newfile->getClientMediaType();
                if (!$cover_file_type == "image/jpg" || !$cover_file_type == "image/jpeg" || !$cover_file_type == "image/jpeg") {
                    $output['error'] = true;
                    $output['message'] = 'Please upload a png, jpg or jpeg image file as your profile photo.';
                    echoRespnse(200, $output);
                    return;
                }

                if ($cover_file_type > 500000) {
                    $output['error'] = true;
                    $output['message'] = 'Upload a profile photo of size less than 500 KB.';
                    echoRespnse(200, $output);
                    return;
                }

                $fileToTest = "uploads/images/users/$uploadCoverName";
                if (file_exists($fileToTest)) {
                    //chmod('your-filename.ext',0755);
                    unlink($fileToTest);
                }
                $newfile->moveTo($fileToTest);
                $userCRUD->updateImage($id, $uploadCoverName);
                if ($admin_mode == 0) {
                    $_SESSION['user_image'] = $uploadCoverName;
                }
            }
        } catch (Exception $e) {
            $output["error"] = true;
            $output["message"] = "Failed to upload profile photo. " . $e->getMessage();
            echoRespnse(200, $output);
            exit;
        }
    }
/********* END OF COVER UPLOAD **********/

    $type = $userCRUD->getUserType($id);
    if (null !== $request->getParam('type')) {
        $type = $request->getParam('type');
    }

    $res = $userCRUD->update($id, $first_name, $last_name, $type, $country, $dob, $latitude, $longitude, $email, $description, $date_updated);
    if (!$res["error"]) {
        $output["error"] = false;
        $output["admin_mode"] = $admin_mode;

        if (null !== $request->getParam('paypal')) {
            $paypal = $request->getParam('paypal');
            if (!empty($paypal)) {
                $userCRUD->updatePaypal($id, $paypal);
            }
        }

        $output["message"] = "Your profile has been updated successfully.";
        if ($admin_mode == 1) {
            $output["message"] = $first_name . "'s profile has been updated successfully.";
        }
        $output["id"] = $id;
        if ($admin_mode == 0) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['userID'] = $id;
        }
        echoRespnse(200, $output);
    } else {
        $output["error"] = true;
        $output["message"] = "Failed to update profile. Please try again." . $res["message"];
        echoRespnse(200, $output);
    }
})->add($authenticate);

/*********************** UPDATE PASSWORD *********************/
$app->post('/users/creds/update', function ($request, $respo, $args) use ($app) {
    $output = array();
    require_once ("dbmodels/user.crud.php");
    require_once 'dbmodels/PassHash.php';
    $userCRUD = new UserCRUD(getConnection());

    $user_id = $request->getParam('member_id');
    $password = $request->getParam('pass1');
    $password2 = $request->getParam('pass2');
    $old_password = $request->getParam('old_password');

    if ($password !== $password2) {
        $output["error"] = true;
        $output["message"] = "Your new password did not match.";
        echoRespnse(200, $output);
        return;
    }
    $email = $userCRUD->getEmail($user_id);
    if ($userCRUD->checkLogin($email, $old_password)) {
        $password_hash = PassHash::hash($password);
        $res = $userCRUD->updatePassword($user_id, $password_hash);
        if ($res) {
            $output["error"] = false;
            $output["message"] = "Your password has been updated successfully.";
        } else {
            $output["error"] = true;
            $output["message"] = "Oops! An error occurred updating Password. Try again.";
        }
    } else {
        $output["error"] = true;
        $output["message"] = "Your current password did not match. Please try again.";
    }
    // echo json response
    echoRespnse(200, $output);
})->add($authenticate);

/******** DELETE USER *********/
$app->post('/users/delete', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    $id = 0;
    if (null !== $request->getParam('user_id')) {
        $id = $request->getParam('user_id');
    }

    if (empty($id) || $id <= 0) {
        $output["error"] = true;
        $output["message"] = "Invalid User ID.";
        echoRespnse(200, $output);
        exit;
    }

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $agentID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
        /******** VERIFY IF NON ADMIN  **********/
        if ($agentRole == 1) {
            $res = $userCRUD->delete($id);
            if ($res) {
                $output["error"] = false;
                $output["message"] = "User profile has been deleted successfully. ";
                $output["id"] = $id;
                echoRespnse(200, $output);
            } else {
                $output["error"] = true;
                $output["message"] = "Failed to delete user. Please try again.";
                echoRespnse(200, $output);
            }
        }
        /******** VERIFY IF NON ADMIN  **********/
    } else {
        $output["error"] = true;
        $output["message"] = "We could not authenticate this request.";
        $output["agentValidationError"] = $agentValidator;
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
})->add($authenticate);