<?php
require_once "Constants.php";
class UserCRUD
{
    public $db;

    public function __construct($DB_con)
    {
        $this->db = $DB_con;
    }

    public function getDb() {
        return $this->db;
    }

    public function register($first_name, $last_name, $user_name, $type, $phone, $email, $password, $dob, $country, $description, $date_created, $status, $role_id, $ref_user_id, $referral_code, $api_key)
    {
        $response = array();
        $response["error"] = true;
        try
        {
            if (!$this->isEmailRegistered($email)) {
                $stmt = $this->db->prepare("INSERT INTO users(first_name, last_name, user_name, type, phone, email, password, dob, country, description, date_created, status, role_id, ref_user_id, referral_code, api_key) VALUES(:first_name, :last_name, :user_name, :type, :phone, :email, :password, :dob, :country, :description, :date_created, :status, :role_id, :ref_user_id, :referral_code, :api_key)");
                $stmt->bindparam(":first_name", $first_name);
                $stmt->bindparam(":last_name", $last_name);
                $stmt->bindparam(":user_name", $user_name);
                $stmt->bindparam(":type", $type);
                $stmt->bindparam(":phone", $phone);
                $stmt->bindparam(":email", $email);
                $stmt->bindparam(":password", $password);
                $stmt->bindparam(":dob", $dob);
                $stmt->bindparam(":country", $country);
                $stmt->bindparam(":description", $description);
                $stmt->bindparam(":date_created", $date_created);
                $stmt->bindparam(":status", $status);
                $stmt->bindparam(":role_id", $role_id);
                $stmt->bindparam(":ref_user_id", $ref_user_id);
                $stmt->bindparam(":referral_code", $referral_code);
                $stmt->bindparam(":api_key", $api_key);

                if ($stmt->execute()) {
                    $response["error"] = false;
                    $response["id"] = $this->db->lastInsertId();
                    $response["code"] = INSERT_SUCCESS;
                    $response["userName"] = $user_name;
                    $response["message"] = "Great! You are now a registered member.";
                } else {
                    $response["error"] = true;
                    $response["message"] = "Oops! An error occurred while registering. Try again.";
                    $response["code"] = INSERT_FAILURE;
                }
            } else {
                $response["error"] = true;
                $response["message"] = "Looks like you are already registered.";
                $response["code"] = ALREADY_EXIST;
            }
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "Exception happended."+$e->getMessage();
            echo $e->getMessage();
            return $response;
        }
    }

    public function getID($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getCountriesUsersSummary($date_start = "", $date_end = "")
    {
        if (!empty($startDate) && !empty($endDate)) {
            $sql = "SELECT country, COUNT(*) as numUsers FROM users WHERE timestamp BETWEEN :date_start AND :date_end GROUP BY country ORDER BY numUsers DESC LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(":date_start" => $date_start, ":date_end" => $date_end));
            $number_of_rows = $stmt->fetchColumn();
            if (empty($number_of_rows) || $number_of_rows === null) {
                return 0;
            }
            return $number_of_rows;
        } else {
            $stmt = $this->db->prepare("SELECT country, COUNT(*) as numUsers FROM users GROUP BY country ORDER BY numUsers DESC LIMIT 10");
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return $rows;
        }
    }

    public function getByUsername($user_name)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_name=:user_name");
        $stmt->execute(array(":user_name" => $user_name));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=:email");
        $stmt->execute(array(":email" => $email));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function isValidApiKey($api_key)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE api_key=:api_key");
        $result = $stmt->execute(array(":api_key" => $api_key));
        $rows = $stmt->fetchColumn();
        return $rows > 0;
    }

