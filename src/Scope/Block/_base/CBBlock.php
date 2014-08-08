<?php
/**
 * Class CBBlock
 * The difference between a CBBlock and a CBScope is that a CBBlock contains code in itself, whereas a CBScope is
 * only a container for variables and functions but contains no code in itself.
 * As a consequence - CBBlock is buildable (whereas CBScope is not)
 * A CBScope is therefore used to make things like CBClass, which has no code in itself.
 * Whereas CBBlock is the base for things like CBFile, CBFunction, loops, etc. All of which contain not only inner
 * variables and functions but also contain actual code.
 */
class CBBlock extends CBScope
{
    /**#@+
     * Block brace mode which is stored at @see $bracesMode
     * Possible values are:
     * - BRACES_MODE_NEVER : never wrap block contents with braces. This value should not be used by manual
     *      configuration, because it would not make sense since if a standard block (conditional, loop, etc.)
     *      contains more than one line - it must be wrapped with braces.
     *      However this value is used internally for certain block types that do not require their contents to be
     *      surrounded by braces, e.g. @see CBFile
     * - BRACES_MODE_ALWAYS : always wrap block contents with braces. This value can be used by manual configuration
     *      and is also used for certain block types that require their contents to always have braces,
     *      e.g. @see CBFunction
     * - BRACES_MODE_AUTO : only wrap block contents with braces if block contains more than one line of code.
     *      This is the default value.
     */
    const BRACES_MODE_AUTO = 1;
    const BRACES_MODE_ALWAYS = 2;
    const BRACES_MODE_NEVER = 3;
    /**#@-*/


    /**
     * @var CBBlock[] These are inner (child) blocks that are added to this block. Loops go here as well.
     * Inner functions have a separate container (in the parent CBScope class).
     */
    protected $_blocks = Array();

    /**
     * @var CBIf[] Inner conditional blocks
     */
    protected $_ifs = Array();

    /**
     * @var CBMutVarAssignment[]
     */
    protected $_mutVarAssignments = Array();

    /**
     * @var int Braces mode (whether to force them or not): one of the CBBlock::BRACES constants
     */
    public $bracesMode = self::BRACES_MODE_AUTO;

    /**
     * @var CBValue Return value
     */
    public $return;

    /**
     * @var bool This is filled with the correct value, during CBBlock->build(). That value is then used later by some other steps of the build() process.
     */
    public $buildHasBraces;


    /**
     * Adds a child block
     * @param CBBlock $block
     */
    public function addBlock(CBBlock $block)
    {
        $block->setParentScope($this);
        if (! in_array($block, $this->_blocks, TRUE)) {
            $this->_blocks[] = $block;
        }
    }

    /**
     * @return CBBlock[] All child blocks
     */
    public function getAllBlocks()
    {
        return $this->_blocks;
    }


    /**
     * Adds a conditional block
     * @param CBIf $if
     */
    public function addIf(CBIf $if)
    {
        if (! in_array($if, $this->_ifs, TRUE)) {
            $this->_ifs[] = $if;
            $if->block->setParentScope($this);
            $if->elseBlock->setParentScope($this);
        }
    }

    /**
     * @return CBIf[] All inner conditional blocks
     */
    public function getAllIfs()
    {
        return $this->_ifs;
    }


    /**
     * Adds an assignment to a mutable variable
     * @param CBMutVarAssignment $mutVarAssignment
     */
    public function addMutVarAssignment(CBMutVarAssignment $mutVarAssignment)
    {
        if (! in_array($mutVarAssignment, $this->_mutVarAssignments, TRUE)) {
            $this->_mutVarAssignments[] = $mutVarAssignment;
        }
    }

    /**
     * @return CBMutVarAssignment[]
     */
    public function getAllMutVarAssigments()
    {
        return $this->_mutVarAssignments;
    }


    /**
     * @param string $code
     * @return string
     */
    protected function addBraces($code)
    {
        return '{' . $this->_cbs->eol
        . $code
        . '}';
    }


