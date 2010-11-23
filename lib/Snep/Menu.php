<?php
/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */

require "Snep/Menu/Item.php";

/**
 * Classe para controle do Menu do snep.
 *
 * @category  Snep
 * @package   Snep_Bootstrap
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class Snep_Menu {

    /**
     * Id para impressão do id="" do menu
     *
     * @var string id
     */
    private $id;

    /**
     * Itens do menu
     *
     * @var Snep_Menu_Item[]
     */
    private $items;

    /**
     * Define uma URL base para todos os itens de menu que não contiverem uma
     * url completa.
     *
     * @var string
     */
    protected $baseUrl = "";

    /**
     * Arquivo XML para fazer parse do menu
     *
     * @param string $xml_file
     */
    public function __construct( $xml_file = null ) {
        if($xml_file !== null) {
            $this->setItemsFromXML( $xml_file );
        }
    }

    /**
     * Retorna o HTML do menu para impressão
     *
     * @return string
     */
    public function __toString() {
        return $this->render();
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Adiciona um item ao menu
     *
     * @param Snep_Menu_Item $new_item
     */
    public function addItem( Snep_Menu_Item $new_item ) {
        $item = $this->getItemById($new_item->getId());
        if($item) {
            $item->setSubmenu(array_merge($item->getSubmenu(), $new_item->getSubmenu()));
        }
        else {
            $this->items[] = $new_item;
        }
    }

    /**
     * Retorna um item do menu a partir do seu id.
     *
     * @param string $id
     * @return Snep_Menu_Item|null
     */
    public function getItemById( $id ) {
        foreach ($this->getItems() as $item) {
            if( $item->getId() === $id ) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Retorna os itens do menu
     *
     * @return Snep_Menu_Item
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Define os itens do menu
     *
     * @param Snep_Menu_Item[] $items
     */
    public function setItems($items) {
        $this->items = $items;
    }

    /**
     * Retorna o id do menu.
     *
     * @return string id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Define o id do menu
     *
     * @param string $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Define os items do menu a partir de um arquivo XML
     *
     * @param string $xml_file
     */
    public function setItemsFromXML( $xml_file ) {
        if( !file_exists($xml_file) ) {
            throw new PBX_Exception_BadArg("File: $xml_file not found.");
        }
        else {
            // Possibilitando o tratamento dos erros de XML
            libxml_use_internal_errors(true);

            $xml = simplexml_load_file( $xml_file );

            // Tratamento de erros de parse no XML
            if (!$xml) {
                $error_msg = "Malformed XML for $xml_file:\n";
                foreach(libxml_get_errors() as $error) {
                    $error_msg .= $error->message;
                }
                throw new PBX_Exception_BadArg($error_msg);
            }
            // Restaurando o comportamento padrão do php para erros de XML
            libxml_use_internal_errors(false);

            $items = array();
            foreach ($xml->item as $item) {
                $item = $this->parseXMLMenuItem($item);
                if($item !== null) {
                    $items[] = $item;
                }
            }

            $this->setItems( $items );
        }
    }

    private function parseXMLMenuItem( $xml_item ) {
        $id         = $xml_item['id'] ? (string) $xml_item['id'] : null;
        $label      = $xml_item['label'] ? (string) $xml_item['label'] : null;

        $uri        = $xml_item['uri'] ? $xml_item['uri'] : null;
        $resourceId = $xml_item['resourceid'] ? (string) $xml_item['resourceid'] : null;
        
        $item = new Snep_Menu_Item($id, $label, $uri);
        $item->setResourceId($resourceId);

        if( count($xml_item->item) > 0 ) {
            foreach ($xml_item->item as $xml_subitem) {
                $item->addSubmenuItem($this->parseXMLMenuItem($xml_subitem));
            }
        }

        return $item;
    }

    /**
     * Verifica se um usuário tem permissão a acessar determinado recurso do sistema
     *
     * @param string $resourceId id do recurso
     * @return boolean isAllowed
     */
    private function isAllowed( $resourceId ) {
        global $id_user;
        
        if ($id_user == 1) {
            return true;
        }
        else if($id_user === null) {
            return false;
        }

        $db = Zend_Registry::get('db');

        $sql_ver = "SELECT permissao FROM permissoes";
        $sql_ver.= " WHERE cod_usuario = '$id_user' AND cod_rotina = '$resourceId'";

        $row = $db->query($sql_ver)->fetchObject();

        if( $row->permissao == "S" ) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Gambiarra violentíssima. Corrige as URL's para que o ambiente de módulos
     * antigos possam conviver com os novos módulos que usam o
     * Zend_Front_Controller. Deve ser removido assim que esses modulos antigos
     * forem atualizados.
     *
     * @TODO Atualizar módulos e dependencias para estrutura Zend e remover
     * esse método.
     */
    private function fixUrl(Snep_Menu_Item $item) {
        if(!Zend_Uri::check($item->getUri())) {
            if(substr($item->getUri(), -4) == ".php") {
                $fixed_uri = $this->baseUrl . $item->getUri();
            } elseif(preg_match("/^javascript:/i", $item->getUri())) {
                $fixed_uri = $item->getUri();
            } else {
                $config = Zend_Registry::get('config');
                $fixed_uri = $this->baseUrl . ($config->system->path->modrewrite ? "" : "index.php/") . $item->getUri();
            }
        }
        else {
            $fixed_uri = $item->getUri();
        }
        $item->setUri($fixed_uri);

        $submenu = $item->getSubmenu();
        if(count($submenu) > 0) {
            foreach ($submenu as $submenu_item) {
                $this->fixUrl($submenu_item);
            }
        }
    }

    /**
     * Percorre os itens do menu e cria um HTML para impressão.
     *
     * O html segue a estrutura de listas desordenadas aninhadas.
     *
     * @return string HTML para impressão do menu
     */
    public function render() {
        $items = "";

        foreach ($this->getItems() as $item) {
            $this->fixUrl($item);
            if( $item->getId() == "logout" ) {
                $logout = $item;
            }
            else if( $item->getResourceId() == "" || $this->isAllowed($item->getResourceId()) ) {
                $items = $item->render() . $items;
            }
        }
        $items = $logout->render() . $items;

        if($this->id !== null) {
            return "<ul id='{$this->getId()}'>" . $items . "</ul>";
        }
        else {
            return "<ul>" . $items . "</ul>";
        }
    }

}
