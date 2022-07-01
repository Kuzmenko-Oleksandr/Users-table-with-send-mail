<?php

namespace Drupal\custom_table_users\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TableForm.
 *
 * @package Drupal\custom_table_users\Form
 */
class TableForm extends FormBase
{
  /**
   * The mail manager.
   *
   * @var MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The email validator.
   *
   * @var EmailValidator
   */
  protected EmailValidator $emailValidator;

  /**
   * The language manager.
   *
   * @var LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;
  /**
   * Constructs a new EmailExampleGetFormPage.
   *
   * @param MailManagerInterface $mail_manager
   *   The mail manager.
   * @param LanguageManagerInterface $language_manager
   *   The language manager.
   * @param EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, EmailValidator $email_validator) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'custom_table_user';
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container
      ->get('plugin.manager.mail'), $container
      ->get('language_manager'), $container
      ->get('email.validator'));
    $form
      ->setMessenger($container
        ->get('messenger'));
    $form
      ->setStringTranslation($container
        ->get('string_translation'));
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $query = \Drupal::database()->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'name', 'mail']);
//    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(12);
    $results = $query->execute()->fetchAll();
    $header = [
      'userid' => t('User id'),
      'username' => t('Username'),
      'email' => t('Email'),
    ];

    $output = [];
    foreach ($results as $result) {
      if ($result->uid != 0 && $result->uid != 1) {
        $output[$result->uid] = [
          'userid' => $result->uid,
          'username' => $result->name,
          'email' => $result->mail,
        ];
      }
    }
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => t('No users found'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this
        ->t('Message for user or users'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Send'),
    ];
//    $form['pager'] = [
//      '#type' => 'pager'
//    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
//    parent::validateForm($form, $form_state);
    if (!$this->emailValidator
      ->isValid($form_state
        ->getValue('table'))) {
      $form_state
        ->setErrorByName('email', $this
          ->t('That e-mail address is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $form_values = $form_state
      ->getValues();

    $module = 'custom_table_user';
    $key = 'contact_message';

    $to = $form_values['email'];
    $from = $this
      ->config('system.site')
      ->get('mail');


    $params = $form_values;

    $language_code = $this->languageManager
      ->getDefaultLanguage()
      ->getId();
    $result = $this->mailManager
      ->mail($module, $key, $to, $language_code, $params, $from, true);
    if ($result['result'] == TRUE) {
      $this
        ->messenger()
        ->addMessage($this
          ->t('Your message has been sent.'));
    }
    else {
      $this
        ->messenger()
        ->addMessage($this
          ->t('There was a problem sending your message and it was not sent.'), 'error');
    }
  }
//    // Display result.
//    foreach ($form_state->getValues() as $key => $value) {
//      return "Hello, World";
//    }
//  }
}
