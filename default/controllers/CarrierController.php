<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Carrier Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */

class CarrierController extends Zend_Controller_Action {

    /**
     * List all Carrier
     */
    public function indexAction() {
        
        $this->view->breadcrumb = $this->view->translate("Cadastro » Operadoras");

        $this->view->url = $this->getFrontController()->getBaseUrl() ."/". $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');
        $select = $db->select()
                        ->from("operadoras")
                        ->order('nome');
                 
        if ($this->_request->getPost('filtro')) {
            $field = mysql_escape_string($this->_request->getPost('campo'));
            $query = mysql_escape_string($this->_request->getPost('filtro'));
            $select->where("n.`$field` like '%$query%'");
        }

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage(Zend_Registry::get('config')->ambiente->linelimit);

        $this->view->carrier = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $opcoes = array("codigo"      => $this->view->translate("Código"),
                        "nome"        => $this->view->translate("Nome"));

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->_request->getPost('campo'));
        $filter->setFieldOptions($opcoes);
        $filter->setFieldValue($this->_request->getPost('filtro'));
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url"     => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add/",
                                          "display" => $this->view->translate("Incluir Operadora"),
                                          "css"     => "include"));
    }

    /**
     *  Add Carrier
     */
    public function addAction() {

        $this->view->breadcrumb = $this->view->translate("Operadoras » Cadastro");

        $db = Zend_Registry::get('db');

        $this->view->objSelectBox = "carrier";

        $xml = new Zend_Config_Xml( "default/forms/carrier.xml" );

        $form = new Snep_Form( $xml );
        $form->setAction( $this->getFrontController()->getBaseUrl() .'/'. $this->getRequest()->getControllerName() . '/add');

        $name = $form->getElement('name');
        $name->setLabel( $this->view->translate('Nome') );

        $ta = $form->getElement('ta');
        $ta->setLabel( $this->view->translate('Tempo de Arranque') );        

        $tf = $form->getElement('tf');
        $tf->setLabel( $this->view->translate('Tempo de Fracionamento') );

        $tbf = $form->getElement('tbf');
        $tbf->setLabel( $this->view->translate('Tarifa Base para Fixo') );

        $tbc = $form->getElement('tbc');
        $tbc->setLabel( $this->view->translate('Tarifa Base de Celular') );

        $_idleCostCenter = Snep_Carrier_Manager::getIdleCostCenter();
        $idleCostCenter = array();
        foreach($_idleCostCenter as $idle) {
            $idleCostCenter[$idle['codigo']] = $idle['codigo'] ." : ". $idle['tipo'] ." - ". $idle['nome'];
        }
        
        $form->setSelectBox( $this->view->objSelectBox, $this->view->translate('Centro de Custos'), $idleCostCenter);

        $form->setButtom();       

        if($this->_request->getPost()) {

                $form_isValid = $form->isValid($_POST);
                $dados = $this->_request->getParams();

                if( $form_isValid ) {

                    $idCarrier = Snep_Carrier_Manager::add( $dados );

                    foreach($dados['box_add'] as $costCenter) {
                        Snep_Carrier_Manager::setCostCenter( $idCarrier, $costCenter );
                    }                    
                    $this->_redirect( $this->getRequest()->getControllerName() );
                }
        }
        $this->view->form = $form;

    }

    /**
     * Edit Carrier
     */
    public function editAction() {

        $this->view->breadcrumb = $this->view->translate("Operadoras » Cadastro");

        $db = Zend_Registry::get('db');

        $id = $this->_request->getParam("id");
        
        $this->view->objSelectBox = "carrier";

        $xml = new Zend_Config_Xml( "default/forms/carrier.xml" );

        $carrier = Snep_Carrier_Manager::get($id);

        $form = new Snep_Form( $xml );
        $form->setAction( $this->getFrontController()->getBaseUrl() .'/'. $this->getRequest()->getControllerName() . '/edit');

        $name = $form->getElement('name');
        $name->setLabel( $this->view->translate('Nome') );
        $name->setValue($carrier['nome']);

        $ta = $form->getElement('ta');
        $ta->setLabel( $this->view->translate('Tempo de Arranque') );
        $ta->setValue($carrier['tpm']);

        $tf = $form->getElement('tf');
        $tf->setLabel( $this->view->translate('Tempo de Fracionamento') );
        $tf->setValue($carrier['tdm']);

        $tbf = $form->getElement('tbf');
        $tbf->setLabel( $this->view->translate('Tarifa Base para Fixo') );
        $tbf->setValue($carrier['tbf']);

        $tbc = $form->getElement('tbc');
        $tbc->setLabel( $this->view->translate('Tarifa Base de Celular') );
        $tbc->setValue($carrier['tbc']);

        $_idleCostCenter = Snep_Carrier_Manager::getIdleCostCenter();
        $idleCostCenter = array();
        foreach($_idleCostCenter as $idle) {
            $idleCostCenter[$idle['codigo']] = $idle['codigo'] ." : ". $idle['tipo'] ." - ". $idle['nome'];
        }

        if( isset( $id )) {
            $_selectedCostCenter = Snep_Carrier_Manager::getCarrierCostCenter( $id );
            $selectedCostCenter = array();
            foreach($_selectedCostCenter as $selected) {
                $selectedCostCenter[$selected['codigo']] = $selected['codigo'] ." : ". $selected['tipo'] ." - ". $selected['nome'];
            }            
        }

        $form->setSelectBox( $this->view->objSelectBox,
                             $this->view->translate('Centro de Custos'),
                             $idleCostCenter,                             
                             $selectedCostCenter );

        $formId = new Zend_Form_Element_Hidden('id');
        $formId->setValue($id);
        
        $form->addElement($formId);
        $form->setButtom();

        if($this->_request->getPost()) {

                $form_isValid = $form->isValid($_POST);
                $dados = $this->_request->getParams();

                if($form_isValid) {

                    Snep_Carrier_Manager::edit($dados);

                    if($dados['box_add']) {
                        
                        Snep_Carrier_Manager::clearCostCenter($dados['id']);

                        foreach($dados['box_add'] as $costCenter) {
                            Snep_Carrier_Manager::setCostCenter( $dados['id'], $costCenter );
                        }
                    }
                    
                    $this->_redirect( $this->getRequest()->getControllerName() );
                }
        }
        $this->view->form = $form;
    }

    /**
     * Remove a Carrier
     */
    public function removeAction() {

       $this->view->breadcrumb = $this->view->translate("Operadoras » Remover");
       $id = $this->_request->getParam('id');

       Snep_Carrier_Manager::remove($id);
       
       $this->_redirect( $this->getRequest()->getControllerName() );

    }
    
}