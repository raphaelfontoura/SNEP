<?php

class LogsController extends Zend_Controller_Action {

    public function indexAction() {
        $this->view->breadcrumb = $this->view->translate("Status » Logs do Sistema");
	$config = Zend_Registry::get('config');

	include( $config->system->path->base . "/inspectors/Permissions.php" );
	$test = new Permissions();
        $response = $test->getTests();
   
        $form = new Snep_Form(new Zend_Config_Xml('./default/forms/logs.xml', 'general', true));
	
        $form->setAction($this->getFrontController()->getBaseUrl() . '/logs/view');
	
	$yesterday = Zend_Date::now()->subDate(1);
	$initDay = $form->getElement('init_day');
	$initDay->setValue(strtok($yesterday, ' '));

	$endDay = $form->getElement('end_day');
	$endDay->setValue(strtok(Zend_Date::now(), ' '));

	$submit = $form->getElement("submit");
	$submit->setLabel("Pesquisar Log");

        /*
	$tail = $form->getElement("cancel");	
	$tail->setLabel("Em tempo real");
	$tail->setAttrib("onclick", "location.href='view/mode/tail/lines/30'");
         * 
         */

        $this->initLogFile();	

	$this->view->form = $form;	
    }
 
    private function initLogFile() {
	$log = new Snep_Log(Zend_Registry::get('config')->system->path->log, 'agi.log');

	return $log;
    }

    public function viewAction() {

	$log = $this->initLogFile();

        $this->view->breadcrumb = $this->view->translate("Status » Logs do Sistema");

	$this->view->back           = $this->view->translate("Voltar");
	$this->view->exibition_mode = $this->view->translate("Modo de exibição:");
	$this->view->normal         = $this->view->translate("Normal");
	$this->view->terminal       = $this->view->translate("Terminal");
	$this->view->contrast       = $this->view->translate("Constraste");
	
	if ($log != 'error') {

	    // Normal search mode
	    if (strcmp($this->_request->getParam('mode'), 'tail')) {

 		 $this->view->mode   = 'normal';
		 $this->view->location = 'index';

	         $result = $log->getLog($this->_request->getPost('init_day'), 
		                 $this->_request->getPost('end_day'),
				 $this->_request->getPost('init_hour'),
				 $this->_request->getPost('end_hour'),
				 $this->_request->getPost('status'),
				 $this->_request->getPost('source'),
				 $this->_request->getPost('dest'));
	
	        if (count($result) > 0) {

	            $this->view->result = $result;
                } else {

		    $this->view->error = $this->view->translate("Nenhum resultado encontrado!");
    		    $this->_helper->viewRenderer('error');
	        }
	
	    // Tail log mode
	    } else {

 		 $this->view->mode   = 'tail';
		 $this->view->location = '../../../../index';

	         $this->view->lines = $this->view->translate("Número de linhas");
	    }
	} else {

            $this->view->error = $this->view->translate("Arquivo de log não pode ser aberto!");
	    $this->_helper->viewRenderer('error');
	}

    }
  
    public function tailAction() {
	$this->_helper->layout->disableLayout();

	$log = $this->initLogFile();
	$lines = $this->_request->getParam('lines');

	$this->view->lines = $lines;
	$result = $log->getTail($lines);

	$this->view->result = $result;
    }

    public function errorAction() {
    }
}
