<?php

namespace Drupal\flexiform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatch;
use Drupal\flexiform\Utility\Token;
use Drupal\flexiform\FlexiformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for flexiform pages.
 */
class FlexiformController extends ControllerBase {

  /**
   * The token service.
   *
   * @var \Drupal\flexiform\Utility\Token
   */
  protected $token;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The flexiform manager.
   *
   * @var \Drupal\flexiform\FlexiformManager
   */
  protected $flexiformManager;

  /**
   * Construct a new flexiform controller.
   *
   * @param \Drupal\flexiform\Utility\Token $token
   */
  public function __construct(Token $token, FormBuilderInterface $form_builder, FlexiformManager $flexiform_manager) {
    $this->token = $token;
    $this->formBuilder = $form_builder;
    $this->flexiformManager = $flexiform_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContinerInterface $container) {
    return new static(
      $container->get('flexiform.token'),
      $container->get('form_builder'),
      $container->get('flexiform.manager')
    );
  }

  /**
   * Flexiform form mode page.
   */
  public function formModePage(EntityFormMode $form_mode, Request $request) {
    $route_match = RouteMatch::createFromRequest($request);
    $settings = $form_mode->getThirdPartySettings('flexiform', 'exposure');

    $entity = $route_match->getParameter('base_entity');
    $provided = [];
    foreach ($settings['parameters'] as $namespace => $info) {
      if ($provided_entity = $route_match->getParameter($namespace)) {
        $provided[$namespace] = $provided_entity;
      }
    }

    list($entity_type_id, $display_mode_name) = explode('.', $form_mode->id(), 2);
    $entity_form_display = EntityFormDisplay::collectRenderDisplay($entity, $display_mode_name);
    $form_object = $this->flexiformManager->getFormObject($entity_form_display, [
        $entity_form_display->getBaseEntityNamespace() => $entity,
      ]);

    $form_state = new FormState();
    $form_state->set('form_entity_provided', $provided);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

  /**
   * Flexiform form mode title callback.
   */
  public function formModePageTitle(EntityFormMode $form_mode, Request $request) {
    $settings = $form_mode->getThirdPartySettings('flexiform', 'exposure');
    // @todo: Tokenize.
    return $settings['title'];
  }
}