    /**
     * CBBlock::build() differs from the CBBuildableEntity::build() - it does not take the $scope parameter, because
     * it does not need it, since it itself is the scope.
     * @param CBBackend $backend
     * @return string
     */
    public function build(CBBackend $backend)
    {
        $totalSentences = 0;

        $allDeclaredVars = $this->getAllDeclaredVars();
        $allBlocks = $this->getAllBlocks();
        $allIfs = $this->getAllIfs();
        $fnCalls = $this->getAllFnCalls();
        $mutVarAssignments = $this->getAllMutVarAssigments();

        $splitNextPart = FALSE;

        $code = '';

        // Start by declaring and initializing all variables
        if ($allDeclaredVars)
        {
            if ($splitNextPart)
                // Different parts of code are split by a single new-line
                $code .= $this->_cbs->eol;

            $splitNextPart = TRUE;
            foreach ($allDeclaredVars as $var)
            {
                $variableDeclaration = $this->buildVariableDeclaration($var, $backend);
                if ($variableDeclaration !== NULL) {
                    $code .= $variableDeclaration;
                    $totalSentences++;
                }
            }
        }

        // Build inner blocks
        if ($allBlocks)
        {
            $insertNewLineBeforeBlock = $splitNextPart;
            foreach ($allBlocks as $block)
            {
                $blockCode = $block->build($backend);
                if ($blockCode) {
                    if ($insertNewLineBeforeBlock) {
                        // Different parts of code are split by a single new-line
                        $code .= $this->_cbs->eol;
                        $insertNewLineBeforeBlock = FALSE;
                    }

                    $splitNextPart = TRUE;
                    $code .= $blockCode . $this->_cbs->eol;
                    $totalSentences++;
                }
            }
        }

        // Build ifs
        if ($allIfs)
        {
            if ($splitNextPart)
                // Different parts of code are split by a single new-line
                $code .= $this->_cbs->eol;

            $splitNextPart = TRUE;
            foreach ($allIfs as $if)
            {
                $code .= $if->build($this, $backend) . $this->_cbs->eol;
                $totalSentences++;
            }
        }

        // Build function calls
        if ($fnCalls)
        {
            if ($splitNextPart)
                // Different parts of code are split by a single new-line
                $code .= $this->_cbs->eol;

            $splitNextPart = TRUE;
            foreach ($fnCalls as $call)
            {
                $code .= $call->build($this, $backend) . ';' . $this->_cbs->eol;
                $totalSentences++;
            }
        }

        // Build mutable variable assignments
        if ($mutVarAssignments) {
            if ($splitNextPart) {
                // Different parts of code are split by a single new-line
                $code .= $this->_cbs->eol;
            }

            $splitNextPart = TRUE;
            foreach ($mutVarAssignments as $asgn) {
                $code .= $asgn->build($this, $backend) . ';' . $this->_cbs->eol;
                $totalSentences++;
            }
        }

        if ($this->return !== NULL)
        {
            if ($splitNextPart)
                // Different parts of code are split by a single new-line
                $code .= $this->_cbs->eol;

            $returnValBuilt = $this->val($this->return, $this, $backend);
            if ($returnValBuilt === NULL)
                $this->_cbs->error("Trying to return a nonreachable value: '{$this->return}'", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

            $code .= "return {$returnValBuilt};" . $this->_cbs->eol;
            $totalSentences++;
        }

        // If there are more than one sentences in the block - wrap it with braces : { }
        if ($this->bracesMode == self::BRACES_MODE_ALWAYS OR ($this->bracesMode == self::BRACES_MODE_AUTO AND $totalSentences > 1))
        {
            if ($code != '') {
                $code = $this->indent(1, $code);
            }
            $code = $this->addBraces($code);
            $this->buildHasBraces = TRUE;
        }
        else
        {
            // If there is only one sentence - strip the EnfOfLine at the end
            if ($code != '')
                $code = substr($code, 0, - strlen($this->_cbs->eol));

            $this->buildHasBraces = FALSE;
        }

        return $code;
    }

    /**
     * Returns all variables that should be declared in this scope.
     * This contains:
     * - No superglobals
     * - If this is a naming scope: all variables that this naming scope contains,
     *      except for undeclared variables (e.g. function parameters)
     * - If this is not a naming scope: all initialized variables that are directly
     *      contained in this scope. Non-initialized variables are not returned,
     *      since there is no need to declare non-initialized variables in container
     *      scope again as they will already be declared in some parent naming scope.
     * @return CBBaseVariable[]
     */
    private function getAllDeclaredVars()
    {
        $allDeclaredVars = Array();
        foreach ($this->_namingVars as $namingVar) {
            if ($namingVar->isDeclared OR $namingVar->isInitialized()) {
                $allDeclaredVars[] = $namingVar;
            }
        }
        foreach ($this->_vars as $containedVar) {
            if (FALSE == in_array($containedVar, $allDeclaredVars, TRUE) AND $containedVar->isInitialized()) {
                $allDeclaredVars[] = $containedVar;
            }
        }
        foreach ($allDeclaredVars as $varIdx => $var) {
            if ($var instanceof CBVariable AND $var->superGlobal) {
                unset($allDeclaredVars[$varIdx]);
            }
        }
        $allDeclaredVars = array_values($allDeclaredVars);

        return $allDeclaredVars;
    }

    /**
     * Builds a variable declaration or NULL if variable could not be built
     * @param CBBaseVariable $var
     * @param CBBackend $backend
     * @return string|null
     */
    private function buildVariableDeclaration(CBBaseVariable $var, CBBackend $backend)
    {
        if ($var->isInitialized() == FALSE OR $var->getParentScope() !== $this)
        {
            // If a variable is not initialized - just add a single line
            // with a comment, containing that variable name
            // (to show that this variable belongs to this naming scope)
            return '// ' . '$' . $var->name . $backend->endOfStatement() . $this->_cbs->eol;
        }
        else
        {
            $builtVar = $var->build($this, $backend) . $this->_cbs->eol;
            if ($builtVar !== NULL) {
                return $builtVar;
            }
        }

        return NULL;
    }
}
