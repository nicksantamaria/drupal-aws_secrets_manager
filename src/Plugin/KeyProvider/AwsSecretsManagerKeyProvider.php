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

  protected $client;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ClientFactory $client_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->clientFactory = $client_factory;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('aws_secrets_manager.client_factory')
    );
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
