<?php 
/*************** CUSTOM FINDER ****************/
$app->get('/products-search/{brand}', function($request, $response, $args) {
        require_once("dbmodels/product.crud.php");
		require_once("dbmodels/brand.crud.php");
		require_once("dbmodels/category.crud.php");
		require_once("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
		$productCRUD  = new ProductCRUD(getConnection());
        $brandCRUD = new BrandCRUD(getConnection());
        $categoryCRUD = new CategoryCRUD(getConnection());
		$result = $productCRUD->getAllProducts();
		$brands = $brandCRUD->getActiveBrands();
		/****************** EXAMINE SELECTION *****************/
		$brand_id = $request->getAttribute('brand');
		$selectedCat = "All Brands";
		$status = 1;
		$sql = "SELECT * FROM products WHERE is_published ='$status' AND size > 0 ";	
if(!empty($brand_id) && $brand_id > 0){
    $sql.= "AND brand = '$brand_id' ";
	$selectedCat = $brandCRUD->getNameByID($brand_id);
}else{
	$sql = "SELECT * FROM products ";	
}
$sql.= "ORDER BY size DESC";
$stmt = getConnection()->prepare($sql);
$custom_data = $stmt->execute();
	/****************** EXAMINED SELECTION *****************/
	$custom_data = array();
	if($stmt->rowCount() > 0){
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			    $tmp = array();
               $tmp["id"] = $row["id"];
               $tmp["name"] = $row["name"];
			    $tmp["price"] = $row["price"];
				$tmp["tag"] = $row["tag"];
			   $tmp["category"] = $categoryCRUD->getNameByID($row["product_category_id"]);
			   $tmp["is_published"] = $row["is_published"];
			   $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
		       $tmp["image"] = $row["image"];
			   array_push($custom_data, $tmp);
			   }
	}
		$vars = [
			'page' => [
			'title' => 'Browse Our Products',
			'description' => 'Search products on Armor Bearer Sports',
			'data' => $custom_data,
			'brands' => $brands
			],
			'finder' => [
			'category'=> $selectedCat
			]
		];	
		
		return $this->view->render($response, 'products-with-sidebar.twig', $vars);
});

/*********** PRODUCT CATEGORIES *************/
	$app->get('/apis/categories/list', function ($request, $response, $args) use ($app){
	require_once("dbmodels/category.crud.php");
	$output = array();	
	$helper = new Helper();
    $categoryCRUD = new CategoryCRUD(getConnection());
    $data = $categoryCRUD->getAllCategories();	
	$output["data"] = $data;
	$output["error"] = true;
    $output["message"] = "Categories fetched.";
	echoRespnse(200, $output);
	});
	
$app->post('/categories/create', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/category.crud.php");
    $categoryCRUD = new CategoryCRUD(getConnection());
	//ADMIN ONLY
	$response = array();
    $response["error"] = false;
	$title = $request->getParam('title');
	$description = $request->getParam('description');
	$is_published = $request->getParam('is_published');
	$magazine_only = 0;
	if(null !== $request->getParam('magazine_only')){
	    $magazine_only = $request->getParam('magazine_only');
	}
	
	if(empty($title)){
		 $response['error'] = true;
         $response['message'] = 'Please enter a name for the category.';
         echoRespnse(200, $response);
		 return;
	}
	
	$qcode = $categoryCRUD->generateCode();
	$url = "";
			if(!empty($title)){
			   $url = str_replace(" ", "-", $title);
			   $url = strtolower($url);
		   }
		   $checkUfn = $categoryCRUD->isCodeValid($url);
		   if ($checkUfn){
			   $url = $qcode."-".$url;
		   }
	
	$res = $categoryCRUD->create($title, $description, $qcode, $url, $magazine_only, $is_published);
	if ($res["code"] == INSERT_SUCCESS) {
                $response["error"] = false;
                $response["message"] = "Your category has been added successfully. ";
				$id = $res["id"];
				$response["id"] = $id;
			 }else{
				  $response["error"] = true;
				  $response["info"] = $res["message"] ;
                  $response["message"] = "Failed to add category. Please try again.";
				  echoRespnse(200, $response);
			 }
	echoRespnse(200, $response);
	});

    $app->get('/edit-category/{id}', function($request, $response, $args) {
	require_once("dbmodels/category.crud.php");
	//ADMIN ONLY		
    $categoryCRUD = new CategoryCRUD(getConnection());
	$qcode = $request->getAttribute('id');
	$id = $categoryCRUD->getIDByQCode($qcode);
	$categories = $categoryCRUD->getID($id);
	if($categories !== NULL){
	$title= $categories["title"];
	$description= $categories["description"];
	$taxonomy= $categories["taxonomy"];
	$qcode= $categories["qcode"];
	$magazine_only= $categories["magazine_only"];
	$is_published= $categories["is_published"];
	}
	
    $vars = [
			'page' => [
			'title' => 'Update Category',
			'description' => 'Update your Existing category'
			],
			'category' => [
			'category_id' => $id,
			'title' => $title,
			'description' => $description,
			'taxonomy' => $taxonomy,
			'qcode' => $qcode,
			'magazine_only' => $magazine_only,
			'is_published' => $is_published			
			]
		];	
	return $this->view->render($response, 'category-edit.twig', $vars);
})->setName('edit-category');

	
$app->post('/categories/update', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/category.crud.php");
    $categoryCRUD = new CategoryCRUD(getConnection());
	//ADMIN ONLY	
	$response = array();
    $response["error"] = false;
	$title = $request->getParam('title');
	$taxonomy = $request->getParam('taxonomy');
	$magazine_only = 0;
	if(null !== $request->getParam('magazine_only')){
	    $magazine_only = $request->getParam('magazine_only');
	}
	$id = $request->getParam('category_id');
	$description = $request->getParam('description');
	$is_published = $request->getParam('is_published');
	
	if(empty($title)){
		 $response['error'] = true;
         $response['message'] = 'Please enter a name for the category.';
         echoRespnse(200, $response);
		 return;
	}
	
	$res = $categoryCRUD->update($id, $title, $description, $taxonomy, $magazine_only, $is_published);
	if ($res) {
                $response["error"] = false;
                $response["message"] = "Category ".$title." has been updated successfully. ";
				$response["id"] = $id;
			 }else{
				  $response["error"] = true;
				  $response["info"] = $res["message"] ;
                  $response["message"] = "Failed to update category. Please try again.";
				  echoRespnse(200, $response);
			 }
	echoRespnse(200, $response);
	});	
/*********** END OF CATEGORIES ***********/

	
	$app->post('/categories/delete', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/category.crud.php");	
    $categoryCRUD = new CategoryCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('category_id');
	//Admin check
	$res = $categoryCRUD->delete($id);		   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "Document category has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
				  $response["error"] = true;
                  $response["message"] = "Failed to delete category. Please try again.";
				  echoRespnse(200, $response);
		}
	});
	
?>