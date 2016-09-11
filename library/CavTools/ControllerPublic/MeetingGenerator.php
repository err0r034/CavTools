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
		$model = _getImoBotModel();
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
		$meetingTopic = $this->_input->filterSingle('meeting_topic', XenForo_Input::STRING);
		$meetingText = $this->_input->filterSingle('meeting_text', XenForo_Input::STRING);
		$date = $this->_input->filterSingle('date', XenForo_Input::STRING);
		$time = $this->_input->filterSingle('time', XenForo_Input::STRING);
		$attendees = $this->_input->filterSingle('attendees', XenForo_Input::STRING);

		$meetingText = htmlspecialchars($meetingText);
		$time = htmlspecialchars($time);
		$date = htmlspecialchars($date);
		$attendees = htmlspecialchars($attendees);

		$convertDate = new DateTime("$date");
		$date = $convertDate->format('U');

		$convertTime = new DateTime("$time");
		$time = $convertTime->format('U');

		// Get Forum ID
		$forumID = XenForo_Application::get('options')->meetingForumID;

		// make thread title + content
		$title = createTitle($date, $department, $topic);
		$content = createContent($date, $deparment, $visitor, $meetingText);
		$threadID = $createThread($title, $content, $forumID);

		$saveData();
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

	public function createContent($date, $deparment, $visitor, $attendees, $meetingText)
	{
		// Get Meeting model
		$model = $this->_getMeetingModel();
		$newline = "/n";

		$formatedDate = date('l the jS of J', $date); // Monday the 2nd of September
		$rank = $model->getRank($visitor['user_id']);

		// Make sure we check if they are able to type first because they might not and its bad

		$header = $department . " meeting schedualed for " . $formatedDate . $newline;
		$header .= $newline . "Organised by: " . $rank . " " . $visitor['username'] . $newline . $newline;

		$attendance = $this->attendanceTable($attendees);

		$main = $meetingText;

		// Return the content
		return $header . $attendance . $main;
	}

	public function attendanceTable($attendees)
	{
		$home = XenForo_Application::get('options')->homeURL;
		$attendees = explode(',', $attendees);

		$table = "[table]" . $newLine . "|-". $newLine  . "| class=\"primaryContent\" colspan=\"4\" align=\"center\" | AWOL Tracking" .
            $newLine . "|- " . $newLine . "| style=\"font-style: italic\" align=\"center\" |Member" . $newLine . "| style=\"font-style: italic\" align=\"center\" |Position" .
            $newLine . "| style=\"font-style: italic\" align=\"center\" |Status" . $newLine . "|-" . $newLine;

		// Generate table
	    foreach($attendees as $attendee) {

			// get milpac model
			$model = $this->_getMilpacModel();

	        $position = $model->milpacsPosition($attendee);
	        $username = $model->getUsername($attendee);
			$status = "Waiting";

	        // Build username
	        $username = '[B][URL="http://'.$home.'/members/'.$attendee.'/"]'. $username.'[/URL][/B]';

	        $table .= "| align=\"center\" |".$username." || align=\"center\" |".$position." || align=\"center\" |".$status. $newLine . "|-" . $newLine;
	    }
	    // Close table
	    $table .= "[/table]";
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
		return $this->getModelFromCache( 'CavTools_Model_Milpac' )
	}
}
