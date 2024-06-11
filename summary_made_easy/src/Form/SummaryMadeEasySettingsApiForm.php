<?php

declare(strict_types=1);

namespace Drupal\summary_made_easy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure summary_made_easy settings for this site.
 */
final class SummaryMadeEasySettingsApiForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'summary_made_easy_summary_made_easy_settings_api';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['summary_made_easy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->config('summary_made_easy.settings')->get('api'),
    ];

    $form['api_organization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Organization'),
      '#default_value' => $this->config('summary_made_easy.settings')->get('api_organization'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('summary_made_easy.settings')
      ->set('api', $form_state->getValue('api'))
      ->set('api_organization', $form_state->getValue('api_organization'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
