<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "button",
 *   title = @Translation("Button"),
 *   description = @Translation("Insert a Button.")
 * )
 */
class Button extends ShortcodeBaseEx {

	public function getThemeVars($with_hash = false, $merge = []) {
		/**
		 * @todo resolve
		 * tab_content from child tab::process results via service nesting like accordions
		 */
		$res = parent::getThemeVars($with_hash, array_merge([
			'color_type'=>null,
			'color'=>null,
			'size'=>null,
			'custom_color'=>null,
			'link'=>null
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