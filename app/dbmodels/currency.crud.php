<?php
require_once("Constants.php");
class CurrencyCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM currencies WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT currency FROM currencies WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function getSymbolByID($id)
 {
  $stmt = $this->db->prepare("SELECT symbol FROM currencies WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getAllCurrencies()
 {
  $stmt = $this->db->prepare("SELECT * FROM currencies");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM currencies WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
}