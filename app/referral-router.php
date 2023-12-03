<?php

	/**************** ASSIGN REWARD VIEW ******************/
	$app->get('/assign-reward-points', function ($request, $response, $args)   {
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    	/********** SERVER SESSION CHECK  ***********/
	if(isset($_SESSION["userID"]) && isset($_SESSION["email"]) && isset($_SESSION["api_key"])){
    $thisUser = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
	if ($thisUser != null && $thisUser["id"] == 1 && $thisUser["role_id"] == 1 ) {
	 }else{
		$uri = $request->getUri()->withPath($this->router->pathFor('unauthorized'));
        return $response->withRedirect((string)$uri);
	 }
	}else{
	   	$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}
	 /********** SERVER SESSION CHECK  ***********/
	$listUsers = $userCRUD->getAllUsers();
    // foreach ($categories_arr as $category) {
    // $tmp = array();
    //             $tmp["id"] = $category["id"];
    //             $tmp["title"] = $category["title"];
				// $tmp["sort_id"] = $category["sort_id"];
				// $tmp["qcode"] = $category["qcode"];
				// $subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
    //             array_push($categories , $tmp);
    // }
	  

    $title = 'Grant Reward Points';
		$vars = [
			'page' => [
 			'title' => $title,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'listUsers' => $listUsers
			]
		];
		return $this->view->render($response, 'assign-reward-point.twig', $vars);
	})->setName('assign-reward-points');
	
	
	/********* ASSIGN REWARD POINT API *******/
	$app->post('/apis/reward-points/grant', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
	$userCRUD = new UserCRUD(getConnection());
	require_once("dbmodels/reward_points.crud.php");
    $pointsCRUD = new RewardPointCRUD(getConnection());
	
	$response = array();
    $response["error"] = false;
	$user_id = $request->getParam('user_id');
	$points = $request->getParam('points');
	$note = $request->getParam('note');
	$transaction_type = "Manual";
	$date_created = date('Y-m-d H:i:s');
	
	$status = "Completed";
	
	
	
	/********** SERVER SESSION CHECK  ***********/
	if(isset($_SESSION["userID"]) && isset($_SESSION["email"]) && isset($_SESSION["api_key"])){
    $thisUser = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
	if ($thisUser != null && $thisUser["id"] == 1 && $thisUser["role_id"] == 1 ) {
	 }else{
	 $response['error'] = true;
         $response['message'] = 'You are authorized to perform this action.';
         echoRespnse(200, $response);
		 return;
	 }
	}else{
	    $response['error'] = true;
         $response['message'] = 'You are authorized to perform this action.';
         echoRespnse(200, $response);
		 return;
	}
	/********** SERVER SESSION CHECK  ***********/
	 
	 
	if(empty($user_id) || $user_id <= 0){
		  $response['error'] = true;
         $response['message'] = 'You must select a user account.';
         echoRespnse(200, $response);
		 return;
	}
	
		if(empty($points) || $points <= 0){
		  $response['error'] = true;
         $response['message'] = 'Reward point must be greater than zero.';
         echoRespnse(200, $response);
		 return;
	}
	
   //$userName = $thisUser["first_name"]."". $thisUser["last_name"];
   $userName = $userCRUD->getNameByID($user_id);
	$operationDone = $pointsCRUD->create($user_id, $points, $transaction_type, $note, $status, $date_created);
	if($operationDone["code"] == INSERT_SUCCESS){
		$response['error'] = false;
         $response['message'] = 'Reward points have been credited to '.$userName.' successfully.';
	}else{
		$response['error'] = true;
         $response['message'] = 'There was an error granting reward points. Please try again.';
	}
	echoRespnse(200, $response);
	});
	
