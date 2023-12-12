<?php
require_once "Constants.php";
class MembershipCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function getByQCode($qcode)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_memberships WHERE qcode=:qcode");
        $stmt->execute(array(":qcode" => $qcode));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function isRefQCodeExists($qcode)
    {
        $sql = "SELECT count(*) FROM user_memberships WHERE qcode=:qcode";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":qcode" => $qcode));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function generateCode()
    {
        require_once "CodeGenerator.php";
        $generator = new CouponGenerator;
        $tokenLength = 16;
        $voucherNum = $generator->generate($tokenLength);
        if ($this->isCodeValid($voucherNum) > 0) {
            generateCode();
        }
        return $voucherNum;
    }

    public function isCodeValid($qcode)
    {
        $stmt = $this->db->prepare("SELECT id from user_memberships WHERE qcode =:qcode");
        $stmt->execute(array(":qcode" => $qcode));
        $editRow = $stmt->fetchColumn();
        return $editRow > 0;
    }

    public function updateExpiryDate($qcode, $date_expiring, $date_updated)
    {
        $response = array();
        try
        {
            $stmt = $this->db->prepare("UPDATE user_memberships SET date_expiring=:date_expiring, date_updated=:date_updated
             WHERE qcode=:qcode");
            $stmt->bindparam(":date_expiring", $date_expiring);
            $stmt->bindparam(":date_updated", $date_updated);
            $stmt->bindparam(":qcode", $qcode);
            $stmt->execute();

            $response["error"] = false;
            $response["message"] = "Membership request updated successfully.";
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "An error occurred while processing your request. Try again.";
            return $response;
        }
    }

    public function update($id, $user_id, $plan_id, $date_created, $date_expiring, $amount, $status, $note, $date_updated)
    {
        $response = array();
        try
        {
            $stmt = $this->db->prepare("UPDATE user_memberships SET user_id=:user_id, plan_id=:plan_id, date_created=:date_created, date_expiring=:date_expiring, amount=:amount, status=:status, note=:note, date_updated=:date_updated WHERE id=:id");
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":plan_id", $plan_id);
            $stmt->bindparam(":date_created", $date_created);
            $stmt->bindparam(":date_expiring", $date_expiring);
            $stmt->bindparam(":amount", $amount);
            $stmt->bindparam(":status", $status);
            $stmt->bindparam(":note", $note);
            $stmt->bindparam(":date_updated", $date_updated);
            $stmt->bindparam(":id", $id);
            $stmt->execute();
            $response["error"] = false;
            $response["message"] = "Subscription details updated successfully.";
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "An error occurred while processing your request. Try again.";
            return $response;
        }
    }

    public function create($user_id, $plan_id, $date_created, $date_expiring, $amount, $status, $qcode, $note)
    {
        $response = array();
        $response["error"] = true;
        $response["msg"] = '';
        $temp = "";
        try
        {
            $stmt = $this->db->prepare("INSERT INTO user_memberships(user_id, plan_id, date_created, date_expiring, amount, status, qcode, note) VALUES(:user_id, :plan_id, :date_created, :date_expiring, :amount, :status, :qcode, :note)");
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":plan_id", $plan_id);
            $stmt->bindparam(":date_created", $date_created);
            $stmt->bindparam(":date_expiring", $date_expiring);
            $stmt->bindparam(":amount", $amount);
            $stmt->bindparam(":status", $status);
            $stmt->bindparam(":qcode", $qcode);
            $stmt->bindparam(":note", $note);
            if ($stmt->execute()) {
                $response["error"] = false;
                $response["id"] = $this->db->lastInsertId();
                $response["code"] = INSERT_SUCCESS;
                $prodID = $response["id"];
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
        $stmt = $this->db->prepare("SELECT * FROM user_memberships WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function isIDExists($id)
    {
        $stmt = $this->db->prepare("SELECT id FROM user_memberships WHERE id=:id");
        $result = $stmt->execute(array(":id" => $id));
        $rows = $stmt->fetchAll();
        $num_rows = count($rows);
        return $num_rows > 0;
    }

    /************/
    public function getMyActivePlan($user_id)
    {
        $status = "Active";
        $sql = "SELECT * FROM user_memberships WHERE user_id=:user_id AND status=:status AND NOW() BETWEEN date_created AND date_expiring ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getMySubscriptionHistory($user_id)
    {
        $sql = "SELECT * FROM user_memberships WHERE user_id=:user_id AND status='Active' ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function isSubscriptionActive($id)
    {
        $sql = "SELECT count(*) FROM user_memberships WHERE id=:id AND status='Active' AND NOW() BETWEEN date_created AND date_expiring";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetchColumn();
        return $editRow;
    }

    public function getMySubscriptionHistoryOLD($user_id)
    {
        $sql = "SELECT * FROM user_memberships WHERE user_id=:user_id AND status='Active' AND NOW() NOT BETWEEN date_created AND date_expiring ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }
    /**************/

    public function getDetailByID($id)
    {
        $status = "Active";
        $sql = "SELECT membersip_plans.*, user_memberships.date_created AS date_enrolled, user_memberships.timestamp AS date_requested, user_memberships.id AS item_id FROM membersip_plans JOIN user_memberships ON(membersip_plans.id = user_memberships.plan_id and user_memberships.id =:id AND user_memberships.status=:status) ORDER BY item_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":id" => $id, ":status" => $status));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getAllPlans()
    {
        $stmt = $this->db->prepare("SELECT * FROM user_memberships ORDER BY id DESC");
        $stmt->execute();
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getPurchasedPlansList()
    {
        $status = "Active";
        $stmt = $this->db->prepare("SELECT * FROM user_memberships WHERE status =:status ORDER BY id DESC");
        $stmt->execute(array(":status" => $status));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getPosterID($id)
    {
        $stmt = $this->db->prepare("SELECT user_id FROM user_memberships WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getNameByID($id)
    {
        $stmt = $this->db->prepare("SELECT title FROM user_memberships WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM user_memberships WHERE id=:id");
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateStatus($qcode, $status, $date_updated)
    {
        $response = array();
        try
        {
            $stmt = $this->db->prepare("UPDATE user_memberships SET status=:status, date_updated=:date_updated
             WHERE qcode=:qcode");
            $stmt->bindparam(":status", $status);
            $stmt->bindparam(":date_updated", $date_updated);
            $stmt->bindparam(":qcode", $qcode);
            $stmt->execute();

            $response["error"] = false;
            $response["message"] = "Membership request updated successfully.";
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "An error occurred while processing your request. Try again.";
            return $response;
        }
    }

    public function getNumMyPlans($user_id)
    {
        $status = "Active";
        $sql = "SELECT count(*) FROM user_memberships WHERE user_id=:user_id AND status=:status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getNumPlanSales($plan_id)
    {
        $status = "Active";
        $sql = "SELECT count(*) FROM user_memberships WHERE plan_id=:plan_id AND status=:status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":plan_id" => $plan_id, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows;
    }

    public function getNumPlanSalesBetween($startDate, $endDate, $plan_id)
    {
        $status = "Active";
        $sql = "SELECT count(*) FROM user_memberships WHERE plan_id=" . $plan_id . " AND status='" . $status . "'";
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND timestamp >= '" . $startDate . "' AND timestamp < '" . $endDate . "'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function getNumMyActivePlan($user_id)
    {
        $status = "Active";
        $sql = "SELECT count(*) FROM user_memberships WHERE user_id=:user_id AND status=:status AND NOW() BETWEEN date_created AND date_expiring ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        if (empty($number_of_rows) || $number_of_rows === null) {
            return 0;
        }
        return $number_of_rows;
    }

    public function getAllMyPlans($user_id, $status)
    {
        $sql = "SELECT * FROM user_memberships WHERE user_id=:user_id AND status=:status ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    /**************** LIVE DATA ***************/
    public function getNumTotalActivePlans($plan_id)
    {
        $status = "Active";
        //$sql = "SELECT count(*) FROM user_memberships WHERE plan_id=:plan_id AND status=:status";
        $sql = "SELECT count(*) FROM user_memberships WHERE plan_id=:plan_id AND status=:status AND NOW() BETWEEN date_created AND date_expiring
  ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":plan_id" => $plan_id, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        if (empty($number_of_rows) || $number_of_rows === null) {
            return 0;
        }
        return $number_of_rows;
    }

    public function getSumAllTransactions($mode)
    {
        $status = "Completed";
        $sql = "SELECT SUM(amount) FROM transaction_details WHERE mode=:mode AND status=:status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":mode" => $mode, ":status" => $status));
        $number_of_rows = $stmt->fetchColumn();
        if (empty($number_of_rows) || $number_of_rows === null) {
            return 0;
        }
        return $number_of_rows;
    }

    public function getSumAllTransactionsBetween($mode, $date_start, $date_end)
    {
        $status = "Completed";
        if ($mode == "") {
            $sql = "SELECT SUM(amount) FROM transaction_details WHERE status=:status AND timestamp BETWEEN :date_start AND :date_end";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(":status" => $status, ":date_start" => $date_start, ":date_end" => $date_end));
            $number_of_rows = $stmt->fetchColumn();
            if (empty($number_of_rows) || $number_of_rows === null) {
                return 0;
            }
            return $number_of_rows;
        } else {
            $sql = "SELECT SUM(amount) FROM transaction_details WHERE mode=:mode AND status=:status AND timestamp BETWEEN :date_start AND :date_end";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(":mode" => $mode, ":status" => $status, ":date_start" => $date_start, ":date_end" => $date_end));
            $number_of_rows = $stmt->fetchColumn();
            if (empty($number_of_rows) || $number_of_rows === null) {
                return 0;
            }
            return $number_of_rows;
        }
    }

    public function getNumTransactionsBetween($mode, $date_start, $date_end)
    {
        $status = "Completed";
        $sql = "SELECT count(1) FROM transaction_details WHERE status='" . $status . "' ";

        if (!empty($mode)) {
            $sql .= " AND mode = '" . $mode . "' ";
        }
        if (!empty($date_start) && !empty($date_end)) {
            $sql .= "AND timestamp BETWEEN '" . $date_start . "' AND '" . $date_end . "'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $number_of_rows = $stmt->fetchColumn();
        if (empty($number_of_rows) || $number_of_rows === null) {
            return 0;
        }
        return $number_of_rows;
    }

    public function getNumSubscriptionsFromCountry($country, $date_start, $date_end)
    {
        $status = "Active";
        if (!empty($date_start) && !empty($date_end)) {
            $sql = "SELECT count(1) FROM user_memberships WHERE status=:status AND timestamp BETWEEN :date_start AND :date_end AND user_id IN(SELECT id FROM users WHERE country=:country)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(":status" => $status, ":date_start" => $date_start, ":date_end" => $date_end, ":country" => $country));
            $number_of_rows = $stmt->fetchColumn();
            if (empty($number_of_rows) || $number_of_rows === null) {
                return 0;
            }
            return $number_of_rows;
        } else {
            $sql = "SELECT count(1) FROM user_memberships WHERE status=:status AND user_id IN(SELECT id FROM users WHERE country=:country)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(":status" => $status, ":country" => $country));
            $number_of_rows = $stmt->fetchColumn();
            if (empty($number_of_rows) || $number_of_rows === null) {
                return 0;
            }
            return $number_of_rows;
        }
    }

    /***************************************/

    public function getAllMyPlansExcept($user_id, $id)
    {
        $status = "Active";
        $sql = "SELECT * FROM user_memberships WHERE user_id=:user_id AND status=:status AND id !=:id ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":user_id" => $user_id, ":status" => $status, ":id" => $id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function getAllUserPlansList()
    {
        $status = "Active";
        $stmt = $this->db->prepare("SELECT * FROM user_memberships WHERE status=:status ORDER BY id DESC");
        $stmt->execute(array(":status" => $status));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function doesQCodexists($qcode)
    {
        $sql = "SELECT count(*) FROM user_memberships WHERE qcode=:qcode";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(":qcode" => $qcode));
        $number_of_rows = $stmt->fetchColumn();
        return $number_of_rows > 0;
    }

    public function deleteByQcode($qcode)
    {
        $stmt = $this->db->prepare("DELETE FROM user_memberships WHERE qcode=:qcode");
        $stmt->bindparam(":qcode", $qcode);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getUsersWithExpiringMemberships()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT um.*, mp.title as user_plan, u.email, u.first_name, u.last_name
                FROM user_memberships um
                left JOIN membersip_plans mp ON um.plan_id = mp.id
                left JOIN users u ON um.user_id = u.id
                WHERE um.date_expiring = CURDATE() + INTERVAL 30 DAY
                ANd um.status = 'Active'
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Handle the exception (log it, return an empty array, etc.)
            return $e->getMessage();
        }
    }
}