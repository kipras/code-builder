<?php

/**
 * Class CBFinal
 * Provides functionality to mark an object as final (i.e. that the object creation is complete and no more changes
 * should be made to that object) and to check if it is final
 */
class CBFinal
{
    /**
     * @var CBSettings
     */
    private $cbs;

    private $parentObject;

    /**
     * @var boolean
     */
    private $final = FALSE;


    public function __construct(CBSettings $cbs, $parentObject)
    {
        $this->cbs = $cbs;
        $this->parentObject = $parentObject;
    }


    /**
     * Finalizes the parent
     */
    public function setFinal()
    {
        $this->final = TRUE;
    }

    /**
     * Shows an error if the parent is already finalized
     * @return bool TRUE if parent is not yet finalized, FALSE if it is finalized
     */
    public function checkNotFinal()
    {
        if ($this->isFinal()) {
            $this->cbs->error(
                "Cannot make modifications to this ".get_class($this->parentObject).", because it is final"
                , get_class($this->parentObject)." is final");
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @return bool TRUE if the parent is finalized, FALSE otherwise
     */
    public function isFinal()
    {
        return $this->final;
    }
}
