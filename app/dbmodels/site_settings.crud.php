<?php
require_once("Constants.php");
class SiteSettingsCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function update($id, $name, $link, $pay_key, $pay_secret, $address, $latitude, $longitude, $enable_member_uploads, $maintenance_on, $email, $facebook_link, $twitter_link)
 {
  try
  {
   $response = array();	
   $response["error"] = true;         
   $stmt=$this->db->prepare("UPDATE site_settings SET name=:name, 
   phone=:phone,
   email=:email,
   pay_key =:pay_key,
   pay_secret =:pay_secret,
                enable_member_uploads=:enable_member_uploads,
				maintenance_on=:maintenance_on,
				address=:address,
				latitude=:latitude,
				longitude=:longitude,
				facebook_link=:facebook_link,
				twitter_link=:twitter_link 
                WHERE id=:id");
   
   $stmt->bindparam(":name",$name);
   $stmt->bindparam(":phone",$phone);
   $stmt->bindparam(":email",$email);
   $stmt->bindparam(":pay_key",$pay_key);
   $stmt->bindparam(":pay_secret",$pay_secret);
   $stmt->bindparam(":enable_member_uploads",$enable_member_uploads);
   $stmt->bindparam(":maintenance_on",$maintenance_on);
   $stmt->bindparam(":address",$address);
   $stmt->bindparam(":latitude",$latitude);
   $stmt->bindparam(":longitude",$longitude);
  
   $stmt->bindparam(":facebook_link",$facebook_link);
   $stmt->bindparam(":twitter_link",$twitter_link);
   $stmt->bindparam(":id",$id);
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
   $response["msg"] =  $e->getMessage(); 
   return $response;
  }
 }
 
 
 
 public function updateBannerLink($banner_link)
 {
 $response = array();
  $response["error"] = true; 
  $response["message"] = "";
  try
  {
   $stmt=$this->db->prepare("UPDATE site_settings SET banner_link=:banner_link WHERE id=1");
   $stmt->bindparam(":banner_link",$banner_link);
   $stmt->execute();
   $response["error"] = false;  
  }
  catch(PDOException $e)
  {
   $response["message"] = "Exception => ".$e->getMessage(); 
   $response["error"] = true;  
   $response["code"] = INSERT_FAILURE; 
   return false;
  }
 }
 
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM site_settings WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
 public function updateImage($id, $image)
 {	 
  $response = array();	
  $response["error"] = true; 
  $response["message"] = "No Image found.";
   /************* UPLOAD IMAGE **************/
	   try{
		   if(!empty($image)){
			$path = "images/services/".$id.".jpg";
		    $actualpath = $path;
			
			//file_put_contents("http://localhost/ARMORBEAREARSPORT/".$path, base64_decode($image));
			
			file_put_contents("../../".$path, base64_decode($image));
			
			$stmt2=$this->db->prepare("UPDATE site_settings SET logo=:logo
             WHERE id=:id");
            $stmt2->bindparam(":logo",$actualpath);
            $stmt2->bindparam(":id",$id);
            $res = $stmt2->execute();
			if($res){
				 $response["error"] = false; 
				 $response["message"] = "Image uploaded => ".$actualpath; 
			}
			}else{
				 $response["error"] = true; 
				 $response["message"] = "Upload a valid image."; 
			}
	   }catch (Exception $e) {
		    $response["error"] = true; 
		   $response["message"] = "Could not upload Image.".$e->getMessage();
	   }
	   /**************************************/
   return $response; 
 }
 
 
   public function isMaintenanceModeOn()
 {
  $id = 1;     
  $stmt = $this->db->prepare("SELECT maintenance_on FROM site_settings WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
    public function getFrontBannerLink()
 {
  $id = 1;     
  $stmt = $this->db->prepare("SELECT banner_link FROM site_settings WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 
 
}