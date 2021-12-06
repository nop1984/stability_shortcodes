<?php

namespace Drupal\stability_shortcodes;

use Drupal\filter\Plugin\FilterInterface;
use Drupal\Core\Language\Language;
use Drupal\shortcode\ShortcodeService;

/**
 * Provide the ShortCode service.
 */
class StabilityShortcodesService extends ShortcodeService {

  public function walkDown($from, $callback, &$extra = [], &$level = 0) {
    $callback($from, $level, $extra);
    if(property_exists($from, 'children') && !empty($from->children)) {
      $level ++;
      foreach($from->children as $item) {
        $this->walkDown($item, $callback, $extra, $level);
      }
      $level --;
    }
  }

  public function walkUp($from, $callback, &$extra = [], &$level = 0) {
    foreach($from as $f) {
      $callback($f, $level, $extra);
      if(property_exists($from, 'parent') && !empty($from->parent)) {
        $level ++;
        $this->walkUp([$from->parent], $callback, $extra, $level);
        $level --;
      }
  
    }
  }

  public function walkDownInit($tree, $shortcodes) {
    $initf = function($item, $level, &$extra) {
      if($item->is_shortcode) {
        $m = $item->m;
        $shortcode_token = $m[2];

        $shortcode = NULL;
        if (isset($extra['shortcodes'][$shortcode_token])) {
          $shortcode_id = $extra['shortcodes'][$shortcode_token]['id'];
          $shortcode = $this->getShortcodePlugin($shortcode_id);
        }
    
        // If shortcode does not exist or is not enabled, return input sans tokens.
        if (empty($shortcode)) {
          // This is an enclosing tag, means extra parameter is present.
          if (!is_null($m[4])) {
            return $m[1] . $m[4] . $m[5];
          }
          // This is a self-closing tag.
          else {
            return $m[1] . $m[5];
          }
        }
    
        // Process if shortcode exists and enabled.

        $attr = $this->parseAttrs($m[3]);
        $shortcode->init($attr, $item);

        $item->shortcode_plugin = $shortcode;
      }
    };
    $extra = ['shortcodes'=>$shortcodes];
    $this->walkDown($tree, $initf, $extra);
  }

  public function walkUpRender($from) {
    $renderf = function($item) {
      //$shortcode->process($attr, $m[4]) . $m[5];
      $cr = '';
      //collect childrens text for content render
      foreach($item->children as $c) {
        $cr .= property_exists($c, 'render_text') ? $c->render_text : $c->text;
      }
      $m = $item->m;
      $attr = !(property_exists($item->shortcode_plugin, 'init_attributes') && !empty($item->shortcode_plugin->init_attributes))?
        $item->shortcode_plugin->init_attributes :
        $m[3];
      $item->render_text = $m[1] . $item->shortcode_plugin->process($this->parseAttrs($attr), $cr) . $m[5];
      return $item->render_text;
    };

    // first render the lowest
    foreach($from as $f) {
      $render = $renderf($f);
    }

    //now render neighbours of the lowest and repeat up to root
    $new_parents = [];
    $parents = $from;
    while(!empty($parents)) {
      $new_parents = [];
      foreach($parents as $f) {
        if(!(property_exists($f, 'parent') && !empty($f->parent)))
          continue;
        foreach($f->parent->children as $c) {
          if(empty($c->is_shortcode))
            continue;
          $renderf($c);
          $new_parents[] = $f->parent;
        }
      }
      $parents = $new_parents;
    }

    
  }

