<?php
class tableUsage_statPps extends tablePps {
    public function __construct() {
        $this->_table = '@__usage_stat';
        $this->_id = 'id';     
        $this->_alias = 'sup_usage_stat';
        $this->_addField('id', 'hidden', 'int', 0, __('id', PPS_LANG_CODE))
			->_addField('code', 'hidden', 'text', 0, __('code', PPS_LANG_CODE))
			->_addField('visits', 'hidden', 'int', 0, __('visits', PPS_LANG_CODE))
			->_addField('spent_time', 'hidden', 'int', 0, __('spent_time', PPS_LANG_CODE))
			->_addField('modify_timestamp', 'hidden', 'int', 0, __('modify_timestamp', PPS_LANG_CODE));
    }
}