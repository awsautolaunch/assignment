<?php

namespace AppBundle\App;

use Predis;

require 'lib/predis/autoload.php';

Class Transporter {

    private $host;
    private $db;
    private $db_user;
    private $redis_conn = null;
    private $redis_port = 6379;
    private $redis_ips_file;

    public function __construct() {
        $this->redis_ips_file = '/var/www/html/configs/redis.ips';
        $this->connectToRedis();
    }

    private function connectToRedis() {
        $redis_ips = $this->getRedisIPs();
	if (!is_array($redis_ips)) {
            $this->redis_conn = false;
            return false;
	}
        $conn_array = $this->generateRedis($redis_ips);
        $this->redis_conn = new Predis\Client($conn_array);
    }

    private function getRedisIPs () {
	if (file_exists($this->redis_ips_file)) {
            return json_decode(file_get_contents($this->redis_ips_file));
	} else {
            return false;
        }
    }

    private function generateRedis ($redis_ips) {
        $conn_array = array();
        $conn_array["scheme"] = 'tcp';
        $conn_array["port"] = $this->redis_port;
        $conn_array["host"] = $redis_ips[array_rand($redis_ips)];
        return $conn_array;
    }

    public function getKeyCount($pattern) {
        if (!$this->redis_conn) {       
            return false;
        }
        return $this->redis_conn->eval("return #redis.call('keys', 'user:*')", 0);
    }

    public function moveData($db_host, $db_name, $db_user, $user_table, $id_field){
        $query = $this->queryDb($db_host, $db_name, $db_user, $user_table);
	if (!$query) {
            return false;
        }

        if (!$this->redis_conn) {       
            return false;
        }

        while ($row = pg_fetch_assoc($result)) {
            $user_id = $this->extractUserId($row);
            $res = $this->redis->hMset($user_id, $row);

            if(!$res) {
                return false;
            }
        }

        return true;
    }   

    private function extractUserId (&$row, $id_field){
            $user_id = 'user:' . $row[$id_field];
            unset($row[$id_field]);
            return $user_id;
    }

    private function queryDb($db_host, $db_name, $db_user, $user_table) {
        $conn = pg_pconnect("host=$db_host dbname=$db_name user=$db_user");
        return pg_query($conn, "SELECT * FROM $user_table");
    } 
}
