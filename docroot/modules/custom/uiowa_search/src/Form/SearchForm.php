<?php

namespace Drupal\uiowa_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements an example form.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uiowa_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['search_terms'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#label_attributes' => [
        'class' => [
          'sr-only',
        ],
      ],
      '#attributes' => [
        'placeholder' => 'Search this site',
      ],
      '#maxlength' => '256',
      '#size' => '15',
    ];

    $form['submit_search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#name' => 'btnG',
    ];

    $form['#action'] = Url::fromRoute('uiowa_search.search_results')->toString();

    $form['#attributes']['class'][] = 'uiowa-search--search-form';
    $form['#attributes']['class'][] = 'search-google-appliance-search-form';
    $form['#attributes']['aria-label'] = 'site search';
    $form['#attributes']['role'] = 'search';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('uiowa_search.search_results', [], [
      'query' => [
        'terms' => $form_state->getValue('search_terms'),
      ],
    ]));
  }

}
