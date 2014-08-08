<?php

/**
 * Class CBList
 * @property CBTypeList $type
 */
class CBList extends CBValue
{
    public function __construct(CBSettings $cbs)
    {
        parent::__construct($cbs);

        $this->type = new CBTypeList($cbs);
    }


    /**
     * @param CBSettings $cbs
     * @param CBValue[] $listOfCBValue
     * @return CBList|null
     */
    public static function factoryFromValueList(CBSettings $cbs, array $listOfCBValue)
    {
        $errorVal = NULL;

        if (count($listOfCBValue) == 0) {
            $cbs->error(
                "An an empty array value is given, cannot determine type. "
                ."You will have to manually set the type for this value."
                , CBSettings::ERROR_AMBIGUOUS_TYPE);
            return $errorVal;
        }

        // @TODO: should also check if all items in the list are of the same type,
        // i.e. if the list is homogeneous. Lists must be homogeneous.
        $firstItem = reset($listOfCBValue);

        return self::factoryFromValueListAndItemType($cbs, $listOfCBValue, $firstItem->type);
    }

    /**
     * @param CBSettings $cbs
     * @param array $listOfCBValue
     * @param CBType $itemType
     * @return CBList
     * @TODO: this should also check to make sure the list is homogeneous, i.e. that all items are of the same type
     */
    public static function factoryFromValueListAndItemType(CBSettings $cbs, array $listOfCBValue, CBType $itemType)
    {
        $cbList = new CBList($cbs);

        $type = new CBTypeList($cbs);
        $type->itemType = $itemType;
        $cbList->type = $type;

        // Make sure there are no index "holes" - CBList lists cannot have them
        $cbList->val = array_values($listOfCBValue);

        return $cbList;
    }


    protected function buildVal(CBScope $scope, CBBackend $backend)
    {
        return $backend->buildListInitializer($this->val, $scope);
    }
}
