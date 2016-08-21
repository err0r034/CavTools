<?php

class CavTools_DataWriter_Meetings extends XenForo_DataWriter {

    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_ct_regi_meetings' => array(
                'meeting_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'meeting_text' => array('type' => self::TYPE_STRING),
                'poster_id' => array('type' => self::TYPE_STRING),
                'posted_date' => array('type' => self::TYPE_FLOAT),
                'meeting_date' => array('type' => self::TYPE_FLOAT),
                'attendees' => array('type' => self::TYPE_STRING),
                'hidden' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
            )
        );
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * @param mixed
     *
     * @see XenForo_DataWriter::_getExistingData()
     *
     * @return array|false
     */
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'meeting_id'))
        {
            return false;
        }

        return array('xf_ct_regi_meetings' => $this->_getMeetingModel()->getTextById($id));
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @see XenForo_DataWriter::_getUpdateCondition()
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'meeting_id = ' . $this->_db->quote($this->getExisting('meeting_id'));
    }

    /**
     * Get the Meeting model.
     *
     * @return CavTools_Model_Meetings
     */
    protected function _getMeetingModel()
    {
        return $this->getModelFromCache ( 'CavTools_Model_Meetings' );
    }
}