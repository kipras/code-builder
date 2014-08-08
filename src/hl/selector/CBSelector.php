<?php

/**
 * Class CBSelector
 * This is a high-level CodeBuilder entity that allows creating new variables by selecting inner items
 * of lists and structures from existing variables.
 */
class CBSelector extends CBEntity
{
    /**
     * @var CBSelectorPathParser
     */
    private $pathParser;

    /**
     * @var CBFinal
     */
    private $final;

    /**
     * @var CBBaseVariable
     */
    private $fromVar;

    /**
     * @var CBBlock
     */
    private $parentBlock;

    /**
     * @var string Selector string
     */
    private $selector;

    /**
     * @var CBSelectorToken[]|null Parsed selector, set automatically by ->finalize()
     */
    private $parsedSelector;

    /**
     * @var CBBaseVariable Cache for ->resVar()
     */
    private $_resVar;

    /**
     * @var bool TRUE if this CBSelector was successfully built, FALSE on error
     */
    private $valid;

    /**
     * @var bool TRUE if the result of this CBSelector was taken and so the selector code should actually be generated
     */
    private $resultUsed = FALSE;

    /**
     * @var CBVarPath
     */
    private $resultVarPath;

    /**
     * @var CBListIterator|null CBSelector implementation top level list iterator. It can contain other nested iterators.
     */
    private $rootIterator;

    /**
     * @var CBMutVarAssignment|CBListIterator|false|null CBSelector implementation - a loop or a single assignment
     */
    private $implementation;

    /**
     * @var CBType
     */
    private $resultType;


    protected function init()
    {
        $this->pathParser = $this->_cbs->factory->selectorPathParser();
        $this->final = $this->_cbs->factory->finalChecker($this);
    }


    public function setFromVar(CBBaseVariable $fromVar)
    {
        if (FALSE == $this->final->checkNotFinal()) {
            return;
        }
        $this->fromVar = $fromVar;
    }

    /**
     * Set the block in which this CBSelector should run
     * @param CBBlock $parentBlock
     */
    public function setParentBlock(CBBlock $parentBlock)
    {
        if (FALSE == $this->final->checkNotFinal()) {
            return;
        }
        $this->parentBlock = $parentBlock;
    }

    public function setSelector($selector)
    {
        if (FALSE == $this->final->checkNotFinal()) {
            return;
        }
        $this->selector = $selector;
    }

