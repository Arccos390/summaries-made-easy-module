<?php

declare(strict_types=1);

namespace Drupal\summary_made_easy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use OpenAI;

/**
 * Configure summary_made_easy settings for this site.
 */
final class SummaryMadeEasySettingsTestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'summary_made_easy_settings_test';
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
    $form['prompt_test'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#default_value' => $this->config('summary_made_easy.settings')->get('prompt_test'),
    ];

    // AJAX button to fetch OpenAI result
    $form['fetch_openai_result'] = [
      '#type' => 'button',
      '#value' => $this->t('Get OpenAI Result'),
      '#ajax' => [
        'callback' => '::fetchOpenAIResult',
        'wrapper' => 'openai-result-wrapper',
      ],
    ];

    // Container for displaying the OpenAI result
    $form['openai_result'] = [
      '#type' => 'textarea',
      '#title' => $this->t('OpenAI Result'),
      '#attributes' => ['readonly' => 'readonly'],
      '#prefix' => '<div id="openai-result-wrapper">',
      '#suffix' => '</div>',
    ];

    $form = parent::buildForm($form, $form_state);

    // Unset the default actions provided by ConfigFormBase
    unset($form['actions']);

    return $form;
  }

  /**
   * AJAX callback to fetch the OpenAI result.
   */
  public function fetchOpenAIResult(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the prompt from the form state
    $prompt = $form_state->getValue('prompt_test');

    // Get the API key from the configuration
    $api_key = $this->config('summary_made_easy.settings')->get('api');

    // Initialize the OpenAI client
    $client = OpenAI::client($api_key);

    // Make the request to OpenAI
    $result = '';
    try {
      $openaiResponse = $client->chat()->create([
        'model' => $this->config('summary_made_easy.settings')->get('openai_model'),
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
      ]);

      if (!empty($openaiResponse->choices[0]->message->content)) {
        $result = $openaiResponse->choices[0]->message->content;
      }
    } catch (\Exception $e) {
      $result = $this->t('Error: @message', ['@message' => $e->getMessage()]);
    }

    // Update the OpenAI result textarea
    $form['openai_result']['#value'] = $result;
    $response->addCommand(new ReplaceCommand('#openai-result-wrapper', $form['openai_result']));

    return $response;
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
    parent::submitForm($form, $form_state);
  }

}
