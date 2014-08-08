<?php

/**
 * Class CBValue
 * @property CBValueSource $source
 * @property mixed $val
 */
class CBValue extends CBBuildableEntity
{
    /**
     * @var CBValueSource A value will have a source if it is the result of a function call or taken from another
     * CBVariable, in which case it is not a constant value but a redirecting value and $val will be NULL
     */
    private $_source;

    /**
     * @var CBType Type information for this CBValue
     */
    public $type;

    /**
     * @var mixed The actual PHP value stored in this CBValue
     */
    private $_val;


    public function __toArray()
    {
        return Array(
            'source' => $this->source,
            'val' => $this->_val,
        );
    }

    public function __get($name)
    {
        switch ($name)
        {
            case 'source':
                return $this->_source;
                break;

            case 'val':
                return $this->_val;
                break;

            default:
                return parent::__get($name);
                break;
        }
    }
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'source':
                if ($value instanceof CBValueSource) {
                    if ($this->_source !== NULL) {
                        $this->_cbs->error(
                            "This CBValue already refers to something - cannot change it, "
                            . "because CBValue is immutable", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                    } else {
                        $this->_source = $value;
                    }
                } else {
                    $this->_cbs->error("CBValue->source must be a CBValueSource", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                }
                break;

            case 'val':
                $this->setVal($value);
                break;

            default:
                return parent::__get($name);
                break;
        }
    }

    /**
     * Returns the appropriate CBValue object for this value.
     * Tries to determine the type of the given value and sets corresponding type information.
     * Returns NULL if type information cannot be determined. This will happen if the value contains an empty array(s).
     * @param CBSettings $cbs
     * @param mixed $val
     * @return CBList|CBStruct|CBValue|null
     * @deprecated Should use CBFactory->atomicValue(), CBFactory->containerValueFromPlainValues(),
     * CBFactory->containerValueFromCBValues()
     */
    public static function factoryFromValue(CBSettings $cbs, $val)
    {
        $errorVal = NULL;

        if ($val instanceof CBVariable) {
            return $val->refVal();
        } else if ($val instanceof CBValue) {
            return $val;
        }

        $type = $cbs->util->determineValueType($val);
        if (! $type) {
            return $errorVal;
        }

        return self::factoryFromValueAndType($cbs, $val, $type);
    }

    /**
     * @param CBSettings $cbs
     * @param mixed $val
     * @param CBType $type
     * @return CBList|CBStruct|CBValue|null
     * @deprecated Should use CBFactory->atomicValue(), CBFactory->containerValueFromPlainValues(),
     * CBFactory->containerValueFromCBValues()
     */
    public static function factoryFromValueAndType(CBSettings $cbs, $val, CBType $type)
    {
        $errorVal = NULL;

        if (is_array($val)) {
            if ($cbs->util->arrayIsAssociative($val)) {
                if (FALSE == $type->isStruct()) {
                    $cbs->error(
                        "When an associative array is given - the container type should be CBStruct"
                        , CBSettings::ERROR_TYPE_SYSTEM);
                    return $errorVal;
                }

                $cbValueList = Array();
                foreach ($val as $key => $v) {
                    /* @var CBTypeStruct $type */
                    if (! isset($type->fieldTypes[$key])) {
                        $cbs->error(
                            "The given CBStruct type does not contain a field '{$key}'"
                            , CBSettings::ERROR_TYPE_SYSTEM);
                        return $errorVal;
                    }

                    $cbValueList[$key] = self::factoryFromValueAndType($cbs, $v, $type->fieldTypes[$key]);
                }
                return CBStruct::factoryFromValueMap($cbs, $cbValueList);
            } else {
                if (FALSE == $type->isList()) {
                    $cbs->error(
                        "When a non-associative array is given - the container type should be CBList"
                        , CBSettings::ERROR_TYPE_SYSTEM);
                    return $errorVal;
                }

                $cbValueList = Array();
                foreach ($val as $v) {
                    /* @var CBTypeList $type */
                    $cbValueList[] = self::factoryFromValueAndType($cbs, $v, $type->itemType);
                }
                return CBList::factoryFromValueListAndItemType($cbs, $cbValueList, $type->itemType);
            }
        }

        return $cbs->factory->atomicValue($val, $type);
    }


