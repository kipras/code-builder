<?php
class CBIf extends CBBuildableEntity
{
    /**
     * @var CBPredicate[]
     */
    public $predicates = Array();

    /**
     * @var CBBlock The block that is executed if the condition is TRUE
     */
    public $block;

    /**
     * @var CBBlock|null Optional: The block that is executed if the the condition is FALSE (else block)
     */
    public $elseBlock;


    /**
     * @param CBSettings $cbs
     */
    public function __construct(CBSettings $cbs)
    {
        parent::__construct($cbs);

        $this->block = new CBBlock($cbs);
        $this->elseBlock = new CBBlock($cbs);
    }


    public function build(CBScope $scope, CBBackend $backend)
    {
        $blockCode = $this->block->build($backend);
        $elseBlockCode = $this->elseBlock->build($backend);

        if ($blockCode OR $elseBlockCode)
        {
            $code  = 'if (';
            foreach ($this->predicates as $pIndex => $p)
                if ($pIndex % 2 == 0)
                    $code .= $p->build($scope, $backend);   // predicate
                else
                    $code .= " {$p} ";      // boolean operator (and/or)
            $code .= ')' . $this->_cbs->eol;

            if (! $blockCode)
            {
                // If there is no "then" block, then there is an "else" block, so we just put in
                // an empty "then block"
                $code .= '{' . $this->_cbs->eol;
                $code .= '}';
            }
            else
            {
                if ($this->block->buildHasBraces == FALSE) {
                    $blockCode = $this->indent(1, $blockCode);
                }
                $code .= $blockCode;
            }

            if ($elseBlockCode)
            {
                $code .= $this->_cbs->eol . 'else' . $this->_cbs->eol;
                if ($this->elseBlock->buildHasBraces)
                    $code .= $elseBlockCode . $this->_cbs->eol;
                else
                    $code .= $this->indent(1, $elseBlockCode);
            }
        }

        return $code;
    }
}
