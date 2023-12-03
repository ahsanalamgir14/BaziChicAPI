<?php
require_once "Constants.php";
class DocumentLikeCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function create($user_id, $doc_id, $date_created)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            $stmt = $this->db->prepare("INSERT INTO document_likes(user_id, doc_id, date_created) VALUES(:user_id, :doc_id, :date_created)");
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":doc_id", $doc_id);
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
            $response["error"] = true;
            $response["code"] = INSERT_FAILURE;
            $response["msg"] = $e->getMessage();
            return $response;
        }
    }

    public function getID($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_likes WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getNumLikes($doc_id)
    {
        $sql = "SELECT count(*) FROM document_likes WHERE doc_id=:doc_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getTotalLikesDone($user_id)
    {
        $sql = "SELECT count(*) FROM document_likes WHERE user_id=:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getNumAllLikes($startDate = "", $endDate = "")
    {
        $sql = "SELECT count(*) FROM document_likes";
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " WHERE date_created >= '" . $startDate . "' AND date_created < '" . $endDate . "'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getMyBookmarks($user_id)
    {
        $is_published = 1;
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id IN(SELECT doc_id FROM document_likes WHERE user_id = :user_id) AND is_published =:is_published LIMIT 100");
        $stmt->bindparam(":user_id", $user_id);
        $stmt->bindparam(":is_published", $is_published);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    public function isLikedBy($user_id, $doc_id)
    {
        $sql = "SELECT count(*) FROM document_likes WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function getActionRecordID($user_id, $doc_id)
    {
        $sql = "SELECT id FROM document_likes WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM document_likes WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
        return true;
    }

    public function deleteFav($user_id, $doc_id)
    {
        $stmt = $this->db->prepare("DELETE FROM document_likes WHERE user_id=:user_id AND doc_id=:doc_id");
        $stmt->bindparam(":user_id", $user_id);
        $stmt->bindparam(":doc_id", $doc_id);
        $stmt->execute();
        return true;
    }

}
