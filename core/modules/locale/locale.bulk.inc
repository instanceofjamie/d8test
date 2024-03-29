<?php

/**
 * @file
 * Mass import-export and batch import functionality for Gettext .po files.
 */

use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\locale\Gettext;
use Drupal\locale\PoDatabaseReader;
use Drupal\Core\Language\Language;


/**
 * Form constructor for the translation import screen.
 *
 * @see locale_translate_import_form_submit()
 * @ingroup forms
 */
function locale_translate_import_form($form, &$form_state) {
  drupal_static_reset('language_list');
  $languages = language_list();

  // Initialize a language list to the ones available, including English if we
  // are to translate Drupal to English as well.
  $existing_languages = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $existing_languages[$langcode] = $language->name;
    }
  }

  // If we have no languages available, present the list of predefined languages
  // only. If we do have already added languages, set up two option groups with
  // the list of existing and then predefined languages.
  form_load_include($form_state, 'inc', 'language', 'language.admin');
  if (empty($existing_languages)) {
    $language_options = language_admin_predefined_list();
    $default = key($language_options);
  }
  else {
    $default = key($existing_languages);
    $language_options = array(
      t('Existing languages') => $existing_languages,
      t('Languages not yet added') => language_admin_predefined_list()
    );
  }

  $validators = array(
    'file_validate_extensions' => array('po'),
    'file_validate_size' => array(file_upload_max_size()),
  );
  $form['file'] = array(
    '#type' => 'file',
    '#title' => t('Translation file'),
    '#description' => theme('file_upload_help', array('description' => t('A Gettext Portable Object file.'), 'upload_validators' => $validators)),
    '#size' => 50,
    '#upload_validators' => $validators,
    '#attributes' => array('class' => array('file-import-input')),
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'locale') . '/locale.bulk.js' => array(),
      ),
    ),
  );
  $form['langcode'] = array(
    '#type' => 'select',
    '#title' => t('Language'),
    '#options' => $language_options,
    '#default_value' => $default,
    '#attributes' => array('class' => array('langcode-input')),
  );

  $form['customized'] = array(
    '#title' => t('Treat imported strings as custom translations'),
    '#type' => 'checkbox',
  );
  $form['overwrite_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );
  $form['overwrite_options']['not_customized'] = array(
    '#title' => t('Overwrite non-customized translations'),
    '#type' => 'checkbox',
    '#states' => array(
      'checked' => array(
         ':input[name="customized"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['overwrite_options']['customized'] = array(
    '#title' => t('Overwrite existing customized translations'),
    '#type' => 'checkbox',
  );

  $form['actions'] = array(
    '#type' => 'actions'
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import')
  );
  return $form;
}

/**
 * Form submission handler for locale_translate_import_form().
 */
function locale_translate_import_form_submit($form, &$form_state) {
  // Ensure we have the file uploaded.
  if ($file = file_save_upload('file', $form['file']['#upload_validators'], 'translations://')) {

    // Add language, if not yet supported.
    $language = language_load($form_state['values']['langcode']);
    if (empty($language)) {
      $language = new Language(array(
        'langcode' => $form_state['values']['langcode']
      ));
      $language = language_save($language);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($language->name))));
    }
    $options = array(
      'langcode' => $form_state['values']['langcode'],
      'overwrite_options' => $form_state['values']['overwrite_options'],
      'customized' => $form_state['values']['customized'] ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED,
    );
    $file = locale_translate_file_attach_properties($file, $options);
    $batch = locale_translate_batch_build(array($file->uri => $file), $options);
    batch_set($batch);
  }
  else {
    form_set_error('file', t('File to import not found.'));
    $form_state['rebuild'] = TRUE;
    return;
  }

  $form_state['redirect'] = 'admin/config/regional/translate';
  return;
}

/**
 * Form constructor for the Gettext translation files export form.
 *
 * @see locale_translate_export_form_submit()
 * @ingroup forms
 */
function locale_translate_export_form($form, &$form_state) {
  $languages = language_list();
  $language_options = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $language_options[$langcode] = $language->name;
    }
  }
  $language_default = language_default();

  if (empty($language_options)) {
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => LANGUAGE_SYSTEM,
    );
    $form['langcode_text'] = array(
      '#type' => 'item',
      '#title' => t('Language'),
      '#markup' => t('No language available. The export will only contain source strings.'),
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => $language_options,
      '#default_value' => $language_default->langcode,
      '#empty_option' => t('Source text only, no translations'),
      '#empty_value' => LANGUAGE_SYSTEM,
    );
    $form['content_options'] = array(
      '#type' => 'details',
      '#title' => t('Export options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
      '#states' => array(
        'invisible' => array(
           ':input[name="langcode"]' => array('value' => LANGUAGE_SYSTEM),
        ),
      ),
    );
    $form['content_options']['not_customized'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include non-customized translations'),
      '#default_value' => TRUE,
    );
    $form['content_options']['customized'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include customized translations'),
      '#default_value' => TRUE,
    );
    $form['content_options']['not_translated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include untranslated text'),
      '#default_value' => TRUE,
    );
  }

  $form['actions'] = array(
    '#type' => 'actions'
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export')
  );
  return $form;
}

