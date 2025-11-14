<?php
class Database {
    private $host = "dpg-d4bgthf5r7bs7395rudg-a.oregon-postgres.render.com";
    private $db_name = "imessage";
    private $username = "imessage_user";
    private $password = "utIroiPuDNpGaeNHNfeBJKend43xFXja";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            // REMOVE THIS LINE - PostgreSQL doesn't use set names utf8
            // $this->conn->exec("set names utf8");
            
            // Set error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