    /**
     * Sets the actual value stored in this CBValue. If this CBValue is a container - makes sure the contained
     * values are also instances of CBValue and not atomic values, otherwise we will have type system issues.
     * @param mixed $value
     */
    public function setVal($value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                if ($v !== NULL AND FALSE == ($v instanceof CBValue)) {
                    $this->_cbs->error("A container CBValue must contain other CBValues and not atomic types", CBSettings::ERROR_TYPE_SYSTEM);
                }
            }
        }

        $this->_val = $value;
    }

    /**
     * @return CBValue A new CBValue of the same type as this CBValue
     */
    public function newVal()
    {
        $class = get_class($this);
        $newVal = new $class($this->_cbs);
        $newVal->type = $this->type;

        return $newVal;
    }


    /**
     * Used for explicit type casts
     * @return CBObject
     */
    public function toObject()
    {
        if ($this instanceof CBObject) {
            return $this;
        }

        $obj = new CBObject($this->_cbs);
        $obj->copyValue($this);

        return $obj;
    }

    /**
     * Used for explicit type casts
     * @return CBList
     */
    public function toList()
    {
        if ($this instanceof CBList) {
            return $this;
        }

        $struct = new CBList($this->_cbs);
        $struct->copyValue($this);

        return $struct;
    }

    /**
     * Used for explicit type casts
     * @return CBStruct
     */
    public function toStruct()
    {
        if ($this instanceof CBStruct) {
            return $this;
        }

        $struct = new CBStruct($this->_cbs);
        $struct->copyValue($this);

        return $struct;
    }


    /**
     * This is used by explicit casting methods (e.g. ->toObject(), toStruct()) to clone the data of
     * an existing CBValue into this CBValue (which will be of an explicit subtype)
     * @param CBValue $fromValue
     */
    private function copyValue(CBValue $fromValue)
    {
        $this->_source = $fromValue->_source;
        $this->val = $fromValue->val;
        $this->type = $fromValue->type;
    }


    /**
     * @return CBVariable A new no-name variable, that contains this value.
     */
    public function assignToNewVar()
    {
        $var = new CBVariable($this->_cbs);
        $var->value = $this;

        return $var;
    }

    /**
     * @return CBMutVar A new no-name mutable variable, that is initialized with this value
     */
    public function assignToNewMutVar()
    {
        return $this->_cbs->factory->mutVar($this);
    }

    /**
     * @return CBValue A new CBValue, that points to this CBValue. This is useful for creating structures that extend
     * other structures.
     */
    public function assignToNewValue()
    {
        // NOTE that we don't copy $this->val to $newVal->val, because if the value of $newVal is used, it should
        // automatically be taken from this CBValue, because the source of $newVal is this CBValue
        $newVal = $this->newVal();
        $newVal->source = CBValueSource::factoryFromValue($this->_cbs, $this);

        return $newVal;
    }


    /**
     * The function is declared final to make sure that extending value types don't break this method.
     * Extending value types should implement ->buildVal() instead
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @return string
     */
    final public function build(CBScope $scope, CBBackend $backend)
    {
        if ($this->_source) {
            return $this->_source->build($scope, $backend);
        } else {
//            // CB_LIST and CB_STRUCT type values are handled here, because both of them are internally stored
//            // as a PHP array and would be handled as a CB_STRUCT
//            // TODO: perhaps all value types should be handled here, i.e. we should look at CBValue->type->typeFlag
//            // when determining value type, instead of determining it at runtime, using is_string() and similar functions?
//            // TODO: It should not be possible for $this->type to be NULL
//            if ($this->type) {
//                if ($this->type->isList()) {
//                    return $backend->buildListInitializer($this->val, $scope);
//                } else if ($this->type->isStruct()) {
//                    return $backend->buildStructInitializer($this->val, $scope);
//                }
//            }

            if ($this->val === NULL) {
                return $backend->buildVal(NULL, $scope);
            } else {
                return $this->buildVal($scope, $backend);
            }
        }
    }

    /**
     * Builds an atomic value. This method is overridden by specific value type classes to build specific values.
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @return string
     */
    protected function buildVal(CBScope $scope, CBBackend $backend)
    {
        $val = $this->typeCastAtomicValue($this->val);
        return $backend->buildVal($val, $scope);
    }

    private function typeCastAtomicValue()
    {
        if ($this->val === NULL) {
            return $this->val;
        }

        if ($this->type instanceof CBTypeAtomic) {
            switch ($this->type->typeFlag) {
                case CBType::CB_STRING:
                    return (string)$this->val;

                case CBType::CB_INT:
                    return (int)$this->val;

                case CBType::CB_FLOAT:
                    return (float)$this->val;

                case CBType::CB_BOOL:
                    return ($this->val ? TRUE : FALSE);
            }
        }
    }
} 
