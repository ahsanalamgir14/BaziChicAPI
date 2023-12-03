<?php

/******** VIEW SAVED READS *********/
$app->post('/apis/saved_reads', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/document_save.crud.php");
    require_once ("dbmodels/document.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/utils.crud.php");
    require_once ("dbmodels/document_reviews.crud.php");
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once ("dbmodels/document_likes.crud.php");
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $notificationCRUD = new DocumentSaveCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $docCRUD = new DocumentCRUD(getConnection());
    $output = array();
    $output["error"] = false;
    $output["message"] = "";

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output['error'] = true;
        $output['message'] = 'Invalid request. Please pass your token to proceed.';
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    if ($agentRole == 1) {
        $data = $notificationCRUD->getAllTheSaves();
    } else {
        $data = $notificationCRUD->getAllMySaves($accessorID);
    }
    //Do proper session management in helper
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["read_status"] = "Just Started";
            $tmp["id"] = $row["id"];
            $tmp["page"] = $row["page"];
            $tmp["progress"] = $row["progress"];
            if ($row["progress"] > 1 && $row["progress"] < 80) {
                $tmp["read_status"] = $row["progress"] . "% Complete";
            } else {
                $tmp["read_status"] = (100 - $row["progress"]) . "% Left";
            }
            // $tmp["is_downloadable"] = $row["is_downloadable"];
            // $tmp["is_reviewed"] = $reviewCRUD->isReviewedBy($accessorID, $row["id"]);
            // $tmp["is_liked"] = $likeCRUD->isLikedBy($accessorID, $row["id"]);

            $tmp["date_created"] = $row["date_created"];
            // if (!empty($row["date_updated"])) {
            //     $tmp["date_updated"] = $utilCRUD->getTimeDifference($row["date_updated"]);
            // }
            //$tmp["link"] = $row["link"];

            $tmp["title"] = "";
            $tmp["qcode"] = "";
            $tmp["cover"] = "";
            $tmp["doc_type"] = "";
            $document = $docCRUD->getID($row["doc_id"]);
            if($document !== null){
                $tmp["title"] = $document["title"];
                $tmp["qcode"] = $document["qcode"];
                $tmp["cover"] = $document["cover"];
                $tmp["access_verb"] = "Read";
                $tmp["doc_type"] = "Document";
                if($document["document_type"]){
                switch ($document["document_type"]) {
                    case 1:
                        $tmp["doc_type"] = "E-Book";
                        $tmp["access_verb"] = "Read";
                        break;
    
                    case 2:
                        $tmp["doc_type"] = "Audio Book";
                        $tmp["access_verb"] = "Listen";
                        break;
    
                    case 3:
                        $tmp["doc_type"] = "Magazine";
                        $tmp["access_verb"] = "Read";
                        break;
                }
            }
            }
            array_push($custom_data, $tmp);
        }
    }
    $output["error"] = false;
    $output["data"] = $custom_data;
    echoRespnse(200, $output);
})->add($authenticate);

/******** VIEW NOTIFICATIONS *********/
$app->get('/notifications', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/notification.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/utils.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $notificationCRUD = new NotificationCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $accessorID = 0;
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        /********* Excahnge Session Data ***********/
        $thisUser = getUserBasicDetails($accessorID);
        $output['userData'] = $thisUser;
        /********* Excahnge Session Data ********/
        $accessorRole = $agentValidator["user_info"]["role_id"];
        if ($accessorRole == 1) {
            $moderate = true;
        }
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    $s_no = 1;
    $data = $notificationCRUD->getNotificationsFor($accessorID, $s_no);
    $numAllNoti = $notificationCRUD->getNumAllNotisFor($accessorID);
    $numUnreadNoti = $notificationCRUD->getNumUnreadNotifications($accessorID);
    //Do proper session management in helper
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["message"] = $row["message"];
            $tmp["status"] = $row["status"];
            $tmp["sender_image"] = $userCRUD->getUserImageByID($row["sender_id"]);
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            $tmp["action_link"] = $notificationCRUD->getActionLink($row["data_id"], $row["data_title"]);
            array_push($custom_data, $tmp);
        }
    }
    $output["result"] = $custom_data;
    $output["numAllNoti"] = $numAllNoti;
    $output["numUnreadNoti"] = $numUnreadNoti;
    if ($numUnreadNoti <= 0) {
        $output["message"] = "";
    } else {
        if ($numUnreadNoti == 1) {
            $output["message"] = "You have one new notification.";
        } else {
            $output["message"] = $numUnreadNoti . " new notifications.";
        }
    }
    /***Mark Displayed Data as Read
    if (count($data) > 0) {
    foreach ($data as $row) {
    $notificationCRUD->updateStatus($row["id"], "Read");
    }} *****/

    echoRespnse(200, $output);
})->add($authenticate);

/******** VIEW FEW NOTIFICATIONS *********/
$app->get('/apis/notifications/latest', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/notification.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/utils.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $notificationCRUD = new NotificationCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $accessorID = 0;
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $thisUser = $userCRUD->getID($accessorID);
        $accessorRole = $agentValidator["user_info"]["role_id"];
        if ($accessorRole == 1) {
            $moderate = true;
        }
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    $s_no = 1;
    $data = $notificationCRUD->getFewNotificationsFor($accessorID, $s_no);
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["message"] = $row["message"];
            $tmp["status"] = $row["status"];
            $tmp["sender_image"] = $userCRUD->getUserImageByID($row["sender_id"]);
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            $tmp["action_link"] = $notificationCRUD->getActionLink($row["data_id"], $row["data_title"]);
            array_push($custom_data, $tmp);
        }
    }
    $output["data"] = $custom_data;
    $output["message"] = "";
    /***Mark Displayed Data as Read
    if (count($data) > 0) {
    foreach ($data as $row) {
    $notificationCRUD->updateStatus($row["id"], "Read");
    }} *****/

    echoRespnse(200, $output);
})->add($authenticate);

