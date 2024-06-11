<?php

declare(strict_types=1);

namespace Drupal\summary_made_easy\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;

/**
 * Plugin implementation of the 'generate_summary_button' widget.
 */
#[FieldWidget(
  id: 'generate_summary_button',
  label: new TranslatableMarkup('Generate Summary on/off'),
  field_types: [
    'string',
    'text',
    'text_long',
    'text_with_summary',
    ],
)]
final class GenerateSummaryWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'generate_summary_button' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['generate_summary_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate Summary Button'),
      '#default_value' => $this->getSetting('generate_summary_button'),
      '#weight' => -1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('generate_summary_button')) {
      $summary[] = $this->t('Show AJAX button: Yes');
    }
    else {
      $summary[] = $this->t('Show AJAX button: No');
    }

//    $summary = [];
//
//    $display_label = $this->getSetting('generate_summary_button');
//    $summary[] = $this->t('Use field label: @display_label', ['@display_label' => ($display_label ? $this->t('Yes') : $this->t('No'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Here you can define how the field should be displayed in the form.
    // This is a basic implementation, you can extend it as per your needs.
    $element = [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
    ];

    return ['value' => $element];
  }

}
