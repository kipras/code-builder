<?php

/**
 * Class CBContainerValueFactory
 * Abstract factory for container type values
 */
abstract class CBContainerValueFactory
{
    /**
     * @var CBSettings
     */
    protected $cbs;


    public function __construct(CBSettings $cbs)
    {
        $this->cbs = $cbs;
    }


    /**
     * @param array $values
     * @param CBType $type
     * @return CBValue|null
     */
    public function factoryContainerValueFromCBValues(array $values, CBType $type)
    {
        $errorVal = NULL;

        if (FALSE == $this->validateInputCBValueTypes($values, $type)) {
            return $errorVal;
        }

        return $this->makeContainerCBValueForInnerCBValues($values, $type);
    }

    /**
     * @param array $values
     * @param CBType $type
     * @return CBValue|null
     */
    public function factoryContainerValueFromPlainValues(array $values, CBType $type)
    {
        $errorVal = NULL;

        if (FALSE == $this->validateValuesArePlain($values)) {
            return $errorVal;
        }

        $cbValueList = $this->buildCBValuesFromPlainValues($values, $type);
        if ($cbValueList === NULL) {
            return $errorVal;
        }

        return $this->factoryContainerValueFromCBValues($cbValueList, $type);
    }

    private function validateValuesArePlain(array $values)
    {
        foreach ($values as $v) {
            if ($v instanceof CBValue) {
                $this->cbs->error(
                    'Value was supposed to be a plain PHP value, instead a '.$this->cbs->util->cb_get_class($v).' was passed'
                    , CBSettings::ERROR_TYPE_SYSTEM);
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Build resulting CBValue. When calling this method input $values are already confirmed to be of valid type,
     * so no type checking is performed.
     * @param array $values
     * @param CBType $type
     * @return CBValue
     */
    protected function makeContainerCBValueForInnerCBValues(array $values, CBType $type)
    {
        $cbValue = $this->cbs->factory->newValueOfType($type);
        $cbValue->val = $values;

        return $cbValue;
    }

    /**
     * @param $innerValue
     * @param CBType $innerValueType
     * @return CBValue|null
     */
    protected function makeInnerCBValueFromPlainValue($innerValue, CBType $innerValueType)
    {
        if ($innerValueType->isAtomic()) {
            if (is_array($innerValue)) {
                $this->cbs->error(
                    'Expected an '.$innerValueType->toString().', but an array was encountered instead'
                    , CBSettings::ERROR_TYPE_SYSTEM);
            }
            return $this->cbs->factory->atomicValue($innerValue, $innerValueType);
        } else {
            if (! is_array($innerValue)) {
                $this->cbs->error(
                    'Expected an array, but a '.$this->cbs->util->cb_get_class($innerValue).' was encountered instead'
                    , CBSettings::ERROR_TYPE_SYSTEM);
                return NULL;
            }
            return $this->cbs->factory->containerValueFromPlainValues($innerValue, $innerValueType);
        }
    }

    /**
     * @param array $values
     * @param CBType $type
     * @return bool TRUE if input value types match the given $type signature, FALSE otherwise
     */
    abstract protected function validateInputCBValueTypes(array $values, CBType $type);

    /**
     * Build a list of CBValues from given plain values. At this point input values are confirmed to be plain.
     * Should not do type consistency validation (validation of input types against given $type) -
     * it will be done later, but can do it if it is necessary.
     * Should use ->makeInnerCBValueFromPlainValue() to build the actual CBValues
     * @param array $values
     * @param CBType $type
     * @return CBValue[]|null
     */
    abstract protected function buildCBValuesFromPlainValues(array $values, CBType $type);
}
