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

	// TODO: Get user from position in db list


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

		// Prepare templates
		$meetingModel = $this->_getMeetingTemplateModel();
		$milpacModel = $this->_getMilpacModel();
        $templates = $meetingModel->getAllMeetingTemplates();
        $memberURL = '/members/';
        for ($i=0;$i<count($templates);$i++) {
            $user = $milpacModel->getUser($templates[$i]['creator']);
            $templates[$i]['creator_username'] = $user['username'];
			$rank = $milpacModel->getRank($templates[$i]['creator']);
			$templates[$i]['creator_rank'] = $rank['title'];
        }

		//Get values from options
        $timeOptions = $this->createTimeOptions();

		$milpacModel = $this->_getMilpacModel();
		$positions = $milpacModel->getAllMilpacPositions();

		// View Parameters
		$viewParams = array(
		    'timeOptions' => $timeOptions,
			'templates' => $templates,
			'positions' => $positions,
			'defaultMessage' => ""
		);

		// Send to template to display
		return $this->responseView('CavTools_ViewPublic_MeetingCreator', 'CavTools_CreateMeeting', $viewParams);
	}

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

		if ($this->_input->filterSingle('selection', XenForo_Input::STRING) === 'A') {
			$templateID = $this->_input->filterSingle('template', XenForo_Input::STRING);
			$templateModel = $this->_getMeetingTemplateModel();
			$template = $templateModel->getTemplateById($templateID);
			$meetingTitle = $template['meeting_title'];
			$meetingText = $template['meeting_text'];
			$meetingText = XenForo_Helper_String::autoLinkBbCode($meetingText);
			$positons = $template['positions'];

		} else if ($this->_input->filterSingle('selection', XenForo_Input::STRING) === 'B') {
			// Form values
	        $meetingTitle = $this->_input->filterSingle('meeting_title', XenForo_Input::STRING);
	        $meetingText = $this->getHelper('Editor')->getMessageText('message', $this->_input);
			$meetingText = XenForo_Helper_String::autoLinkBbCode($meetingText);
			$positions = serialize($_POST["positions"]);
		}

		// TODO: check if template is pushed to forum post correctly

		$date = $this->_input->filterSingle('date', XenForo_Input::STRING);
		$time = $this->_input->filterSingle('time', XenForo_Input::STRING);
		$time = htmlspecialchars($time);
		$date = htmlspecialchars($date);

		$convertDate = new DateTime("$date");
		$date = $convertDate->format('U');

		$convertTime = new DateTime("$time");
		$time = $convertTime->format('U');

		// Get Forum ID
		$forumID = XenForo_Application::get('options')->meetingForumID;

		// make thread title + content
		$title = $this->createTitle($date, $meetingTitle);
		$content = $this->createContent($date, $visitor, $meetingText, $positions);
		$threadID = $this->createThread($title, $content, $forumID);

		$this->saveData($meetingText, $visitor,
						$date, $meetingTitle, $time);

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

	public function saveData($meetingText, $poster,
					$meetingDate, $meetingTopic, $time)
	{
		$dw = XenForo_DataWriter::create('CavTools_DataWriter_Meetings');
		$dw->set('meeting_text', $meetingText);
		$dw->set('poster_id', $poster['user_id']);
		$dw->set('posted_date', date('U'));
		$dw->set('meeting_date', $meetingDate);
		$dw->set('meeting_topic', $meetingTopic);
		$dw->set('meeting_time', $time);
		$dw->save();
	}

	public function createTitle($date, $topic)
	{
		// Meeting: Department | Topic | 01JAN16
		$formatedDate = date('dMy', $date); // 13Jun2016
		$title = "[Meeting] " . $topic . " | " . $formatedDate;
		return $title;
	}

	public function createContent($date, $visitor, $meetingText, $positions)
	{
		// Get Meeting model
		$model = $this->_getMeetingModel();
		$newline = "\n";

		$formattedDate = date('l jS F', $date);
		$rank = $model->getRank($visitor['user_id']);

		// Make sure we check if they are able to type first because they might not and its bad

		$attendance = $this->attendanceTable($positions);

		$header = "Meeting scheduled for " . $formattedDate . $newline;
		$header .= $newline . "Organised by: " . $rank . " " . $visitor['username'] . $newline . $newline;

		$main = $meetingText . $newline . $newline . $attendance;

		// Return the content
		return $header . $main;
	}

	public function attendanceTable($positions)
	{
		$home = XenForo_Application::get('options')->homeURL;
		$positions = unserialize($positions);
        $newLine = "\n";

		$table = "[table]" . $newLine . "|-". $newLine  . "| class=\"primaryContent\" colspan=\"3\" align=\"center\" | Meeting Attendance" .
            $newLine . "|- " . $newLine . "| style=\"font-style: italic\" align=\"center\" |Member" . $newLine . "| style=\"font-style: italic\" align=\"center\" |Position" .
            $newLine . "| style=\"font-style: italic\" align=\"center\" |Status" . $newLine . "|-" . $newLine;

		$spoiler = "[spoiler]";

		// Generate table
	    foreach($positions as $position) {

			// get milpac model
			$model = $this->_getMilpacModel();
			$user = $model->getUserFromPosId($position);
			$username = $user['username'];
			$userID = $user['user_id'];
			$title = $model->milpacsPosition($userID);
			$rank  = $model->getRank($userID);
			$rank  = $rank['title'];
			$status = "Waiting";

			// TODO:
			// Generalize, we don't want to enter each
			// username for things like the CSC

	        // Build username
	        $userLink = '[B][URL="http://'.$home.'/members/'.$userID.'/"]'. $username.'[/URL][/B]';

	        $table .= "| align=\"center\" |".$rank." ".$userLink." || align=\"center\" |".$title." || align=\"center\" |".$status. $newLine . "|-" . $newLine;

			$spoiler .= "@".$username." ";
	    }
	    // Close table
	    $table .= "[/table]";
		// Close spoiler
		$spoiler .= "[/spoiler]";

		return $table . $newLine . $newLine . $spoiler;
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

	protected function _getMeetingTemplateModel()
	{
		return $this->getModelFromCache( 'CavTools_Model_MeetingTemplate' );
	}
}
