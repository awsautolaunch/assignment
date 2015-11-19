<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appProdUrlMatcher.
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appProdUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);
        $context = $this->context;
        $request = $this->request;

        // homepage
        if (rtrim($pathinfo, '/') === '') {
            if (substr($pathinfo, -1) !== '/') {
                return $this->redirect($pathinfo.'/', 'homepage');
            }

            return array (  '_controller' => 'AppBundle\\Controller\\DefaultController::indexAction',  '_route' => 'homepage',);
        }

        if (0 === strpos($pathinfo, '/t')) {
            // app_endpoint_teardown
            if ($pathinfo === '/teardown') {
                return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::tearDown',  '_route' => 'app_endpoint_teardown',);
            }

            // app_endpoint_takedown
            if ($pathinfo === '/takedown') {
                return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::takeDown',  '_route' => 'app_endpoint_takedown',);
            }

            // app_endpoint_test
            if ($pathinfo === '/test') {
                return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::testAction',  '_route' => 'app_endpoint_test',);
            }

            // app_endpoint_populatecache
            if ($pathinfo === '/transfer') {
                return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::populateCache',  '_route' => 'app_endpoint_populatecache',);
            }

        }

        // app_endpoint_countredis
        if ($pathinfo === '/count') {
            return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::countRedis',  '_route' => 'app_endpoint_countredis',);
        }

        // app_endpoint_bootupserver
        if ($pathinfo === '/setup') {
            return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::bootUpServer',  '_route' => 'app_endpoint_bootupserver',);
        }

        // app_endpoint_healthcheck
        if ($pathinfo === '/hungry') {
            return array (  '_controller' => 'AppBundle\\Controller\\EndpointController::healthCheck',  '_route' => 'app_endpoint_healthcheck',);
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
