<?php

class CavTools_Model_Meetings extends XenForo_Model
{
    public function getMeetingById($ID)
    {
        return $this->_getDb()->fetchRow("
            SELECT * 
            FROM xf_ct_regi_meetings
            WHERE meeting_id = '$ID'
        ");
    }

    public function getAllMeetings()
    {
        return $this->_getDb()->fetchAll("
        SELECT *
        FROM xf_ct_regi_meetings
        WHERE hidden = FALSE 
        ORDER BY meeting_id ASC
        ");
    }
}