    /**
     * @return bool TRUE if this CBSelector is successfully constructed, finalized and usable. FALSE otherwise.
     */
    public function finalize()
    {
        if ($this->final->isFinal()) {
            return TRUE;
        }

        $this->parsedSelector = $this->parseSelector($this->selector);
        $this->implementation = $this->buildImplementation();

        if ($this->isValid()) {
            $this->final->setFinal();
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param string $selector
     * @return CBSelectorToken[]|null
     */
    private function parseSelector($selector)
    {
        return $this->pathParser->parse($selector);
    }

    /**
     * @param string $text
     */
    private function showError($text)
    {
        $title = 'Selector error';
        $this->_cbs->error($text, $title);
    }

    /**
     * @return bool TRUE if this CBSelector is a fully built and is a valid selector, FALSE otherwise
     */
    public function isValid()
    {
        $errorTitle = get_class($this)." is invalid";

        if (FALSE == ($this->parentBlock instanceof CBBlock)) {
            $this->_cbs->error(
                get_class($this)."->parentBlock must be set", $errorTitle);
            return FALSE;
        }

        if ($this->fromVar === NULL) {
            $this->_cbs->error(
                get_class($this)."->fromVar must be set", $errorTitle);
            return FALSE;
        }

        if ($this->selector === NULL) {
            $this->_cbs->error(
                get_class($this)."->selector must be set", $errorTitle);
            return FALSE;
        } else {
            if ($this->valid !== TRUE) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Retrieving the result variable also builds the selector implementation, i.e. all loops and
     * intermediate variables created along the way.
     * Creating selector implementation during ->resVar() (instead of during construction of CBSelector) makes sure,
     * that if result var is not retrieved (i.e. CBSelector is created but never used) that the intermediate variables
     * necessary for selector implementation are also not built.
     * @return CBBaseVariable
     */
    public function resVar()
    {
        if ($this->resultUsed === FALSE) {
            if ($this->isValid()) {
                $this->finalize();
                $this->resultUsed = TRUE;

                if ($this->implementation instanceof CBMutVarAssignment) {
                    $this->parentBlock->addMutVarAssignment($this->implementation);
                } else if ($this->implementation instanceof CBListIterator) {
                    $this->parentBlock->addBlock($this->implementation);
                } else if ($this->implementation !== FALSE) {
                    $this->_cbs->constructionError('Unexpected implementation type');
                }

                $this->_resVar->setParentScope($this->parentBlock);
            }
        }

        return $this->_resVar;
    }

    /**
     * Builds result variable and along the way builds all the necessary CodeBuilder entities (e.g. loops)
     * that are necessary for calculating it
     * @param CBType $resValueType
     * @return CBMutVar
     */
    private function buildResMutVar(CBType $resValueType)
    {
        if ($resValueType->isList()) {
            $resValue = $this->_cbs->factory->emptyListValue($resValueType);
        } else {
            $resValue = $this->_cbs->factory->newValueOfType($resValueType);
        }
        $resMutVar = $resValue->assignToNewMutVar();

        return $resMutVar;
    }

    /**
     * Validates selector and builds its implementation. If the implementation is just a simple assignment to a
     * CBVariable - returns FALSE. Otherwise returns a buildable entity that represents the implementation of
     * this CBSelector (the top-level CBListIterator loop).
     * On error returns NULL.
     * @return CBListIterator|CBMutVarAssignment|false|null
     */
    private function buildImplementation()
    {
        $errorVal = NULL;

        $this->valid = FALSE;

        $resultVar = $this->fromVar;
        $resultVarAccessorPath = Array();

        /** @var CBListIterator $rootListIterator */
        /** @var CBListIterator $currentListIterator */
        $rootListIterator = NULL;
        $currentListIterator = NULL;

        $tokenListCount = 0;
        $selectorCount = count($this->parsedSelector);
        foreach ($this->parsedSelector as $partIdx => $selectorPart) {
            if ($selectorPart instanceof CBSelectorTokenList) {
                $tokenListCount++;

                // We build $listSourceVarPath here to make sure type checking is done even if list token is ineffective
                $listSourceVarPath = $this->_cbs->factory->varPath($resultVar, $resultVarAccessorPath);
                if (FALSE == $listSourceVarPath->getType()->isList()) {
                    $this->showError(
                        'Trying to loop over a value of type '.$listSourceVarPath->getType()->toString()
                        .', which is not a list');
                    return $errorVal;
                }

                // If the selector ends with this list token - no need to build a loop for it,
                // because it can only be used to merge results, but does no iteration itself
                if ($partIdx < $selectorCount - 1) {
                    // Build a loop over the referenced list
                    $parentListIterator = $currentListIterator;
                    $listSourceVarPath = $this->_cbs->factory->varPath($resultVar, $resultVarAccessorPath);
                    $currentListIterator = $this->_cbs->factory->listIterator($listSourceVarPath);
                    if ($parentListIterator !== NULL) {
                        $parentListIterator->addBlock($currentListIterator);
                    }

                    if ($rootListIterator === NULL) {
                        $rootListIterator = $currentListIterator;
                    }

                    // Update source var accessor for next iterator
                    $resultVar = $currentListIterator->getIteratorVar();
                    $resultVarAccessorPath = Array();
                }
            } else if ($selectorPart instanceof CBSelectorTokenField) {
                $resultVarAccessorPath[] = $selectorPart->name;
            }
        }

        $resultVarPath = $this->_cbs->factory->varPath($resultVar, $resultVarAccessorPath);
        if ($resultVarPath === NULL) {
            $this->valid = FALSE;
            return $errorVal;
        }

        $resultType = $resultVarPath->getType();
        if ($resultType === NULL) {
            $this->_cbs->constructionError('Could not determine result type of this '.$this->cbs->util->cb_get_class($this));
            $this->valid = FALSE;
            return $errorVal;
        }

        $this->resultVarPath = $resultVarPath;
        $this->rootIterator = $rootListIterator;

        $this->buildImplementation_buildResVarAndFinalResultAssignment(
            $currentListIterator, $resultType, $hasImplementation, $resVar, $finalResultAssignment
            , $rootListIterator, $resultVarPath);

        $this->_resVar = $resVar;
        $this->resultType = $resultType;

        $this->valid = TRUE;
        if (FALSE == $hasImplementation) {
            return FALSE;
        } else if ($rootListIterator) {
            return $rootListIterator;
        } else {
            return $finalResultAssignment;
        }
    }

    private function buildImplementation_buildResVarAndFinalResultAssignment(
        CBListIterator &$currentListIterator = NULL, &$resultType, &$hasImplementation
        , &$resVar, &$finalResultAssignment
        , CBListIterator $rootListIterator = NULL, CBVarPath $resultVarPath)
    {
        $hasImplementation = FALSE;
        if ($rootListIterator) {
            if ($this->resultShouldBeMerged()) {
                $resMutVar = $this->buildResMutVar($resultType);
                $resVar = $resMutVar;
                $finalResultAssignment = $this->_cbs->factory->mutVarAssignment($resMutVar);
                $finalResultAssignment->setListMergeWithValue($resultVarPath->refVal());
            } else {
                $resultType = $this->_cbs->factory->typeList($resultType);
                $resMutVar = $this->buildResMutVar($resultType);
                $resVar = $resMutVar;
                $finalResultAssignment = $this->_cbs->factory->mutVarAssignment($resMutVar);
                $finalResultAssignment->setListAppendValue($resultVarPath->refVal());
            }
            $currentListIterator->addMutVarAssignment($finalResultAssignment);
            $hasImplementation = TRUE;
        } else {
            $resVar = $resultVarPath->refVal()->assignToNewVar();
        }
    }

    /**
     * CBSelector result should be merged (it should merge the inner-most iterators) if all of these conditions are met:
     * - There are 2 or more list tokens ([]) in the selector
     * - The selector ends with a list token
     * - The inner-most iterator points to a list
     * @return bool
     */
    private function resultShouldBeMerged()
    {
        $tokenListCount = 0;
        foreach ($this->parsedSelector as $selectorPart) {
            if ($selectorPart instanceof CBSelectorTokenList) {
                $tokenListCount++;
            }
        }
        $lastToken = end($this->parsedSelector);

        if ($tokenListCount > 1 AND $lastToken instanceof CBSelectorTokenList) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
