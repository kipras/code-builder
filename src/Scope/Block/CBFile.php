<?php
class CBFile extends CBBlock
{
    /**
     * @var int Braces mode (whether to force them or not): one of the CBBlock::BRACES constants
     * @see CBBlock::$bracesMode
     */
    public $bracesMode = CBBlock::BRACES_MODE_NEVER;

    /**
     * @var CBClass[] Class(es) to be defined in this file
     */
    protected $_classes = Array();

    /**
     * @var string File name
     */
    public $name;


    /**
     * Adds a class definition to this file
     * @param CBClass $class
     */
    public function addClass(CBClass $class)
    {
        $class->setParentScope($this);
        $this->_classes[$class->name] = $class;
    }


    public function build(CBBackend $backend, CBFileBuildContext $ctx = NULL)
    {
        if ($ctx === NULL) {
            $ctx = new CBFileBuildContext();
        }

        $code = $backend->buildFileHeader($this);

        $splitNextPart = FALSE;

        if ($this->_dependencies)
        {
            $splitNextPart = TRUE;
            foreach ($this->_dependencies as $dep)
                $code .= "require_once '{$dep}';" . $this->_cbs->eol;
        }

        if ($this->_types) {
            $splitNextTypedef = FALSE;
            foreach ($this->_types as $t) {
                $typedef = $backend->buildTypeDefinition($t);
                if ($typedef) {
                    if ($splitNextPart OR $splitNextTypedef) {
                        $code .= $this->_cbs->eol . $this->_cbs->eol;
                    }
                    $code .= $typedef . $backend->endOfStatement();
                    $splitNextTypedef = TRUE;
                    $splitNextPart = TRUE;
                }
            }
        }

        if ($this->_fns)
        {
            if ($splitNextPart)
                // Different parts of code are split by double new-line
                $code .= $this->_cbs->eol . $this->_cbs->eol;

            $splitNextPart = TRUE;
            foreach ($this->_fns as $index => $fn)
            {
                $code .= $fn->build($backend);
                if ($index < count($this->_fns) - 1)
                    $code .= $this->_cbs->eol . $this->_cbs->eol;
            }
        }

        if ($this->_classes)
        {
            if ($splitNextPart)
                // Different parts of code are split by double new-line
                $code .= $this->_cbs->eol . $this->_cbs->eol;

            $splitNextPart = TRUE;
            foreach ($this->_classes as $index => $class)
            {
                $code .= $class->build($backend);
                if ($index < count($this->_classes) - 1)
                    $code .= $this->_cbs->eol . $this->_cbs->eol;
            }
        }

        $blockCode = parent::build($backend);
        if ($ctx->footer != '') {
            if ($blockCode != '') {
                $blockCode .= $this->_cbs->eol . $this->_cbs->eol;
            }
            $blockCode .= $ctx->footer;
        }

        if ($blockCode != '')
        {
            if ($splitNextPart) {
                // Different parts of code are split by double new-line
                $code .= $this->_cbs->eol . $this->_cbs->eol;
            }

            $code .= $backend->buildMainFunction($blockCode);
        }

        // Make sure file ends in a new line
        $code .= $this->_cbs->eol;

        return $code;
    }
}
