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

    public function getRank($userID)
    {
        // TODO:
        // query rank via userID lookup
        // return as full RANK i.e. PRIVATE not PVT
    }

    public function getUsersFromGroup($groupID)
    {
        $positions = $this->_getDb()->fetchAll("
        SELECT *
        FROM xf_pe_roster_position
        WHERE position_group_id = '$groupID'
        ");

        $users = $this->_getDb()->fetchAll("
        SELECT *
        FROM xf_pe_roster_user_relation
        WHERE position_id IN (".implode(',',$positions['position_id']).")
        OR CAST(secondary_position_ids AS CHAR(100)) IN (".implode(',',$positions['position_id']).")
        ");

        return $users['user_id'];
    }

    public function getUsersFromCustomGroup($customGroup)
    {

    }
}
