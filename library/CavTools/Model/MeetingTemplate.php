<?php

class CavTools_Model_MeetingTemplate extends XenForo_Model
{
    public function getTemplateById($ID)
    {
        return $this->_getDb()->fetchRow("
            SELECT *
            FROM xf_ct_regi_meetings
            WHERE meeting_id = '$ID'
        ");
    }

    public function getAllMeetingTemplates()
    {
        return $this->_getDb()->fetchAll("
        SELECT *
        FROM xf_ct_regi_meeting_templates
        WHERE hidden = FALSE
        ORDER BY meeting_template_id ASC
        ");
    }

    public function getTitleFromID($posID)
    {
        return $this->_getDb()->fetchRow("
        SELECT position_title
        FROM xf_pe_roster_position
        WHERE position_id = '$posID'
        ");
    }
}
