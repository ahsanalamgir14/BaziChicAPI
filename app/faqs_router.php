<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/******** VIEW BAZICHIC FAQS *********/	
	$app->get('/frequently-asked-questions', function (Request $request, Response $response, $args){
	     require_once("dbmodels/utils.crud.php");
	    $utilCRUD = new UtilCRUD(getConnection());
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
        $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
    $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
    
    
				//array_push($tmp["subcategories"] , $subcategory);
                array_push($categories , $tmp);
    }
		$vars = [
			'page' => [
			'title' => 'FAQs | BaziChic Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'categories'=> $categories
			]
		];
		return $this->view->render($response, 'frequently-asked-questions.twig', $vars);
	})->setName('frequently-asked-questions');
	

/******** GET BAZI FAQS *********/
	$app->get('/list-faqs', function (Request $request, Response $response, $args){
        require_once("dbmodels/utils.crud.php");
	    $utilCRUD = new UtilCRUD(getConnection());
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
	    
	    //$docQCode = $request->getAttribute('id');
	    $custom_data = $faqCRUD->getAllFAQs(1);
	    
        $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
        foreach ($categories_arr as $category) {
        $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
    
    
				//array_push($tmp["subcategories"] , $subcategory);
                array_push($categories , $tmp);
    }
	
	
	  /****************** EXAMINE SELECTION *****************/
	$custom_info = "";
	$sql = "SELECT * FROM faqs WHERE is_published = 1";
	$sql_errors = "";
	$allGetVars = $request->getQueryParams();
	$search_item = $allGetVars['search_item'];
	if(!empty($search_item)){
		 try{
		  $custom_info .= "Search for ".$search_item." | ";
		   $sql .= " AND title LIKE '%$search_item%' ";
		 }catch(Exception $e){
		     $sql_errors = "Error1: ".$e->getMessage();
		 }
		}
		
	$category = $allGetVars['category'];
	if(!empty($category)){
	    if($faqCategoryCRUD->isCodeValid($category)){
	        $categoryID = $faqCategoryCRUD->getSubCategoryIDByQCode($category);
	         try{
		 // $custom_info .= "Search for ".$search_item." | ";
		   $sql .= " AND subcategory = '$categoryID' ";
		 }catch(Exception $e){
		     $sql_errors = "Error2: ".$e->getMessage();
		 }
	    }
		}
		
		
$sql.= " ORDER BY id DESC";
$stmt = getConnection()->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();
$custom_data = array();
 foreach ($data as $thisCategory) {
                 $thisCategoryCustom = getFAQDetails($thisCategory["id"]);
				array_push($custom_data , $thisCategoryCustom);
    }
/****************** EXAMINE SELECTION *****************/
 
$vars = [
			'page' => [
			'title' => 'View FAQs',
			'description' => 'Find E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'data' => $custom_data,
			'categories' => $categories
			],
		];
		return $this->view->render($response, 'list-faqs.twig', $vars);
	})->setName('list-faqs');
/**********************************************************/


/******** CREATE NEW FAQ CATEGORY *********/
	$app->post('/apis/faqCategories/create', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$sort_id = $request->getParam('sort_id');
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(strlen($title) > 50){
        $response["error"] = true;
        $response["message"] = "FAQ category title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
	$res = $faqCRUD->create($title, $sort_id);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ category has been created successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to create FAQ category. Please try again.";
		echoRespnse(200, $response);
		}
	});
/**********************************************************/

/******** UPDATE FAQ CATEGORY *********/
	$app->post('/apis/faqCategories/update', function ($request, $respo, $args) use ($app) {
    require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$sort_id = $request->getParam('sort_id');
	$id = $request->getParam('id');
	if(!$faqCRUD->doesIDExists($id)){
		$response["error"] = true;
        $response["message"] = "Looks like we did not find the FAQ category you want to update. ";
	    echoRespnse(200, $response);
		exit;
	}
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ category. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(strlen($title) > 40){
        $response["error"] = true;
        $response["message"] = "FAQ category title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
	$res = $faqCRUD->update($id, $title, $sort_id);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ category has been updated successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to update FAQ category. Please try again.".$res["message"];
		echoRespnse(200, $response);
		}
	});

 /******** DELETE FAQ CATEGORY *********/
	$app->post('/apis/faqCategories/delete', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('id');
	/*
	if (!checkSession()) {
		$response["error"] = true;
        $response["message"] = "Please login to perform this action.";
		echoRespnse(200, $response);
		exit;
	}*/

	$res = $faqCRUD->delete($id);	   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "FAQ category has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to delete FAQ category. Please try again.";
		echoRespnse(200, $response);
		}
	});
	
