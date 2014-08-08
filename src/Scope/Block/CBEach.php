<?php

/**
 * Class CBEach
 * Used to iterate over structures
 * @property CBVariable $key
 * @property CBVariable $item
 * @deprecated for iterating over lists - use CBListIterator. Iterating over structures should not be possible,
 * because you lose type information. Structure modifications should be made by explicitly addressing specific fields.
 */
class CBEach extends CBBlock
{
    /**
     * @var CBVariable Input list fo iterate over
     */
    public $list;

    /**
     * @var CBVariable Variable of the key iterator (optional).
     * Use it, by just assigning a variable to it, and CBEach will take care of the rest.
     * If you won't assign a variable to $key then no key iterator will be built, as in:
     * foreach ($list as $item)
     */
    protected $_key;

    /**
     * @var CBVariable Variable of the iterator item (optional)
     */
    protected $_item;


    /**
     * @param CBSettings $cbs
     * @param CBVariable $list Optional (can be set later): input list to iterate over
     * @param CBVariable $item Optional: CBVariable to use as the iterator
     */
    public function __construct(CBSettings $cbs, CBVariable $list = NULL, CBVariable $item = NULL)
    {
        parent::__construct($cbs);

        if ($list !== NULL)
        {
            $this->list = $list;
        }


        if ($item !== NULL)
        {
            $this->item = $item;
        }
        else
        {
            $this->item = new CBVariable($cbs);
        }
    }

    public function __get($name)
    {
        switch ($name)
        {
            case 'key':
                return $this->_key;
                break;

            case 'item':
                return $this->_item;
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
            case 'key':
                if ($this->getParentScope() AND $this->_key)
                    $this->getParentScope()->removeVar($this->_key);
                $this->_key = $value;
                if ($this->getParentScope())
                    $this->getParentScope()->addVar($this->_key);
                break;

            case 'item':
                // When changing the item variable - remove the previously set item variable from scope
                if ($this->getParentScope() AND $this->_item)
                    $this->getParentScope()->removeVar($this->_item);
                $this->_item = $value;
                if ($this->getParentScope())
                    $this->getParentScope()->addVar($this->_item);
                break;

            default:
                return parent::__set($name, $value);
                break;
        }
    }


    /**
     * @param CBScope $scope
     * @see CBScope::setParentScope()
     */
    public function setParentScope(CBScope $scope)
    {
        // When changing the parent scope variable - remove $this->_key and $this->_item from it's scope
        $parentScopeBefore = $this->getParentScope();

        if ($parentScopeBefore AND $this->_key)
            $parentScopeBefore->removeVar($this->_key);
        if ($parentScopeBefore AND $this->_item)
            $parentScopeBefore->removeVar($this->_item);

        parent::setParentScope($scope);

        $this->getParentScope()->addVar($this->_item);
        if ($this->_key)
            $this->getParentScope()->addVar($this->_key);

        // Always keep dependencies at the parent scope (if available)
        foreach ($this->_dependencies as $dep)
            $this->getParentScope()->addDependency($dep);
    }


    public function build(CBBackend $backend)
    {
        if ($this->list === NULL)
            $this->_cbs->error("List cannot be NULL", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $pathToList = $this->buildPathToVariable($this->list, $backend);
        if ($pathToList === NULL)
            $this->_cbs->error("List '{$this->list->name}' is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $pathToKey = NULL;
        if ($this->_key)
        {
            $pathToKey = $this->buildPathToVariable($this->_key, $backend);
            if ($pathToKey === NULL)
                $this->_cbs->error("Item '{$this->_key->name}' is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        $pathToItem = $this->buildPathToVariable($this->_item, $backend);
        if ($pathToItem === NULL)
            $this->_cbs->error("Item '{$this->_item->name}' is unreachable from the block in which this foreach is defined", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $code  = "foreach ({$pathToList} as " . ($pathToKey ? "{$pathToKey} => " : '') . "{$pathToItem})" . $this->_cbs->eol;
        $codeBlock = parent::build($backend);
        if ($this->buildHasBraces == FALSE) {
            $codeBlock = $this->indent(1, $codeBlock);
        }
        $code .= $codeBlock;

        return $code;
    }
}
