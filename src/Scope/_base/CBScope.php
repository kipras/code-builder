<?php

/**
 * Class CBScope
 */
abstract class CBScope extends CBEntity implements ICBHasParentScope
{
    const SCOPE_VARS = 1;
    const SCOPE_FNS = 2;
    const SCOPE_FNCALLS = 3;
    const SCOPE_TYPES = 4;


    /**
     * @var CBScope|null The parent lexical scope to which this scope is added. NULL if this is the top-level scope.
     * Each code Block has a parent.
     * If this is a block in a function - then it will be the parent CBBlock (or the function).
     * If this is the main block of the function - then it will be the CBClass or parent CBFile.
     * If this is the code, that goes directly in a file - then the parent will be that CBFile.
     */
    private $_parentScope;


    /**
     * @var CBScope[] A list of child scopes
     */
    private $_innerScopes = Array();


    /**
     * @var CBBaseVariable[]
     */
    public $_vars = Array();

    /**
     * @var CBBaseVariable[]
     */
    public $_namingVars = Array();


    /**
     * @var CBFunction[]
     */
    public $_fns = Array();

    /**
     * @var CBFunction[]
     */
    public $_namingFns = Array();


    /**
     * @var CBFunctionCall[]
     */
    public $_fnCalls = Array();


    /**
     * @var CBType[] Types that should be declared in this scope
     */
    public $_types = Array();

    /**
     * @var CBType[]
     */
    public $_namingTypes = Array();


    private function SCOPE_TYPES()
    {
        return Array(
            self::SCOPE_VARS,
            self::SCOPE_FNS,
            self::SCOPE_FNCALLS,
            self::SCOPE_TYPES,
        );
    }

    /**
     * @return CBScope
     * @see ICBHasParentScope::getParentScope()
     */
    public function getParentScope()
    {
        return $this->_parentScope;
    }

    /**
     * Sets parent scope. If previously there was no naming scope in the parent scope chain and now there is -
     * we also go through all items in the scope and through all inner scopes and their items recursively
     * to add named items to the naming scope.
     * @param CBScope $parentScope
     * @see ICBHasParentScope::setParentScope()
     */
    public function setParentScope(CBScope $parentScope)
    {
        $hadParentNamingScopeBefore = $this->getNamingScope() !== NULL;

        $this->_parentScope = $parentScope;
        $parentScope->_innerScopes[] = $this;

        // Always keep dependencies at the parent scope (if available)
        foreach ($this->_dependencies as $dep)
            $this->_parentScope->addDependency($dep);

        $namingScope = $this->getNamingScope();
        $hasNamingScopeNow = $namingScope !== NULL;
        if ($hasNamingScopeNow AND FALSE == $hadParentNamingScopeBefore) {
            $this->addAllItemsToNamingScope($namingScope);
        }
    }

    /**
     * Go through all items in this scope and through all inner scopes and their items recursively
     * to add named items to the naming scope.
     */
    private function addAllItemsToNamingScope()
    {
        $SCOPE_TYPES = $this->SCOPE_TYPES();
        foreach ($SCOPE_TYPES as $scopeType) {
            $scopeTypeItems = $this->getScopeItemsByType($scopeType);
            foreach ($scopeTypeItems as $item) {
                $this->_addToNamingScope($item, $scopeType);
            }
        }

        foreach ($this->_innerScopes as $innerScope) {
            $innerScope->addAllItemsToNamingScope();
        }
    }


    /**
     * @param array $scope One of the scope items container arrays (e.g. Scope::$_vars)
     * @param string $name
     * @return string
     */
    protected function _genName(array $scope, $name = 'tmp')
    {
        $i = 1;
        while (array_key_exists($name . $i, $scope))
            $i++;

        return $name . $i;
    }


    private function getScopeItemsByType($scopeType)
    {
        if ($scopeType == self::SCOPE_VARS) {
            return $this->_vars;
        } else if ($scopeType == self::SCOPE_FNS) {
            return $this->_fns;
        } else if ($scopeType == self::SCOPE_FNCALLS) {
            return $this->_fnCalls;
        } else if ($scopeType == self::SCOPE_TYPES) {
            return $this->_types;
        } else if ($scopeType == self::SCOPE_VARS) {
            $this->_cbs->error("Unexpected scope data type = ".$scopeType, "Unexpected scope data type");
            return NULL;
        }
    }

