<?php

namespace Drupal\aws_secrets_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a config form for AWS Secrets Manager.
 */
class AwsSecretsManagerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['aws_secrets_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_secrets_manager.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aws_secrets_manager.settings');
    $form['aws_key'] = [
      '#type' => 'textfield',
      '#title' => t('AWS Key'),
      '#default_value' => $config->get('aws_key'),
    ];
    $form['aws_secret'] = [
      '#type' => 'textfield',
      '#title' => t('AWS Secret'),
      '#default_value' => $config->get('aws_secret'),
    ];
    $form['aws_region'] = [
      '#type' => 'textfield',
      '#title' => t('AWS Region'),
      '#description' => t('The region which contains the KMS key(s)'),
      '#default_value' => $config->get('aws_region'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('aws_secrets_manager.settings');
    $config
      ->set('aws_key', $form_state->getValue('aws_key'))
      ->set('aws_secret', $form_state->getValue('aws_secret'))
      ->set('aws_region', $form_state->getValue('aws_region'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
