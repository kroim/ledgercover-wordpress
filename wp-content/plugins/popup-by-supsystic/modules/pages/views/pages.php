<?php
class pagesViewPps extends viewPps {
    public function displayDeactivatePage() {
        $this->assign('GET', reqPps::get('get'));
        $this->assign('POST', reqPps::get('post'));
        $this->assign('REQUEST_METHOD', strtoupper(reqPps::getVar('REQUEST_METHOD', 'server')));
        $this->assign('REQUEST_URI', basename(reqPps::getVar('REQUEST_URI', 'server')));
        parent::display('deactivatePage');
    }
}

