<?php

namespace Drupal\aws_secrets_manager\Plugin\KeyProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Aws\SecretsManager\SecretsManagerClient;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Adds a key provider that allows a key to be stored in AWS Secrets Manager.
 *
 * @KeyProvider(
 *   id = "aws_secrets_manager",
 *   label = "AWS Secrets Manager",
 *   description = @Translation("This provider stores the key in AWS Secrets Manager."),
 *   storage_method = "aws_secrets_manager",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class AwsSecretsManagerKeyProvider extends KeyProviderBase implements KeyProviderSettableValueInterface, KeyPluginFormInterface {

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The KMS client.
   *
   * @var \Aws\SecretsManager\SecretsManagerClient
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = \Drupal::configFactory();
    return $instance
      ->setClient($container->get('aws_secrets_manager.aws_secrets_manager_client'))
      ->setLogger($container->get('logger.channel.aws_secrets_manager'));
  }

  /**
   * Sets kmsClient property.
   *
   * @param \Aws\SecretsManager\SecretsManagerClient $client
   *   The secrets manager client.
   *
   * @return self
   *   Current object.
   */
  public function setClient(SecretsManagerClient $client) {
    $this->client = $client;
    return $this;
  }

  /**
   * Sets logger property.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   *
   * @return self
   *   Current object.
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'secret_name' => '',
      'property_name' => '',
      'read_only' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['secret_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Name'),
      '#description' => $this->t('The name of the secret in AWS Secrets Manager.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['secret_name'] ?? '',
    ];

    $form['property_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property Name'),
      '#description' => $this->t('If the secret is a JSON object, what is the property name that contains the value.'),
      '#required' => FALSE,
      '#default_value' => $this->getConfiguration()['property_name'] ?? '',
    ];

    $form['read_only'] = [
      '#title' => $this->t('Read only'),
      '#description' => $this->t('Whether or not we should try to update this value in AWS Secrets Manager.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getConfiguration()['read_only'] ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $name = $this->getConfiguration()['secret_name'] ?? $key->id();
    $property_name = $this->getConfiguration()['property_name'] ?? '';

    try {
      $response = $this->client->getSecretValue([
        "SecretId" => $this->secretName($name),
      ]);

      if ($value = $response->get('SecretString')) {
        if (!empty($property_name)) {
          $value = json_decode($value);
          if (!isset($value->{$property_name})) {
            $this->logger->error(sprintf("unable to find property %p in secret %s", $property_name, $name));
            return '';
          }
          else {
            return $value->{$property_name};
          }
        }
        return $value;
      }
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to retrieve secret %s", $name));
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $read_only = $this->getConfiguration()['read_only'] ?? FALSE;
    $name = $this->getConfiguration()['secret_name'] ?? $key->id();

    if ($read_only) {
      $this->logger->error(sprintf("can't update read only secret %s", $name));
      return FALSE;
    }

    $label = $key->label();

    $property_name = $this->getConfiguration()['property_name'] ?? '';

    if (!empty($property_name)) {
      $key_value = [$property_name => $key_value];
    }

    try {
      $this->client->createSecret([
        "Name" => $this->secretName($name),
        "Description" => $label,
        "SecretString" => $key_value,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to create secret %s", $name));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    $read_only = $this->getConfiguration()['read_only'] ?? FALSE;
    $name = $this->getConfiguration()['secret_name'] ?? $key->id();

    if ($read_only) {
      $this->logger->error(sprintf("can't delete read only secret %s", $name));
      return FALSE;
    }

    try {
      $this->client->deleteSecret([
        "SecretId" => $this->secretName($name),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to delete secret %s", $name));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Generates a prefixed secret name.
   *
   * @param string $key_name
   *   The key machine name.
   *
   * @return string
   *   The secret name as stored in AWS.
   */
  public function secretName($key_name) {
    $config = $this->configFactory->get('aws_secrets_manager.settings');
    $config->get('secret_prefix');
    $parts = [
      $config->get('secret_prefix'),
      $key_name,
    ];
    return implode("-", array_filter($parts));
  }

}
