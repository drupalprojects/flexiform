<?php
/**
 * @file
 *  Contains class for a basic entity getter.
 */

/**
 * Form entity to get a field collection item.
 */
class FlexiformFormEntityFieldCollection extends FlexiformFormEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    parent::getEntity();

    $settings = $this->settings;
    $base_type = $this->getParamType('base');

    // Get the base entity.
    $base_entity = $this->getParam('base');

    // Check we have enough information to load the entity.
    if (!$base_entity) {
      return FALSE;
    }

    if ($settings['delta'] == 'new') {
      $field = field_info_field($settings['bundle']);
      $item_count = count($base_entity->{$settings['bundle']}[LANGUAGE_NONE]);
      if ($field['cardinality'] < 0 || $item_count < $field['cardinality']) {
        $target_entity = entity_create('field_collection_item', array('field_name' => $settings['bundle']));
        $target_entity->setHostEntity($base_type, $base_entity);
      }
    }
    else {
      if (empty($base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][$settings['delta']]['value'])) {
        if ($settings['create']) {
          $target_entity = entity_create('field_collection_item', array('field_name' => $settings['bundle']));
          $target_entity->setHostEntity($base_type, $base_entity);
        }
      }
      else {
        // Load the entity;
        $target_id = $base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][$settings['delta']]['value'];
        $target_entity = entity_load_single('field_collection_item', $target_id);
      }
    }

    if (!$target_entity) {
      return FALSE;
    }

    // Check the entity is the right bundle.
    list(,,$bundle) = entity_extract_ids('field_collection_item', $target_entity);
    if ($bundle != $settings['bundle']) {
      return FALSE;
    }

    return $target_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($form, &$form_state) {
    $form = parent::configForm($form, $form_state);
    $field = field_info_field($this->settings['bundle']);

    if ($field['cardinality'] != 1) {
      $options = array();
      $options['new'] = t('Always New Item');
      $i = 0;
      while ($i < 10) {
        if ($field['cardinality'] > 0 && $i >= $field['cardinality']) {
          break;
        }

        $options[$i] = $i+1;
        ++$i;
      }

      $form['settings']['delta'] = array(
        '#title' => t('Delta'),
        '#type' => 'select',
        '#description' => t('Select which value of this field you want to use. If "Always New Item" is selected, a new item will be added unless there is no space for one, in which case no entity will be provided.'),
        '#options' => $options,
        '#default_value' => !empty($this->settings['delta']) ? $this->settings['delta'] : 0,
      );
    }

    $form['settings']['create'] = array(
      '#title' => t('Create if Empty'),
      '#description' => t('When ticked, if the field item is empty, a new collection will be created.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->settings['create']),
    );

    return $form;
  }
}
