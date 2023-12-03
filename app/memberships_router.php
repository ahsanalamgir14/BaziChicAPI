<?php
// Psr-7 Request and Response interfaces
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

	//MANAGE PLAN PACKAGES
	$app->get('/apis/subscription_plans', function ($request, $respo, $args) use ($app) {
	    require_once("dbmodels/membership_plan.crud.php");
     	$planCRUD = new PlanCRUD(getConnection());
        $data = $planCRUD->getAllActivePlans();
		$output = array();
        $output["error"] = false;
        $output["data"] = $data;
		 echoRespnse(200, $output);
	});
	
/*************** CREATE MEMBERSHIP VIEW****************/
	$app->get('/confirm-subscription/{ref_code}', function (Request $request, Response $response, $args)   {
	    $_SESSION["last_saved"] = $_SERVER['REQUEST_URI'];
	    if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('register')); 
        return $response->withRedirect((string)$uri);
	    }
	    require_once("dbmodels/membership_plan.crud.php");
     	$planCRUD = new PlanCRUD(getConnection());
     	require_once("dbmodels/user_membership.crud.php");
     	$membershipCRUD = new MembershipCRUD(getConnection());
        require_once("dbmodels/utils.crud.php");
     	$utilCRUD = new UtilCRUD(getConnection());
     	require_once("dbmodels/transaction_details.crud.php");
        $paymentCRUD = new PaymentCRUD(getConnection());
        $ref_code = $request->getAttribute('ref_code');
        if(empty($ref_code)){
	    $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
    if(!$membershipCRUD->isRefQCodeExists($ref_code)){
         $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
    $selectedItem = $membershipCRUD->getByQCode($ref_code);
    if($selectedItem !== null){
        $plan_id = $selectedItem["plan_id"];
        $user_id = $selectedItem["user_id"];
        $date_expiring = $selectedItem["date_expiring"];
        $date_created = $selectedItem["date_created"];
        $amount = $selectedItem["amount"];
        $status = $selectedItem["status"];

        if($status !== "Pending"){
       if($paymentCRUD->isRefQCodeExists($ref_code)){
           return $response->withRedirect('/invoice/'.$ref_code); 
       }else{
        $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string)$uri);
       }
        }
        $selectedMembership = $planCRUD->getID($plan_id);
    }else{
        $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
	$sandboxMode = false;
$paypalURL = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		 if(!$sandboxMode){
		     $paypalURL = "https://www.paypal.com/cgi-bin/webscr";
		 }
	$siteURL = "https://www.bazichic.com/";
	$UnEncsiteURL = "https://www.bazichic.com/";
    $vars = [
			'page' => [
			'title' => 'Confirm Subscription | Bazichik - Chinese Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'selectedMembership' => $selectedMembership,
			'datenow' => $datenow,
			'plan_id' => $plan_id,
			'date_expiring' => $date_expiring,
			'amount' => $amount,
			'ref_code'=> $ref_code,
						'paypal' => [
			'PAYPAL_ID' => "finance@bazichic.com",
			'PAYPAL_URL' => $paypalURL,
			'itemName' => "Subscription Plan",
			'itemID' => "$plan_id",
			'PAYPAL_CURRENCY' => "USD",
			'PAYPAL_RETURN_URL' => $siteURL."payment-success",
			'PAYPAL_CANCEL_URL' => $siteURL."payment-cancel",
			'PAYPAL_NOTIFY_URL' => $UnEncsiteURL."payment-notify"
			]
			]
		];
		return $this->view->render($response, 'subscription-payment-master.twig', $vars);
	})->setName('confirm-subscription');
	
	
	/********** USER PREVIEW HELPER ******/
	$app->get('/users/preview/{id}', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	$response = "";
	$id = $request->getAttribute('id');
	$thisUser = $userCRUD->getID($id);
	if($thisUser != null){
		$userName = $thisUser["name"];
		$response  = $userName."";
	}else{
        $response = "Failed to load user preview.";
		echoRespnse(200, $response);
	}
	echoRespnse(200, $response);
	});
	


	/********* CREATE MEMBERSHIP REQUEST AND TRANSACTION DETAILS *******/
	$app->post('/purchases/membership', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/membership_plan.crud.php");
	require_once("dbmodels/user_membership.crud.php");
	require_once("dbmodels/user.crud.php");
	//require_once("dbmodels/transaction_details.crud.php");
	require_once("dbmodels/notification.crud.php");
	require_once("dbmodels/utils.crud.php");
	$utilCRUD = new UtilCRUD(getConnection());
    $notiCRUD = new NotificationCRUD(getConnection());
    //$transactionCRUD = new TransactionCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $membershipCRUD = new MembershipCRUD(getConnection());
	$response = array();
    $response["error"] = false;
	$response["note"] = '';
	$response['message'] = '';
	/******** REGISER NEW USER *********/
	$plan_id = $request->getParam('plan_id');
	$user_id = 0;
    if(null !== $request->getParam('user_id')){
		$user_id = $request->getParam('user_id');
	}else{
	if(null !== $request->getParam('first_name')){
		$first_name = $request->getParam('first_name');
	}else{
		$response['error'] = true;
         $response['message'] = 'Please enter your first name.';
         echoRespnse(200, $response);
		 return;
	}
	
	if(null !== $request->getParam('email')){
		$email = $request->getParam('email');
	}else{
		$response['error'] = true;
        $response['message'] = 'Please enter your email address.';
         echoRespnse(200, $response);
		 return;
	}
	
	if(null !== $request->getParam('country')){
		$country = $request->getParam('country');
	}else{
		$response['error'] = true;
        $response['message'] = 'Please select a country you belong to.';
         echoRespnse(200, $response);
		 return;
	}
	
	//Validate Email
	if(null !== $request->getParam('password')){
		$password = $request->getParam('password');
	}else{
		$response['error'] = true;
        $response['message'] = 'Please enter a password to sign into your account.';
         echoRespnse(200, $response);
		 return;
	}
	$dob = "";
	if(null !== $request->getParam('dob')){
		$dob = $request->getParam('dob');
	}
	$referral_code = "";
	if(null !== $request->getParam('referral_code')){
		$referral_code = $request->getParam('referral_code');
	}
	$registerResponse = quickRegister($first_name, $last_name, $email, $country, $dob, $password, $referral_code);
	
	if($registerResponse["error"]){
		$response['error'] = true;
        $response['message'] = $registerResponse["message"];
         echoRespnse(200, $response);
		 return;
	}else{
	$user_id = $registerResponse["id"];
	$response["note"] .= "User account created first. ";
	}
	}
	/******** REGISER NEW USER *********/
	
	if(null !== $request->getParam('plan_name')){
		$plan_name = $request->getParam('plan_name');
	}else{
		$plan_name = $planCRUD->getNameByID($plan_id);
	}
	
	//Get Membership Details
	$amount = $request->getParam('amount');
	$status = $request->getParam('status');
	$note = $request->getParam('note');
	$date_created = date('Y-m-d H:i:s');
	$date_expiring = $request->getParam('date_expiring');
	
//Validate Check
	if(empty($user_id) || $user_id <= 0){
		  $response['error'] = true;
         $response['message'] = 'Looks like there was an error registering your account. Please try again.';
         echoRespnse(200, $response);
		 return;
	}
	
	if(empty($plan_id) || $plan_id < 0){
		  $response['error'] = true;
         $response['message'] = 'Please choose a membership plan.';
         echoRespnse(200, $response);
		 return;
	}
	
	/*
	if(empty($amount) || $amount < 0){
		  $response['error'] = true;
         $response['message'] = 'Total amount must be greater than zero.';
         echoRespnse(200, $response);
		 return;
	}*/
	
	if(empty($date_expiring)){
		  $response['error'] = true;
         $response['message'] = 'Could not determine an expiry date. Reload and try again.';
         echoRespnse(200, $response);
		 return;
	}	
	
	$user_name = $userCRUD->getNameByID($user_id);
	$qcode = $membershipCRUD->generateCode();
	$res = $membershipCRUD->create($user_id, $plan_id, $date_created, $date_expiring, $amount, $status, $qcode, $note);
	if ($res["code"] == INSERT_SUCCESS) {
                $response["error"] = false;
                $response["qcode"] = $qcode;
				$response['message'] =  "We are taking you to subscribe for the ".$plan_name." plan. If you see this message please press the back button on your browser to continue.";
				
				/*
				$message = "You have activated the ".$plan_name." plan successfully. Enjoy complete access till ".$date_expiring.".";
				try{
				$title = $plan_name." Plan Activated";
				$noti_res = $notiCRUD->create($sender = 1, $user_id, $title, $message, $user_id, $data_title="ActivateMember", "Pending", $date_created);
			    if ($noti_res["code"] == INSERT_SUCCESS) {
				$response["note"].= "Notification Sent for activating plan.";
			    }
				}catch(Exception $e){
					$response["note"] .= "Failed to activate plan. Please try again.";
				}
				*/
				
				$id = $res["id"];
				$response["id"] = $id;
				/********** Add Transaction Details	************
				$payResponse = $transactionCRUD->create($sender, $receiver, $amount, $id, $mode, $status, $txn_id, $pay_key, $date_of_txn);
				if ($payResponse["code"] == INSERT_SUCCESS) {
					$response["message"] .= " Your transaction details have been received.";
					//$res = $membershipCRUD->create($user_id, $plan_id, $status, $note, $activation_date);
				}else{
					$response["message"] .= " There was an error validating the transaction details. Please try again.";
				}
				********* Add Transaction Details	*************/
			 }else{
				  $response["error"] = true;
                  $response["message"] = "Failed to generate link for membership plan. Please try again.";
				  echoRespnse(200, $response);
			 }
	echoRespnse(200, $response);
	});
	
	
	
	
	
	
