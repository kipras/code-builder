<?php
class CBTypeStruct extends CBType
{
    /**
     * @var CBType[]
     */
    public $fieldTypes = Array();


    /**
     * @param $fieldName
     * @return bool TRUE if this CBTypeStruct contains a field with this name
     */
    public function hasField($fieldName)
    {
        return $this->getFieldType($fieldName) !== NULL;
    }

    /**
     * @param $fieldName
     * @return CBType|null
     */
    public function getFieldType($fieldName)
    {
        if (array_key_exists($fieldName, $this->fieldTypes)) {
            return $this->fieldTypes[$fieldName];
        } else {
            return NULL;
        }
    }
}
