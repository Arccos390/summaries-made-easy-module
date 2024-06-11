<?php

declare(strict_types=1);

namespace Drupal\summary_made_easy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure summary_made_easy settings for this site.
 */
final class SummaryMadeEasySettingsForm extends ConfigFormBase {

  /**
   * OpenAI chat completion models.
   * @todo Fetch the data from the OpenAI library.
   *
   * @var array
   * The GPT models available for completion.
   */
  protected array $models = [
    "GPT-3.5" => [
      "gpt-3.5-turbo" => "gpt-3.5-turbo",
    ],
    "GPT-4" => [
      "gpt-4" => "gpt-4",
      "gpt-4-turbo" => "gpt-4-turbo",
    ],
    "GPT-4o" => [
      "gpt-4o" => "gpt-4o",
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'summary_made_easy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['summary_made_easy.settings'];
  }

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constructs a SummaryMadeEasySettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t('The model which will generate the completion. <a href="@link" target="_blank">Learn more</a>.', ['@link' => 'https://platform.openai.com/docs/models']),
      '#options' => $this->models,
      '#default_value' => !empty($this->config('summary_made_easy.settings')->get('openai_model')) ? $this->config('summary_made_easy.settings')->get('openai_model') : 'gpt-3.5-turbo',
      '#required' => TRUE,
    ];

    // @todo Add more settings like token limitation.

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#default_value' => $this->config('summary_made_easy.settings')->get('prompt'),
    ];

    // Fetch all content types.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $form['content_types_prompt'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Types and Text Fields to include in the prompt'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // Loop through each content type and build the form elements.
    foreach ($content_types as $content_type_id => $content_type) {
      $form['content_types_prompt'][$content_type_id] = [
        '#type' => 'details',
        '#title' => $content_type->label(),
        '#open' => FALSE,
      ];

      $form['content_types_prompt'][$content_type_id]['fields'] = [
        '#type' => 'container',
      ];

      // Load all fields for the current content type.
      $field_definitions = $this->entityTypeManager->getStorage('field_config')
        ->loadByProperties(['entity_type' => 'node', 'bundle' => $content_type_id]);

      foreach ($field_definitions as $field_definition) {
        $field_name = $field_definition->getName();
        $field_type = $field_definition->getType();

        // Handle composite fields like body.
        if ($field_type === 'text_with_summary') {
          $form['content_types_prompt'][$content_type_id]['fields']["{$field_name}"]['value'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@field_name (Value)', ['@field_name' => $field_definition->getLabel()]),
            '#parents' => ['content_types_prompt', $content_type_id, 'fields', "{$field_name}", 'value'],
            '#default_value' => $this->config('summary_made_easy.settings')->get("content_types_prompt.{$content_type_id}.{$field_name}.value"),
          ];
          $form['content_types_prompt'][$content_type_id]['fields']["{$field_name}"]['summary'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@field_name (Summary)', ['@field_name' => $field_definition->getLabel()]),
            '#parents' => ['content_types_prompt', $content_type_id, 'fields', "{$field_name}", 'summary'],
            '#default_value' => $this->config('summary_made_easy.settings')->get("content_types_prompt.{$content_type_id}.{$field_name}.summary"),
          ];
        }
        // @todo Have all the types in a separate method and call them from there.
        elseif (in_array($field_type, ['string', 'string_long', 'text', 'text_long'])) {
          $form['content_types_prompt'][$content_type_id]['fields'][$field_name] = [
            '#type' => 'checkbox',
            '#title' => $field_definition->getLabel(),
            '#parents' => ['content_types_prompt', $content_type_id, 'fields', $field_name],
            '#default_value' => $this->config('summary_made_easy.settings')->get("content_types_prompt.{$content_type_id}.{$field_name}"),
          ];
        }
      }
    }

    $form['content_types_button'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Types and Text Fields to attach the generate button'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // Loop through each content type and build the form elements.
    foreach ($content_types as $content_type_id => $content_type) {
      $form['content_types_button'][$content_type_id] = [
        '#type' => 'details',
        '#title' => $content_type->label(),
        '#open' => FALSE,
      ];

      $form['content_types_button'][$content_type_id]['fields'] = [
        '#type' => 'container',
      ];

      // Load all fields for the current content type.
      $field_definitions = $this->entityTypeManager->getStorage('field_config')
        ->loadByProperties(['entity_type' => 'node', 'bundle' => $content_type_id]);

      foreach ($field_definitions as $field_definition) {
        $field_name = $field_definition->getName();
        $field_type = $field_definition->getType();
        // @todo text_with_summary does not work for altering the content through ajax with ckeditor.
        // @todo Another problem is that there are a lot of duplicates.
        if (in_array($field_type, ['string','string_long', 'text', 'text_long'])) {
          $form['content_types_button'][$content_type_id]['fields'][$field_name] = [
            '#type' => 'checkbox',
            '#title' => $field_definition->getLabel(),
            '#parents' => ['content_types_button', $content_type_id, 'fields', $field_name],
            '#default_value' => $this->config('summary_made_easy.settings')->get("content_types_button.{$content_type_id}.{$field_name}"),
          ];
        }
      }
    }

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
      ->set('prompt', $form_state->getValue('prompt'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->save();

    // Loop through each content type and save the checkbox values.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $content_type_id => $content_type) {
      $field_definitions = $this->entityTypeManager->getStorage('field_config')
        ->loadByProperties(['entity_type' => 'node', 'bundle' => $content_type_id]);

      foreach ($field_definitions as $field_definition) {
        $field_type = $field_definition->getType();
        $field_name = $field_definition->getName();

        // Handle composite fields like body.
        $additional_fields = ['prompt', 'button'];
        foreach ($additional_fields as $additional_field) {
          if ($field_type === 'text_with_summary') {
            $value = $form_state->getValue(["content_types_{$additional_field}", $content_type_id, 'fields', "{$field_name}", 'value']);
            $this->config('summary_made_easy.settings')
              ->set("content_types_{$additional_field}.{$content_type_id}.{$field_name}.value", $value)
              ->save();

            $summary = $form_state->getValue(["content_types_{$additional_field}", $content_type_id, 'fields', "{$field_name}", 'summary']);
            $this->config('summary_made_easy.settings')
              ->set("content_types_{$additional_field}.{$content_type_id}.{$field_name}.summary", $summary)
              ->save();
          } elseif (in_array($field_type, ['string', 'string_long', 'text', 'text_long'])) {
            $value = $form_state->getValue(["content_types_{$additional_field}", $content_type_id, 'fields', $field_name]);
            $this->config('summary_made_easy.settings')
              ->set("content_types_{$additional_field}.{$content_type_id}.{$field_name}", $value)
              ->save();
          }
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
