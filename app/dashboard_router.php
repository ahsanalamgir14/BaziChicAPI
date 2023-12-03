<?php
/************* USER DASHBOARD SUMMARY API ***********/
$app->post('/apis/dashboard', function ($request, $respo, $args) use ($app) {
    $output = array();
    $output["error"] = true;
    $output["accessGranted"] = false;
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once ("dbmodels/category.crud.php");
    $categoryCRUD = new CategoryCRUD(getConnection());
    require_once ("dbmodels/document_reviews.crud.php");
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once ("dbmodels/document_likes.crud.php");
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    require_once ("dbmodels/document_save.crud.php");
    $docSaveCRUD = new DocumentSaveCRUD(getConnection());
    require_once ("dbmodels/activity.crud.php");
    $activityCRUD = new ActivityCRUD(getConnection());
    require_once ("dbmodels/notification.crud.php");
    $notificationCRUD = new NotificationCRUD(getConnection());
    require_once ("dbmodels/reward_points.crud.php");
    $rewardPointCRUD = new RewardPointCRUD(getConnection());
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());

    require_once ("dbmodels/referral.crud.php");
    $referCRUD = new ReferralCRUD(getConnection());

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentID = 0;
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $agentID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output["error"] = true;
        $output["message"] = "BaziChic server could not authenticate this request.";
        $output["agentValidationError"] = $agentValidator;
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    $thisUser = getUserBasicDetails($agentID);
      /********* Excahnge Session Data ***********/
      $output['userData'] = $thisUser;
      /********* Excahnge Session Data ********/
    /*********** #### ALLOW If Active Subscription ***********/
    $warning = "";
    $membership_info = array();
    $numMyPlans = $membershipCRUD->getNumMyActivePlan($agentID);
    if ($numMyPlans > 0) {
        $activePaidPlan = $membershipCRUD->getMyActivePlan($agentID);
        if ($activePaidPlan !== null) {
            $output["accessGranted"] = true;
            $activePlanName = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activePlanQcode = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activeExpiry = $utilCRUD->getFormalDate($activePaidPlan["date_expiring"]);

            $membership_info["activePlanName"] = $activePlanName;
            $membership_info["activePlanQcode"] = $activePlanQcode;
            $membership_info["activeExpiry"] = $activeExpiry;
            $membership_info["message"] = $activePaidPlan;
            try {
                $date_created = date('Y-m-d H:i:s');
                $numDays = $utilCRUD->dateDiffInDays($date_created, $activePaidPlan["date_expiring"]);
                if ($numDays < 20) {
                    $warning = "The " . $activePlanName . " Subscription is active up to " . $activeExpiry . ".";
                    $warning .= ' Renew within ' . $numDays . ' days to enjoy uninterrupted access.';
                }
            } catch (Exception $e) {
            }}
    }

     /**** First Check Trial Periods  *****/
     $trialMessage = "";
     require_once ("dbmodels/free_trials.crud.php");
     $freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
     $numActiveTrials = $freeTrialsCRUD->getNumMyActivePlan($agentID);
     if ($numActiveTrials > 0) {
         $activeTrial = $freeTrialsCRUD->getMyActivePlan($agentID);
         if ($activeTrial !== null) {
             $activeTrialPlanName = $planCRUD->getNameByID($activeTrial["plan_id"]);
             $planQcode = $planCRUD->getQCodeByID($activeTrial["plan_id"]);
             $activeTrialExpiry = $utilCRUD->getFormalDate($activeTrial["date_expiring"]);
             //DELETE
             $anchorUrl = "https://www.bazichic.com/get-membership/" . $planQcode;
             $trialMessage = 'You are on a 10 Days Free Trial for ' . $activeTrialPlanName . ' Subscription. Your trial ends on ' . $activeTrialExpiry . '. <a href="' . $anchorUrl . '"> Buy Subscription.</a>';
         }
     }
     /**** End of free trials ***********************/

    if ($numMyPlans <= 0 && $numActiveTrials <= 0) {
        //Prevent Dashboard Access
        // $output["error"] = true;
        // $output["message"] = "You should have an active subscription plan to access your BaziChic account.";
        // $output["accessGranted"] = false;
        // echoRespnse(200, $output);
        // exit;
        $output["warning"] = "You should have an active subscription plan to access your BaziChic account.";
    
    }
    $output["error"] = false;

    /************ ## Step 2: Get Activities, Saved Reads, Rewards ## *************/
    $reward_points = $rewardPointCRUD->getCurrentRewardPointFor($agentID);
    $recent_activities = array();
    $dash_notis = array();
    $saved_docs = array();
    //$membership_info= checkMembership($agentID);
    $data = $docCRUD->getAllDocuments();
    $num_reviews = $reviewCRUD->getTotalReviewsDone($agentID);
    $num_likes = $likeCRUD->getTotalLikesDone($agentID);
    $num_saves = $docSaveCRUD->getNumAllMySaves($agentID);

    /********** SAVED DOCS  ***********/
    $data = $docSaveCRUD->getAllMySaves($agentID);
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["read_status"] = "Just Started";
            $tmp["id"] = $row["id"];
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
            $tmp["page"] = $row["page"];
            $tmp["progress"] = $row["progress"];

            if ($row["progress"] >= 0 && $row["progress"] < 80) {
                $tmp["read_status"] = $row["progress"] . "% Complete";
            } else {
                $tmp["read_status"] = (100 - $row["progress"]) . "% Left";
            }
            $tmp["date_created"] = $row["date_created"];
            // if(!empty($tmp["date_created"])){
            //   try {
            //     $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            //   } catch (\Throwable $th) {
            //   }
            // }
           
            //$tmp["date_created"] = $row["date_created"];
            //$tmp["link"] = $row["link"];
            array_push($saved_docs, $tmp);
        }
    }

    $my_connections = array();
    $my_referral_codes = array();
    if ($thisUser["type"] == "Affiliate" || true) {
        $dataMyCon = $referCRUD->getMyConnections($thisUser["id"]);
        if (count($dataMyCon) > 0) {
            foreach ($dataMyCon as $conrow) {
                $con_tmp = array();
                $con_tmp["id"] = $conrow["id"];
                $con_tmp["first_name"] = $conrow["first_name"];
                $con_tmp["last_name"] = $conrow["last_name"];
                $con_tmp["referral_code"] = $conrow["referral_code"];
                $con_tmp["date_created"] = $conrow["date_created"];
                try {
                    $con_tmp["date_created"] = $utilCRUD->getFormalDate($conrow["date_created"]);
                } catch (Exception $e) {
                }
                array_push($my_connections, $con_tmp);
            }
        }

        $referralCodesArr = $referCRUD->getAllMyReferrals($agentID);
        if (count($referralCodesArr) > 0) {
            foreach ($referralCodesArr as $coderow) {
                $codetmp = array();
                $codetmp["id"] = $coderow["id"];
                $codetmp["code"] = $coderow["code"];
                $codetmp["status"] = $coderow["status"];
                $codetmp["date_created"] = $coderow["date_created"];
                $codetmp["date_updated"] = $coderow["date_updated"];
                try {
                    if (!empty($coderow["date_created"])) {
                        $codetmp["date_created"] = $utilCRUD->getFormalDate($coderow["date_created"]);
                    }
                } catch (Exception $e) {
                }
                $codetmp["total_redeems"] = $referCRUD->getNumRedeems($coderow["code"]);
                array_push($my_referral_codes, $codetmp);
            }
        }

    }

    $recent_activities_arr = $activityCRUD->getBaziChicActivitiesFor($agentID, 0, 10);
        if (count($recent_activities_arr) > 0) {
            foreach ($recent_activities_arr as $row) {
                $tmp = array();
                $tmp["id"] = $row["id"];
                $tmp["title"] = $row["title"];
                $tmp["message"] = $row["message"];
                $tmp["status"] = $row["status"];
                //$tmp["user_image"] = $userCRUD->getUserImageByID($row["who_id"]);
                $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
                $tmp["action_link"] = $notificationCRUD->getActionLink($row["data_id"], $row["data_title"]);
                array_push($recent_activities, $tmp);
            }
        }
    /******** GET LAST 10 NOTIFICATIONS *******
    $dash_notis_arr = $notificationCRUD->getNotificationsFor($agentID, 1);
    if (count($dash_notis_arr) > 0) {
        foreach ($dash_notis_arr as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["message"] = $row["message"];
            $tmp["status"] = $row["status"];
            $tmp["sender_image"] = $userCRUD->getUserImageByID($row["sender_id"]);
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            $tmp["action_link"] = $notificationCRUD->getActionLink($row["data_id"], $row["data_title"]);
            array_push($dash_notis, $tmp);
        }
    }
    ******* END OF NOTIS ******/

    /************************************/
    $output["warning"] = $warning;
    $output["saved_docs"] = $saved_docs;
    $output["recent_activities"] = $recent_activities;

    //$output["dash_notis"] = $dash_notis;
    $output["trialMessage"] = $trialMessage;
    $output["membership_info"] = $membership_info;
    //Stats info
    $output["num_reviews"] = $num_reviews;
    $output["num_likes"] = $num_likes;
    $output["num_saves"] = $num_saves;
    $output["reward_points"] = $reward_points;
    $output["my_connections"] = $my_connections;
    $output["my_referral_codes"] = $my_referral_codes;
    $output["thisUser"] = $thisUser;

    /********* ECHO RESULTS *********/
    echoRespnse(200, $output);
})->add($authenticate);

