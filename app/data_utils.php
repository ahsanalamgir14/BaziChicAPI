<?php
function getSubscriptionItemDetail($subscription_id, $extended = false)
{
    require_once "dbmodels/user.crud.php";
    $userCRUD = new UserCRUD(getConnection());
    require_once "dbmodels/utils.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    require_once "dbmodels/membership_plan.crud.php";
    $planCRUD = new PlanCRUD(getConnection());
    require_once "dbmodels/user_membership.crud.php";
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once "dbmodels/transaction_details.crud.php";
    $paymentCRUD = new PaymentCRUD(getConnection());
    $row = $membershipCRUD->getID($subscription_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["user_id"] = $row["user_id"];
        if ($extended) {
            $tmp["user_name"] = "";
            $tmp["user_name"] = $userCRUD->getUsernameByID($row["user_id"]);
            $tmp["full_name"] = $userCRUD->getNameByID($row["user_id"]);
        }
        $tmp["plan_id"] = $row["plan_id"];
        $tmp["plan_name"] = $planCRUD->getNameByID($row["plan_id"]);
        $tmp["amount"] = $row["amount"];
        $tmp["qcode"] = $row["qcode"];
        $tmp["note"] = $row["note"];

        $tmp["isCurrent"] = $membershipCRUD->isSubscriptionActive($row["id"]);
        //$tmp["role"] = $userCRUD->getRoleName($row["role_id"]);
        $tmp["timestamp"] = $row["timestamp"];
        $tmp["date_created"] = $row["date_created"];
        $tmp["date_expiring"] = $row["date_expiring"];
        $tmp["date_updated"] = $row["date_updated"];
        $tmp["hasTransactionDetail"] = false;
        $tmp["paymentMode"] = "";
        $tmp["txnID"] = "";
        $tmp["status"] = "";
        if ($paymentCRUD->isRefQCodeExists($row["qcode"])) {
            $tmp["hasTransactionDetail"] = true;
            $txnDetails = $paymentCRUD->getByRefCoe($row["qcode"]);
            if ($txnDetails && $txnDetails["mode"]) {
                $tmp["paymentMode"] = $txnDetails["mode"];
                $tmp["txnID"] = $txnDetails["txn_id"];
                $tmp["status"] = $txnDetails["status"];
                //$tmp["timestamp"] = $txnDetails["timestamp"];
            }
        }

        /*
        try {
        $tmp["date_created"] = $utilCRUD->getFormattedDate($row["date_created"]);
        } catch (\Throwable $th) {
        $tmp["date_created"] = "";
        }
         */
        return $tmp;
    }
    return null;
}

function getUserBasicDetails($user_id)
{
    require_once "dbmodels/user.crud.php";
    $userCRUD = new UserCRUD(getConnection());
    require_once "dbmodels/utils.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    $row = $userCRUD->getID($user_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["firstName"] = $row["first_name"];
        $tmp["lastName"] = $row["last_name"];
        $tmp["user_name"] = $row["user_name"];
        $tmp["email"] = $row["email"];
        //$tmp["phone"] = $row["phone"];
        $tmp["country"] = $row["country"];
        $tmp["type"] = $row["type"];
        $tmp["userImage"] = $row["user_image"];
        if (!empty($tmp["userImage"])) {
            $tmp["userImage"] = "users/" . $tmp["userImage"];
        }
        $tmp["status"] = $row["status"];

        $tmp["role_id"] = $row["role_id"];
        $tmp["role"] = "User";
        if ($tmp["role_id"] == 1) {
            $tmp["role"] = "Admin";
        } else {
            $tmp["role"] = "User";
        }
        //$tmp["role"] = $userCRUD->getRoleName($row["role_id"]);
        $tmp["api_key"] = $row["api_key"];
        $tmp["token"] = $utilCRUD->generateTXNID(16);
        $tmp["date_created"] = $row["date_created"];
        try {
            //$tmp["date_created"] = $utilCRUD->getFormattedDate($row["date_created"]);
        } catch (\Throwable $th) {
            $tmp["date_created"] = "";
        }
        return $tmp;
    }
    return null;
}