$app->get('/referral-codes', function ($request, $response, $args){
	if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}
	require_once("dbmodels/referral.crud.php");
	require_once("dbmodels/utils.crud.php");
	require_once("dbmodels/user.crud.php");
	require_once("dbmodels/reward_points.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	$referCRUD = new ReferralCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $pointsCRUD = new RewardPointCRUD(getConnection());
    $data = $referCRUD->getAllMyReferrals($_SESSION["userID"]);
	$custom_data = array();
	if (count($data) > 0) {
			   foreach ($data as $row) {
			   $tmp = array();
               $tmp["id"] = $row["id"];
               $tmp["code"] = $row["code"];
			   $tmp["status"] = $row["status"];
			   $tmp["date_created"] = $row["date_created"];
			   $tmp["date_updated"] = $row["date_updated"]; 
			   try{
				if(!empty($row["date_created"])){
				   $tmp["date_created"] =  $utilCRUD->getFormalDate($row["date_created"]);
			   }
			   }catch(Exception $e){
			   }
			   $tmp["total_redeems"] = $referCRUD->getNumRedeems($row["code"]);
			   array_push($custom_data, $tmp);
			   }
	}
	$num_codes = $referCRUD->getNumMyRefeerals($_SESSION["userID"], "");
	$num_connections = $referCRUD->getNumMyConnections($_SESSION["userID"]);
	$reward_point =  $pointsCRUD->getCurrentRewardPointFor($_SESSION["userID"]);
	
		$vars = [
			'page' => [
			'title' => 'My Referral Codes',
			'description' => 'List of Referral Codes',
			'data' => $custom_data,
			'num_codes' => $num_codes,
			'reward_points' => $reward_point,
			'num_connections' => $num_connections,
			],
		];		
		return $this->view->render($response, 'referral-codes.twig', $vars);
	})->setName('referral-codes');
	
	
	/********* CREATE NEW REFFERAL CODE *******/
	$app->post('/referrals/create', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
	require_once("dbmodels/referral.crud.php");
	require_once("dbmodels/utils.crud.php");
    $referralCRUD = new ReferralCRUD(getConnection());
	$userCRUD = new UserCRUD(getConnection());
	$utilCRUD = new UtilCRUD(getConnection());
	
	$response = array();
    $response["error"] = false;
	$user_id = $request->getParam('user_id');
	$date_created = date('Y-m-d H:i:s');
	
	if(empty($user_id) || $user_id <= 0){
		  $response['error'] = true;
         $response['message'] = 'You are authorized to generate referral code.';
         echoRespnse(200, $response);
		 return;
	}
	$code = $utilCRUD->generateReferralCode();
	$operationDone = $referralCRUD->create($user_id, $code, $date_created);
	if($operationDone["code"] == INSERT_SUCCESS){
		$response['error'] = false;
         $response['message'] = 'Your referral code has been generated. Please share this code to someone you know.';
	}else{
		$response['error'] = true;
         $response['message'] = 'There was an error generating referral code. Please try again.';
	}
	echoRespnse(200, $response);
	});
	
	
	
	$app->get('/reward-points', function ($request, $response, $args){
	if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('login')); 
        return $response->withRedirect((string)$uri);
	}
	$adminMode = false;
	require_once("dbmodels/utils.crud.php");
	require_once("dbmodels/user.crud.php");
	require_once("dbmodels/reward_points.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $pointsCRUD = new RewardPointCRUD(getConnection());
    $data = $pointsCRUD->getAllMyRewardPoints($_SESSION["userID"]);
	$custom_data = array();
	$points_summary = array();
	
	/********** SERVER SESSION CHECK  ***********/
	if(isset($_SESSION["userID"]) && isset($_SESSION["email"]) && isset($_SESSION["api_key"])){
    $thisUser = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
	if ($thisUser != null && $thisUser["id"] == 1 && $thisUser["role_id"] == 1 ) {
	    $data = $pointsCRUD->getAllRewardPoints();
	    $points_summary = $pointsCRUD->getRewardPointsSummary();
	    $adminMode = true;
	 }
	}else{
	   	$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}
	 /********** SERVER SESSION CHECK  ***********/
	
	
	if (count($data) > 0) {
			   foreach ($data as $row) {
			   $tmp = array();
               $tmp["note"] = $row["note"];
               $tmp["points"] = $row["points"];
			   $tmp["username"] = $userCRUD->getNameByID($row["user_id"]);
			   $tmp["date_created"] =  $utilCRUD->getFormalDate($row["date_created"]);
			   //$tmp["transaction_type"] = $row["transaction_type"];
			   //$tmp["total_redeems"] = $referCRUD->getNumRedeems($row["code"]);
			   array_push($custom_data, $tmp);
			   }
	}

		$vars = [
			'page' => [
			'title' => 'My Reward Points',
			'description' => 'List of Bonus Points',
			'data' => $custom_data,
			'adminMode' => $adminMode
			],
		];		
		return $this->view->render($response, 'reward_points.twig', $vars);
	})->setName('reward-points');
	
?>