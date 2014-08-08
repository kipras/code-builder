<?php

/**
 * Class CBFactory
 * This is the CodeBuilder class factory, it takes care of constructing CodeBuilder classes
 * and setting up their dependency graph.
 * Whenever one of CodeBuilder classes has to be constructed - you should use this factory for that,
 * instead of manually constructing the class.
 */
class CBFactory
{
    /**
     * @var CBSettings
     */
    private $cbs;


    public function __construct(CBSettings $cbs)
    {
        $this->cbs = $cbs;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // Low level entities


    /**
     * @return CBSettings
     */
    public function settings()
    {
        return $this->cbs;
    }

    /**
     * @param object $parentObject
     * @return CBFinal
     */
    public function finalChecker($parentObject)
    {
        return new CBFinal($this->cbs, $parentObject);
    }

    /**
     * @return CBFile
     */
    public function file()
    {
        return new CBFile($this->cbs);
    }

    /**
     * @return CBFunction
     */
    public function fn()
    {
        return new CBFunction($this->cbs);
    }

    /**
     * @return CBBlock
     */
    public function block()
    {
        return new CBBlock($this->cbs);
    }

    /**
     * @param CBBaseVariable|CBVarPath $listVarPath
     * @param CBVariable $iteratorVar
     * @return CBListIterator|null
     */
    public function listIterator($listVarPath = NULL, CBVariable $iteratorVar = NULL)
    {
        $errorVal = NULL;

        $r = new CBListIterator($this->cbs);

        if ($listVarPath !== NULL) {
            if ($listVarPath instanceof CBBaseVariable) {
                $listVarPath = $this->varPath($listVarPath);
            } else if (FALSE == ($listVarPath instanceof CBVarPath)) {
                $this->cbs->constructionError(__METHOD__.' expects $listVarPath to be CBBaseVariable|CBVarPath');
                return $errorVal;
            }
            $r->setListVarPath($listVarPath);

            if ($iteratorVar !== NULL) {
                $r->setIteratorVar($iteratorVar);
            } else {
                if ($r->getListVarPath()) {
                    $value = $this->newValueOfType($r->getListVarPath()->getType()->itemType);
                    $iteratorVar = $value->assignToNewVar();
                    $iteratorVar->setIsInitialized(FALSE);
                    $r->setIteratorVar($iteratorVar);
                }
            }
        }

        if ($r->makeSureIsValid()) {
            return $r;
        } else {
            return $errorVal;
        }
    }


    /**
     * @return CBVariable
     */
    public function variable()
    {
        return new CBVariable($this->cbs);
    }

    /**
     * @param CBValue $initialValue
     * @return CBMutVar
     */
    public function mutVar(CBValue $initialValue)
    {
        $r = new CBMutVar($this->cbs);
        $r->setInitialValue($initialValue);
        return $r;
    }

    /**
     * @param CBMutVar|CBVarPath $mutVar
     * @return CBMutVarAssignment
     */
    public function mutVarAssignment($mutVar)
    {
        $errorVal = NULL;

        if ($mutVar instanceof CBVarPath) {
            if (FALSE == ($mutVar->getVar() instanceof CBMutVar)) {
                $this->cbs->constructionError("If a CBVarPath is given as \$mutVar - it has to be a selectorPath for a CBMutVar");
                return $errorVal;
            }
            $mutVarPath = $mutVar;
        } else if ($mutVar instanceof CBMutVar) {
            $mutVarPath = $this->varPath($mutVar);
        } else {
            $this->cbs->constructionError(__METHOD__." expects passed \$mutVar to be CBMutVar|CBVarPath");
            return $errorVal;
        }

        $r = new CBMutVarAssignment($this->cbs);
        $r->setMutVarPath($mutVarPath);

        return $r;
    }

    /**
     * @param CBBaseVariable $var
     * @param string[]|CBVariable[] $path A structure selectorPath. Can contain field string names or variables.
     * @return CBVarPath|null
     */
    public function varPath(CBBaseVariable $var, array $path = Array())
    {
        $r = new CBVarPath($this->cbs);
        $r->setVar($var, $path);
        if ($r->isValid()) {
            return $r;
        } else {
            return NULL;
        }
    }


    /**
     * Builds a CBValue from a plain atomic PHP value, with the given type.
     * @param mixed $val
     * @param CBType $type
     * @return CBValue
     * @TODO: Instead of casting strings to the given type -
     * make sure this function checks if the given value is of the correct type.
     * Do NOT allow string values for everything! String values are only allowed when CBType is CB_STRING.
     * Right now we cast to not break existing stuff, because existing CBValue static functions were rerouted
     * through this, but once other regressions are fixed we should change this and update any broken stuff.
     */
    public function atomicValue($val, CBType $type)
    {
        $cbValue = new CBValue($this->cbs);
        $cbValue->type = $type;
        if (is_string($val) AND FALSE == $cbValue->type->isString()) {
            $val = self::castTypeFromString($this->cbs, $val, $cbValue->type);
        }
        $cbValue->val = $val;

        return $cbValue;
    }

    private static function castTypeFromString(CBSettings $cbs, $string, CBType $type)
    {
        if ($type->isInt()) {
            return (int)$string;
        } else if ($type->isFloat()) {
            return (float)$string;
        } else {
            $cbs->error("Don't know how to correctly typecast this value from string", CBSettings::ERROR_UNEXPECTED_TYPE);
            return $string;
        }
    }

    /**
     * Type checks a list of CBValues against a given container type and then constructs a container CBValue for them
     * @param array|null $values
     * @param CBType $type
     * @return CBValue|null
     */
    public function containerValueFromCBValues($values, CBType $type)
    {
        $errorVal = NULL;

        $checkValues = $this->containerValue_checkIfInputIsArrayOrNull($values, $type);
        if ($checkValues !== TRUE) {
            return $checkValues;
        }

        $valueFactory = $this->createContainerValueFactory($type);
        if ($valueFactory === NULL) {
            return $errorVal;
        }

        return $valueFactory->factoryContainerValueFromCBValues($values, $type);
    }

    /**
     * Builds CBValues from given plain values, then type checks them against a given container type
     * and finally - constructs a container CBValue for them.
     * Also, if the container type is nested - makes sure that the given tree of values is fully plain
     * (i.e. that it does not contain CBValues at some level).
     * This is done to ensure that there can only be two input types for container value factories:
     * CBValues and plain values. And that a mixture of the two cannot be possible, or otherwise we would
     * end up with messy data structures.
     * @param array|null $values
     * @param CBType $type
     * @return CBValue|null
     */
    public function containerValueFromPlainValues($values, CBType $type)
    {
        $errorVal = NULL;

        $checkValues = $this->containerValue_checkIfInputIsArrayOrNull($values, $type);
        if ($checkValues !== TRUE) {
            return $checkValues;
        }

        $valueFactory = $this->createContainerValueFactory($type);
        if ($valueFactory === NULL) {
            return $errorVal;
        }

        return $valueFactory->factoryContainerValueFromPlainValues($values, $type);
    }

    /**
     * Returns TRUE if input is an array, otherwise returns a CBValue or NULL on error
     * @param $values
     * @param CBType $type
     * @return bool|CBValue|null
     */
    private function containerValue_checkIfInputIsArrayOrNull($values, CBType $type)
    {
        if ($values === NULL) {
            $cbValue = $this->newValueOfType($type);
            $cbValue->val = NULL;
            return $cbValue;
        } else if (FALSE == is_array($values)) {
            $this->cbs->error(
                __METHOD__.' expects $values to be either an array or null, instead got: '.$this->cbs->util->cb_get_class($values)
                , CBSettings::ERROR_TYPE_SYSTEM);
            return NULL;
        }

        return TRUE;
    }

    /**
     * @param CBType $type
     * @return CBContainerValueFactory|null
     */
    private function createContainerValueFactory(CBType $type)
    {
        if ($type instanceof CBTypeList) {
            return new CBContainerValueListFactory($this->cbs);
        } else if ($type instanceof CBTypeStruct) {
            return new CBContainerValueStructFactory($this->cbs);
        }

        $this->cbs->error(
            'Could not create container value factory for type '.$this->cbs->util->cb_get_class($type).', probably type is not a container'
            , CBSettings::ERROR_UNEXPECTED_TYPE);
        return NULL;
    }


    /**
     * @param string $string
     * @return CBValue
     */
    public function stringValue($string)
    {
        $r = $this->newAtomicValue($this->typeString());
        $r->val = ($string === NULL ? NULL : (string)$string);
        return $r;
    }


    /**
     * @param CBType $type
     * @return CBList|CBStruct|CBValue|null
     */
    public function newValueOfType(CBType $type)
    {
        if ($type instanceof CBTypeList) {
            $r = $this->newListValue($type);
        } else if ($type instanceof CBTypeStruct) {
            $r = $this->newStructValue($type);
        } else if ($type instanceof CBTypeAtomic) {
            $r = $this->newAtomicValue($type);
        } else {
            $this->cbs->unexpectedTypeError("Unknown type: ".$this->cbs->util->cb_get_class($type));
            return NULL;
        }
        return $r;
    }

    /**
     * @param CBTypeAtomic $type
     * @return CBValue
     */
    public function newAtomicValue(CBTypeAtomic $type)
    {
        $r = new CBValue($this->cbs);
        $r->type = $type;
        return $r;
    }

    /**
     * Cannot use 'list' for this method's name, because 'list' is a reserved word in PHP
     * @param CBTypeList $type
     * @return CBList
     */
    public function newListValue(CBTypeList $type)
    {
        $r = new CBList($this->cbs);
        $r->type = $type;
        return $r;
    }

    /**
     * @param CBTypeList $type
     * @return CBList
     */
    public function emptyListValue(CBTypeList $type)
    {
        $r = $this->newListValue($type);
        $r->setVal(Array());
        return $r;
    }

    /**
     * @param CBTypeStruct $type
     * @return CBStruct
     */
    public function newStructValue(CBTypeStruct $type)
    {
        $r = new CBStruct($this->cbs);
        $r->type = $type;
        return $r;
    }


    /**
     * @return CBTypeUnknown
     */
    public function typeUnknown()
    {
        return new CBTypeUnknown($this->cbs);
    }

    /**
     * @param int $typeFlag
     * @return CBTypeAtomic
     */
    public function typeAtomic($typeFlag)
    {
        return new CBTypeAtomic($this->cbs, $typeFlag);
    }

    /**
     * @return CBTypeAtomic
     */
    public function typeString()
    {
        return $this->typeAtomic(CBType::CB_STRING);
    }

    /**
     * @return CBTypeAtomic
     */
    public function typeInt()
    {
        return $this->typeAtomic(CBType::CB_INT);
    }

    /**
     * @return CBTypeAtomic
     */
    public function typeFloat()
    {
        return $this->typeAtomic(CBType::CB_FLOAT);
    }

    /**
     * @return CBTypeAtomic
     */
    public function typeBool()
    {
        return $this->typeAtomic(CBType::CB_BOOL);
    }

    /**
     * @return CBTypeObject
     */
    public function typeObject()
    {
        return new CBTypeObject($this->cbs);
    }

    /**
     * @param CBType|null $itemType
     * @return CBTypeList
     */
    public function typeList(CBType $itemType = NULL)
    {
        $t = new CBTypeList($this->cbs);
        if ($itemType !== NULL) {
            $t->itemType = $itemType;
        }
        return $t;
    }

    /**
     * @param CBType[]|null $fieldTypes
     * @return CBTypeStruct
     */
    public function typeStruct(array $fieldTypes = NULL)
    {
        $r = new CBTypeStruct($this->cbs);
        if ($fieldTypes !== NULL) {
            $r->fieldTypes = $fieldTypes;
        }
        return $r;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // High level (helper) entities


    /**
     * @param CBBlock $parentBlock
     * @param CBBaseVariable $fromVar
     * @param string $selector
     * @return CBSelector|null
     */
    public function selector(CBBlock $parentBlock, CBBaseVariable $fromVar, $selector)
    {
        $r = new CBSelector($this->cbs);
        $r->setParentBlock($parentBlock);
        $r->setFromVar($fromVar);
        $r->setSelector($selector);
        $r->finalize();
        if ($r->isValid()) {
            return $r;
        } else {
            return NULL;
        }
    }

    /**
     * @return CBSelectorPathParser
     */
    public function selectorPathParser()
    {
        return new CBSelectorPathParser($this->cbs);
    }
}
