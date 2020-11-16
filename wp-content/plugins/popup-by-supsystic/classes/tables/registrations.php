<?php
class tableRegistrationsPps extends tablePps {
    public function __construct() {
        $this->_table = '@__registrations';
        $this->_id = 'id';
        $this->_alias = 'sup_registrations';
        $this->_addField('id', 'hidden', 'int')
			->_addField('username', 'text', 'varchar')
			->_addField('email', 'text', 'varchar')
			->_addField('hash', 'text', 'varchar')
			->_addField('activated', 'text', 'int')
			->_addField('popup_id', 'text', 'int')
			->_addField('date_created', 'text', 'varchar')
			->_addField('all_data', 'text', 'text');
    }
}