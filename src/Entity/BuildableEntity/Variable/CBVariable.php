<?php
/**
 * Class CBVariable
 * @property CBScope $parentScope
 * @property string $name
 * @property CBValue $value
 * @property bool $superGlobal
 */
class CBVariable extends CBBaseVariable
{
    /**
     * @var CBObject If this is an Object property - this holds the Object that this property belongs to
     */
    public $parentObject;

    /**
     * @var CBValue
     */
    private $_value;

    /**
     * @var bool
     */
    private $_isInitialized = FALSE;

    /**
     * @var bool If you set $strictName to TRUE, CodeBuilder will throw an error, if adding this variable to a scope,
     * where a variable with this name already exists (instead of adding a suffix to it).
     * Use this, if you need to force a variable to have an exact name.
     */
    public $strictName = FALSE;

    /**
     * @var bool If $superGlobal is set to TRUE, $strictName will also be automatically set to TRUE
     * TODO: try to eliminate the use of superglobals, and remove their support from CodeBuilder too
     */
    private $_superGlobal = FALSE;


    /**
     * @param CBSettings $cbs
     * @param string $name Optional: name of the variable, can be set later or can be not set at all - in that case
     * a name will be generated for it
     */
    public function __construct(CBSettings $cbs, $name = NULL)
    {
        parent::__construct($cbs);

        if ($name !== NULL AND (! is_string($name) AND ! $name instanceof CBString))
            $this->_cbs->error("\'{$name}\' is not a valid variable name", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $this->name = $name;
    }


    public function __get($name)
    {
        switch ($name)
        {
            case 'value':
                return $this->_value;
                break;

            case 'superGlobal':
                return $this->_superGlobal;
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
            case 'value':
                if ($value instanceof CBValue) {
                    if ($this->_value !== NULL) {
                        $this->_cbs->error(
                            "This CBVariable is already assigned a value - cannot change it, "
                            . "because CBVariable is immutable", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                    } else {
                        $this->_value = $value;
                        $this->_isInitialized = TRUE;
                    }
                } else {
                    $this->_cbs->error("CBVariable->value must be a CBValue", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                }

                break;

            case 'superGlobal':
                if ($value == TRUE) {
                    $this->strictName = TRUE;
                }
                $this->_superGlobal = $value;
                break;

            default:
                return parent::__set($name, $value);
                break;
        }
    }


    /**
     * For debugging purposes (e() uses this)
     * @return array
     */
    public function __toArray()
    {
        return Array(
            'parentObject' => $this->parentObject,
            '_name' => $this->name,
            '_value' => $this->_value,
            'strictName' => $this->strictName,
            '_superGlobal' => $this->_superGlobal,
        );
    }


    public function getType()
    {
        if ($this->_value === NULL) {
            $this->_cbs->constructionError(
                "This ".$this->cbs->util->cb_get_class($this)." has no type information yet, because it is not yet fully built: "
                ." ->value is not set");
            return $this->_cbs->factory->typeUnknown();
        }

        return $this->_value->type;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function getInitializerValue()
    {
        return ($this->_isInitialized ? $this->_value : NULL);
    }

    /**
     * @param bool $isInitialized
     */
    public function setIsInitialized($isInitialized)
    {
        $this->_isInitialized = $isInitialized;
    }


    /**
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @param bool $buildAccessorOnly If this is set to TRUE - only returns the name of the variable. Otherwise
     * builds a variable declaration, with an initializer (if an initial value is set).
     * @return string
     */
    public function build(CBScope $scope, CBBackend $backend, $buildAccessorOnly = FALSE)
    {
        if ($this->superGlobal) {
            $this->_cbs->error("Super globals should not be built", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
            return '';
        }

        return parent::build($scope, $backend, $buildAccessorOnly);
    }
}
