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
}
