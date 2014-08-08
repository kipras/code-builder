<?php
class CBContainerValueStructFactory extends CBContainerValueFactory
{
    protected function validateInputCBValueTypes(array $values, CBType $type)
    {
        /* @var CBTypeStruct $type */

        foreach ($type->fieldTypes as $field => $fieldType) {
            if (FALSE == array_key_exists($field, $values)) {
                $this->expectedValueError($field, $fieldType);
                return FALSE;
            }
        }

        foreach ($values as $field => $v) {
            if (FALSE == isset($type->fieldTypes[$field])) {
                $this->unexpectedValueError($field);
                return FALSE;
            }

            $expectedType = $type->fieldTypes[$field];
            if (FALSE == ($v->type->isTypeIdentical($expectedType))) {
                $this->cbs->error(
                    'Structure item for field "'.$field.'" is of type '.$v->type->toString().', expected type: '.$expectedType->toString()
                    , CBSettings::ERROR_TYPE_SYSTEM);
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function buildCBValuesFromPlainValues(array $values, CBType $type)
    {
        /* @var CBTypeStruct $type */
        $errorVal = NULL;

        foreach ($type->fieldTypes as $field => $fieldType) {
            if (FALSE == array_key_exists($field, $values)) {
                $this->expectedValueError($field, $fieldType);
                return $errorVal;
            }
        }

        $cbValueList = Array();
        foreach ($values as $field => $v) {
            if (FALSE == isset($type->fieldTypes[$field])) {
                $this->unexpectedValueError($field);
                return $errorVal;
            }

            $val = $this->makeInnerCBValueFromPlainValue($v, $type->fieldTypes[$field]);
            if ($val === NULL) {
                return $errorVal;
            }
            $cbValueList[$field] = $val;
        }

        return $cbValueList;
    }

    private function unexpectedValueError($field)
    {
        $this->cbs->error('An unexpected value was set for struct field "'.$field.'"', CBSettings::ERROR_TYPE_SYSTEM);
    }

    private function expectedValueError($field, CBType $fieldType)
    {
        $this->cbs->error(
            'No value was given for struct field "'.$field.'".'
            .' This struct expects a value of type '.$fieldType->toString().' for this field.'
            , CBSettings::ERROR_TYPE_SYSTEM);
    }
}
