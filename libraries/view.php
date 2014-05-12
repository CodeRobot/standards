<?php

  namespace CodeRobot\Standards\Enforcer;

  /**
   * View
   *
   * @package Enforcer
   **/
  class View {


    /**
     * Render the view
     *
     * @param string $template The path to the template file
     * @param array  $data     The data to pass to the view
     *
     * @return string
     **/
    public static function render($template, array $data = []) {
      extract($data);

      ob_start();
      require $template . '.php';
      return ob_get_clean();
    }


  }