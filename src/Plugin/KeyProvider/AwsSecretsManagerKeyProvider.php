<?php

/**
 * @file
 * Contains Drupal\aws_secrets_manager\Plugin\KeyProvider\AwsSecretsManagerKeyProvider.
 */

namespace Drupal\aws_secrets_manager\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;

use Drupal\aws_secrets_manager\ClientFactory;

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
 *     "required" = TRUE
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
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $name = $key->id();
    // @todo fetch key from AWS.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $name = $key->id();
    $label = $key->label();

    // @todo save key in AWS.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    $name = $key->id();
    // @todo delete key from AWS.
  }

}
