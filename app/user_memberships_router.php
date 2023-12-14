<?php

$app->get('/apis/send-expiry-emails', function ($request, $response) {
    require_once("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());

    try {
        // Connect to your database and retrieve users with memberships expiring in 30 days
        $users = $membershipCRUD->getUsersWithExpiringMemberships();
        // return $response->withJson(['message' => 'users found', 'users' => $users]);
        // Check if there are users
        if (empty($users)) {
            return $response->withJson(['message' => 'No users with expiring memberships found', 'users' => $users]);
        } 

        $helper = new Helper();

        // Loop through users and send email notifications
        foreach ($users as $user) {
            $to = $user['email'];
            $subject = 'Your Membership is Expiring with in 30 days';
            $message = 'Dear ' . $user['first_name'] . ', your membership is expiring in 30 days.';
            $expiryDate = new DateTime($user['date_expiring']);
            $formattedExpiryDate = $expiryDate->format('m-d-Y');
            $message = '
                <html>
                <head>
                    <title>Subscription Expiry Notification</title>
                </head>
                <body>
                    <p>Dear ' . htmlspecialchars($user['first_name']) . ',</p>
                    <p>Your ' . htmlspecialchars($user['user_plan']) . ' subscription with BaziChic is expiring on ' . htmlspecialchars($formattedExpiryDate) . '.</p>
                    <p>Please renew your subscription to avail continued access.</p>
                    <p>Regards<br/>BaziChic</p>
                </body>
                </html>
            ';
            // Use a mail library or PHP's mail function to send the email
            // Example using PHP mail function:
            $helper->sendEmail($to, $subject, $message);
        }

        // Return the response indicating success
        return $response->withJson(['message' => 'Emails sent successfully']);
    } catch (Exception $e) {
        // Handle exceptions (log it, return an error response, etc.)
        return $response->withJson(['error' => 'An error occurred: ' . $e->getMessage()]);
    }
});

$app->post('/api/process-payment', function ($request, $response) {
    \Stripe\Stripe::setApiKey('sk_test_51GR7jjEFRCIyd6pMzvGbGVdeArWhBerC4fZMgOmnGDhQxjmtguVpjZ1vNmcIrd9VxT6GGWoPUgDZWLZkIjXZQPrv00tzDwelP2');
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/transaction_details.crud.php");
    $transactionCRUD = new PaymentCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    $user_id = $request->getParam('user_id');
    $plan_id = $request->getParam('plan_id');
    $startDate = $request->getParam('startDate');
    $expiryDate = $request->getParam('expiryDate');
    $amount = $request->getParam('amount');
    $token = $request->getParam('token');
    $mode = "Online";
    $note = "Stripe";
    $txn_id = "";
    
    if ($user_id) {
        $user_id = $request->getParam('user_id');
    }
    if ($plan_id) {
        $plan_id = $request->getParam('plan_id');
    }
    if ($startDate) {
        $startDate = $request->getParam('startDate');
    }
    if ($expiryDate) {
        $expiryDate = $request->getParam('expiryDate');
    }
    if ($amount) {
        $amount = $request->getParam('amount');
    }
    if ($token) {
        $token = $request->getParam('token');
    }

    // return $token;
    
    if (empty($user_id) || $user_id < 0 || empty($plan_id) || $plan_id < 0 || empty($startDate) || empty($expiryDate) || empty($amount) || $amount < 0) {
        $output["error"] = true;
        $output["message"] = "Invalid input data. Please check your input fields.";
        echoRespnse(200, $output);
    }
    
    $planTitle = $planCRUD->getNameByID($plan_id);
    
    if(!isDateFormatValid($startDate)){ 
        $output["error"] = true;
        $output["startDate"] = $startDate;
        $output["message"] = "Please select valid subscription start date.";
        echoRespnse(200, $output);
        exit;
    }
    if(!isDateFormatValid($expiryDate)){
        $output["error"] = true;
        $output["message"] = "Please select valid subscription expiry date.";
        echoRespnse(200, $output);
        exit;
    }
    if($startDate > $expiryDate){
        $output["error"] = true;
        $output["message"] = "Invalid subscription expiry date.";
        echoRespnse(200, $output);
        exit;
    }
        
    if (strpos($startDate, ' ') == false) {
        $startDate = $startDate." 00:00:00";
    }
    if (strpos($expiryDate, ' ') == false) {
        $expiryDate = $expiryDate." 00:00:00";
    }
    if (!empty($txn_id)) {
        if (null !== $request->getParam('id')) {
        }else{
            if ($transactionCRUD->doesTXNExists($txn_id)) {
                $output["error"] = true;
                $output["message"] = "Same transaction ID already exists.";
                echoRespnse(200, $output);
                exit;
            } 
        }
    }else{
        $txn_id = $utilCRUD->generateTXNID(10);
    }
    if (empty($mode)) {
        $output["error"] = true;
        $output["message"] = "Please select the mode of payment.";
        echoRespnse(200, $output);
        exit;
    }
    
    $subscriptionID = 0;
    $membershipInfo = "";
    $update = false;
    if (null !== $request->getParam('id')) {
        $subscriptionID = $request->getParam('id');
        if (empty($subscriptionID) || $subscriptionID < 0) {
            $output["error"] = true;
            $output["message"] = "Please select a valid subscription ID.";
            echoRespnse(200, $output);
            exit;
        }
        //Check in database if this ID exists
        if ($membershipCRUD->isIDExists($subscriptionID)) {
			$membershipInfo = $membershipCRUD->getID($subscriptionID);
            $update = true;
        } else {
            $output["error"] = true;
            $output["message"] = "Could not retrieve subscription details.";
            echoRespnse(200, $output);
            exit;
        }
    }
    $user_email = $userCRUD->getEmail($user_id);
    $userData = $userCRUD->getUserByEmail($user_email);
    $txnStatus = "Completed";
    
    $date_updated = date('Y-m-d H:i:s');
    if ($update) {
        try {
            // Create a charge using the Stripe API
            $charge = \Stripe\Charge::create([
                'amount' =>  $amount * 100,
                'currency' => 'usd',
                'description' => 'Payment for your product or service',
                'source' => $token, // obtained with Stripe.js
            ]);
    
            // Charge was successful, return a success response
            // return $response->withJson(['message' => 'Payment successful', 'charge' => $charge]);
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined, return an error response
            return $response->withJson(['error' => $e->getMessage()], 400);
        } catch (\Stripe\Exception\RateLimitException | \Stripe\Exception\InvalidRequestException | \Stripe\Exception\AuthenticationException | \Stripe\Exception\ApiConnectionException | \Stripe\Exception\Base $e) {
            // Other Stripe-related errors, return an error response
            return $response->withJson(['error' => $e->getMessage()], 500);
        }
        $res = $membershipCRUD->update($subscriptionID, $user_id, $plan_id, $startDate, $expiryDate, $amount, "Active", $note, $date_updated);
        if (!$res["error"]) {
            $output["error"] = false;
            $output["message"] = "Subscription has been updated successfully.";

            //Also update the transaction details
            if (null !== $request->getParam('hasTransactionDetail')) {
                $hasTransactionDetail = $request->getParam('hasTransactionDetail');
                try {
                    $transRowID = $transactionCRUD->getRowIDByRefCode($membershipInfo["qcode"]);
                    $output["transactionRowID"] = $transRowID;
                    if(!empty($transRowID) && is_numeric($transRowID)){
					$transResult = $transactionCRUD->update($transRowID, $txn_id, $mode, $note);
                    if (!$transResult["error"]) {
                        $output["message"] .= " Transaction details have been updated. ";
                        $output["qcode"] = $membershipInfo["qcode"];
                    }
				   }else{
                        //Create new transaction if not exists
                        try {
                            $transResult = $transactionCRUD->create($user_id, $user_email, "", $planTitle, $membershipInfo["qcode"], $amount, "USD", $txn_id, $txn_id, $txnStatus, $txnStatus, $note, $mode);
                            if (!$transResult["error"]) {
                                $output["message"] .= " Transaction details have been updated. ";
                            }
                        } catch (Exception $e) {
                            $output["message"] .= " Error saving transaction details. ";
                        }

                        //Notify by e-mail
                        try {
                            notifyUserOfNewSubscription($user_email, $userData["firstName"], $planTitle, $startDate, $expiryDate, $qcode);
                        } catch  (Exception $e) {
                            $output["debug"] = " Error sending e-mail notification for new subscription. ";
                        }
                   }
                } catch (Exception $e) {
                    $output["message"] .= " Error saving transaction details. ";
                }
            }
            echoRespnse(200, $output);
        } else {
            $output["error"] = true;
            $output["message"] = "Failed to update Subscription. Please try again.";
            echoRespnse(200, $output);
        }
    } else {
        try {
            // Create a charge using the Stripe API
            $charge = \Stripe\Charge::create([
                'amount' =>  $amount * 100,
                'currency' => 'usd',
                'description' => 'Payment for your product or service',
                'source' => $token, // obtained with Stripe.js
            ]);
    
            // Charge was successful, return a success response
            // return $response->withJson(['message' => 'Payment successful', 'charge' => $charge]);
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined, return an error response
            return $response->withJson(['error' => $e->getMessage()], 400);
        } catch (\Stripe\Exception\RateLimitException | \Stripe\Exception\InvalidRequestException | \Stripe\Exception\AuthenticationException | \Stripe\Exception\ApiConnectionException | \Stripe\Exception\Base $e) {
            // Other Stripe-related errors, return an error response
            return $response->withJson(['error' => $e->getMessage()], 500);
        }
        $qcode = $membershipCRUD->generateCode();
        $res = $membershipCRUD->create($user_id, $plan_id, $startDate, $expiryDate, $amount, "Active", $qcode, $note);
        if (!$res["error"]) {
            $output["error"] = false;
            $output["message"] = "New subscription has been assigned successfully.";
            $output["expiryDate"] = $expiryDate;
            $output["startDate"] = $startDate;
            $output["id"] = $res["id"];
            $output["qcode"] = $qcode;
            $output["user_name"] = $userCRUD->getUsernameByID($user_id);
            /********** CREATE NEW TXN ENTRY *********/
            try {
                $transResult = $transactionCRUD->create($user_id, $user_email, "", $planTitle, $qcode, $amount, "USD", $txn_id, $txn_id, $txnStatus, $txnStatus, $note, $mode);
                if (!$transResult["error"]) {
                    $output["message"] .= " Transaction details have been updated. ";
                }
            } catch (Exception $e) {
                $output["message"] .= " Error saving transaction details. ";
            }

            //Notify by e-mail
            try {
                notifyUserOfNewSubscription($user_email, $userData["first_name"], $planTitle, $startDate, $expiryDate, $qcode);
            } catch  (Exception $e) {
                $output["debug"] = " Error sending e-mail notification for new subscription. ";
            }

            echoRespnse(200, $output);
        } else {
            $output["error"] = true;
            $output["moreInfo"] = $res["msg"];
            $output["message"] = "Failed to assign Subscription. Please try again.";
            echoRespnse(200, $output);
        }
    }
})->add($authenticate);

/********** ASSIGN & UPDATE SUBSCRIPTION *********/
$app->post('/subscriptions/assign', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/transaction_details.crud.php");
    $transactionCRUD = new PaymentCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    $user_id = 0;
    $plan_id = 0;
    $mode = "";
    $startDate = "";
    $expiryDate = "";
    $note = "";
    $amount = "";
    $status = "Pending";
    $txn_id = "";
    if (null !== $request->getParam('txn_id')) {
        $txn_id = $request->getParam('txn_id');
    }
    /***** Step 1: Capture Input/ Fields *****/
    if (null !== $request->getParam('user_id')) {
        $user_id = $request->getParam('user_id');
    }
    if (null !== $request->getParam('plan_id')) {
        $plan_id = $request->getParam('plan_id');
    }
    if (null !== $request->getParam('startDate')) {
        $startDate = $request->getParam('startDate');
    }
    if (null !== $request->getParam('expiryDate')) {
        $expiryDate = $request->getParam('expiryDate');
    }
    if (null !== $request->getParam('amount')) {
        $amount = $request->getParam('amount');
    }
    if (null !== $request->getParam('note')) {
        $note = $request->getParam('note');
    }
    if (null !== $request->getParam('status')) {
        $status = $request->getParam('status');
    }
    if (null !== $request->getParam('mode')) {
        $mode = $request->getParam('mode');
    }

    /***** Step 2: Validate Fields *****/
    if (empty($user_id) || $user_id < 0) {
        $output["error"] = true;
        $output["message"] = "Please select a valid subscriber ID.";
        echoRespnse(200, $output);
        exit;
    }
    if (empty($plan_id) || $plan_id < 0) {
        $output["error"] = true;
        $output["message"] = "Please select a valid subscription plan.";
        echoRespnse(200, $output);
        exit;
    }
    $planTitle = $planCRUD->getNameByID($plan_id);
    if (empty($planTitle)) {
        $output["error"] = true;
        $output["message"] = "Please select a valid subscription plan.";
        echoRespnse(200, $output);
        exit;
    }
    if (empty($startDate)) {
        $output["error"] = true;
        $output["message"] = "Please select subscription start date.";
        echoRespnse(200, $output);
        exit;
    }
    if (empty($expiryDate)) {
        $output["error"] = true;
        $output["message"] = "Please select subscription expiry date.";
        echoRespnse(200, $output);
        exit;
    }
    if (empty($mode)) {
        $output["error"] = true;
        $output["message"] = "Please select the mode of payment.";
        echoRespnse(200, $output);
        exit;
    }
	if (!empty($txn_id)) {
    if (null !== $request->getParam('id')) {
    }else{
        if ($transactionCRUD->doesTXNExists($txn_id)) {
			$output["error"] = true;
			$output["message"] = "Same transaction ID already exists.";
			echoRespnse(200, $output);
			exit;
		} 
    }
    }else{
		$txn_id = $utilCRUD->generateTXNID(10);
	}
    /****
    if(!isDateTimeFormatValid($startDate) || !isDateFormatValid($startDate)){  **/
    if(!isDateFormatValid($startDate)){ 
    $output["error"] = true;
    $output["startDate"] = $startDate;
    $output["message"] = "Please select valid subscription start date.";
    echoRespnse(200, $output);
    exit;
    }
    if(!isDateFormatValid($expiryDate)){
    $output["error"] = true;
    $output["message"] = "Please select valid subscription expiry date.";
    echoRespnse(200, $output);
    exit;
    }
    if($startDate > $expiryDate){
        $output["error"] = true;
        $output["message"] = "Invalid subscription expiry date.";
        echoRespnse(200, $output);
        exit;
    }
    
	if (strpos($startDate, ' ') == false) {
		$startDate = $startDate." 00:00:00";
	}
	if (strpos($expiryDate, ' ') == false) {
		$expiryDate = $expiryDate." 00:00:00";
	}
    $subscriptionID = 0;
	$membershipInfo = "";
    $update = false;
    if (null !== $request->getParam('id')) {
        $subscriptionID = $request->getParam('id');
        if (empty($subscriptionID) || $subscriptionID < 0) {
            $output["error"] = true;
            $output["message"] = "Please select a valid subscription ID.";
            echoRespnse(200, $output);
            exit;
        }
        //Check in database if this ID exists
        if ($membershipCRUD->isIDExists($subscriptionID)) {
			$membershipInfo = $membershipCRUD->getID($subscriptionID);
            $update = true;
        } else {
            $output["error"] = true;
            $output["message"] = "Could not retrieve subscription details.";
            echoRespnse(200, $output);
            exit;
        }
    }
    $user_email = $userCRUD->getEmail($user_id);
    $userData = $userCRUD->getUserByEmail($user_email);
    $txnStatus = "Completed";
    
    $date_updated = date('Y-m-d H:i:s');
    if ($update) {
        $res = $membershipCRUD->update($subscriptionID, $user_id, $plan_id, $startDate, $expiryDate, $amount, "Active", $note, $date_updated);
        if (!$res["error"]) {
            $output["error"] = false;
            $output["message"] = "Subscription has been updated successfully.";

            //Also update the transaction details
            if (null !== $request->getParam('hasTransactionDetail')) {
                $hasTransactionDetail = $request->getParam('hasTransactionDetail');
                try {
                    $transRowID = $transactionCRUD->getRowIDByRefCode($membershipInfo["qcode"]);
                    $output["transactionRowID"] = $transRowID;
                    if(!empty($transRowID) && is_numeric($transRowID)){
					$transResult = $transactionCRUD->update($transRowID, $txn_id, $mode, $note);
                    if (!$transResult["error"]) {
                        $output["message"] .= " Transaction details have been updated. ";
                    }
				   }else{
                    //Create new transaction if not exists
                    try {
                        $transResult = $transactionCRUD->create($user_id, $user_email, "", $planTitle, $membershipInfo["qcode"], $amount, "USD", $txn_id, $txn_id, $txnStatus, $txnStatus, $note, $mode);
                        if (!$transResult["error"]) {
                            $output["message"] .= " Transaction details have been updated. ";
                        }
                    } catch (Exception $e) {
                        $output["message"] .= " Error saving transaction details. ";
                    }
                   }
                } catch (Exception $e) {
                    $output["message"] .= " Error saving transaction details. ";
                }
            }
            echoRespnse(200, $output);
        } else {
            $output["error"] = true;
            $output["message"] = "Failed to update Subscription. Please try again.";
            echoRespnse(200, $output);
        }
    } else {
        $qcode = $membershipCRUD->generateCode();
        $res = $membershipCRUD->create($user_id, $plan_id, $startDate, $expiryDate, $amount, "Active", $qcode, $note);
        if (!$res["error"]) {
            $output["error"] = false;
            $output["message"] = "New subscription has been assigned successfully.";
            $output["expiryDate"] = $expiryDate;
            $output["startDate"] = $startDate;
            $output["id"] = $res["id"];
            $output["user_name"] = $userCRUD->getUsernameByID($user_id);
            /********** CREATE NEW TXN ENTRY *********/
            try {
                $transResult = $transactionCRUD->create($user_id, $user_email, "", $planTitle, $qcode, $amount, "USD", $txn_id, $txn_id, $txnStatus, $txnStatus, $note, $mode);
                if (!$transResult["error"]) {
                    $output["message"] .= " Transaction details have been updated. ";
                }
            } catch (Exception $e) {
                $output["message"] .= " Error saving transaction details. ";
            }

           //Notify by e-mail
            try {
                notifyUserOfNewSubscription($user_email, $userData["firstName"], $planTitle, $startDate, $expiryDate, $qcode);
            } catch  (Exception $e) {
                $output["debug"] = " Error sending e-mail notification for new subscription. ";
            }

            echoRespnse(200, $output);
        } else {
            $output["error"] = true;
            $output["moreInfo"] = $res["msg"];
            $output["message"] = "Failed to assign Subscription. Please try again.";
            echoRespnse(200, $output);
        }
    }
    //echoRespnse(200, $output);
})->add($authenticate);
/**********************************************************/

