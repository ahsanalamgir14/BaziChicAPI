<?php
require_once "Constants.php";
class PaymentCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function doesTXNExists($txn_id)
    {
        $sql = "SELECT count(*) FROM transaction_details WHERE txn_id=:txn_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":txn_id" => $txn_id));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function isRefQCodeExists($refCode)
    {
        $sql = "SELECT count(*) FROM transaction_details WHERE refCode=:refCode";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":refCode" => $refCode));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function getByRefCoe($refCode)
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_details WHERE refCode=:refCode");
        $stmt->execute(array(":refCode" => $refCode));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function create($user_id, $sender, $receiver, $item_code, $refCode, $amount, $currency_code, $txn_id, $pay_key, $status, $app_status, $note, $mode = "Online")
    {
        $response = array();
        $response["error"] = true;
        $temp = "";
        try
        {
            $stmt = $this->db->prepare("INSERT INTO transaction_details(user_id, sender, receiver, item_code, refCode, amount, currency_code, txn_id, pay_key, status, app_status, note, mode) VALUES(:user_id, :sender, :receiver, :item_code, :refCode, :amount, :currency_code, :txn_id, :pay_key, :status, :app_status, :note, :mode)");

            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":sender", $sender);
            $stmt->bindparam(":receiver", $receiver);
            $stmt->bindparam(":item_code", $item_code);
            $stmt->bindparam(":refCode", $refCode);
            $stmt->bindparam(":amount", $amount);
            $stmt->bindparam(":currency_code", $currency_code);
            $stmt->bindparam(":txn_id", $txn_id);
            $stmt->bindparam(":pay_key", $pay_key);
            $stmt->bindparam(":status", $status);
            $stmt->bindparam(":app_status", $app_status);
            $stmt->bindparam(":note", $note);
            $stmt->bindparam(":mode", $mode);
            if ($stmt->execute()) {
                $response["error"] = false;
                $response["id"] = $this->db->lastInsertId();
                $response["code"] = INSERT_SUCCESS;
                //$prodID = $response["id"];
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

    public function update($id, $txn_id, $mode, $note)
    {
        $stmt = $this->db->prepare("UPDATE transaction_details SET txn_id=:txn_id, mode=:mode, note=:note
          WHERE id=:id ");
        $stmt->bindparam(":txn_id", $txn_id);
        $stmt->bindparam(":mode", $mode);
        $stmt->bindparam(":note", $note);
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getID($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_details WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getMyPayments($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_details WHERE user_id=:user_id ORDER BY id DESC");
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getAllTransactions()
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_details ORDER BY id DESC");
        $stmt->execute();
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getUserByTXNID($txn_id)
    {
        $stmt = $this->db->prepare("SELECT user_id FROM transaction_details WHERE txn_id=:txn_id");
        $stmt->execute(array(":txn_id" => $txn_id));
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getTXNByItemID($item_code)
    {
        $stmt = $this->db->prepare("SELECT txn_id FROM transaction_details WHERE item_code=:item_code");
        $stmt->execute(array(":item_code" => $item_code));
        $rows = $stmt->fetchColumn();
//   if(empty($rows)){
        //       return "";
        //   }
        return $rows;
    }

    public function getTXNByQcode($refCode)
    {
        $stmt = $this->db->prepare("SELECT txn_id FROM transaction_details WHERE refCode=:refCode");
        $stmt->execute(array(":refCode" => $refCode));
        $rows = $stmt->fetchColumn();

        return $rows;
    }

    public function getTXNModeByQcode($refCode)
    {
        $stmt = $this->db->prepare("SELECT mode FROM transaction_details WHERE refCode=:refCode");
        $stmt->execute(array(":refCode" => $refCode));
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getRowIDByRefCode($refCode)
    {
        $stmt = $this->db->prepare("SELECT id FROM transaction_details WHERE refCode=:refCode");
        $stmt->execute(array(":refCode" => $refCode));
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getTXNStatusByQcode($refCode)
    {
        $stmt = $this->db->prepare("SELECT status FROM transaction_details WHERE refCode=:refCode");
        $stmt->execute(array(":refCode" => $refCode));
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function isPlanTransactionExists($user_id, $status)
    {
        $sql = "SELECT count(*) FROM transaction_details WHERE user_id=:user_id AND status=:status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function deleteByRefCode($refCode)
    {
        $stmt = $this->db->prepare("DELETE FROM transaction_details WHERE refCode=:refCode");
        $stmt->bindparam(":refCode", $refCode);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

}
