<?php
class tablePopupPps extends tablePps {
    public function __construct() {
        $this->_table = '@__popup';
        $this->_id = 'id';
        $this->_alias = 'sup_popup';
        $this->_addField('id', 'text', 'int')
				->_addField('label', 'text', 'varchar')
				->_addField('active', 'text', 'int')
				->_addField('original_id', 'text', 'int')
				->_addField('params', 'text', 'text')
				->_addField('html', 'text', 'text')
				->_addField('css', 'text', 'text')
				->_addField('img_preview', 'text', 'text')
				
				->_addField('show_on', 'text', 'int')
				->_addField('show_to', 'text', 'int')
				->_addField('show_pages', 'text', 'int')
				->_addField('type_id', 'text', 'int')
				
				->_addField('views', 'text', 'int')
				->_addField('unique_views', 'text', 'int')
				->_addField('actions', 'text', 'int')
				
				->_addField('show_in_admin_area', 'text', 'int')
				
				->_addField('date_created', 'text', 'text');
    }
}