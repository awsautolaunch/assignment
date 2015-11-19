<?php
// src/AppBundle/Controller/EndpointController.php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\App\NameGenerator;
use AppBundle\App\Transporter;
use AppBundle\App\ServerGenerator;


class EndpointController extends Controller
{
    private $db_host = '/tmp';
    private $db_name = 'site';
    private $db_user = 'postgres';

    /**
     * @Route("/teardown")
    */
    function tearDown() {
        $server_generator = new ServerGenerator();
        $result = $server_generator->tearDown();
        if ($result == false){
            return $this->sendResponse("message", "Required params are missing", 400);
        }
        return $this->sendResponse("errors",$result, 201);
    }

    /**
     * @Route("/takedown")
    */
    function takeDown() {
        $server_generator = new ServerGenerator();
        $result = $server_generator->takeDown();
        if ($result === false) {
            return $this->sendResponse("status","Failed to take down a node.", 503);
        }

        return $this->sendResponse("status", $result, 201);
    }

    /**
     * @Route("/test")
    */
    function testAction() {
        $response_array = $this->initializeResponse();
        $status = "503";
        $generator = new NameGenerator($this->db_host, $this->db_name, $this->db_user);
        $result = $generator->GenerateAndSave('users');
        if ($result){
            $response_array['success'] = true;
            $status = 200;
        }

        return new JsonResponse($response_array, $status);
    }

    /**
     * @Route("/transfer")
    */
    function populateCache() {
        $response_array = $this->initializeResponse('success');
        $status = "503";
        $transporter = new Transporter();
        $result = $transporter->moveData($this->db_host, $this->db_name, $this->db_user, 'users', 'id');
        if ($result){
            $response_array['success'] = true;
            $response_array['count'] = $this->countRedis();
            $status = 200;
        }

        return new JsonResponse($response_array, $status);
    }

    /**
     * @Route("/count")
    */
    function countRedis() {
        $response_array = $this->initializeResponse('count');
        $status = "503";
        $transporter = new Transporter();
        $count = $transporter->getKeyCount('user:*');
        if ($count !== false){
            $response_array['count'] = $count;
            $status = 200;
        }

        return new JsonResponse($response_array, $status);
    }

    /**
     * @Route("/setup")
    */
    function bootUpServer(Request $request) {

        $params = array();
        $content = $this->get("request")->getContent();
        if (!empty($content)) {   
            $params = json_decode($content, true); // 2nd param to get as array
        } else {
            return $this->sendResponse("message", "Required params are missing", 400);
        }

        $server_generator = new ServerGenerator();
        $result = $server_generator->checkInputSanity($params);
        if ($result == false){
            return $this->sendResponse("message", "Required params are missing", 400);
        }

        $result = $server_generator->bootUpServers($params);
        if ($result === false) {
            return $this->sendResponse("message", "Failed to bringup servers", 503);
        }
        return $this->sendResponse("errors", $result, 201);
    }

    private function sendResponse ($key="success", $value="false", $status=503) {
        $response_array = $this->initializeResponse($key, $value);
        return new JsonResponse($response_array, $status);
    }

    /**
     * @Route("/hungry")
    */
    function healthCheck() {
        return new Response('Feed me');
    }

    private function initializeResponse($key="success", $value=false) {
        $response_array = array();
        $response_array[$key] = $value;
        return $response_array;
    }

    private function logIt($message) {
        error_log(print_r($message, true) . "\n");
    }
}
?>
