<?php

class CavTools_ControllerPublic_MeetingPreBake extends XenForo_ControllerPublic_Abstract {

    public function actionIndex()
    {
        // Get values from options
        $enable = XenForo_Application::get('options')->enableMeetingCreation;

        if(!$enable) {
            throw $this->getNoPermissionResponseException();
        }

        if (!XenForo_Visitor::getInstance()->hasPermission('CavToolsGroupId', 'createMeeting'))
        {
            throw $this->getNoPermissionResponseException();
        }

        // Models
        $meetingModel = $this->_getMeetingTemplateModel();
        $milpacModel = $this->_getMilpacModel();

        // Prepare templates
        $templates = $meetingModel->getAllMeetingTemplates();
        $memberURL = '/members/';
        for ($i=0;$i<count($templates);$i++) {
            $user = $milpacModel->getUser($templates[$i]['creator']);
            $member = $memberURL . $templates[$i]['creator'];
            $templates[$i]['username'] = "<a href=" . $member . "><b>" . $user['username'];
            $templates[$i]['positions'] = unserialize($templates[$i]['positions']);
            print_r($templates[$i]['positions']);
            $reqPositions = "";
            foreach ($templates[$i]['positions'] as $posID)
            {
                $title = $meetingModel->getTitleFromID($posID);
                $reqPositions .= $title['position_title'] . ",\n";
            }
            $templates[$i]['milpacs'] = $reqPositions;
        }

        // Set Time Zone to UTC
        date_default_timezone_set("UTC");

        // Prepare positions
        $positions = $milpacModel->getAllMilpacPositions();

        // View Parameters
        $viewParams = array(
            'defaultMessage' => "",
            'templates' => $templates,
            'positions' => $positions
        );

        // Send to template to display
        return $this->responseView('CavTools_ViewPublic_MeetingPreBake', 'CavTools_CreateMeetingTemplate', $viewParams);
    }

    public function actionPost()
    {
        // The user data from the visitor
        $visitor  = XenForo_Visitor::getInstance()->toArray();

        // Form values
        $meetingTitle = $this->_input->filterSingle('meeting_title', XenForo_Input::STRING);
        $meetingText = $this->_input->filterSingle('meeting_text', XenForo_Input::STRING);
        $positions = $_POST['positions'];

        // Prepare array for storage
        $positions = serialize($positions);

        // Write data
        $this->datawriter($meetingTitle, $meetingText, $positions, $visitor);

        // Redirect after post
        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('meetingtemplate'),
            new XenForo_Phrase('Meeting Pre-baked')
        );
    }

    public function datawriter($meetingTitle, $meetingText, $positions, $visitor)
    {
        // Datawriter
        $dw = XenForo_DataWriter::create('CavTools_DataWriter_MeetingPreBake');
        $dw->set('meeting_title', $meetingTitle);
        $dw->set('meeting_text', $meetingText);
        $dw->set('positions', $positions);
        $dw->set('creator', $visitor['user_id']);
        $dw->save();
    }

    protected function _getMilpacModel()
    {
        // Milpac model
        return $this->getModelFromCache( 'CavTools_Model_Milpac' );
    }

    protected function _getMeetingTemplateModel()
    {
        // Meeting model
        return $this->getModelFromCache( 'CavTools_Model_MeetingTemplate' );
    }
}
