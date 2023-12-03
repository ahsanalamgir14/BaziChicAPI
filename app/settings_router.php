<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

    /********** SITE CONFIG ***********/
	$app->get('/system-configuration', function(Request $request, Response $response, $args) {
    require_once("dbmodels/site_settings.crud.php");
    $settingsCRUD = new SiteSettingsCRUD(getConnection());
    require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    
   
    /*
    	if(!checkAdminSession()){
		$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}*/
	//ADMIN ONLY
	 if(isset($_SESSION["userID"]) && isset($_SESSION["api_key"])){
         /********** SERVER SESSION CHECK  ***********/
    $thisUser = $userCRUD->getUserByAPIKey($_SESSION["api_key"]);
     //return true;
	if ($thisUser !== null && $thisUser["id"] == 1 && $thisUser["role_id"] == 1 ) {
	    //return true;
	 }else{
		$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	 }
	 }else{
	     $uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	 }
	 
	$id = 1;
	$settings = $settingsCRUD->getID($id);
    $vars = [
			'page' => [
			'title' => 'Manage Site Configuration',
			'description' => 'Update your Site Settings',
			'settings' => $settings
			]
		];
	return $this->view->render($response, 'site_settings.twig', $vars);
})->setName('system-configuration');


$app->post('/settings/update', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/site_settings.crud.php");
    $settingsCRUD = new SiteSettingsCRUD(getConnection());
	//ADMIN ONLY
	$helper = new Helper();
	
	$response = array();
    $response["error"] = false;
	$name = $request->getParam('name');
	$pay_key = $request->getParam('pay_key');
	$pay_secret = $request->getParam('pay_secret');
	$address = $request->getParam('address');
	$latitude = $request->getParam('latitude');
	$longitude = $request->getParam('longitude');
	$enable_member_uploads = 0;
	$maintenance_on = 0;
	if(null !== $request->getParam('enable_member_uploads')){
		$enable_member_uploads = $request->getParam('enable_member_uploads');
	}
	if(null !== $request->getParam('maintenance_on')){
		$maintenance_on = $request->getParam('maintenance_on');
	}
	$email = $request->getParam('email');
	$facebook_link = $request->getParam('facebook_link');
	$twitter_link = $request->getParam('twitter_link');
	$id = 1;
	if(empty($name)){
		 $response['error'] = true;
         $response['message'] = 'Please enter your site name';
         echoRespnse(200, $response);
		 return;
	}

	$res = $settingsCRUD->update($id, $name, $link, $pay_key, $pay_secret, $address, $latitude, $longitude, $enable_member_uploads, $maintenance_on, $email, $facebook_link, $twitter_link);
	if($res["error"]){
        $response["error"] = false;
        $response["message"] = "System settings has been updated successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to update settings. Please try again.";
		echoRespnse(200, $response);
		}
	});

/*********** END OF CONFIGURATION **********/

?>