/************************************************/
    
/******** CREATE NEW FAQ SUBCATEGORY *********/
	$app->post('/apis/faqSubCategories/create', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$category_id = $request->getParam('category_id');
	$sort_id = $request->getParam('sort_id');
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ sub-category. ";
	    echoRespnse(200, $response);
		exit;
   }
   $qcode = $faqCRUD->generateSubCategoryCode();
   if(strlen($title) > 50){
        $response["error"] = true;
        $response["message"] = "FAQ sub-category title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
	$res = $faqCRUD->createSubCategory($title, $qcode, $category_id, $sort_id);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ sub-category has been created successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to create FAQ sub-category. Please try again.";
		echoRespnse(200, $response);
		}
	});
/**********************************************************/

/******** UPDATE FAQ SUBCATEGORY *********/
	$app->post('/apis/faqSubCategories/update', function ($request, $respo, $args) use ($app) {
    require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$category_id = $request->getParam('category_id');
	$sort_id = $request->getParam('sort_id');
	$id = $request->getParam('id');
	if(!$faqCRUD->doesSubCategoryIDExists($id)){
		$response["error"] = true;
        $response["message"] = "Looks like we did not find the FAQ sub-category you want to update. ";
	    echoRespnse(200, $response);
		exit;
	}
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ sub-category. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(strlen($title) > 40){
        $response["error"] = true;
        $response["message"] = "FAQ sub-category title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
   $qcode = $faqCRUD->getSubCategoryQCode($id);
	$res = $faqCRUD->updateSubCategory($id, $title, $qcode, $category_id, $sort_id);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ sub-category has been updated successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to update FAQ sub-category. Please try again.".$res["message"];
		echoRespnse(200, $response);
		}
	});
/**********************************************************/
 /******** DELETE FAQ SUBCATEGORY *********/
	$app->post('/apis/faqSubCategories/delete', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/faq_category.crud.php");
	$faqCRUD = new FAQCategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('id');
	/*
	if (!checkSession()) {
		$response["error"] = true;
        $response["message"] = "Please login to perform this action.";
		echoRespnse(200, $response);
		exit;
	}*/

	$res = $faqCRUD->deleteSubCategory($id);	   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "FAQ sub-category has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to delete FAQ sub-category. Please try again.";
		echoRespnse(200, $response);
		}
	});
	
/************************************************/


/********************* FAQ VIEWS ONLY ***************************/	

