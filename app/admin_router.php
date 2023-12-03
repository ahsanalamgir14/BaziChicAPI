<?php
/************* ADMIN SUMMARY API ***********/
$app->post('/apis/summary', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/document_reviews.crud.php");
    require_once ("dbmodels/utils.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/document_likes.crud.php");
    $likeCRUD = new DocumentLikeCRUD(getConnection());
    require_once ("dbmodels/document_save.crud.php");
    $docSaveCRUD = new DocumentSaveCRUD(getConnection());

    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());

    require_once ("dbmodels/document.crud.php");
    $documentCRUD = new DocumentCRUD(getConnection());

    require_once ("dbmodels/category.crud.php");
    $categoryCRUD = new CategoryCRUD(getConnection());

    require_once ("dbmodels/transaction_details.crud.php");
    $paymentCRUD = new PaymentCRUD(getConnection());

    require_once ("dbmodels/contacts.crud.php");
    $contactsCRUD = new ContactsCRUD(getConnection());

    require_once ("dbmodels/activity.crud.php");
    $activityCRUD = new ActivityCRUD(getConnection());

    require_once ("dbmodels/free_trials.crud.php");
    $trialsCRUD = new FreeTrialsCRUD(getConnection());
    require_once ("dbmodels/reward_points.crud.php");
    $pointsCRUD = new RewardPointCRUD(getConnection());
    $output = array();
    $output['error'] = true;
    $output['message'] = '';
    $output["filterType"] = "";
    $output["startDate"] = "";
    $output["endDate"] = "";
    $output["data"] = "";
    $output["countries_stat"] = array();
    $durationInDays = 1;
    /********** REQUEST AUTH CHECK  ***********/
    $callerInfo = getRequestingAgent($request);
    if (!$callerInfo["error"]) {
        $userInfo = $callerInfo["user_info"];
        if ($userInfo !== null && $userInfo["role_id"] == 1) {
        } else {
            $output['error'] = true;
            $output['code'] = $userInfo["role_id"];
            $output['message'] = 'Unauthorized request.';
            echoRespnse(200, $output);
            exit;
        }
    } else {
        $output['error'] = true;
        $output['message'] = 'Invalid request signature.';
        echoRespnse(200, $output);
        exit;
    }
    /********** REQUEST AUTH CHECK  ***********/

    /******* Apply Filter ******/
    $filterType = "All";
    $startDate = "";
    $endDate = "";

    if (null !== $request->getParam('startDate')) {
        $startDate = $request->getParam('startDate');
    }
    if (null !== $request->getParam('endDate')) {
        $endDate = $request->getParam('endDate');
    }
    if (null !== $request->getParam('filterType')) {
        $filterType = $request->getParam('filterType');
    }
    if (null !== $request->getParam('test')) {
        $test = $request->getParam('test');
        $output["testFLAG"] = $test;
    }

    /*** Get Resources categories Stats ***/
    $resourceCategoriesArr = array();
    $resourceCategories = $categoryCRUD->getAllCategories();
    if (count($resourceCategories) > 0) {
        foreach ($resourceCategories as $resourceCategory) {
            if ($resourceCategory['id']) {
                $tmp = array();
                $tmp['title'] = $resourceCategory['title'];
                $tmp['numResources'] = $documentCRUD->getNumDocumentsInCategory($resourceCategory['id']);
                $tmp['numViews'] = $documentCRUD->getNumViewsInCategory($resourceCategory['id']);
                array_push($resourceCategoriesArr, $tmp);
            }
        }
    }
    $output["resourceCategories"] = $resourceCategoriesArr;
    /*** Get Resources categories Stats ***/

    $recent_activities = array();
    $recent_activities_arr = $activityCRUD->getBaziChicActivitiesFor(0, 0, 7);
    if (count($recent_activities_arr) > 0) {
        foreach ($recent_activities_arr as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["title"] = $row["title"];
            $tmp["message"] = $row["message"];
            $tmp["status"] = $row["status"];
            //$tmp["user_image"] = $userCRUD->getUserImageByID($row["who_id"]);
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            $tmp["action_link"] = $activityCRUD->getActionLink($row["data_id"], $row["data_title"]);
            array_push($recent_activities, $tmp);
        }
    }
    $output["activities"] = $recent_activities;
    // Get Payment Stats
    $paymentStats = array();
    $today = date('Y-m-d');
    $monthCounter = -7;
    $period = 7;
    for ($i = 1; $i <= $period; $i++) {
        $loopStartDate = $today;
        $loopEndDate = strtotime("last day of -" . $monthCounter . " month", strtotime($today));
        $today = date('Y-m-d', $loopEndDate);
        $tmp = array();
        $tmp['startDate'] = $today;
        $tmp['endDate'] = $loopEndDate;
        $tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $loopStartDate, $loopEndDate);
        //$tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $startDate, $endDate);
        //array_push($paymentStats, $tmp);
    }

    // date('Y-m-d', strtotime("last day of -1 month"));

    $dynamicPaymentStats = array();
    $output["paymentStats"] = array();

    /***********  BILLING STATS
    $begin = new DateTime($startDate);
    $end =  new DateTime($endDate);

    $step = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $step, $end);

    foreach ($period as $dt)
    {
    $tmp = array();
    $tmp['date'] = $dt;
    array_push($paymentStats, $tmp);
    }
     ***********/

    //$output["paymentStats"] = $paymentStats;
    //End of Payment Stats

    $data_stats = array();
    $data_stats["numPointsClaimed"] = 0;
    $date_start = date('Y-m-d');
    if (!empty($filterType)) {
        $output["error"] = false;
        $dateTimeNow = new DateTime(date('Y-m-d'));
        $todayStartTxt = $dateTimeNow->format('Y-m-d');
        $todayStart = $todayStartTxt . " 00:00:00";
        $todayEnd = $todayStartTxt . " 23:59:59";
        $dateTimeNow = new DateTime($todayStart);
        $dateNow = $dateTimeNow->format('Y-m-d H:i:s');

        //GLOBAL DASHBOARD DATA
        $data_stats["_currentMembersWithStarter"] = $membershipCRUD->getNumTotalActivePlans(1);
        $data_stats["_currentMembersWithPremium"] = $membershipCRUD->getNumTotalActivePlans(2);
        $data_stats["_currentMembersWithStudent"] = $membershipCRUD->getNumTotalActivePlans(3);
        $data_stats["_currentMembers"] = $data_stats["_currentMembersWithStarter"] + $data_stats["_currentMembersWithPremium"] + $data_stats["_currentMembersWithStudent"];
        $data_stats["_totalFreeTrialsActive"] = $trialsCRUD->getNumAllActiveTrials();
           
        /************ START DYNAMIC DATA ***********/
        if (!empty($startDate) && !empty($endDate)) {
            $output["error"] = true;
            $output["message"] = "Custom filter is not supported yet.";
            echoRespnse(200, $output);
            exit;
        } else {
            if ($filterType === "All") {
                $output['message'] = 'Entire Data summary has been fetched. ' . $filterType;
                $startDate = "";
                $endDate = "";
                $data_stats["totalUserAccounts"] = $userCRUD->getNumUsers(2);
                $data_stats["totalActiveUserAccounts"] = $userCRUD->getNumUsers(2, "Active");
                $data_stats["totalPendingUserAccounts"] = $userCRUD->getNumUsers(2, "Pending");
                $data_stats["totalSubscribedMembers"] = $userCRUD->getNumSubscribedUsers();

                $data_stats["totalFreeTrials"] = $trialsCRUD->getNumAllFreeTrials();
                $data_stats["totalRewardPoints"] = $pointsCRUD->getTotalRewardPointsAwarded();
                $data_stats["numPeopleRewarded"] = $pointsCRUD->getNumPeopleRewarded();
                $data_stats["numPointsClaimed"] = 0;
                $data_stats["totalMembersWithStarter"] = $membershipCRUD->getNumPlanSales(1);
                $data_stats["totalMembersWithPremium"] = $membershipCRUD->getNumPlanSales(2);
                $data_stats["totalMembersWithStudent"] = $membershipCRUD->getNumPlanSales(3);
               
                $data_stats["numTransactions"] = $membershipCRUD->getNumTransactionsBetween("", "", "");
                $data_stats["amountPaidOnline"] = $membershipCRUD->getSumAllTransactions("Online");
                $data_stats["amountPaidOnline"] = $membershipCRUD->getSumAllTransactions("Online");
                $data_stats["amountPaidManual"] = $membershipCRUD->getSumAllTransactions("Manual");
                $data_stats["amountPaidTotal"] = $data_stats["amountPaidOnline"] + $data_stats["amountPaidManual"];

                $data_stats["totalFreeTrials"] = $trialsCRUD->getNumAllFreeTrials();

                $data_stats["numEbooks"] = $documentCRUD->getNumDocs(1, 1);
                $data_stats["numMagazines"] = $documentCRUD->getNumDocs(1, 3);
                $data_stats["numEbooksPending"] = $documentCRUD->getNumDocs(0, 1);
                $data_stats["numMagazinesPending"] = $documentCRUD->getNumDocs(0, 3);
                
                //$data_stats["numDocCategories"] = $categoryCRUD->getNumAllCategories(1);
                //$data_stats["numDocCategoriesDraft"] = $categoryCRUD->getNumAllCategories(0);
                $data_stats["numDocLikes"] = $likeCRUD->getNumAllLikes();
                $data_stats["numDocReviews"] = $reviewCRUD->getNumAllReviews();
                $data_stats["numDocSaves"] = $docSaveCRUD->getNumAllSaves();
                $data_stats["numTotalContacts"] = $contactsCRUD->getNumMessages();
            } else {
                $output["flag"] = "custom";
                $output['message'] = 'Periodic Data summary has been fetched.';
                switch ($filterType) {
                    case "Today":
                        $startDate = $todayStart;
                        $endDate = $todayEnd;
                        break;

                    case "Week":
                        $day = date('w');
                        $startDate = date('Y-m-d', strtotime('-' . $day . ' days'));
                        $endDate = date('Y-m-d', strtotime('+' . (6 - $day) . ' days'));
                        $startDate = $startDate . " 00:00:00";
                        $endDate = $endDate . " 23:59:59";
                        break;

                    case "Month":
                        $durationInDays = 7;
                        //Below is actually this month and not last 30 days
                        $startDate = date('Y-m-01'); // hard-coded '01' for first day
                        $endDate = date('Y-m-t');
                        $startDate = $startDate . " 00:00:00";
                        $endDate = $endDate . " 23:59:59";
                        break;

                    default:
                }

                /***********
                 * START DYNAMIC DATA ANALYSIS
                 * *******************************/
                $data_stats["totalUserAccounts"] = $userCRUD->getNumUsersBetween($startDate, $endDate, $status = "");
                $data_stats["totalActiveUserAccounts"] = $userCRUD->getNumUsersBetween($startDate, $endDate, $status = "Active");
                $data_stats["totalPendingUserAccounts"] = $userCRUD->getNumUsersBetween($startDate, $endDate, $status = "Pending");
               
                $data_stats["totalSubscribedMembers"] = $userCRUD->getNumSubscribedUsersBetween($startDate, $endDate);
                $data_stats["totalMembersWithStarter"] = $membershipCRUD->getNumPlanSalesBetween($startDate, $endDate, 1);
                $data_stats["totalMembersWithPremium"] = $membershipCRUD->getNumPlanSalesBetween($startDate, $endDate, 2);
                $data_stats["totalMembersWithStudent"] = $membershipCRUD->getNumPlanSalesBetween($startDate, $endDate, 3);
               
                $data_stats["numTransactions"] = $membershipCRUD->getNumTransactionsBetween("", $startDate, $endDate);
                $data_stats["amountPaidOnline"] = $membershipCRUD->getSumAllTransactionsBetween("Online", $startDate, $endDate);
                $data_stats["amountPaidManual"] = $membershipCRUD->getSumAllTransactionsBetween("Manual", $startDate, $endDate);
                $data_stats["amountPaidTotal"] = $data_stats["amountPaidOnline"] + $data_stats["amountPaidManual"];
                
                $data_stats["totalFreeTrials"] = $trialsCRUD->getNumAllFreeTrialsBetween($startDate, $endDate);
                //E-doc creation
                $data_stats["numEbooks"] = $documentCRUD->getNumDocsBetween(1, 1, $startDate, $endDate);
                $data_stats["numMagazines"] = $documentCRUD->getNumDocsBetween(1, 3, $startDate, $endDate);
                $data_stats["numEbooksPending"] = $documentCRUD->getNumDocsBetween(0, 1, $startDate, $endDate);
                $data_stats["numMagazinesPending"] = $documentCRUD->getNumDocsBetween(0, 3, $startDate, $endDate);

                $data_stats["numDocLikes"] = $likeCRUD->getNumAllLikes($startDate, $endDate);
                $data_stats["numDocReviews"] = $reviewCRUD->getNumAllReviews($startDate, $endDate);
                $data_stats["numDocSaves"] = $docSaveCRUD->getNumAllSaves($startDate, $endDate);
                $data_stats["numTotalContacts"] = $contactsCRUD->getNumMessages($startDate, $endDate);
                /***********
                 * END DYNAMIC DATA ANALYSIS
                 * *********/

/*
$begin = new DateTime($startDate);
$end = new DateTime($endDate);
$durationInDays = 1;
$interval = DateInterval::createFromDateString($durationInDays . ' day');
$period = new DatePeriod($begin, $interval, $end);

foreach ($period as $dt) {
$tmp['date'] =  $dt->format("l Y-m-d H:i:s\n");
//$tmp['start'] = $dt->start;
//$tmp['end'] = $dt->end;
$tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $loopStartDate, $loopEndDate);

//$tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $startDate, $endDate);
array_push($dynamicPaymentStats, $tmp);
}
 */

                /***********  BILLING STATS ****/
                $begin = new DateTime($startDate);
                $end = new DateTime($endDate);
                $step = DateInterval::createFromDateString($durationInDays . ' day');
                $period = new DatePeriod($begin, $step, $end);
                foreach ($period as $datetime => $dt) {
                    $tmp = array();
                    $loopStartDate = $dt->format('Y-m-d H:i:s');
                    $tmp['startDate'] = $loopStartDate;
                    $tmp['startDateFormat'] = $dt->format('M d');
                    //$dt->add(new DateInterval('P'.$durationInDays.'D'));
                    $dt->sub(new DateInterval('P' . $durationInDays . 'D'));
                    $loopEndDate = $dt->format('Y-m-d H:i:s');
                    $tmp['endDate'] = $loopEndDate;
                    $tmp['endDateFormat'] = $dt->format('M d');
                    $tmp['datetime'] = $tmp['endDateFormat'] . " - " . $tmp['startDateFormat'];
                    $tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $loopEndDate, $loopStartDate);
                    $tmp['numSale'] = $membershipCRUD->getNumTransactionsBetween("", $loopEndDate, $loopStartDate);

                    /*

                    $tmp['startDate'] = $startDate;
                    $tmp['loopStartDate'] = date("M d", strtotime($tmp['startDate']));

                    //Apply the interval
                    //$loopEndDate = $dt->add(new DateInterval('P'.$durationInDays.'D'));
                    $dt->add($durationInDays);
                    $loopEndDate = $dt->format('Y-m-d H:i:s');
                    //date_format($loopEndDate, 'Y-m-d H:i:s');
                    //$loopEndDate = $dt->add(new DateInterval('P".$durationInDays."D'));
                    //$loopEndDate = strtotime($durationInDays . " day", strtotime($loopStartDate));
                    $tmp['loopEndDate'] = date("M d", strtotime($loopEndDate));
                    //->format('Y-m-d H:i:s')
                    //$tmp['totalSale'] = $membershipCRUD->getSumAllTransactionsBetween("", $loopEndDate, $loopStartDate);
                    //$tmp['numSale'] = $membershipCRUD->getNumTransactionsBetween("", $loopEndDate, $loopStartDate);
                     */
                    array_push($dynamicPaymentStats, $tmp);
                }
                /***********/

                $output["paymentStats"] = $dynamicPaymentStats;

/***********
 * END DYNAMIC GRAPH DATA
 * *********/

            }

            /***** FILTERED GRAPHICAL DATA *****/
            /*** Get Resources categories Stats ***/
            $countries_statArr = array();
            $countries_stat = $userCRUD->getCountriesUsersSummary($startDate, $endDate);
            if (count($countries_stat) > 0) {
                foreach ($countries_stat as $statItem) {
                    if ($resourceCategory['id']) {
                        $tmp = array();
                        $tmp['country'] = $statItem['country'];
                        $tmp['numUsers'] = $statItem['numUsers'];
                        $tmp['numSubscribers'] = 0;
                        $tmp['numSubscribers'] = $membershipCRUD->getNumSubscriptionsFromCountry($statItem['country'], $startDate, $endDate);
                        array_push($countries_statArr, $tmp);
                    }
                }
            }
            $output["countries_stat"] = $countries_statArr;
            /*** Get Resources categories Stats ***/

        }
        //$output["data"]
        $output["filterType"] = $filterType;
        $output["startDate"] = $startDate;
        $output["endDate"] = $endDate;
    } else {
        $output["error"] = true;
        $output["message"] = "Invalid data filter specified.";
        echoRespnse(200, $output);
        exit;
    }
    /******* Apply Filter ******/

    /************
     * OVERALL GRAPHICAL DATA
     * **************/

    /************ START OF DAILY EARNINGS **************
    $output["earningsArr"] = array();
    //$tmp = array();
    //$tmp["date"] = $date_start;
    //$tmp["earning"] = $row["doc_id"];
    for ($i = 1; $i < 100; $i++) {
    $tmp = array();
    $for_date_start = date('Y-m-d', strtotime('-' . ($i - 1) . ' days'));
    $tmp["date_start"] = $utilCRUD->getFormalDate($for_date_start);
    $for_date_end = date('Y-m-d', strtotime('-' . $i . ' days'));
    $tmp["date_end"] = $utilCRUD->getFormalDate($for_date_end);
    //$tmp["earning"] = $membershipCRUD->getSumAllTransactionsBetween("Manual", $for_date_start, $for_date_end);
    $tmp["earning"] = $membershipCRUD->getSumAllTransactionsBetween("Manual", $for_date_end, $for_date_start);
    if ($tmp["earning"] > 0) {
    //array_push($output["earningsArr"], $tmp);
    }
    //array_push($data_stats["earningsArr"], $tmp);
    }

     ************ END OF DAILY EARNINGS **************/

    /*
    $data = $reviewCRUD->getAllDocReviews();
    $custom_data = array();
    if (count($data) > 0) {
    foreach ($data as $row) {
    $tmp = array();
    $tmp["id"] = $row["id"];
    $tmp["doc_id"] = $row["doc_id"];
    $tmp["stars"] = $row["stars"];
    $tmp["text"] = $row["text"];
    $tmp["user_id"] = $row["user_id"];
    $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);

    $tmp["reviewer_name"] = $userCRUD->getNameByID($row["user_id"]);
    $tmp["doc_name"] = $documentCRUD->getNameByID($row["doc_id"]);
    array_push($custom_data, $tmp);
    }
    }

     */

    $output['error'] = false;
    $output['data'] = $data_stats;
    echoRespnse(200, $output);
});

