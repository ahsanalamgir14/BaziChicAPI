<?php
/************* LIST DOCS ************/
$app->post('/apis/documents/manage', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    $accessorID = 0;
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $accessorRole = $agentValidator["user_info"]["role_id"];
        if ($accessorRole == 1) {
            $moderate = true;
        }
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    $data = $docCRUD->getAllDocuments(0);
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = getDocDetails($row["id"], $accessorID, true);
            array_push($custom_data, $tmp);
        }
    }
    $output["error"] = false;
    $output["items"] = $custom_data;
    echoRespnse(200, $output);
})->add($authenticate);

/************* LIST DOCS ************/
$app->post('/apis/documents/list', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once "dbmodels/category.crud.php";
    $categoryCRUD = new CategoryCRUD(getConnection());
    $output = array();
    $output["error"] = true;
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    /******** VERIFY THE REQUESTING AGENT **********/
    $data = $docCRUD->getAllDocuments(0);
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["qcode"] = $row["qcode"];
            $tmp["category"] = $categoryCRUD->getNameByID($row["category_id"]);
            //$tmp["numViews"] = $docCRUD->getNumViews($docID);
            $tmp["cover"] = $row["cover"];
            if (!empty($tmp["cover"])) {
                $tmp["cover"] = $tmp["cover"];
            }
            array_push($custom_data, $tmp);
        }
    }
    $output["error"] = false;
    $output["items"] = $custom_data;
    echoRespnse(200, $output);
});

/************* VIEW DOCUMENT ************/
$app->post('/apis/documents/view', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $date_created = date('Y-m-d H:i:s');
    $output = array();
    $output["error"] = true;
    $output["message"] = "";
    $output["adminMode"] = false;
    $output["hasValidMembership"] = false;
    $docQCode = "";
    if (null !== $request->getParam('qcode')) {
        $docQCode = $request->getParam('qcode');
    }
    $watchMode = 0;
    if (null !== $request->getParam('watchMode')) {
        $watchMode = $request->getParam('watchMode');
    }

    $accessorID = 0;
    $accessorRole = 0;
    if (empty($docQCode)) {
        $output["error"] = true;
        $output["message"] = "Invalid document code.";
        echoRespnse(200, $output);
        exit;
    }
    if (!$docCRUD->isQCodeExists($docQCode)) {
        $output["error"] = true;
        $output["message"] = "Document not found.";
        echoRespnse(200, $output);
        exit;
    }

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $accessorRole = $agentValidator["user_info"]["role_id"];
        if($accessorID == 1 && $accessorRole == 1){
            $output["adminMode"] = true;
            $output["hasValidMembership"] = true;
        }
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    $docID = $docCRUD->getIDByQCode($docQCode);
    $thisItem = getDocDetails($docID, $accessorID, false);
    /******** WATCH MODE ON *******/
    if($watchMode > 0){
        if ($accessorID == 0 || $accessorRole == 0)
        {
           $output["error"] = true;
                $output["message"] = "You are not authorized to perform this operation.";
                echoRespnse(200, $output);
                exit;
        }
    //Allow only if user has an active plan
    if($accessorRole != 1){
        require_once ("dbmodels/user_membership.crud.php");
        $membershipCRUD = new MembershipCRUD(getConnection());
        $currentPlan = $membershipCRUD->getMyActivePlan($accessorID);
        //Hide this
        $output["currentPlan"] = $currentPlan;
        if ($currentPlan !== null && $currentPlan["user_id"] == $accessorID) {
            //$output['doc_type_name'] = $thisItem["doc_type_name"];
            $output["hasValidMembership"] = true;
            //If this is e-book only allow premium membership
            if($thisItem["doc_type_name"] == "E-Book"){
                if($currentPlan["plan_id"] !== 2){
                    $output["hasValidMembership"] = false;
                    $output["error"] = true;
                    $output["message"] = $accessorRole."You must have Premium subscription plan to access E-Books.";
                    echoRespnse(200, $output);
                    exit;
                }
            }
        }else{
        $output["error"] = true;
        $output["hasValidMembership"] = false;
        $output["message"] = "You must have an active subscription plan to access this document.";
        echoRespnse(200, $output);
        exit;
        }
        /**** Log Reading Activity ********/
        if($accessorID > 0){
        require_once ("dbmodels/activity.crud.php");
        $activityCRUD = new ActivityCRUD(getConnection());
         try {
            $title = "Reading ". $thisItem["doc_type_name"];
            $activity = $agentValidator["user_info"]["first_name"] . " started reading " . $thisItem["title"] . " now.";
            $activity_res = $activityCRUD->create(1, $title, $activity,  $thisItem["qcode"], $data_title = "Reading", 0, $date_created);
            if ($activity_res["code"] == INSERT_SUCCESS) {
                $output["debug"] = "Logged new reading activity.";
            }} catch (Exception $e) {
            $output["note"] .= "Error logging activity. " . $e->getMessage();
        }
        }
        /**** Log Activity ********/
    }
    }
    /****************************/

    $output["item"] = $thisItem;
    if ($thisItem !== null) {
        $relatedProducts = $docCRUD->getFewRelatedDocuments($thisItem["category_id"]);
        $output["relatedProducts"] = array();
        if (count($relatedProducts) > 0) {
            foreach ($relatedProducts as $row) {
                $tmp = array();
                $tmp["id"] = $row["id"];
                $tmp["title"] = $row["title"];
                $tmp["qcode"] = $row["qcode"];
                $tmp["cover"] = $row["cover"];
                if (!empty($tmp["cover"])) {
                    $tmp["cover"] = $tmp["cover"];
                }

                //$tmp["link"] = $row["link"];
                // if (!empty($tmp["link"])) {
                //     $tmp["link"] = BASE_DOC_URL . "/" . $tmp["link"];
                // }
                array_push($output["relatedProducts"], $tmp);
            }
        }
    }
    //Add viewer analytics
    $docCRUD->addDocumentView($docID, $accessorID);
    $output["error"] = false;
    echoRespnse(200, $output);
});