    private function setScopeItemsByType($scopeType, array $items)
    {
        if ($scopeType == self::SCOPE_VARS) {
            $this->_vars = $items;
        } else if ($scopeType == self::SCOPE_FNS) {
            $this->_fns = $items;
        } else if ($scopeType == self::SCOPE_FNCALLS) {
            $this->_fnCalls = $items;
        } else if ($scopeType == self::SCOPE_TYPES) {
            $this->_types = $items;
        } else if ($scopeType == self::SCOPE_VARS) {
            $this->_cbs->error("Unexpected scope data type = ".$scopeType, "Unexpected scope data type");
        }
    }


    /**
     * @param int $scopeType
     * @return CBBaseVariable[]|CBFunction[]|CBType[]|null
     */
    private function getNamingScopeNamingItemsByType($scopeType)
    {
        if (FALSE === $this->isNamingScope()) {
            $this->_cbs->error("This is not a naming scope", "Not a naming scope");
            return NULL;
        }

        if ($scopeType == self::SCOPE_VARS) {
            return $this->_namingVars;
        } else if ($scopeType == self::SCOPE_FNS) {
            return $this->_namingFns;
        } else if ($scopeType == self::SCOPE_TYPES) {
            return $this->_namingTypes;
        } else if ($scopeType == self::SCOPE_VARS) {
            $this->_cbs->error("Unexpected scope data type = ".$scopeType, "Unexpected scope data type");
            return NULL;
        }
    }

    /**
     * @param int $scopeType
     * @param CBBaseVariable[]|CBFunction[]|CBType[] $namingItems
     */
    private function setNamingScopeNamingItemsByType($scopeType, array $namingItems)
    {
        if (FALSE === $this->isNamingScope()) {
            $this->_cbs->error("This is not a naming scope", "Not a naming scope");
            return;
        }

        if ($scopeType == self::SCOPE_VARS) {
            $this->_namingVars = $namingItems;
        } else if ($scopeType == self::SCOPE_FNS) {
            $this->_namingFns = $namingItems;
        } else if ($scopeType == self::SCOPE_TYPES) {
            $this->_namingTypes = $namingItems;
        } else if ($scopeType == self::SCOPE_VARS) {
            $this->_cbs->error("Unexpected scope data type = ".$scopeType, "Unexpected scope data type");
        }
    }


    /**
     * @return bool
     */
    protected function isNamingScope()
    {
        return $this instanceof CBFile OR $this instanceof CBClass OR $this instanceof CBFunction;
    }

    /**
     * @return CBScope|null Parent naming scope or this scope if this is a naming scope. If there is no naming scope
     * yet - returns NULL.
     */
    private function getNamingScope()
    {
        $scope = $this;
        while ($scope !== NULL AND FALSE == $scope->isNamingScope()) {
            $scope = $scope->getParentScope();
        }
        return $scope;
    }


    /**
     * Used to add variables/functions to their respective scopes
     * @param CBEntity $item A CBVariable/CBFunction/CBType
     * @param int $scopeType One of the SCOPE_ constants
     */
    protected function _addToScope(CBEntity $item, $scopeType)
    {
        if (FALSE == ($item instanceof ICBHasParentScope)) {
            $this->_cbs->error("\$item must implement ICBHasParentScope", "\$item does not support scopes");
            return;
        }

        if (FALSE == ($item instanceof ICBNamedEntity)) {
            $this->_cbs->error("\$item must implement ICBNamedEntity", "\$item is not a named entity");
            return;
        }

        if ($item instanceof ICBHasParentScope AND $item instanceof ICBNamedEntity) {
            // Add to scope items list
            $scopeItems = $this->getScopeItemsByType($scopeType);
            if (FALSE == in_array($item, $scopeItems, TRUE)) {
                $scopeItems[] = $item;
            }
            $this->setScopeItemsByType($scopeType, $scopeItems);

            // Set item's parent scope last, because setParentScope() may invoke this function again and we
            // would end up in a mutual recursion loop, e.g.:
            // CBScope->addVar() -> CBScope->_addToScope() -> CBVariable->setParentScope() ->
            // CBVariable->getParentBlock()->addVar()
            // However, on that second try, array_search() at the start of _addToScope() will not return FALSE
            // anymore and we won't get here.
            // We just have to make sure to call ->setParentScope() after array_search().
            if ($item->getParentScope() !== $this) {
                $item->setParentScope($this);
            }

            // Finally - add to naming scope
            $this->_addToNamingScope($item, $scopeType);
        }
    }

