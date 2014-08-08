<?php

/**
 * Class CBTypeUnknown
 * This type is passed around instead of NULL values for things that have no type information set yet.
 * The benefit is the reduced need for "if ($type !== NULL) {...}".
 */
class CBTypeUnknown extends CBType
{
}
