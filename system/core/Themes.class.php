<?php

  /**
    This is the Themes class of the urlau.be CMS core.

    This file contains the Themes class of the urlau.be CMS core. The user themes are managed through this class.
    It loads the themes and activates the selected theme when requested by the active handler.

    @package urlaube\urlaube
    @version 0.1a4
    @author  Yahe <hello@yahe.sh>
    @since   0.1a0
  */

  // ===== DO NOT EDIT HERE =====

  // prevent script from getting called directly
  if (!defined("URLAUBE")) { die(""); }

  if (!class_exists("Themes")) {
    class Themes extends Base {

      // FIELDS

      protected static $themename = null;

      protected static $config = null;

      // runtime status
      protected static $active = null;
      protected static $themes = null;

      // GETTER FUNCTIONS

      public static function get($name) {
        if (null === static::$config) {
          static::$config = new Content();
        }

        return static::$config->get($name);
      }

      public static function getActive() {
        return static::$active;
      }

      public static function getThemename() {
        return static::$themename;
      }

      public static function isset($name) {
        if (null === static::$config) {
          static::$config = new Content();
        }

        return static::$config->isset($name);
      }

      // SETTER FUNCTIONS

      public static function preset($name, $value) {
        if (null === static::$config) {
          static::$config = new Content();
        }

        return static::$config->preset($name, $value);
      }

      public static function set($name, $value) {
        if (null === static::$config) {
          static::$config = new Content();
        }

        return static::$config->set($name, $value);
      }

      public static function setThemename($themename) {
        if (is_string($themename)) {
          static::$themename = $themename;
        } else {
          Debug::log("given value has wrong format", DEBUG_WARN);
        }

        return ($themename === static::$themename);
      }

      public static function unset($name) {
        if (null === static::$config) {
          static::$config = new Content();
        }

        return static::$config->unset($name);
      }

      // RUNTIME FUNCTIONS

      public static function load() {
        // load the user themes
        _loadExtensions(USER_THEMES_PATH, THEME_FILE);
      }

      public static function register($entity, $function, $name) {
        $result = false;

        if (checkMethod($entity, $function)) {
          if (is_string($name)) {
            // check if the theme name is not already taken
            if ((null === static::$themes) || (!isset(static::$themes[$names]))) {
              // prepare themes array
              if (null === static::$themes) {
                static::$themes = array();
              }

              // store the given method as a new theme
              static::$themes[$name] = array(THEME_ENTITY   => $entity,
                                             THEME_FUNCTION => $function);

              // we're done
              $result = true;
            } else {
              Debug::log("given name is already registered", DEBUG_WARN);
            }
          } else {
            Debug::log("given name has wrong format", DEBUG_WARN);
          }
        } else {
          Debug::log("given entity or function does not exist", DEBUG_WARN);
        }

        return $result;
      }

      public static function run() {
        $result = false;

        // filter the content before calling the theme
        Main::CONTENT(Plugins::run(FILTER_CONTENT, true, Main::CONTENT()));

        // call the before-theme plugins
        Plugins::run(BEFORE_THEME);

        $themes_item = null;
        // if no theme name is set we take the first theme
        if (null === static::$themename) {
          // reset the theme pointer and thereby retrieve the first entry
          $themes_item = reset(static::$themes);
          if (false === $themes_item) {
            // reset $item to NULL if $themes array is empty
            $themes_item = null;
          }
        } else {
          if (isset(static::$themes[static::$themename])) {
            $themes_item = static::$themes[static::$themename];
          }
        }

        // proceed with the retrieved theme item
        if (null !== $themes_item) {
          // set the active theme
          static::$active = $themes_item[THEME_ENTITY];

          // call the theme
          $result = callMethod($themes_item[THEME_ENTITY],
                               $themes_item[THEME_FUNCTION]);

          // unset the active theme
          static::$active = null;
        }

        // warn if no theme has been found
        if (false === $result) {
          Debug::log("no theme found matching the given name", DEBUG_WARN);
        }

        // call the after-theme plugins
        Plugins::run(AFTER_THEME);

        return $result;
      }

    }
  }