    private function _addToNamingScope(CBEntity $item, $scopeType)
    {
        $namingScope = $this->getNamingScope();
        if ($namingScope === NULL) {
            return;
        }

        if ($item instanceof ICBNamedEntity) {
            $namingScopeNamingItems = $namingScope->getNamingScopeNamingItemsByType($scopeType);

            // Check if item is not added to the naming scope yet or if it is added with a different name
            $namingScopeExistingKey = array_search($item, $namingScopeNamingItems, TRUE);
            $name = $item->getName();
            if ($namingScopeExistingKey === FALSE OR $namingScopeExistingKey != $name) {
                // 1. Add to naming scope (naming information)
                if ($name === NULL) {
                    $tmpNamePrefix = ($scopeType == self::SCOPE_TYPES ? 'Tmp': 'tmp');
                    $name = $namingScope->_genName($namingScopeNamingItems, $tmpNamePrefix);
                    $item->setName($name);
                } else {
                    // If there is already a different item set in this scope,
                    // with the same name - add a unique suffix to the name of this item
                    if (array_key_exists($name, $namingScopeNamingItems) AND $namingScopeNamingItems[$name] !== $item) {
                        $name = $namingScope->_genName($namingScopeNamingItems, $name);
                        $item->setName($name);
                    }
                }

                // If this item is already added to the naming scope, but with a different name -
                // first remove it from the naming scope
                if ($namingScopeExistingKey !== FALSE) {
                    unset($namingScopeNamingItems[$namingScopeExistingKey]);
                }

                // And add it
                $namingScopeNamingItems[$name] = $item;
            }

            $namingScope->setNamingScopeNamingItemsByType($scopeType, $namingScopeNamingItems);
        }
    }

    /**
     * Adds a dependency. $dep may be a string (filename of an existing dependency)
     * or a CBFile/CBClass/CBFunction
     * @param $dep string|CBFile|CBClass|CBFunction A dependency
     * @see CBEntity::addDependency()
     */
    public function addDependency($dep)
    {
        // Always keep dependencies at the parent scope (if available)
        if ($this->getParentScope() !== NULL)
            $this->getParentScope()->addDependency($dep);
        else
            parent::addDependency($dep);
    }

