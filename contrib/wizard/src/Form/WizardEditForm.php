<?php

namespace Drupal\flexiform_wizard\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an edit form for flexiform wizard entities.
 */
class WizardEditForm extends WizardForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form = parent::form($form, $form_state);

    $form['parameters'] = [
      '#type' => 'table',
      '#title' => $this->t('Parameters'),
      '#header' => [
        $this->t('Label'),
        $this->t('Machine-Name'),
        $this->t('Entity Type'),
      ],
      '#empty' => $this->t("This wizard doesn't have an parameters defined yet. Add parameters by altering the path."),
    ];
    // @todo: Parameter rows.

    $form['pages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine-Name'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t("This wizard doesn't have any pages defined yet."),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'wizard-page-weight',
        ],
      ],
    ];
    foreach ($entity->getPages() as $name => $page) {
      $form['pages'][$name]['#attributes']['class'][] = 'draggable';
      $form['pages'][$name]['#weight'] = $page['weight'] ?: 0;
      $form['pages'][$name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page Label'),
        '#title_display' => 'invisible',
        '#default_value' => $page['label'],
      ];
      $form['pages'][$name]['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-Name'),
        '#title_display' => 'invisible',
        '#default_value' => $name,
      ];
      $form['pages'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $page['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $page['weight'],
        '#attributes' => ['class' => ['wizard-page-weight']],
      ];
    }

    return $form;
  }

}