/********################### DOCUMENT COVER UPLOAD API ###################*********/
$app->post('/front_banners/upload', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/site_settings.crud.php");
    $settingsCRUD = new SiteSettingsCRUD(getConnection());
    $response = array();
    $doc_type_name = "Banner";
    $response["error"] = true;
    $file_size = 0;
    $uploadFileName = "";
    $ext = "";
    $response['debug'] = "";
    $files = $request->getUploadedFiles();
    if (empty($files['cover_image'])) {
        $response['error'] = true;
        $response['message'] = 'You must upload a  photo for Home Page Top ' . $doc_type_name . '.';
        echoRespnse(200, $response);
        exit;
    }

    $uploadCoverName = "main_banner";
    /********* START COVER UPLOAD **********/
    if (!empty($files['cover_image'])) {
        //Delete Cover Image

        $fileToTest = $settingsCRUD->getFrontBannerLink();
        try {
            //$fileToTest = "uploads/banners/$uploadCoverName";
            if (file_exists($fileToTest)) {
                unlink($fileToTest);
                $response['debug'] .= ' Deleted: ' . $fileToTest . '.';
            }
        } catch (Exception $e) {
            $response['debug'] .= 'Exception deleting old file: ' . $e->getMessage() . '.';
        }

        try {
            $newfile = $files['cover_image'];
            $cover_file_type = "Unknown";
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $uploadCoverName = $newfile->getClientFilename();
                $uploadCoverName = explode(".", $uploadCoverName);
                $ext = array_pop($uploadCoverName);
                $ext = strtolower($ext);
                $uploadCoverName = $uploadCoverName . "." . $ext;

                $file_size = $newfile->getSize();
                $cover_file_type = $newfile->getClientMediaType();
                if (!$cover_file_type == "image/jpg" || !$cover_file_type == "image/jpeg" || !$cover_file_type == "image/jpeg") {
                    $response['error'] = true;
                    $response['message'] = 'Please upload a png, jpg or jpeg image file as the Cover Image.';
                    echoRespnse(200, $response);
                    return;
                }

                if ($cover_file_type > 1000000) {
                    $response['error'] = true;
                    $response['message'] = 'Upload a cover image of size not more than 1 MB.';
                    echoRespnse(200, $response);
                    return;
                }
                $newfile->moveTo("uploads/images/banners/$uploadCoverName");
                $res = $settingsCRUD->updateBannerLink($uploadCoverName);
                if (!$res["error"]) {
                    $response["error"] = false;
                    $response["message"] = "Banner has been updated successfully. ";
                    $response["id"] = $id;
                    echoRespnse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to upload banner. Please try again." . $res["message"];
                    echoRespnse(200, $response);
                }
            }
        } catch (Exception $e) {
            $response["error"] = true;
            $response["message"] = "Failed to upload banner. Please try again.";
            echoRespnse(200, $response);
            exit;
        }
    }