    /**
     * Returns the variable with full selectorPath to it (from the current code block)
     * as a string, IF the variable is reachable from the current _scope.
     * If it is not reachable - returns NULL.
     *
     * The passed $var *must* be an instance of CBVariable and not the string
     * name of the variable.
     * @param CBBaseVariable $var
     * @param CBBackend $backend
     * @return null|string
     */
    public function buildPathToVariable(CBBaseVariable $var, CBBackend $backend)
    {
        $varName = $backend->varName($var);

        if (in_array($var, $this->_vars, TRUE)) {
            return $varName;
        }

        if ($this->getParentScope() instanceof CBClass)
        {
            if ($this->getParentScope()->containsVar($var)) {
                return '$this->' . $var->name;
            } else {
                $this->_cbs->error(
                    "Variable '{$var->name}' is unreachable from this scope"
                    , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                return NULL;
            }
        }

        if ($this->getParentScope() instanceof CBScope) {
            return $this->getParentScope()->buildPathToVariable($var, $backend);
        }

        $this->_cbs->error(
            "Variable '{$var->name}' is unreachable from this scope"
            , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        return NULL;
    }

    /**
     * @param $name
     * @return CBVariable|null The CBVariable for the given $name, if such a variable exists in this scope or any of
     * the parent scopes. If not - returns NULL.
     */
    public function getVariableByName($name)
    {
        if (array_key_exists($name, $this->_vars))
            return $this->_vars[$name];

        if ($this->getParentScope() instanceof CBBlock)
            return $this->getParentScope()->getVariableByName($name);

        return NULL;
    }


    /**
     * Adds passed variable to this scope
     * @param CBBaseVariable $var
     */
    public function addVar(CBBaseVariable $var)
    {
        $this->_addToScope($var, self::SCOPE_VARS);

        if ($var instanceof CBVariable) {
            if ($var->value AND $var->value->type) {
                $this->addType($var->value->type);
            }
        }
    }

    /**
     * Returns TRUE if this is the direct parent container scope of the given variable, FALSE otherwise.
     * NOTE that if the variable is contained in some parent scope of this scope - then that variable
     * will be accessible from this scope, but this method will return FALSE.
     * @param CBBaseVariable $var
     * @return bool
     */
    public function containsVar(CBBaseVariable $var)
    {
        return FALSE !== array_search($var, $this->_vars, TRUE);
    }

    /**
     * Adds passed CBVariable to this scope as an undeclared variable.
     * The variable will be usable in this scope, but will not be declared in it.
     * @param CBVariable $var
     */
    public function addUndeclaredVar(CBVariable $var)
    {
        $this->_addToScope($var, self::SCOPE_VARS);
        $var->isDeclared = FALSE;
    }

    /**
     * Returns TRUE if this is the direct parent container scope of the given variable and
     * the given variable has been added as an undeclared variable, FALSE otherwise.
     * @param CBBaseVariable $var
     * @return bool
     */
    public function containsUndeclaredVar(CBBaseVariable $var)
    {
        return $this->containsVar($var) AND FALSE == $var->isDeclared;
    }

    /**
     * Removes the passed CBVariable from this scope
     * @param CBVariable $var
     */
    public function removeVar(CBVariable $var)
    {
        $key = array_search($var, $this->_vars);
        if ($key !== FALSE)
            unset($this->_vars[$key]);
    }
    /**
     * @return CBBaseVariable[] Returns all variables in this scope
     */
    public function getAllVars()
    {
        return $this->_vars;
    }


    /**
     * Adds passed CBFunction to this scope
     * @param CBFunction $fn
     */
    public function addFn(CBFunction $fn)
    {
        $this->_addToScope($fn, self::SCOPE_FNS);
    }

    /**
     * Returns the given function if it exists in this scope, otherwise returns NULL
     * @param string $fnName Function name
     * @return CBFunction|null
     */
    public function getFnByName($fnName)
    {
        foreach ($this->_fns as $function) {
            if ($function->name == $fnName) {
                return $function;
            }
        }
        return NULL;
    }
    /**
     * @return CBFunction[] Returns all functions in this scope
     */
    public function getAllFns()
    {
        return $this->_fns;
    }


    /**
     * Adds passed CBFunctionCall to this scope
     * @param CBFunctionCall $fnCall
     */
    public function addFnCall(CBFunctionCall $fnCall)
    {
        if (! in_array($fnCall, $this->_fnCalls, TRUE))
            $this->_fnCalls[] = $fnCall;
    }
    /**
     * @return CBFunctionCall[] Returns all function calls in this scope
     */
    public function getAllFnCalls()
    {
        return $this->_fnCalls;
    }


    /**
     * Adds passed CBType to this scope.
     * Types are always added to the top-most scope, which should be a CBFile. This is done to make sure
     * that when building C programs, all types defined in one file would have unique names.
     * @param CBType $type
     */
    public function addType(CBType $type)
    {
        // Always keep types at the parent scope (if available)
        if ($this->getParentScope() !== NULL) {
            $this->getParentScope()->addType($type);
        } else {
            $this->_addToScope($type, self::SCOPE_TYPES);
        }
    }
    /**
     * @return CBType[]
     */
    public function getTypes()
    {
        return $this->_types;
    }
}
