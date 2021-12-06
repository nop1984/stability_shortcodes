<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "column",
 * 	 token = "col",
 *   title = @Translation("Column"),
 *   description = @Translation("Insert a Column.")
 * )
 */
class Column extends ShortcodeBaseEx {
	public function getThemeVars($with_hash = false, $merge = []) {
		$res = parent::getThemeVars($with_hash, array_merge([
			'phone'=>null,
			'tablet'=>null,
			'desktop'=>null,
			'wide'=>null,
		], $merge));
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