/********* END OF COVER UPLOAD **********/
})->add($authenticate);

/*************** USER LISTING ****************/
$app->get('/apis/users', function ($request, $response, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/utils.crud.php");
    require_once ("dbmodels/user_membership.crud.php");
    require_once ("dbmodels/membership_plan.crud.php");
    require_once ("dbmodels/reward_points.crud.php");
    $pointsCRUD = new RewardPointCRUD(getConnection());
    $membershipCRUD = new MembershipCRUD(getConnection());
    $utilCRUD = new UtilCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $planCRUD = new PlanCRUD(getConnection());
    $output = array();
    /******** TYPE 1: SYSTEM ADMIN ONLY **********/
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $roleID = $agentValidator["user_info"]["role_id"];
        if ($roleID == 1) {
            $output["adminMode"] = true;
        } else {
            $output["error"] = true;
            $output["message"] = "You are not authorized to request this action. ";
            echoRespnse(200, $output);
            return;
        }
    } else {
        $output["error"] = true;
        $output["agentValidationError"] = $agentValidator;
        echoRespnse(200, $output);
        exit;
    }
    /******** VERIFY THE REQUESTING AGENT **********/

    $data = $userCRUD->getAllUsers();
    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["first_name"] = $row["first_name"];
            $tmp["last_name"] = $row["last_name"];
            $tmp["fullName"] = $row["first_name"] . " " . $row["last_name"];
            $tmp["email"] = $row["email"];
            $tmp["status"] = $row["status"];
            $tmp["user_name"] = $row["user_name"];
            //$tmp["user_image"] = $row["user_image"];
            $tmp["country"] = $row["country"];
            $tmp["date_created"] = $row["date_created"];
            try {
                $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);
            } catch (\Throwable $th) {
                $tmp["date_created"] = "";
            }
            $tmp["description"] = $row["description"];
            $tmp["last_seen"] = $row["last_active"];
            //$tmp["loyalty_points"] = $pointsCRUD->getCurrentRewardPointFor($row["id"]);

            //Membership Info
            $tmp["membership_info"] = "";
            $numAllPlans = $membershipCRUD->getNumMyPlans($row["id"]);
            $tmp["numAllPlans"] = $numAllPlans;

            $numActivePlans = $membershipCRUD->getNumMyActivePlan($row["id"]);

            if ($numActivePlans > 0) {
                $activePlan = $membershipCRUD->getMyActivePlan($tmp["id"]);
                //$date_created = $activePlan["date_created"];
                //$date_expiring = $activePlan["date_expiring"];
                $tmp["membership_info"] = $planCRUD->getNameByID($activePlan["plan_id"]);
            }
            /*
            if(!empty($row["date_updated"])){
            $tmp["last_seen"] = "Active ".$utilCRUD->getTimeDifference($row["date_updated"]);
            }
             */
            array_push($custom_data, $tmp);
        }
    }
    $output["error"] = false;
    $output["items"] = $custom_data;
    echoRespnse(200, $output);
})->add($authenticate);

