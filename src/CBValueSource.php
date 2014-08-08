<?php
class CBValueSource extends CBEntity
{
    const TYPE_VARIABLE = 1;
    const TYPE_VAR_PATH = 2;
    const TYPE_VALUE = 3;
    const TYPE_FUNCTION_CALL_RESULT = 4;
    const TYPE_NEW_OBJECT = 5;
    const TYPE_THIS = 6;
    const TYPE_STRUCT = 7;


    /**
     * @var int
     */
    public $type;

    /**
     * @var CBBaseVariable
     */
    public $var;

    /**
     * @var CBValue
     */
    public $value;

    /**
     * @var CBVarPath
     */
    public $varPath;

    /**
     * @var CBObject
     */
    public $object;

    /**
     * @var CBClass
     */
    public $class;

    /**
     * @var CBClassRef
     */
    public $classRef;

    /**
     * @var CBFunctionCallResult
     */
    public $fnCallRes;

    /**
     * @var CBStruct
     */
    public $fromStruct;

    /**
     * @var string
     */
    public $fromStructKey;


    /**
     * @param CBSettings $cbs
     * @param CBBaseVariable $var
     * @return CBValueSource
     */
    public static function factoryFromVar(CBSettings $cbs, CBBaseVariable $var)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_VARIABLE;
        $r->var = $var;

        return $r;
    }

    /**
     * @param CBSettings $cbs
     * @param CBVarPath $varPath
     * @return CBValueSource
     */
    public static function factoryFromVarPath(CBSettings $cbs, CBVarPath $varPath)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_VAR_PATH;
        $r->varPath = $varPath;

        return $r;
    }

    /**
     * @param CBSettings $cbs
     * @param CBValue $value
     * @return CBValueSource
     */
    public static function factoryFromValue(CBSettings $cbs, CBValue $value)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_VALUE;
        $r->value = $value;

        return $r;
    }

    /**
     * @param CBSettings $cbs
     * @param CBFunctionCallResult $fnCallRes
     * @return CBValueSource
     */
    public static function factoryFromFnCallRes(CBSettings $cbs, CBFunctionCallResult $fnCallRes)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_FUNCTION_CALL_RESULT;
        $r->fnCallRes = $fnCallRes;

        return $r;
    }

    /**
     * @param CBSettings $cbs
     * @param CBObject $object
     * @param CBClass|CBClassRef $classOrClassRef
     * @return CBValueSource
     */
    public static function factoryFromNewObject(CBSettings $cbs, CBObject $object, $classOrClassRef)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_NEW_OBJECT;
        $r->object = $object;

        if ($classOrClassRef instanceof CBClass) {
            $r->class = $classOrClassRef;
        } else if ($classOrClassRef instanceof CBClassRef) {
            $r->classRef = $classOrClassRef;
        } else {
            $cbs->error(
                __METHOD__." expects \$classOrClassRef to be a CBClass/CBClassRef,"
                    ." instead ".$cbs->util->cb_get_class($classOrClassRef)." was given"
                , CBSettings::ERROR_WRONG_TYPE);
        }

        return $r;
    }

    public static function factoryFromThis(CBSettings $cbs)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_THIS;
        return $r;
    }

    public static function factoryFromForeignStruct(CBSettings $cbs, CBStruct $struct, $key)
    {
        $r = new CBValueSource($cbs);
        $r->type = self::TYPE_STRUCT;
        $r->fromStruct = $struct;
        $r->fromStructKey = $key;
        return $r;
    }


    /**
     * @return bool TRUE if this CBValueSource represents a foreign value (i.e. CBValue->val should not be used
     * directly), FALSE otherwise.
     */
    public function isForeign()
    {
        if ($this->type == CBValueSource::TYPE_VARIABLE) {
            return TRUE;
        } else if ($this->type == CBValueSource::TYPE_VALUE) {
            return TRUE;
        } else if ($this->type == CBValueSource::TYPE_FUNCTION_CALL_RESULT) {
            return TRUE;
        }

        return FALSE;
    }


    /**
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @return string
     */
    public function build(CBScope $scope, CBBackend $backend)
    {
        if ($this->type == self::TYPE_VARIABLE) {
            return $this->val($this->var, $scope, $backend);
        } else if ($this->type == self::TYPE_VAR_PATH) {
            return $this->varPath->build($scope, $backend);
        } else if ($this->type == self::TYPE_VALUE) {
            return $backend->buildVal($this->value, $scope);
        } else if ($this->type == self::TYPE_FUNCTION_CALL_RESULT) {
            return $this->fnCallRes->fnCall->build($scope, $backend);
        } else if ($this->type == self::TYPE_NEW_OBJECT) {
            return $this->object->buildObjectInitializer();
        } else if ($this->type == self::TYPE_THIS) {
            return $backend->buildThis();
        } else if ($this->type == self::TYPE_STRUCT) {
            return $this->fromStruct->build($scope, $backend) . $backend->buildStructFieldAccessor($this->fromStructKey);
        } else {
            $this->_cbs->error("Unknown CBValueSource type", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        return '';
    }
}
