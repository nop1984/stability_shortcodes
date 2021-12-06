<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "spacer",
 *   title = @Translation("Spacer"),
 *   description = @Translation("Insert a Spacer.")
 * )
 */
class Spacer extends ShortcodeBaseEx {

	public function getThemeVars($with_hash = false, $merge = []) {
		/**
		 * @todo ShortcodeBaseEx must implement style object with
		 * add/remove methods and merge user styles using it in spec proc
		 */
		$res = parent::getThemeVars($with_hash, array_merge(['height'=>null], $merge));
		return $res;
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