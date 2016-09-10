<?php

class CavTools_ControllerPublic_MeetingGenerator extends XenForo_ControllerPublic_Abstract
{
    // TODO:
    // - Generate meetings
    // - Store meetings
    // - Present meetings
    // - take attendance with bot (https://docs.planetteamspeak.com/ts3/php/framework/index.html)
    // - Site route
    // - Site options
    // - Site perms

    /**
    * GET IMO
    * BOT VALUES
    *
    * @returns array of userID => INT, username => STRING
    **/

    public function getIMOBot()
    {
        //Get values from options
        $userID = XenForo_Application::get('options')->botID;
        $model = _getImoBotModel();
        $bot = $model->getBot($userID);
        return $bot;
    }

    /**
    * Init view
    * @throws exception if not enabled
    * @throws exception if no perms
    *
    * @gets departments from XenForo options
    *
    * @returns object containing viewParams to view
    **/

    public function actionIndex() {

        //Get values from options
        $enable = XenForo_Application::get('options')->enableMeetingCreation;

        if(!$enable) {
            throw $this->getNoPermissionResponseException();
        }

        if (!XenForo_Visitor::getInstance()->hasPermission('CavToolsGroupId', 'CreateMeeting'))
        {
            throw $this->getNoPermissionResponseException();
        }

        //Set Time Zone to UTC
        date_default_timezone_set("UTC");

        //Get values from options
        $departments = XenForo_Application::get('options')->departments;

        $departments = explode(',', $departments);

        //View Parameters
        $viewParams = array(
            'departments' => $departments
        );

        //Send to template to display
        return $this->responseView('CavTools_ViewPublic_CreateMeeting', 'CavTools_CreateMeeting', $viewParams);
    }

    public function actionPost()
    {
        // The user data from the visitor
        $visitor  = XenForo_Visitor::getInstance()->toArray();

        // Form values
        $department = $this->_input->filterSingle('department', XenForo_Input::STRING);
        // TODO
        // Add meeting topic to the database
        // +
        // html form
        $meetingTopic = $this->_input->filterSingle('meeting_topc', XenForo_Input::STRING);
        $meetingText = $this->_input->filterSingle('meeting_text', XenForo_Input::STRING);
        $date = $this->_input->filterSingle('date', XenForo_Input::STRING);
        $time = $this->_input->filterSingle('time', XenForo_Input::STRING);
        $attendees = $this->_input->filterSingle('attendees', XenForo_Input::STRING);

        $meetingText = htmlspecialchars($meetingText);
        $time = htmlspecialchars($time);
        $date = htmlspecialchars($date);
        $attendees = $this->_input->filterSingle($attendees);

        $convertDate = new DateTime("$date");
        $date = $convertDate->format('U');

        $convertTime = new DateTime("$time");
        $time = $convertTime->format('U');

        // Get Forum ID
        $forumID = XenForo_Application::get('options')->meetingForumID;

        // make thread title + content
        $title = createTitle($date, $department, $topic);
        $content = $createContent(FIX ME);




    }

    public function createThread()
    {
        // get bot values

        // write the thread
        $writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
        $writer->set('user_id', $poster['user_id']);
        $writer->set('username', $poster['username']);
        $writer->set('title', $title);
        $postWriter = $writer->getFirstMessageDw();
        $postWriter->set('message', $message);
        $writer->set('node_id', $forumID);
        $writer->set('sticky', true);
        $writer->preSave();
        $writer->save();
        return $writer->getDiscussionId();
    }

    public function saveData()
    {
        $dw = XenForo_DataWriter::create('CavTools_DataWriter_Meetings');
        // Do data values
        $dw->save();
    }

    public function createTitle($date, $department, $topic)
    {
        // Meeting: Department | Topic | 01JAN16
        $formatedDate = date('dMy', $date); // 13Jun2016
        $title = $deparment . " | " . $topic . " | " . $formatedDate;
        return $title;
    }

    public function createContent()
    {
        // Get Meeting model
        $model = _getMeetingModel();
        $newline = "/n";

        $formatedDate = date('l the jS of J', $date); // Monday the 2nd of September
        $rank = $model->getRank($visitor['user_id']);

        $header = $department . " meeting schedualed for " . $formatedDate . $newline;
        $header .= $newline . "Organised by: " . $rank . " " . $visitor['username'] . $newline . $newline;

        // TODO:
        // Create content for attendance -:
        // -------
        // RNK.Last.F - Waiting
        // -------
        // RNK.Last.F - Present
        // -------
        // RNK.Last.F - Approved Absence
        // -------
        // RNK.Last.F - Absent

        // Perhaps use sepperate function

        $attendance = "";

        $main = $meetingText;

        // Return the content
        return $header . $attendance . $main;
    }

    protected function _getImoBotModel()
    {
        return $this->getModelFromCache ( 'CavTools_Model_IMOBot' );
    }

    protected function _getMeetingModel()
    {
        return $this->getModelFromCache ( 'CavTools_Model_Meetings' );
    }
}
