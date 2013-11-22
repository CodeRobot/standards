<?php


  /**
   * CodeRobot/Commenting/ProfanityCommentsSniff Test
   *
   * This scans comments for profanity only.
   *
   * Expected Result:
   *   --------------------------------------------------------------------------------
   *   FOUND 1 ERROR(S) AFFECTING 1 LINE(S)
   *   --------------------------------------------------------------------------------
   *   20 | ERROR | Profanity found. "fucking" is not allowed to be used inside a
   *      |       | comment.
   *   --------------------------------------------------------------------------------
   **/


  // This fucking thing sucks
  $balls = new Cunt();