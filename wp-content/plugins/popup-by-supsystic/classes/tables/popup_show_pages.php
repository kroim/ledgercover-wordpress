<?php
class tablePopup_show_pagesPps extends tablePps {
    public function __construct() {
        $this->_table = '@__popup_show_pages';
        $this->_id = 'id';
        $this->_alias = 'sup_popup_show_pages';
        $this->_addField('popup_id', 'text', 'int')
				->_addField('post_id', 'text', 'int')
				->_addField('not_show', 'text', 'int');
    }
}