/************* CREATE NEW DOC ***********/
$app->post('/apis/documents/upload', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/document.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = false;
    $message_suffix = "";

    /****************/
    $user_id = 0;
    $title = "";
    $description = "";
    $qcode = "";
    $cover = "";
    $category_id = "";
    $document_type = 0;
    $doc_type_name = "E-Book";
    $author_name = "";
    $author_link = "";
    $author_desc = "";
    $ext = "";
    $uploadFileName = "";
    $price = 0;
    $num_pages = 0;
    $listen_time = "";
    $read_time = 0;
    $tag = "";
    $is_published = 0;
    $is_downloadable = "";
    $note = "";
    $docType = "";
    /****************/
    $user_id = 1;
    if (null !== $request->getParam('title')) {
        $title = $request->getParam('title');
    }
    if (null !== $request->getParam('description')) {
        $description = $request->getParam('description');
    }
    if (null !== $request->getParam('category_id')) {
        $category_id = $request->getParam('category_id');
    }
    if (null !== $request->getParam('doc_type_name')) {
        $doc_type_name = $request->getParam('doc_type_name');
    }
    if (null !== $request->getParam('document_type')) {
        $document_type = $request->getParam('document_type');
    }
    if (null !== $request->getParam('author_name')) {
        $author_name = $request->getParam('author_name');
    }
    if (null !== $request->getParam('author_link')) {
        $author_link = $request->getParam('author_link');
    }
    if (null !== $request->getParam('author_desc')) {
        $author_desc = $request->getParam('author_desc');
    }
    if (null !== $request->getParam('num_pages')) {
        $num_pages = $request->getParam('num_pages');
    }
    if (null !== $request->getParam('listen_time')) {
        $listen_time = $request->getParam('listen_time');
    }
    if (null !== $request->getParam('read_time')) {
        $read_time = $request->getParam('read_time');
    }
    if (null !== $request->getParam('tag')) {
        $tag = $request->getParam('tag');
    }
    if (null !== $request->getParam('is_published')) {
        $is_published = $request->getParam('is_published') ? 1 : 0;
    }
    if (null !== $request->getParam('is_downloadable')) {
        $is_downloadable = $request->getParam('is_downloadable');
    }
    if (empty($is_published)) {
        $is_published = 0;
    }
    if (empty($is_downloadable)) {
        $is_downloadable = 0;
    }
    if (empty($num_pages)) {
        $num_pages = 0;
    }
    if (empty($listen_time)) {
        $listen_time = 0;
    }
    if (null !== $request->getParam('note')) {
        $note = $request->getParam('note');
    }
    $date_created = date('Y-m-d H:i:s');
    $qcode = $docCRUD->generateCode();
    $datetimecode = date('YmdHis');
    $role_id = $userCRUD->getRoleID($user_id);
    $allowWithoutFile = true;
    /************** DOCUMENT TYPE BASED VALIDATION ****************/
    /* if (empty($_SESSION["userID"]) || $_SESSION["userID"] < 0) {
    $response['error'] = true;
    $response['message'] = 'You must be registered to upload your documents. Signup to get started.';
    echoRespnse(200, $response);
    return;
    }*/

    if (empty($user_id)) {
        $response['error'] = true;
        $response['message'] = 'Invalid session. Login and try again.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($doc_type_name)) {
        $response['error'] = true;
        $response['message'] = 'Invalid document type selected. Please try again.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($title)) {
        $response['error'] = true;
        $response['message'] = 'Please enter a title for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($category_id) || $category_id <= 0) {
        $response['error'] = true;
        $response['message'] = 'You must select a category for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }
    if (empty($document_type) || $document_type <= 0) {
        $response['error'] = true;
        $response['message'] = 'You must select the document type.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($description)) {
        $response['error'] = true;
        $response['message'] = 'You must enter a detailed description about this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (strlen($description) < 30) {
        $response['error'] = true;
        $response['message'] = 'Too short description. Add more detail about this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($author_name)) {
        $response['error'] = true;
        $response['message'] = 'You must enter the author name for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (!empty($author_link)) {
        if (filter_var($author_link, FILTER_VALIDATE_URL)) {
        } else {
            $response['error'] = true;
            $response['message'] = 'You must enter a valid website link that is relevant to the author of this ' . $doc_type_name . '.';
            echoRespnse(200, $response);
            return;
        }}

    $file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $files = $request->getUploadedFiles();
    if ($is_published == 1) {
        if (empty($files['doc_link']) || empty($files['cover_image'])) {
            $response['error'] = true;
            $response['message'] = 'You must upload a cover photo and file to publish this ' . $doc_type_name . '. Uncheck publish option to save as draft.';
            echoRespnse(200, $response);
            exit;
        }
    }

    /**************** ########## 4. DOCUMENT TO DATABASE ######### **********************/
    $res = $docCRUD->create($user_id, $title, $description, $qcode, $cover, $category_id, $document_type, $author_name, $author_link, $author_desc, $ext, $uploadFileName, $price, $num_pages, $listen_time, $read_time, $tag, $is_published, $is_downloadable, $note, $date_created);
    $response['error'] = false;

    if ($res["code"] == INSERT_SUCCESS) {
        $response['error'] = false;
        $response['id'] = $res["id"];
        $response['qcode'] = $qcode;
        $response['message'] = $doc_type_name . ' has been saved successfully.' . $message_suffix;

        /********* START OF KEYWORD SAVE **********/
        try {
            $keyword = $request->getParam('keyword');
            if (!empty($keyword)) {
                $myArray = explode(',', $keyword);
                foreach ($myArray as $my_Array) {
                    $docCRUD->createDocKeyword($res["id"], $my_Array);
                }
            }} catch (Exception $e) {}
        /********* END OF KEYWORD SAVE **********/

        /**** Step 2. START COVER UPLOAD ******/
        $coverUploadResult = doUploadDocumentCover($request, $res["id"], true);
        if (!$coverUploadResult["error"]) {
            //$response['message'] .= $coverUploadResult['message'];
        } else {
            $response['error'] = true;
            $response['message'] = 'Pass the document cover.';
            $response['debug'] = $coverUploadResult["message"];
            echoRespnse(200, $response);
            return;
        }

        /**** Step 3. START FILE UPLOAD ******/
        $fileUploadResult = doUploadDocumentFile($request, $res["id"], true);
        if (!$fileUploadResult["error"]) {
            $response['message'] .= $fileUploadResult['message'];
        } else {
            $response['error'] = true;
            $response['message'] = 'Pass the document file.';
            $response['debug'] = $fileUploadResult["message"];
            echoRespnse(200, $response);
            return;
        }

        /**** Step 5. START AUDIO FILE UPLOAD ******/
        if (!empty($files['audio_files'])) {
            try {
                $response['audioUploadResult'] = $audioUploadResult = doUploadAudioFile($request, $res["id"], true);
                if (!$audioUploadResult["error"]) {
                    $response['message'] .= $audioUploadResult['message'];
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Failed to upload audio.';
                    $response['debug'] = $audioUploadResult["message"];
                    echoRespnse(200, $response);
                    return;
                }
            } catch (Exception $e) {
                $response["error"] = true;
                $response["message"] .= " Failed to upload audio file.";
                echoRespnse(200, $response);
                exit;
            }
        }
        /****** END OF FILE  ****/

        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create " . $doc_type_name . ". Please try again.";
        if ($res["debug"]) {
            $response["debug"] = $res["debug"];
        }

        echoRespnse(200, $response);
        exit;
    }
    /******************* ########## 4. END OF DOCUMENT TO DATABASE ######### **********************/
})->add($authenticate);