$app->post('/apis/user_reviews', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/document.crud.php");
    require_once ("dbmodels/user.crud.php");
    require_once ("dbmodels/document_reviews.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    $userCRUD = new UserCRUD(getConnection());
    $reviewCRUD = new DocumentReviewCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    $adminMode = false;
    $output = array();
    $output["error"] = true;

    /******** VERIFY THE REQUESTING AGENT **********/
    $accessorID = 0;
    $accessorRole = 0;
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $accessorRole = $agentValidator["user_info"]["role_id"];
    }

    /******** VERIFY THE REQUESTING AGENT **********/
    if ($accessorRole == 1) {
        $data = $reviewCRUD->getAllDocReviews();
    } else {
        $data = $reviewCRUD->getAllReviewsByUser($accessorID);
    }

    $custom_data = array();
    if (count($data) > 0) {
        foreach ($data as $row) {
            $tmp = array();
            $tmp["id"] = $row["id"];
            $tmp["doc_id"] = $row["doc_id"];
            $tmp["stars"] = $row["stars"];
            $tmp["text"] = $row["text"];
            $tmp["user_id"] = $row["user_id"];
            $tmp["date_created"] = $utilCRUD->getTimeDifference($row["date_created"]);

            $tmp["reviewer_name"] = $userCRUD->getNameByID($row["user_id"]);
            $tmp["doc_name"] = $docCRUD->getNameByID($row["doc_id"]);
            array_push($custom_data, $tmp);
        }
    }
    $output["data"] = $custom_data;
    $output["error"] = false;
    $output["message"] = "Reviews fetched.";
    echoRespnse(200, $output);
})->add($authenticate);

