<?php

namespace AppBundle\App;

Class NameGenerator {

    private $host;
    private $db;
    private $db_user;

    public function __construct($host, $db, $db_user) {
        $this->host = $host;
        $this->db = $db;
        $this->db_user = $db_user;
    }

    private function generateNameList() {
        $rand_string = str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $first_name = substr($rand_string,0,8);
        $last_name = substr($rand_string,8,9);
        return array($first_name, $last_name);
    }

    private function getAge() {
        return rand(18,99);
    }

    private function dbInsert($table, $row) {
        $conn = pg_pconnect("host=" . $this->host . " dbname=" . $this->db . " user=" . $this->db_user);
        $res =  pg_insert($conn, $table, $row);
        pg_close($conn);
        return $res;
    }


    public function GenerateAndSave($table){
        list($first_name, $last_name) = $this->generateNameList();
        $age = $this->getAge();
        return $this->SaveToDB($first_name, $last_name, $age, $table);
    }

    private function SaveToDB($first_name, $last_name, $age, $table) {

        $row = array();
        $row['firstname'] = $first_name;
        $row['lastname'] = $last_name;
        $row['age'] = $age;


        $res = $this->dbInsert('users', $row);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
}
