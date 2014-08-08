<?php
class CBContainerValueListFactory extends CBContainerValueFactory
{
    protected function validateInputCBValueTypes(array $values, CBType $type)
    {
        /* @var CBTypeList $type */
        foreach ($values as $v) {
            if (FALSE == ($v->type->isTypeIdentical($type->itemType))) {
                $this->cbs->error(
                    'List item is of type '.$v->type->toString().', expected type: '.$type->itemType->toString()
                    , CBSettings::ERROR_TYPE_SYSTEM);
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function buildCBValuesFromPlainValues(array $values, CBType $type)
    {
        /* @var CBTypeList $type */
        $cbValueList = Array();
        foreach ($values as $k => $v) {
            $val = $this->makeInnerCBValueFromPlainValue($v, $type->itemType);
            if ($val === NULL) {
                return NULL;
            }
            $cbValueList[$k] = $val;
        }
        return $cbValueList;
    }
}