/************* REFERRAL SUMMARY API ***********/
$app->post('/apis/user_referrals', function ($request, $respo, $args) use ($app) {
    $output = array();
    $output["error"] = true;
    $output["accessGranted"] = false;
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once ("dbmodels/category.crud.php");
    $categoryCRUD = new CategoryCRUD(getConnection());
    require_once ("dbmodels/document_reviews.crud.php");
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once ("dbmodels/document_likes.crud.php");
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    require_once ("dbmodels/document_save.crud.php");
    $docSaveCRUD = new DocumentSaveCRUD(getConnection());
    require_once ("dbmodels/activity.crud.php");
    $activityCRUD = new ActivityCRUD(getConnection());
    require_once ("dbmodels/notification.crud.php");
    $notificationCRUD = new NotificationCRUD(getConnection());
    require_once ("dbmodels/reward_points.crud.php");
    $rewardPointCRUD = new RewardPointCRUD(getConnection());
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());

    require_once ("dbmodels/referral.crud.php");
    $referCRUD = new ReferralCRUD(getConnection());

    /******** VERIFY THE REQUESTING AGENT **********/
    $agentID = 0;
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $agentID = $agentValidator["user_info"]["id"];
        $agentRole = $agentValidator["user_info"]["role_id"];
    } else {
        $output["error"] = true;
        $output["message"] = "BaziChic server could not authenticate this request.";
        $output["agentValidationError"] = $agentValidator;
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/
    $thisUser = getUserBasicDetails($agentID);
    /*********** #### ALLOW If Active Subscription ***********/
    $warning = "";
    $membership_info = array();
    $numMyPlans = $membershipCRUD->getNumMyActivePlan($agentID);
    if ($numMyPlans > 0) {
        $activePaidPlan = $membershipCRUD->getMyActivePlan($agentID);
        if ($activePaidPlan !== null) {
            $output["accessGranted"] = true;
            $activePlanName = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activePlanQcode = $planCRUD->getQCodeByID($activePaidPlan["plan_id"]);
            $activeExpiry = $utilCRUD->getFormalDate($activePaidPlan["date_expiring"]);

            $membership_info["activePlanName"] = $activePlanName;
            $membership_info["activePlanQcode"] = $activePlanQcode;
            $membership_info["activeExpiry"] = $activeExpiry;
            $membership_info["message"] = $activePaidPlan;
            try {
                $date_created = date('Y-m-d H:i:s');
                $numDays = $utilCRUD->dateDiffInDays($date_created, $activePaidPlan["date_expiring"]);
                if ($numDays < 20) {
                    $warning = "The " . $activePlanName . " Subscription is active up to " . $activeExpiry . ".";
                    $warning .= ' Renew within ' . $numDays . ' days to enjoy uninterrupted access.';
                }
            } catch (Exception $e) {
            }}
    }

     /**** First Check Trial Periods  *****/
     $trialMessage = "";
     require_once ("dbmodels/free_trials.crud.php");
     $freeTrialsCRUD = new FreeTrialsCRUD(getConnection());
     $numActiveTrials = $freeTrialsCRUD->getNumMyActivePlan($agentID);
     if ($numActiveTrials > 0) {
         $activeTrial = $freeTrialsCRUD->getMyActivePlan($agentID);
         if ($activeTrial !== null) {
             $activeTrialPlanName = $planCRUD->getNameByID($activeTrial["plan_id"]);
             $planQcode = $planCRUD->getQCodeByID($activeTrial["plan_id"]);
             $activeTrialExpiry = $utilCRUD->getFormalDate($activeTrial["date_expiring"]);
             //DELETE
             $anchorUrl = "https://www.bazichic.com/get-membership/" . $planQcode;
             $trialMessage = 'You are on a 10 Days Free Trial for ' . $activeTrialPlanName . ' Subscription. Your trial ends on ' . $activeTrialExpiry . '. <a href="' . $anchorUrl . '"> Buy Subscription.</a>';
         }
     }
     /**** End of free trials ***********************/

    if ($numMyPlans <= 0 && $numActiveTrials <= 0) {
        //Prevent Dashboard Access
        $output["error"] = true;
        $output["message"] = "You should have an active subscription plan to access your BaziChic account.";
        $output["accessGranted"] = false;
        echoRespnse(200, $output);
        exit;
    }
    $output["error"] = false;

    /************ ## Step 2: Get Activities, Saved Reads, Rewards ## *************/
    $reward_points = $rewardPointCRUD->getCurrentRewardPointFor($agentID);
    $recent_activities = array();
    $dash_notis = array();
    $saved_docs = array();
    //$membership_info= checkMembership($agentID);
    $data = $docCRUD->getAllDocuments();
    $num_reviews = $reviewCRUD->getTotalReviewsDone($agentID);
    $num_likes = $likeCRUD->getTotalLikesDone($agentID);
    $num_saves = $docSaveCRUD->getNumAllMySaves($agentID);
    /************************************/
    $output["warning"] = $warning;
    $output["saved_docs"] = $saved_docs;
    $output["recent_activities"] = $recent_activities;

    //$output["dash_notis"] = $dash_notis;
    $output["trialMessage"] = $trialMessage;
    $output["membership_info"] = $membership_info;
    //Stats info
    $output["num_reviews"] = $num_reviews;
    $output["num_likes"] = $num_likes;
    $output["num_saves"] = $num_saves;
    $output["reward_points"] = $reward_points;
    $output["my_connections"] = $my_connections;
    $output["my_referral_codes"] = $my_referral_codes;
    $output["thisUser"] = $thisUser;

    /********* ECHO RESULTS *********/
    echoRespnse(200, $output);
})->add($authenticate);
