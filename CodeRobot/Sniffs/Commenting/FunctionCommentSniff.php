<?php


  /**
   * Parses and verifies the doc comments for functions
   *
   * Verifies that :
   *  - A comment exists
   *  - There is a blank newline after the short description
   *  - There is a blank newline between the long and short description
   *  - There is a blank newline between the long description and tags
   *  - Parameter names represent those in the method
   *  - Parameter comments are in the correct order
   *  - Parameter comments are complete
   *  - A type hint is provided for array and custom class
   *  - Type hint matches the actual variable/class type
   *  - A blank line is present before the first and after the last parameter
   *  - A return type exists
   *  - Any throw tag must have a comment
   *  - The tag order and indentation are correct
   */
  class CodeRobot_Sniffs_Commenting_FunctionCommentSniff implements PHP_CodeSniffer_Sniff {

    /**
     * The name of the method that we are currently processing.
     *
     * @var string
     */
    private $_methodName = '';

    /**
     * The position in the stack where the fucntion token was found.
     *
     * @var int
     */
    private $_functionToken = NULL;

    /**
     * The position in the stack where the class token was found.
     *
     * @var int
     */
    private $_classToken = NULL;

    /**
     * The index of the current tag we are processing.
     *
     * @var int
     */
    private $_tagIndex = 0;

    /**
     * The function comment parser for the current method.
     *
     * @var PHP_CodeSniffer_Comment_Parser_FunctionCommentParser
     */
    protected $commentParser = NULL;

    /**
     * The current PHP_CodeSniffer_File object we are processing.
     *
     * @var PHP_CodeSniffer_File
     */
    protected $currentFile = NULL;


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_FUNCTION);
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

      $find = array(
        T_COMMENT,
        T_DOC_COMMENT,
        T_CLASS,
        T_FUNCTION,
        T_OPEN_TAG
      );

      $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1));

      if ($commentEnd === FALSE) {
        return;
      }

      // If the token that we found was a class or a function, then this
      // function has no doc comment.
      $code = $tokens[$commentEnd]['code'];

      if ($code === T_COMMENT) {
        $error = 'You must use "/**" style comments for a function comment';
        $phpcsFile->addError($error, $stackPtr);
        return;
      } else if ($code !== T_DOC_COMMENT) {
        $error = 'Missing function doc comment';
        $phpcsFile->addError($error, $stackPtr);
        return;
      }

      // If there is any code between the function keyword and the doc block
      // then the doc block is not for us.
      $ignore    = PHP_CodeSniffer_Tokens::$scopeModifiers;
      $ignore[]  = T_STATIC;
      $ignore[]  = T_WHITESPACE;
      $ignore[]  = T_ABSTRACT;
      $ignore[]  = T_FINAL;
      $prevToken = $phpcsFile->findPrevious($ignore, ($stackPtr - 1), NULL, TRUE);
      if ($prevToken !== $commentEnd) {
        $phpcsFile->addError('Missing function doc comment', $stackPtr);
        return;
      }

      $this->_functionToken = $stackPtr;

      foreach ($tokens[$stackPtr]['conditions'] as $condPtr => $condition) {
        if ($condition === T_CLASS || $condition === T_INTERFACE) {
          $this->_classToken = $condPtr;
          break;
        }
      }

      // Find the first doc comment.
      $commentStart      = ($phpcsFile->findPrevious(T_DOC_COMMENT, ($commentEnd - 1), NULL, TRUE) + 1);
      $comment           = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));
      $this->_methodName = $phpcsFile->getDeclarationName($stackPtr);

      try {
        $this->commentParser = new PHP_CodeSniffer_CommentParser_FunctionCommentParser($comment, $phpcsFile);
        $this->commentParser->parse();
      } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
        $line = ($e->getLineWithinComment() + $commentStart);
        $phpcsFile->addError($e->getMessage(), $line);
        return;
      }

      $comment = $this->commentParser->getComment();
      if (is_null($comment) === TRUE) {
        $error = 'Function doc comment is empty';
        $phpcsFile->addError($error, $commentStart);
        return;
      }

      $this->processParams($commentStart, $commentEnd);
      $this->processSees($commentStart);
      $this->processReturn($commentStart, $commentEnd);
      $this->processThrows($commentStart);

      // Check for a comment description.
      $short = $comment->getShortComment();
      if (trim($short) === '') {
        $error = 'Missing short description in function doc comment';
        $phpcsFile->addError($error, $commentStart);
        return;
      }

      // No extra newline before short description.
      $newlineCount = 0;
      $newlineSpan  = strspn($short, $phpcsFile->eolChar);
      if ($short !== '' && $newlineSpan > 0) {
        $line  = ($newlineSpan > 1) ? 'newlines' : 'newline';
        $error = "Extra $line found before function comment short description";
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      $newlineCount = (substr_count($short, $phpcsFile->eolChar) + 1);

      // Exactly one blank line between short and long description.
      $long = $comment->getLongComment();
      if (empty($long) === FALSE) {
        $between        = $comment->getWhiteSpaceBetween();
        $newlineBetween = substr_count($between, $phpcsFile->eolChar);
        if ($newlineBetween !== 2) {
          $error = 'There must be exactly one blank line between descriptions in function comment';
          $phpcsFile->addError($error, ($commentStart + $newlineCount + 1));
        }

        $newlineCount += $newlineBetween;

        $testLong = trim($long);
        if (preg_match('|[A-Z]|', $testLong[0]) === 0) {
          $error = 'Function comment long description must start with a capital letter';
          $phpcsFile->addError($error, ($commentStart + $newlineCount));
        }
      }

      // Exactly one blank line before tags.
      $params = $this->commentParser->getTagOrders();
      if (count($params) > 1) {
        $newlineSpan = $comment->getNewlineAfter();
        if ($newlineSpan !== 2) {
          $error = 'There must be exactly one blank line before the tags in function comment';
          if ($long !== '') {
            $newlineCount += (substr_count($long, $phpcsFile->eolChar) - $newlineSpan + 1);
          }

          $phpcsFile->addError($error, ($commentStart + $newlineCount));
          $short = rtrim($short, $phpcsFile->eolChar . ' ');
        }
      }

      // Short description must be single line and not end with a full stop.
      $testShort = trim($short);
      $lastChar  = $testShort[(strlen($testShort) - 1)];
      if (substr_count($testShort, $phpcsFile->eolChar) !== 0) {
        $error = 'Function comment short description must be on a single line';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      if ($testShort == 'undocumented function') {
        $error = 'Function requires a short description, has a placeholder';
        $phpcsFile->addError($error, ($commentStart + 1));
      } else {
        if (preg_match('|[A-Z]|', $testShort[0]) === 0) {
          $error = 'Function comment short description must start with a capital letter';
          $phpcsFile->addError($error, ($commentStart + 1));
        }
      }

      if ($lastChar == '.') {
        $error = 'Function comment should not end with a period';
        $phpcsFile->addError($error, ($commentStart + 1));
      }

      // Check for unknown/deprecated tags.
      $unknownTags = $this->commentParser->getUnknown();
      foreach ($unknownTags as $errorTag) {
        $error = "@$errorTag[tag] tag is not allowed in function comment";
        $phpcsFile->addWarning($error, ($commentStart + $errorTag['line']));
      }
    }


    /**
     * Process the see tags
     *
     * @param integer $commentStart The position in the stack where the comment started
     *
     * @return void
     */
    protected function processSees($commentStart) {
      $sees = $this->commentParser->getSees();
      if (empty($sees) === FALSE) {
        $tagOrder = $this->commentParser->getTagOrders();
        $index    = array_keys($this->commentParser->getTagOrders(), 'see');
        foreach ($sees as $i => $see) {
          $errorPos = ($commentStart + $see->getLine());
          $since    = array_keys($tagOrder, 'since');
          if (count($since) === 1 && $this->_tagIndex !== 0) {
            $this->_tagIndex++;
            if ($index[$i] !== $this->_tagIndex) {
              $error = 'The @see tag is in the wrong order; the tag follows @since';
              $this->currentFile->addError($error, $errorPos);
            }
          }

          $content = $see->getContent();
          if (empty($content) === TRUE) {
            $error = 'Content missing for @see tag in function comment';
            $this->currentFile->addError($error, $errorPos);
            continue;
          }

          $spacing = substr_count($see->getWhitespaceBeforeContent(), ' ');
          if ($spacing !== 4) {
            $error  = '@see tag indented incorrectly; ';
            $error .= "expected 4 spaces but found $spacing";
            $this->currentFile->addError($error, $errorPos);
          }
        }
      }
    }


    /**
     * Process the return comment of this function comment
     *
     * @param integer $commentStart The position in the stack where the comment started
     * @param integer $commentEnd   The position in the stack where the comment ended
     *
     * @return void
     */
    protected function processReturn($commentStart, $commentEnd) {
      // Skip constructor and destructor.
      $className = '';
      if ($this->_classToken !== NULL) {
        $className = $this->currentFile->getDeclarationName($this->_classToken);
        $className = strtolower(ltrim($className, '_'));
      }

      $methodName      = strtolower(ltrim($this->_methodName, '_'));
      $isSpecialMethod = ($this->_methodName === '__construct' || $this->_methodName === '__destruct');
      $return          = $this->commentParser->getReturn();

      if ($isSpecialMethod === FALSE && $methodName !== $className) {
        if ($return !== NULL) {
          $tagOrder = $this->commentParser->getTagOrders();
          $index    = array_keys($tagOrder, 'return');
          $errorPos = ($commentStart + $return->getLine());
          $content  = trim(str_replace('*', '', $return->getRawContent()));

          if (count($index) > 1) {
            $error = 'Only 1 @return tag is allowed in function comment';
            $this->currentFile->addError($error, $errorPos);
            return;
          }

          $since = array_keys($tagOrder, 'since');
          if (count($since) === 1 && $this->_tagIndex !== 0) {
            $this->_tagIndex++;
            if ($index[0] !== $this->_tagIndex) {
              $error = 'The @return tag is in the wrong order; the tag follows @see (if used) or @since';
              $this->currentFile->addError($error, $errorPos);
            }
          }

          if (empty($content) === TRUE) {
            $error = 'Return type missing for @return tag in function comment';
            $this->currentFile->addError($error, $errorPos);
          } else {
            // Check return type (can be multiple, separated by '|').
            $typeNames      = explode('|', $content);
            $suggestedNames = array();
            foreach ($typeNames as $i => $typeName) {
              $suggestedName = PHP_CodeSniffer::suggestType($typeName);
              if (in_array($suggestedName, $suggestedNames) === FALSE) {
                $suggestedNames[] = $suggestedName;
              }
            }

            $suggestedType = implode('|', $suggestedNames);
            if ($content !== $suggestedType) {
              $error = "Function return type \"$content\" is invalid. Use $suggestedType.";
              $this->currentFile->addError($error, $errorPos);
            }

            $tokens = $this->currentFile->getTokens();

            // If the return type is void, make sure there is
            // no return statement in the function.
            if ($content === 'void') {
              if (isset($tokens[$this->_functionToken]['scope_closer']) === TRUE) {
                $endToken = $tokens[$this->_functionToken]['scope_closer'];
                $return   = $this->currentFile->findNext(T_RETURN, $this->_functionToken, $endToken);
                if ($return !== FALSE) {
                  // If the function is not returning anything, just
                  // exiting, then there is no problem.
                  $semicolon = $this->currentFile->findNext(T_WHITESPACE, ($return + 1), NULL, TRUE);
                  if ($tokens[$semicolon]['code'] !== T_SEMICOLON) {
                    $error = 'Function return type is void, but function contains return statement';
                    $this->currentFile->addError($error, $errorPos);
                  }
                }
              }
            } else if ($content !== 'mixed') {
              // If return type is not void, there needs to be a
              // returns statement somewhere in the function that
              // returns something.
              if (isset($tokens[$this->_functionToken]['scope_closer']) === TRUE) {
                $endToken = $tokens[$this->_functionToken]['scope_closer'];
                $return   = $this->currentFile->findNext(T_RETURN, $this->_functionToken, $endToken);
                if ($return === FALSE) {
                  $error = 'Function return type is not void, but function has no return statement';
                  $this->currentFile->addError($error, $errorPos);
                } else {
                  $semicolon = $this->currentFile->findNext(T_WHITESPACE, ($return + 1), NULL, TRUE);
                  if ($tokens[$semicolon]['code'] === T_SEMICOLON) {
                    $error = 'Function return type is not void, but function is returning void here';
                    $this->currentFile->addError($error, $return);
                  }
                }
              }
            }
          }
        } else {
          $error = 'Missing @return tag in function comment';
          $this->currentFile->addError($error, $commentEnd);
        }
      }
    }


    /**
     * Process any throw tags that this function comment has
     *
     * @param integer $commentStart The position in the stack where the comment started
     *
     * @return void
     */
    protected function processThrows($commentStart) {
      if (count($this->commentParser->getThrows()) === 0) {
        return;
      }

      $tagOrder = $this->commentParser->getTagOrders();
      $index    = array_keys($this->commentParser->getTagOrders(), 'throws');

      foreach ($this->commentParser->getThrows() as $i => $throw) {
        $exception = $throw->getValue();
        $content   = trim($throw->getComment());
        $errorPos  = ($commentStart + $throw->getLine());
        if (empty($exception) === TRUE) {
          $error = 'Exception type and comment missing for @throws tag in function comment';
          $this->currentFile->addError($error, $errorPos);
        } else if (empty($content) === TRUE) {
          $error = 'Comment missing for @throws tag in function comment';
          $this->currentFile->addError($error, $errorPos);
        } else {
          // Starts with a capital letter and not end with a period.
          $firstChar = $content{0};
          if (strtoupper($firstChar) !== $firstChar) {
            $error = '@throws tag comment must start with a capital letter';
            $this->currentFile->addError($error, $errorPos);
          }

          $lastChar = $content[(strlen($content) - 1)];
          if ($lastChar == '.') {
            $error = '@throws tag comment must not end with a period';
            $this->currentFile->addError($error, $errorPos);
          }
        }

        $since = array_keys($tagOrder, 'since');
        if (count($since) === 1 && $this->_tagIndex !== 0) {
          $this->_tagIndex++;
          if ($index[$i] !== $this->_tagIndex) {
            $error = 'The @throws tag is in the wrong order; the tag follows @return';
            $this->currentFile->addError($error, $errorPos);
          }
        }
      }

    }


    /**
     * Process the function parameter comments
     *
     * @param integer $commentStart The position in the stack where the comment started
     * @param integer $commentEnd   The position in the stack where the comment ended
     *
     * @return void
     */
    protected function processParams($commentStart, $commentEnd) {
      $realParams  = $this->currentFile->getMethodParameters($this->_functionToken);
      $params      = $this->commentParser->getParams();
      $foundParams = array();

      if (empty($params) === FALSE) {
        if (substr_count($params[(count($params) - 1)]->getWhitespaceAfter(), $this->currentFile->eolChar) !== 2) {
          $error    = 'Last parameter comment requires a blank newline after it';
          $errorPos = ($params[(count($params) - 1)]->getLine() + $commentStart);
          $this->currentFile->addError($error, $errorPos);
        }

        // Parameters must appear immediately after the comment.
        if ($params[0]->getOrder() !== 2) {
          $error    = 'Parameters must appear immediately after the comment';
          $errorPos = ($params[0]->getLine() + $commentStart);
          $this->currentFile->addError($error, $errorPos);
        }

        $previousParam      = NULL;
        $spaceBeforeVar     = 10000;
        $spaceBeforeComment = 10000;
        $longestType        = 0;
        $longestVar         = 0;

        foreach ($params as $param) {
          $paramComment = trim($param->getComment());
          $errorPos     = ($param->getLine() + $commentStart);

          // Make sure that there is only one space before the var type.
          if ($param->getWhitespaceBeforeType() !== ' ') {
            $error = 'Expected 1 space before variable type';
            $this->currentFile->addError($error, $errorPos);
          }

          $spaceCount = substr_count($param->getWhitespaceBeforeVarName(), ' ');
          if ($spaceCount < $spaceBeforeVar) {
            $spaceBeforeVar = $spaceCount;
            $longestType    = $errorPos;
          }

          $spaceCount = substr_count($param->getWhitespaceBeforeComment(), ' ');

          if ($spaceCount < $spaceBeforeComment && $paramComment !== '') {
            $spaceBeforeComment = $spaceCount;
            $longestVar         = $errorPos;
          }

          // Make sure they are in the correct order, and have the correct name.
          $pos       = $param->getPosition();
          $paramName = ($param->getVarName() !== '') ? $param->getVarName() : '[ UNKNOWN ]';

          if ($previousParam !== NULL) {
            $previousName = ($previousParam->getVarName() !== '') ? $previousParam->getVarName() : 'UNKNOWN';

            // Check to see if the parameters align properly.
            if ($param->alignsVariableWith($previousParam) === FALSE) {
              $error = 'The variable names for parameters ' . $previousName . ' (' . ($pos - 1) . ') and ' . $paramName . ' (' . $pos . ') do not align';
              $this->currentFile->addError($error, $errorPos);
            }

            if ($param->alignsCommentWith($previousParam) === FALSE) {
              $error = 'The comments for parameters ' . $previousName . ' (' . ($pos - 1) . ') and ' . $paramName . ' (' . $pos . ') do not align';
              $this->currentFile->addError($error, $errorPos);
            }
          }

          // Variable must be one of the supported standard type.
          $typeNames = explode('|', $param->getType());
          foreach ($typeNames as $typeName) {
            $suggestedName = PHP_CodeSniffer::suggestType($typeName);
            if ($typeName !== $suggestedName) {
              $error = "Expected \"$suggestedName\"; found \"$typeName\" for $paramName at position $pos";
              $this->currentFile->addError($error, $errorPos);
            } else if (count($typeNames) === 1) {
              // Check type hint for array and custom type.
              $suggestedTypeHint = '';
              if (strpos($suggestedName, 'array') !== FALSE) {
                $suggestedTypeHint = 'array';
              } else if (in_array($typeName, PHP_CodeSniffer::$allowedTypes) === FALSE) {
                $suggestedTypeHint = $suggestedName;
              }

              if ($suggestedTypeHint !== '' && isset($realParams[($pos - 1)]) === TRUE) {
                if ($suggestedTypeHint != 'array') {
                  $typeHint = $realParams[($pos - 1)]['type_hint'];
                  if ($typeHint === '') {
                    $error = "Type hint \"$suggestedTypeHint\" missing for $paramName at position $pos";
                    $this->currentFile->addError($error, ($commentEnd + 2));
                  } else if ($typeHint !== $suggestedTypeHint) {
                    $error = "Expected type hint \"$suggestedTypeHint\"; found \"$typeHint\" for $paramName at position $pos";
                    $this->currentFile->addError($error, ($commentEnd + 2));
                  }
                }
              } else if ($suggestedTypeHint === '' && isset($realParams[($pos - 1)]) === TRUE) {
                $typeHint = $realParams[($pos - 1)]['type_hint'];
                if ($typeHint !== '') {
                  $error = "Unknown type hint \"$typeHint\" found for $paramName at position $pos";
                  $this->currentFile->addError($error, ($commentEnd + 2));
                }
              }
            }
          }

          // Make sure the names of the parameter comment matches the
          // actual parameter.
          if (isset($realParams[($pos - 1)]) === TRUE) {
            $realName      = $realParams[($pos - 1)]['name'];
            $foundParams[] = $realName;

            // Append ampersand to name if passing by reference.
            if ($realParams[($pos - 1)]['pass_by_reference'] === TRUE) {
              $realName = '&' . $realName;
            }

            if ($realName !== $param->getVarName()) {
              $error  = 'Doc comment var "' . $paramName;
              $error .= '" does not match actual variable name "' . $realName;
              $error .= '" at position ' . $pos;
              $this->currentFile->addError($error, $errorPos);
            }
          } else {
            // We must have an extra parameter comment.
            $error = 'Superfluous doc comment at position ' . $pos;
            $this->currentFile->addError($error, $errorPos);
          }

          if ($param->getVarName() === '') {
            $error = 'Missing parameter name at position ' . $pos;
            $this->currentFile->addError($error, $errorPos);
          }

          if ($param->getType() === '') {
            $error = 'Missing type at position ' . $pos;
            $this->currentFile->addError($error, $errorPos);
          }

          if ($paramComment === '') {
            $error = 'Missing comment for param "' . $paramName . '" at position ' . $pos;
            $this->currentFile->addError($error, $errorPos);
          } else {
            // Param comments must start with a capital letter and
            // not end with the full stop.
            $firstChar = $paramComment{0};
            if (preg_match('|[A-Z]|', $firstChar) === 0) {
              $error = 'Param comment must start with a capital letter';
              $this->currentFile->addError($error, $errorPos);
            }

            $lastChar = $paramComment[(strlen($paramComment) - 1)];
            if ($lastChar == '.') {
              $error = 'Param comments must not end with a period';
              $this->currentFile->addError($error, $errorPos);
            }
          }

          $previousParam = $param;
        }

        if ($spaceBeforeVar !== 1 && $spaceBeforeVar !== 10000 && $spaceBeforeComment !== 10000) {
          $error = 'Expected 1 space after the longest type';
          $this->currentFile->addError($error, $longestType);
        }

        if ($spaceBeforeComment !== 1 && $spaceBeforeComment !== 10000) {
          $error = 'Expected 1 space after the longest variable name';
          $this->currentFile->addError($error, $longestVar);
        }

      }

      $realNames = array();
      foreach ($realParams as $realParam) {
        $realNames[] = $realParam['name'];
      }

      // Report missing comments.
      $diff = array_diff($realNames, $foundParams);
      foreach ($diff as $neededParam) {
        if (count($params) !== 0) {
          $errorPos = ($params[(count($params) - 1)]->getLine() + $commentStart);
        } else {
          $errorPos = $commentStart;
        }

        $error = 'Doc comment for "' . $neededParam . '" missing';
        $this->currentFile->addError($error, $errorPos);
      }
    }


  }