<?php


  /**
   * Checks the nesting level for methods.
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Metrics_NestingLevelSniff implements PHP_CodeSniffer_Sniff {


    /**
     * A nesting level than this value will throw a warning.
     *
     * @var int
     */
    protected $nestingLevel = 3;


    /**
     * A nesting level than this value will throw an error.
     *
     * @var int
     */
    protected $absoluteNestingLevel = 10;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(T_FUNCTION);
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore abstract methods.
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        // Detect start and end of this function definition.
        $start = $tokens[$stackPtr]['scope_opener'];
        $end   = $tokens[$stackPtr]['scope_closer'];

        $nestingLevel = 0;

        // Find the maximum nesting level of any token in the function.
        for ($i = ($start + 1); $i < $end; $i++) {
            $level = $tokens[$i]['level'];
            if ($nestingLevel < $level) {
                $nestingLevel = $level;
            }
        }

        // We subtract the nesting level of the function itself.
        $nestingLevel = ($nestingLevel - $tokens[$stackPtr]['level'] - 1);

        if ($nestingLevel > $this->absoluteNestingLevel) {
            $error = "Function's nesting level ($nestingLevel) exceeds allowed maximum of ".$this->absoluteNestingLevel;
            $phpcsFile->addError($error, $stackPtr);
        } else if ($nestingLevel > $this->nestingLevel) {
            $warning = "Function's nesting level ($nestingLevel) exceeds ".$this->nestingLevel.'; consider refactoring the function';
            $phpcsFile->addWarning($warning, $stackPtr);
        }

    }
}