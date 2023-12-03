<?php
require __DIR__ . '/../vendor/autoload.php';
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;
use Respect\Validation\Validator as v;

// Application settings
$settings = require __DIR__ . '/../app/settings.php';
$app = new Slim\App($settings);

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$container = $app->getContainer();
//$csrf = $container->csrf;
// Register component on container
$container['view'] = function ($container) {
$view = new \Slim\Views\Twig('templates');
$view->addExtension(new \Slim\Views\TwigExtension($container['router'],
$container['request']->getUri()));
$container['view']->addGlobal('session', $_SESSION);
return $view;
};
$container['upload_directory'] = __DIR__ . '/uploads/documents';
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['view']->render($response->withStatus(404), '404.html', [
            "myMagic" => "Let's roll"
        ]);
    };
};
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};


// Add our dependencies to the container
require __DIR__ . '/../app/dependencies.php';
require __DIR__ . '/../app/middleware.php';
require __DIR__ . '/../app/helper.php';
require __DIR__ . '/../app/data_utils.php';
require __DIR__ . '/../app/routes.php';
require __DIR__ . '/../app/auth_router.php';
require __DIR__ . '/../app/user_routes.php';
require __DIR__ . '/../app/notifications_router.php';
require __DIR__ . '/../app/user_memberships_router.php';
require __DIR__ . '/../app/dashboard_router.php';
require __DIR__ . '/../app/document_routes.php';
require __DIR__ . '/../app/document_category_routes.php';
require __DIR__ . '/../app/document_viewer.php';
//Review below
require __DIR__ . '/../app/document_reviews.php';
require __DIR__ . '/../app/memberships_router.php';
require __DIR__ . '/../app/referral-router.php';
require __DIR__ . '/../app/free_trials_router.php';
require __DIR__ . '/../app/settings_router.php';
require __DIR__ . '/../app/admin_router.php';
require __DIR__ . '/../app/faqs_router.php';

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

