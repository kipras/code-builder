<?php

class CBListIterator extends CBBlock
{
    /**
     * @var CBVarPath|null Input list fo iterate over
     */
    private $_listVarPath;

    /**
     * @var CBVariable|null Variable of the index iterator (optional).
     * Use it, by just assigning a variable to it, and CBEach will take care of the rest.
     * If you won't assign a variable to $key then no key iterator will be built, as in:
     * foreach ($list as $item)
     */
    private $_indexVar;

    /**
     * @var CBVariable|null Iterator variable
     */
    private $_iteratorVar;


    /**
     * @return CBBaseVariable|null
     */
    public function getListVarPath()
    {
        return $this->_listVarPath;
    }

    /**
     * @param CBVarPath $listVarPath
     * @return CBListIterator
     */
    public function setListVarPath(CBVarPath $listVarPath)
    {
        if ($this->_listVarPath !== NULL) {
            $this->_cbs->constructionError($this->cbs->util->cb_get_class($this).' list to iterate over is already set');
            return $this;
        }
        if ($this->typeCheckListAndIterator_showErrors($listVarPath)) {
            $this->_listVarPath = $listVarPath;
        }

        return $this;
    }


    /**
     * @return CBVariable|null
     */
    public function getIteratorVar()
    {
        return $this->_iteratorVar;
    }

    /**
     * @param CBVariable $iteratorVar
     * @return CBListIterator
     */
    public function setIteratorVar(CBVariable $iteratorVar)
    {
        if ($this->_iteratorVar !== NULL) {
            $this->_cbs->constructionError($this->cbs->util->cb_get_class($this).' iterator variable is already set');
            return $this;
        }
        if ($iteratorVar->getType() instanceof CBTypeUnknown) {
            $this->_cbs->constructionError($this->cbs->util->cb_get_class($this).' iterator variable must have a type set');
            return $this;
        }
        if ($this->typeCheckListAndIterator_showErrors(FALSE, $iteratorVar)) {
            $this->_iteratorVar = $iteratorVar;
            if ($iteratorVar->getParentScope() === NULL) {
                $this->addVar($iteratorVar);
            }
        }

        return $this;
    }


    /**
     * @return CBVariable|null
     */
    public function getIndexVar()
    {
        if ($this->_indexVar === NULL) {
            $value = $this->_cbs->factory->newAtomicValue($this->_cbs->factory->typeInt());
            $indexVar = $value->assignToNewVar();
            $indexVar->setIsInitialized(FALSE);
            $this->setIndexVar($indexVar);
        }

        return $this->_indexVar;
    }

    /**
     * @param CBVariable $indexVar
     * @return CBListIterator
     */
    public function setIndexVar(CBVariable $indexVar)
    {
        if ($this->_indexVar !== NULL) {
            $this->_cbs->constructionError($this->cbs->util->cb_get_class($this).' index variable is already set');
            return $this;
        }
        if ($indexVar->getType() instanceof CBTypeUnknown) {
            $this->_cbs->constructionError($this->cbs->util->cb_get_class($this).' index variable must have a type set');
            return $this;
        }
        if ($this->typeCheckListAndIterator_showErrors(FALSE, FALSE, $indexVar)) {
            $this->_indexVar = $indexVar;
            if ($indexVar->getParentScope() === NULL) {
                $this->addVar($indexVar);
            }
        }

        return $this;
    }


    /**
     * @param CBVarPath|bool $listVarPath
     * @param CBVariable|bool $iteratorVar
     * @param CBVariable|bool $indexVar
     * @return bool TRUE If list, iterator and index variables have no type problems, FALSE otherwise
     */
    private function typeCheckListAndIterator_showErrors($listVarPath = FALSE, $iteratorVar = FALSE, $indexVar = FALSE)
    {
        return $this->typeCheckListAndIterator(TRUE, $listVarPath, $iteratorVar, $indexVar);
    }

