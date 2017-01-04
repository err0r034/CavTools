<?php

class CavTools_Model_Milpacs extends XenForo_Model {

    public function getMilpacByUserID($userID) {

        $query = $this->_getDb()->fetchRow("
            SELECT *
            FROM xf_pe_roster_user_relation
            WHERE user_id = ?
        ", $userID);

        if ($query == null) {
            return null;
        } else {
            return $query;
        }
    }

    public function getAllMilpacs() {
        return $this->_getDb()->fetchAll("
            SELECT *
            FROM xf_pe_roster_user_relation
            WHERE roster_id = 1
            ORDER BY rank_id asc
        ");
    }

    public function getRankTitle($rankID) {
        $query =  $this->_getDb()->fetchRow("
            SELECT title
            FROM xf_pe_roster_rank
            WHERE rank_id = ?
            ", $rankID);

        return $query['title'];
    }
}