/************ UTILITY METHOD FOR DOCUMENT FILE UPLOADS ************/
function doUploadDocumentCover($request, $id, $updateFlag = false)
{
    $response = array();
    $response["error"] = true;
    $response['message'] = "";
    require_once "dbmodels/document.crud.php";
    $docCRUD = new DocumentCRUD(getConnection());
    $thisDoc = $docCRUD->getID($id);
    $doc_type_name = $docCRUD->getDocTypeName($thisDoc["document_type"]);
    $file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $response['debug'] = "";
    $files = $request->getUploadedFiles();
    if (empty($files['cover_image'])) {
        $response['error'] = true;
        $response['message'] = 'You must upload a cover photo and file to publish this ' . $doc_type_name . '.';
        return $response;
    }

    /********* START COVER UPLOAD **********/
    if (!empty($files['cover_image'])) {
        if ($updateFlag) {
            //Delete Cover Image
            $uploadCoverName = $docCRUD->getDocCover($id);
            if (!empty($uploadCoverName)) {
                try {
                    $fileToTest = "uploads/images/docs/$uploadCoverName";
                    if (file_exists($fileToTest)) {
                        unlink($fileToTest);
                        $response['debug'] .= ' Deleted: ' . $fileToTest . '.';
                    }
                } catch (Exception $e) {
                    $response['debug'] .= 'Exception deleting old file: ' . $e->getMessage() . '.';
                }}
        }

        try {
            $newfile = $files['cover_image'];
            $cover_file_type = "Unknown";
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $uploadCoverName = $newfile->getClientFilename();
                $uploadCoverName = explode(".", $uploadCoverName);
                $ext = array_pop($uploadCoverName);
                $ext = strtolower($ext);
                $uploadCoverName = $thisDoc["qcode"] . "." . $ext;

                $file_size = $newfile->getSize();
                $cover_file_type = $newfile->getClientMediaType();
                if (!$cover_file_type == "image/jpg" || !$cover_file_type == "image/jpeg" || !$cover_file_type == "image/jpeg") {
                    $response['error'] = true;
                    $response['message'] = 'Please upload a png, jpg or jpeg image file as the Cover Image.';
                    return $response;
                }

                if ($cover_file_type > 1000000) {
                    $response['error'] = true;
                    $response['message'] = 'Upload a cover image of size not more than 1 MB.';
                    return $response;
                }
                $newfile->moveTo("uploads/images/docs/$uploadCoverName");
                $res = $docCRUD->updateCover($id, $uploadCoverName);
                if ($res) {
                    $response["error"] = false;
                    if ($updateFlag) {
                        $response["message"] = "Cover has been updated successfully. ";
                    } else {
                        $response["message"] = "Cover has been uploaded successfully. ";
                    }
                    return $response;
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to upload cover. Please try again.";
                    return $response;
                }
            }
        } catch (Exception $e) {
            $response["error"] = true;
            $response["message"] = "Failed to upload cover image for " . $doc_type_name . ". Please try again.";
            return $response;
        }
    }
    /********* END OF COVER UPLOAD **********/
    return $response;
}

