<?php
    class Connection {
        private $host = 'localhost';
        private $dbname = 'crime_mapping';
        private $username = 'root';
        private $password = '';
        private $charset = 'utf8';
        private $conn;

        private $dsn;
        private $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        public function __construct() {
            $this->dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$this->charset";
            try {
                $this->conn = new PDO($this->dsn, $this->username, $this->password, $this->options);
            } catch(PDOException $e) {
                echo $e->getMessage();
            } 
        }

        public function getConnection() {
            return $this->conn;
        }
    }
?>