/**************** LIST ALL FAQs ******************/
	$app->get('/manage-faqs', function ($request,  $response, $args)   {
	 if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}
	
	require_once("dbmodels/user.crud.php");
	$userCRUD = new UserCRUD(getConnection());
    require_once("dbmodels/utils.crud.php");
	    $utilCRUD = new UtilCRUD(getConnection());
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
    $data = $faqCRUD->getAllFAQs(1);
    
    
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
	 
	 
    $custom_data = array();
	if (count($data) > 0) {
			   foreach ($data as $row) {
			   $tmp = array();
               $tmp["id"] = $row["id"];
               $tmp["title"] = $row["title"];
                $tmp["description"] = $row["description"];
                $tmp["qcode"] = $row["qcode"];
                $tmp["url"] = $row["url"];
                $tmp["category_id"] = $row["category_id"];
                $tmp["category"] = $faqCategoryCRUD->getNameByID($row["category_id"]);
                //$tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
			   array_push($custom_data, $tmp);
			   }
	}
	
	
	 $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
    $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
    
    
				//array_push($tmp["subcategories"] , $subcategory);
                array_push($categories , $tmp);
    }
    
    $subcategories = $faqCategoryCRUD->getAllFAQSubCategories();
    

		$vars = [
			'page' => [
			'title' => 'Manage FAQs',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'data' => $custom_data,
				'categories' => $categories,
					'subcategories' => $subcategories
			],
		];
		return $this->view->render($response, 'manage-faqs.twig', $vars);
	})->setName('manage-faqs');
	
	
	
	/**************** LIST FAQs CATEGORU SUBCATEGORY ******************/
	$app->get('/faqs-category-manager', function ($request,  $response, $args)   {
	 if (!checkSession()) {
		$uri = $request->getUri()->withPath($this->router->pathFor('login'));
        return $response->withRedirect((string)$uri);
	}
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
    require_once("dbmodels/utils.crud.php");
	    $utilCRUD = new UtilCRUD(getConnection());
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
	
	
	 $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
    $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				$tmp["numFaqs"] = $faqCRUD->getNumFAQsInCategory($category["id"]);
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
    
    
				//array_push($tmp["subcategories"] , $subcategory);
                array_push($categories , $tmp);
    }
    
    //$subcategories = $faqCategoryCRUD->getAllFAQSubCategories();
    

		$vars = [
			'page' => [
			'title' => 'Manage FAQs Categories',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'categories' => $categories
			],
		];
		return $this->view->render($response, 'faqs-category-manager.twig', $vars);
	})->setName('faqs-category-manager');
	
	
	/**************** CREATE FAQ VIEW ******************/
	$app->get('/add-faq', function ($request, $response, $args)   {
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
	    
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
	 
        $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
    $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
                array_push($categories , $tmp);
    }

    $updateMode = false;
    $title = 'Add New FAQ';
    //$list_categories = $categoryCRUD->getActiveEbookCategories();
    if($updateMode){
        $title = 'Update FAQ';
    }
		$vars = [
			'page' => [
			'title' => $title,
			'categories' => $categories,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines'
			],
		];
		return $this->view->render($response, 'faq-create.twig', $vars);
	});
	

	/**************** CREATE FAQ VIEW ******************/
	$app->get('/edit-faq/{id}', function ($request, $response, $args)   {
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
        $categories = array();
       
       
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
	 
	 
        $docQCode = $request->getAttribute('id');
	    //$faqEntry = $faqCRUD->getBySEOUrl($docQCode);
	    $faqEntry = $faqCRUD->getByQCode($docQCode);

	    $category_name = $faqCategoryCRUD->getNameByID($faqEntry["category_id"]);
	    $subcategory_name =  $faqCategoryCRUD->getSubCategoryNameByID($faqEntry["subcategory_id"]);
	    $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
       $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				$tmp["qcode"] = $category["qcode"];
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
                array_push($categories , $tmp);
    }
	  

    $title = 'Update FAQ';
		$vars = [
			'page' => [
 			'title' => $title,
			'categories' => $categories,
			'faqEntry' => $faqEntry,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'category_name' => $category_name,
			'subcategory_name' => $subcategory_name
			]
		];
		return $this->view->render($response, 'faq-edit.twig', $vars);
	})->setName('edit-faq');



	/**************** CREATE FAQCATEGORY VIEW ******************/
		$app->get('/add-faq-category', function ($request, $response, $args)   {
        $title = 'Add FAQ Category';
		$vars = [
			'page' => [
 			'title' => $title,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines'
			]
		];
		return $this->view->render($response, 'faq-category-add.twig', $vars);
	})->setName('add-faq-category');
	
	
	
	$app->get('/edit-faq-category/{id}', function ($request, $response, $args)   {
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
        $categories = array();
       
        $docQCode = $request->getAttribute('id');
	    $faqEntry = $faqCategoryCRUD->getID($docQCode);

        $title = 'Update FAQ Category';
		$vars = [
			'page' => [
 			'title' => $title,
			'faqEntry' => $faqEntry,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines'
			]
		];
		return $this->view->render($response, 'faq-category-edit.twig', $vars);
	})->setName('edit-faq-category');
	
	
		/**************** CREATE FAQ SUB-CATEGORY VIEW ******************/
		$app->get('/add-faq-subcategory', function ($request, $response, $args)   {
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());	    
        $title = 'Add FAQ Sub-Category';
        $categories = $faqCategoryCRUD->getAllFAQCategories();
		$vars = [
			'page' => [
 			'title' => $title,
 			'categories' => $categories,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines'
			]
		];
		return $this->view->render($response, 'faq-subcategory-add.twig', $vars);
	    })->setName('add-faq-subcategory');
	    
	
	
	    $app->get('/edit-faq-subcategory/{id}', function ($request, $response, $args)   {
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
        $categories = array();
       
        $docQCode = $request->getAttribute('id');
	    $faqEntry = $faqCategoryCRUD->getSubCategoryByID($docQCode);

	    $category_name = $faqCategoryCRUD->getNameByID($faqEntry["category_id"]);
	    $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
       $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				$tmp["qcode"] = $category["qcode"];
                array_push($categories , $tmp);
    }
	  

    $title = 'Update FAQ Sub-Category';
		$vars = [
			'page' => [
 			'title' => $title,
			'categories' => $categories,
			'faqEntry' => $faqEntry,
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines',
			'category_name' => $category_name
			]
		];
		return $this->view->render($response, 'edit-faq-subcategory.twig', $vars);
	})->setName('edit-faq-subcategory');
	