function doUploadDocumentFile($request, $id, $updateFlag = false)
{
    require_once "dbmodels/document.crud.php";
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $response["message"] = "";
    $thisDoc = $docCRUD->getID($id);
    $qcode = $thisDoc["qcode"];

    //$file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $files = $request->getUploadedFiles();
    /**********************************/
    $date_updated = date('Y-m-d H:i:s');
    /*********##############  3. START OF FILE UPLOAD ############# **********/
    if (!empty($files['doc_link'])) {
        $docCRUD->updateDateModified($id, $date_updated);
        if ($updateFlag) {
            /*********** If doc already exists delete it first ****************/
            $existingImage = $docCRUD->getDocFileLink($id);
            if (!empty($existingImage)) {
                $folder = "uploads/documents/";
                $toDelete = $folder . "" . $existingImage;
                $response["old_file"] = $toDelete;
                if (!unlink($toDelete)) {
                    $response["imgLogs"] = "Existing file can not be deleted. " . $toDelete;
                } else {
                    $response["imgLogs"] = "Existing image deleted.";
                }
            }
        }
        /************************************************/

        try {
            $newfile = $files['doc_link'];
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $uploadFileName = $newfile->getClientFilename();
                $uploadFileName = explode(".", $uploadFileName);
                $ext = array_pop($uploadFileName);
                $ext = strtolower($ext);
                //$uploadFileName = $user_id."_".$date_created.".". $ext;
                $datetimecode = date('YmdHis');
                $uploadFileName = $qcode . "." . $ext;
                $oldFileName = $docCRUD->getDocFileLink($id);

                //$url_name = $docCRUD->getDocUrlName($document_type);
                $file_size = $newfile->getSize();
                $file_size = $file_size / (1024 * 1024);
                if ($file_size > 50) {
                    $response['error'] = true;
                    $response['message'] = 'Your file exceeds the maximum allowed size. Upload a document of less than 50 MB.';
                    return $response;
                }

                $file_type = $newfile->getClientMediaType();
                $fileInfo = $file_type;
                /********** VALIDATE FILE TYPE *********/
                if ($file_type == "application/pdf") {} else {
                    $response['error'] = true;
                    $response['message'] = 'Please upload a valid PDF file.';
                    return $response;
                }

                if (empty($uploadFileName) || strlen($uploadFileName) < 5) {
                    $response['error'] = true;
                    $response['message'] = 'Could not write the file you uploaded. Please check your file and try again.';
                    return $response;
                }
                /********* END FILE TYPE VALIDATION **********/

/********* START FILE UPLOAD **********/
                try {
                    $fileToUpdate = "uploads/documents/$uploadFileName";
                    if (!empty($oldFileName)) {
                        $fileToTest = "uploads/documents/$oldFileName";
                        if (file_exists($fileToTest)) {
                            unlink($fileToTest);
                        }
                    }
                    $newfile->moveTo($fileToUpdate);
                    /****######BREAKER#######***
                    $response["error"] = true;
                    $response["message"] .= $oldFileName." I AM A DEBUG BREAKER =>".$uploadFileName." && UPLOAD-RESULT: ".$uploadResult;
                    echoRespnse(200, $response);
                    exit;
                     ****######BREAKER#######***/

                    $num_pages = 0;
                    if (null !== $request->getParam('num_pages')) {
                        $num_pages = $request->getParam('num_pages');
                    }

                    if ($docCRUD->updateFileLink($id, $uploadFileName)) {
                        $docCRUD->updateFileInfo($id, $file_type, $num_pages, $file_size);
                        $response["error"] = false;
                        $response["message"] = "File uploaded successfully";
                        return $response;
                    } else {
                        $response["error"] = true;
                        $response["message"] = "Failed to upload attachment.";
                        return $response;
                    }
                } catch (Exception $e) {
                    $response["error"] = true;
                    $response["message"] = "Failed to upload attachment with Exception - " . $e->getMessage();
                    return $response;
                }
/********* END OF FILE UPLOAD **********/

            } /*else{
        $response['error'] = true;
        $response['message'] = 'Upload a valid document.';
        echoRespnse(200, $response);
        return;
        }*/
        } catch (Exception $e) {
            $response["error"] = true;
            $response["message"] .= " Error while uploading attached file. Please try again. " . $e->getMessage();
            return $response;
        }

    } else {
        $response['error'] = true;
        $response['message'] = 'Please upload a valid file.';
        return $response;
    }
    /********* END OF FILE UPLOAD UPDATE **********/
}