/**
 * Form submission handler for locale_translate_export_form().
 */
function locale_translate_export_form_submit($form, &$form_state) {
  // If template is required, language code is not given.
  if ($form_state['values']['langcode'] != LANGUAGE_SYSTEM) {
    $language = language_load($form_state['values']['langcode']);
  }
  else {
    $language = NULL;
  }
  $content_options = isset($form_state['values']['content_options']) ? $form_state['values']['content_options'] : array();
  $reader = new PoDatabaseReader();
  $languageName = '';
  if ($language != NULL) {
    $reader->setLangcode($language->langcode);
    $reader->setOptions($content_options);
    $languages = language_list();
    $languageName = isset($languages[$language->langcode]) ? $languages[$language->langcode]->name : '';
    $filename = $language->langcode .'.po';
  }
  else {
    // Template required.
    $filename = 'drupal.pot';
  }

  $item = $reader->readItem();
  if (!empty($item)) {
    $uri = tempnam('temporary://', 'po_');
    $header = $reader->getHeader();
    $header->setProjectName(config('system.site')->get('name'));
    $header->setLanguageName($languageName);

    $writer = new PoStreamWriter;
    $writer->setUri($uri);
    $writer->setHeader($header);

    $writer->open();
    $writer->writeItem($item);
    $writer->writeItems($reader);
    $writer->close();

    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: text/plain; charset=utf-8");
    print file_get_contents($uri);
    drupal_exit();
  }
  else {
    drupal_set_message('Nothing to export.');
  }
}

/**
 * Prepare a batch to import all translations.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code. Optional, defaults to NULL, which means
 *     that the language will be detected from the name of the files.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 *
 * @param $force
 *   (optional) Import all available files, even if they were imported before.
 *
 * @todo
 *   Integrate with update status to identify projects needed and integrate
 *   l10n_update functionality to feed in translation files alike.
 *   See http://drupal.org/node/1191488.
 */
function locale_translate_batch_import_files($options, $force = FALSE) {
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'finish_feedback' => TRUE,
  );

  if (!empty($options['langcode'])) {
    $langcodes = array($options['langcode']);
  }
  else {
    // If langcode was not provided, make sure to only import files for the
    // languages we have enabled.
    $langcodes = array_keys(language_list());
  }

  $files = locale_translate_get_interface_translation_files(array(), $langcodes);

  if (!$force) {
    $result = db_select('locale_file', 'lf')
      ->fields('lf', array('langcode', 'uri', 'timestamp'))
      ->condition('langcode', $langcodes)
      ->execute()
      ->fetchAllAssoc('uri');
    foreach ($result as $uri => $info) {
      if (isset($files[$uri]) && filemtime($uri) <= $info->timestamp) {
        // The file is already imported and not changed since the last import.
        // Remove it from file list and don't import it again.
        unset($files[$uri]);
      }
    }
  }
  return locale_translate_batch_build($files, $options);
}

/**
 * Get interface translation files present in the translations directory.
 *
 * @param array $projects
 *   Project names from which to get the translation files and history.
 *   Defaults to all projects.
 * @param array $langcodes
 *   Language codes from which to  get the translation files and history.
 *   Defaults to all languagues
 *
 * @return array
 *   An array of interface translation files keyed by their URI.
 */
function locale_translate_get_interface_translation_files($projects = array(), $langcodes = array()) {
  module_load_include('compare.inc', 'locale');
  $files = array();
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  // Scan the translations directory for files matching a name pattern
  // containing a project name and language code: {project}.{langcode}.po or
  // {project}-{version}.{langcode}.po.
  // Only files of known projects and languages will be returned.
  if ($directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations')) {
    $result = file_scan_directory($directory, '![a-z_]+(\-[0-9a-z\.\-\+]+|)\.[^\./]+\.po$!', array('recurse' => FALSE));

    foreach ($result as $filepath => $file) {
      // Update the file object with project name and version from the file name.
      $file = locale_translate_file_attach_properties($file);
      if (in_array($file->project, $projects)) {
        if (in_array($file->langcode, $langcodes)) {
          $files[$file->uri] = $file;
        }
      }
    }
  }
  return $files;
}

/**
 * Build a locale batch from an array of files.
 *
 * @param $files
 *   Array of file objects to import.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code. Optional, defaults to NULL, which means
 *     that the language will be detected from the name of the files.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 *
 * @return
 *   A batch structure or FALSE if $files was empty.
 */
