<?php


  /**
   * Parses and verifies the class doc comment
   *
   * Verifies that :
   * - A class doc comment exists.
   * - There is exactly one blank line before the class comment.
   * - Short description ends with a full stop.
   * - There is a blank line after the short description.
   * - Each paragraph of the long description ends with a full stop.
   * - There is a blank line between the description and the tags.
   * - Check the format of the since tag (x.x.x).
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_CLASS);
    }


    /**
     * Processes this test, when one of its tokens is encountered
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned
     * @param integer              $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $this->currentFile = $phpcsFile;

      $tokens = $phpcsFile->getTokens();
      $find   = array(
        T_ABSTRACT,
        T_WHITESPACE,
        T_FINAL
      );

      // Extract the class comment docblock.
      $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), NULL, TRUE);

      if ($commentEnd !== FALSE && $tokens[$commentEnd]['code'] === T_COMMENT) {
        $phpcsFile->addError('You must use "/**" style comments for a class comment', $stackPtr);
        return;
      } else if ($commentEnd === FALSE || $tokens[$commentEnd]['code'] !== T_DOC_COMMENT) {
        $phpcsFile->addError('Missing class doc comment', $stackPtr);
        return;
      }

      $commentStart = ($phpcsFile->findPrevious(T_DOC_COMMENT, ($commentEnd - 1), NULL, TRUE) + 1);
      $commentNext  = $phpcsFile->findPrevious(T_WHITESPACE, ($commentEnd + 1), $stackPtr, FALSE, $phpcsFile->eolChar);

      // Distinguish file and class comment.
      $prevClassToken = $phpcsFile->findPrevious(T_CLASS, ($stackPtr - 1));
      if ($prevClassToken === FALSE) {
        // This is the first class token in this file, need extra checks.
        $prevNonComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($commentStart - 1), NULL, TRUE);
        if ($prevNonComment !== FALSE) {
          $prevComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($prevNonComment - 1));
          if ($prevComment === FALSE) {
            // There is only 1 doc comment between open tag and class token.
            $newlineToken = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), $stackPtr, FALSE, $phpcsFile->eolChar);
            if ($newlineToken !== FALSE) {
              $newlineToken = $phpcsFile->findNext(T_WHITESPACE, ($newlineToken + 1), $stackPtr, FALSE, $phpcsFile->eolChar);
              if ($newlineToken !== FALSE) {
                // Blank line between the class and the doc block.
                // The doc block is most likely a file comment.
                $phpcsFile->addError('Missing class doc comment', ($stackPtr + 1));
                return;
              }
            }
          }

          // Exactly two blank line before the class comment.
          $prevTokenEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($commentStart - 1), NULL, TRUE);
          if ($prevTokenEnd !== FALSE) {
            $blankLineBefore = 0;
            for ($i = ($prevTokenEnd + 1); $i < $commentStart; $i++) {
              if ($tokens[$i]['code'] === T_WHITESPACE && $tokens[$i]['content'] === $phpcsFile->eolChar) {
                $blankLineBefore++;
              }
            }
            if ($blankLineBefore !== 2) {
              $error = 'There must be exactly two blank lines immediately before the class comment (' . $blankLineBefore . ')';
              $phpcsFile->addError($error, ($commentStart - 1));
            }
          }
        }
      }

      $comment = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));

      // Parse the class comment docblock.
      try {
        $this->commentParser = new PHP_CodeSniffer_CommentParser_ClassCommentParser($comment, $phpcsFile);
        $this->commentParser->parse();
      } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
        $line = ($e->getLineWithinComment() + $commentStart);
        $phpcsFile->addError($e->getMessage(), $line);
        return;
      }

      $comment = $this->commentParser->getComment();
      if (is_null($comment) === TRUE) {
        $error = 'Class doc comment is empty';
        $phpcsFile->addError($error, $commentStart);
        return;
      }

      // Check for a comment description.
      $short = rtrim($comment->getShortComment(), $phpcsFile->eolChar);
      if (trim($short) === '') {
        return;
      }

      // No extra newline before short description.
      $newlineCount = 0;
      $newlineSpan  = strspn($short, $phpcsFile->eolChar);
      if ($short !== '' && $newlineSpan > 0) {
        $line  = ($newlineSpan > 1) ? 'newlines' : 'newline';
        $error = 'Extra ' . $line . ' found before class comment short description';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      $newlineCount = (substr_count($short, $phpcsFile->eolChar) + 1);

      // Exactly one blank line between short and long description.
      $long = $comment->getLongComment();
      if (empty($long) === FALSE) {
        $between        = $comment->getWhiteSpaceBetween();
        $newlineBetween = substr_count($between, $phpcsFile->eolChar);
        if ($newlineBetween !== 2) {
          $error = 'There must be exactly one blank line between descriptions in class comment';
          $phpcsFile->addError($error, ($commentStart + $newlineCount + 1));
        }

        $newlineCount += $newlineBetween;

        $testLong = trim($long);
        if (preg_match('|[A-Z]|', $testLong[0]) === 0) {
          $error = 'Class comment long description must start with a capital letter';
          $phpcsFile->addError($error, ($commentStart + $newlineCount));
        }
      }

      // Exactly one blank line before tags.
      $tags = $this->commentParser->getTagOrders();
      if (count($tags) > 1) {
        $newlineSpan = $comment->getNewlineAfter();
        if ($newlineSpan !== 2) {
          $error = 'There must be exactly one blank line before the tags in class comment';
          if ($long !== '') {
            $newlineCount += (substr_count($long, $phpcsFile->eolChar) - $newlineSpan + 1);
          }

          $phpcsFile->addError($error, ($commentStart + $newlineCount));
          $short = rtrim($short, $phpcsFile->eolChar . ' ');
        }
      }

      // Short description must be single line and end with a full stop.
      $testShort = trim($short);
      $lastChar  = $testShort[(strlen($testShort) - 1)];
      if (substr_count($testShort, $phpcsFile->eolChar) !== 0) {
        $error = 'Class comment short description must be on a single line';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      if (preg_match('|[A-Z]|', $testShort[0]) === 0) {
        $error = 'Class comment short description must start with a capital letter';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      if ($lastChar == '.') {
        $error = 'Class comment short description must not end with a period';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      // Check for unknown/deprecated tags.
      $unknownTags = $this->commentParser->getUnknown();
      foreach ($unknownTags as $errorTag) {
        $error = "@$errorTag[tag] tag is not allowed in class comment";
        $phpcsFile->addWarning($error, ($commentStart + $errorTag['line']));
        return;
      }
    }


  }