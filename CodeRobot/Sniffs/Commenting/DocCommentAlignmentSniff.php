<?php


  /**
   * CodeRobot_Sniffs_Commenting_DocCommentAlignmentSniff
   *
   * Tests that the stars in a doc comment align correctly
   */
  class CodeRobot_Sniffs_Commenting_DocCommentAlignmentSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_DOC_COMMENT);
    }


    /**
     * Processes this test, when one of its tokens is encountered
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned
     * @param integer              $stackPtr  The position of the current token in the stack passed in $tokens
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      // We are only interested in function/class/interface doc block comments.
      $nextToken = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), NULL, TRUE);
      $ignore    = array(
        T_CLASS,
        T_INTERFACE,
        T_FUNCTION,
        T_PUBLIC,
        T_PRIVATE,
        T_PROTECTED,
        T_STATIC,
        T_ABSTRACT
      );
      if(in_array($tokens[$nextToken]['code'], $ignore) === FALSE) {
        // Could be a file comment.
        $prevToken = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr - 1), NULL, TRUE);
        if($tokens[$prevToken]['code'] !== T_OPEN_TAG) {
          return;
        }
      }

      // We only want to get the first comment in a block. If there is
      // a comment on the line before this one, return.
      $docComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($stackPtr - 1));
      if($docComment !== FALSE) {
        if($tokens[$docComment]['line'] === ($tokens[$stackPtr]['line'] - 1)) {
          return;
        }
      }

      $comments       = array($stackPtr);
      $currentComment = $stackPtr;
      $lastComment    = $stackPtr;
      while(($currentComment = $phpcsFile->findNext(T_DOC_COMMENT, ($currentComment + 1))) !== FALSE) {
        if($tokens[$lastComment]['line'] === ($tokens[$currentComment]['line'] - 1)) {
          $comments[]  = $currentComment;
          $lastComment = $currentComment;
        } else {
          break;
        }
      }

      // The $comments array now contains pointers to each token in the
      // comment block.
      $requiredColumn  = strpos($tokens[$stackPtr]['content'], '*');
      $requiredColumn += $tokens[$stackPtr]['column'];

      foreach($comments as $commentPointer) {
        // Check the spacing after each asterisk.
        $content   = $tokens[$commentPointer]['content'];
        $firstChar = substr($content, 0, 1);
        $lastChar  = substr($content, -1);
        if($firstChar !== '/' &&  $lastChar !== '/') {
          $matches = array();
          preg_match('|^(\s+)?\*(\s+)?@|', $content, $matches);
          if(empty($matches) === FALSE) {
            if(isset($matches[2]) === FALSE) {
              $error = 'Expected 1 space between asterisk and tag; 0 found';
              $phpcsFile->addError($error, $commentPointer);
            } else {
              $length = strlen($matches[2]);
              if($length !== 1) {
                $error = 'Expected 1 space between asterisk and tag; ' . $length . ' found';
                $phpcsFile->addError($error, $commentPointer);
              }
            }
          }
        }

        // Check the alignment of each asterisk.
        $currentColumn  = strpos($content, '*');
        $currentColumn += $tokens[$commentPointer]['column'];

        if($currentColumn === $requiredColumn) {
          // Star is aligned correctly.
          continue;
        }

        $expected  = ($requiredColumn - 1);
        $expected .= ($expected === 1) ? ' space' : ' spaces';
        $found     = ($currentColumn - 1);
        $error     = 'Expected ' . $expected . ' before asterisk; ' . $found . ' found';
        $phpcsFile->addError($error, $commentPointer);
      }
    }


  }