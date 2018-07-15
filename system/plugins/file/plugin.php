<?php

  /**
    This is the File class of the urlau.be CMS.

    This file contains the File class of the urlau.be CMS core. The file class simplifies the loading of file-based
    CMS entries.

    @package urlaube\urlaube
    @version 0.1a6
    @author  Yahe <hello@yahe.sh>
    @since   0.1a0
  */

  // ===== DO NOT EDIT HERE =====

  // prevent script from getting called directly
  if (!defined("URLAUBE")) { die(""); }

  if (!class_exists("File")) {
    class File extends Base implements Plugin {

      // RUNTIME FUNCTIONS

      public static function fileToUri($filename) {
        $result = null;

        if (is_string($filename)) {
          if (0 === strpos($filename, USER_CONTENT_PATH)) {
            // get relevant part of the filename
            $filename = substr($filename, strlen(USER_CONTENT_PATH));

            // remove file extension
            $filename = notrail($filename, CONTENT_FILE_EXT);

            // replace DS with US and prepend root URI
            $result = trail(Main::ROOTURI().strtr($filename, DS, US), US);
          }
        }

        return $result;
      }

      public static function loadContent($filename, $skipcontent = false, $filter = null) {
        $result = null;

        // check if the file exists
        if (is_file($filename) &&
            istrail($filename, CONTENT_FILE_EXT)) {
          // read the file as an array
          $file = file($filename);
          if (false !== $file) {
            // iterate through $file to read all attributes
            $index = 0;
            while ($index < count($file)) {
              $pos = strpos($file[$index], ":");
              if (false !== $pos) {
                $left  = strtolower(trim(substr($file[$index], 0, $pos)));
                $right = trim(substr($file[$index], $pos+1));

                if ((false !== $right) && (0 < strlen($left)) && (0 < strlen($right))) {
                  // preset result
                  if (null === $result) {
                    $result = new Content();
                  }

                  $result->set($left, $right);
                } else {
                  Debug::log("ignored file line because left or right are empty", DEBUG_DEBUG);
                }
              } else {
                // check if this is the empty line
                if (0 === strlen(trim($file[$index]))) {
                  // break the loop
                  break;
                } else {
                  // ignore the line
                  Debug::log("ignored file line because it does not contain a colon", DEBUG_DEBUG);
                }
              }

              // increment index
              $index++;
            }

            // try to set the content
            if (!$skipcontent) {
              // delete all lines that do not belong to the content
              for ($counter = $index-1; $counter >= 0; $counter--) {
                unset($file[$counter]);
              }

              // get content string
              $content = trim(implode($file));
              if (0 < strlen($content)) {
                // preset result
                if (null === $result) {
                  $result = new Content();
                }

                $result->set(CONTENT, $content);
              }
            }

            // only set the file name and URI when there is a result
            if (null !== $result) {
              $result->set(FILE, $filename);
              $result->set(URI,  static::fileToUri($filename));
            }
          }
        }

        // call the filter function if one is given
        if (null !== $result) {
          if (is_callable($filter)) {
            // if the filter wants to drop the entry it has to return null
            $result = $filter($result);
          }
        }

        return $result;
      }

      public static function loadContentDir($dirname, $skipcontent = false, $filter = null, $recursive = false) {
        $result = null;

        if (is_dir($dirname)) {
          $dirname = trail($dirname, DS);

          // prepare $files array
          $files = scandir($dirname, SCANDIR_SORT_ASCENDING);
          if (false !== $files) {
            // iterate through the file list
            foreach ($files as $files_item) {
              if (is_file($dirname.$files_item)) {
                $temp = static::loadContent($dirname.$files_item, $skipcontent, $filter);
                if (null !== $temp) {
                  // preset result
                  if (null === $result) {
                    $result = array();
                  }

                  $result[] = $temp;
                }
              } else {
                // read files recursively
                if (("." !== $files_item) && (".." !== $files_item)) {
                  if (is_dir($dirname.$files_item) && $recursive) {
                    $temp = static::loadContentDir($dirname.$files_item, $skipcontent, $filter, $recursive);
                    if (is_array($temp)) {
                      // preset result
                      if (null === $result) {
                        $result = array();
                      }

                      $result = array_merge($result, $temp);
                    }
                  }
                }
              }
            }
          }
        }

        return $result;
      }

    }
  }
