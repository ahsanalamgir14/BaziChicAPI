<?php
/*********************** VERIFY ACCOUNT VIA EMAIL *********************/
$app->post('/apis/account/resend-verification', function ($request, $response, $args) use ($app)
{
    $output = array();
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $email = $request->getParam('email');
    if (empty($email))
    {
        $output['error'] = true;
        $output['message'] = 'You must enter your registered e-mail address.';
        echoRespnse(200, $output);
        exit;
    }
    if ($userCRUD->isEmailInDatabase($email))
    {
		$userData = $userCRUD->getUserByEmail($email);
		if($userData !== null){
			
	/******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"])
    {
        $agentID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
		/******** CROSS CHECK TOKEN  **********/
        if ($agentID != $userData["id"])
        {
           $output["error"] = true;
                $output["message"] = "You are not authorized to perform this operation.";
                echoRespnse(200, $output);
                exit;
        }
        /******** CROSS CHECK TOKEN  **********/
    }
    else
    {
        $output['error'] = true;
        $output['message'] = 'Invalid request. Please pass your token to proceed.';
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
	
		$userStatus = $userData["status"];
		if($userStatus == "Blocked"){
		$output['error'] = true;
        $output['message'] = 'Your account has been blocked to access BaziChic Services. Please contact your Service Provider for further details.';
        echoRespnse(200, $output);
        exit;
		}
		if($userStatus == "Active"){
		$output['error'] = false;
		$tmp = getUserBasicDetails($userData["id"]);
        $output['userData'] = $tmp;
        $output['message'] = 'Your account has already been verified. Please try signing in using your password.';
        echoRespnse(200, $output);
        exit;
		}
		$numOTPSentToday = $utilCRUD->getNumOTPSentToday($email);
		$output['numOTPSentToday'] = $numOTPSentToday;
		if($numOTPSentToday <= 3){
		$newOtp = $utilCRUD->generateNewOTP();	
		$utilCRUD->sendVerificationOTP($email, $newOtp);
		$output['error'] = false;
        $output['message'] = 'We have sent an OTP to your registered email. Please check your SPAM folder as well.';
		
		try{
			$sendToEmail = $userData["email"];
			$emailToName = $userData["first_name"]." ".$userData["last_name"];
			notifyUserWithVerificationCode($sendToEmail, $emailToName, $newOtp, $numOTPSentToday);
		}catch(Exception $e)
        {
		   $output["notifier"] .= "Error sending e-mail: " . $e->getMessage();
        }
			
		}else{
		$output['error'] = true;
        $output['message'] = 'You have exceeded the daily limit for receiving OTP. Please check your email for last OTP or try again after 24 hours.';
        echoRespnse(200, $output);
        exit;
		}
		}
    }else{
		$output['error'] = true;
        $output['message'] = 'We do not find any account registered with this email.';
        echoRespnse(200, $output);
        return;
	}
    echoRespnse(200, $output);
})->add($authenticate);

/*********************** VERIFY ACCOUNT VIA EMAIL *********************/
$app->post('/apis/account/verify', function ($request, $response, $args) use ($app) {
    $output = array();
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $email = $request->getParam('email');
    $otp = $request->getParam('otp');
    if (empty($otp)) {
        $output['error'] = true;
        $output['message'] = 'Please enter the OTP sent to your registered email.';
        echoRespnse(200, $output);
        exit;
    }

    if (empty($email)) {
        $output['error'] = true;
        $output['message'] = 'You must enter your registered email.';
        echoRespnse(200, $output);
        exit;
    }

    if ($userCRUD->isEmailInDatabase($email)) {
        $userData = $userCRUD->getUserByEmail($email);
        if ($userData !== null) {

            /******** VERIFY THE REQUESTING AGENT **********/
            $agentValidator = getRequestingAgent($request);
            if (!$agentValidator["error"]) {
                $agentID = $agentValidator["user_info"]["id"];
                $agentRole = $agentValidator["user_info"]["role_id"];
                /******** CROSS CHECK TOKEN  **********/
                if ($agentID != $userData["id"]) {
                    //$output["agentID"] = $agentID;
                    //$output["userData"] = $userData;
                    $output["error"] = true;
                    $output["message"] = "You are not authorized to perform this operation.";
                    echoRespnse(200, $output);
                    exit;
                }
                /******** CROSS CHECK TOKEN  **********/
            } else {
                $output['error'] = true;
                $output['message'] = 'Invalid request. Please pass your token to proceed.';
                echoRespnse(200, $output);
                exit;
            }
            /******** VERIFY THE REQUESTING AGENT **********/

            $userStatus = $userData["status"];
            if ($userStatus == "Blocked") {
                $output['error'] = true;
                $output['message'] = 'Your account has been blocked to access BaziChic Services. Please contact us if you have any concern related to your account.';
                echoRespnse(200, $output);
                exit;
            }
            if ($userStatus == "Active") {
                $output['error'] = true;
                $output['message'] = 'Welcome! Your account is now verified.';
                $output['error'] = false;
                $tmp = getUserBasicDetails($userData["id"]);
                $output['userData'] = $tmp;
                notifyUserWithWelcomeMessage($email, $tmp["firstName"]);
                echoRespnse(200, $output);
                exit;
            }
            if ($utilCRUD->getNumUnusedOTPSentTo($email) > 0) {
                $otpEntry = $utilCRUD->getLastOTPEntryFor($email);
                if ($otpEntry !== null) {
                    if ($otpEntry["otp"] == $otp) {
                        $date_now = date('Y-m-d H:i:s');
                        $utilCRUD->setOTPVerified($otpEntry["id"], $date_now);
                        $userCRUD->updateStatus($userData["id"], "Active");
                        $output['error'] = false;
                        $output['message'] = 'Your email address has been verified.';

                        $tmp = getUserBasicDetails($userData["id"]);
                        $output['userData'] = $tmp;
                        echoRespnse(200, $output);
                        exit;
                    } else {
                        $output['error'] = true;
                        $output['message'] = 'The OTP did not match. Please check your last verification e-mail and try again.';
                        echoRespnse(200, $output);
                        exit;
                    }
                }
            } else {
                $newOtp = $utilCRUD->generateNewOTP();
                $utilCRUD->sendVerificationOTP($email, $newOtp);
                $output['error'] = true;
                $output['message'] = 'We have sent you an OTP to your registered e-mail address. Please check your SPAM folder as well.';

                try {
                    $emailToName = $userData["first_name"] . " " . $userData["last_name"];
                    notifyUserWithVerificationCode($email, $emailToName, $newOtp, 0);
                } catch (Exception $e) {
                    $output["notifier"] = "Error sending e-mail: " . $e->getMessage();
                }

                echoRespnse(200, $output);
                exit;
            }
        }
    } else {
        $output['error'] = true;
        $output['message'] = 'We do not find any account registered with this e-mail address.';
        echoRespnse(200, $output);
        return;
    }
    echoRespnse(200, $output);
})->add($authenticate);
?>