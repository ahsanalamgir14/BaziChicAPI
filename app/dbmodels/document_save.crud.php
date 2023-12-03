<?php
require_once "Constants.php";
class DocumentSaveCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function create($user_id, $doc_id, $page, $progress, $date_created)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            $stmt = $this->db->prepare("INSERT INTO document_saves(user_id, doc_id, page, progress, date_created) VALUES(:user_id, :doc_id, :page, :progress, :date_created)");
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":doc_id", $doc_id);
            $stmt->bindparam(":page", $page);
            $stmt->bindparam(":progress", $progress);
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
            echo $e->getMessage();
            return $response;
        }

    }

    public function updateSave($id, $page, $progress, $date_updated)
    {
        $response = array();
        $response["error"] = true;
        $response["code"] = INSERT_FAILURE;
        $response["message"] = "Your request could not be processed.";

        /************* UPLOAD IMAGE **************/
        try {
            $stmt2 = $this->db->prepare("UPDATE document_saves SET page=:page,
			progress=:progress,
			date_updated=:date_updated
             WHERE id=:id");
            $stmt2->bindparam(":page", $page);
            $stmt2->bindparam(":progress", $progress);
            $stmt2->bindparam(":date_updated", $date_updated);
            $stmt2->bindparam(":id", $id);
            $res = $stmt2->execute();
            if ($res) {
                $response["code"] = INSERT_SUCCESS;
                $response["error"] = true;
                $response["message"] = "Your read has been saved successfully.";
            } else {
                $response["error"] = false;
                $response["message"] = "Failed to update save point.";
            }
        } catch (Exception $e) {
            $response["error"] = false;
            $response["message"] = "Error while processing request.";
        }
        /**************************************/
        return $response;
    }

    public function getID($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document_saves WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getNumSaves($doc_id)
    {
        $sql = "SELECT count(*) FROM document_saves WHERE doc_id=:doc_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getNumAllMySaves($user_id)
    {
        $sql = "SELECT count(*) FROM document_saves WHERE user_id=:user_id GROUP BY doc_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        if ($number_of_rows == null || empty($number_of_rows)) {
            $number_of_rows = 0;
        }
        return $number_of_rows;
    }

    public function getNumAllSaves($startDate = "", $endDate = "")
    {
        $sql = "SELECT count(*) FROM document_saves";
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " WHERE date_created >= '" . $startDate . "' AND date_created < '" . $endDate . "'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function isSavedBy($user_id, $doc_id)
    {
        $sql = "SELECT count(*) FROM document_saves WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function getActionRecordID($user_id, $doc_id)
    {
        $sql = "SELECT id FROM document_saves WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getLastSavedPage($user_id, $doc_id)
    {
        $sql = "SELECT page FROM document_saves WHERE doc_id=:doc_id AND user_id =:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":doc_id" => $doc_id, ":user_id" => $user_id));
        $number_of_rows = $stmt->fetchColumn();
        if ($number_of_rows == null || empty($number_of_rows)) {
            return 0;
        }
        return $number_of_rows;
    }

    public function getAllTheSaves()
    {
        $sql = "SELECT d.id, d.title, d.qcode, d.cover, d.document_type, ds.page, d.link, d.is_downloadable, ds.progress, ds.date_created, ds.date_updated FROM documents d INNER JOIN document_saves ds ON d.id = ds.doc_id WHERE d.id IN(SELECT doc_id FROM document_saves ORDER BY id DESC) GROUP BY d.id ORDER BY ds.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getAllMySaves($user_id)
    {
        //$sql = "SELECT d.id, d.title, d.qcode, d.cover, d.document_type, ds.page, d.link, d.is_downloadable, ds.progress, ds.date_created, ds.date_updated FROM documents d INNER JOIN document_saves ds ON d.id = ds.doc_id WHERE d.id IN(SELECT doc_id FROM document_saves WHERE user_id = :user_id ORDER BY id DESC) GROUP BY d.id ORDER BY ds.id DESC";
        $sql = "SELECT * FROM document_saves WHERE user_id =:user_id ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        //$stmt = $this->db->prepare("SELECT * FROM document_saves WHERE user_id =:user_id ORDER BY id DESC");
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getAllMySavesFirst($user_id)
    {
        $sql = "SELECT * FROM documents WHERE id IN(SELECT doc_id FROM document_saves WHERE user_id = :user_id)";
        $stmt = $this->db->prepare($sql);
        //$stmt = $this->db->prepare("SELECT * FROM document_saves WHERE user_id =:user_id ORDER BY id DESC");
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM document_saves WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
        return true;
    }

    public function deleteSave($user_id, $doc_id)
    {
        $stmt = $this->db->prepare("DELETE FROM document_saves WHERE user_id=:user_id AND doc_id=:doc_id");
        $stmt->bindparam(":user_id", $user_id);
        $stmt->bindparam(":doc_id", $doc_id);
        $stmt->execute();
        return true;
    }

}
