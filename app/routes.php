<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function sendEmail($to, $subject, $body)
{
    $helper = new Helper();
    $response = array();
    $response = $helper->sendEmail($to, $subject, $body);
    return $response;
}

//stripe Web hook
	$app->get('/subscription-success', function ($request, $respo, $args) use ($app) {
	    require_once("dbmodels/membership_plan.crud.php");
     	$planCRUD = new PlanCRUD(getConnection());
        $stripe = new \Stripe\StripeClient('pk_test_LHMHo4FCtltdVijsCnSjiN8X00Qa33WfAw');
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = 'whsec_GDtHSYimlE3mElx8wyP5nV8eHeDYqexH';
        $payload = @file_get_contents('php://input');
        $title = 'Web Hook Call';
        $data = $planCRUD->createStripeWebhook($title, $payload);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

		$output = array();
        $output["error"] = false;
        $output["data"] = $payload;
        
try {
  $output["debug"] = "Trying hook";    
  $event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $endpoint_secret
  );
   $output["debug"] = "Tried hook"; 
} catch(\UnexpectedValueException $e) {
  // Invalid payload
   $output["debug"] = "Invalid payload"; 
  http_response_code(400);
  exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
  // Invalid signature
  $output["debug"] = "Invalid signature"; 
  http_response_code(400);
  exit();
}


		 echoRespnse(200, $output);
	});
	
$app->get('/apis/bazichic/home', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    require_once ("dbmodels/category.crud.php");
    require_once ("dbmodels/membership_plan.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $data = $docCRUD->getAllDocuments();
    $testimonials = $docCRUD->getAllTestimonials();
    $membership_plans = $planCRUD->getAllActivePlans();
    $categoryCRUD = new CategoryCRUD(getConnection());
    $categories = $categoryCRUD->getAllCategories(1);

    //Get All E-Books
    $ebooks = $docCRUD->getAllDocumentsByDocType(1);
    $allEbooks = array();
    if (count($ebooks) > 0) {
        foreach ($ebooks as $row) {
            $tmp = getDocDetails($row["id"], 0);
            array_push($allEbooks, $tmp);
        }
    }

    //Get All Magazines
    $allMagazinesArr = $docCRUD->getAllDocumentsByDocType(3);
    $allMagazines = array();
    if (count($allMagazinesArr) > 0) {
        foreach ($allMagazinesArr as $row) {
            $tmp = getDocDetails($row["id"], 0);
            array_push($allMagazines, $tmp);
        }
    }

    //Get All Latest Stuffs
    $latest_docs_arr = $docCRUD->getAllLatestLiveDocuments();
    $latest_docs = array();
    if (count($latest_docs_arr) > 0) {
        foreach ($latest_docs_arr as $row) {
            $tmp = getDocDetails($row["id"], 0);
            array_push($latest_docs, $tmp);
        }
    }
    //

    $output = array();
    $output['error'] = false;
    $output['allMagazines'] = $allMagazines;
    $output['latest_docs'] = $latest_docs;
    $output['allEbooks'] = $allEbooks;
    $output['membership_plans'] = $membership_plans;
    $output['testimonials'] = $testimonials;
    $output['categories'] = $categories;
    echoRespnse(200, $output);
});

