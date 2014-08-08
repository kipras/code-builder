<?php
/**
 * Class ICBHasParentScope
 * Interface for CodeBuilder entities that have a parent scope (scopes, variables, functions)
 */
interface ICBHasParentScope
{
    /**
     * @return CBScope
     */
    public function getParentScope();

    /**
     * @param CBScope $scope
     */
    public function setParentScope(CBScope $scope);
}
