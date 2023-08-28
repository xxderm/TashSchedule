<?php

class Database {
	private $DBNAME = "myDb";
	private $HOST = "db";
	private $USERNAME = "local";
	private $PASSWORD = "123";
	
    private static $instance = null;
    private $connection;
	
	private function __construct() {
		$conStr = "mysql:host=" . $this->HOST . ";dbname=" . $this->DBNAME;
		try {
			$this->connection = new PDO($conStr, 
										$this->USERNAME,
										$this->PASSWORD);
			$this->connection->setAttribute(
								PDO::ATTR_ERRMODE,
								PDO::ERRMODE_EXCEPTION);								
		}
		catch (PDOException $e) {
			echo "Connection exc: " . $e->getMessage();
		}
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function query(&$sql, $params = []) {
		$stmt = $this->connection->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}
}