function getUserFullDetails($user_id)
{
    require_once "dbmodels/user.crud.php";
    $userCRUD = new UserCRUD(getConnection());
    require_once "dbmodels/user_membership.crud.php";
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once "dbmodels/utils.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    $row = $userCRUD->getID($user_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["firstName"] = $row["first_name"];
        $tmp["lastName"] = $row["last_name"];
        $tmp["user_name"] = $row["user_name"];
        $tmp["email"] = $row["email"];
        $tmp["type"] = $row["type"];
        $dob = $row["dob"];
        $tmp["dob"] = date("Y-m-d", strtotime($dob));
        $tmp["phone"] = $row["phone"];
        $tmp["country"] = $row["country"];
        $tmp["userImage"] = $row["user_image"];

        if (!empty($tmp["userImage"])) {
            $tmp["userImage"] = BASE_IMAGE_URL . "users/" . $tmp["userImage"];
        }
        $tmp["status"] = $row["status"];

        $tmp["role_id"] = $row["role_id"];
        $tmp["role"] = "User";
        if ($tmp["role_id"] === 1) {
            $tmp["role"] = "User";
        } else {
            if ($tmp["role_id"] === 3) {
                $tmp["role"] = "Affiliate";
            } else {
                $tmp["role"] = "User";
            }
        }
        $tmp["date_created"] = $row["date_created"];
        // try {
        //     $tmp["date_created"] =  $utilCRUD->getTimeDifference($row["date_created"]);
        // } catch (\Throwable $th) {
        //     $tmp["date_created"] = "";
        // }
        //$tmp["hasBeenMember"] = $isMember = $membershipCRUD->isIDExists($row["id"]);
        $tmp["last_seen"] = $row["last_active"];
        return $tmp;
    }
    return null;
}

function getDocDetails($docID, $user_id, $thumbnail = false)
{
    require_once "dbmodels/document.crud.php";
    $docCRUD = new DocumentCRUD(getConnection());
    require_once "dbmodels/utils.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    require_once "dbmodels/category.crud.php";
    $categoryCRUD = new CategoryCRUD(getConnection());
    require_once "dbmodels/document_reviews.crud.php";
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once "dbmodels/document_likes.crud.php";
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    require_once "dbmodels/document_save.crud.php";
    $docSaveCRUD = new DocumentSaveCRUD(getConnection());
    $row = $docCRUD->getID($docID);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["title"] = $row["title"];
        $tmp["numViews"] = $docCRUD->getNumViews($docID);
        $tmp["cover"] = $row["cover"];
        $tmp["link"] = $row["link"];
        if (!empty($tmp["link"])) {
            $tmp["link"] = BASE_DOC_URL . "/" . $tmp["link"];
        }
        $tmp["qcode"] = $row["qcode"];
        $tmp["is_published"] = $row["is_published"];
        $tmp["category_id"] = $row["category_id"];
        $tmp["category"] = $categoryCRUD->getNameByID($row["category_id"]);
        $tmp["document_type"] = $row["document_type"];
        $tmp["doc_type_name"] = "";
        if ($row["document_type"] && is_numeric($row["document_type"])) {
            $tmp["doc_type_name"] = $docCRUD->getDocTypeName($row["document_type"]);
        }

        if (!$thumbnail) {
            $tmp["author_name"] = $row["author_name"];
            $tmp["description"] = $row["description"];
            $tmp["file_type"] = $row["file_type"];
            $tmp["note"] = $row["note"];
            $tmp["tag"] = $row["tag"];
            $tmp["date_updated"] = "";
            if (!empty($row["date_updated"])) {
                try {
                    $tmp["date_updated"] = $utilCRUD->getFormalDate($row["date_updated"]);
                } catch (Exception $e) {
                }
            }
            $tmp["is_downloadable"] = $row["is_downloadable"];
            $tmp["is_liked"] = false;
            $tmp["avg_rating"] = $reviewCRUD->getAvgReviewsFor($row["id"]);
            $tmp["num_reviews"] = $reviewCRUD->getNumReviewsFor($row["id"]);
            $tmp["num_likes"] = $likeCRUD->getNumLikes($row["id"]);
            if ($user_id > 0) {
                $tmp["is_liked"] = $likeCRUD->isLikedBy($user_id, $row["id"]);
            }
            $tmp["num_saves"] = $docSaveCRUD->getNumSaves($row["id"]);
            if (!empty($row["date_created"])) {
                try {
                    $tmp["date_created"] = $utilCRUD->getFormalDate($row["date_created"]);
                } catch (Exception $e) {
                }
            }
            $tmp["num_pages"] = $row["num_pages"];
            $tmp["is_saved"] = false;
            $tmp["is_reviewed"] = false;
            if ($user_id > 0) {
                $tmp["is_saved"] = $docSaveCRUD->isSavedBy($user_id, $row["id"]);
                $tmp["is_reviewed"] = $reviewCRUD->isReviewedBy($user_id, $row["id"]);
            }

            $keywords = $docCRUD->getAllTags($row["id"]);
            $tmp["keywords"] = array();
            if (count($keywords) > 0) {
                foreach ($keywords as $tag_val) {
                    if ($tag_val['keyword']) {
                        array_push($tmp["keywords"], $tag_val['keyword']);
                    }
                }
            }
            //Check for audio file
            $custom_audio_tracks = array();
            if ($docCRUD->doesHaveAudio($row["id"])) {
                //Get formatted audio tracks
                $audio_tracks = $docCRUD->getAudioTracksByID($row["id"]);
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
            }
            $tmp["audio_tracks"] = $custom_audio_tracks;
        }

        return $tmp;
    }
    return null;
}

