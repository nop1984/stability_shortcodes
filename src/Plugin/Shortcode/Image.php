<?php

namespace Drupal\stability_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\stability_shortcodes\Plugin\ShortcodeBaseEx;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "image",
 *   title = @Translation("Image"),
 *   description = @Translation("Image")
 * )
 */
class Image extends ShortcodeBaseEx {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
		'path' => '<front>',
		'url' => '',
		'title' => '',
		'class' => '',
		'id' => '',
		'style' => '',
		'media_file_url' => FALSE,
	  ],
		$attributes
	  );
	  $url = $attributes['url'];
	  if (empty($url)) {
		$url = $this->getUrlFromPath($attributes['path'], $attributes['media_file_url']);
	  }
	  $title = $this->getTitleFromAttributes($attributes['title'], $text);
	  $class = $this->addClass($attributes['class'], 'button');
  
	  // Build element attributes to be used in twig.
	  $element_attributes = [
		'href' => $url,
		'class' => $class,
		'id' => $attributes['id'],
		'style' => $attributes['style'],
		'title' => $title,
	  ];
  
	  // Filter away empty attributes.
	  $element_attributes = array_filter($element_attributes);
  
	  $output = [
		'#theme' => 'shortcode_button',
	  // Not required for rendering, just for extra context.
		'#url' => $url,
		'#attributes' => $element_attributes,
		'#text' => $text,
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