<?php
  /**
   * Squiz_Sniffs_Classes_ClassFileNameSniff.
   *
   * Tests that the file name and the name of the class contained within the file
   * match.
   */
  class CodeRobot_Sniffs_Classes_ClassFileNameSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array();
      return array(
        T_CLASS,
        T_INTERFACE,
      );
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens   = $phpcsFile->getTokens();
      $decName  = $phpcsFile->findNext(T_STRING, $stackPtr);
      $fullPath = basename($phpcsFile->getFilename());
      $fileName = substr($fullPath, 0, strrpos($fullPath, '.'));

      $found_class_name    = $tokens[$decName]['content'];
      $expected_class_name = $fileName;

      if($found_class_name !== $expected_class_name) {
        $error = $found_class_name . ' name doesn\'t match filename. Found "' . $found_class_name . '"; expected "' . $expected_class_name . '"';
        $phpcsFile->addError($error, $stackPtr);
      }
    }
  }
