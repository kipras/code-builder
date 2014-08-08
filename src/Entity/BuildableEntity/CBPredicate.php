<?php
class CBPredicate extends CBBuildableEntity
{
    /**
     * @var CBBuildableEntity Left value (must be an instance of CBBuildableEntity, use CBValue for plain values)
     */
    public $left;

    /**
     * @var string Comparison operator
     */
    public $operator;

    /**
     * @var CBBuildableEntity Right value (must be an instance of CBBuildableEntity, use CBValue for plain values)
     */
    public $right;


    public function build(CBScope $scope, CBBackend $backend)
    {
        if (! $this->left instanceof CBBuildableEntity)
            $this->_cbs->error("Left side of CBPredicate is not a buildable value", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        if (! $this->right instanceof CBBuildableEntity)
            $this->_cbs->error("Right side of CBPredicate is not a buildable value", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $leftVal = $this->val($this->left, $scope, $backend);
        if ($leftVal === NULL)
            $this->_cbs->error("Could not build left side of the CBPredicate", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $rightVal = $this->val($this->right, $scope, $backend);
        if ($leftVal === NULL)
            $this->_cbs->error("Could not build right side of the CBPredicate", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);


        return $leftVal . ' ' . $this->operator . ' ' . $rightVal;
    }
}
