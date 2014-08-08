<?php

/**
 * Class CBMutVarAssignment
 * CodeBuilder entity which contains an assignment of some value into a CBMutVar.
 */
class CBMutVarAssignment extends CBBuildableEntity
{
    const TYPE_ASSIGN = 1;
    const TYPE_ADD = 2;
    const TYPE_SUBTRACT = 3;
    const TYPE_LIST_APPEND = 4;
    const TYPE_LIST_MERGE = 5;


    /**
     * @var CBVarPath The left side of the assignment: variable to assign to
     */
    private $mutVarPath;

    /**
     * @var int Type of assignment (one of TYPE_ constants), defaults to a simple assignment
     */
    private $type = self::TYPE_ASSIGN;

    /**
     * @var CBValue The right side of the assignment: value to assign to the variable
     */
    private $value;


    /**
     * @return CBMutVar
     */
    public function getMutVarPath()
    {
        return $this->mutVarPath;
    }

    public function setMutVarPath(CBVarPath $mutVarPath)
    {
        $this->mutVarPath = $mutVarPath;
    }


    /**
     * @return int Type of assignment (one of TYPE_ constants), defaults to a simple assignment
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type Type of assignment (one of TYPE_ constants)
     */
    private function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return CBMutVarAssignment
     */
    public function setTypeAssign()
    {
        $this->setType(self::TYPE_ASSIGN);
        return $this;
    }

    /**
     * @return CBMutVarAssignment
     */
    public function setTypeAdd()
    {
        $this->setType(self::TYPE_ADD);
        return $this;
    }

    /**
     * @return CBMutVarAssignment
     */
    public function setTypeSubtract()
    {
        $this->setType(self::TYPE_SUBTRACT);
        return $this;
    }

    /**
     * @return CBMutVarAssignment
     */
    public function setTypeListAppend()
    {
        if (FALSE == $this->mutVarPath->getType()->isList()) {
            $this->_cbs->constructionError(
                'A list append assignment expects result variable to contain a list, instead it contains '
                .$this->mutVarPath->getType()->toString());
        }
        $this->setType(self::TYPE_LIST_APPEND);
        return $this;
    }

    /**
     * @return CBMutVarAssignment
     */
    public function setTypeListMerge()
    {
        if (FALSE == $this->mutVarPath->getType()->isList()) {
            $this->_cbs->constructionError(
                'A list merge assignment expects result variable to contain a list, instead it contains '
                .$this->mutVarPath->getType()->toString());
        }
        $this->setType(self::TYPE_LIST_MERGE);
        return $this;
    }


    /**
     * @return CBValue
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setValue(CBValue $value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setAssignValue(CBValue $value)
    {
        $this->setTypeAssign();
        $this->setValue($value);
        return $this;
    }

    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setAddValue(CBValue $value)
    {
        $this->setTypeAdd();
        $this->setValue($value);
        return $this;
    }

    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setSubtractValue(CBValue $value)
    {
        $this->setTypeSubtract();
        $this->setValue($value);
        return $this;
    }

    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setListAppendValue(CBValue $value)
    {
        $this->setTypeListAppend();
        $this->setValue($value);
        return $this;
    }

    /**
     * @param CBValue $value
     * @return CBMutVarAssignment
     */
    public function setListMergeWithValue(CBValue $value)
    {
        if (FALSE == $value->type->isList()) {
            $this->_cbs->constructionError(
                'A list merging assignment expects $value to be a list, instead got '.$value->type->toString());
            return $this;
        }

        $this->setTypeListMerge();
        $this->setValue($value);
        return $this;
    }


    public function build(CBScope $scope, CBBackend $backend)
    {
        $mutVarPathBuilt = $this->mutVarPath->build($scope, $backend);
        if ($mutVarPathBuilt === NULL) {
            return NULL;
        }

        $valueBuilt = $this->value->build($scope, $backend);
        if ($valueBuilt === NULL) {
            $this->_cbs->constructionError(
                "Failed to build right side value of this ".$this->cbs->util->cb_get_class($this));
        }

        return $this->buildAssignment($backend, $mutVarPathBuilt, $valueBuilt);
    }

    private function buildAssignment(CBBackend $backend, $mutVarPathBuilt, $valueBuilt)
    {
        if ($this->type == self::TYPE_ASSIGN) {
            return $backend->buildAssignment($mutVarPathBuilt, $valueBuilt);
        } else if ($this->type == self::TYPE_ADD) {
            return $backend->buildAddAssignment($mutVarPathBuilt, $valueBuilt);
        } else if ($this->type == self::TYPE_SUBTRACT) {
            return $backend->buildSubtractAssignment($mutVarPathBuilt, $valueBuilt);
        } else if ($this->type == self::TYPE_LIST_APPEND) {
            return $backend->buildAddToList($mutVarPathBuilt, $valueBuilt);
        } else if ($this->type == self::TYPE_LIST_MERGE) {
            return $backend->buildMergeLists($mutVarPathBuilt, $valueBuilt);
        } else {
            $this->_cbs->constructionError(
                "Unknown ".$this->cbs->util->cb_get_class($this)." type");
            return NULL;
        }
    }
}