/*************** CREATE MEMBERSHIP VIEW****************/
	$app->get('/get-membership/{member_type}', function (Request $request, Response $response, $args)   {
	    if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('register')); 
        return $response->withRedirect((string)$uri);
	    }
	    require_once("dbmodels/membership_plan.crud.php");
     	$planCRUD = new PlanCRUD(getConnection());
     	require_once("dbmodels/user_membership.crud.php");
	    $membershipCRUD = new MembershipCRUD(getConnection());
     	require_once("dbmodels/reward_points.crud.php");
     	$pointCRUD = new RewardPointCRUD(getConnection());
       require_once("dbmodels/utils.crud.php");
     	$utilCRUD = new UtilCRUD(getConnection());
        $member_type = $request->getAttribute('member_type');
        if(empty($member_type)){
	    $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
    if(!$planCRUD->isMembershipQcodeExists($member_type)){
         $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
     $_SESSION["last_saved"] = $_SERVER['REQUEST_URI'];
$selectedMembership = $planCRUD->getByQCode($member_type);
 if($selectedMembership == null){
         $uri = $request->getUri()->withPath($this->router->pathFor('membership'));
        return $response->withRedirect((string)$uri);
    }
$plan_id = $selectedMembership["id"];
$datenow = date('Y-m-d H:i:s');
$date = strtotime($datenow);
$expiryDate =	date('Y-m-d H:i:s', strtotime('+'.$selectedMembership['duration'].' days'));
$datenowDisplay = $utilCRUD->getFormalDate($datenow);
$expiryDateDisplay = $utilCRUD->getFormalDate($expiryDate);

$priceWithRedeem = $selectedMembership["price"];
$canRedeem = false;
$current_points = $pointCRUD->getCurrentRewardPointFor($_SESSION["userID"]);
$remaining_points = $pointCRUD->getCurrentRewardPointFor($_SESSION["userID"]);
$redeemStatus = "You are not eligible to use your loyalty points to complete this transaction.";
/*
if($selectedMembership["price"] > 0){
if($current_points > 0){
 if($current_points >= $selectedMembership["price"]){
     $priceWithRedeem = 0;
     $remaining_points =  ($current_points - $selectedMembership["price"]);  
     $redeemStatus .= " Remianing loyalty points will be ".$remaining_points.".";
 }else{
     $priceWithRedeem = $selectedMembership["price"] - $current_points;
     $remaining_points = 0;
     $redeemStatus = "Your loyalty point is ".$current_points.". You can still redeem your points to complete this transaction.";
 }
}else{
    $canRedeem = false;
     $redeemStatus = "You do not have sufficient loyalty points to redeem at this time.";
}
}else{
     $redeemStatus = "There is no fee to complete this subscription. You can always use your loyalty points for paid subscriptions.";
}
*/

  /**** First Check Trial Periods  *****/
$canHaveFreeTrial = true;
$freeTrialMsg = "You are eligible for a free trial.";
$subscriptionInfo = "";
$canSubscribe = true;
require_once("dbmodels/free_trials.crud.php");
$freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
 
$numAllTrials =  $freeTrialsCRUD->getNumAllPlansFor($_SESSION["userID"], $plan_id);
if($numAllTrials > 0){
$canHaveFreeTrial = false;
$freeTrialMsg = "You have already availed the Free Trial.";
}
	    
	    $numActiveTrials =  $freeTrialsCRUD->getNumMyActivePlan($_SESSION["userID"]);
	    if($numActiveTrials > 0){
	        $activeTrial =  $freeTrialsCRUD->getMyActivePlan($_SESSION["userID"]);
	         if($numAllTrials > 0){
	      $canHaveFreeTrial = false;
	    }
	    }else{
	       
	    }
      /**** First Check Trial Periods  *****/


      /*********** Then Check Active Paid Subscriptions ***********/
	  $numMyPlans = $membershipCRUD->getNumMyActivePlan($_SESSION["userID"]);
	  if($numMyPlans > 0){
	     $activePaidPlan =  $membershipCRUD->getMyActivePlan($_SESSION["userID"]); 
	     $activePlanName = $planCRUD->getNameByID($activePaidPlan["plan_id"]);
	    $activeExpiry = $utilCRUD->getFormalDate($activePaidPlan["date_expiring"]);
	    // $subscriptionInfo = $activePaidPlan["plan_id"]." compare to ".$plan_id;
	    if($activePaidPlan["plan_id"] == $plan_id){
	        $subscriptionInfo = "Your ".$activePlanName." Subscription will expire on ".$activeExpiry.".";
	        $canSubscribe = false;
	         $canHaveFreeTrial = false;
            $freeTrialMsg = "";
	    }
	 }
	 /*******************************/
	 
$sandboxMode = false;
$paypalURL = "https://www.sandbox.paypal.com/cgi-bin/webscr";
if(!$sandboxMode){
$paypalURL = "https://www.paypal.com/cgi-bin/webscr";
}
$siteURL = "https://www.bazichic.com/";
$UnEncsiteURL = "https://www.bazichic.com/";
$vars = [
			'page' => [
			'title' => 'Confirm Membership | Bazichik - Chinese Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'selectedMembership' => $selectedMembership,
			'datenow' => $datenow,
			'freeTrialMsg'=> $freeTrialMsg,
			'canSubscribe'=> $canSubscribe,
			'subscriptionInfo'=> $subscriptionInfo,
			'canHaveFreeTrial'=> $canHaveFreeTrial,
			'expiryDate' => $expiryDate,
			'datenowDisplay' => $datenowDisplay,
			'expiryDateDisplay' => $expiryDateDisplay,
			'current_points' => $current_points,
			'remaining_points' => $remaining_points,
			'canRedeem' => $canRedeem,
			'priceWithRedeem' => $priceWithRedeem,
			'redeemStatus' => $redeemStatus,
			'paypal' => [
			//'PAYPAL_ID' => "sb-vbunn778158@business.example.com",
			'PAYPAL_ID' => "finance@bazichic.com",
			'PAYPAL_URL' => $paypalURL,
			'itemName' => "Subscription Plan",
			'itemID' => "StarterPlan",
			//'priceToPay' => 10,
			'PAYPAL_CURRENCY' => "USD",
			'priceToPay' => 1,
			'PAYPAL_RETURN_URL' => $siteURL."payment-success",
			'PAYPAL_CANCEL_URL' => $siteURL."payment-cancel",
			'PAYPAL_NOTIFY_URL' => $UnEncsiteURL."payment-notify"
			]
			]
		];
		return $this->view->render($response, 'purchase-membership-form.twig', $vars);
	})->setName('get-membership');
	
	
	
	// PAYMENT ROUTE
	$app->get('/payment-success', function (Request $request, Response $response, $args){
	    $display_message = "";
	    $helper = new Helper();
	    require_once("dbmodels/utils.crud.php");
     	$utilCRUD = new UtilCRUD(getConnection());
     	require_once("dbmodels/user_membership.crud.php");
     	$membershipCRUD = new MembershipCRUD(getConnection());
     	require_once("dbmodels/transaction_details.crud.php");
        $paymentCRUD = new PaymentCRUD(getConnection());
        
        require_once("dbmodels/user.crud.php");
     	$userCRUD = new UserCRUD(getConnection());
        //$paymentCRUD->create($_SESSION["userID"], "This", "That", "A001", "87879879879879", "None", "UI", "879797987", "y8787987897", "Pending", "Youty", "");
         
       $date_created = date('Y-m-d H:i:s');  
       $gets = http_build_query($_GET);
       $qcode = "";
	//if(!empty($_GET['item_number']) && !empty($_GET['tx']) && !empty($_GET['amt']) && $_GET['st'] == 'Completed'){ 
	if(!empty($_GET['item_number']) && !empty($_GET['tx']) && !empty($_GET['amt'])){
    $item_number = $_GET['item_number'];   
    $txn_id = $_GET['tx'];  
    $payment_gross = $_GET['amt'];
    $currency_code = $_GET['cc']; 
    $payment_status = $_GET['st'];
    $qcode = $_GET['cm']; 
    $display_message = "Your TXN ID is ".$txn_id.". The payment status is ".$payment_status.".";
    
    // Check if transaction data exists with the same TXN ID.  
    //$prevPaymentResult = $db->query("SELECT * FROM user_subscriptions WHERE txn_id = '".$txn_id."'");  
     
    //if($prevPaymentResult->num_rows > 0){ 
    //    $paymentData = $prevPaymentResult->fetch_assoc(); 
    //} 
    $userID = 0;
    $planItem = "";
    //Rather check if any such custom qcode has been generated and pending
    if($membershipCRUD->isRefQCodeExists($qcode)){
    $subscriptionEntry =  $membershipCRUD->getByQCode($qcode); 
    if($subscriptionEntry !== null && $subscriptionEntry["status"] == "Pending"){
    $userID = $subscriptionEntry["user_id"];
    }else{
         $utilCRUD->createPayTest($_SESSION["userID"], "No Pending Ref Code Found ", $gets);
         $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string)$uri);
    }
    }else{
        $utilCRUD->createPayTest($_SESSION["userID"], "No Subscription Ref Code Found ", $gets);
         $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string)$uri);
    }
    $note = http_build_query($_GET);
   
    try{
        $subscriptionEntry = $paymentCRUD->create($userID, $_SESSION["email"], "", $item_number, $qcode, $payment_gross, $currency_code, $txn_id, $txn_id, $payment_status, $payment_status, $note);
         if(!$subscriptionEntry["error"]){
         $membershipCRUD->updateStatus($qcode, "Active", $date_created); 
         }
    }catch(Exception $e){
    	$subscriptionEntry = $paymentCRUD->create($userID, $_SESSION["email"], "", $item_number, $qcode, $payment_gross, $currency_code, $txn_id, $txn_id, $payment_status, $payment_status, $e->getMessage());
    	if(!$subscriptionEntry["error"]){
         $membershipCRUD->updateStatus($qcode, "Active", $date_created);
         }
    }
}