$app->get('/membership/info/{id}', function ($request, $response, $args) {
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    $output = array();
    if (null !== $request->getAttribute('id')) {
        $id = $request->getAttribute('id');
        $plan = $planCRUD->getID($id);
        $output["error"] = false;
        $output["price"] = $plan["price"];
        $output["duration"] = $plan["duration"];
        echoRespnse(200, $output);
    } else {
        $output["error"] = true;
        $output["message"] = "Failed to fetch plan details.";
        echoRespnse(200, $output);
    }
});

//MANAGE ALL SUBSCRIPTIONS
$app->get('/apis/subscriptions/manage', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/membership_plan.crud.php");
    require_once ("dbmodels/user_membership.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/transaction_details.crud.php");
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $transactionCRUD = new PaymentCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $membershipCRUD = new MembershipCRUD(getConnection());
    $output = array();
    $output["error"] = true;

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $agentID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
        /******** VERIFY IF NON ADMIN  **********/
        if ($agentRole != 1) {
            $output["error"] = false;
            $output["message"] = "Permission denied. ";
            echoRespnse(200, $output);
        }
        /******** VERIFY IF NON ADMIN  **********/
    } else {
        $output["error"] = true;
        $output["message"] = "We could not authenticate this request.";
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    $all_plans = array();
    $all_plans_arr = $membershipCRUD->getAllUserPlansList();
    if (count($all_plans_arr) > 0) {
        foreach ($all_plans_arr as $thisrow) {
            $tmp = getSubscriptionItemDetail($thisrow["id"], true);
            array_push($all_plans, $tmp);
        }
    }
    $output["error"] = false;
    $output["data"] = $all_plans;
    echoRespnse(200, $output);
})->add($authenticate);

