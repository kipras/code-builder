<?php
/**
 * Code Builder settings class.
 * You can change values of this class (or extend this class) to customize
 * the behavior of Code Builder (error reporting, etc.).
 */
class CBSettings
{
    const ERROR_TYPE_SYSTEM = "Type system error";
    const ERROR_AMBIGUOUS_TYPE = "Ambiguous type";
    const ERROR_UNEXPECTED_TYPE = "Unexpected type";
    const ERROR_WRONG_TYPE = "Wrong type";
    const ERROR_UNNAMED_TYPE = "Unnamed type";
    const ERROR_CONSTRUCTION = "Object graph construction error";


    /**
     * @var string
     */
    public $eol = "\n";

    /**
     * @var string
     */
    public $tab = "    ";

    /**
     * @var CBFactory
     */
    public $factory;

    /**
     * @var CBUtil
     */
    public $util;


    public function __construct()
    {
        $this->factory = new CBFactory($this);
        $this->util = new CBUtil($this);
    }


    public function indent($amount, $code = FALSE)
    {
        return $this->util->indent($amount, $code);
    }

    /**
     * Displays a warning and continues execution
     * @param string $text
     * @param string $title
     */
    public function warning($text, $title)
    {
        echo 'Warning: ' . $title . "\n" . $text . "\n\n";
    }

    /**
     * Displays an error. Execution should be stopped afterwards.
     * @param string $text
     * @param string $title
     * @throws Exception
     */
    public function error($text, $title)
    {
        throw new Exception('Error: ' . $title . "\n" . $text);
    }

    /**
     * @param string $text
     */
    public function typeSystemError($text)
    {
        $this->error($text, CBSettings::ERROR_TYPE_SYSTEM);
    }

    /**
     * @param string $text
     */
    public function constructionError($text)
    {
        $this->error($text, CBSettings::ERROR_CONSTRUCTION);
    }

    public function unexpectedTypeError()
    {
        $this->error('Unexpected type', CBSettings::ERROR_UNEXPECTED_TYPE);
    }
}
