<?php

class CavTools_Route_Prefix_MeetingTracker implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('CavTools_ControllerPublic_MeetingTracker', $routePath, 'forums');
    }
}
