<?php
class admin_navViewPps extends viewPps {
	public function getBreadcrumbs() {
		$this->assign('breadcrumbsList', dispatcherPps::applyFilters('mainBreadcrumbs', $this->getModule()->getBreadcrumbsList()));
		return parent::getContent('adminNavBreadcrumbs');
	}
}