/******** DELETE NOTIFICATIONS *********/
$app->post('/notifications-delete', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/notification.crud.php");
    $notiCRUD = new NotificationCRUD(getConnection());
    $response = array();
    $response["error"] = true;
    $id = $request->getParam('noti_id');
    if (!checkSession()) {
        $response["error"] = true;
        $response["message"] = "Please login to perform this action.";
        echoRespnse(200, $response);
        exit;
    }
    $res = $notiCRUD->delete($id);
    if ($res) {
        $response["error"] = false;
        $response["message"] = "Notification has been deleted successfully. ";
        $response["id"] = $id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to delete Notification. Please try again.";
        echoRespnse(200, $response);
    }
});

/*********** PUSH NOTIFICATION ***********/
$app->post('/apis/push_notifications/send', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once 'dbmodels/utils.crud.php';
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/notification.crud.php");
    $notiCRUD = new NotificationCRUD(getConnection());
    $output = array();
    $output['error'] = true;
    $output['message'] = "";
    $output['note'] = "";
    $accessorID = 0;
    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $accessorRole = $agentValidator["user_info"]["role_id"];
        if (!($accessorRole == 1)) {
            $output['error'] = true;
            $output['message'] = 'You are not authorized to send push notifications.';
            echoRespnse(200, $output);
            return;
        }
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    $status = "Pending";
    $date_created = date('Y-m-d H:i:s');
    $receiver_id = 0;
    $title = "";
    $message = "";
    $data_id = 0;
    $data_title = "";
    $type = "all";

    if (null !== $request->getParam('broadcast_type')) {
        $type = $request->getParam('broadcast_type');
    }
    $receiver = "";
    if (null !== $request->getParam('receiver')) {
        $receiver = $request->getParam('receiver');
    } else {
        $output['error'] = true;
        $output['message'] = "Receivers list can not be empty.";
        echoRespnse(200, $output);
        return;
    }
    $sendEmail = true;
    if (null !== $request->getParam('sendEmail')) {
        $sendEmail = $request->getParam('sendEmail');
    }

    if (null !== $request->getParam('title')) {
        $title = $request->getParam('title');
    } else {
        $output['error'] = true;
        $output['message'] = "Title can not be empty.";
        echoRespnse(200, $output);
        return;
    }

    if (null !== $request->getParam('message')) {
        $message = $request->getParam('message');
    } else {
        $output['error'] = true;
        $output['message'] = "Notification message can not be empty.";
        echoRespnse(200, $output);
        return;
    }
    if (null !== $request->getParam('data_id')) {
        $data_id = $request->getParam('data_id');
    }
    if (null !== $request->getParam('data_title')) {
        $data_title = $request->getParam('data_title');
    }
    $params = array('title' => $title, 'message' => $message, 'data_id' => $data_id, 'data_title' => $data_title);

    $counter = 0;
    $receiverArr = explode(",", $receiver);
    $output['receiverArr'] = $receiverArr;
    //$output['iterations'] = count($receiverArr);

    $output['receivers'] = "";
    if (count($receiverArr) > 0) {
        foreach ($receiverArr as $receiver_id) {
            if (is_numeric($receiver_id) && $receiver_id > 0) {
                $thisName = $userCRUD->getNameByID($receiver_id);
                $thisEmail = $userCRUD->getEmail($receiver_id);
                $output['receivers'] .= $thisEmail . ",";
                $output['notifyResult'] = sendNotify($accessorID, $receiver_id, $title, $message, $data_id, $data_title);
                $output['message'] = "Notification created for " . $thisName . ". ";
                if ($sendEmail) {
                    if (!empty($thisName) && !empty($thisEmail)) {
                        $output['message'] .= "E-mail to " . $thisEmail . " sent.";
                        $output['emailNotifyResult'] = notifyByEmail($thisEmail, $thisName, $params);
                        $counter++;
                    }
                }
                //$thisToken = $userCRUD->getToken($receiver_id);
            }
        }
    } else {
        $output['error'] = true;
        $output['message'] = "No receipt found in request.";
        echoRespnse(200, $output);
        exit;
    }
    $output['error'] = false;
    $output['count'] = $counter;
    if ($counter > 1) {
        $output['message'] = $counter . " notifications sent successfully";
    }
    echoRespnse(200, $output);
})->add($authenticate);

/******** VIEW ACTIVITIES *********/
$app->get('/apis/activities', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/activity.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/utils.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $notificationCRUD = new ActivityCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $output = array();
    $output["error"] = false;
    $output["message"] = "";

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output['error'] = true;
        $output['message'] = 'Invalid request. Please pass your token to proceed.';
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    if ($agentRole == 1) {
        $data = $notificationCRUD->getBaziChicActivitiesFor();
    } else {
        $data = $notificationCRUD->getBaziChicActivitiesFor($accessorID);
    }
    //Do proper session management in helper
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["message"] = $row["message"];
            $tmp["status"] = $row["status"];
            $tmp["user_image"] = $userCRUD->getUserImageByID($row["who_id"]);
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            $tmp["action_link"] = $notificationCRUD->getActionLink($row["data_id"], $row["data_title"]);
            array_push($custom_data, $tmp);
        }
    }

    //Mark Displayed Data as Read
    /*
    if (count($data) > 0) {
    foreach ($data as $row) {
    $notificationCRUD->updateStatus($row["id"], "Read");
    }}
     */

    $output["error"] = false;
    $output["data"] = $custom_data;
    echoRespnse(200, $output);
})->add($authenticate);
