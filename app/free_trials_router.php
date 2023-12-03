<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

    /******** ASSIGN SUBSCRIPTION *********/
	$app->post('/apis/free_trials/grant', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/free_trials.crud.php");
    $freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
    require_once("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once("dbmodels/utils.crud.php");
	$utilCRUD = new UtilCRUD(getConnection());
	require_once("dbmodels/membership_plan.crud.php");
	$planCRUD = new PlanCRUD(getConnection());
	$response = array();
    $response["error"] = true;
		
	$user_id = $request->getParam('user_id');
	$plan_id = $request->getParam('plan_id');
	
	$planTitle = $planCRUD->getNameByID($plan_id);
	$startDate  = new DateTime($startDate);
    $startDate = $startDate->format('Y-m-d');
    $closingDate = new DateTime($startDate);
    $closingDate->modify('+10 day');
    $endDate = $closingDate->format('Y-m-d');
	
	$numActivePlans = $membershipCRUD->getNumMyActivePlan($user_id);
	if($numActivePlans > 0){
	    $latestActivePlan = $membershipCRUD->getMyActivePlan($user_id);
	    $thisActivePlanID = $latestActivePlan["plan_id"];
	    $activePlanTitle = $planCRUD->getNameByID($thisActivePlanID);
	    if($thisActivePlanID == $plan_id){
	    $response["error"] = true;
	    $tillDate = $utilCRUD->getFormalDate($latestActivePlan["date_expiring"]);
        $response["message"] = $planTitle." Subscription is already active for your account upto ".$tillDate.".";
	    echoRespnse(200, $response);
		exit;
	    }
	    
	    if($thisActivePlanID > $plan_id){
	    $response["error"] = true;
	    $tillDate = $utilCRUD->getFormalDate($latestActivePlan["date_expiring"]);
        $response["message"] = $activePlanTitle." Subscription is already active for your account upto ".$tillDate.". Are you sure that you want to start your trial for ".$planTitle."?";
	    echoRespnse(200, $response);
		exit;
	    }else{
	    //$response["error"] = true;
	    //$tillDate = $utilCRUD->getFormalDate($latestActivePlan["date_expiring"]);
        //$response["message"] = $activePlanTitle." Subscription is already active for your account upto ".$tillDate.". This request could not be processed.";
	    //echoRespnse(200, $response);
		//exit;
	    }
	    
	}
	
	
	$numActiveTrials =  $freeTrialsCRUD->getNumMyActivePlan($user_id);
	if($numActiveTrials > 0){
	     $activeTrialWithPlanName = $planCRUD->getNameByID($thisActivePlanID);
	}
	//$numAllActivatesBefore =  $freeTrialsCRUD->getNumAllPlansFor($user_id,  $plan_id);
	$numCurrentActivates =  $freeTrialsCRUD->getNumActivePlansFor($user_id,  $plan_id);
	
	
	
    if($numCurrentActivates > 0){
        $response["error"] = true;
        $response["message"] = "You already have a free trail going on for ".$planTitle." Subscription.";
	    echoRespnse(200, $response);
		exit;
   }
   
    if($numAllActivatesBefore > 0){
        $response["error"] = true;
        $response["message"] = "You have already availed your free trail for ".$planTitle." Subscription.";
	    echoRespnse(200, $response);
		exit;
   }
   
   if($numActiveTrials > 0){
        $response["error"] = true;
        $response["message"] = "Looks like you already have free trials hoing on. Please wait until you activate free trial for ".$planTitle." Subscription.";
	    echoRespnse(200, $response);
		exit;
   }
   
	$date_created = date('Y-m-d H:i:s');
	$res = $freeTrialsCRUD->create($user_id, $plan_id, $startDate, $endDate);
	if (!$res["error"]) {
        $response["error"] = false;
		$endDateFormat = $utilCRUD->getFormalDate($endDate);
        $response["message"] = "Your free trial for ".$planTitle." has been started. Your free trial ends on ".$endDateFormat.".";
		$response["id"] = 	$res["id"];
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to enable the free trial. Please try again.";
		echoRespnse(200, $response);
		}
	});
/**********************************************************/



  /*************** TRIALS LISTING FOR ADMIN ****************/
    $app->get('/free-trials-summary', function($request, $response, $args) {
	require_once("dbmodels/user.crud.php");
	require_once("dbmodels/utils.crud.php");
	require_once("dbmodels/user_membership.crud.php");
	require_once("dbmodels/free_trials.crud.php");
    $freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
	require_once("dbmodels/membership_plan.crud.php");
	require_once("dbmodels/reward_points.crud.php");
    $pointsCRUD = new RewardPointCRUD(getConnection());
    $membershipCRUD = new MembershipCRUD(getConnection());
	$utilCRUD = new UtilCRUD(getConnection());
	$userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    
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
	 
	$data = $freeTrialsCRUD->getAllPlans();
	$custom_data = array();
	if (count($data) > 0) {
			   foreach ($data as $row) {
               $tmp = array();
               $tmp["id"] = $row["id"];
               $tmp["user_id"] = $row["user_id"];
			   $tmp["plan_id"] = $row["plan_id"];
			   $tmp["plan_name"] = $planCRUD->getNameByID($row["plan_id"]);
			   $tmp["user_name"] = $userCRUD->getNameByID($row["user_id"]);
			
			   $tmp["date_created"] =  $utilCRUD->getFormalDate($row["date_created"]);
			   $tmp["date_expiring"] = $utilCRUD->getFormalDate($row["date_expiring"]);
			   array_push($custom_data, $tmp);
			   }
	}
	
    $vars = [
			'page' => [
			'title' => 'View All Free Trials',
			'description' => 'List of All Free Trials',
			'data' => $custom_data
			]
		];	
	return $this->view->render($response, 'admin_free_trials.twig', $vars);
})->setName('free-trials-summary');

	
	
?>