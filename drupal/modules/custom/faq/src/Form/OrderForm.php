<?php

/**
 * @file
 * Contains \Drupal\faq\Form\OrderForm.
 */

namespace Drupal\faq\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\faq\FaqHelper;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for reordering the FAQ-s.
 */
class OrderForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $category = NULL) {

    //get category id from route values
    if (is_numeric(FaqHelper::arg(1))) {
      $category = FaqHelper::arg(1);
    }

    $order = $date_order = '';
    $faq_settings = $this->config('faq.settings');

    $use_categories = $faq_settings->get('use_categories');
    if (!$use_categories) {
      $step = "order";
    }
    elseif (!isset($form_state['values']) && empty($category)) {
      $step = "categories";
    }
    else {
      $step = "order";
    }
    $form['step'] = array(
      '#type' => 'value',
      '#value' => $step,
    );

    // Categorized q/a.
    if ($step == "categories") {

      // Get list of categories.
      $vocabularies = Vocabulary::loadMultiple();
      $options = array();
      foreach ($vocabularies as $vid => $vobj) {
        $tree = taxonomy_get_tree($vid);
        foreach ($tree as $term) {
          if (!FaqHelper::taxonomyTermCountNodes($term->tid)) {
            continue;
          }
          $options[$term->tid] = $this->t($term->name);
          $form['choose_cat']['faq_category'] = array(
            '#type' => 'select',
            '#title' => t('Choose a category'),
            '#description' => t('Choose a category that you wish to order the questions for.'),
            '#options' => $options,
            '#multiple' => FALSE,
          );

          $form['choose_cat']['search'] = array(
            '#type' => 'submit',
            '#value' => t('Search'),
            '#submit' => array('faq_order_settings_choose_cat_form_submit'),
          );
        }
      }
    }
    else {
      $default_sorting = $faq_settings->get('default_sorting');
      $default_weight = 0;
      if ($default_sorting != 'DESC') {
        $default_weight = 1000000;
      }

      $options = array();
      if (!empty($form_state['values']['faq_category'])) {
        $category = $form_state['values']['faq_category'];
      }

      // Uncategorized ordering.
      $query = db_select('node', 'n');
      $query->join('node_field_data', 'd', 'n.nid = d.nid');
      $query->fields('n', array('nid'))
        ->fields('d', array('title'))
        ->addTag('node_access')
        ->condition('n.type', 'faq')
        ->condition('d.status', 1);

      // Works, but involves variable concatenation - safe though, since
      // $default_weight is an integer.
      $query->addExpression("COALESCE(w.weight, $default_weight)", 'effective_weight');
      // Doesn't work in Postgres.
      //$query->addExpression('COALESCE(w.weight, CAST(:default_weight as SIGNED))', 'effective_weight', array(':default_weight' => $default_weight));

      if (empty($category)) {
        $category = 0;
        $w_alias = $query->leftJoin('faq_weights', 'w', 'n.nid = %alias.nid AND %alias.tid = :category', array(':category' => $category));
        $query->orderBy('effective_weight', 'ASC')
          ->orderBy('d.sticky', 'DESC')
          ->orderBy('d.created', $default_sorting == 'DESC' ? 'DESC' : 'ASC');
      }
      // Categorized ordering.
      else {
        $ti_alias = $query->innerJoin('taxonomy_index', 'ti', '(n.nid = %alias.nid)');
        $w_alias = $query->leftJoin('faq_weights', 'w', 'n.nid = %alias.nid AND %alias.tid = :category', array(':category' => $category));
        $query->condition('ti.tid', $category);
        $query->orderBy('effective_weight', 'ASC')
          ->orderBy('d.sticky', 'DESC')
          ->orderBy('d.created', $default_sorting == 'DESC' ? 'DESC' : 'ASC');
      }

      $options = $query->execute()->fetchAll();

      $form['weight']['faq_category'] = array(
        '#type' => 'value',
        '#value' => $category,
      );

      // Show table ordering form.
      $form['order_no_cats']['#tree'] = TRUE;
      $form['order_no_cats']['#theme'] = 'faq_draggable_question_order_table';

      foreach ($options as $i => $record) {
        $form['order_no_cats'][$i]['nid'] = array(
          '#type' => 'hidden',
          '#value' => $record->nid,
        );
        $form['order_no_cats'][$i]['title'] = array('#markup' => String::checkPlain($record->title));
        $form['order_no_cats'][$i]['sort'] = array(
          '#type' => 'weight',
          '#delta' => count($options),
          '#default_value' => $i,
        );
      }

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save order'),
        '#button_type' => 'primary',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state['values']['op'] == t('Save order') && !empty($form_state['values']['order_no_cats'])) {

      foreach ($form_state['values']['order_no_cats'] as $i => $faq) {
        $nid = $faq['nid'];
        $index = $faq['sort'];
        db_merge('faq_weights')
          ->fields(array(
            'weight' => $index,
          ))
          ->key(array(
            'tid' => $form_state['values']['faq_category'],
            'nid' => $nid,
          ))
          ->execute();
      }

      parent::submitForm($form, $form_state);
    }
  }

}
