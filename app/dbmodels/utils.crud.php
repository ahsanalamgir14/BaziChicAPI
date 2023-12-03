<?php
require_once "Constants.php";
class UtilCRUD
{
    private $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function dateDiffInDays($date1, $date2)
    {
        $diff = strtotime($date2) - strtotime($date1);
        return abs(round($diff / 86400));
    }

    public function createNewUsername($length)
    {
        $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
        $name = "";

        for ($i = 0; $i < $length; $i++) {
            $name .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        if ($this->isUserNameExists($name) > 0) {
            createNewUsername($length);
        }
        return $name;
    }

    public function generateTXNID($length)
    {
        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $name = "";

        for ($i = 0; $i < $length; $i++) {
            $name .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        if ($this->isTXNIDExists($name) > 0) {
            generateTXNID($length);
        }
        return $name;
    }

    public function createFileName()
    {
        $length = 8;
        $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
        $name = "";

        for ($i = 0; $i < $length; $i++) {
            $name .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        if ($this->isUserNameExists($name) > 0) {
            createNewUsername($length);
        }
        return $name;
    }

    public function isUserNameExists($user_name)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE user_name=:user_name");
        $result = $stmt->execute(array(":user_name" => $user_name));
        $rows = $stmt->fetchAll(); // assuming $result == true
        $num_rows = count($rows);
        return $num_rows > 0;
    }

    public function isTXNIDExists($txn_id)
    {
        $stmt = $this->db->prepare("SELECT id FROM transaction_details WHERE txn_id=:txn_id");
        $result = $stmt->execute(array(":txn_id" => $txn_id));
        $rows = $stmt->fetchAll();
        $num_rows = count($rows);
        return $num_rows > 0;
    }

    public function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    private function getIntervalFromSeconds($init)
    {
        $days = floor($init / (24 * 60 * 60));
        $hours = floor(($init / (24 * 60 * 60)) % 24);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        if ($days > 0) {
            if ($days >= 30) {
                $months = floor($days / 30);
                return $months . " months";
            } else {
                return $days . " days";
            }
        } else {
            if ($hours > 0) {
                if ($hours >= 1) {
                    return $hours . " hour";
                } else {
                    return $hours . " hours";
                }
            } else {
                if ($minutes >= 1) {
                    return $minutes . " minutes";
                } else {
                    return "few seconds";
                }
            }
        }
    }

    public function getTimeDifference($datetime, $full = false)
    {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    public function getFormalDate($date_created)
    {
        try {
            $month = date("F", strtotime($date_created));
            $this_date = new DateTime($date_created);
            $day = $this_date->format('d');
            $year = $this_date->format('Y');
            return $day . " " . $month . " " . $year;
        } catch (Exception $e) {
            return $date_created;
        }
    }

/***** START OF REFERRAL CODE *******/
    public function generateReferralCode()
    {
        $length = 8;
        $characters = "QWERTYUIOPASDFGHJKLZXCVBNM0123456789";
        $code = "";

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        if ($this->isCodeExists($code) > 0) {
            generateReferralCode($length);
        }
        return $code;
    }

    public function isCodeExists($code)
    {
        $stmt = $this->db->prepare("SELECT id FROM referrals WHERE code=:code");
        $result = $stmt->execute(array(":code" => $code));
        $rows = $stmt->fetchAll();
        $num_rows = count($rows);
        return $num_rows > 0;
    }
    /***** END OF REFERRAL CODE *******/

    /******************* USER ACCOUNT VERIFICATION *******************/
    public function generateNewOTP()
    {
        $length = 4;
        $characters = "0123456789";
        $otp = "";
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $otp;
    }

    public function generateTempPassword()
    {
        $length = 10;
        $characters = "0123456789";
        $otp = "";
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $otp;
    }

    public function sendVerificationOTP($email, $otp)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            $stmt = $this->db->prepare("INSERT INTO generated_otps(email, otp) VALUES(:email, :otp)");
            $stmt->bindparam(":email", $email);
            $stmt->bindparam(":otp", $otp);
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

    public function getLastOTPSent($email)
    {
        $stmt = $this->db->prepare("SELECT otp FROM generated_otps WHERE email=:email ORDER BY id DESC LIMIT 1");
        $result = $stmt->execute(array(":email" => $email));
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getLastOTPEntryFor($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM generated_otps WHERE email=:email ORDER BY id DESC LIMIT 1");
        $stmt->execute(array(":email" => $email));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getNumOTPSentToday($email)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM generated_otps WHERE email=:email AND timestamp >= CURDATE()");
        $stmt->execute(array(":email" => $email));
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function getNumUnusedOTPSentTo($email)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM generated_otps WHERE email=:email AND used =0");
        $stmt->execute(array(":email" => $email));
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function setOTPVerified($id, $date_used)
    {
        $response = array();
        $response["error"] = true;
        $response["message"] = "";
        try
        {
            $stmt = $this->db->prepare("UPDATE generated_otps SET used= 1,
				date_used=:date_used WHERE id=:id");
            $stmt->bindparam(":date_used", $date_used);
            $stmt->bindparam(":id", $id);
            $stmt->execute();
            $response["error"] = false;
            $response["message"] = "OTP has been used successfully.";
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "Failed to redeem OTP. " . $e->getMessage();
            return $response;
        }
    }
/********************** END OF VERIFICATION ************************/

    /************* PAYPAL TESTS **************/
    public function createPayTest($user_id, $title, $body)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            $stmt = $this->db->prepare("INSERT INTO paypal_tests(user_id, title, body) VALUES(:user_id, :title, :body)");
            $stmt->bindparam(":user_id", $user_id);
            $stmt->bindparam(":title", $title);
            $stmt->bindparam(":body", $body);
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
    /************** END OF TESTS*************/

}