function getTrialDetails($user_id)
{
    require_once "dbmodels/user.crud.php";
    require_once "dbmodels/membership_plan.crud.php";
    require_once "dbmodels/utils.crud.php";
    require_once "dbmodels/free_trials.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $trialsCRUD = new FreeTrialsCRUD(getConnection());
    $row = $trialsCRUD->getID($user_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["user_id"] = $row["user_id"];
        $tmp["plan_id"] = $row["plan_id"];
        $tmp["plan_name"] = $planCRUD->getNameByID($row["plan_id"]);
        //$tmp["user_name"] = $userCRUD->getNameByID($row["user_id"]);
        $tmp["timestamp"] = $row["timestamp"];
        $tmp["date_created"] = $utilCRUD->getFormalDate($row["date_created"]);
        $tmp["date_expiring"] = $utilCRUD->getFormalDate($row["date_expiring"]);
        $tmp["active"] = false;
        try {
            $today = date('Y-m-d');
            $date_created = date('Y-m-d', strtotime($row["date_created"]));
            $date_expiring = date('Y-m-d', strtotime($row["date_expiring"]));

            if (($today >= $date_created) && ($today <= $date_expiring)) {
                $tmp["active"] = true;
            }
        } catch (\Throwable $th) {
        }
        return $tmp;
    }
    return null;
}

function getFAQSubCategoryDetails($user_id)
{
    require_once "dbmodels/faq.crud.php";
    $faqCRUD = new FAQCRUD(getConnection());
    require_once "dbmodels/faq_category.crud.php";
    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
    $row = $faqCategoryCRUD->getSubCategoryByID($user_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["title"] = $row["title"];
        $tmp["category_id"] = $row["category_id"];
        $tmp["category"] = $faqCategoryCRUD->getNameByID($row["category_id"]);
        $tmp["qcode"] = $row["qcode"];
        //$tmp["numFaqs"] = 11;
        $tmp["numFaqs"] = $faqCRUD->getNumFAQsInSubCategory($row["category_id"]);
        //$tmp["date_created"] = $utilCRUD->getFormattedDate($row["date_created"]);
        return $tmp;
    }
    return null;
}

