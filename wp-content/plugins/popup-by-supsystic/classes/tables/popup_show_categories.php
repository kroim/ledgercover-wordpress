<?php
class tablePopup_show_categoriesPps extends tablePps {
    public function __construct() {
        $this->_table = '@__popup_show_categories';
        $this->_id = 'id';
        $this->_alias = 'sup_popup_show_categories';
        $this->_addField('popup_id', 'text', 'int')
				->_addField('term_id', 'text', 'int')
				->_addField('not_show', 'text', 'int');
    }
}