/********** UPDATE SUBSCRIPTION *********/
$app->post('/subscriptions/update', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/transaction_details.crud.php");
    $transactionCRUD = new PaymentCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $user_id = $request->getParam('user_id');
    $plan_id = $request->getParam('plan_id');
    if (empty($plan_id) || $plan_id < 0) {
        $response["error"] = true;
        $response["message"] = "Please select a valid subscription plan.";
        echoRespnse(200, $response);
        exit;
    }
    $startDate = $request->getParam('startDate');
    $endDate = "";

    $plan_subtype = 0;
    if (null !== $request->getParam('plan_subtype')) {
        $plan_subtype = $request->getParam('plan_subtype');
    }
    $planTitle = $planCRUD->getNameByID($plan_id);
    $duration = $planCRUD->getTenureByID($plan_id);
    if (!empty($startDate)) {
        //$date = strtotime($startDate);
        //$closingDate = date('M/D/Y', $date);
        //$closingDate = $closingDate->format('Y-m-d H:i:s');
        //$closingDate = $closingDate->format('Y-m-d');
        //$endDate = date('Y-m-d', strtotime($closingDate. ' + 1 days'));
        //$closingDate = new DateTime($closingDate);

        $startDate = new DateTime($startDate);
        $startDate = $startDate->format('Y-m-d');

        $closingDate = new DateTime($startDate);

        if ($plan_id == 2 && $plan_subtype == "Monthly") {
            $closingDate->modify('+30 day');
        } else {
            $closingDate->modify('+' . $duration . ' day');
        }
        $endDate = $closingDate->format('Y-m-d');
    } else {
        $response["error"] = true;
        $response["message"] = "Please select a subscription start date.";
        echoRespnse(200, $response);
        exit;
    }

    //$endDate = $request->getParam('endDate');
    $note = $request->getParam('note');
    $amount = $request->getParam('amount');
    //$amount = $planCRUD->getPriceByID($plan_id);
    $status = "Pending";
    $txnStatus = "Pending";
    if (null !== $request->getParam('status')) {
        $checkStatus = $request->getParam('status');
        if ($checkStatus == 1) {
            $status = "Active";
            $txnStatus = "Completed";
        }
    }

    $txn_id = "";
    if (null !== $request->getParam('txn_id')) {
        $txn_id = $request->getParam('txn_id');
    } else {
        $txn_id = $utilCRUD->generateTXNID(10);
    }

    /*
    if (!checkSession()) {
    $response["error"] = true;
    $response["message"] = "Please login to perform this action.";
    echoRespnse(200, $response);
    exit;
    }
    if ($_SESSION["role_id"] != 1) {
    $response["error"] = true;
    $response["message"] = "You are not authorized to perform this action. ";
    $response["id"] = $id;
    echoRespnse(200, $response);
    exit;
    }
     */

    $numActivePlans = $membershipCRUD->getNumMyActivePlan($user_id);
    if ($numActivePlans > 0) {
        $response["error"] = true;
        $response["message"] = "An active subscription plan already exists for this account. ";
        echoRespnse(200, $response);
        exit;
    }

    $date_created = date('Y-m-d H:i:s');
    $qcode = $membershipCRUD->generateCode();
    $res = $membershipCRUD->create($user_id, $plan_id, $startDate, $endDate, $amount, $status, $qcode, $note);
    if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "Subscription has been assigned successfully.";
        $response["endDate"] = $endDate;
        $response["startDate"] = $startDate;
        $response["id"] = $res["id"];
        $response["user_name"] = $userCRUD->getUsernameByID($user_id);
        $user_email = $userCRUD->getEmail($user_id);
        try {
            $transResult = $transactionCRUD->create($user_id, $user_email, "", $planTitle, $qcode, $amount, "USD", $txn_id, $txn_id, $txnStatus, $txnStatus, $note, "Manual");
            if (!$transResult["error"]) {
                $response["message"] .= " Transaction details have been saved. ";
            }
        } catch (Exception $e) {
            $response["message"] .= " Error saving transaction details. ";
        }
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to assign Subscription. Please try again.";
        echoRespnse(200, $response);
    }
});

