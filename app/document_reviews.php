<?php

	/********* SAVE DOCUMENT *******/
	$app->post('/apis/documents/save', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	require_once("dbmodels/notification.crud.php");
	$notiCRUD = new NotificationCRUD(getConnection());
	require_once("dbmodels/activity.crud.php");
	$activityCRUD = new ActivityCRUD(getConnection());
	require_once("dbmodels/document.crud.php");
	$docCRUD = new DocumentCRUD(getConnection());
	require_once("dbmodels/document_save.crud.php");
	$docSaveCRUD = new DocumentSaveCRUD(getConnection());
	$response = array();
    $response["error"] = false;
    $response["title"] = "";
	$user_id = $request->getParam('user_id');
	$doc_id = $request->getParam('doc_id');
	$page = $request->getParam('page');
	$progress = $request->getParam('progress');
	$date_created = date('Y-m-d H:i:s');
	
    if(!$docCRUD->isIDExists($doc_id)){
	 $response['error'] = true;
	  $response["title"] = "Document not found";
     $response['message'] = 'The document you are trying to save is not available at this moment.';
     echoRespnse(200, $response);
	 return;
	}

	if(empty($user_id)){
		  $response['error'] = true;
         $response['message'] = 'You must be logged in to save your reads.';
         echoRespnse(200, $response);
		 return;
	}
	
	$projectName = $docCRUD->getNameByID($doc_id);	
	$doc_type_selected = $docCRUD->getDocType($doc_id);
	$doc_type = "";
	switch($doc_type_selected){
        case 1:
            $doc_type = "E-Book";
            break;
            
         case 2:
            $doc_type = "Audio Book";
            break;
            
            case 3:
            $doc_type = "Magazine";
            break;
    }
		 
	$whoName = $userCRUD->getNameByID($user_id);
	if(empty($page)){
		$page = 0;
	}
if(empty($progress)){
		$progress = 0;
	}
	$updateOperation = false;
	if($docSaveCRUD->isSavedBy($user_id, $doc_id)){
		$record_id = $docSaveCRUD->getActionRecordID($user_id, $doc_id);
		 $response["id"] = $record_id;
		$res = $docSaveCRUD->updateSave($record_id, $page, $progress, $date_created);
		$updateOperation = true;
	}else{
		$res = $docSaveCRUD->create($user_id, $doc_id, $page, $progress, $date_created);
	}
	$ownerID = $docCRUD->getOwnerID($doc_id);	
    $qcode = $docCRUD->getQCodeByID($doc_id);	
	if ($res["code"] == INSERT_SUCCESS) {
        $response["error"] = false;
		if($updateOperation){
		     $response["title"] = "Savepoint Updated";
			$response["message"] = 'Your read for '.$doc_type.' - '.$projectName.' has been saved.';
		}else{
		    $response["title"] = $doc_type." Saved";
			$response["message"] = ''.$doc_type.' - '.$projectName.' saved successfully.';
			$id = $res["id"];
		    $response["id"] = $id;
		}
		
		//Notify now	
		try{
			$title = $doc_type.' Saved';
			$activity = "";
			if($updateOperation){
			$message = 'You updated the '.$doc_type.' - '.$projectName.' savepoint.';
			$activity = $whoName.' resaved  the '.$doc_type.' - '.$projectName.' to library.';
			}else{
			$message = 'You saved the '.$doc_type.' - '.$projectName.' to your library.';
			$activity = $whoName.' saved  the '.$doc_type.' - '.$projectName.' to library.';
			}
			//$noti_res = $notiCRUD->create($user_id, $ownerID, $title, $message, $qcode, $data_title="SavePoint", $status="Pending", $date_created);
			//if ($noti_res["code"] == INSERT_SUCCESS) {
				//$response["message"] .= " Savepoint updated.";
			//}
			//$activity_res = $activityCRUD->create($user_id, $title, $activity, $qcode, $data_title="SavePoint", 0, $date_created);
			//if ($activity_res["code"] == INSERT_SUCCESS) {
				//$response["message"] .= " Log Created.";
			//}
		}catch(Exception $e){
			$response["message"] .= "Error sending notification.";
		}
			 }else{
				  $response["error"] = true;
                  $response["message"] = "Failed to create savepoint. Please try again.";
				  echoRespnse(200, $response);
			 }
	echoRespnse(200, $response);
	});
	
	
	
	/********* REVIEW DOCUMENT *******/
	$app->post('/documents/reviews', function ($request, $respo, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	require_once("dbmodels/notification.crud.php");
	$notiCRUD = new NotificationCRUD(getConnection());
	require_once("dbmodels/activity.crud.php");
	$activityCRUD = new ActivityCRUD(getConnection());
	require_once("dbmodels/document.crud.php");
	$docCRUD = new DocumentCRUD(getConnection());
	require_once("dbmodels/document_reviews.crud.php");
	$reviewCRUD = new DocumentReviewCRUD(getConnection());
	$response = array();
    $response["error"] = false;
    $response["debug"] = "";
	$user_id = $request->getParam('user_id');
	$doc_id = $request->getParam('doc_id');
	$stars = $request->getParam('rating');
	$text = $request->getParam('text');
	$date_created = date('Y-m-d H:i:s');
	

    if(!$docCRUD->isIDExists($doc_id)){
	 $response['error'] = true;
     $response['message'] = 'The document you are reviewing is not available at this moment.';
     echoRespnse(200, $response);
	 return;
	}

	if(empty($user_id)){
		  $response['error'] = true;
         $response['message'] = 'You must be logged in to submit your review.';
         echoRespnse(200, $response);
		 return;
	}
	
	$projectName = $docCRUD->getNameByID($doc_id);	
	$doc_type_selected = $docCRUD->getDocType($doc_id);
	$doc_type = "";
	switch($doc_type_selected){
        case 1:
            $doc_type = "E-Book";
            break;
            
         case 2:
            $doc_type = "Audio Book";
            break;
            
            case 3:
            $doc_type = "Magazine";
            break;
    }
		 
	$whoName = $userCRUD->getNameByID($user_id);
	if(empty($stars)){
		 $response['error'] = true;
         $response['message'] = 'Rate this '.$doc_type.' on the scale of 5 stars.';
         echoRespnse(200, $response);
		 return;
	}
	
	if($stars <=0 || $stars > 5){
		  $response['error'] = true;
         $response['message'] = 'Please give a rating between 1-5 stars based on your experience.';
         echoRespnse(200, $response);
		 return;
	}
	$updateOperation = false;
	if($reviewCRUD->isReviewedBy($user_id, $doc_id)){
		$record_id = $reviewCRUD->getReviewedRecordID($user_id, $doc_id);
		 $response["id"] = $record_id;
		$res = $reviewCRUD->updateReview($record_id, $stars, $text, $date_created);
		$updateOperation = true;
	}else{
		$res = $reviewCRUD->addReview($doc_id, $user_id, $stars, $text, $date_created);
	}
	$ownerID = $docCRUD->getOwnerID($doc_id);	
    $qcode = $docCRUD->getQCodeByID($doc_id);	
	if ($res["code"] == INSERT_SUCCESS) {
        $response["error"] = false;
		if($updateOperation){
			$response["message"] = 'Your Review for the '.$doc_type.' has been updated successfully.';
		}else{
			$response["message"] = 'Your Review for this '.$doc_type.' has been submitted successfully.';
			$id = $res["id"];
		    $response["id"] = $id;
		}
		
		//Notify now	
		try{
			if($updateOperation){
			$title = $doc_type.' Review Updated';
			$message = 'You updated your rating to '.$stars.' stars for '.$doc_type.' '.$projectName.'.';
			$activity = $whoName.' updated his rating to '.$stars.' stars for the '.$doc_type.' '.$projectName.'.';
		}else{
			$title = 'New '.$doc_type.' Review';
			$message = 'You left a '.$stars.' stars rating for '.$doc_type.' '.$projectName.'.';
			$activity = $whoName.' left a '.$stars.' stars rating for the '.$doc_type.' '.$projectName.'.';
		}
			$noti_res = $notiCRUD->create($user_id, $ownerID, $title, $message, $qcode, $data_title="Review", $status="Pending", $date_created);
			if ($noti_res["code"] == INSERT_SUCCESS) {
				$response["message"] .= " Thanks for your feedback.";
			}
			$activity_res = $activityCRUD->create($user_id, $title, $activity, $qcode, $data_title="Review", 0, $date_created);
			if ($activity_res["code"] == INSERT_SUCCESS) {
				//$response["message"] .= " Log Created.";
			}
		}catch(Exception $e){
			$response["debug"] .= "Error sending notification.";
		}
			 }else{
				  $response["error"] = true;
                  $response["message"] = "Failed to post a review. Please try again.".$res["message"] ;
			 }
	//$response["test"] = $ownerID." = ".$whoName." = ".$qcode." | message: ".$message;		 
	echoRespnse(200, $response);
	});
	
	
	/******** DELETE DOCUMENT REVIEW *********/
	$app->post('/document_reviews/delete', function ($request, $respo, $args) use ($app) {	
    require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	require_once("dbmodels/document_reviews.crud.php");
	$reviewCRUD = new DocumentReviewCRUD(getConnection());
	$response = array();
    $response["error"] = true;
	$id = $request->getParam('review_id');
	$review = $reviewCRUD->getID($id);
	$owner_id = $review["user_id"];
	if (!checkSession()) {
		$response["error"] = true;
        $response["message"] = "Please login to perform this action.";
		echoRespnse(200, $response);
		exit;
	}
	if ($_SESSION["role_id"] !== 1) {
	if ($owner_id != $_SESSION["userID"]) {
		$response["error"] = true;
        $response["message"] = "You are not authorized to perform this action. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		exit;
	}
	}
	$res = $reviewCRUD->delete($id);		   
	if ($res) {
        $response["error"] = false;
        $response["message"] = "Your review has been deleted successfully. ";
		$response["id"] = $id;
	    echoRespnse(200, $response);
		}else{
				  $response["error"] = true;
                  $response["message"] = "Failed to delete review. Please try again.";
				  echoRespnse(200, $response);
		}
	});
	
	/********* LIKE DISLIKE DOCUMENT *******/
	$app->post('/documents/endorse', function ($request, $respo, $args) use ($app) {
	if (!checkSession()) {
		 $response['error'] = true;
         $response['message'] = 'You must login to recommend a document.';
         echoRespnse(200, $response);
		 return;
	}
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	require_once("dbmodels/notification.crud.php");
	$notiCRUD = new NotificationCRUD(getConnection());
	require_once("dbmodels/activity.crud.php");
	$activityCRUD = new ActivityCRUD(getConnection());
	require_once("dbmodels/document.crud.php");
	$docCRUD = new DocumentCRUD(getConnection());
	require_once("dbmodels/document_likes.crud.php");
	$likeCRUD = new DocumentLikeCRUD(getConnection());
	$response = array();
    $response["error"] = false;
	$user_id = 0;
	if(isset($_SESSION["userID"])){
		 $user_id = $_SESSION["userID"];
	}else{
		$user_id = $request->getParam('user_id');
	}
	$doc_id = $request->getParam('doc_id');
	$date_created = date('Y-m-d H:i:s');
	
	
	
	if(!$docCRUD->isIDExists($doc_id)){
	 $response['error'] = true;
     $response['message'] = 'The document is not available at this moment.';
     echoRespnse(200, $response);
	 return;
	}
	
	$projectName = $docCRUD->getNameByID($doc_id);	
	$doc_type_selected = $docCRUD->getDocType($doc_id);
	$doc_type = "";
	switch($doc_type_selected){
        case 1:
            $doc_type = "E-Book";
            break;
            
         case 2:
            $doc_type = "Audio Book";
            break;
            
            case 3:
            $doc_type = "Magazine";
            break;
    }
	
	$whoName = $userCRUD->getNameByID($user_id);
	if($likeCRUD->isLikedBy($user_id, $doc_id)){
		$record_id = $likeCRUD->getActionRecordID($user_id, $doc_id);
		$response["id"] = $record_id;
		$res = $likeCRUD->delete($record_id);
		$updateOperation = true;
		if ($res) {
		$response["error"] = false;	
		$response["title"] = "Recommendation Deleted";
		$response["message"] = 'Your recommendation for this '.$doc_type.' has been deleted.';
		}else{
		$response["error"] = true;	
		$response["title"] = "";
		$response["message"] = 'Failed to remove your recommendation for this '.$doc_type.'. Please try again.';
		}
	}else{
		$res = $likeCRUD->create($user_id, $doc_id, $date_created);
		if ($res["code"] == INSERT_SUCCESS) {
        $response["error"] = false;
		$response["next_action"] = "None";
		$response["title"] = $doc_type." Favourited";
		$response["message"] = 'You favourited the '.$doc_type.'- '.$projectName.'.';
		$response["next_action"] = '<i class="fa fa-thumbs-o-down"></i> Remove Endorsement';
		$id = $res["id"];
		$response["id"] = $id;
		//Notify now
		$ownerID = $docCRUD->getOwnerID($doc_id);	
        $qcode = $docCRUD->getQCodeByID($doc_id);		
		try{
			$title = $doc_type." Recommended";
			$message = "You favourited the ".$doc_type." - ".$projectName.".";
			$activity  = $whoName." favourited the ".$doc_type." - ".$projectName.".";
			$noti_res = $notiCRUD->create($user_id, $ownerID, $title, $message, $qcode, $data_title="Like", $status="Pending", $date_created);
			if ($noti_res["code"] == INSERT_SUCCESS) {
				$response["message"] .= ' Thanks for your feedback.';
			}
			$activity_res = $activityCRUD->create($user_id, $title, $activity, $qcode, $data_title="Like", 0, $date_created);
			if ($activity_res["code"] == INSERT_SUCCESS) {
				//$response["message"] .= " Log Created.";
			}
		}catch(Exception $e){
			$response["info"] .= "Error sending notification => ".$e->getMessage();
		}
			 }else{
				  $response["error"] = true;
                  $response["message"] = 'Failed to endorse '.$doc_type.'. Please try again.';
				  echoRespnse(200, $response);
			 }	
	} 
	echoRespnse(200, $response);
	});
	
?>