<?php

class CavTools_Model_Milpac extends XenForo_Model {

    public function milpacsPosition($user)
    {
        if (is_int($user)) {
            $userID = $user;
            $username = NULL;
        } else {
            $username = $user;
            $userID = NULL;
        }

        $query = $this->_getDb()->fetchRow("
                SELECT t1.position_id, t1.user_id, t2.position_title
                FROM xf_pe_roster_user_relation t1
                INNER JOIN xf_pe_roster_position t2
                ON t1.position_id = t2.position_id
                WHERE user_id = '$userID'
                OR username = '$username'
                ");
        return $query['position_title'];
    }

    public function getUser($user)
    {
        if (is_int($user)) {
            $userID = $user;
            $username = NULL;
        } else {
            $username = $user;
            $userID = NULL;
        }

        return $this->_getDb()->fetchRow("
                SELECT *
                FROM xf_user
                WHERE user_id = '$userID'
                OR username = '$username'
                ");
    }

    public function getRank($userID)
    {
        return $this->_getDb()->fetchRow("
        SELECT title
        FROM xf_pe_roster_rank
        INNER JOIN xf_pe_roster_user_relation
        ON xf_pe_roster_rank.rank_id = xf_pe_roster_user_relation.rank_id
        WHERE xf_pe_roster_user_relation.user_id = '$userID'
        ")
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

    public function getAllMilpacPositions()
    {
        return $this->_getDb()->fetchAll("
        SELECT position_id, position_title
        FROM xf_pe_roster_position
        ORDER BY materialized_order ASC
        ");
    }
}
