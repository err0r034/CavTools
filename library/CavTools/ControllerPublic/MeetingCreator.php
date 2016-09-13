<?php

class CavTools_ControllerPublic_MeetingCreator extends XenForo_ControllerPublic_Abstract
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
		// Get values from options
		$model = $this->_getImoBotModel();
		$bot = $model->getBot();
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

		if (!XenForo_Visitor::getInstance()->hasPermission('CavToolsGroupId', 'createMeeting'))
		{
			throw $this->getNoPermissionResponseException();
		}

		//Set Time Zone to UTC
		date_default_timezone_set("UTC");

		//Get values from options
		$departments = XenForo_Application::get('options')->departments;
		$departments = explode(',', $departments);
        $timeOptions = $this->createTimeOptions();

		//View Parameters
		$viewParams = array(
		    'timeOptions' => $timeOptions,
			'departments' => $departments
		);

		//Send to template to display
		return $this->responseView('CavTools_ViewPublic_CreateMeeting', 'CavTools_CreateMeeting', $viewParams);
	}

    // Get time options 0000 - 2300
    public function createTimeOptions()
    {
        $timeOptions = array();
        for ($i=0;$i<24;$i++)
        {
            if ($i < 10) {
                // 06:00
                $timeValue ="0" . $i . "00";
            } else {
                // 14:00
                $timeValue = $i . "00";
            }
            // 00:00, 01:00
            array_push($timeOptions, $timeValue);
        }
        return $timeOptions;
    }

	public function actionPost()
	{
		// The user data from the visitor
		$visitor  = XenForo_Visitor::getInstance()->toArray();

		// Form values
		$meetingTopic = $this->_input->filterSingle('meeting_topic', XenForo_Input::STRING);
        $department = $this->_input->filterSingle('department', XenForo_Input::STRING);
		$meetingText = $this->_input->filterSingle('meeting_text', XenForo_Input::STRING);
		$date = $this->_input->filterSingle('date', XenForo_Input::STRING);
		$time = $this->_input->filterSingle('time', XenForo_Input::STRING);
		$attendees = $this->_input->filterSingle('attendees', XenForo_Input::STRING);

		$meetingText = htmlspecialchars($meetingText);
		$time = htmlspecialchars($time);
		$date = htmlspecialchars($date);
		$attendees = htmlspecialchars($attendees);
		$attendees = str_replace(' ', '', $attendees);
		$attendees = rtrim($attendees, ",");

		$convertDate = new DateTime("$date");
		$date = $convertDate->format('U');

		$convertTime = new DateTime("$time");
		$time = $convertTime->format('U');

		// Get Forum ID
		$forumID = XenForo_Application::get('options')->meetingForumID;

		// make thread title + content
		$title = $this->createTitle($date, $department, $meetingTopic);
		$content = $this->createContent($date, $department, $visitor, $attendees, $meetingText);
		$threadID = $this->createThread($title, $content, $forumID);

		$this->saveData($department, $meetingText, $visitor,
						$date, $attendees, $meetingTopic, $time);

        // redirect after post
        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('threads', array('thread_id' => $threadID)), // 7cav.us/threads/123
            new XenForo_Phrase('event_created')
        );
	}

	public function createThread($title, $content, $forumID)
	{
		// get bot values
		$poster = $this->getIMOBot();

		// write the thread
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->set('user_id', $poster['user_id']);
		$writer->set('username', $poster['username']);
		$writer->set('title', $title);
		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $content);
		$writer->set('node_id', $forumID);
		$writer->set('sticky', true);
		$writer->preSave();
		$writer->save();
		return $writer->getDiscussionId();
	}

	public function saveData($department, $meetingText, $poster,
					$meetingDate, $attendees, $meetingTopic, $time)
	{
		$dw = XenForo_DataWriter::create('CavTools_DataWriter_Meetings');
		$dw->set('department', $department);
		$dw->set('meeting_text', $meetingText);
		$dw->set('poster_id', $poster['user_id']);
		$dw->set('posted_date', date('U'));
		$dw->set('meeting_date', $meetingDate);
		$dw->set('attendees', $attendees);
		$dw->set('meeting_topic', $meetingTopic);
		$dw->set('meeting_time', $time);
		$dw->save();
	}

	public function createTitle($date, $department, $topic)
	{
		// Meeting: Department | Topic | 01JAN16
		$formatedDate = date('dMy', $date); // 13Jun2016
		$title = $department . " | " . $topic . " | " . $formatedDate;
		return $title;
	}

	public function createContent($date, $department, $visitor, $attendees, $meetingText)
	{
		// Get Meeting model
		$model = $this->_getMeetingModel();
		$newline = "\n";

		$formattedDate = date('l jS F', $date);
		$rank = $model->getRank($visitor['user_id']);

		// Make sure we check if they are able to type first because they might not and its bad

		$header = $department . " meeting scheduled for " . $formattedDate . $newline;
		$header .= $newline . "Organised by: " . $rank . " " . $visitor['username'] . $newline . $newline;

		$attendance = $this->attendanceTable($attendees) . $newline . $newline;

		$main = $meetingText;

		// Return the content
		return $header . $attendance . $main;
	}

	public function attendanceTable($attendees)
	{
		$home = XenForo_Application::get('options')->homeURL;
		$attendees = explode(',', $attendees);
        $newLine = "\n";

		$table = "[table]" . $newLine . "|-". $newLine  . "| class=\"primaryContent\" colspan=\"3\" align=\"center\" | Meeting Attendance" .
            $newLine . "|- " . $newLine . "| style=\"font-style: italic\" align=\"center\" |Member" . $newLine . "| style=\"font-style: italic\" align=\"center\" |Position" .
            $newLine . "| style=\"font-style: italic\" align=\"center\" |Status" . $newLine . "|-" . $newLine;

		// Generate table
	    foreach($attendees as $attendee) {

			// get milpac model
			$model = $this->_getMilpacModel();

	        $position = $model->milpacsPosition($attendee);
	        $user = $model->getUser($attendee);
			$username = $user['username'];
			$userID = $user['user_id'];
			$status = "Waiting";

			// TODO:
			// Generalize, we don't want to enter each
			// username for things like the CSC

	        // Build username
	        $username = '[B][URL="http://'.$home.'/members/'.$userID.'/"]'. $username.'[/URL][/B]';

	        $table .= "| align=\"center\" |".$username." || align=\"center\" |".$position." || align=\"center\" |".$status. $newLine . "|-" . $newLine;
	    }
	    // Close table
	    return $table .= "[/table]";
	}

	protected function _getImoBotModel()
	{
		return $this->getModelFromCache ( 'CavTools_Model_IMOBot' );
	}

	protected function _getMeetingModel()
	{
		return $this->getModelFromCache ( 'CavTools_Model_Meetings' );
	}

	protected function _getMilpacModel()
	{
		return $this->getModelFromCache( 'CavTools_Model_Milpac' );
	}
}