function locale_translate_batch_build($files, $options) {
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'finish_feedback' => TRUE,
  );
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call locale_translate_batch_import for every batch operation.
      $operations[] = array('locale_translate_batch_import', array($file, $options));
    }
    // Save the translation status of all files.
    $operations[] =  array('locale_translate_batch_import_save', array());

    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => drupal_get_path('module', 'locale') . '/locale.bulk.inc',
    );
    if ($options['finish_feedback']) {
      $batch['finished'] = 'locale_translate_batch_finished';
    }
    return $batch;
  }
  return FALSE;
}

/**
 * Perform interface translation import as a batch step.
 *
 * @param object $file
 *   A file object of the gettext file to be imported. The file object must
 *   contain a language parameter (other than LANGUAGE_NOT_SPECIFIED). This
 *   is used as the language of the import.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'message': Alternative message to display during import.
 *
 * @param $context
 *   Contains a list of files imported.
 */
function locale_translate_batch_import($file, $options, &$context) {
  // Merge the default values in the $options array.
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
  );

  if (isset($file->langcode) && $file->langcode != LANGUAGE_NOT_SPECIFIED) {

    try {
      if (empty($context['sandbox'])) {
        $context['sandbox']['parse_state'] = array(
          'filesize' => filesize(drupal_realpath($file->uri)),
          'chunk_size' => 200,
          'seek' => 0,
        );
      }
      // Update the seek and the number of items in the $options array().
      $options['seek'] = $context['sandbox']['parse_state']['seek'];
      $options['items'] = $context['sandbox']['parse_state']['chunk_size'];
      $report = GetText::fileToDatabase($file, $options);
      // If not yet finished with reading, mark progress based on size and
      // position.
      if ($report['seek'] < filesize($file->uri)) {

        $context['sandbox']['parse_state']['seek'] = $report['seek'];
        // Maximize the progress bar at 95% before completion, the batch API
        // could trigger the end of the operation before file reading is done,
        // because of floating point inaccuracies. See
        // http://drupal.org/node/1089472
        $context['finished'] = min(0.95, $report['seek'] / filesize($file->uri));
        if (isset($options['message'])) {
          $context['message'] = t('@message (@percent%).', array('@message' => $options['message'], '@percent' => (int) ($context['finished'] * 100)));
        }
        else {
          $context['message'] = t('Importing translation file: %filename (@percent%).', array('%filename' => $file->filename, '@percent' => (int) ($context['finished'] * 100)));
        }
      }
      else {
        // We are finished here.
        $context['finished'] = 1;

        // Store the file data for processing by the next batch operation.
        $file->timestamp = filemtime($file->uri);
        $context['results']['files'][$file->uri] = $file;
        $context['results']['languages'][$file->uri] = $file->langcode;
      }

      // Add the reported values to the statistics for this file.
      // Each import iteration reports statistics in an array. The results of
      // each iteration are added and merged here and stored per file.
      if (!isset($context['results']['stats']) || !isset($context['results']['stats'][$file->uri])) {
        $context['results']['stats'][$file->uri] = array();
      }
      foreach ($report as $key => $value) {
        if (is_numeric($report[$key])) {
          if (!isset($context['results']['stats'][$file->uri][$key])) {
            $context['results']['stats'][$file->uri][$key] = 0;
          }
          $context['results']['stats'][$file->uri][$key] += $report[$key];
        }
        elseif (is_array($value)) {
          $context['results']['stats'][$file->uri] += array($key => array());
          $context['results']['stats'][$file->uri][$key] = array_merge($context['results']['stats'][$file->uri][$key], $value);
        }
      }
    }
    catch (Exception $exception) {
      // Import failed. Store the data of the failing file.
      $context['results']['failed_files'][] = $file;
      watchdog('locale', 'Unable to import translations file: @file', array('@file' => $file->uri));
    }
  }
}

/**
 * Batch callback: Save data of imported files.
 *
 * @param $context
 *   Contains a list of imported files.
 */
function locale_translate_batch_import_save($context) {
  if (isset($context['results']['files'])) {
    foreach ($context['results']['files'] as $file) {
      // Update the file history if both project and version are known. This
      // table is used by the automated translation update function which tracks
      // translation status of module and themes in the system. Other
      // translation files are not tracked and are therefore not stored in this
      // table.
      if ($file->project && $file->version) {
        $file->last_checked = REQUEST_TIME;
        locale_translation_update_file_history($file);
      }
    }
  }
}

/**
 * Finished callback of system page locale import batch.
 */
