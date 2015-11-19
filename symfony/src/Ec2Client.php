<?php

namespace AppBundle\App;

use Aws;

require __DIR__ . '/AWS/aws-autoloader.php';

Class Ec2Client {

    private $ec2;
    private $auto_scaling;

    public function __construct($region, $version) {
        $this->ec2 = new  Aws\Ec2\Ec2Client([
            'version' => $version,
            'region'  => $region
        ]);

        $this->auto_scaling = new  Aws\AutoScaling\AutoScalingClient([
            'version' => $version,
            'region'  => $region
        ]);
    }

    private function logIt($message) {
        error_log(print_r($message, true));
    }

    private function formatParams (&$params) {
        $params['DryRun'] = ($params['DryRun'] == 1 ? true : false); 
    }

    public function terminateInstances($params) {
        return $this->ec2->terminateInstances($params);
    }

    public function disastrousFunction() {
        $result = $this->describeInstances();
        $reservations = $result->toArray();
        $instanceIDs = $this->getInstancesDetails($reservations['Reservations']);
        return $this->terminateInstances(array('InstanceIds' => $instanceIDs));
    }

    private function getInstancesDetails($reservations) {
        $instanceIDs = array();
        foreach ($reservations as $reservation) {
            $instances = $reservation['Instances'];
            foreach ($instances as $instance) {

/*
                echo 'Instance Name: ' . $instanceName . PHP_EOL;
                echo '---> State: ' . $instance['State']['Name'] . PHP_EOL;
                echo '---> Instance ID: ' . $instance['InstanceId'] . PHP_EOL;
                echo '---> Image ID: ' . $instance['ImageId'] . PHP_EOL;
                echo '---> Private Dns Name: ' . $instance['PrivateDnsName'] . PHP_EOL;
                echo '---> Instance Type: ' . $instance['InstanceType'] . PHP_EOL;
                echo '---> Security Group: ' . $instance['SecurityGroups'][0]['GroupName'] . PHP_EOL;
*/
                $instanceIDs[] = $instance['InstanceId'];
            }

        }
        return $instanceIDs;;
    }

    public function createInstance($params) {
        $this->formatParams($params);
        return $this->ec2->runInstances($params);
    }

    public function updateAutoScalingGroup($params) {
        $this->formatParams($params);
        return $this->auto_scaling->updateAutoScalingGroup($params);
    }

    private function make_call($client, $func_name, $filter_name, $filter = null) {

        $filters = $this->getFilters($filter_name, $filter);
        if ($filters === false) {
            return false;
        }
        return call_user_func(array($this->$client, $func_name), $filters);
    }

    public function describeAutoScalingGroups($filter = null) {
        return $this->make_call('auto_scaling', 'describeAutoScalingGroups', 'AutoScalingGroupNames');
    }

    public function describeInstances($filter = null) {
        return $this->make_call('ec2', 'describeInstances', 'Filters', $filter);
    }

    public function getInstanceIDs($role_name) {
        $filter = array(
            array(
                'Name' => 'tag:group_name',
                'Values' => array($role_name)
            ),
            array(
                'Name' => 'instance-state-code',
                'Values' => array(16)
            )
        );
        $result = $this->describeInstances($filter);
        if ($result === false) {
            return false;
        }

        $reservations = $result->toArray();
        $instanceIDs = $this->getInstancesDetails($reservations['Reservations']);
        return $instanceIDs;
    }

    public function getInstanceCount($role_name) {
        $instanceIds = $this->getInstanceIDs($role_name);
        if ($instanceIds === false) {
            return false;
        }
        return count($instanceIds);
    }

    private function getFilters($filter_name, $filter) {
        $filters = array();
        if ($filter != null) {
            if (is_array($filter)) {
                $filters[$filter_name] = $filter;        
            } else {
                return false;
            }
        } 
        return $filters;
    }
}