/******** INVOICE *********/
$app->get('/invoice/{id}', function ($request, $response, $args) use ($app) {
    $output = array();
    $output["error"] = true;
    $output["message"] = "";
    $docQCode = $request->getAttribute('id');
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/transaction_details.crud.php");
    $paymentCRUD = new PaymentCRUD(getConnection());

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output['error'] = true;
        $output['message'] = 'Invalid request. Please pass your token to proceed.';
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    if (empty($docQCode)) {
        $output["error"] = true;
        $output["message"] = "Invalid Invoice ID. Please try again.";
        echoRespnse(200, $output);
        exit;
    }
    if (!$membershipCRUD->isRefQCodeExists($docQCode)) {
        $output["error"] = true;
        $output["message"] = "Invalid Invoice ID. Please try again.";
        echoRespnse(200, $output);
        exit;
    }
    /*
    if (!$paymentCRUD->isRefQCodeExists($docQCode)) {
    $output["error"] = true;
    $output["message"] = "No transaction details found. Please try again.";
    echoRespnse(200, $output);
    exit;
    }*/
    $membershipEntry = $membershipCRUD->getByQCode($docQCode);

    if (!($agentRole == 1)) {
        if ($accessorID !== $membershipEntry["user_id"]) {
            $output["error"] = true;
            $output["message"] = "You are not authorized to access the requested resource.";
            echoRespnse(200, $output);
            exit;
        }
    }

    $transactionEntry = $paymentCRUD->getByRefCoe($docQCode);
    $thisUser = getUserBasicDetails($membershipEntry["user_id"]);
    //$dateOfSubscription = $utilCRUD->getFormalDate($dateOfSubscription);
    $output["error"] = false;
    $output["result"] = array();
    $output["result"]["membershipEntry"] = $membershipEntry;
    $output["result"]["transactionEntry"] = $transactionEntry;
    $output["result"]["thisUser"] = $thisUser;
    // echo json response
    echoRespnse(200, $output);
})->add($authenticate);