$app->post('/apis/enquiries/list', function ($request, $respo, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/utils.crud.php");
    $utilCRUD = new UtilCRUD(getConnection());
    require_once ("dbmodels/contacts.crud.php");
    $contactsCRUD = new ContactsCRUD(getConnection());
    $output = array();
    $output["error"] = true;

    /******** VERIFY THE REQUESTING AGENT **********/
    $accessorID = 0;
    $accessorRole = 0;
    $agentValidator = getRequestingAgent($request);
    if (!$agentValidator["error"]) {
        $accessorID = $agentValidator["user_info"]["id"];
        $accessorRole = $agentValidator["user_info"]["role_id"];
    }else{
        $output["error"] = true;
        $output["message"] = "Invalid request.";
        echoRespnse(200, $output);
        exit;
    }

    /******** VERIFY THE REQUESTING AGENT **********/
    if ($accessorRole != 1) {
        $output["error"] = true;
        $output["message"] = "Permission denied.";
        echoRespnse(200, $output);
        exit;
    }
    $data = $contactsCRUD->getAllMessages();
    $output["data"] = $data;
    $output["error"] = false;
    $output["message"] = "Enquiries have been fetched.";
    echoRespnse(200, $output);
})->add($authenticate);

/************* GET USAGE LOGS ***********/
$app->post('/apis/usage_logs', function ($request, $response, $args) use ($app) {
	require_once("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
	$output = array();
    $output["error"] = true;
	$agentValidator = getRequestingAgent($request);
	if(!$agentValidator["error"]){
				$agentID = $agentValidator["user_info"]["id"];
				$agentRole = $agentValidator["user_info"]["role_id"];
				$output["agentID"] = $agentID;
	}else{
				$output["error"] = true;
                $output["message"] = "BaziChic server could not authenticate this request.";
				$output["agentValidationError"] = $agentValidator;
				echoRespnse(200, $output);
				exit;
	}
    if($agentRole !== 1){
		$output["error"] = true;
        $output["message"] = "BaziChic server could not authenticate this request.";
		echoRespnse(200, $output);
		exit;
	}
	$output["error"] = false;
	$output["summary"] = array();
	$dataArr = $userCRUD->getAllUsage();
	$data = array();
	if (count($dataArr) > 0) {
	foreach ($dataArr as $row) {
	$tmp = getUsageDetail($row);
	array_push($data, $tmp);
	}
	}
    
	$output["summary"] = $data;
	/********* ECHO RESULTS *********/
    echoRespnse(200, $output);
    })->add($authenticate);

?>