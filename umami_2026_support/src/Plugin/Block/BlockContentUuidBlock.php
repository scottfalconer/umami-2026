<?php

declare(strict_types=1);

namespace Drupal\umami_2026_support\Plugin\Block;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a Custom Block (block_content) loaded by UUID.
 *
 * Drupal recipes import configuration before content. When block placement
 * config uses the core `block_content:<uuid>` derivative plugin IDs, those
 * derivatives do not exist yet during install (because the block_content
 * entities are created later), producing warnings.
 *
 * This plugin avoids those warnings by using a stable plugin ID and storing
 * the target block UUID as configuration.
 */
#[Block(
  id: "umami_2026_block_content_uuid",
  admin_label: new TranslatableMarkup("Content block by UUID (Umami 2026)"),
  category: new TranslatableMarkup("Umami 2026"),
)]
final class BlockContentUuidBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly EntityDisplayRepositoryInterface $entityDisplayRepository,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'block_uuid' => '',
      'view_mode' => 'full',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $entity = $this->getBlockContentEntity();
    if (!$entity) {
      return AccessResult::forbidden();
    }
    return $entity->access('view', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $entity = $this->getBlockContentEntity();
    if (!$entity) {
      return [];
    }

    $view_mode = (string) ($this->configuration['view_mode'] ?? 'full');
    return $this->entityTypeManager
      ->getViewBuilder('block_content')
      ->view($entity, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['block_uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block UUID'),
      '#default_value' => (string) ($this->configuration['block_uuid'] ?? ''),
      '#required' => TRUE,
      '#description' => $this->t('UUID of the custom block (block_content) entity to render.'),
    ];

    $block = $this->getBlockContentEntity();
    if ($block instanceof BlockContentInterface) {
      $options = $this->entityDisplayRepository
        ->getViewModeOptionsByBundle('block_content', $block->bundle());
    }
    else {
      $options = $this->entityDisplayRepository->getViewModeOptions('block_content');
    }

    // Always allow "full"; depending on configuration, it may not be returned
    // by the repository.
    $options = ['full' => $this->t('Full')] + $options;

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#default_value' => (string) ($this->configuration['view_mode'] ?? 'full'),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['block_uuid'] = (string) $form_state->getValue('block_uuid');
    $this->configuration['view_mode'] = (string) $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function createPlaceholder(): bool {
    return TRUE;
  }

  /**
   * Loads the block_content entity configured for this block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block_content entity, translated for the current context.
   */
  private function getBlockContentEntity() {
    $uuid = (string) ($this->configuration['block_uuid'] ?? '');
    if ($uuid === '') {
      return NULL;
    }

    $entity = $this->entityRepository->loadEntityByUuid('block_content', $uuid);
    if (!$entity) {
      return NULL;
    }

    return $this->entityRepository->getTranslationFromContext($entity);
  }

}
