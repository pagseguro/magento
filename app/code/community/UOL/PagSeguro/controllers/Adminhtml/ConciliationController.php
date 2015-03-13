<?php

/**
************************************************************************
Copyright [2015] [PagSeguro Internet Ltda.]

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
************************************************************************
*/

class UOL_PagSeguro_Adminhtml_ConciliationController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Creates the layout of the administration
	 * Set the PagSeguro menu must be selected
	 */
    public function indexAction()
    {
    	$_SESSION['store_id'] = Mage::app()->getRequest()->getParam('store');	    	
        $this->loadLayout();		
		$this->_setActiveMenu('pagseguro_menu')->renderLayout();				
    }		
}
