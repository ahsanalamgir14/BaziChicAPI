<?php
/******************* END OF EBOOK READER ******************/
$app->post('/bazichic-ebook-reader', function (Request $request, Response $response, $args) use ($app) {
    require_once ("dbmodels/user.crud.php");
    $userCRUD = new UserCRUD(getConnection());
    require_once ("dbmodels/document.crud.php");
    $docCRUD = new DocumentCRUD(getConnection());
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    $allowReading = true;
    $userMessage = "";

    $doc_id = $request->getParam('doc_id');
    $doc_link = $request->getParam('doc_link');
    $is_downloadable = $request->getParam('is_downloadable');
    if (!$docCRUD->isIDExists($doc_id)) {
        $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string) $uri);
    }

    $vars = [
        'page' => [
            'title' => 'E-Book Reader | Bazichic - Chinese Metaphysics Consultancy',
            'description' => 'Access Unlimited E-Books, Audio Books and Magazines on Chinese Metaphysics',
            'doc_id' => $doc_id,
            'doc_link' => $doc_link,
            'is_downloadable' => $is_downloadable,
            'allowReading' => $allowReading,
            'userMessage' => $userMessage,
        ],
    ];
    return $this->view->render($response, 'ebook-reader.twig', $vars);
})->setName('bazichic-ebook-reader');
/******************* END OF EBOOK READER ******************/

/*************** GET DOCUMENT DETAIL ***************/
$app->get('/book-detail/{id}', function ($request, $response, $args) use ($app) {
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
    require_once ("dbmodels/user_membership.crud.php");
    $membershipCRUD = new MembershipCRUD(getConnection());
    require_once ("dbmodels/membership_plan.crud.php");
    $planCRUD = new PlanCRUD(getConnection());

    $is_membership_active = false;
    $free_trial_period = 30;
    $docQCode = $request->getAttribute('id');
    if (!$docCRUD->isQCodeExists($docQCode)) {
        $uri = $request->getUri()->withPath($this->router->pathFor('404.html'));
        return $response->withRedirect((string) $uri);
    }
    $docID = $docCRUD->getIDByQCode($docQCode);
    $document = $docCRUD->getID($docID);
    $doc_type = "";
    $unlock_tip = "Start reading anything. anywhere on Bazichic.";
    $unlock_title = "READ DOCUMENT";
    if ($document !== null) {
        $name = $document["title"];
        $doc_type_selected = $document["document_type"];
        switch ($doc_type_selected) {
            case 1:
                $doc_type = "E-Book";
                $unlock_title = "Read E-Book";
                $unlock_tip = "Click here to start reading this E-book now.";
                break;

            case 2:
                $doc_type = "Audio Book";
                $unlock_title = "Listen Audio Book";
                $unlock_tip = "Click here to start listening this audio book now.";
                break;

            case 3:
                $doc_type = "Magazine";
                $unlock_title = "Read Magazine";
                $unlock_tip = "Click here to read this Magazine now.";
                break;
        }
        $is_liked = false;
        $is_saved = false;
        $is_reviewed = false;
        $my_review = array();
        $membership_info = "";

        $vars = [
            'page' => [
                'title' => "Read " . $name . " Online on BaziChic - Chinese Metaphysics Consultancy",
                'description' => "Find more E-Books, Audio Books and Magazines at Bazichic.",
                'og_title' => "Read " . $name . " Online on BaziChic - Chinese Metaphysics Consultancy",
                'secure_img_url' => "https://www.bazichic.com/uploads/images/docs/" . $document["cover"],
                'og_image' => "https://www.bazichic.com/uploads/images/docs/" . $document["cover"],
                'og_url' => "https://www.bazichic.com/book-detail/" . $document["qcode"],

                'unlock_title' => $unlock_title,
                'unlock_tip' => $unlock_tip,
                'free_trial_period' => $free_trial_period,
                'is_membership_active' => $is_membership_active,
                'membership_info' => $membership_info,
                'active_plan' => $active_plan,
            ],
            'document' => [
                'id' => $docID,
                'title' => $document["title"],
                'description' => $document["description"],
                'category_id' => $document["category_id"],
                'cover' => $document["cover"],
                'qcode' => $document["qcode"],
                'category' => $categoryCRUD->getNameByID($document["category_id"]),
                'keywords' => $keyword_list,
                'price' => $document["price"],
                'read_time' => $document["read_time"],
                'listen_time' => $document["listen_time"],
                'document_type' => $document["document_type"],
                'doc_type' => $doc_type,
                'user_id' => $document["user_id"],
                'author_name' => $document["author_name"],
                'author_link' => $document["author_link"],
                'link' => $document["link"],
                'tag' => $document["tag"],
                'author_desc' => $document["author_desc"],
                'is_downloadable' => $document["is_downloadable"],
                'is_published' => $document["is_published"],
                'date_created' => $utilCRUD->getTimeDifference($document["date_created"]),
                'num_pages' => $document["num_pages"],
                'all_reviews' => $all_reviews,

                'avg_rating' => $reviewCRUD->getAvgReviewsFor($document["id"]),
                'num_reviews' => $reviewCRUD->getNumReviewsFor($document["id"]),
                'num_likes' => $likeCRUD->getNumLikes($document["id"]),
                'num_saves' => $docSaveCRUD->getNumSaves($document["id"]),
                'is_liked' => $is_liked,
                'is_saved' => $is_saved,
                'is_reviewed' => $is_reviewed,
                'my_review' => $my_review,
            ],
        ];
    }
    return $this->view->render($response, 'book-detail.twig', $vars);
})->setName('book-detail');