    public function getUserByAPIKey($api_key)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE api_key=:api_key LIMIT 1");
        $stmt->execute(array(":api_key" => $api_key));
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow;
    }

    public function getIDByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email='$email'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function isEmailRegistered($email)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email=:email");
        $result = $stmt->execute(array(":email" => $email));
        $rows = $stmt->fetchAll(); // assuming $result == true
        $num_rows = count($rows);
        return $num_rows > 0;
    }

    public function getEmail($id)
    {
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getRoleID($id)
    {
        $stmt = $this->db->prepare("SELECT role_id FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getUserImageByID($id)
    {
        $stmt = $this->db->prepare("SELECT user_image FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getIDByUsername($user_name)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE user_name='$user_name'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getUsernameByID($id)
    {
        $stmt = $this->db->prepare("SELECT user_name FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getNameByID($id)
    {
        $stmt = $this->db->prepare("SELECT first_name FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT last_name FROM users WHERE id='$id'");
        $stmt->execute();
        $rows2 = $stmt->fetchColumn();

        return $rows . " " . $rows2;
    }

    public function getNameByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT first_name FROM users WHERE email='$email'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT last_name FROM users WHERE email='$email'");
        $stmt->execute();
        $rows2 = $stmt->fetchColumn();

        return $rows . " " . $rows2;
    }

    public function getUsersByRole($role_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role_id=:role_id");
        $result = $stmt->execute(array(":role_id" => $role_id));
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function update($id, $first_name, $last_name, $type, $country, $dob, $latitude, $longitude, $email, $description, $date_updated)
    {
        $response = array();
        $response["error"] = true;
        $response["message"] = "Profile update request is received successfully.";
        $response["note"] = "";

        try
        {
            $stmt = $this->db->prepare("UPDATE users SET first_name=:first_name,
                last_name=:last_name,
                type=:type,
                country=:country,
				dob=:dob,
                latitude=:latitude,
				longitude=:longitude,
				email=:email,
				description=:description,
				date_updated=:date_updated
             WHERE id=:id");

            $stmt->bindparam(":first_name", $first_name);
            $stmt->bindparam(":last_name", $last_name);
            $stmt->bindparam(":type", $type);
            $stmt->bindparam(":country", $country);
            $stmt->bindparam(":dob", $dob);
            $stmt->bindparam(":latitude", $latitude);
            $stmt->bindparam(":longitude", $longitude);
            $stmt->bindparam(":email", $email);
            $stmt->bindparam(":description", $description);
            $stmt->bindparam(":date_updated", $date_updated);
            $stmt->bindparam(":id", $id);
            $stmt->execute();

            $response["error"] = false;
            $response["message"] = "Profile updated successfully. " . $response["note"];
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            //$response["message"] = $e->getMessage();
            $response["message"] = "Error updating profile. Please try again.";
            return $response;
        }
    }

    public function updateImage($id, $user_image)
    {
        $response = array();
        $response["error"] = true;
        $response["message"] = "Profile photo update request is received successfully.";

        /************* UPLOAD IMAGE **************/
        try {
            if (!empty($user_image)) {
                $actualpath = $user_image;
                $stmt2 = $this->db->prepare("UPDATE users SET user_image=:user_image
             WHERE id=:id");

                $stmt2->bindparam(":user_image", $actualpath);
                $stmt2->bindparam(":id", $id);
                $res = $stmt2->execute();
                if ($res) {
                    $response["message"] = "Profile photo updated successfully.";
                }
            }
        } catch (Exception $e) {
            $response["message"] = "Could not upload Image.";
        }
        /**************************************/

        $response["error"] = false;
        return $response;
    }

    public function updateStatus($id, $status)
    {
        $response = array();
        try
        {
            $stmt = $this->db->prepare("UPDATE users SET status=:status
                WHERE id=:id ");
            $stmt->bindparam(":status", $status);
            $stmt->bindparam(":id", $id);
            $stmt->execute();

            $response["error"] = false;
            if ($status == "Active") {
                $response["message"] = "User account is activated successfully.";
            } else {
                $response["message"] = "This User account has been blocked.";
            }
            return $response;
        } catch (PDOException $e) {
            $response["error"] = true;
            $response["message"] = "An error occurred while processing your request. Try again.";
            return $response;
        }
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
        return true;
    }

    public function getAllUsers()
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role_id != 1 ORDER BY id DESC");
        $stmt->execute();
        $editRow = $stmt->fetchAll();
        return $editRow;
    }

    public function updatePassword($id, $password)
    {
        $stmt = $this->db->prepare("UPDATE users SET password=:password
             WHERE id=:id ");
        $stmt->bindparam(":password", $password);
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateLastActive($id, $last_active)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_active=:last_active
             WHERE id=:id ");
        $stmt->bindparam(":last_active", $last_active);
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserType($id)
    {
        $stmt = $this->db->prepare("SELECT type FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function getPaypalByUserID($id)
    {
        $stmt = $this->db->prepare("SELECT paypal FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function updatePaypal($id, $paypal)
    {
        $stmt = $this->db->prepare("UPDATE users SET paypal=:paypal
             WHERE id=:id ");
        $stmt->bindparam(":paypal", $paypal);
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateFather($id, $ref_user_id)
    {
        $stmt = $this->db->prepare("UPDATE users SET ref_user_id=:ref_user_id
             WHERE id=:id");
        $stmt->bindparam(":ref_user_id", $ref_user_id);
        $stmt->bindparam(":id", $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getNumUsers($role_id, $status = "")
    {
        if ($role_id == 0) {
            $stmt = $this->db->prepare("SELECT count(*) FROM users WHERE role_id != 1");
            $stmt->execute();
        } else {
            if (empty($status)) {
                $stmt = $this->db->prepare("SELECT count(*) FROM users WHERE role_id =:role_id");
                $stmt->execute(array(":role_id" => $role_id));
            } else {
                $stmt = $this->db->prepare("SELECT count(*) FROM users WHERE role_id=:role_id AND status=:status");
                $stmt->execute(array(":role_id" => $role_id, ":status" => $status));
            }
        }
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function getNumUsersBetween($startDate, $endDate, $status = "")
    {
        $sql = "SELECT count(*) FROM users WHERE";
        if (!empty($status)) {
            $sql .= " status='$status'";
            if (!empty($startDate) && !empty($endDate)) {
                $sql .= " AND timestamp >= '" . $startDate . "' AND timestamp <= '" . $endDate . "'";
            }
        } else {
            if (!empty($startDate) && !empty($endDate)) {
                $sql .= " timestamp >= '" . $startDate . "' AND timestamp <= '" . $endDate . "'";
            }
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function getNumSubscribedUsers()
    {
        $sql = "SELECT count(*) FROM users WHERE id IN(SELECT DISTINCT(user_id) FROM user_memberships WHERE status='Active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function getNumSubscribedUsersBetween($startDate, $endDate)
    {
        $sql = "SELECT count(*) FROM users WHERE id IN(SELECT user_id FROM user_memberships WHERE ";
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= "timestamp >= '" . $startDate . "' AND timestamp <= '" . $endDate . "' AND ";
        }
        $sql .= "status='Active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $numRow = $stmt->fetchColumn();
        return $numRow;
    }

    public function whoReferedThis($id)
    {
        $stmt = $this->db->prepare("SELECT ref_user_id FROM users WHERE id='$id'");
        $stmt->execute();
        $rows = $stmt->fetchColumn();
        return $rows;
    }

    public function isEmailInDatabase($email)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email=:email");
        $result = $stmt->execute(array(":email" => $email));
        $rows = $stmt->fetchColumn();
        if (!empty($rows)) {
            return true;
        }
        return false;
    }

    public function checkLogin($email, $password)
    {
        require_once 'PassHash.php';
        $stmt = $this->db->prepare("SELECT password FROM users WHERE email='$email'");
        $stmt->execute();
        $password_hash = $stmt->fetchColumn();

        if ($stmt->rowCount() > 0) {
            if (PassHash::check_password($password_hash, $password)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**********
     * USAGE TRACKER
     * ********/

    public function addToUsage($api_key, $signature, $callerInfo="")
    {
     $response = array();	
     $response["error"] = true;  
     try
     {
      $ipAddress = $_SERVER['REMOTE_ADDR'];  
      $stmt = $this->db->prepare("INSERT INTO app_usage(signature, api_key, callerInfo, ipAddress) VALUES(:signature, :api_key, :callerInfo, :ipAddress)");
      $stmt->bindparam(":signature", $signature);
      $stmt->bindparam(":api_key", $api_key);
      $stmt->bindparam(":callerInfo", $callerInfo);
      $stmt->bindparam(":ipAddress", $ipAddress);
      if($stmt->execute()){
          $response["error"] = false;  
          $response["id"] = $this->db->lastInsertId();  
          $response["code"] = INSERT_SUCCESS; 
      }else{
          $response["error"] = true;  
          $response["code"] = INSERT_FAILURE; 
      }
      return $response;
     }
     catch(PDOException $e)
     {
      $response["error"] = true;  
      $response["code"] = INSERT_FAILURE; 	  
      $response["message"] = $e->getMessage();  
      return $response;
     }
    }
    
     public function getUsage($api_key)
    {
     $stmt = $this->db->prepare("SELECT COUNT(id) FROM app_usage WHERE api_key = :api_key");
     $stmt->execute(array(":api_key"=>$api_key));
     $counts = $stmt->fetchColumn(); 
     if(empty($counts) || $counts <= 0){
         return 0;
     }else{
          return $counts;
     }
    }
    
     public function getTotalUsage($startDate, $endDate)
    {
     $sql = "SELECT COUNT(id) FROM app_usage";
     if(!empty($startDate) && !empty($endDate)){
        $sql .= " WHERE timestamp >= '".$startDate."' AND timestamp <= '".$endDate."'";
     }
     
     $stmt = $this->db->prepare($sql);
     $stmt->execute();
     $counts = $stmt->fetchColumn(); 
     if(empty($counts) || $counts <= 0){
         return 0;
     }else{
          return $counts;
     }
    }
    
     public function getUniqueUsage($startDate, $endDate)
    {
     $sql = "SELECT COUNT(DISTINCT api_key) FROM app_usage";
     if(!empty($startDate) && !empty($endDate)){
        $sql .= " WHERE timestamp >= '".$startDate."' AND timestamp <= '".$endDate."'";
     }
     
     $stmt = $this->db->prepare($sql);
     $stmt->execute();
     $counts = $stmt->fetchColumn(); 
     if(empty($counts) || $counts <= 0){
         return 0;
     }else{
          return $counts;
     }
    }

    public function getAllUsage()
    {
     $stmt = $this->db->prepare("SELECT * FROM app_usage ORDER BY timestamp DESC LIMIT 1000");
     $stmt->execute();
     $editRow=$stmt->fetchAll();
     return $editRow;
    }

    public function registerUser($registrationData)
    {
        require_once ("dbmodels/user.crud.php");
        require_once 'dbmodels/PassHash.php';
        require_once 'dbmodels/utils.crud.php';
        require_once ("dbmodels/referral.crud.php");
        require_once ("dbmodels/notification.crud.php");
        require_once ("dbmodels/activity.crud.php");
        require_once ("dbmodels/user_membership.crud.php");
        require_once ("dbmodels/reward_points.crud.php");
        $helper = new Helper();
        $membershipCRUD = new MembershipCRUD(getConnection());
        $referralCRUD = new ReferralCRUD(getConnection());
        $notiCRUD = new NotificationCRUD(getConnection());
        $activityCRUD = new ActivityCRUD(getConnection());
        $referralCRUD = new ReferralCRUD(getConnection());
        $rewardPointCRUD = new RewardPointCRUD(getConnection());
        $utilCRUD = new UtilCRUD(getConnection());
        $userCRUD = new UserCRUD(getConnection());
        $output = array();
        $output["note"] = "";
        // reading post parameters
        $adminMode = false;
        $first_name = "";
        $last_name = "";
        $email = "";
        $dob = "";
        $country = "";
        $referral_code = "";
        $ref_user_id = 0;
        $type = "Customer";
        $source = "";
        if (null !== $request->getParam('first_name')) {
            $first_name = $request->getParam('first_name');
        }
        if (null !== $request->getParam('last_name')) {
            $last_name = $request->getParam('last_name');
        }
        if (null !== $request->getParam('email')) {
            $email = $request->getParam('email');
        }
        if (null !== $request->getParam('dob')) {
            $dob = $request->getParam('dob');
        }
        if (null !== $request->getParam('country')) {
            $country = $request->getParam('country');
        }
        if (null !== $request->getParam('referral_code')) {
            $referral_code = $request->getParam('referral_code');
        }
    
        if (!empty($referral_code)) {
            $ref_user_id = $referralCRUD->getUserID($referral_code);
        }
        if (null !== $request->getParam('type')) {
            $type = $request->getParam('type');
        }
        if (null !== $request->getParam('adminMode')) {
            $adminMode = true;
        }

        if (empty($first_name)) {
            $output["error"] = true;
            $output["message"] = "First name can not be empty.";
            echoRespnse(200, $output);
            exit;
        }

        if (empty($last_name)) {
            $output["error"] = true;
            $output["message"] = "Please enter a last name.";
            echoRespnse(200, $output);
            exit;
        }

        if (empty($email)) {
            $output["error"] = true;
            $output["message"] = "Let us know your e-mail address.";
            echoRespnse(200, $output);
            exit;
        }

        if (empty($dob)) {
            $output["error"] = true;
            $output["message"] = "Let us know your birth date.";
            echoRespnse(200, $output);
            exit;
        }
        if(!isDateFormatValid($dob)){
            $output["error"] = true;
            $output["dob"] = $dob;
            $output["test1"] = isDateTimeFormatValid($dob);
            $output["test2"] = isDateFormatValid($dob);
            $output["message"] = "Please select valid date of birth.";
            echoRespnse(200, $output);
            exit;
        }

        if (empty($country)) {
            $output["error"] = true;
            $output["message"] = "Select the country of your residence.";
            echoRespnse(200, $output);
            exit;
        }
        if (null !== $request->getParam('source')) {
            $source = $request->getParam('source');
        }
        if (null !== $request->getParam('autogen_pass') && $request->getParam('autogen_pass') == true) {
            $password = $utilCRUD->createNewUsername(8);
        } else {
            $password = "";
            $confirmPassword = "";
            $password = $request->getParam('password');
            $confirmPassword = $request->getParam('confirmPassword');
            if (empty($password)) {
                $output['error'] = true;
                $output['message'] = 'Please set a password for this account.';
                echoRespnse(200, $output);
                return;
            }
            if (strlen($password) < 6) {
                $output['error'] = true;
                $output['message'] = 'Your password must be at least 6 characters. Please enter a strong password.';
                echoRespnse(200, $output);
                return;
            }
            if (empty($confirmPassword)) {
                $output['error'] = true;
                $output['message'] = 'Please repeat your password.';
                echoRespnse(200, $output);
                return;
            }
            if (strlen($confirmPassword) < 6) {
                $output['error'] = true;
                $output['message'] = 'Your password must be at least 6 characters. Please review the repeated password.';
                echoRespnse(200, $output);
                return;
            }
            if ($password !== $confirmPassword) {
                $output['error'] = true;
                $output['message'] = 'Your password did not match.';
                echoRespnse(200, $output);
                return;
            }
        }
        $phone = "";
        $status = "Pending";
        $date_created = date('Y-m-d H:i:s');
        $role_id = 2;
        $description = "";
        $password_hash = PassHash::hash($password);
        $api_key = $utilCRUD->generateApiKey();
        $user_name = $utilCRUD->createNewUsername(8);
        $res = $userCRUD->register($first_name, $last_name, $user_name, $type, $phone, $email, $password_hash, $dob, $country, $description, $date_created, $status, $role_id, $ref_user_id, $referral_code, $api_key);
        if ($res["code"] == INSERT_SUCCESS) {
            $output["error"] = false;
            if($adminMode){
                $output["message"] = "New account for ".$first_name." ".$last_name." has been created successfully.";
            }else{
                $output["message"] = "Your new account has been created successfully.";
            }
            if(!empty($source)){
                $userCRUD->updateRegSource($source);
            }
            $user_id = $res["id"];
            $output["id"] = $user_id;
            $output["user_name"] = $user_name;

            /********* FOR WEB ***********/
            $userData = getUserBasicDetails($user_id);
            $output['userData'] = $userData;
            /********* Notify now and send email ********/
            try {
                $title = 'Welcome to BaziChic';
                $message = 'Hi ' . $first_name . '! Thanks for registering your account with BaziChic.';
                $noti_res = $notiCRUD->create(1, $user_id, $title, $message, $user_name, $data_title = "WelcomeSelf", $status = "Pending", $date_created);
                if ($noti_res["code"] == INSERT_SUCCESS) {
                    $output["note"] .= " Notified successfully.";
                }

                /********* EMAIL NOTIFICATION **********/
                if (null !== $request->getParam('notify_account')) {
                    $subject = "Welcome to BaziChic";
                    $body = '<h4>Welcome  ' . $first_name . '!</h4>';
                    if (null !== $request->getParam('moderator')) {
                        $body .= '<h4>A new account has been registered for you at BaziChic.</h4>';
                    } else {
                        $body .= '<h4>Your new BaziChic account has been created successfully. Thanks for registering an account with us.</h4>';
                    }
                    $body .= '<h4>Use the autogenerated password - ' . $password . ' to access your account. Please update your password to keep the account safe once you verify your registered e-mail. </h4>';
                    $body .= '<br>';
                    $body .= getEmailFooter();
                    $emailResult = $helper->sendEmail($email, $subject, $body);
                    if (!$emailResult["error"]) {
                        $output["message"] .= " We have sent further instructions to your registered email address.";
                    } else {
                        //$output["message"] = "";
                    }
                }
                /********* EMAIL SENT **********/

            } catch (Exception $e) {
                $output["note"] .= " Error notifying." . $e->getMessage();
            }
            /********* Notify Done ********/

            /**** Log Activity ********/
            try {
                $title = "New Registration - " . $first_name . " " . $last_name;
                $activity = $first_name . " " . $last_name . " from " . $country . " registered an account.";
                $activity_res = $activityCRUD->create(1, $title, $activity, $user_name, $data_title = "Registration", 0, $date_created);
                if ($activity_res["code"] == INSERT_SUCCESS) {
                    $output["note"] .= " Logged new accunt activity.";
                }} catch (Exception $e) {
                $output["note"] .= "Error logging activity. " . $e->getMessage();
            }
            /**** Log Activity ********/

            /******* Send OTP *******/
            try{
                $newOtp = $utilCRUD->generateNewOTP();	
                $emailToName = $first_name." ".$last_name;
                notifyUserWithVerificationCode($email, $emailToName, $newOtp, 0);
            }catch(Exception $e)
            {
            $output["verifier"] = "Error sending verification e-mail: " . $e->getMessage();
            }
            /**************/

            /********** PROCESS REFERRAL ***********/
            try {
                if (is_numeric($ref_user_id) && $ref_user_id > 0) {
                    if ($referralCRUD->isReferralCodeExist($referral_code)) {
                        //Update Referral
                        $ref_user_id = $referralCRUD->getUserID($referral_code);
                        $date_updated = date('Y-m-d H:i:s');
                        $referralCRUD->updateReferral($referral_code, $status = "Used", $date_updated);
                        $userCRUD->updateFather($user_id, $ref_user_id);
                        $referredUser = $userCRUD->getNameByID($user_id);
                        $referringUser = $userCRUD->getNameByID($ref_user_id);
                        $output['message'] .= ' The Referral Code has been applied.';
                        $output["note"] .= "Referrals Updated.";
                        $ref_message = "Your referral code " . $referral_code . " from " . $referringUser . " has been applied successfully.";
                        //Notify New User
                        if (!empty($referredUser)) {
                            $notiCRUD->create($ref_user_id, $user_id, "Referral Code Applied", $ref_message, $user_id, $data_title = "ReferralApplied", $status = "Pending", $date_created);
                            $output["note"] .= " Referred user Notified. ";
                        }
                        //Notify Referrering User
                        if (!empty($referringUser)) {
                            $con_message = "Congrats! You have a new connection. " . $referredUser . " just used your referral code to register an account.";
                            $notiCRUD->create($user_id, $ref_user_id, "New Connection", $con_message, $user_id, $data_title = "ReferralApplied", $status = "Pending", $date_created);
                            $output["note"] .= " Referring User notified.";
                        }
                        //Process Reward Points
                        $note = $referredUser . " registered a new account using code " . $referral_code . ".";
                        $rewarding_res = $rewardPointCRUD->create($ref_user_id, $points = 5, "Refferal Bonus", $note, "Complete", $date_created);
                        if ($rewarding_res["code"] == INSERT_SUCCESS) {
                            $output["note"] .= " Reward point processed successfully.";
                        } else {
                            $output["note"] .= " Failed to process Reward point. " . $rewarding_res["msg"];
                        }
                    } else {
                        //$output['error'] = true;
                        $output['message'] .= 'The Referral Code you applied was invalid.';
                        $output["note"] .= "Invalid Referral Code.";
                    }
                }
            } catch (Exception $e) {
                $output["note"] .= " ERROR DURING REFERRAL PROCESS: " . $e->getMessage();
            }
            /********** REFERRAL PROCESSED ***********/

        } else if ($res["code"] == INSERT_FAILURE) {
            $output["error"] = true;
            $output["message"] = "Oops! An error occurred while registering user";
        } else if ($res["code"] == ALREADY_EXIST) {
            $output["error"] = true;
            $output["message"] = "The email is already registered.";
        }
    }
}
