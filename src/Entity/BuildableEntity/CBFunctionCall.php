<?php
class CBFunctionCall extends CBBuildableEntity
{
    /**
     * @var CBClass|CBClassRef|CBObject|string|null If this is a method call - this will contain
     * the object or the class (if this is a static method call). Otherwise this should be NULL.
     */
    public $object;

    /**
     * @var CBFunction|string
     */
    public $function;

    /**
     * @var array
     */
    public $params;


    /**
     * @param CBSettings $cbs
     * @param CBClass|CBClassRef|CBObject|string|null $object If this is a method call - this will contain the object
     * or the class (if this is a static method call). Otherwise this should be NULL.
     * @param CBFunction|string $function
     * @param array $params
     */
    public function __construct(CBSettings $cbs, $object, $function, $params = Array())
    {
        parent::__construct($cbs);

        if ($object !== NULL AND (! is_string($object) AND ! $object instanceof CBClass
                AND ! $object instanceof CBClassRef AND ! $object instanceof CBObject))
        {
            $this->_cbs->error(
                "'" . $this->cbs->util->cb_get_class($object) . "' is not a valid class/object"
                , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        $this->object = $object;

        if (empty($function))
            $this->_cbs->error("Function name cannot be empty", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        if (! is_string($function) AND ! $function instanceof CBFunction)
            $this->_cbs->error("\'{$function}\' is not a valid callable function/method", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        $this->function = $function;

        $this->params = $params;
    }


    /**
     * Returns a result of the function call.
     * You will usually call this right after constructing a CBFunctionCall.
     *
     * TODO: figure out if this is really necessary, or if we never use
     * CBObject->callFn() result directly anywhere, then we should remove
     * the need for the user to manually call ->res() every time he wants to use
     * a function call result.
     * @return CBFunctionCallResult
     */
    public function res()
    {
        return new CBFunctionCallResult($this->_cbs, $this);
    }

    public function build(CBScope $scope, CBBackend $backend)
    {
        $source = '';
        if (is_string($this->object)) {
            $source = $this->object . '::';
        } else if ($this->object instanceof CBClass) {
            $source = $this->object->name . '::';
        } else if ($this->object instanceof CBClassRef) {
            $source = $this->object->name . '::';
        } else if ($this->object instanceof CBObject) {
            $source = $this->object->build($scope, $backend) . '->';
        } else if ($this->object instanceof CBObjectReference) {
            $source = $this->object->build($scope) . '->';
        }

        else if ($this->object instanceof CBVariable)
            $source = '$' . $this->object->name . '->';

        $function = '';
        if (is_string($this->function)) {
            $function = $this->function;
        } else if ($this->function instanceof CBFunction) {
            $function = $this->function->name;
        }

        $code = $source . $function . '(';

        // If there is only one parameter, we will write it inline : function($param)
        // Otherwise, each parameter will go in a new line, like this: function (
        //      $param1
        //    , $param2
        // )
        if (count($this->params) > 1) {
            $code .= $this->_cbs->eol;
        }
        foreach ($this->params as $index => $p)
        {
            $paramValue = $this->val($p, $scope, $backend);
            if (count($this->params) > 1) {
                // If we are at the 2nd or later parameter - remove two spaces from the start indent
                // and put a comma and a space there (", ")
                if ($index > 0) {
                    $paramValue = ', ' . $paramValue;
                }
                $paramValue = $this->indent(1, $paramValue);
                if ($index > 0) {
                    $paramValue = substr($paramValue, 2);
                }
            }
            $code .= $paramValue;
            if (count($this->params) > 1) {
                $code .= $this->_cbs->eol;
            }
        }

        $code .= ')';

        return $code;
    }
}
