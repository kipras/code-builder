<?php

/**
 * Class CBStruct
 * @property CBTypeStruct $type
 */
class CBStruct extends CBValue
{
    public function __construct(CBSettings $cbs)
    {
        parent::__construct($cbs);

        $this->type = new CBTypeStruct($cbs);
    }


    /**
     * @param CBSettings $cbs
     * @param CBValue[] $mapOfCBValue
     * @return CBList
     */
    public static function factoryFromValueMap(CBSettings $cbs, array $mapOfCBValue)
    {
        $cbStruct = new CBStruct($cbs);
        $cbStruct->type = self::makeTypeFromMapOfCBValue($cbs, $mapOfCBValue);

        $cbStruct->val = $mapOfCBValue;

        return $cbStruct;
    }

    /**
     * @param CBSettings $cbs
     * @param CBValue[] $mapOfCBValue
     * @return CBTypeStruct
     */
    private static function makeTypeFromMapOfCBValue(CBSettings $cbs, array $mapOfCBValue)
    {
        $fieldTypes = Array();
        foreach ($mapOfCBValue as $key => $val) {
            $fieldTypes[$key] = $val->type;
        }

        $type = new CBTypeStruct($cbs);
        $type->fieldTypes = $fieldTypes;

        return $type;
    }

    /**
     * @param string $key
     * @return CBValue|null
     */
    public function getValueForKey($key)
    {
        $errorVal = NULL;

        if ($this->source AND $this->source->isForeign()) {
            // @TODO: later this check should be done for all values, not only foreign.
            // For now this check is only done here to not break existing things.
            if (! $this->type OR ! array_key_exists($key, $this->type->fieldTypes)) {
                $this->_cbs->error(
                    "The type of this ".get_class($this)." does not have a value set for key '{$key}'"
                    , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                return $errorVal;
            }

            $cbValue = new CBValue($this->_cbs);
            $cbValue->type = $this->type->fieldTypes[$key];
            $cbValue->source = CBValueSource::factoryFromForeignStruct($this->_cbs, $this, $key);

            return $cbValue;
        }

        if (! array_key_exists($key, $this->val)) {
            $this->_cbs->error(
                "This ".__CLASS__." does not have a value set for key '{$key}'"
                , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
            return $errorVal;
        }

        return $this->val[$key];
    }

    /**
     * @param string $key
     * @param CBValue|mixed $value
     */
    public function setValueForKey($key, $value)
    {
        if (isset($this->type->fieldTypes[$key])) {
            $this->_cbs->error(
                "This ".__CLASS__." already has a value set for key '{$key}'"
                , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
            return;
        }

        if ($value instanceof CBValue) {
            $type = $value->type;
        } else {
            $type = $this->_cbs->util->determineValueType($value);
        }

        $this->type->fieldTypes[$key] = $type;

        $val = $this->val;
        $val[$key] = $value;
        $this->setVal($val);
    }


    protected function buildVal(CBScope $scope, CBBackend $backend)
    {
        return $backend->buildStructInitializer($this->val, $scope);
    }
}