function doUploadAudioFile($request, $id, $updateFlag = false)
{
    require_once "dbmodels/document.crud.php";
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $response["operations"] = array();
    $response["message"] = "";
    $uploadFileName = "";
    $ext = "";
    $files = $request->getUploadedFiles();
    $order = 0;
    if (null !== $request->getParam('sno')) {
        $order = $request->getParam('sno');
    }
    $qcode = "";
    if (null !== $request->getParam('qcode')) {
        $qcode = $request->getParam('qcode');
    }
    $title = "";
    if (null !== $request->getParam('title')) {
        $title = $request->getParam('title');
    }
    if (empty($title) || strlen($title) < 2) {
        $response['error'] = true;
        $response['message'] = 'Please assign a title for the track.';
        return $response;
    }
    /**********************************/
    $date_updated = date('Y-m-d H:i:s');
    /*********##############  3. START OF FILE UPLOAD ############# **********/
    if (!empty($files['audio_files'])) {
        $existingImage = $qcode . "-" . $order;

        try {
            $audioTracks = $files['audio_files'];
            $trackID = 0;
            $newfile = $audioTracks;
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $uploadFileName = $newfile->getClientFilename();
                $uploadFileName = explode(".", $uploadFileName);
                $ext = array_pop($uploadFileName);
                $ext = strtolower($ext);
                $uploadFileName = $qcode . "-" . $order . "." . $ext;

                if (!empty($uploadFileName)) {
                    $folder = "uploads/audios/";
                    $toDelete = $folder . "" . $uploadFileName;
                    if (file_exists($toDelete)) {
                        $response["old_file"] = $toDelete;
                        if (!unlink($toDelete)) {
                            $response["imgLogs"] = "Existing audio track can not be deleted. " . $toDelete;
                        } else {
                            $response["imgLogs"] = "Existing audio track deleted.";
                        }
                    }
                }
                /**** Delete Done ****/

                $file_size = $newfile->getSize();
                $file_size = $file_size / (1024 * 1024);
                if ($file_size > 50) {
                    $response['error'] = true;
                    $response['message'] = 'Your file exceeds the maximum allowed size. Upload a document of less than 50 MB.';
                    return $response;
                }

                $file_type = $newfile->getClientMediaType();
                /********** VALIDATE FILE TYPE *****/
                if ($file_type == "audio/mpeg" || $file_type == "audio/mp3" || $file_type == "audio/wave" || $file_type == "audio/wav") {} else {
                    $response['error'] = true;
                    $response['file_type'] = $file_type;
                    $response['message'] = 'Please upload a valid file. Allowed formats include mp3, mpeg, wav.';
                    return $response;
                }

                if (empty($uploadFileName) || strlen($uploadFileName) < 5) {
                    $response['error'] = true;
                    $response['message'] = 'Could not write the file you uploaded. Please check your file and try again.';
                    return $response;
                }

                /********* END FILE TYPE VALIDATION **********/

/********* START FILE UPLOAD **********/
                try {
                    $fileToUpdate = "uploads/audios/$uploadFileName";
                    if (!empty($oldFileName)) {
                        $fileToTest = "uploads/audios/$oldFileName";
                        if (file_exists($fileToTest)) {
                            unlink($fileToTest);
                        }
                    }
                    $newfile->moveTo($fileToUpdate);
                    //More Info
                    $filename = $newfile->getClientFilename();
                    if ($docCRUD->addAudioLink($id, $order, $title, $file_type, $uploadFileName)) {
                        $info = "File " . $trackID . " uploaded successfully";
                        array_push($response["operations"], $info);
                        //return $response;
                    } else {
                        array_push($response["operations"], "Error saving track " . $trackID);
                    }
                } catch (Exception $e) {
                    $info = "Failed to upload track " . $trackID . " -> " . $e->getMessage();
                    array_push($response["operations"], $info);
                }
/********* END OF FILE UPLOAD **********/
            }
            $response['error'] = false;
            return $response;
        } catch (Exception $e) {
            $response["error"] = true;
            $response["message"] .= " Error while uploading audio file. Please try again. " . $e->getMessage();
            return $response;
        }

    } else {
        $response['error'] = true;
        $response['message'] = 'Please upload a valid file.';
        return $response;
    }
    return $response;
    /********* END OF FILE UPLOAD UPDATE **********/
}

