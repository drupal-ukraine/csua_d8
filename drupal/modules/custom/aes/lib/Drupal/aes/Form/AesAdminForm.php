<?php
/**
 * @file
 * Contains \Drupal\aes\Form\AesAdminForm.
 */

namespace Drupal\aes\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a fields form controller.
 */
class AesAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aes_admin';
  }

  /**
   * Constructs an object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('aes.settings');

    $phpseclib_error_msg = "";

    $phpsec_load_result = aes_load_phpsec();
    $phpsec_loaded = FALSE;
    if ($phpsec_load_result > 0) {
      $phpsec_loaded = TRUE;
    }
    elseif ($phpsec_load_result == -1) {
      // Missing set_include_path.
      $phpseclib_error_msg = " <span style=\"color:#f00;\">" . t("Warning: phpseclib was found but can't be loaded since this sever doesn't allow setting the PHP include path.") . "</span>";
    }
    elseif ($phpsec_load_result == -2) {
      // Couldn't find phpseclib - don't output anything since this is perfectly normal if using mcrypt.
    }
    elseif ($phpsec_load_result == -3) {
      // Found phpseclib, but couldn't read its files.
      $phpseclib_error_msg = " <span style=\"color:#f00;\">" . t("Warning: phpseclib was found but couldn't be read, check permissions.") . "</span>";
    }

    $form = array();

    $form['aes'] = array(
      '#type' => 'fieldset',
      '#title' => t('AES settings'),
      '#collapsible' => FALSE,
    );

    $encryption_implementations = array();
    if ($phpsec_loaded) {
      $encryption_implementations["phpseclib"] = t("PHP Secure Communications Library (phpseclib)");
    }
    if (extension_loaded("mcrypt")) {
      $encryption_implementations["mcrypt"] = t("Mcrypt extension");
    }

    if (!empty($encryption_implementations["mcrypt"]) && !empty($encryption_implementations["phpseclib"])) {
      $implementations_description = t("The Mcrypt implementation is the (only) implementation this module used until support for phpseclib was added. The Mcrypt implementation is faster than phpseclib and also lets you define the cipher to be used, other than that, the two implementations are equivalent.");
    }
    elseif (!empty($encryption_implementations["mcrypt"]) && empty($encryption_implementations["phpseclib"])) {
      $implementations_description = t("The Mcrypt extension is the only installed implementation.") . $phpseclib_error_msg;
    }
    elseif (empty($encryption_implementations["mcrypt"]) && !empty($encryption_implementations["phpseclib"])) {
      $implementations_description = t("PHP Secure Communications Library is the only installed implementation.");
    }

    if (empty($encryption_implementations)) {
      $encryption_implementations = array(t('None!'));
      drupal_set_message(t("You do not have an AES implementation installed!"), "error");
    }

    $form['aes']['aes_implementation'] = array(
      '#type' => 'select',
      '#title' => t('AES implementation'),
      '#options' => $encryption_implementations,
      '#default_value' => $config->get("implementation"),
      '#description' => $implementations_description,
    );

    if ($config->get("implementation") == "phpseclib") {
      $cipher_select_value = "rijndael-128";
      $cipher_select_disabled = TRUE;
      $cipher_description = t("Cipher is locked to Rijndael 128 when using the phpseclib implementation.");
    }
    else {
      $cipher_select_value = $config->get("cipher");
      $cipher_select_disabled = FALSE;
      $cipher_description = "";
    }

    $form['aes']['aes_cipher'] = array(
      '#type' => 'select',
      '#title' => t('Cipher'),
      '#options' => array(
        'rijndael-128' => 'Rijndael 128',
        'rijndael-192' => 'Rijndael 192',
        'rijndael-256' => 'Rijndael 256',
      ),
      '#default_value' => $cipher_select_value,
      '#disabled' => $cipher_select_disabled,
      '#description' => $cipher_description,
    );

    $form['aes']['aes_key'] = array(
      '#type' => 'password',
      '#title' => t('Key'),
      '#description' => t("The key for your encryption system. You normally don't need to worry about this since this module will generate a key for you if none is specified. However you have the option of using your own custom key here."),
    );

    $form['aes']['aes_key_c'] = array(
      '#type' => 'password',
      '#title' => t('Confirm key'),
    );

    $form['aes']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('BLAH!!.'));
  }
}
