<?php
class CBFunctionParameter extends CBBuildableEntity
{
    /**
     * @var bool Set this to TRUE to make a parameter writable, by default parameters are readonly
     */
    public $writable = FALSE;

    /**
     * @var CBVariable
     */
    private $variable;


    public function __construct(CBSettings $cbs, CBVariable $variable = NULL)
    {
        parent::__construct($cbs);

        if ($variable === NULL) {
            $variable = new CBVariable($this->_cbs);
        }
        $this->variable = $variable;
    }

    /**
     * @return CBVariable
     */
    public function getParamVar()
    {
        return $this->variable;
    }

    public function build(CBScope $scope, CBBackend $backend)
    {
        return ($this->writable ? '&' : '') . $this->variable->build($scope, $backend, TRUE);
    }
} 
