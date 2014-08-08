<?php
abstract class CBBuildableEntity extends CBEntity
{
    /**
     * Declaration of CBBuildableEntity::build(). This has to be implemented by the extending classes.
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @return string
     */
    abstract public function build(CBScope $scope, CBBackend $backend);
}
