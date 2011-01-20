<?php
/**
 * Description of Contacts
 * 
 * @author elton
 */
class Snep_Field_Manager {
	
    /**
     * Filtra campos para listagem no index.
     * 
     * @param $id
     */
    public static function getFilter ($field, $query) {

        $db = Zend_Registry::get('db');
        $select = $db->select()
        			 ->from(array('f' => 'ad_contact_field'));            
        			 
        if (!is_null($query)) {
                $select->where ("$field like '%$query%'");
        }

        return $select;
    }
    
    /**
     * Busca dados do campo pelo id.
     * 
     * @param $id
     * @return $registros
     */
    public static function get($id) {
        $db = Zend_Registry::get('db');
        $select = $db->select()
                        ->from(array('c' => 'ad_contact_field'))
                        ->where('c.id = ?', $id);

        $stmt = $db->query($select);
        $registros = $stmt->fetch();

        return $registros;
    }

   /**
     * Atualiza informações de campo no banco de dados.
     *
     * @param array $contact
     */
    public static function edit($field) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        $value = array(
            'name' => $field['name'],
            'type' => $field['type'],
        	'required' => $field['required']
        );

        $db->update('ad_contact_field', $value, "id='{$field['id']}'");
        
        try {
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }
    
	/**
    * Exclui informações de campo dinamico no banco de dados.
    *
    * @param array $field
    */
    public static function del($field) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {
            $db->delete('ad_contact_field_value', "field = $field");
            $db->delete('ad_contact_field', "id = $field");
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return $e;
        }
    }
    
	// retorna fields do contato
    public static function getFields($value, $id) {

        $db = Zend_Registry::get('db');
        if ($value) {
        	$select = $db->select()
                     ->from(array('c' => 'ad_contact'))
                     ->join(array('v' => 'ad_contact_field_value'), 
                     			  'c.id = v.contact')
                     ->join(array('f' => 'ad_contact_field'),
                     			 'f.id = v.field')	
                     ->where('c.id = ?', $id);                     
        } else {
        	$select = $db->select()
                     ->from(array('f' => 'ad_contact_field'));
        }
        
        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

    public function insertDynElements($subForm, $id)
    {
    	$value = true;
    	if ( is_null( $id ) ) {
    		$value = false;
    	} else {
    		$fields2 = Snep_Field_Manager::getFields($value, $id);
    	}

    	$fields = Snep_Field_Manager::getFields(false, null);

        foreach ($fields as $f) {

        	$element = $subForm->createElement($f['type'], $f['id'])
						 ->setLabel($f['name'])
						 ->setDecorators(array(
            			 	'ViewHelper',
            				'Description',
            				'Errors',
            				array(array('dd' => 'HtmlTag'), array('tag' => 'dd')),
            				array('Label', array('tag' => 'dt')),
            				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class'=>'form_element'))
        				));
			$element->addValidators(array(
    					array('NotEmpty', true)
						));

			if ($f['required']) {
				$element->setRequired(true);
			}
			if ($value) {
				foreach ($fields2 as $f2) {
					if ($f2['name'] == $f['name']) {
						$element->setValue($f2['value']);
					}
				}
			}

       		$subForm->addElement($element);
        }

    }
    
}
