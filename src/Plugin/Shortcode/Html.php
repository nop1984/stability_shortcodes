<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "html",
 *   title = @Translation("HTML"),
 *   description = @Translation("HTML code.")
 * )
 */
class Html extends ShortcodeBaseEx {

  /**
   * {@inheritdoc}
   */
  public function processPrepareVars(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
	  $tvars = parent::processPrepareVars($attributes, $text, $langcode);
	  $tvars['text'] = str_replace(array('<table', '<ul>', '</ul>', '<ol>'), array('<table class = "table table-bordered table-striped"', '<div class = "list"><ul>', '</ul></div>',  '<ol class = "list">'), $tvars['text']);
	  return $tvars;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[block id="1" (view="full") /]') . '</strong>';
    $output[] = $this->t('Inserts a block.') . '</p>';
    if ($long) {
      $output[] = '<p>' . $this->t('The block display view can be specified using the <em>view</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }
}