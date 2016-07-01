<?php

class CavTools_ControllerPublic_AutoADR extends XenForo_ControllerPublic_Abstract {

    // Runs on Page load
    public function actionIndex()
    {
        // Get enable value
        $enable = XenForo_Application::get('options')->enableADR;

        // If not enabled, escape
        if(!$enable) 
        {
            throw $this->getNoPermissionResponseException();
        }

        // If cannot view, escape
        if (!XenForo_Visitor::getInstance()->hasPermission('CavToolsGroupId', 'viewADR'))
        {
            throw $this->getNoPermissionResponseException();
        }

        // If cannot action, don't display the button to rebuild
        if (XenForo_Visitor::getInstance()->hasPermission('CavToolsGroupId', 'canUpdateADR'))
        {
            $canAction = true;
        } else {
            $canAction = false;
        }

        // Get the latest ADR construct
        $model = $this->_getADRModel();
        $data = $model->getLatestADR();


        // View Parameters
        $viewParams = array(
            'data' => $data['text'],
            'canAction' => $canAction
        );

        // Send to template to display
        return $this->responseView('CavTools_ViewPublic_ADR', 'CavTools_adr', $viewParams);
    }

    // Runs on Page POST
    public function actionPost()
    {
        // Create the new ADR then store in DB
        $data = $this->createList();
        $this->createData($data);

        // redirect after post
        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('adr')
        );
    }

    // Func to build ADR
    public function createList()
    {
        // Set initial vars
        $data = "";
        $newLine= "<br />";

        // Get ADR data model
        $model = $this->_getADRModel();

        // Get home URL from enlistment options
        $home = XenForo_Application::get('options')->homeURL;

        // Begin members build
        $members = $model->getPrimaryPosIDs();

        // Get all secondary billets
        $secondaryBillets = $model->getSecondaryPosIDs();

        // Begin build of secondary billets
        $extraMembers = array();
        foreach ($secondaryBillets as $secondaryBillet)
        {
            // All the individual position IDs
            $billets = explode(',', $secondaryBillet['CAST(t1.secondary_position_ids AS CHAR(100))']);

            // Iterate through all Pos IDs
            foreach ($billets as $billet) {
                // Get the data for given Pos ID
                $positionData = $model->getPositionData($billet);

                // Build a member for that position
                $secondaryMembers = array(
                    'position_id' => $positionData['position_id'],
                    'position_title' => $positionData['position_title'],
                    'title' => $secondaryBillet['title'],
                    'username' => $secondaryBillet['username'],
                    'user_id' => $secondaryBillet['user_id'],
                    'materialized_order' => $positionData['materialized_order'],
                    'position_group_id' => $positionData['position_group_id']
                );

                // Add this member to secondary billet list
                array_push($extraMembers ,$secondaryMembers);
            }
        }
        // Combine secondary billets with primary
        $members = array_merge($members, $extraMembers);
        // Sort member list via Milpacs display order
        $this->sksort($members,"materialized_order", true);

        // Begin headings
        $hqData = "<br><h3>Regimental Headquarters</h3><hr><br>";
        $bat1HQData = "<br><h3>1-7 Command</h3><hr><br>";
        $suptData = "<br><h3>Support Departments</h3><hr><br>";
        $alpha1Data = "<br><h3>Alpha Company</h3><hr><br>";
        $bravo1Data = "<br><h3>Bravo Company</h3><hr><br>";
        $charlie1Data = "<br><h3>Charlie Company</h3><hr><br>";
        $training1Data = "<br><h3>Training Company</h3><hr><br>";
        $bat2HQData = "<br><h3>2-7 Command</h3><hr><br>";
        $bravo2Data = "<br><h3>Bravo Company</h3><hr><br>";
        $alpha2Data = "<br><h3>Alpha Company</h3><hr><br>";
        $charlie2Data = "<br><h3>Charlie Company</h3><hr><br>";
        $newRecruitsData = "<br><h3>New Recruits</h3><hr><br>";
        $starterData = "<br><h3>Starter Company</h3><hr><br>";

        // Begin Support headings
        $jagData = "<br><h3>Judge Advocate General's Office (JAG)</h3><hr><br>";
        $s1Data = "<br><h3>S1 Department</h3><hr><br>";
        $s2Data = "<br><h3>S2 Military Intelligence Department</h3><hr><br>";
        $mpData = "<br><h3>Military Police</h3><hr><br>";
        $s3Data = "<br><h3>S3 Department</h3><hr><br>";
        $s5Data = "<br><h3>S5 Public Relations Department</h3><hr><br>";
        $rrdData = "<br><h3>Regimental Recruiting Department (RRD)</h3><hr><br>";
        $rtcData = "<br><h3>Recruit Training Command (RTC)</h3><hr><br>";
        $ncoData = "<br><h3>NCO Academy</h3><hr><br>";
        $s6Data = "<br><h3>S6 - Regimental IMO (Information Management Office)</h3><hr><br>";

        // Create data entry for each member
        foreach ($members as $member) {
            // Build Profile link
            $userURL = '<a href="/members/'.$member['user_id'].'">'.$member['title']." ".$member['username'].'</a>';

            // Decide which data group to assign
            // TODO - change from magic numbers to Xen options
            switch ($member['position_group_id'])
            {
                case '1':
                    $hqData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '3':
                    $bat1HQData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '4':
                    // All support department
                    // Decide on which department via position ID
                    switch($member['position_id'])
                    {
                        case ($member['position_id'] == '15' || $member['position_id'] == '16' || $member['position_id'] == '17' || $member['position_id'] == '219'):
                            $s1Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '18' || $member['position_id'] == '19' || $member['position_id'] == '20'):
                            $s2Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '21' || $member['position_id'] == '22' || $member['position_id'] == '23'):
                            $s3Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '24' || $member['position_id'] == '25' || $member['position_id'] == '26'):
                            $s5Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] =='3' || $member['position_id'] == '27' || $member['position_id'] == '28'):
                            $s6Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '29' || $member['position_id'] == '30' || $member['position_id'] == '220' || $member['position_id'] == '198' || $member['position_id'] == '228'):
                            $rtcData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '31' || $member['position_id'] == '32' || $member['position_id'] == '203' || $member['position_id'] == '227'):
                            $rrdData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] =='33' || $member['position_id'] == '34'):
                            $jagData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '35' || $member['position_id'] == '36' || $member['position_id'] == '201' || $member['position_id'] == '229'):
                            $mpData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                        case ($member['position_id'] == '37' || $member['position_id'] == '38'):
                            $ncoData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                            break;
                    }
                    break;
                case '5':
                    $alpha1Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '6':
                    $bravo1Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '7':
                    $charlie1Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '8':
                    $training1Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '9':
                    $bat2HQData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '10':
                    $bravo2Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '11':
                    $alpha2Data .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '12':
                    $charlie2Data .= "<p>". $member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '13':
                    $newRecruitsData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
                case '15':
                    $starterData .= "<p>".$member['position_title'] . " : " . $userURL . $newLine."</p>";
                    break;
            }
        }

        // Build Battalion Data
        $bat1Total = "<h3>1st Battalion</h3><hr><br>" . $bat1HQData . $alpha1Data . $bravo1Data . $charlie1Data . $training1Data;
        $bat2Total = "<h3>2nd Battalion</h3><hr><br>" . $bat2HQData . $alpha2Data . $bravo2Data . $charlie2Data;

        // Build Support Data
        $supportTotal = $jagData . $newLine . $s1Data . $newLine . $s2Data . $newLine .
            $mpData . $newLine . $s3Data . $newLine . $s5Data . $newLine .
            $rrdData . $newLine .$rtcData . $newLine .$ncoData . $newLine .
            $s6Data  . $newLine;

        // Combine data sets
        $data .= $newLine . $hqData . $supportTotal . $bat1Total .$newLine . $newLine. $bat2Total;

        // Give our data some breathing space
        $data .= $newLine. $newLine . $newLine;

        // Return new ADR
        return $data;
    }

    // Sorting fun
    public function sksort(&$array, $subkey="id", $sort_ascending=false) {

        if (count($array))
            $temp_array[key($array)] = array_shift($array);

        foreach($array as $key => $val){
            $offset = 0;
            $found = false;
            foreach($temp_array as $tmp_key => $tmp_val)
            {
                if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
                {
                    $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                        array($key => $val),
                        array_slice($temp_array,$offset)
                    );
                    $found = true;
                }
                $offset++;
            }
            if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
        }

        if ($sort_ascending) $array = array_reverse($temp_array);

        else $array = $temp_array;
    }

    // Calls DataWriter
    public function createData($data)
    {
        $dw = XenForo_DataWriter::create('CavTools_DataWriter_ADR');
        $dw->set('text', $data);
        $dw->save();
    }

    // Gets ADR data model
    protected function _getADRModel()
    {
        return $this->getModelFromCache ( 'CavTools_Model_ADR' );
    }
}

