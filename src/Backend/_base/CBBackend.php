<?php
/**
 * Class CBBackend
 *
 * INFO:
 * CBBackend uses separate buildVarUninitializedDeclaration() and buildVarInitializedDeclaration() methods, to make sure all
 * logic for separating these two variable declaration methods is contained solely within CBBackend classes,
 * instead of CBVariable. This should allow for all sorts of initializer declarations, instead of constricting
 * us to some predefined format.
 */
abstract class CBBackend
{
    /**
     * @var CBSettings
     */
    protected $cbs;


    public function __construct(CBSettings $cbs)
    {
        $this->cbs = $cbs;
    }


    /**
     * @return string
     */
    abstract public function endOfStatement();

    /**
     * @param CBFile $file
     * @return string
     */
    abstract public function buildFileHeader(CBFile $file);

    /**
     * @param string $code
     * @return string
     */
    abstract public function buildMainFunction($code);

    /**
     * @param CBType $type
     * @return string
     */
    abstract public function buildTypeDefinition(CBType $type);

    /**
     * @param CBVariable $var
     * @return string
     */
    abstract public function buildVarUninitializedDeclaration(CBVariable $var);

    /**
     * @param CBBaseVariable $var
     * @param CBScope $scope
     * @return string
     */
    abstract public function buildVarInitializedDeclaration(CBBaseVariable $var, CBScope $scope);

    /**
     * @param string $string
     * @return string
     */
    abstract public function buildStringVal($string);

    /**
     * @param array $list
     * @param CBScope $scope
     * @return string
     */
    abstract public function buildListInitializer(array $list, CBScope $scope);

    /**
     * @param int|string $index
     * @return string
     */
    abstract public function buildListIndexAccessor($index);

    /**
     * @param string $pathToList
     * @param string $pathToItem
     * @param null $pathToIndex
     * @return string
     */
    abstract public function buildListIterator($pathToList, $pathToItem, $pathToIndex = NULL);

    /**
     * @param string $pathToList Path to list variable
     * @param string $pathToItem Accessor of item to add
     * @return string
     */
    abstract public function buildAddToList($pathToList, $pathToItem);

    /**
     * @param string $pathToFirstList
     * @param string $pathToSecondList
     * @return string
     */
    abstract public function buildMergeLists($pathToFirstList, $pathToSecondList);

    /**
     * @param array $struct
     * @param CBScope $scope
     * @return string
     */
    abstract public function buildStructInitializer(array $struct, CBScope $scope);

    /**
     * @param string $fieldName
     * @return string
     */
    abstract public function buildStructFieldAccessor($fieldName);

    /**
     * @return string
     */
    abstract public function buildThis();


    /**
     * @param string $left
     * @param string $right
     * @return string
     */
    public function buildAssignment($left, $right)
    {
        return $this->_buildAssignment($left, $right, '=');
    }

    public function buildAddAssignment($left, $right)
    {
        return $this->_buildAssignment($left, $right, '+=');
    }

    public function buildSubtractAssignment($left, $right)
    {
        return $this->_buildAssignment($left, $right, '-=');
    }

    private function _buildAssignment($left, $right, $oper)
    {
        return $left.' '.$oper.' '.$right;
    }

    /**
     * @param mixed $val
     * @param CBScope $scope
     * @return string|null
     */
    final public function buildVal($val, CBScope $scope)
    {
        if (is_array($val)) {
            // @TODO: why can't we determine type here?
            // It seems that the only problem should be empty arrays (you have no items to determine item type from).
//            $this->cbs->error(
//                "PHP array value given to ".__METHOD__.", cannot determine type. "
//                ."Please put it in a CBValue wrapper with correct type information"
//                , CBSettings::ERROR_AMBIGUOUS_TYPE);

            // @TODO: also, determining types probably should not be necessary here, since we are building
            // a constant value (in this case - an array initializer) from given atomic values and directly
            // building it to a code string. So there is no need to store type information.
            // Determining & storing type information would be necessary if we built a CBValue here, that we then
            // later assigned to a CBVariable. It would be necessary, when building the declaration of that CBVariable.
            $cbValue = CBValue::factoryFromValue($this->cbs, $val);
            return $this->buildVal($cbValue, $scope);
        } else if ($val instanceof CBValue) {
            return $val->build($scope, $this);
        } else if ($val instanceof CBBaseVariable) {
            return $scope->buildPathToVariable($val, $this);
        } else if ($val instanceof CBFunctionCallResult) {
            return $val->fnCall->build($scope, $this);
        } else if (is_string($val)) {
            return $this->buildStringVal($val);
        } else if (is_numeric($val)) {
            return $val;
        } else if ($val === TRUE) {
            return 'TRUE';
        } else if ($val === FALSE) {
            return 'FALSE';
        } else if ($val === NULL) {
            return 'NULL';
        } else {
            $this->cbs->error("Unexpected value type given to ".__METHOD__, CBSettings::ERROR_UNEXPECTED_TYPE);
        }

        return NULL;
    }

    final public function buildVarDeclarationStatement(CBBaseVariable $var, CBScope $scope)
    {
        if ($var->isInitialized()) {
            return $this->buildVarInitializedDeclaration($var, $scope) . $this->endOfStatement();
        } else {
            return $this->buildVarUninitializedDeclaration($var);
        }
    }

    public function varName(CBBaseVariable $var)
    {
        return $var->name;
    }

    final public function buildVarInitializer(CBBaseVariable $var, CBScope $scope)
    {
        $varName = $this->varName($var);
        $initializerValue = $var->getInitializerValue();
        $code = $initializerValue->build($scope, $this);

        // If this variable contains an Object - set Object properties
        if ($initializerValue instanceof CBObject) {
            $allDynamicProps = $initializerValue->getAllDynamicProps();
            foreach ($allDynamicProps as $prop) {
                $code .= ';' . $this->cbs->eol;
                if ($prop->value !== NULL) {
                    $code .= $varName . '->' . $prop->name . ' = ' . $prop->value->build($scope, $this);
                }
            }
        }

        return $code;
    }


    protected function error($text, $title)
    {
        $this->cbs->error($text, $title);
    }
}