/******** DELETE DOCUMENT API *********/
$app->post('/apis/documents/delete', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $doc_id = $request->getParam('doc_id');
    $id = $docCRUD->getIDByQCode($doc_id);

    if (!$docCRUD->isQCodeExists($doc_id)) {
        $response['error'] = true;
        $response['message'] = 'The document you are trying to delete is not available at this moment.';
        echoRespnse(200, $response);
        return;
    }

    //Delete Cover Image
    $uploadCoverName = $docCRUD->getDocCover($doc_id);
    if (!empty($uploadCoverName)) {
        try {
            $fileToTest = "uploads/images/docs/$uploadCoverName";
            if (file_exists($fileToTest)) {
                unlink($fileToTest);
            }
        } catch (Exception $e) {}}

    //Delete File
    $uploadFileName = $docCRUD->getDocFileLink($doc_id);
    if (!empty($uploadFileName)) {
        try {
            $fileToTest = "uploads/documents/$uploadFileName";
            if (file_exists($fileToTest)) {
                unlink($fileToTest);
            }
        } catch (Exception $e) {}}

    $res = $docCRUD->delete($id);
    if ($res) {
        $response["error"] = false;
        $response["message"] = "Your Document has been deleted successfully. ";
        $response["id"] = $id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to delete document. Please try again.";
        echoRespnse(200, $response);
    }
})->add($authenticate);