    /**
     * @param CBVarPath|bool $listVarPath
     * @param CBVariable|bool $iteratorVar
     * @param CBVariable|bool $indexVar
     * @return bool TRUE If list, iterator and index variables have no type problems, FALSE otherwise
     */
    private function typeCheckListAndIterator_dontShowErrors($listVarPath = FALSE, $iteratorVar = FALSE, $indexVar = FALSE)
    {
        return $this->typeCheckListAndIterator(FALSE, $listVarPath, $iteratorVar, $indexVar);
    }

    /**
     * @param bool $showErrors
     * @param CBVarPath|bool $listVarPath
     * @param CBVariable|bool $iteratorVar
     * @param CBVariable|bool $indexVar
     * @return bool TRUE If list, iterator and index variables have no type problems, FALSE otherwise
     */
    private function typeCheckListAndIterator($showErrors = TRUE, $listVarPath = FALSE, $iteratorVar = FALSE, $indexVar = FALSE)
    {
        if ($listVarPath === FALSE) {
            $listVarPath = $this->_listVarPath;
        }
        if ($indexVar === FALSE) {
            $indexVar = $this->_indexVar;
        }
        if ($iteratorVar === FALSE) {
            $iteratorVar = $this->_iteratorVar;
        }

        if ($listVarPath) {
            $listType = $listVarPath->getType();
            if ($listType instanceof CBTypeList) {
                if ($indexVar) {
                    $indexType = $indexVar->getType();
                    if (FALSE == $indexType->isInt()) {
                        if ($showErrors) {
                            $this->_cbs->constructionError('Iterator index variable type must be int');
                        }
                        return FALSE;
                    }
                }

                if ($iteratorVar) {
                    $iteratorType = $iteratorVar->getType();
                    if (FALSE == $listType->itemType->matchesOrIsSuperTypeOf($iteratorType)) {
                        if ($showErrors) {
                            $this->_cbs->constructionError(
                                'Iterator variable type "'.$iteratorType->toString().'"'
                                .' does not match list type "'.$listType->toString().'"');
                        }
                        return FALSE;
                    }
                }
            } else { // if ($listType instanceof CBTypeList) {
                $this->listTypeError();
                return FALSE;
            }
        }

        return TRUE;
    }


    /**
     * @return bool
     */
    public function makeSureIsValid()
    {
        return $this->checkIsValid(TRUE);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->checkIsValid(FALSE);
    }

    /**
     * @param bool $showErrors
     * @return bool
     */
    private function checkIsValid($showErrors)
    {
        if (FALSE == ($this->_listVarPath instanceof CBVarPath)) {
            if ($showErrors) {
                $this->_cbs->constructionError('List ot iterate over is not set');
            }
            return FALSE;
        }

        if (FALSE == ($this->_iteratorVar instanceof CBVariable)) {
            if ($showErrors) {
                $this->_cbs->constructionError('Iterator variable is not set');
            }
            return FALSE;
        }

        $this->typeCheckListAndIterator($showErrors);

        return TRUE;
    }

    private function listTypeError()
    {
        $this->_cbs->constructionError('Variable to iterate over must contain a list');
    }


    public function build(CBBackend $backend)
    {
        $this->makeSureIsValid();

        $pathToList = $this->_listVarPath->build($this->getParentScope(), $backend);
        if ($pathToList === NULL) {
            $this->_cbs->error("List is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        $pathToIndex = NULL;
        if ($this->_indexVar) {
            $pathToIndex = $this->buildPathToVariable($this->_indexVar, $backend);
            if ($pathToIndex === NULL) {
                $this->_cbs->error("Item '{$this->_indexVar->name}' is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
            }
        }

        $pathToItem = $this->buildPathToVariable($this->_iteratorVar, $backend);
        if ($pathToItem === NULL) {
            $this->_cbs->error("Item '{$this->_iteratorVar->name}' is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        $code  = "foreach ({$pathToList} as " . ($pathToIndex ? "{$pathToIndex} => " : '') . "{$pathToItem})" . $this->_cbs->eol;
        $codeBlock = parent::build($backend);
        if ($this->buildHasBraces == FALSE) {
            $codeBlock = $this->indent(1, $codeBlock);
        }
        $code .= $codeBlock;

        return $code;
    }
}