/*************** SEND AN EMAIL ******************/
$first_name = $userCRUD->getNameByID($userID);
$userEmail = $userCRUD->getEmail($userID);
//$userEmail = "javacheartofmine@gmail.com";
	$subject = "Bazichic ".$planItem." Subscription Confirmation";
			$body = 'Dear  '.$first_name.'!';
			$body .= '<br><br>';
			 $body .= 'You have subscribed to our '.$planItem.' plan successfully. Transaction Reference ID for your payment is '.$txn_id.'.';
            $body .= '<br><br>';
            
            $body .= 'Thanks for your subscription at Bazichic. Have a great journey ahead.';
          
            $body .= '<br><br>To Sign into your Account <a href="https://www.bazichic.com/login">Click Here</a>.';
            
            $body .= '<br><br>';
            $body .= 'For more details, Visit our official site at <a href="https://www.bazichic.com">www.bazichic.com</a>.';
            
            $body .= '<br><br>If you have any query, complaint or suggestion regarding our services feel free to write us at customer_support@bazichic.com.';
            
                         $body .= '<div style="display:block;"><a href="http://bazichic.com" class="logo">
					<img src="http://bazichic.com/images/logo.png" style="height:60px;" alt="Bazichic">
				</a></div>';
				
			$helper->sendEmail($userEmail, $subject, $body);
