<?php
class tableModulesPps extends tablePps {
    public function __construct() {
        $this->_table = '@__modules';
        $this->_id = 'id';     /*Let's associate it with posts*/
        $this->_alias = 'sup_m';
        $this->_addField('label', 'text', 'varchar', 0, __('Label', PPS_LANG_CODE), 128)
                ->_addField('type_id', 'selectbox', 'smallint', 0, __('Type', PPS_LANG_CODE))
                ->_addField('active', 'checkbox', 'tinyint', 0, __('Active', PPS_LANG_CODE))
                ->_addField('params', 'textarea', 'text', 0, __('Params', PPS_LANG_CODE))
                //->_addField('has_tab', 'checkbox', 'tinyint', 0, __('Has Tab', PPS_LANG_CODE))
                //->_addField('description', 'textarea', 'text', 0, __('Description', PPS_LANG_CODE), 128)
                ->_addField('code', 'hidden', 'varchar', '', __('Code', PPS_LANG_CODE), 64)
                ->_addField('ex_plug_dir', 'hidden', 'varchar', '', __('External plugin directory', PPS_LANG_CODE), 255);
    }
}