function getFAQDetails($user_id)
{
    require_once "dbmodels/faq.crud.php";
    $faqCRUD = new FAQCRUD(getConnection());
    require_once "dbmodels/faq_category.crud.php";
    $faqCategoryCRUD = new FAQCategoryCRUD(getConnection());
    require_once "dbmodels/utils.crud.php";
    $utilCRUD = new UtilCRUD(getConnection());
    $row = $faqCRUD->getID($user_id);
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["title"] = $row["title"];
        $tmp["description"] = $row["description"];
        $tmp["url"] = $row["url"];
        $tmp["category_id"] = $row["category_id"];
        $tmp["category"] = $faqCategoryCRUD->getNameByID($row["category_id"]);
        $tmp["qcode"] = $row["qcode"];
        $tmp["subcategory_name"] = "";
        if ($row["subcategory_id"] > 0) {
            $tmp["subcategory_name"] = $faqCategoryCRUD->getSubCategoryNameByID($row["subcategory_id"]);
        }
        $tmp["date_created"] = "";
        $tmp["date_updated"] = "";

        try
        {
            // if(!empty($row["date_created"])){
            //       $tmp["date_created"] = $utilCRUD->getFormattedDate($row["date_created"]);
            //     }
            // if(!empty($row["date_updated"])){
            //   $tmp["date_updated"] = $utilCRUD->getFormattedDate($row["date_updated"]);
            // }
        } catch (Exception $e) {
        }
        return $tmp;
    }
    return null;
}

function sendNotify($sender_id, $receiver_id, $title, $message, $data_id, $data_title, $type = "Default")
{
    require_once "dbmodels/notification.crud.php";
    $notiCRUD = new NotificationCRUD(getConnection());
    $response = array();
    $response["test"] = "";
    $status = "Pending";
    $date_created = date('Y-m-d H:i:s');
    try {
        $noti_res = $notiCRUD->create($sender_id, $receiver_id, $title, $message, $data_id, $data_title, $status, $date_created);
        if ($noti_res["code"] == INSERT_SUCCESS) {
            $response["error"] = false;
            $response["message"] = "Notification Sent.";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to send notification.";
        }
    } catch (Exception $e) {
        $response["error"] = true;
        $response["test"] = "NotiException: " . $e->getMessage();
        $response["message"] = "Oops! Error send notification.";
        return $response;
    }
    return $response;
}

