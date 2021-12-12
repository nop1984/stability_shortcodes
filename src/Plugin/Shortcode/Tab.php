<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "tab",
 *   title = @Translation("Tab"),
 *   description = @Translation("Insert a Tab.")
 * )
 */
class Tab extends ShortcodeBaseEx {

	/**
	 * {@inheritdoc}
	 */
    public function getThemeRegistry()
    {
        return [
            $this->getThemeId() => [
                'variables' => $this->getThemeVars(),
            ],
			$this->getThemeId().'_content' => [
				'variables' => $this->getThemeVars(),
			]
        ];
    }

	public function getThemeVars($with_hash = false, $merge = []) {
		/**
		 * @todo resolve
		 * tab_content from child tab::process results via service nesting like accordions
		 */
		$res = parent::getThemeVars($with_hash, array_merge(['tab_counter'=>null], $merge));
		return $res;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultAttributes() {
		return ['icon'=>''] + parent::getDefaultAttributes();
	}

	/**
     * {@inheritdoc}
     */
    public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED)
    {
        $tvars = $this->processPrepareVars($attributes, $text, $langcode);
		$tvars['#tab_counter'] = $this->global_id;

        $output = $tvars + [
            '#theme' => $this->getThemeId().'_content',
        ];

		if(!empty($this->parent_shortcode)) {
			$this->parent_shortcode->tab_content = empty($this->parent_shortcode->tab_content) ? '' : $this->parent_shortcode->tab_content;
			$this->parent_shortcode->tab_content .= $this->render($output);
		}

		$output = $tvars + [
            '#theme' => $this->getThemeId(),
        ];
        return $this->render($output);
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