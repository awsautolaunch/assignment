<?php

namespace AppBundle\App;

require __DIR__ . '/lib/Ec2Client.php';

Class ServerGenerator {

    private $ec2;
    private $auto_scaling;
    private $boot_status;

    public function __construct($region = "us-west-2", $version = "latest") {
        $this->ec2 = new  Ec2Client($region, $version);
    }

    public function tearDown() {
        return $this->ec2->disastrousFunction();
    }

    public function checkInputSanity  ($params) {
        $required_params = $this->minRequiredParams();
        foreach($params as $node_data) {
            $aws_params = array_keys($node_data['aws']);
            $expected_params = $required_params[$node_data['chef']['role_name']];
            if (count(array_diff($expected_params, $aws_params)) > 0) {
                return false;
            }
        }
        return true;
    }

    public function bootUpServers($params) {
        foreach($params as $node_data) {
            $role_name = $node_data['chef']['role_name'];
            $enabled = $node_data['common']['enabled'];
            if (!$enabled) {
                continue;
            }

            if ($node_data['common']['type'] == 'instance') {
                $this->boot_status[$role_name] = $this->bringUpInstances($role_name, $node_data['aws']);
            } elseif ($node_data['common']['type'] == 'array') {
                $this->logIt("Booting up servers " + implode($aws_info));
                $this->boot_status[$role_name] = $this->ec2->updateAutoScalingGroup($node_data['aws']);
            }
        }
        return $this->boot_status;
    }

    private function bringUpInstances($role_name, $aws_info) {
        $current_count = $this->ec2->getInstanceCount($role_name);
        if ($current_count === false) {
            return false;
        }
        $max_count = $aws_info['MaxCount'];
        if ($current_count < $max_count) {
            $aws_info['MaxCount'] = $max_count - $current_count;
        } else if ($current_count > $max_count) {
            return "Current server count is greater than MaxCount";
        } else {
            return "Pool already at MaxCount.";
        }

        if ($aws_info['MaxCount'] < $aws_info['MinCount']) {
            return "MinCount cannot be greater than MaxCount";
        }
        $this->logIt("Booting up servers " . implode("\n", $aws_info));
        $result =  $this->ec2->createInstance($aws_info);
        if ($result) {
            $this->logIt("Add to job queue");
        }
        return $result;
    }

    public function takeDown() {
        $instanceIDs = $this->ec2->getInstanceIDs('db_node');
        if ($instanceIDs == false || count($instanceIDs) == 0) {
            return false;
        }
        $unluckyNode = array($instanceIDs[array_rand($instanceIDs)]);
        return $this->ec2->terminateInstances(array('InstanceIds' => $unluckyNode));
    }

    private function logIt($message) {
        error_log(print_r($message, true));
    }

    private function respondWithStatus($key, $message, $status) {
        return array(
                "key" => $key,
                "message" => $message,
                "status" => $status
            );
    }

    private function minRequiredParams() {
        $common = array('IamInstanceProfile', 'ImageId', 'InstanceType', 'KeyName', 'MaxCount', 'MinCount', 'group_name', 'SecurityGroupIds');
        return array (
                    "db_node" => $common,
                    "redis_node" => $common,
                    "web_node" => array('AutoScalingGroupName', 'MinSize', 'MaxSize', 'LaunchConfigurationName')
                );
    }
}