/**********************************************/

/****************************** OTHER LOGS ******************************/
$utilCRUD->createPayTest($custom, "Payment Success for ".$custom, $gets);
/************************************************************/

		$vars = [
			'page' => [
			'title' => 'Payment Successful | Bazichik - Chinese Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'display_message' => $display_message,
			'qcode' => $qcode
			],
		];

		return $this->view->render($response, 'payment-confirmation.twig', $vars);
	})->setName('payment-success');
	
	
	
	/******************** PAYPAL CANCEL ROUTE **********************/
		$app->get('/payment-cancel', function (Request $request, Response $response, $args){
	    $display_message = "";
		$vars = [
			'page' => [
			'title' => 'Payment Failed | Bazichik - Chinese Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'display_message' => $display_message
			],
		];

		return $this->view->render($response, 'payment-cancel.twig', $vars);
	})->setName('payment-cancel');
	
	

/****************** PAYPAL IPN ROUTE *******************/	
$app->post('/payment-notify', function (Request $request, Response $response, $args){
$display_message = "";
require_once("dbmodels/utils.crud.php");
$utilCRUD = new UtilCRUD(getConnection());
require_once("dbmodels/user_membership.crud.php");
$membershipCRUD = new MembershipCRUD(getConnection());
/******************** IPN STARTS ***************************/
$sandboxMode = false;
$paypalURL = "https://www.sandbox.paypal.com/cgi-bin/webscr";
if(!$sandboxMode){
$paypalURL = "https://www.paypal.com/cgi-bin/webscr";
}
$siteURL = "https://www.bazichic.com/";
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode ('=', $keyval);
    if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
}
 
/***********************************************************/
$utilCRUD->createPayTest(0, "IPN Log", $raw_data);
/************************************************************/



// Read the post from PayPal system and add 'cmd' 
$req = 'cmd=_notify-validate'; 
if(function_exists('get_magic_quotes_gpc')) { 
    $get_magic_quotes_exists = true; 
} 
foreach ($myPost as $key => $value) { 
    if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
    } else { 
        $value = urlencode($value); 
    } 
    $req .= "&$key=$value"; 
} 
 
