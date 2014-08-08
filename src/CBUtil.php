<?php

class CBUtil
{
    /**
     * @var CBSettings
     */
    private $cbs;


    public function __construct(CBSettings $cbs)
    {
        $this->cbs = $cbs;
    }


    public function indent($amount, $code = FALSE)
    {
        $indent = str_repeat($this->cbs->tab, $amount);
        if ($code == FALSE)
            return $indent;

        $lines = explode($this->cbs->eol, $code);
        foreach ($lines as $k => $v)
            if ($v != '')
                $lines[$k] = $indent . $v;

        return join($this->cbs->eol, $lines);
    }

    /**
     * @param array $arr
     * @return bool
     * @link http://stackoverflow.com/a/4254008/532201
     */
    public function arrayIsAssociative(array $arr)
    {
        return (bool)count(array_filter(array_keys($arr), 'is_string'));
    }

    /**
     * @param mixed $val A PHP value
     * @return CBType|null Determined CBType or NULL on error
     */
    public function determineValueType($val)
    {
        $errorVal = NULL;
        if (is_array($val)) {
            if ($this->arrayIsAssociative($val)) {
                $type = $this->cbs->factory->typeStruct();
                foreach ($val as $key => $v) {
                    $type->fieldTypes[$key] = $this->determineValueType($v);
                }
                return $type;
            } else {
                if (count($val) == 0) {
                    $this->cbs->error(
                        "An an empty array value is given, cannot determine type. "
                        ."You will have to manually set the type for this value."
                        , CBSettings::ERROR_AMBIGUOUS_TYPE);
                    return $errorVal;
                }

                // @TODO: should also check if all items in the list are of the same type,
                // i.e. if the list is homogeneous. Lists must be homogeneous.
                $type = $this->cbs->factory->typeList();
                $firstVal = reset($val);
                $type->itemType = $this->determineValueType($firstVal);

                return $type;
            }
        } else {
            return $this->determineAtomicValueType($val);
        }
    }

    /**
     * @param mixed $val
     * @return CBTypeAtomic|null
     */
    private function determineAtomicValueType($val)
    {
        if ($val instanceof CBValue) {
            if ($val->type) {
                return $val->type;
            } else {
                $this->cbs->error("CBValue given with no type set, this should not happen", CBSettings::ERROR_TYPE_SYSTEM);
            }
        } else if (is_int($val)) {
            return $this->cbs->factory->typeInt();
        } else if (is_float($val)) {
            return $this->cbs->factory->typeFloat();
        } else if (is_string($val)) {
            if (is_numeric($val)) {
                if (preg_match(
                    '/^[+-]?(([0-9]+)|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*)'
                    .'|(([0-9]+|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*))[eE][+-]?[0-9]+))$/'
                    , $val))
                {
                    return $this->cbs->factory->typeFloat();
                } else {
                    return $this->cbs->factory->typeInt();
                }
            } else {
                return $this->cbs->factory->typeString();
            }
        } else if ($val === TRUE OR $val === FALSE) {
            return $this->cbs->factory->typeBool();
        } else if ($val === NULL) {
            $this->cbs->error(
                "NULL value given, cannot determine type. You have to manually set the type of this CBValue"
                , CBSettings::ERROR_AMBIGUOUS_TYPE);
        } else {
            $this->cbs->error(
                "Unexpected value given, could not determine type "
                , CBSettings::ERROR_UNEXPECTED_TYPE);
        }

        return NULL;
    }

    /**
     * Returns class name of the given $object. This fixes the problem of
     * standard get_class() where if you passed NULL to it it would return
     * the name of the class from which get_class() was called, because that
     * is the default behavior of that function, and NULL is the default value.
     * However, this leads to difficulties when debugging, because if you don't
     * know this and don't specifically check against NULL - you get a value
     * (the name of the called class) which is misleading.
     * Also, this returns type names for arrays and atomic types, not just
     * objects.
     * @param $object
     * @return string The class of the $object
     */
    public function cb_get_class($object)
    {
        if (is_object($object)) {
            return get_class($object);
        } else {
            return gettype($object);
        }
    }
}
