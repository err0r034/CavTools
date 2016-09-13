<?php

class CavTools_MeetingPreBake extends XenForo_ControllerPublic_Abstract {

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

        // Set Time Zone to UTC
        date_default_timezone_set("UTC");

        $model = $this->_getMilpacModel();
        $positions = $model->getAllMilpacPositions();

        // View Parameters
        $viewParams = array(
            'milpacPositions' => $positions
        );

        // Send to template to display
        return $this->responseView('CavTools_ViewPublic_CreateMeeting', 'CavTools_CreateMeeting', $viewParams);
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
        $this->datawriter($meetingTitle, $meetingText, $positions);

        // Redirect after post
        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('meetingtemplate'),
            new XenForo_Phrase('Meeting Pre-baked')
        );
    }

    public function datawriter($meetingTitle, $meetingText, $positions)
    {
        // Datawriter
        $dw = XenForo_DataWriter::create('CavTools_DataWriter_MeetingPreBake');
        $dw->set('meeting_title', $meetingTitle);
        $dw->set('meeting_text', $meetingText);
        $dw->set('positions', $positions);
        $dw->save();
    }

    protected function _getMilpacModel()
    {
        // Milpac model
        return $this->getModelFromCache( 'CavTools_Model_Milpac' );
    }
}
