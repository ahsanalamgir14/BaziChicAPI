<?php
/***********************/
$authenticate = function($request, $response, $next) {
	require_once("dbmodels/user.crud.php");
	$userCRUD = new UserCRUD(getConnection());
    $headers = $request->getHeaders();
    $output = array();
    $authArr = $request->getHeader("Authorization");
	$api_key = $authArr[0];
	
	$prefix = 'Bearer ';
	if (strpos($api_key, $prefix) !== false) {
    $api_key = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $api_key);
    }
	
	$output["api_key"] = $api_key;
    if (isset($api_key) && !empty($api_key)) {
        if (!$userCRUD->isValidApiKey($api_key)) {
            $output["error"] = true;
            $output["message"] = "Access Denied. Invalid Api key. ".$api_key;
            echoRespnse(401, $output);
			$request = $request->withAttribute('error', true);
            return $response;
        } else {
			$output["error"] = false;
            $output["message"] = "Access Granted.";
			$response = $next($request, $response);
            $userCRUD->getUserByAPIKey($api_key);
            $callerInfo = "New API";
            //$callerInfo = "getUserInfo : ".$request->getUserInfo();
            //$callerInfo .= "getBasePath : ".$request->getBasePath();
            //$callerInfo .= "getHost : ".$request->getScheme();
            
            $userCRUD->addToUsage($api_key, $request->getUri(), $callerInfo);
            return $response->withHeader('Content-type', 'application/json');
        }
    } else {
        $output["error"] = true;
        $output["message"] = "Request token is misssing.";
        echoRespnse(400, $output);
		$request = $request->withAttribute('error', true);
        return $response;
    }
};
/**********************/

?>