<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "tabs",
 *   title = @Translation("Tabs container"),
 *   description = @Translation("Insert a Tabs container.")
 * )
 */
class Tabs extends ShortcodeBaseEx {
	public function getThemeVars($with_hash = false, $merge = []) {
		/**
		 * @todo resolve
		 * tab_content from child tab::process results via service nesting like accordions
		 */
		$res = parent::getThemeVars($with_hash, array_merge(['tab_content'=>null], $merge));
		return $res;
	}

  public function processPrepareVars(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $tvars = parent::processPrepareVars($attributes, $text, $langcode);
    $tvars['#tab_content'] = $this->tab_content; // populated in Tab.php
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