/******** DOCUMENT UPDATE API *********/
$app->post('/apis/documents/update', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/document.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = false;
    $user_id = $request->getParam('user_id');

    //Validate Doc ID
    $id = 0;
    if (null !== $request->getParam('doc_id')) {
        $id = $request->getParam('doc_id');
    }
    if (empty($id) || $id == 0 || !is_numeric($id)) {
        $response['error'] = true;
        $response['message'] = 'Invalid request.';
        echoRespnse(200, $response);
        return;
    }
    if (!$docCRUD->isIDExists($id)) {
        $response['error'] = true;
        $response['message'] = 'This document is not available to modify at this moment. Check back later.';
        echoRespnse(200, $response);
        return;
    }
    /****************/
    $user_id = 0;
    $title = "";
    $description = "";
    $qcode = "";
    $cover = "";
    $category_id = "";
    $document_type = 0;
    $doc_type_name = "E-Book";
    $author_name = "";
    $author_link = "";
    $author_desc = "";
    $ext = "";
    $uploadFileName = "";
    $price = 0;
    $num_pages = 0;
    $listen_time = "";
    $read_time = 0;
    $tag = "";
    $is_published = 0;
    $is_downloadable = "";
    $note = "";
    $docType = "";
    /****************/
    $user_id = 1;
    if (null !== $request->getParam('title')) {
        $title = $request->getParam('title');
    }
    if (null !== $request->getParam('description')) {
        $description = $request->getParam('description');
    }
    if (null !== $request->getParam('category_id')) {
        $category_id = $request->getParam('category_id');
    }
    if (null !== $request->getParam('doc_type_name')) {
        $doc_type_name = $request->getParam('doc_type_name');
    }
    if (null !== $request->getParam('document_type')) {
        $document_type = $request->getParam('document_type');
    }
    if (null !== $request->getParam('author_name')) {
        $author_name = $request->getParam('author_name');
    }
    if (null !== $request->getParam('author_link')) {
        $author_link = $request->getParam('author_link');
    }
    if (null !== $request->getParam('author_desc')) {
        $author_desc = $request->getParam('author_desc');
    }
    if (null !== $request->getParam('num_pages')) {
        $num_pages = $request->getParam('num_pages');
    }
    if (null !== $request->getParam('listen_time')) {
        $listen_time = $request->getParam('listen_time');
    }
    if (null !== $request->getParam('read_time')) {
        $read_time = $request->getParam('read_time');
    }
    if (null !== $request->getParam('tag')) {
        $tag = $request->getParam('tag');
    }
    if (null !== $request->getParam('is_published')) {
        $is_published = $request->getParam('is_published') ? 1 : 0;
    }
    if (null !== $request->getParam('is_downloadable')) {
        $is_downloadable = $request->getParam('is_downloadable');
    }
    if (empty($is_published)) {
        $is_published = 0;
    }
    if (empty($is_downloadable)) {
        $is_downloadable = 0;
    }
    if (empty($num_pages)) {
        $num_pages = 0;
    }
    if (empty($listen_time)) {
        $listen_time = 0;
    }
    if (null !== $request->getParam('note')) {
        $note = $request->getParam('note');
    }
    $date_updated = date('Y-m-d H:i:s');

    $qcode = $docCRUD->getQCodeByID($id);
    if (empty($title)) {
        $response['error'] = true;
        $response['message'] = 'Please enter a title for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($category_id) || $category_id <= 0) {
        $response['error'] = true;
        $response['message'] = 'You must select a category for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($description)) {
        $response['error'] = true;
        $response['message'] = 'You must enter a detailed description about this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (strlen($description) < 30) {
        $response['error'] = true;
        $response['message'] = 'Too short description. Add more detail about this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (empty($author_name)) {
        $response['error'] = true;
        $response['message'] = 'You must enter the author name for this ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        return;
    }

    if (!empty($author_link)) {
        if (filter_var($author_link, FILTER_VALIDATE_URL)) {
        } else {
            $response['error'] = true;
            $response['message'] = 'You must enter a valid website link that is relevant to the author of this ' . $doc_type_name . '.';
            echoRespnse(200, $response);
            return;
        }}
    //Step 2: Perform DB Operation
    $res = $docCRUD->update($id, $title, $description, $category_id, $document_type, $author_name, $author_link, $author_desc, $price, $num_pages, $listen_time, $read_time, $tag, $is_published, $is_downloadable, $note, $date_updated);
    if ($res) {
        $response["error"] = false;
        $response["message"] = $doc_type_name . ' has been updated successfully.';
        $response["id"] = $id;

        /********* START OF KEYWORD SAVE **********/
        try {
            $numTags = 0;
            $keyword = $request->getParam('keyword');
            if (!empty($keyword)) {
                $myArray = explode(',', $keyword);
                $numTags = count($myArray);
                foreach ($myArray as $my_Array) {
                    if ($docCRUD->isTagged($id, $my_Array)) {
                    } else {
                        $docCRUD->createDocKeyword($id, $my_Array);
                    }
                }
            } else {
                $docCRUD->deleteTagsForDocument($id);
            }

            //Process existing keywords
            $existingTags = $docCRUD->getAllTags($id);
            if (count($existingTags) > 0) {
                foreach ($existingTags as $tag_val) {
                    if (in_array($tag_val['keyword'], $myArray)) {} else {
                        $docCRUD->deleteKeywordByID($tag_val['id']);
                    }
                }
            }
        } catch (Exception $e) {
            $response['error'] = true;
            $response['message'] = 'Exception updating keyword: ' . $e->getMessage();
            echoRespnse(200, $response);
            return;
        }
        /********* END OF KEYWORD SAVE **********/

        /*********############ 3. START COVER UPLOAD #############**********/
        $files = $request->getUploadedFiles();
        if (!empty($files['cover_image'])) {
            try {
                $coverUploadResult = doUploadDocumentCover($request, $id, true);
                if (!$coverUploadResult["error"]) {
                    //$response['message'] .= " ".$coverUploadResult['message'];
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Pass the document cover.';
                    $response['debug'] = $coverUploadResult["message"];
                    echoRespnse(200, $response);
                    return;
                }
            } catch (Exception $e) {
                $response["error"] = true;
                $response["message"] .= " Failed to upload cover photo.";
                echoRespnse(200, $response);
                exit;
            }
        }
        /****** END OF COVER ****/

        /**** Step 4. START FILE UPLOAD ******/
        if (!empty($files['doc_link'])) {
            try {
                $fileUploadResult = doUploadDocumentFile($request, $id, true);
                if (!$fileUploadResult["error"]) {
                    $response['message'] .= $fileUploadResult['message'];
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Failed to save document file.';
                    $response['debug'] = $fileUploadResult["message"];
                    echoRespnse(200, $response);
                    return;
                }
            } catch (Exception $e) {
                $response["error"] = true;
                $response["message"] .= " Failed to upload document file.";
                echoRespnse(200, $response);
                exit;
            }
        }
        /****** END OF FILE  ****/
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = 'Failed to update ' . $doc_type_name . '. Please try again.';
        echoRespnse(200, $response);
    }
});
/******** END OF DOCUMENT UPDATE API *********/

