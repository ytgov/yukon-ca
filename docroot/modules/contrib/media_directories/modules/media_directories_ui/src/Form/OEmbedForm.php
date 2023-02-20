<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to add remote content using OEmbed resources.
 */
class OEmbedForm extends AddMediaFormBase {

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The oEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * Constructs a new OEmbedForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, Token $token, ThemeManagerInterface $theme_manager, RendererInterface $renderer, UrlResolverInterface $url_resolver, ResourceFetcherInterface $resource_fetcher) {
    parent::__construct($entity_type_manager, $current_user, $token, $theme_manager, $renderer);
    $this->urlResolver = $url_resolver;
    $this->resourceFetcher = $resource_fetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('theme.manager'),
      $container->get('renderer'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.resource_fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    $media_type = parent::getMediaType($form_state);
    if (!$media_type->getSource() instanceof OEmbedInterface) {
      throw new \InvalidArgumentException('Can only add media types which use an oEmbed source plugin.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'media-library-add-form--oembed';

    $media_type = $this->getMediaType($form_state);
    $providers = $media_type->getSource()->getProviders();

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['media-library-add-form__input-wrapper'],
      ],
    ];

    $form['container']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#description' => $this->t('Allowed providers: @providers.', [
        '@providers' => implode(', ', $providers),
      ]),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://',
        'class' => ['media-library-add-form-oembed-url'],
      ],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_directories_ui.media.add'),
        'options' => [
          'query' => [
            'media_type' => ($form_state->get('media_type') ? $form_state->get('media_type')->id() : $form_state->get('selected_type')),
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['media-library-add-form-oembed-submit'],
      ],
    ];
    return $form;
  }

  /**
   * Validates the oEmbed URL.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    if ($url) {
      try {
        $resource_url = $this->urlResolver->getResourceUrl($url);
        $this->resourceFetcher->fetchResource($resource_url);
      }
      catch (ResourceException $e) {
        $form_state->setErrorByName('url', $e->getMessage());
      }
    }
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('url')], $form, $form_state);
  }

}
