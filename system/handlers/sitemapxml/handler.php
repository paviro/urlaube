<?php

  /**
    This is the SitemapXmlHandler class of the urlau.be CMS.

    This file contains the SitemapXmlHandler class of the urlau.be CMS. The
    sitemap.xml handler generates a sitemap file.

    @package urlaube\urlaube
    @version 0.1a9
    @author  Yahe <hello@yahe.sh>
    @since   0.1a0
  */

  // ===== DO NOT EDIT HERE =====

  // prevent script from getting called directly
  if (!defined("URLAUBE")) { die(""); }

  class SitemapXmlHandler extends BaseSingleton implements Handler {

    // CONSTANTS

    const REGEX = "~^\/sitemap\.xml$~";

    // INTERFACE FUNCTIONS

    public static function getContent($metadata, &$pagecount) {
      $pagecount = 1;
      $result    = null;

      // try to get data from cache
      if (getcache(null, $data, static::class)) {
        // check that the returned content matches
        if (is_array($data) && isset($data[CONTENT]) && isset($data[PAGECOUNT])) {
          $pagecount = $data[PAGECOUNT];
          $result    = $data[CONTENT];
        }
      } else {
        $result = FilePlugin::loadContentDir(USER_CONTENT_PATH, false,
                                             function ($content) {
                                               $result = null;

                                               // check that $content is not hidden
                                               if (!istrue(value($content, HIDDEN))) {
                                                 // check that $content is not hidden from sitemap
                                                 if (!istrue(value($content, HIDDENFROMSITEMAP))) {
                                                   // check that $content is not a relocation
                                                   if (null === value($content, RELOCATE)) {
                                                     $result = $content;
                                                   }
                                                 }
                                               }

                                               return $result;
                                             },
                                             true);

        // try to set data in cache
        setcache(null, [CONTENT => $result, PAGECOUNT => $pagecount], static::class);
      }

      return $result;
    }

    public static function getUri($metadata) {
      return value(Main::class, ROOTURI)."sitemap.xml";
    }

    public static function parseUri($uri) {
      $result = null;

      $metadata = preparecontent(parseuri($uri, static::REGEX));
      if ($metadata instanceof Content) {
        $result = $metadata;
      }

      return $result;
    }

    // RUNTIME FUNCTIONS

    public static function run() {
      $result = false;

      $metadata = static::parseUri(relativeuri());
      if (null !== $metadata) {
        // set the metadata to be processed by plugins
        Main::set(METADATA, $metadata);

        $content = preparecontent(static::getContent($metadata, $pagecount));
        if (null !== $content) {
          // check if the URI is correct
          $fixed = static::getUri($metadata);
          if (0 !== strcmp(value(Main::class, URI), $fixed)) {
            relocate($fixed.querystring(), false, true);
          } else {
            // filter the content
            $content = preparecontent(Plugins::run(FILTER_CONTENT, true, $content));

            // set the content type
            header("Content-Type: application/xml");

            // return a minimalistic sitemap.xml
            print(fhtml("<?xml version=\"1.0\" encoding=\"UTF-8\"?>".NL.
                        "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">".NL.
                        "  <url>".NL.
                        "    <loc>%s</loc>".NL.
                        "    <changefreq>daily</changefreq>".NL.
                        "    <priority>1.0</priority>".NL.
                        "  </url>".NL,
                        absoluteurl(US)));

            if (null !== $content) {
              // make sure that we are handling an array
              if (!is_array($content)) {
                $content = [$content];
              }

              foreach ($content as $content_item) {
                // try to parse the update field
                $lastmod = value($content_item, UPDATE);
                if (null !== $lastmod) {
                  $lastmod = strtotime($lastmod);
                  if (false === $lastmod) {
                    // it failed
                    $lastmod = null;
                  }
                }

                // try to parse the date field
                if (null === $lastmod) {
                  $lastmod = value($content_item, DATE);
                  if (null !== $lastmod) {
                    $lastmod = strtotime($lastmod);
                    if (false === $lastmod) {
                      // it failed
                      $lastmod = null;
                    }
                  }
                }

                // use the file modification time as a last resort
                if (null === $lastmod) {
                  $lastmod = filemtime(value($content_item, FILE));
                }

                print(fhtml("  <url>".NL.
                            "    <loc>%s</loc>".NL.
                            "    <lastmod>%s</lastmod>".NL.
                            "    <changefreq>monthly</changefreq>".NL.
                            "    <priority>0.5</priority>".NL.
                            "  </url>".NL,
                            absoluteurl(value($content_item, URI)),
                            date("Y-m-d", $lastmod)));
              }
            }

            print("</urlset>");
          }

          // we handled this page
          $result = true;
        }
      }

      return $result;
    }

  }

  // register handler
  Handlers::register(SitemapXmlHandler::class, "run", SitemapXmlHandler::REGEX, [GET, POST], ADDSLASH_SYSTEM);