/******** DELETE TRANSACTION DETAILS *********/
$app->post('/apis/subscriptions/delete', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/transaction_details.crud.php");
    $transactionCRUD = new PaymentCRUD(getConnection());
    $output = array();
    $output["error"] = true;

     /******** VERIFY THE REQUESTING AGENT **********/
     $agentValidator = getRequestingAgent($request);
     if (!$agentValidator["error"]) {
         $agentID = $agentValidator["user_info"]["id"];
         $agentRole = $agentValidator["user_info"]["role_id"];
         /******** VERIFY IF NON ADMIN  **********/
         if ($agentRole != 1) {
             $output["error"] = false;
             $output["message"] = "Permission denied. ";
             echoRespnse(200, $output);
         }
         /******** VERIFY IF NON ADMIN  **********/
     } else {
         $output["error"] = true;
         $output["message"] = "We could not authenticate this request.";
         echoRespnse(200, $output);
         exit;
     }
     /******** VERIFY THE REQUESTING AGENT **********/

    $txnQCode = "";
    if (null !== $request->getParam('txnQCode')) {
        $txnQCode = $request->getParam('txnQCode');
    }
    if(empty($txnQCode)){
        $output['error'] = true;
        $output['message'] = 'Subscription ID not found.';
        echoRespnse(200, $output);
        exit;
    }
    if(!$membershipCRUD->doesQCodexists($txnQCode)){
        $output['error'] = true;
        $output['message'] = 'Subscription ID is invalid.';
        echoRespnse(200, $output);
        exit;
    }
    $res = $membershipCRUD->deleteByQcode($txnQCode);
    if ($res) {
        $output["error"] = false;
        $deleteAssignment = $transactionCRUD->deleteByRefCode($txnQCode);
        $output["message"] = "Assigned subscription has been deleted";
        if ($deleteAssignment) {
            $output["message"] .= " successfully. ";
        }
        echoRespnse(200, $output);
    } else {
        $output["error"] = true;
        $output["message"] = "Failed to delete subscription. Please try again.";
        echoRespnse(200, $output);
    }
})->add($authenticate);

