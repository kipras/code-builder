<?php

/**
 * Class CBTestNamingBlock
 * A version of CBBlock for testing which can be used as a top-level container.
 * A standard CBBlock cannot be used as a top-level container, because it is not a naming block.
 */
class CBTestNamingBlock extends CBBlock
{
    protected function isNamingScope()
    {
        return TRUE;
    }
}
