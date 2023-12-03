<?php
require_once "Constants.php";
class DocumentReviewCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function addReview($doc_id, $user_id, $stars, $text, $date_created)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            $stmt = $this->db->prepare("INSERT INTO document_reviews(doc_id, user_id, stars, text, date_created) VALUES(:doc_id, :user_id, :stars, :text, :date_created)");
            $stmt->bindparam(":doc_id", $doc_id);
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":stars", $stars);
            $stmt->bindparam(":text", $text);
            $stmt->bindparam(":date_created", $date_created);
            if ($stmt->execute()) {
                $response["error"] = false;
                $response["id"] = $this->db->lastInsertId();
                $response["code"] = INSERT_SUCCESS;
            } else {
                $response["error"] = true;
                $response["code"] = INSERT_FAILURE;
            }
            return $response;
        } catch (PDOException $e) {
            //echo $e->getMessage();
            $response["error"] = true;
            $response["code"] = INSERT_FAILURE;
            return $response;
        }

    }

    public function getReviewedRecordID($user_id, $doc_id)
    {
        $sql = "SELECT id FROM document_reviews WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function updateReview($id, $stars, $text, $date_updated)
    {
        $response = array();
        $response["error"] = true;
        $response["code"] = INSERT_FAILURE;
        $response["message"] = "Your request could not be processed.";

        /************* UPLOAD IMAGE **************/
        try {
            $stmt2 = $this->db->prepare("UPDATE document_reviews SET stars=:stars,
			text=:text,
			date_updated=:date_updated
             WHERE id=:id");
            $stmt2->bindparam(":stars", $stars);
            $stmt2->bindparam(":text", $text);
            $stmt2->bindparam(":date_updated", $date_updated);
            $stmt2->bindparam(":id", $id);
            $res = $stmt2->execute();
            if ($res) {
                $response["code"] = INSERT_SUCCESS;
                $response["error"] = true;
                $response["message"] = "Your review has been updated successfully.";
            } else {
                $response["error"] = false;
                $response["message"] = "Failed to update review.";
            }
        } catch (Exception $e) {
            $response["error"] = false;
            $response["message"] = "Error while processing request." . $e->getMessage();
        }
        /**************************************/
        return $response;
    }

    public function getID($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_reviews WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getMyReview($user_id, $doc_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_reviews WHERE user_id=:user_id AND doc_id=:doc_id");
        $stmt->execute(array(":user_id" => $user_id, ":doc_id" => $doc_id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getReviewsFor($doc_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_reviews WHERE doc_id=:doc_id ORDER BY id DESC");
        $stmt->execute(array(":doc_id" => $doc_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getAllReviewsByUser($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_reviews WHERE user_id=:user_id ORDER BY id DESC");
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getAllDocReviews()
    {
        $stmt = $this->db->prepare("SELECT * FROM document_reviews");
        $stmt->execute();
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function isReviewedBy($user_id, $doc_id)
    {
        $sql = "SELECT count(*) FROM document_reviews WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function getNumReviewsFor($doc_id)
    {
        $sql = "SELECT count(*) FROM document_reviews WHERE doc_id=:doc_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getAvgReviewsFor($doc_id)
    {
        $sql = "SELECT AVG(stars) FROM document_reviews WHERE doc_id=:doc_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getTotalReviewsDone($user_id)
    {
        $sql = "SELECT count(*) FROM document_reviews WHERE user_id=:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getNumAllReviews($startDate = "", $endDate = "")
    {
        $sql = "SELECT count(*) FROM document_reviews";
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " WHERE date_created >= '" . $startDate . "' AND date_created < '" . $endDate . "'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM document_reviews WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
        return true;
    }

}