function notifyByEmail($sendToEmail, $emailToName, $params)
{
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    $emailTitle = $params["title"];
    $noti_message = $params["message"];
    /******* Send Email *********/
    $sendEmailNoti = true;
    if ($sendEmailNoti) {
        $email_body = '<html><body>';
        $email_body .= '<h4>Hello ' . $emailToName . ' </h4>';
        $email_body .= '<h4>' . $noti_message . ' </h4>';

        $url = "";
        $preeUrl = "#/";
        if (!empty($params["data_id"])) {
            $url = $preeUrl . "/" . $params["data_id"];
            $email_body .= '<a style="box-shadow:inset 0px 1px 0px 0px #54a3f7;
	background:linear-gradient(to bottom, #007dc1 5%, #0061a7 100%);
	background-color:#007dc1;
	border-radius:3px;
	border:1px solid #124d77;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:13px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #154682;" href="' . $url . '"> Open BaziChic</a>';
        }

        $email_body .= getEmailFooter();
        $email_body .= '</body></html>';
        try
        {
            sendEmail($sendToEmail, $emailTitle, $email_body);
            $output["message"] = "E-mail notification sent successfully.";
        } catch (Exception $e) {
            $output["error"] = true;
            $output["message"] .= "Error sending e-mail: " . $e->getMessage();
        }
    }
    /****** Send Email ******/
    return $output;
}

/*******
 * E-mail Templates
 * ********/

/************* EMAIL SENDER **********/
function notifyUserWithVerificationCode($sendToEmail, $emailToName, $otp, $numOTPSent = 0)
{
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    $emailTitle = "Verify BaziChic Account";
    $noti_message = "Thanks for registering an account with BaziChic. Use the OTP " . $otp . " to verify your registered e-mail address.";

    if ($numOTPSent == 1) {
        $emailTitle = "BaziChic Account Verification";
    } else {
        if ($numOTPSent > 2) {
            $emailTitle = "BaziChic Account Verification";
            $noti_message = "Looks like you are facing an issue while accessing your account. Use this OTP " . $otp . " and verify your registered e-mail address.";
        }
    }
    $sendEmailNoti = true;
    if ($sendEmailNoti) {
        $email_body = '<html><body>';
        $email_body .= '<h4>Hello ' . $emailToName . ' </h4>';
        $email_body .= '<h4>' . $noti_message . ' </h4><br>';

        $email_body .= '<a style="box-shadow:inset 0px 1px 0px 0px #54a3f7;
	background:linear-gradient(to bottom, #007dc1 5%, #0061a7 100%);
	background-color:#007dc1;
	border-radius:3px;
	border:1px solid #124d77;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:13px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #154682;" href="' . BASE_URL . '#/authentication/verify"> Verify Account</a><br>';

        if ($numOTPSent > 1) {
            $email_body .= '<h4 style="margin-bottom:3px;margin-top:1px;">Feel free to contact BaziChic Support if you are facing any issue accessing your account. </h4>';
        }
        $email_body .= getEmailFooter();
        $email_body .= '</body></html>';
        try
        {
            sendEmail($sendToEmail, $emailTitle, $email_body);
            $output["message"] = "E-mail notification sent successfully.";
        } catch (Exception $e) {
            $output["error"] = true;
            $output["message"] .= "Error sending e-mail: " . $e->getMessage();
        }
    }
    return $output;
}

function notifyUserWithWelcomeMessage($sendToEmail, $emailToName)
{
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    $emailTitle = "Welcome to BaziChic";
    $noti_message = '<h5>Your BaziChic account is now verified. Check out our subscription plans to get full access to our E-Books and Magazines.</h5>';
    $noti_message .= '<h5 style="color: #222;">We are now offering 10 Days Free Trial for all plans.</h5>';
    
    $sendEmailNoti = true;
    if ($sendEmailNoti) {
        $email_body = '<html><body>';
        $email_body .= '<h4>Welcome ' . $emailToName . ' </h4>';
        $email_body .= '<h4>' . $noti_message . ' </h4><br>';

        $email_body .= '<a style="box-shadow:inset 0px 1px 0px 0px #54a3f7;
	background:linear-gradient(to bottom, #007dc1 5%, #0061a7 100%);
	background-color:#007dc1;
	border-radius:3px;
	border:1px solid #124d77;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:13px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #154682;" href="' . BASE_URL . '#/authentication/login"> Get Started</a><br>';

        $email_body .= getEmailFooter();
        $email_body .= '</body></html>';
        try
        {
            sendEmail($sendToEmail, $emailTitle, $email_body);
            $output["message"] = "E-mail notification sent successfully.";
        } catch (Exception $e) {
            $output["error"] = true;
            $output["message"] .= "Error sending e-mail: " . $e->getMessage();
        }
    }
    return $output;
}

function notifyUserOfNewSubscription($sendToEmail, $emailToName, $planName, $startDate, $expiryDate, $txnID)
{
    $output = array();
    $output["error"] = false;
    $output["message"] = "";
    $emailTitle = "BaziChic " . $planName . " Subscription";
    $noti_message = "Congrats! Your " . $planName . " subscription has been activated successfully. Details related with the subscription are as follows:";

    $sendEmailNoti = true;
    if ($sendEmailNoti) {
        $email_body = '<html><body>';
        $email_body .= '<h4>Dear ' . $emailToName . ' </h4>';
        $email_body .= '<h4>' . $noti_message . ' </h4><br>';
        $email_body .= '<h4>Start Date: ' . $startDate . ' </h4>';
        $email_body .= '<h4>Expiry Date: ' . $expiryDate . ' </h4>';
        $email_body .= '<h4>TXN ID: ' . $txnID . ' </h4>';
        $email_body .= '<br>';
        $email_body .= '<h4 style="margin-bottom:3px;margin-top:1px;color:#565656;">Get access to abundance of Chinese Metaphysics information, knowledge and wisdom. </h4>';
        $email_body .= '<br>';
        $email_body .= '<a style="box-shadow:inset 0px 1px 0px 0px #54a3f7;
	background:linear-gradient(to bottom, #007dc1 5%, #0061a7 100%);
	background-color:#007dc1;
	border-radius:3px;
	border:1px solid #124d77;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:13px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #154682;" href="' . BASE_URL . '#/invoice/' . $txnID . '"> View Invoice</a>';

        $email_body .= getEmailFooter();
        $email_body .= '</body></html>';
        try
        {
            sendEmail($sendToEmail, $emailTitle, $email_body);
            $output["message"] = "E-mail notification sent successfully.";
        } catch (Exception $e) {
            $output["error"] = true;
            $output["message"] .= "Error sending e-mail: " . $e->getMessage();
        }
    }
    return $output;
}

function getEmailFooter()
{
    $body = '<br style="margin-top:10px;">';
    $body .= '<img src="https://www.bazichic.com/images/logo.png" style="width: auto; height:80px; display: block;">';
    $body .= '<h4>For more details, Visit our official website :<br> <a style="color:blue;" href="' . BASE_URL . '">www.bazichic.com</a><h4>';
    $body .= 'To Sign into your account <a href="' . BASE_URL . '#/authentication/login">Click here</a>.';
    //Add facebook
    $facebook = '<div style="display:flex;align-items: center;margin-bottom:0px;color:blue;">&nbsp;&nbsp;<a href="https://www.facebook.com/BaziChicConsultancy/"><img src="https://cdn-icons-png.flaticon.com/512/124/124010.png" alt="Follow BaziChic on Facebook" style="width: auto; height:24px;"></a></div>';
    $pinterst = '<div style="display:flex;align-items: center;margin-top:0px;">&nbsp;&nbsp;<a href="https://in.pinterest.com/BaziChic/"><img alt="Follow BaziChic on pinterest" src="https://cdn-icons-png.flaticon.com/512/3536/3536559.png" style="width: auto; height:24px;"><h5 style="color:grey;"></a></div>';

    //Add facebook
    $body .= '<br><div style="margin-bottom:1px;margin-top:1px;"><img style="width: auto; height:24px;vertical-align:middle" src="https://cdn-icons-png.flaticon.com/512/124/124010.png" style="width: auto; height:24px;">
    <span style=""><a href="https://www.facebook.com/BaziChicConsultancy/">Follow BaziChic on Facebook </a></span>
  </div>';
    //Add Pinterst
    $body .= '<br><div style="margin-top:1px;"><img style="width: auto; height:24px;vertical-align:middle" src="https://cdn-icons-png.flaticon.com/512/3536/3536559.png" style="width: auto; height:24px;">
  <span style=""><a href="https://in.pinterest.com/BaziChic/">Follow BaziChic on pinterest </a></span>
</div>';
    $body .= '<h5 style="color:grey;">This is an autogenerated notification from BaziChic. Please do not reply to this e-mail.</h5>';
    return $body;
}

/*****************************/

function isDateFormatValidOld($date)
{
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
        return true;
    } else {
        return false;
    }
}

function isDateTimeFormatValid($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function getUsageDetail($row)
{
    //require_once("dbmodels/user.crud.php");
    //$userCRUD = new UserCRUD(getConnection());
    if ($row != null) {
        $tmp = array();
        $tmp["id"] = $row["id"];
        $tmp["timestamp"] = $row["timestamp"];
        $tmp["signature"] = $row["signature"];
        //$tmp["user"] = $userCRUD->getUserNameByAPIKey($row["api_key"]);
        $tmp["token"] = $row["api_key"];
        return $tmp;
    }
    return null;
}

function isDateFormatValid($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