/************ END OF VIEWS ***********/

/******** CREATE NEW FAQ *********/
	$app->post('/apis/faqs/create', function ($request, $respo, $args) use ($app) {
    require_once("dbmodels/utils.crud.php");
	$utilCRUD = new UtilCRUD(getConnection());
	require_once("dbmodels/faq.crud.php");
	$faqCRUD = new FAQCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$category_id = $request->getParam('category_id');
	$subcategory_id = $request->getParam('subcategory_id');
	$description = $request->getParam('description');
	$sort_id = $request->getParam('sort_id');
	$qcode = $request->getParam('qcode');
	if(empty($sort_id)){
	    $sort_id = 1;
	}
	$is_published = 1;
	if(null !== $request->getParam('is_published')){
	    $is_published = $request->getParam('is_published');
	}
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(strlen($title) > 100){
        $response["error"] = true;
        $response["message"] = "FAQ title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(empty($category_id)){
        $response["error"] = true;
        $response["message"] = "You must select a category for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
    if(empty($subcategory_id)){
        $response["error"] = true;
        $response["message"] = "You must select a sub-category for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
   $url = "";
   $qcode = $faqCRUD->generateCode();
   if(!empty($title)){
        $title = ltrim($title);
		$url = str_replace(" ", "-", $title);
		$url = strtolower($url);
	}
	$checkUfn = $faqCRUD->isUFNExists($url);
	if ($checkUfn){
		$url = $qcode."-".$url;
	}
	//$date_created = date('Y-m-d H:i:s');
	$res = $faqCRUD->create($title, $description, $category_id, $subcategory_id, $sort_id, $qcode, $url, $is_published);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ has been created successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to create FAQ. Please try again.";
		echoRespnse(200, $response);
		}
	});
/**********************************************************/

/******** UPDATE FAQ *********/
	$app->post('/apis/faqs/update', function ($request, $respo, $args) use ($app) {
    require_once("dbmodels/utils.crud.php");
	$utilCRUD = new UtilCRUD(getConnection());
	require_once("dbmodels/faq.crud.php");
	$faqCRUD = new FAQCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$title = $request->getParam('title');
	$category_id = $request->getParam('category_id');
	$subcategory_id = $request->getParam('subcategory_id');
	$description = $request->getParam('description');
	$sort_id = $request->getParam('sort_id');
	//$qcode = $request->getParam('qcode');
	$id = $request->getParam('id');
	if(!$faqCRUD->doesIDExists($id)){
		$response["error"] = true;
        $response["message"] = "Looks like we did not find the FAQ entry you want to update. ";
	    echoRespnse(200, $response);
		exit;
	}
	$is_published = 1;
	if(null !== $request->getParam('is_published')){
	    $is_published = $request->getParam('is_published');
	}
    if(empty($title)){
        $response["error"] = true;
        $response["message"] = "You must enter a title for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(strlen($title) > 100){
        $response["error"] = true;
        $response["message"] = "FAQ title is too large. ";
	    echoRespnse(200, $response);
		exit;
   }
   if(empty($category_id)){
        $response["error"] = true;
        $response["message"] = "You must select a category for FAQ entry. ";
	    echoRespnse(200, $response);
		exit;
   }
   $url = "";
   $qcode = $faqCRUD->getQCode($id);
   if(!empty($title)){
       $title = ltrim($title);
			   $url = str_replace(" ", "-", $title);
			   $url = strtolower($url);
	}
	$checkUfn = $faqCRUD->isUFNExists($url, $id);
	if ($checkUfn){
		$url = $qcode."-".$url;
	}
	$date_created = date('Y-m-d H:i:s');
	$res = $faqCRUD->update($id, $title, $description, $category_id, $subcategory_id, $sort_id, $qcode, $url, $is_published, $date_created);
	if (!$res["error"]) {
        $response["error"] = false;
        $response["message"] = "FAQ has been updated successfully.";
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to update FAQ. Please try again.".$res["message"];
		echoRespnse(200, $response);
		}
	});
/**********************************************************/
	
	
/******** VIEW SINGLE FAQ *********/	
	$app->get('/faq/{id}/{url}', function (Request $request, Response $response, $args){
	    $docQCode = $request->getAttribute('id');
	    $docUrl = $request->getAttribute('url');
	    require_once("dbmodels/utils.crud.php");
	    $utilCRUD = new UtilCRUD(getConnection());
	    require_once("dbmodels/faq.crud.php");
	    $faqCRUD = new FAQCRUD(getConnection());
		require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
        //$categories = $faqCategoryCRUD->getAllFAQCategories();
		
//      if(empty($docQCode)){
// 		$uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
//      return $response->withRedirect((string)$uri);
// 	    }
	    
	  
	    
// 	    if(!$paymentCRUD->isRefQCodeExists($docQCode)){
// 		$uri = $request->getUri()->withPath($this->router->pathFor('404'));
//      return $response->withRedirect((string)$uri);
// 	    }


        $categories = array();
        $categories_arr = $faqCategoryCRUD->getAllFAQCategories();
       foreach ($categories_arr as $category) {
    $tmp = array();
                $tmp["id"] = $category["id"];
                $tmp["title"] = $category["title"];
				$tmp["sort_id"] = $category["sort_id"];
				
				$subcategory = $faqCategoryCRUD->getFAQSubCategories($category["id"]);
				$tmp["subcategories"] = array();
				 $tmp["numCategories"] = count($subcategory);
				 foreach ($subcategory as $thisCategory) {
                 $thisCategoryCustom = getFAQSubCategoryDetails($thisCategory["id"]);
				array_push($tmp["subcategories"] , $thisCategoryCustom);
    }
    
    
				//array_push($tmp["subcategories"] , $subcategory);
                array_push($categories , $tmp);
    }
    
	    
	    $faqEntry = $faqCRUD->getByQCode($docQCode);
	    //$faqEntry = $faqCRUD->getBySEOUrl($docQCode);
		$vars = [
			'page' => [
			'title' => 'View FAQ | Bazichic Metaphysics Consultancy',
			'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
			'categories'=> $categories,
			'faq' => [
			'id' => $faqEntry["id"],
			'title' => $faqEntry["title"],
			'description' => $faqEntry["description"],
			'category_id' => $faqEntry["category_id"],
			'url' => $faqEntry["url"],
			'qcode' => $faqEntry["qcode"]
			]
			]
		];
		return $this->view->render($response, 'faq.twig', $vars);
	})->setName('faq');
	



	
    /******** DELETE FAQ *********/
	$app->post('/apis/faqs/delete', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/faq.crud.php");
	$faqCRUD = new FAQCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('id');
	/*
	if (!checkSession()) {
		$response["error"] = true;
        $response["message"] = "Please login to perform this action.";
		echoRespnse(200, $response);
		exit;
	}*/

	$res = $faqCRUD->delete($id);	   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "FAQ has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
		$response["error"] = true;
        $response["message"] = "Failed to delete FAQ. Please try again.";
		echoRespnse(200, $response);
		}
	});
	
	
	 /**************** GET SUB CATEGORIES ***********/
		$app->get('/apis/faqsubcategories/list/{category}', function($request, $response, $args) {
	    require_once("dbmodels/faq_category.crud.php");
	    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
	    $var_response = array();
		$selectedID = $request->getAttribute('category');
		if(is_numeric($selectedID) && $selectedID > 0){
		$data = $faqCategoryCRUD->getFAQSubCategories($selectedID);	   
	    }
		
        if ($data) {
                $var_response["error"] = false;
				$var_response["result"] = $data;
                $var_response["message"] = count($data)." faqs categories found.";
        } else {
                $var_response["error"] = true;
                $var_response["message"] = "Failed to list faqs subcategories.";
        }
            echoRespnse(200, $var_response);
			//echo json_encode($data);
        });	
	
?>