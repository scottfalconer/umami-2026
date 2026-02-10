<?php

declare(strict_types=1);

namespace Drupal\umami_2026_support\Plugin\ConfigAction;

use Drupal\canvas\ComponentSource\ComponentSourceManager;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Forces Canvas to (re)generate Component config entities during recipe apply.
   *
   * This is a workaround for install-time ordering during recipe application:
   * Canvas component generation/updates can occur after recipe config actions
   * run, but Drupal CMS recipes (and our site template) want to disable some
   * `canvas.component.*` configs via config actions.
   */
#[ConfigAction(
  id: 'umamiGenerateCanvasComponents',
  admin_label: new TranslatableMarkup('Generate Canvas components'),
)]
final class GenerateCanvasComponents implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ComponentSourceManager $componentSourceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(ComponentSourceManager::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    // The action targets a config object, but the actual work is global.
    $this->componentSourceManager->generateComponents();
  }

}