function locale_translate_batch_finished($success, $results) {
  if ($success) {
    $additions = $updates = $deletes = $skips = 0;
    $strings = $langcodes = array();
    if (isset($results['failed_files'])) {
        if (module_exists('dblog')) {
          $message = format_plural(count($results['failed_files']), 'One translation file could not be imported. <a href="@url">See the log</a> for details.', '@count translation files could not be imported. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
        }
        else {
          $message = format_plural(count($results['failed_files']), 'One translation files could not be imported. See the log for details.', '@count translation files could not be imported. See the log for details.');
        }
        drupal_set_message($message, 'error');
    }
    if (isset($results['files'])) {
      $skipped_files = array();
      // If there are no results and/or no stats (eg. coping with an empty .po
      // file), simply do nothing.
      if ($results && isset($results['stats'])) {
        foreach ($results['stats'] as $filepath => $report) {
          $additions += $report['additions'];
          $updates += $report['updates'];
          $deletes += $report['deletes'];
          $skips += $report['skips'];
          if ($report['skips'] > 0) {
            $skipped_files[] = $filepath;
          }
          $strings = array_merge($strings, $report['strings']);
        }
        // Get list of unique string identifiers and language codes updated.
        $strings = array_unique($strings);
        $langcodes = array_unique(array_values($results['languages']));
      }
      drupal_set_message(format_plural(count($results['files']),
        'One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.',
        '@count translation files imported. %number translations were added, %update translations were updated and %delete translations were removed.',
        array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)
      ));
      watchdog('locale', 'Translations imported: %number added, %update updated, %delete removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes));

      if ($skips) {
        if (module_exists('dblog')) {
          $message = format_plural($skips, 'One translation string was skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
        }
        else {
          $message = format_plural($skips, 'One translation string was skipped because of disallowed or malformed HTML. See the log for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.');
        }
        drupal_set_message($message, 'warning');
        watchdog('locale', '@count disallowed HTML string(s) in files: @files.', array('@count' => $skips, '@files' => implode(',', $skipped_files)), WATCHDOG_WARNING);
      }

      if ($strings) {
        // Clear cache and force refresh of JavaScript translations.
        _locale_refresh_translations($langcodes, $strings);
      }
    }
  }
}

/**
 * Creates a file object and populates the timestamp property.
 *
 * @param $filepath
 *   The filepath of a file to import.
 *
 * @return
 *   An object representing the file.
 */
function locale_translate_file_create($filepath) {
  $file = new stdClass();
  $file->filename = drupal_basename($filepath);
  $file->uri = $filepath;
  $file->timestamp = filemtime($file->uri);
  return $file;
}

/**
 * Generate file properties from filename and options.
 *
 * An attempt is made to determine the translation language, project name and
 * project version from the file name. Supported file name patterns are:
 * {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or {langcode}.po.
 * Alternatively the translation language can be set using the $options.
 *
 * @param object $file
 *   A file object of the gettext file to be imported.
 * @param array $options
 *   An array with options:
 *   - 'langcode': The language code. Overrides the file language.
 *
 * @return object
 *   Modified file object.
 */
function locale_translate_file_attach_properties($file, $options = array()) {
  // Extract project, verion and language code from the file name. Supported:
  // {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or {langcode}.po
  preg_match('!
    (                       # project OR project and version OR emtpy (group 1)
      ([a-z_]+)             # project name (group 2)
      \.                    # .
      |                     # OR
      ([a-z_]+)             # project name (group 3)
      \-                    # -
      ([0-9a-z\.\-\+]+)     # version (group 4)
      \.                    # .
      |                     # OR
    )                       # (empty)
    ([^\./]+)               # language code (group 5)
    \.                      # .
    po                      # po extension
    $!x', $file->filename, $matches);
  if (isset($matches[5])) {
    $file->project = $matches[2] . $matches[3];
    $file->version = $matches[4];
    $file->langcode = isset($options['langcode']) ? $options['langcode'] : $matches[5];
  }
  else {
    $file->langcode = LANGUAGE_NOT_SPECIFIED;
  }
  return $file;
}

/**
 * Deletes interface translation files and translation history records.
 *
 * @param array $projects
 *   Project names from which to delete the translation files and history.
 *   Defaults to all projects.
 * @param array $langcodes
 *   Language codes from which to delete the translation files and history.
 *   Defaults to all languagues
 *
 * @return boolean
 *   TRUE if files are removed sucessfully. FALSE if one or more files could
 *   not be deleted.
 */
function locale_translate_delete_translation_files($projects = array(), $langcodes = array()) {
  $fail = FALSE;
  locale_translation_file_history_delete($projects, $langcodes);

  // Delete all translation files from the translations directory.
  if ($files = locale_translate_get_interface_translation_files($projects, $langcodes)) {
    foreach ($files as $file) {
      $success = file_unmanaged_delete($file->uri);
      if (!$success) {
        $fail = TRUE;
      }
    }
  }
  return !$fail;
}