/**************** HERO *****************/
$app->get('/', function (Request $request, Response $response, $args)   {
    require_once("dbmodels/document.crud.php");
    require_once("dbmodels/category.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    $categoryCRUD = new CategoryCRUD(getConnection());
    require_once("dbmodels/document_reviews.crud.php");
	$reviewCRUD = new DocumentReviewCRUD(getConnection());
	require_once("dbmodels/document_likes.crud.php");
	$likeCRUD = new DocumentLikeCRUD(getConnection());
	//$doc_type = $request->getAttribute('doc_type');
	$doc_type_id =  0;
    $doc_type =  "all";
    $display_type = "Document";
    $data = $docCRUD->getAllDocuments(1);
    
    
    
    /****************** EXAMINE SELECTION *****************/
	$status = 1;
	$custom_info = "";
	$sql = "SELECT * FROM documents WHERE is_published ='$status'";
	$sql_errors = "";
try{
  	$sql.= " ORDER BY id DESC";
$stmt = getConnection()->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();  
}catch(Exception $e){
		     $sql_errors = "Error3: ".$e->getMessage();
		 }
    switch($doc_type){
        case "e-book":
            $display_type = "E-Book";
            $doc_type_id =  1;
            $data = $docCRUD->getAllDocumentsByDocType($doc_type_id);
            break;

         case "audio-book":
            $display_type = "Audio Book";
            $doc_type_id =  2;
            $data = $docCRUD->getAllDocumentsByDocType($doc_type_id);
            break;

         case "magazine":
            $display_type = "Magazine";
            $doc_type_id =  3;
            $data = $docCRUD->getAllDocumentsByDocType($doc_type_id);
            break;
            
        case "all":
            break;
    }
    $categories = $categoryCRUD->getAllCategories(1);
    $custom_data = array();
	if (count($data) > 0) {
			   foreach ($data as $row) {
			   $tmp = array();
               $tmp["id"] = $row["id"];
               $tmp["title"] = $row["title"];
                //$tmp["description"] = $row["description"];
                 $tmp["price"] = $row["price"];
                //$tmp["category_id"] = $row["category_id"];
                //$tmp["document_type"] = $row["document_type"];
            
                $tmp["qcode"] = $row["qcode"];
                $tmp["avg_rating"] = $reviewCRUD->getAvgReviewsFor($row["id"]);
                $tmp["num_reviews"] = $reviewCRUD->getNumReviewsFor($row["id"]);
                $tmp["num_likes"] = $likeCRUD->getNumLikes($row["id"]);
                $tmp["cover"] = $row["cover"];

        $is_liked = false;
			   array_push($custom_data, $tmp);
		}}
	
		$vars = [
			'page' => [
			'title' => 'E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'description' => 'Find E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'data' => $custom_data,
			'display_type' => $display_type,
			'doc_type_id' => $doc_type_id,
			'custom_info' => $custom_info,
			'carosell_view' => true,
			'sql' => $sql
			],
		];
		return $this->view->render($response, 'e-book-store.twig', $vars);
	})->setName('e-book-store');


/************ EBOOK READER *****************/
//$app->post('/ebook-reader', function (Request $request, Response $response, $args)   {
$app->post('/ebook-reader', function ($request, $response, $args) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());

    require_once ("dbmodels/document_save.crud.php");
    $docSaveCRUD = new DocumentSaveCRUD(getConnection());

    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/free_trials.crud.php");
    $freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());
    $doc_id = $request->getParam('doc_id');
    $doc_link = $request->getParam('doc_link');
    $is_downloadable = $request->getParam('is_downloadable');

    if (!$docCRUD->isIDExists($doc_id)) {
        $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string) $uri);
    }

    //Get Doc Type too
    $docTypeID = $docCRUD->getDocType($doc_id);
    $docTypeName = $docCRUD->getDocTypeName($doc_id);
    $allowReading = false;
    $isTrialPeriodOn = 0;
    $trialMessage = "";
    $userMessage = "Looks like you are not authorized to access this content. ";

    /********** SERVER SESSION CHECK  ***********/
    if (true) {
        $thisUser = $userCRUD->getUserByAPIKey("18b95e4144faef78496e9e70ade1e464");
        if ($thisUser !== null) {
            /*********************/
            if ($thisUser["role_id"] == 1) {
                $allowReading = true;
                $userMessage = "You are viewing this as Admin.";
            } 
        } else {
            $uri = $request->getUri()->withPath($this->router->pathFor('login'));
            return $response->withRedirect((string) $uri);
        }
    }
    /********** SERVER SESSION CHECK  ***********/

    //If Doc is saved get the page number
    $openPage = 1;
    if ($docSaveCRUD->getLastSavedPage($agentID, $doc_id) > 0) {
        $openPage = $docSaveCRUD->getLastSavedPage($agentID, $doc_id);
    }

    $vars = [
        'page' => [
            'title' => 'E-Book Reader | BaziChic - Chinese Metaphysics Consultancy',
            'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
            'doc_id' => $doc_id,
            'doc_link' => $doc_link,
            'is_downloadable' => $is_downloadable,
            'allowReading' => $allowReading,
            'userMessage' => $userMessage,
            'isTrialPeriodOn' => $isTrialPeriodOn,
            'trialMessage' => $trialMessage,
            'openPage' => $openPage,
        ],
    ];
    return $this->view->render($response, 'ebook-reader.twig', $vars);
})->setName('ebook-reader');
/******************* END OF EBOOK READER ******************/

