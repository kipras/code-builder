<?php

/**
 * Class CBTypeAtomic
 * @property-read int $typeFlag
 */
class CBTypeAtomic extends CBType
{
    /**
     * @var int One of CBType::CB_ constants
     */
    protected $typeFlag;


    public function __construct(CBSettings $cbs, $typeFlag)
    {
        if (FALSE == (in_array($typeFlag, $this->atomicTypeFlags()))) {
            $cbs->error("Unknown type or type is not atomic", CBSettings::ERROR_UNEXPECTED_TYPE);
        }

        parent::__construct($cbs);

        $this->typeFlag = $typeFlag;
    }


    public function __get($name)
    {
        switch ($name) {
            case 'typeFlag':
                return $this->typeFlag;

            default:
                return parent::__get($name);
        }
    }


    public function __toArray()
    {
        return array_merge(parent::__toArray(), Array(
                'typeFlag' => $this->typeFlag,
            ));
    }
}
