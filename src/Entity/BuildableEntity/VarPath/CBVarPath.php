<?php

/**
 * Class CBVarPath
 * Allows accessing a variable or something within a variable, e.g. a CBStruct field.
 */
class CBVarPath extends CBBuildableEntity
{
    /**
     * @var CBFinal
     */
    private $final;

    /**
     * @var bool
     */
    private $valid;

    /**
     * @var CBBaseVariable
     */
    private $var;

    /**
     * @var CBVarPathListItem[]
     */
    private $path;

    /**
     * @var CBType Type of the accessed value
     */
    private $type;


    public function __construct(CBSettings $cbs)
    {
        parent::__construct($cbs);

        $this->final = $this->_cbs->factory->finalChecker($this);
    }

    public function __toArray()
    {
        return Array(
            'var' => $this->var,
            'selectorPath' => $this->path,
        );
    }


    /**
     * @return CBBaseVariable|null
     */
    public function getVar()
    {
        if (FALSE == $this->final->isFinal()) {
            $this->_cbs->constructionError($this->_cbs->util->cb_get_class($this).' not fully constructed yet, cannot use it');
            return NULL;
        }
        return $this->var;
    }

    /**
     * @param CBBaseVariable $var
     * @param string[]|CBVariable[] $path A structure selectorPath. Can contain field string names or variables.
     * @return CBVarPath
     */
    public function setVar(CBBaseVariable $var, array $path = Array())
    {
        if (FALSE == $this->final->checkNotFinal()) {
            return $this;
        }

        $type = NULL;
        $pathParsed = $this->parseAndValidatePath($var, $path, $type);
        if ($pathParsed === NULL) {
            $this->valid = FALSE;
            return $this;
        }

        $this->valid = TRUE;
        $this->var = $var;
        $this->path = $pathParsed;
        $this->type = $type;

        $this->final->setFinal();

        return $this;
    }


    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return CBType|null Type of the accessed value
     */
    public function getType()
    {
        if (FALSE == $this->final->isFinal()) {
            $this->_cbs->constructionError($this->_cbs->util->cb_get_class($this).' not fully constructed yet, cannot determine type');
            return NULL;
        }

        return $this->type;
    }


    /**
     * @return CBVariable|null
     */
    public function assignToNewVar()
    {
        if (FALSE == $this->final->isFinal()) {
            $this->_cbs->constructionError($this->_cbs->util->cb_get_class($this).' not fully constructed yet, cannot use it');
            return NULL;
        }

        $refVal = $this->refVal();
        if ($refVal) {
            return $refVal->assignToNewVar();
        }
        return NULL;
    }

    /**
     * @return CBList|CBStruct|CBValue|null A CBValue that refers to this CBVarPath
     */
    public function refVal()
    {
        if (FALSE == $this->final->isFinal()) {
            $this->_cbs->constructionError($this->_cbs->util->cb_get_class($this).' not fully constructed yet, cannot use it');
            return NULL;
        }

        $value = $this->_cbs->factory->newValueOfType($this->getType());
        $value->source = CBValueSource::factoryFromVarPath($this->_cbs, $this);
        return $value;
    }


    /**
     * Makes sure the selectorPath accessor is valid by checking what it contains and then type checking the selectorPath
     * @param CBBaseVariable $var
     * @param string[]|CBVariable[] $path
     * @param CBType $type Determined accessed value type is returned in this param
     * @return bool
     */
    private function parseAndValidatePath(CBBaseVariable $var, array $path, CBType &$type = NULL)
    {
        $errorVal = NULL;

        $type = $var->getType();
        $pathParsed = Array();
        foreach ($path as $p) {
            if ($type instanceof CBTypeStruct) {
                if (is_string($p)) {
                    if ($type->hasField($p)) {
                        $type = $type->getFieldType($p);
                        $pathParsed[] = new CBVarPathStructField($p);
                    } else {
                        $this->_cbs->constructionError(
                            'At this point the selectorPath contains a CBStruct that has no such field "'.$p.'".'
                            .' Struct type: '.$type->toString());
                        return $errorVal;
                    }
                } else { // if (is_string($p))
                    $this->_cbs->constructionError(
                        'Unexpected selectorPath item type '.$this->_cbs->util->cb_get_class($p).' - at this point the selectorPath contains a CBStruct,
                        so only a string is allowed as a selectorPath item for direct field access');
                    return $errorVal;
                }
            } else if ($type instanceof CBTypeList) {
                if ($p instanceof CBValue) {
                    if (FALSE == $p->type->isInt()) {
                        $this->_cbs->constructionError(
                            'Path item is a '.$this->_cbs->util->cb_get_class($p).' of type "'.$p->type->toString().'"'
                            .' but at this point the selectorPath contains a CBList, so only variables of type int'
                            .' are allowed to work as an index for that list');
                        return $errorVal;
                    } else {
                        $pathParsed[] = new CBVarPathListIndex($p);
                    }
                } else if (is_int($p)) {
                    $pathParsed[] = new CBVarPathListIndex($p);
                } else {
                    e($p);
                    $this->_cbs->constructionError(
                        'At this point the selectorPath contains a CBList so only integer constants and'
                        .' values of type int are allowed as a selectorPath item');
                    return $errorVal;
                }
                $type = $type->itemType;
            } else {
                $this->_cbs->constructionError(
                    'Path is invalid - at this point in the selectorPath the accessed '.$this->_cbs->util->cb_get_class($var).' contains'
                    .' something of type "'.$type->toString().'", which cannot be used to continue selectorPath access'
                    .' and has to be used directly instead');
                return $errorVal;
            }

            if (FALSE == (is_string($p) OR $p instanceof CBValue)) {
                $this->_cbs->constructionError($this->_cbs->util->cb_get_class($this)." selectorPath should be a list made of string|CBValue");
                return $errorVal;
            }
        }

        return $pathParsed;
    }


    public function build(CBScope $scope, CBBackend $backend)
    {
        $code = $scope->buildPathToVariable($this->var, $backend);
        if ($code === NULL) {
            return $code;
        }

        foreach ($this->path as $p) {
            if ($p instanceof CBVarPathStructField) {
                $code .= $backend->buildStructFieldAccessor($p->field);
            } else if ($p instanceof CBVarPathListIndex) {
                $index = $p->index;
                if ($index instanceof CBValue) {
                    $index = $index->build($scope, $backend);
                }
                $code .= $backend->buildListIndexAccessor($index);
            }
        }

        return $code;
    }
}
