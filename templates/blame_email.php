<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width"/>
  <?php
    require 'partials/email_css.php';
  ?>
</head>
<body>
  <table class="body">
    <tr>
      <td class="center" align="center" valign="top">
        <center>
          <table class="row header">
            <tr>
              <td class="center" align="center">
                <center>

                  <table class="container">
                    <tr>
                      <td class="wrapper last">

                        <table class="twelve columns">
                          <tr>
                            <td class="six sub-columns">
                              <img src="https://www.sparkhire.com/theme/img/SH-logo.png">
                            </td>
                            <td class="six sub-columns last" style="text-align:right; vertical-align:middle;">&nbsp;

                            </td>
                            <td class="expander"></td>
                          </tr>
                        </table>

                      </td>
                    </tr>
                  </table>
                </center>
              </td>
            </tr>
          </table>

          <table class="container">
            <tr>
              <td>

                <table class="row">
                  <tr>
                    <td class="wrapper last">

                      <table class="twelve columns">
                        <tr>
                          <td>
                            <h1>Jenkins Code Sniffer Report</h1>
                            <p class="lead">
                              Jenkins looked through your code, and thinks you stink.
                            </p>
                            <p>
                              These errors need to be corrected.
                            </p>
                          </td>
                          <td class="expander"></td>
                        </tr>
                      </table>

                      <?php
                        foreach ($files as $file => $report) {
                      ?>
                      <h6>File: <code><?= $file ?></code></h6>
                      <strong>Found <?= count($report) ?> errors</strong>
                      <table class="twelve columns">
                        <thead>
                          <tr>
                            <td style="font-weight:bold">
                              Line
                            </td>
                            <td style="font-weight:bold">
                              Error
                            </td>
                            <td style="font-weight:bold">
                              Sniff
                            </td>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                            foreach ($report as $error) {
                          ?>
                          <tr>
                            <td>
                              <code><?= $error->line ?></code>
                            </td>
                            <td>
                              <code><?= $error->message ?></code>
                            </td>
                            <td>
                              <code><?= $error->standard ?></code>
                            </td>
                          </tr>
                          <?php
                            }
                          ?>
                        </tbody>
                      </table>
                      <?php
                        }
                      ?>
                    </td>
                  </tr>
                </table>

                <table class="row callout">
                  <tr>
                    <td class="wrapper last">

                      <table class="twelve columns">
                        <tr>
                          <td class="panel">
                            <p style="margin-bottom:0">
                              Correct lint errors in a separate commit so that diff's are easier.
                            </p>
                          </td>
                          <td class="expander"></td>
                        </tr>
                      </table>

                    </td>
                  </tr>
                </table>

                <table class="row">
                  <tr>
                    <td class="wrapper last">

                      <table class="twelve columns">
                        <tr>
                          <td align="center">
                            <center>
                              <p style="text-align:center;">
                                Sent at 3:22 on Monday May 12<sup>th</sup>.
                              </p>
                              <p style="text-align:center;">
                                Triggered by build #<a href="https://sparkhire.codebasehq.com/projects/sparkhire/repositories/api/commit/d600a3ac7d">d600a3ac7d</a>.
                              </p>
                            </center>
                          </td>
                          <td class="expander"></td>
                        </tr>
                      </table>

                    </td>
                  </tr>
                </table>

              <!-- container end below -->
              </td>
            </tr>
          </table>

        </center>
      </td>
    </tr>
  </table>
</body>
</html>