/****** ADD DOCUMENT AUDIO TRACK *******/
$app->post('/apis/documents/audio_tracks', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/document.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = false;
    $response['message'] = '';
    /****************/
    $user_id = 0;
    $title = "";
    $qcode = "";
    $order = 1;
    $date_created = date('Y-m-d H:i:s');
    $role_id = $userCRUD->getRoleID($user_id);
    if (null !== $request->getParam('title')) {
        $title = $request->getParam('title');
    }
    if (null !== $request->getParam('qcode')) {
        $qcode = $request->getParam('qcode');
    }
    if (null !== $request->getParam('order')) {
        $order = $request->getParam('order');
    }
    if (empty($qcode)) {
        $response['error'] = true;
        $response['message'] = 'Invalid document code.';
        echoRespnse(200, $response);
        return;
    }
    if (empty($title)) {
        $response['error'] = true;
        $response['message'] = 'Please enter a title for this audio track.';
        echoRespnse(200, $response);
        return;
    }
    if (!$docCRUD->isQCodeExists($qcode)) {
        $response['error'] = true;
        $response['message'] = 'The document you are trying to delete is not available at this moment.';
        echoRespnse(200, $response);
        return;
    }

    $docID = $docCRUD->getIDByQCode($qcode);
    $file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $files = $request->getUploadedFiles();
    $audioTracks = $files['audio_files'];
    /**** Step : START AUDIO FILE UPLOAD ******/
    if (!empty($files['audio_files'])) {
        try {
            $response = doUploadAudioFile($request, $docID, true);
            if (!$response["error"]) {
                $response['error'] = false;
                //$response['message'] = "Audio track uploaded successfully.";
                $response['audio_tracks'] = $docCRUD->getAudioTracksByQcode($qcode);
            } else {
                $response['error'] = true;
                $response['debug'] = $response["message"];
                //$response['message'] = 'Failed to upload audio track.';
                echoRespnse(200, $response);
                return;
            }
        } catch (Exception $e) {
            $response["error"] = true;
            $response["message"] .= " Failed to upload audio file.";
            echoRespnse(200, $response);
            exit;
        }
    } else {
        $response["error"] = true;
        $response["message"] = "You must upload an audio file.";
        echoRespnse(200, $response);
        exit;
    }
    echoRespnse(200, $response);
})->add($authenticate);

/******** DELETE DOCUMENT API *********/
$app->post('/documents/audio_tracks/delete', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $id = $request->getParam('item_id');
    if (!$docCRUD->doesAudioTrackExist($id)) {
        $response['error'] = true;
        $response['id'] = $id;
        $response['message'] = 'The audio track is not available.';
        echoRespnse(200, $response);
        return;
    }

    //Delete File
    $uploadFileName = $docCRUD->getAudioFileUrlByID($id);
    if (!empty($uploadFileName)) {
        try {
            $fileToTest = BASE_AUDIO_URL . "/" . $uploadFileName;
            if (file_exists($fileToTest)) {
                $response["note"] = "Deleted " . $fileToTest;
                unlink($fileToTest);
            }
        } catch (Exception $e) {}
    }
    $docID = $docCRUD->getDocumentIDByTrackID($id);
    $response["docID"] = $docID;
    $response["docFileURL"] = $docCRUD->getAudioFileUrlByID($id);
    $res = $docCRUD->deleteAudioLink($id);
    if ($res) {
        $response["error"] = false;
        $response["message"] = "Audio track has been deleted successfully. ";
        $response["id"] = $id;

        $audio_tracks = $docCRUD->getAudioTracksByID($docID);
        $custom_audio_tracks = array();
        if (count($audio_tracks) > 0) {
            foreach ($audio_tracks as $track) {
                $tmp = array();
                $tmp["id"] = $track["id"];
                $tmp["title"] = $track["title"];
                $tmp["sno"] = $track["sno"];
                $tmp["file"] = $track["file"];
                $tmp["timestamp"] = $track["timestamp"];
                if (!empty($tmp["file"])) {
                    $tmp["file"] = BASE_AUDIO_URL . "/" . $tmp["file"];
                }
                array_push($custom_audio_tracks, $tmp);
            }
        }
        $response["audio_tracks"] = $custom_audio_tracks;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to delete audio track. Please try again.";
        echoRespnse(200, $response);
    }
})->add($authenticate);