/********* CONTACT PROCESSING *******/
$app->post('/contact/submit', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/contacts.crud.php");
    $contactsCRUD = new ContactsCRUD(getConnection());
    require_once ('recaptcha/recaptchalib.php');
    $publickey = "6LfazN4UAAAAAHcW2a4LovKYvU-FhXAW7rmazyyy";
    //$capcha = recaptcha_get_html($publickey);
    $response = array();
    $response["error"] = false;
    $name = $request->getParam('name');
    $email = $request->getParam('email');
    $message = $request->getParam('comments');
    $subject = $request->getParam('subject');
    $date_created = date('Y-m-d H:i:s');
    $captcha = $request->getParam('captcha');

    if (empty($name)) {
        $response['error'] = true;
        $response['message'] = 'Please enter your full name.';
        echoRespnse(200, $response);
        return;
    }
    if (empty($email)) {
        $response['error'] = true;
        $response['message'] = 'Please enter a valid email address.';
        echoRespnse(200, $response);
        return;
    }
    if (empty($message)) {
        $response['error'] = true;
        $response['message'] = 'You must enter your message.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($captcha)) {
        $response['error'] = true;
        $response['message'] = 'Please enter the captcha to submit this form.';
        echoRespnse(200, $response);
        return;
    }

    if ($captcha != "bazichic") {
        $response['error'] = true;
        $response['message'] = 'Please enter a valid captcha.';
        echoRespnse(200, $response);
        return;
    }

    // Validate reCAPTCHA box
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $secretKey = '6LfazN4UAAAAAE7K4VszbU2fbnuKXEXYv-e69wzs';
        // Verify the reCAPTCHA response
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $_POST['g-recaptcha-response']);
        // Decode json data
        $capchaResponseData = json_decode($verifyResponse);
        $response["capchaResponseData"] = $capchaResponseData;
        // If reCAPTCHA response is valid
        if ($capchaResponseData->success) {

        } else {
            $statusMsg = 'Robot verification failed, please try again.';
        }
    } else {
        //$response['error'] = true;
        //$statusMsg = 'Please check on the reCAPTCHA box.';
        //$response['message'] = $statusMsg;
        //echoRespnse(200, $response);
        //return;
    }

    $res = $contactsCRUD->create($name, $email, $subject, $message, $date_created);
    //$to      = 'support@bazichic.com';
    $to = 'customer_support@bazichic.com';
    $headers = 'From: ' . $email . '' . "\r\n" .
    'Reply-To: ' . $email . '' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    mail("digitechgeeksolutions@gmail.com", "From BaziChic " . $subject, $message, $headers);
    if ($res["code"] == INSERT_SUCCESS) {
        $response["error"] = false;
        $response["message"] = "Your message has been sent successfully. We will get back to you as soon as possible.";
        $id = $res["id"];
        $response["id"] = $id;
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to send message. Please try again.";
        echoRespnse(200, $response);
    }
    echoRespnse(200, $response);
});

$app->get('/404', function (Request $request, Response $response, $args) {
    $vars = [
        'page' => [
            'title' => 'Page Not Found | BaziChic',
            'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
        ],
    ];

    return $this->view->render($response, '404.html', $vars);
})->setName('404');

/**************** STARTS COMING SOON ******************/
$app->get('/coming-soon', function (Request $request, Response $response, $args) {
    $helper = new Helper();
    if ($helper->isMaintenanceModeOn()) {
        $vars = [
            'page' => [
                'title' => 'Coming Soon | Bazichik - Chinese Metaphysics Consultancy',
                'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
            ],
        ];
        return $this->view->render($response, 'coming-soon.html', $vars);
    } else {
        $uri = $request->getUri()->withPath($this->router->pathFor('404'));
        return $response->withRedirect((string) $uri);
    }
})->setName('coming-soon');
/**************** ENDS COMING SOON ******************/

/*********************** THE MDDLE TT ****************************/
function getRequestingAgent($request)
{
    require_once "dbmodels/user.crud.php";
    $userCRUD = new UserCRUD(getConnection());
    $headers = $request->getHeaders();
    $output = array();
    $output["error"] = true;
    $output["message"] = "Invalid Api key";
    $authArr = $request->getHeader("Authorization");
    //$api_key = $authArr[0];
    $api_key = "";
    if (isset($authArr[0])) {
        $api_key = $authArr[0];
    }
    //Remove Bearer String if exists
    $prefix = 'Bearer ';
    if (strpos($api_key, $prefix) !== false) {
        $api_key = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $api_key);
    }
    $output["api_key"] = $api_key;
    if (isset($api_key) && !empty($api_key)) {
        if (!$userCRUD->isValidApiKey($api_key)) {
            $output["error"] = true;
            $output["message"] = "Access Denied. Invalid Authorization key.";
        } else {
            $output["user_info"] = $userCRUD->getUserByAPIKey($api_key);
            $output["error"] = false;
            $output["message"] = "Access Granted.";
        }
    } else {
        $output["error"] = true;
        $output["message"] = "Access Denied. No Authorization found with request.";
    }
    return $output;
}
/***************************************************/

function getConnection()
{
    $host = 'localhost';
    // $db = 'bazichic_uatdb';
    // $user = 'bazichic_user';
    // $pass = '}gt^V&EHES{V';
    //Prod
    $db   = 'bazichic_db';
    $user = 'bazichic_dbuser';
    $pass = '?jBUGWn?BglC';
    $charset = 'utf8';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opt);
    return $pdo;
}

function echoRespnse($status_code, $response)
{
    //$app = \Slim\Slim::getInstance();
    // Http response code
    //$app->status($status_code);
    // setting response content type to json
    //$app->contentType('application/json');
    echo json_encode($response);
}
