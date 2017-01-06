<?php

class CavTools_ControllerPublic_TigChecker extends XenForo_ControllerPublic_Abstract {

    public function actionIndex() {

        $milpacModel = $this->_getMilpacsModel();
        $users = $milpacModel->getAllMilpacs();

        $highTig = array();
        $medTig  = array();
        $lowTig  = array();
        $count = 0;

        $dischPos = XenForo_Application::get('options')->dischargedPosition;
        $disDischPos = XenForo_Application::get('options')->dishonorableDischargePosition;

        $ranks = array(
            27 => array('ab' => "PVT", 'tig' => 30),
            26 => array('ab' => "PFC", 'tig' => 60),
            25 => array('ab' => "SPC", 'tig' => 120),
            24 => array('ab' => "CPL", 'tig' => 120),
            23 => array('ab' => "SGT", 'tig' => 120),
            22 => array('ab' => "SSG", 'tig' => 180),
            21 => array('ab' => "SFC", 'tig' => 180),
            16 => array('ab' => "WO1", 'tig' => 120),
            15 => array('ab' => "CW2", 'tig' => 180),
            14 => array('ab' => "CW3", 'tig' => 180),
            13 => array('ab' => "CW4", 'tig' => 180),
            11 => array('ab' => "2LT", 'tig' => 180),
            10 => array('ab' => "1LT", 'tig' => 180),
             9 => array('ab' => "CPT", 'tig' => 180),
             8 => array('ab' => "MAJ", 'tig' => 180),
             7 => array('ab' => "LTC", 'tig' => 180)
        );

        foreach ($users as $user) {

            $discharged = false;
            if ($user['position_id'] == $disDischPos || $user['position_id'] == $dischPos) {
                $discharged = true;
            }

            $ranked = false;
            if(!array_key_exists($user['rank_id'], $ranks)) {
                $ranked = true;
            }

            if (!$discharged && !$ranked) {

                $promoDate = $user['promotion_date'];
                date_default_timezone_set("UTC");
                $today = time();
                $tig = $today - $promoDate;
                $tigDays = floor($tig / (60 * 60 * 24));
                $rank = $ranks[$user['rank_id']];
                $tigComp = $tigDays - $rank['tig'];

                $user['tig'] = $tigComp;

                if ($tigComp >= 180) {
                    array_push($highTig, $user);
                } else if (180 > $tigComp && $tigComp >= 90) {
                    array_push($medTig, $user);
                } else if (90 > $tigComp && $tigComp >= 0) {
                    array_push($lowTig, $user);
                }
            }
        }

        $highData = "";
        $medData  = "";
        $lowData  = "";
        $userUrl  = '/rosters/profile?uniqueid=';

        $tig = array();
        foreach ($highTig as $key => $row) {
            $tig[$key] = $row['tig'];
        }
        array_multisort($tig, SORT_DESC, $highTig);

        $tig = array();
        foreach ($medTig as $key => $row) {
            $tig[$key] = $row['tig'];
        }
        array_multisort($tig, SORT_DESC, $medTig);

        $tig = array();
        foreach ($lowTig as $key => $row) {
            $tig[$key] = $row['tig'];
        }
        array_multisort($tig, SORT_DESC, $lowTig);

        foreach ($highTig as $user) {
            $rankTitle = $milpacModel->getRankTitle($user['rank_id']);
            $highData .= "<tr><td><b>" . $rankTitle . "</td><td><a href=" . $userUrl .
            $user['relation_id'] . "><b>" . $user['username'] . "</b></a></td>" .
            "<td><b style='color:red'>" . $user['tig'] . " days extra TIG</b></td>";
        }

        foreach ($medTig as $user) {
            $rankTitle = $milpacModel->getRankTitle($user['rank_id']);
            $medData .= "<tr><td><b>" . $rankTitle . "</td><td><a href=" . $userUrl .
            $user['relation_id'] . "><b>" . $user['username'] . "</b></a></td>" .
            "<td><b style='color:orange'>" . $user['tig'] . " days extra TIG</b></td>";
        }

        foreach ($lowTig as $user) {
            $rankTitle = $milpacModel->getRankTitle($user['rank_id']);
            $lowData .= "<tr><td><b>" . $rankTitle . "</td><td><a href=" . $userUrl .
            $user['relation_id'] . "><b>" . $user['username'] . "</b></a></td>" .
            "<td><b style='color:yellow'>" . $user['tig'] . " days extra TIG</b></td>";
        }

        //View Parameters
        $viewParams = array(
            'highData' => $highData,
            'medData'  => $medData,
            'lowData'  => $lowData,
        );

        //Send to template to display
        return $this->responseView('CavTools_ViewPublic_tigcheck', 'CavTools_TigCheck', $viewParams);
    }

    protected function _getMilpacsModel()
    {
        return $this->getModelFromCache( 'CavTools_Model_Milpacs' );
    }

}
