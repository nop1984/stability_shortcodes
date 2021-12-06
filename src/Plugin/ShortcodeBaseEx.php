<?php

namespace Drupal\stability_shortcodes\Plugin;

use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Core\Language\Language;

/**
 * Provides a base class for Shortcode plugins.
 * @todo this should be a part of contrib module
 *
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\shortcode\ShortcodePluginManager
 * @see \Drupal\shortcode\Plugin\ShortcodeInterface
 * @see plugin_api
 */
abstract class ShortcodeBaseEx extends ShortcodeBase
{
	protected $class = "";

    public $init_attributes = null;
    public $parse_tree_item = null;
    public static $global_id_counter = 0;
    public $global_id;

    public function init($attributes, $parse_tree_item) {
        dpm($this->getThemeId() . ' -- ' . $this->global_id . ' -- ' . self::$global_id_counter);
        $this->init_attributes = $attributes;
        $this->parse_tree_item = $parse_tree_item;
        $this->global_id = self::$global_id_counter;
        self::$global_id_counter++;
    }

    public function getThemeVars($with_hash = false, $merge =[])
    {
        $tvars = array_merge(['attributes'=>null, 'text'=>null], $merge);
        if ($with_hash) {
            foreach (array_keys($tvars) as $k) {
                $tvars["#$k"] = $tvars[$k];
                unset($tvars[$k]);
            }
        }
        return $tvars;
    }

    public function getThemeId()
    {
        return 'shortcode_' . $this->getPluginId();
    }

    public function getThemeRegistry()
    {
        return [
            $this->getThemeId() => [
                'variables' => $this->getThemeVars(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED)
    {

        // Merge with default attributes.
        $attributes = $this->getAttributes($this->getDefaultAttributes(),
            $attributes
        );

        $title = $this->getTitleFromAttributes($attributes['title'], $text);
        $this->class = $this->addClass($attributes['class'], $this->class);

        // Build element attributes to be used in twig.
        $element_attributes = [
            'class' => $this->class,
            'title' => $title,
        ] + $attributes;

        // Filter away empty attributes.
        $element_attributes = array_filter($element_attributes);

        // remove theme variables from attributes
        $tvars = $this->getThemeVars();
        foreach($tvars as $k => $v) {
            if(array_key_exists($k, $element_attributes)) {
                $tvars[$k] = $element_attributes[$k];
                unset($element_attributes[$k]);
            }
        }

        $element_attributes = $this->doAtrributesTranslation($element_attributes);

        $tvars['attributes'] = $element_attributes;
        $tvars['text'] = $text;
        $tvars = $this->getThemeVars(true, $tvars);

        $output = $tvars + [
            '#theme' => $this->getThemeId(),
        ];

        return $this->render($output);
    }

	/**
	 * @todo should be part of annotation
	 */
	public function getDefaultAttributes() {
		return [
            'title' => '',
            'class' => '',
            'id' => '',
            'style' => '',
        ] + $this->getThemeVars();
	}

    /**
     * lists special shortcode attributes which must be translated
     * to HTML ouput under different name
     * Example: [link url="..."] --> <a href="...">
     * where attr 'url' translated as 'href'
     *
     * @return array
     */
    public function getAttributesTranslation() {
        return [];
    }

    /**
     * does translation of attributes array per getAttributesTranslation()
     *
     * @param array $attrs where key is subject of translation
     * @return array
     */
    public function doAtrributesTranslation($attrs) {
        $tr = $this->getAttributesTranslation();
        foreach($tr as $a => $t) {
            if(array_key_exists($a, $attrs)) {
                $attrs[$t] = $attrs[$a];
                unset($attrs[$a]);
            }
        }
        return $attrs;
    }

}