$app->get('/apis/subscriptions/validate', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentID = 0;
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $agentID = $agentValidator["user_info"]["id"];
        //$agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output["error"] = true;
        $output["message"] = "BaziChic server could not authenticate this request.";
        //$output["agentValidationError"] = $agentValidator;
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    $thisUser = getUserBasicDetails($agentID);
      /********* Excahnge Session Data ***********/
      $output['userData'] = $thisUser;
      /********* Excahnge Session Data ********/
    /*********** Check Active Subscription ***********/
    $membership_info = array();
    $numMyPlans = $membershipCRUD->getNumMyActivePlan($agentID);
    if ($numMyPlans > 0) {
        $activePaidPlan = $membershipCRUD->getMyActivePlan($agentID);
        if ($activePaidPlan !== null) {
            $output["error"] = false;
            $activePlanName = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activePlanQcode = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activeExpiry = $utilCRUD->getFormalDate($activePaidPlan["date_expiring"]);

            $membership_info["activePlanName"] = $activePlanName;
            $membership_info["activePlanQcode"] = $activePlanQcode;
            $membership_info["activeExpiry"] = $activeExpiry;
            $membership_info["message"] = $activePaidPlan;
            try {
                $date_created = date('Y-m-d H:i:s');
                $numDays = $utilCRUD->dateDiffInDays($date_created, $activePaidPlan["date_expiring"]);
                if ($numDays < 20) {
                    $warning = "The " . $activePlanName . " Subscription is active up to " . $activeExpiry . ".";
                    $warning .= ' Renew within ' . $numDays . ' days to enjoy uninterrupted access.';
                }
            } catch (Exception $e) {
            }
        }
    }
    $output['numMyPlans'] = $numMyPlans;
    $output['membership_info'] = $membership_info;
    echoRespnse(200, $output);
})->add($authenticate);