/* 
 * Post IPN data back to PayPal to validate the IPN data is genuine 
 * Without this step anyone can fake IPN data 
 */ 


$ch = curl_init($paypalURL); 
if ($ch == FALSE) { 
    return FALSE; 
} 
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0); 
curl_setopt($ch, CURLOPT_POST, 1); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $req); 
curl_setopt($ch, CURLOPT_SSLVERSION, 6); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1); 
 
// Set TCP timeout to 30 seconds 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: company-name')); 
$res = curl_exec($ch); 
 
/* 
 * Inspect IPN validation result and act accordingly 
 * Split response headers and payload, a better way for strcmp 
 */  
$tokens = explode("\r\n\r\n", trim($res)); 
$res = trim(end($tokens)); 
if (strcmp($res, "VERIFIED") == 0 || strcasecmp($res, "VERIFIED") == 0) { 
     
    // Retrieve transaction data from PayPal 
    $paypalInfo = $_POST; 
    $subscr_id = $paypalInfo['subscr_id']; 
    $payer_email = $paypalInfo['payer_email']; 
    $item_name = $paypalInfo['item_name']; 
    $item_number = $paypalInfo['item_number']; 
    $txn_id = !empty($paypalInfo['txn_id'])?$paypalInfo['txn_id']:''; 
    $payment_gross =  !empty($paypalInfo['mc_gross'])?$paypalInfo['mc_gross']:0; 
    $currency_code = $paypalInfo['mc_currency']; 
    $subscr_period = !empty($paypalInfo['period3'])?$paypalInfo['period3']:floor($payment_gross/$itemPrice); 
    $payment_status = !empty($paypalInfo['payment_status'])?$paypalInfo['payment_status']:''; 
    $custom = $paypalInfo['custom']; 
    $subscr_date = !empty($paypalInfo['subscr_date'])?$paypalInfo['subscr_date']:date("Y-m-d H:i:s"); 
    $dt = new DateTime($subscr_date); 
    $subscr_date = $dt->format("Y-m-d H:i:s"); 
    $subscr_date_valid_to = date("Y-m-d H:i:s", strtotime(" + $subscr_period month", strtotime($subscr_date))); 
     
    $summary = "TXN: ".$txn_id." | Period: ".$subscr_period." | Subscription Date: ".$subscr_date." | Valid Upto: ".$subscr_date_valid_to." | Payment Status: ".$payment_status."| Custom: ".$custom;
    if(isset($_SESSION["userID"])){
         $utilCRUD->createPayTest($_SESSION["userID"], "IPNSummary for ".$custom, $summary);
    }else{
        $utilCRUD->createPayTest(0, "IPNSummary for ".$custom, $summary);
    }


    if(!empty($txn_id) && !empty($custom)){ 
       if(!empty($subscr_date_valid_to)){
        $date_updated = date('Y-m-d H:i:s');
        $membershipCRUD->updateExpiryDate($custom, $subscr_date_valid_to, $date_updated);
        $utilCRUD->createPayTest(0, "IPN Period Update", $custom+" expiry date is updated to => "+$subscr_date_valid_to);
    }
    }
    
} 
/******************** IPN ENDS *******************/

		$vars = [
			'page' => [
			'title' => 'Payment Notification | Bazichik - Chinese Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'display_message' => $display_message
			],
		];

		return $this->view->render($response, 'payment-confirmation.twig', $vars);
	})->setName('payment-notify');
	
	
	
	/************* PLAN UPDATE *******************/
	$app->post('/membership_plans/update', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('plan_id');
	$title = $request->getParam('title');
	$membersip_desc = $request->getParam('membersip_desc');
	$currency_id = 1;
	$price = $request->getParam('price');
	$duration = $request->getParam('duration');
	$tagline = $request->getParam('tagline');
	$sort_order = $request->getParam('sort_order');
	$is_highlighted = 0;
	if(null !== $request->getParam('is_highlighted')){
				$is_highlighted = 1;
	}
	$is_available = 0;
	if(null !== $request->getParam('is_available')){
		$is_available = 1;
	}
	$note = $request->getParam('note');
	if(empty($sort_order)){
		$sort_order = 0;
	}
	$date_updated = date('Y-m-d H:i:s');
	
	if(empty($title)){
		 $response['error'] = true;
         $response['message'] = 'Please enter a title for this plan.';
         echoRespnse(200, $response);
		 return;
	}
	
	if(empty($price)){
		  $price = 0;
	}
	
	if(empty($duration) || $duration <= 0){
		  $response['error'] = true;
         $response['message'] = 'You must enter a validity period (in days) for this plan.';
         echoRespnse(200, $response);
		 return;
	}
	
	if(empty($membersip_desc) || strlen($membersip_desc) <= 10){
		  $response['error'] = true;
         $response['message'] = 'You must enter a formatted description or features of this plan.';
         echoRespnse(200, $response);
		 return;
	}
	
	$res = $planCRUD->update($id, $title, $membersip_desc, $price, $duration, $currency_id, $tagline, $is_available, $note, $is_highlighted, $sort_order);
	if ($res["code"] == INSERT_SUCCESS) {
        $response["error"] = false;
		if($is_available == 1){
					$response["message"] = "Membership Plan has been updated successfully.";
				}else{
					$response["message"] = "Membership Plan has been updated and saved as a draft.";
				}
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to update plan. Please try again.".$res["msg"];
		echoRespnse(200, $response);
		}
	});
	
	
	/******** DELETE PLAN *********/
	$app->post('/membership-plan/delete', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('plan_id');
	//$owner_id = $blogCRUD->getAuthorID($id);
	if (!checkSession()) {
		$response["error"] = true;
        $response["message"] = "Please login to perform this action.";
		echoRespnse(200, $response);
		exit;
	}
	if ($_SESSION["role_id"] != 1) {
	//if ($owner_id != $_SESSION["userID"]) {
		$response["error"] = true;
        $response["message"] = "You are not authorized to perform this action. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		exit;
	//}
	}
	$res = $planCRUD->delete($id);	   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "Investment plan has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
				  $response["error"] = true;
                  $response["message"] = "Failed to delete plan. Please try again.";
				  echoRespnse(200, $response);
		}
	});
	