  /**
   * Processes the Shortcodes according to the text and the text format.
   *
   * @param string $text
   *   The string containing shortcodes to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The text filter.
   *
   * @return string
   *   The processed string.
   */
  public function process($text, $langcode = Language::LANGCODE_NOT_SPECIFIED, FilterInterface $filter = NULL) {
    $shortcodes = $this->getShortcodePlugins($filter);

    // Processing recursively, now embedding tags within other tags is
    // supported!
    $chunks = preg_split('!(\[{1,2}.*?\]{1,2})!', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    $heap = [];
    $heap_index = [];

    $tree = (object)['children'=>[]];
    $cur_parent_node = $tree;

    $lowest_shortcodes = [];

    foreach($chunks as $c) {
      $escaped = FALSE;

      if ((substr($c, 0, 2) == '[[') && (substr($c, -2, 2) == ']]')) {
        $escaped = TRUE;
        // Checks media tags, eg: [[{ }]].
        if ((substr($c, 0, 3) != '{') && (substr($c, -3, 1) != '}')) {
          // Removes the outer [].
          $c = substr($c, 1, -1);
        }
      }
      // Decide this is a Shortcode tag or not.
      if (!$escaped && ($c[0] == '[') && (substr($c, -1, 1) == ']')) {
        // The $c maybe contains Shortcode macro.
        // This is maybe a self-closing tag.
        // Removes outer [].
        $original_text = $c;
        $c = substr($c, 1, -1);
        $c = trim($c);

        $ts = explode(' ', $c);
        $tag = array_shift($ts);
        $tag = trim($tag, '/');

        if (!$this->isValidShortcodeTag($tag)) {
          // This is not a valid shortcode tag, or the tag is not enabled.
          $cur_parent_node->children[] =(object)[
            'text'=> $original_text, 
            'is_shortcode'=>false, 
            'm'=>[], 
            'children'=>[],
            'parent'=> $cur_parent_node,
          ];
        }
        // This is a valid shortcode tag, and self-closing.
        elseif (substr($c, -1, 1) == '/') {
          // Processes a self closing tag, - it has "/" at the end.
          $m = [
            $c,
            '',
            $tag,
            implode(' ', $ts),
            NULL,
            '',
          ];
          $new = (object)[
            'text'=> $c, 
            'is_shortcode'=>true, 
            'm'=>$m,
            'children'=>[],
            'parent'=> $cur_parent_node
          ];
          $cur_parent_node->children[] = $new;
          $lowest_shortcodes[] = $new;
        }
        // A closing tag, we can process the heap.
        elseif ($c[0] == '/') {
          $closing_tag = substr($c, 1);
          //if closing tag != cur_parent then markup is broken
          //unset cur_parent is_shortcode to avoid processing

          $is_lowest = true;
          foreach($cur_parent_node->children as $c) {
            if(count($c->children) || $c->is_shortcode) {
              $is_lowest = false;
              break;
            }
          }
          
          if($is_lowest) {
            $lowest_shortcodes[] = $cur_parent_node;
          }

          $cur_parent_node = $cur_parent_node->parent;

        } else {
          //new shortcode opening, is valid
          $m = [
            $c,
            '',
            $tag,
            implode(' ', $ts),
            NULL,
            '',
          ];
          $new = (object)[
            'text'=> $c, 
            'is_shortcode'=>true, 
            'm' => $m,
            'children'=>[],
            'parent'=> $cur_parent_node,
            'tag' => $tag
          ];
          $cur_parent_node->children[] = $new;
          $cur_parent_node = $new;

        }
      } else {
        // just text or markup, may be inside shortcode
        $cur_parent_node->children[] = (object)[
          'text'=> $c, 
          'is_shortcode'=>false,
          'm' => null,
          'children' => [],
          'parent'=> $cur_parent_node
        ];
      }
    }

    $this->walkDownInit($tree, $shortcodes);
    $this->walkUpRender($lowest_shortcodes);

    $result = '';
    foreach($tree->children as $item) {
      $result .= $item->is_shortcode ? $item->render_text : $item->text;
    }
    return $result;

    
    dpm($chunks);
    foreach ($chunks as $c) {

      if (!$c) {
        continue;
      }

      $escaped = FALSE;

      if ((substr($c, 0, 2) == '[[') && (substr($c, -2, 2) == ']]')) {
        $escaped = TRUE;
        // Checks media tags, eg: [[{ }]].
        if ((substr($c, 0, 3) != '{') && (substr($c, -3, 1) != '}')) {
          // Removes the outer [].
          $c = substr($c, 1, -1);
        }
      }
      // Decide this is a Shortcode tag or not.
      if (!$escaped && ($c[0] == '[') && (substr($c, -1, 1) == ']')) {
        // The $c maybe contains Shortcode macro.
        // This is maybe a self-closing tag.
        // Removes outer [].
        $original_text = $c;
        $c = substr($c, 1, -1);
        $c = trim($c);

        $ts = explode(' ', $c);
        $tag = array_shift($ts);
        $tag = trim($tag, '/');

        if (!$this->isValidShortcodeTag($tag)) {
          // This is not a valid shortcode tag, or the tag is not enabled.
          array_unshift($heap_index, '_string_');
          array_unshift($heap, $original_text);
        }
        // This is a valid shortcode tag, and self-closing.
        elseif (substr($c, -1, 1) == '/') {
          // Processes a self closing tag, - it has "/" at the end.
          /*
           * The exploded array elements meaning:
           * 0 - the full tag text?
           * 1/5 - An extra [] to allow for escaping Shortcodes with double
           * [[]].
           * 2 - The Shortcode name.
           * 3 - The Shortcode argument list.
           * 4 - The content of a Shortcode when it wraps some content.
           */

          $m = [
            $c,
            '',
            $tag,
            implode(' ', $ts),
            NULL,
            '',
          ];
          array_unshift($heap_index, '_string_');
          array_unshift($heap, $this->processTag($m, $shortcodes));
        }
        // A closing tag, we can process the heap.
        elseif ($c[0] == '/') {
          $closing_tag = substr($c, 1);

          $process_heap = [];
          $process_heap_index = [];
          $found = FALSE;

          // Get elements from heap and process.
          do {
            $tag = array_shift($heap_index);
            $heap_text = array_shift($heap);

            if ($closing_tag == $tag) {
              // Process the whole tag.
              $m = [
                $tag . ' ' . $heap_text,
                '',
                $tag,
                $heap_text,
                implode('', $process_heap),
                '',
              ];
              $str = $this->processTag($m, $shortcodes);
              array_unshift($heap_index, '_string_');
              array_unshift($heap, $str);
              $found = TRUE;
            }
            else {
              array_unshift($process_heap, $heap_text);
              array_unshift($process_heap_index, $tag);
            }
          } while (!$found && $heap);

          if (!$found) {
            foreach ($process_heap as $val) {
              array_unshift($heap, $val);
            }
            foreach ($process_heap_index as $val) {
              array_unshift($heap_index, $val);
            }
          }

        }
        // A starting tag. Add into the heap.
        else {
          array_unshift($heap_index, $tag);
          array_unshift($heap, implode(' ', $ts));
        }
      }
      else {
        // Maybe not found a pair?
        array_unshift($heap_index, '_string_');
        array_unshift($heap, $c);
      }
      // End of foreach.
    }

    return (implode('', array_reverse($heap)));
  }

  /**
   * Provides Html corrector for wysiwyg editors.
   *
   * Correcting p elements around the divs. <div> elements are not allowed
   * in <p> so remove them.
   *
   * @param string $text
   *   Text to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The filter plugin that triggered this process.
   *
   * @return string
   *   The processed string.
   */
  public function postprocessText($text, $langcode, FilterInterface $filter = NULL) {

    // preg_match_all('/<p>s.*<!--.*-->.*<div/isU', $text, $r);
    // dpm($r, '$r');
    // Take note these are disrupted by the comments inserted by twig debug
    // mode.
    $patterns = [
      '|#!#|is',
      '!<p>(&nbsp;|\s)*(<\/*div>)!is',
      '!<p>(&nbsp;|\s)*(<div)!is',
      // '!<p>(&nbsp;|\s)*(<!--(.*?)-->)*(<div)!is', // Trying to ignore HTML
      // comments.
      '!(<\/div.*?>)\s*</p>!is',
      '!(<div.*?>)\s*</p>!is',
    ];

    $replacements = [
      '',
      '\\2',
      '\\2',
      // '\\3',.
      '\\1',
      '\\1',
    ];
    return preg_replace($patterns, $replacements, $text);
  }

  /**
   * Regular Expression callable for do_shortcode() for calling Shortcode hook.
   *
   * See for details of the match array contents.
   *
   * @param array $m
   *   Regular expression match array.
   *
   *     0 - the full tag text?
   *     1/5 - An extra [ or ] to allow for escaping shortcodes with double [[]]
   *     2 - The Shortcode name
   *     3 - The Shortcode argument list
   *     4 - The content of a Shortcode when it wraps some content.
   * @param array $enabled_shortcodes
   *   Array of enabled shortcodes for the active text format.
   *
   * @return string|false
   *   FALSE on failure.
   */
  protected function processTag(array $m, array $enabled_shortcodes) {
    $shortcode_token = $m[2];

    $shortcode = NULL;
    if (isset($enabled_shortcodes[$shortcode_token])) {
      $shortcode_id = $enabled_shortcodes[$shortcode_token]['id'];
      $shortcode = $this->getShortcodePlugin($shortcode_id);
    }

    // If shortcode does not exist or is not enabled, return input sans tokens.
    if (empty($shortcode)) {
      // This is an enclosing tag, means extra parameter is present.
      if (!is_null($m[4])) {
        return $m[1] . $m[4] . $m[5];
      }
      // This is a self-closing tag.
      else {
        return $m[1] . $m[5];
      }
    }

    // Process if shortcode exists and enabled.
    $attr = $this->parseAttrs($m[3]);
    return $m[1] . $shortcode->process($attr, $m[4]) . $m[5];
  }

  /**
   * Retrieve all attributes from the Shortcodes tag.
   *
   * The attributes list has the attribute name as the key and the value of the
   * attribute as the value in the key/value pair. This allows for easier
   * retrieval of the attributes, since all attributes have to be known.
   *
   * @param string $text
   *   The Shortcode tag attribute line.
   *
   * @return array
   *   List of attributes and their value.
   */
  protected function parseAttrs($text) {
    $attributes = [];
    if (empty($text)) {
      return $attributes;
    }
    $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    $text = html_entity_decode($text);
    if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
      foreach ($match as $m) {
        if (!empty($m[1])) {
          $attributes[strtolower($m[1])] = stripcslashes($m[2]);
        }
        elseif (!empty($m[3])) {
          $attributes[strtolower($m[3])] = stripcslashes($m[4]);
        }
        elseif (!empty($m[5])) {
          $attributes[strtolower($m[5])] = stripcslashes($m[6]);
        }
        elseif (isset($m[7]) and strlen($m[7])) {
          $attributes[] = stripcslashes($m[7]);
        }
        elseif (isset($m[8])) {
          $attributes[] = stripcslashes($m[8]);
        }
      }
    }
    else {
      $attributes = ltrim($text);
    }
    return $attributes;
  }

  public function getShortcodePlugin($shortcode_id) {
//    $plugins = &drupal_static(__FUNCTION__, []);
//    if (!isset($plugins[$shortcode_id])) {

      /** @var \Drupal\shortcode\Shortcode\ShortcodePluginManager $type */
      $type = \Drupal::service('plugin.manager.shortcode');

      $plugins[$shortcode_id] = $type->createInstance($shortcode_id);
//    }
    return $plugins[$shortcode_id];
  }
}