function checkAdminSession()
{
    require_once("../app/dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    	 //return true;
    	 
    if(isset($_SESSION["userID"]) && isset($_SESSION["api_key"])){
         /********** SERVER SESSION CHECK  ***********/
    $thisUser = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
	if ($thisUser !== null && $thisUser["id"] == 1 && $thisUser["role_id"] == 1 ) {
	    return true;
	 }else{
		 return false;
	 }
	 /********** SERVER SESSION CHECK  ***********/
	}
	return false;
}


function checkMembership($user_id) {
	require_once("dbmodels/membership_plan.crud.php");
	require_once("dbmodels/user_membership.crud.php");
	require_once("dbmodels/user.crud.php");
	require_once("dbmodels/utils.crud.php");
	$utilCRUD = new UtilCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $membershipCRUD = new MembershipCRUD(getConnection());
    $activePlans = array();
    if($membershipCRUD->getNumMyActivePlan($user_id) > 0){
    $row = $membershipCRUD->getMyActivePlan($user_id);
	$activePlans["id"] = $row["id"];
    $activePlans["user_id"] = $row["user_id"];
    $activePlans["plan_id"] = $row["plan_id"];
    $activePlans["amount"] = $row["amount"];
    $activePlans["date_created"] = $row["date_created"];
	$activePlans["date_expiring"] = $row["date_expiring"];
	$activePlans["plan_name"] = $planCRUD->getNameByID($row["plan_id"]);
	return $activePlans;
    }else{
        return NULL;
    }
}


/*********************************************/
function quickRegister($first_name, $last_name, $email, $country, $dob, $password, $referral_code)
{
           // require_once("dbmodels/user.crud.php");
			require_once 'dbmodels/PassHash.php';
			require_once 'dbmodels/utils.crud.php';
			require_once("dbmodels/referral.crud.php");
			require_once("dbmodels/notification.crud.php");
			require_once("dbmodels/activity.crud.php");
			require_once("dbmodels/user_membership.crud.php");
			require_once("dbmodels/reward_points.crud.php");
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
            if(!email_validation($email)) { 
            $output['error'] = true;
         $output['message'] = 'Please enter a valid email address.';
		 return $output;
    }
    
			$ref_user_id = 0;
		    if(!empty($referral_code)){
			$ref_user_id = $referralCRUD->getUserID($referral_code);
			}
			   
			$phone = "";
			$status = "Pending";
			$date_created = date('Y-m-d H:i:s');
			$role_id = 2;
			$description = "";
			$password_hash = PassHash::hash($password);
		    $api_key = $utilCRUD->generateApiKey();
			$user_name = $utilCRUD->createNewUsername(8);
            $res = $userCRUD->register($first_name, $last_name, $user_name, $phone, $email, $password_hash ,$dob, $country, $description, $date_created, $status, $role_id, $ref_user_id, $referral_code, $api_key);
            if ($res["code"] == INSERT_SUCCESS) {
                $output["error"] = false;
			    $output["message"] = "Great! Your new account has been registered successfully.";
				$user_id = $res["id"];
				$output["id"] = $user_id;
				$output["user_name"] = $user_name;
			
            /********* FOR WEB ***********/			
			//session_start();
			$_SESSION['app'] = "bazichik"; 
	        $_SESSION['userID'] = $user_id;
	        $_SESSION['role_id'] = $role_id;
	        $_SESSION['first_name'] = $first_name;
	        $_SESSION['last_name'] = $last_name;
			$_SESSION['username'] = $user_name;
			$_SESSION['status'] = $status;
			$_SESSION['email'] = $email;
			$_SESSION['api_key'] = $api_key;
			 $_SESSION['user_image'] = $userData["user_image"];
			/********* NOTIFY AND WELCOME ******
			 /********* Notify now ********/	
		try{
			$title = 'Welcome to Bazichik';
			$message = 'Hi '.$first_name.'! Thanks for registering an account with Bazichik.';
			$noti_res = $notiCRUD->create(1, $user_id, $title, $message, $user_name, $data_title="WelcomeSelf", $status="Pending", $date_created);
			if ($noti_res["code"] == INSERT_SUCCESS) {
				$output["note"] .= " Notified successfully.";
			}
		}catch(Exception $e){
			$output["note"] .= " Error notifying.".$e->getMessage();
		}
		/********* Notify Done ********/	
		
			/**** Log Activity ********/
			try{
			$title = "New Registration - ".$first_name." ".$last_name; 
			$activity = $first_name." ".$last_name." from ".$country." registered an account.";
			$activity_res = $activityCRUD->create(1, $title, $activity, $user_name, $data_title="Registration", 0, $date_created);
			if ($activity_res["code"] == INSERT_SUCCESS) {
			$output["note"] .= " Logged new accunt activity.";
			}}catch(Exception $e){
			$output["note"] .= "Error logging activity. ".$e->getMessage();
		    }
		    /**** Log Activity ********/
		    
		    /********** PROCESS REFERRAL ***********/
		    try{
			if(is_numeric($ref_user_id) && $ref_user_id > 0){
				if($referralCRUD->isReferralCodeExist($referral_code)){
					//Update Referral
					$ref_user_id = $referralCRUD->getUserID($referral_code);
					$date_updated = date('Y-m-d H:i:s');
					$referralCRUD->updateReferral($referral_code, $status ="Used", $date_updated);
					$userCRUD->updateFather($user_id, $ref_user_id);
					$referredUser = $userCRUD->getNameByID($user_id);
					$referringUser = $userCRUD->getNameByID($ref_user_id);
					$output['message'] .= ' The Referral Code has been applied.';
					 $output["note"] .= "Referrals Updated.";
					 $ref_message = "Your referral code ".$referral_code." from ".$referringUser." has been applied successfully.";
					 //Notify New User
					 if(!empty($referredUser)){
						  $notiCRUD->create($ref_user_id, $user_id, "Referral Code Applied", $ref_message, $user_id, $data_title="ReferralApplied", $status="Pending", $date_created);
						  $output["note"] .= " Referred user Notified. ";
					 }
					 //Notify Referrering User
					 if(!empty($referringUser)){
						   $con_message = "Congrats! You have a new connection. ".$referredUser." just used your referral code to register an account.";
					       $notiCRUD->create($user_id, $ref_user_id, "New Connection", $con_message, $user_id, $data_title="ReferralApplied", $status="Pending", $date_created);
						   $output["note"] .= " Referring User notified.";
					 }
					 //Process Reward Points
					 $note= $referredUser." registered a new account using code ".$referral_code.".";
					 $rewarding_res = $rewardPointCRUD->create($ref_user_id, $points=5, "Refferal Bonus", $note, "Complete", $date_created);
					 if ($rewarding_res["code"] == INSERT_SUCCESS) {
				     $output["note"] .= " Reward point processed successfully.";
		            	}else{
		            	    $output["note"] .= " Failed to process Reward point. ".$rewarding_res["msg"];
		            	}
				}else{
				//$output['error'] = true;
                $output['message'] .= 'The Referral Code you applied was invalid.';
				 $output["note"] .= "Invalid Referral Code.";
				}
			}
            }catch(Exception $e){
			$output["note"] .= " ERROR DURING REFERRAL PROCESS: ".$e->getMessage();
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
            return $output;
}
/*************************************************************/




function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}