//EDIT PLAN VIEW
$app->get('/edit-membership-plan/{id}', function($request, $response, $args) {
	if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('login')); 
        return $response->withRedirect((string)$uri);
	}	
	if ($_SESSION["role_id"] != 1) {
		$uri = $request->getUri()->withPath($this->router->pathFor('unauthorized')); 
        return $response->withRedirect((string)$uri);
	 }
    require_once("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
	$id = $request->getAttribute('id');
	$plan = $planCRUD->getByQCode($id);
	if($plan !== NULL){
	$title= $plan["title"];
	$membersip_desc = $plan["membersip_desc"];
	$price= $plan["price"];
	$tagline= $plan["tagline"];
	$duration= $plan["duration"];
	$sort_order = $plan["sort_order"];
	$is_highlighted = $plan["is_highlighted"];
	$note= $plan["note"];
	$is_available= $plan["is_available"];
	$date_created = $plan["date_created"];
	}
	
    $vars = [
			'page' => [
			'title' => 'Update Membership Plan',
			'description' => 'Update Membership Plan'
			],
			'membership' => [
			'plan_id' => $plan["id"],
			'title' => $title,
            'membership_desc' => $membersip_desc,
			'duration' => $duration,
			'price' => $price,
			'tagline' => $tagline,
			'is_available' => $is_available,
			'note' => $note,
			'sort_order' => $sort_order,
			'is_highlighted' => $is_highlighted,
			'qcode' => $plan["qcode"],
			'currency_id' => $currency_id,
			'date_created' => $date_created			
			]
		];	
	return $this->view->render($response, 'membership-edit.twig', $vars);
})->setName('edit-membership-plan');
?>