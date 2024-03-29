<?php

/**
 * @file
 * Definition of Drupal\views\ViewExecutable.
 */

namespace Drupal\views;

use Symfony\Component\HttpFoundation\Response;
use Drupal\views\Plugin\Core\Entity\View;

/**
 * @defgroup views_objects Objects that represent a View or part of a view
 * @{
 * These objects are the core of Views do the bulk of the direction and
 * storing of data. All database activity is in these objects.
 */

/**
 * An object to contain all of the data to generate a view, plus the member
 * functions to build the view query, execute the query and render the output.
 */
class ViewExecutable {

  /**
   * The config entity in which the view is stored.
   *
   * @var Drupal\views\Plugin\Core\Entity\View
   */
  public $storage;

  /**
   * Whether or not the view has been built.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $built = FALSE;

  /**
   * Whether the view has been executed/query has been run.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $executed = FALSE;

  /**
   * Any arguments that have been passed into the view.
   *
   * @var array
   */
  public $args = array();

  /**
   * An array of build info.
   *
   * @var array
   */
  public $build_info = array();

  /**
   * Whether this view uses AJAX.
   *
   * @var bool
   */
  public $use_ajax = FALSE;

  /**
   * Where the results of a query will go.
   *
   * The array must use a numeric index starting at 0.
   *
   * @var array
   */
  public $result = array();

  // May be used to override the current pager info.

  /**
   * The current page. If the view uses pagination.
   *
   * @var int
   */
  public $current_page = NULL;

  /**
   * The number of items per page.
   *
   * @var int
   */
  public $items_per_page = NULL;

  /**
   * The pager offset.
   *
   * @var int
   */
  public $offset = NULL;

  /**
   * The total number of rows returned from the query.
   *
   * @var array
   */
  public $total_rows = NULL;

  /**
   * Attachments to place before the view.
   *
   * @var array()
   */
  public $attachment_before = array();

  /**
   * Attachments to place after the view.
   *
   * @var array
   */
  public $attachment_after = array();

  // Exposed widget input

  /**
   * All the form data from $form_state['values'].
   *
   * @var array
   */
  public $exposed_data = array();

  /**
   * An array of input values from exposed forms.
   *
   * @var array
   */
  public $exposed_input = array();

  /**
   * Exposed widget input directly from the $form_state['values'].
   *
   * @var array
   */
  public $exposed_raw_input = array();

  /**
   * Used to store views that were previously running if we recurse.
   *
   * @var array
   */
  public $old_view = array();

  /**
   * To avoid recursion in views embedded into areas.
   *
   * @var array
   */
  public $parent_views = array();

  /**
   * Whether this view is an attachment to another view.
   *
   * @var bool
   */
  public $is_attachment = NULL;

  /**
   * Identifier of the current display.
   *
   * @var string
   */
  public $current_display;

  /**
   * Where the $query object will reside.
   *
   * @var Drupal\views\Plugin\query\QueryInterface
   */
  public $query = NULL;

  /**
   * The used pager plugin used by the current executed view.
   *
   * @var Drupal\views\Plugin\views\pager\PagerPluginBase
   */
  public $pager = NULL;

  /**
   * The current used display plugin.
   *
   * @var Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $display_handler;

  /**
   * The list of used displays of the view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var array
   */
  public $displayHandlers;

  /**
   * The current used style plugin.
   *
   * @var Drupal\views\Plugin\views\style\StylePluginBase
   */
  public $style_plugin;

  /**
   * Stores the current active row while rendering.
   *
   * @var int
   */
  public $row_index;

   /**
   * Allow to override the url of the current view.
   *
   * @var string
   */
  public $override_url = NULL;

  /**
   * Allow to override the path used for generated urls.
   *
   * @var string
   */
  public $override_path = NULL;

  /**
   * Allow to override the used database which is used for this query.
   *
   * @var bool
   */
  public $base_database = NULL;

  // Handlers which are active on this view.

  /**
   * Stores the field handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\field\FieldPluginBase
   * objects.
   *
   * @var array
   */
  public $field;

  /**
   * Stores the argument handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\argument\ArgumentPluginBase
   * objects.
   *
   * @var array
   */
  public $argument;

  /**
   * Stores the sort handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\sort\SortPluginBase objects.
   *
   * @var array
   */
  public $sort;

  /**
   * Stores the filter handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\filter\FilterPluginBase
   * objects.
   *
   * @var array
   */
  public $filter;

  /**
   * Stores the relationship handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\relationship\RelationshipPluginBase
   * objects.
   *
   * @var array
   */
  public $relationship;

  /**
   * Stores the area handlers for the header which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $header;

  /**
   * Stores the area handlers for the footer which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $footer;

  /**
   * Stores the area handlers for the empty text which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $empty;

  /**
   * Stores the current response object.
   *
   * @var Symfony\Component\HttpFoundation\Response
   */
  protected $response = NULL;

  /**
   * Does this view already have loaded it's handlers.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $inited;

  /**
   * The name of the active style plugin of the view.
   *
   * @todo remove this and just use $this->style_plugin
   *
   * @var string
   */
  public $plugin_name;

  /**
   * The options used by the style plugin of this running view.
   *
   * @todo To be able to remove it, Drupal\views\Plugin\views\argument\ArgumentPluginBase::default_summary()
   *   should instantiate the style plugin.
   * @var array
   */
  public $style_options;

  /**
   * The rendered output of the exposed form.
   *
   * @var string
   */
  public $exposed_widgets;

  /**
   * If this view has been previewed.
   *
   * @var bool
   */
  public $preview;

  /**
   * Force the query to calculate the total number of results.
   *
   * @todo Move to the query.
   *
   * @var bool
   */
  public $get_total_rows;

  /**
   * Indicates if the sorts have been built.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $build_sort;

  /**
   * Stores the many-to-one tables for performance.
   *
   * @var array
   */
  public $many_to_one_tables;

  /**
   * A unique identifier which allows to update multiple views output via js.
   *
   * @var string
   */
  public $dom_id;

  /**
   * A render array container to store render related information.
   *
   * For example you can alter the array and attach some css/js via the
   * #attached key. This is the required way to add custom css/js.
   *
   * @var array
   *
   * @see drupal_process_attached
   */
  public $element = array(
    '#attached' => array(
      'css' => array(),
      'js' => array(),
      'library' => array(),
    ),
  );

  /**
   * Constructs a new ViewExecutable object.
   *
   * @param Drupal\views\Plugin\Core\Entity\View $storage
   *   The view config entity the actual information is stored on.
   */
  public function __construct(View $storage) {
    // Reference the storage and the executable to each other.
    $this->storage = $storage;
    $this->storage->set('executable', $this);

    // Add the default css for a view.
    $this->element['#attached']['css'][] = drupal_get_path('module', 'views') . '/css/views.base.css';
  }

  /**
   * @todo.
   */
  public function save() {
    $this->storage->save();
  }

  /**
   * Returns a list of the sub-object types used by this view. These types are
   * stored on the display, and are used in the build process.
   */
  public function displayObjects() {
    return array('argument', 'field', 'sort', 'filter', 'relationship', 'header', 'footer', 'empty');
  }

  /**
   * Set the arguments that come to this view. Usually from the URL
   * but possibly from elsewhere.
   */
  public function setArguments($args) {
    $this->args = $args;
  }

  /**
   * Change/Set the current page for the pager.
   */
  public function setCurrentPage($page) {
    $this->current_page = $page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->set_current_page($page);
    }
  }

  /**
   * Get the current page from the pager.
   */
  public function getCurrentPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_current_page();
    }

    if (isset($this->current_page)) {
      return $this->current_page;
    }
  }

  /**
   * Get the items per page from the pager.
   */
  public function getItemsPerPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_items_per_page();
    }

    if (isset($this->items_per_page)) {
      return $this->items_per_page;
    }
  }

  /**
   * Set the items per page on the pager.
   */
  public function setItemsPerPage($items_per_page) {
    $this->items_per_page = $items_per_page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->set_items_per_page($items_per_page);
    }
  }

  /**
   * Get the pager offset from the pager.
   */
  public function getOffset() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_offset();
    }

    if (isset($this->offset)) {
      return $this->offset;
    }
  }

  /**
   * Set the offset on the pager.
   */
  public function setOffset($offset) {
    $this->offset = $offset;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->set_offset($offset);
    }
  }

  /**
   * Determine if the pager actually uses a pager.
   */
  public function usePager() {
    if (!empty($this->pager)) {
      return $this->pager->use_pager();
    }
  }

  /**
   * Whether or not AJAX should be used. If AJAX is used, paging,
   * tablesorting and exposed filters will be fetched via an AJAX call
   * rather than a page refresh.
   */
  public function setUseAJAX($use_ajax) {
    $this->use_ajax = $use_ajax;
  }

  /**
   * Set the exposed filters input to an array. If unset they will be taken
   * from $_GET when the time comes.
   */
  public function setExposedInput($filters) {
    $this->exposed_input = $filters;
  }

  /**
   * Figure out what the exposed input for this view is.
   */
  public function getExposedInput() {
    // Fill our input either from $_GET or from something previously set on the
    // view.
    if (empty($this->exposed_input)) {
      $this->exposed_input = drupal_container()->get('request')->query->all();
      // unset items that are definitely not our input:
      foreach (array('page', 'q') as $key) {
        if (isset($this->exposed_input[$key])) {
          unset($this->exposed_input[$key]);
        }
      }

      // If we have no input at all, check for remembered input via session.

      // If filters are not overridden, store the 'remember' settings on the
      // default display. If they are, store them on this display. This way,
      // multiple displays in the same view can share the same filters and
      // remember settings.
      $display_id = ($this->display_handler->isDefaulted('filters')) ? 'default' : $this->current_display;

      if (empty($this->exposed_input) && !empty($_SESSION['views'][$this->storage->get('name')][$display_id])) {
        $this->exposed_input = $_SESSION['views'][$this->storage->get('name')][$display_id];
      }
    }

    return $this->exposed_input;
  }

  /**
   * Set the display for this view and initialize the display handler.
   */
  public function initDisplay() {
    if (isset($this->current_display)) {
      return TRUE;
    }

    // Instantiate all displays
    foreach ($this->storage->get('display') as $id => $display) {
      $this->displayHandlers[$id] = drupal_container()->get("plugin.manager.views.display")->createInstance($display['display_plugin']);
      if (!empty($this->displayHandlers[$id])) {
        // Initialize the new display handler with data.
        // @todo Refactor display to not need the handler data by reference.
        $this->displayHandlers[$id]->init($this, $this->storage->getDisplay($id));
        // If this is NOT the default display handler, let it know which is
        // since it may well utilize some data from the default.
        // This assumes that the 'default' handler is always first. It always
        // is. Make sure of it.
        if ($id != 'default') {
          $this->displayHandlers[$id]->default_display =& $this->displayHandlers['default'];
        }
      }
    }

    $this->current_display = 'default';
    $this->display_handler = $this->displayHandlers['default'];

    return TRUE;
  }

  /**
   * Get the first display that is accessible to the user.
   *
   * @param array|string $displays
   *   Either a single display id or an array of display ids.
   *
   * @return string
   *   The first accessible display id, at least default.
   */
  public function chooseDisplay($displays) {
    if (!is_array($displays)) {
      return $displays;
    }

    $this->initDisplay();

    foreach ($displays as $display_id) {
      if ($this->displayHandlers[$display_id]->access()) {
        return $display_id;
      }
    }

    return 'default';
  }

  /**
   * Set the display as current.
   *
   * @param $display_id
   *   The id of the display to mark as current.
   */
  public function setDisplay($display_id = NULL) {
    // If we have not already initialized the display, do so. But be careful.
    if (empty($this->current_display)) {
      $this->initDisplay();

      // If handlers were not initialized, and no argument was sent, set up
      // to the default display.
      if (empty($display_id)) {
        $display_id = 'default';
      }
    }

    $display_id = $this->chooseDisplay($display_id);

    // If no display id sent in and one wasn't chosen above, we're finished.
    if (empty($display_id)) {
      return FALSE;
    }

    // Ensure the requested display exists.
    if (empty($this->displayHandlers[$display_id])) {
      $display_id = 'default';
      if (empty($this->displayHandlers[$display_id])) {
        debug(format_string('set_display() called with invalid display ID @display.', array('@display' => $display_id)));
        return FALSE;
      }
    }

    // Set the current display.
    $this->current_display = $display_id;

    // Ensure requested display has a working handler.
    if (empty($this->displayHandlers[$display_id])) {
      return FALSE;
    }

    // Set a shortcut
    $this->display_handler = $this->displayHandlers[$display_id];

    return TRUE;
  }

  /**
   * Find and initialize the style plugin.
   *
   * Note that arguments may have changed which style plugin we use, so
   * check the view object first, then ask the display handler.
   */
  public function initStyle() {
    if (isset($this->style_plugin)) {
      return is_object($this->style_plugin);
    }

    if (!isset($this->plugin_name)) {
      $style = $this->display_handler->getOption('style');
      $this->plugin_name = $style['type'];
      $this->style_options = $style['options'];
    }

    $this->style_plugin = drupal_container()->get("plugin.manager.views.style")->createInstance($this->plugin_name);

    if (empty($this->style_plugin)) {
      return FALSE;
    }

    // init the new style handler with data.
    $this->style_plugin->init($this, $this->display_handler, $this->style_options);
    return TRUE;
  }

  /**
   * Acquire and attach all of the handlers.
   */
  public function initHandlers() {
    $this->initDisplay();
    if (empty($this->inited)) {
      foreach ($this::viewsHandlerTypes() as $key => $info) {
        $this->_initHandler($key, $info);
      }
      $this->inited = TRUE;
    }
  }

  /**
   * Initialize the pager
   *
   * Like style initialization, pager initialization is held until late
   * to allow for overrides.
   */
  public function initPager() {
    if (!isset($this->pager)) {
      $this->pager = $this->display_handler->getPlugin('pager');

      if ($this->pager->use_pager()) {
        $this->pager->set_current_page($this->current_page);
      }

      // These overrides may have been set earlier via $view->set_*
      // functions.
      if (isset($this->items_per_page)) {
        $this->pager->set_items_per_page($this->items_per_page);
      }

      if (isset($this->offset)) {
        $this->pager->set_offset($this->offset);
      }
    }
  }

  /**
   * Render the pager, if necessary.
   */
  public function renderPager($exposed_input) {
    if (!empty($this->pager) && $this->pager->use_pager()) {
      return $this->pager->render($exposed_input);
    }

    return '';
  }

  /**
   * Create a list of base tables eligible for this view. Used primarily
   * for the UI. Display must be already initialized.
   */
  public function getBaseTables() {
    $base_tables = array(
      $this->storage->get('base_table') => TRUE,
      '#global' => TRUE,
    );

    foreach ($this->display_handler->getHandlers('relationship') as $handler) {
      $base_tables[$handler->definition['base']] = TRUE;
    }
    return $base_tables;
  }

  /**
   * Run the preQuery() on all active handlers.
   */
  protected function _preQuery() {
    foreach ($this::viewsHandlerTypes() as $key => $info) {
      $handlers = &$this->$key;
      $position = 0;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->position = $position;
        $handlers[$id]->preQuery();
        $position++;
      }
    }
  }

  /**
   * Run the postExecute() on all active handlers.
   */
  protected function _postExecute() {
    foreach ($this::viewsHandlerTypes() as $key => $info) {
      $handlers = &$this->$key;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->postExecute($this->result);
      }
    }
  }

  /**
   * Attach all of the handlers for each type.
   *
   * @param $key
   *   One of 'argument', 'field', 'sort', 'filter', 'relationship'
   * @param $info
   *   The $info from viewsHandlerTypes for this object.
   */
  protected function _initHandler($key, $info) {
    // Load the requested items from the display onto the object.
    $this->$key = $this->display_handler->getHandlers($key);

    // This reference deals with difficult PHP indirection.
    $handlers = &$this->$key;

    // Run through and test for accessibility.
    foreach ($handlers as $id => $handler) {
      if (!$handler->access()) {
        unset($handlers[$id]);
      }
    }
  }

  /**
   * Build all the arguments.
   */
  protected function _buildArguments() {
    // Initially, we want to build sorts and fields. This can change, though,
    // if we get a summary view.
    if (empty($this->argument)) {
      return TRUE;
    }

    // build arguments.
    $position = -1;

    // Create a title for use in the breadcrumb trail.
    $title = $this->display_handler->getOption('title');

    $this->build_info['breadcrumb'] = array();
    $breadcrumb_args = array();
    $substitutions = array();

    $status = TRUE;

    // Iterate through each argument and process.
    foreach ($this->argument as $id => $arg) {
      $position++;
      $argument = &$this->argument[$id];

      if ($argument->broken()) {
        continue;
      }

      $argument->setRelationship();

      $arg = isset($this->args[$position]) ? $this->args[$position] : NULL;
      $argument->position = $position;

      if (isset($arg) || $argument->has_default_argument()) {
        if (!isset($arg)) {
          $arg = $argument->get_default_argument();
          // make sure default args get put back.
          if (isset($arg)) {
            $this->args[$position] = $arg;
          }
          // remember that this argument was computed, not passed on the URL.
          $argument->is_default = TRUE;
        }

        // Set the argument, which will also validate that the argument can be set.
        if (!$argument->set_argument($arg)) {
          $status = $argument->validateFail($arg);
          break;
        }

        if ($argument->is_exception()) {
          $arg_title = $argument->exception_title();
        }
        else {
          $arg_title = $argument->get_title();
          $argument->query($this->display_handler->useGroupBy());
        }

        // Add this argument's substitution
        $substitutions['%' . ($position + 1)] = $arg_title;
        $substitutions['!' . ($position + 1)] = strip_tags(decode_entities($arg));

        // Since we're really generating the breadcrumb for the item above us,
        // check the default action of this argument.
        if ($this->display_handler->usesBreadcrumb() && $argument->uses_breadcrumb()) {
          $path = $this->getUrl($breadcrumb_args);
          if (strpos($path, '%') === FALSE) {
            if (!empty($argument->options['breadcrumb_enable']) && !empty($argument->options['breadcrumb'])) {
              $breadcrumb = $argument->options['breadcrumb'];
            }
            else {
              $breadcrumb = $title;
            }
            $this->build_info['breadcrumb'][$path] = str_replace(array_keys($substitutions), $substitutions, $breadcrumb);
          }
        }

        // Allow the argument to muck with this breadcrumb.
        $argument->set_breadcrumb($this->build_info['breadcrumb']);

        // Test to see if we should use this argument's title
        if (!empty($argument->options['title_enable']) && !empty($argument->options['title'])) {
          $title = $argument->options['title'];
        }

        $breadcrumb_args[] = $arg;
      }
      else {
        // determine default condition and handle.
        $status = $argument->default_action();
        break;
      }

      // Be safe with references and loops:
      unset($argument);
    }

    // set the title in the build info.
    if (!empty($title)) {
      $this->build_info['title'] = $title;
    }

    // Store the arguments for later use.
    $this->build_info['substitutions'] = $substitutions;

    return $status;
  }

  /**
   * Do some common building initialization.
   */
  public function initQuery() {
    if (!empty($this->query)) {
      $class = get_class($this->query);
      if ($class && $class != 'stdClass') {
        // return if query is already initialized.
        return TRUE;
      }
    }

    // Create and initialize the query object.
    $views_data = views_fetch_data($this->storage->get('base_table'));
    $this->storage->set('base_field', !empty($views_data['table']['base']['field']) ? $views_data['table']['base']['field'] : '');
    if (!empty($views_data['table']['base']['database'])) {
      $this->base_database = $views_data['table']['base']['database'];
    }

    $this->query = $this->display_handler->getPlugin('query');
    return TRUE;
  }

  /**
   * Build the query for the view.
   */
  public function build($display_id = NULL) {
    if (!empty($this->built)) {
      return;
    }

    if (empty($this->current_display) || $display_id) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    // Let modules modify the view just prior to building it.
    foreach (module_implements('views_pre_build') as $module) {
      $function = $module . '_views_pre_build';
      $function($this);
    }

    // Attempt to load from cache.
    // @todo Load a build_info from cache.

    $start = microtime(TRUE);
    // If that fails, let's build!
    $this->build_info = array(
      'query' => '',
      'count_query' => '',
      'query_args' => array(),
    );

    $this->initQuery();

    // Call a module hook and see if it wants to present us with a
    // pre-built query or instruct us not to build the query for
    // some reason.
    // @todo: Implement this. Use the same mechanism Panels uses.

    // Run through our handlers and ensure they have necessary information.
    $this->initHandlers();

    // Let the handlers interact with each other if they really want.
    $this->_preQuery();

    if ($this->display_handler->usesExposed()) {
      $exposed_form = $this->display_handler->getPlugin('exposed_form');
      $this->exposed_widgets = $exposed_form->render_exposed_form();
      if (form_set_error() || !empty($this->build_info['abort'])) {
        $this->built = TRUE;
        // Don't execute the query, but rendering will still be executed to display the empty text.
        $this->executed = TRUE;
        return empty($this->build_info['fail']);
      }
    }

    // Build all the relationships first thing.
    $this->_build('relationship');

    // Set the filtering groups.
    if (!empty($this->filter)) {
      $filter_groups = $this->display_handler->getOption('filter_groups');
      if ($filter_groups) {
        $this->query->set_group_operator($filter_groups['operator']);
        foreach ($filter_groups['groups'] as $id => $operator) {
          $this->query->set_where_group($operator, $id);
        }
      }
    }

    // Build all the filters.
    $this->_build('filter');

    $this->build_sort = TRUE;

    // Arguments can, in fact, cause this whole thing to abort.
    if (!$this->_buildArguments()) {
      $this->build_time = microtime(TRUE) - $start;
      $this->attachDisplays();
      return $this->built;
    }

    // Initialize the style; arguments may have changed which style we use,
    // so waiting as long as possible is important. But we need to know
    // about the style when we go to build fields.
    if (!$this->initStyle()) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    if ($this->style_plugin->usesFields()) {
      $this->_build('field');
    }

    // Build our sort criteria if we were instructed to do so.
    if (!empty($this->build_sort)) {
      // Allow the style handler to deal with sorting.
      if ($this->style_plugin->build_sort()) {
        $this->_build('sort');
      }
      // allow the plugin to build second sorts as well.
      $this->style_plugin->build_sort_post();
    }

    // Allow area handlers to affect the query.
    $this->_build('header');
    $this->_build('footer');
    $this->_build('empty');

    // Allow display handler to affect the query:
    $this->display_handler->query($this->display_handler->useGroupBy());

    // Allow style handler to affect the query:
    $this->style_plugin->query($this->display_handler->useGroupBy());

    // Allow exposed form to affect the query:
    if (isset($exposed_form)) {
      $exposed_form->query();
    }

    if (config('views.settings')->get('sql_signature')) {
      $this->query->add_signature($this);
    }

    // Let modules modify the query just prior to finalizing it.
    $this->query->alter($this);

    // Only build the query if we weren't interrupted.
    if (empty($this->built)) {
      // Build the necessary info to execute the query.
      $this->query->build($this);
    }

    $this->built = TRUE;
    $this->build_time = microtime(TRUE) - $start;

    // Attach displays
    $this->attachDisplays();

    // Let modules modify the view just after building it.
    foreach (module_implements('views_post_build') as $module) {
      $function = $module . '_views_post_build';
      $function($this);
    }

    return TRUE;
  }

  /**
   * Internal method to build an individual set of handlers.
   *
   * @todo Some filter needs this function, even it is internal.
   *
   * @param string $key
   *    The type of handlers (filter etc.) which should be iterated over to
   *    build the relationship and query information.
   */
  public function _build($key) {
    $handlers = &$this->$key;
    foreach ($handlers as $id => $data) {

      if (!empty($handlers[$id]) && is_object($handlers[$id])) {
        $multiple_exposed_input = array(0 => NULL);
        if ($handlers[$id]->multipleExposedInput()) {
          $multiple_exposed_input = $handlers[$id]->group_multiple_exposed_input($this->exposed_data);
        }
        foreach ($multiple_exposed_input as $group_id) {
          // Give this handler access to the exposed filter input.
          if (!empty($this->exposed_data)) {
            $converted = FALSE;
            if ($handlers[$id]->isAGroup()) {
              $converted = $handlers[$id]->convert_exposed_input($this->exposed_data, $group_id);
              $handlers[$id]->store_group_input($this->exposed_data, $converted);
              if (!$converted) {
                continue;
              }
            }
            $rc = $handlers[$id]->acceptExposedInput($this->exposed_data);
            $handlers[$id]->storeExposedInput($this->exposed_data, $rc);
            if (!$rc) {
              continue;
            }
          }
          $handlers[$id]->setRelationship();
          $handlers[$id]->query($this->display_handler->useGroupBy());
        }
      }
    }
  }

  /**
   * Execute the view's query.
   *
   * @param string $display_id
   *   The machine name of the display, which should be executed.
   *
   * @return bool
   *   Return whether the executing was successful, for example an argument
   *   could stop the process.
   */
  public function execute($display_id = NULL) {
    if (empty($this->built)) {
      if (!$this->build($display_id)) {
        return FALSE;
      }
    }

    if (!empty($this->executed)) {
      return TRUE;
    }

    // Don't allow to use deactivated displays, but display them on the live preview.
    if (!$this->display_handler->isEnabled() && empty($this->live_preview)) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_execute') as $module) {
      $function = $module . '_views_pre_execute';
      $function($this);
    }

    // Check for already-cached results.
    if (!empty($this->live_preview)) {
      $cache = $this->display_handler->getPlugin('cache', 'none');
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }
    if ($cache->cache_get('results')) {
      if ($this->pager->use_pager()) {
        $this->pager->total_items = $this->total_rows;
        $this->pager->update_page_info();
      }
    }
    else {
      $this->query->execute($this);
      // Enforce the array key rule as documented in
      // views_plugin_query::execute().
      $this->result = array_values($this->result);
      $this->_postExecute();
      $cache->cache_set('results');
    }

    // Let modules modify the view just after executing it.
    foreach (module_implements('views_post_execute') as $module) {
      $function = $module . '_views_post_execute';
      $function($this);
    }

    $this->executed = TRUE;
  }

  /**
   * Render this view for a certain display.
   *
   * Note: You should better use just the preview function if you want to
   * render a view.
   *
   * @param string $display_id
   *   The machine name of the display, which should be rendered.
   *
   * @return (string|NULL)
   *   Return the output of the rendered view or NULL if something failed in the process.
   */
  public function render($display_id = NULL) {
    $this->execute($display_id);

    // Check to see if the build failed.
    if (!empty($this->build_info['fail'])) {
      return;
    }
    if (!empty($this->build_info['denied'])) {
      return;
    }

    drupal_theme_initialize();
    $config = config('views.settings');

    // Set the response so other parts can alter it.
    $this->response = new Response('', 200);

    $start = microtime(TRUE);
    if (!empty($this->live_preview) && $config->get('ui.show.additional_queries')) {
      $this->startQueryCapture();
    }

    $exposed_form = $this->display_handler->getPlugin('exposed_form');
    $exposed_form->pre_render($this->result);

    // Check for already-cached output.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }
    if ($cache && $cache->cache_get('output')) {
    }
    else {
      if ($cache) {
        $cache->cache_start();
      }

      // Run pre_render for the pager as it might change the result.
      if (!empty($this->pager)) {
        $this->pager->pre_render($this->result);
      }

      // Initialize the style plugin.
      $this->initStyle();

      // Give field handlers the opportunity to perform additional queries
      // using the entire resultset prior to rendering.
      if ($this->style_plugin->usesFields()) {
        foreach ($this->field as $id => $handler) {
          if (!empty($this->field[$id])) {
            $this->field[$id]->pre_render($this->result);
          }
        }
      }

      $this->style_plugin->pre_render($this->result);

      // Let each area handler have access to the result set.
      foreach (array('header', 'footer', 'empty') as $area) {
        foreach ($this->{$area} as $handler) {
          $handler->preRender($this->result);
        }
      }

      // Let modules modify the view just prior to rendering it.
      foreach (module_implements('views_pre_render') as $module) {
        $function = $module . '_views_pre_render';
        $function($this);
      }

      // Let the themes play too, because pre render is a very themey thing.
      foreach ($GLOBALS['base_theme_info'] as $base) {
        $function = $base->name . '_views_pre_render';
        if (function_exists($function)) {
          $function($this);
        }
      }
      $function = $GLOBALS['theme'] . '_views_pre_render';
      if (function_exists($function)) {
        $function($this);
      }

      $this->display_handler->output = $this->display_handler->render();
      if ($cache) {
        $cache->cache_set('output');
      }
    }

    $exposed_form->post_render($this->display_handler->output);

    if ($cache) {
      $cache->post_render($this->display_handler->output);
    }

    // Let modules modify the view output after it is rendered.
    foreach (module_implements('views_post_render') as $module) {
      $function = $module . '_views_post_render';
      $function($this, $this->display_handler->output, $cache);
    }

    // Let the themes play too, because post render is a very themey thing.
    foreach ($GLOBALS['base_theme_info'] as $base) {
      $function = $base->name . '_views_post_render';
      if (function_exists($function)) {
        $function($this);
      }
    }
    $function = $GLOBALS['theme'] . '_views_post_render';
    if (function_exists($function)) {
      $function($this, $this->display_handler->output, $cache);
    }

    if (!empty($this->live_preview) && $config->get('ui.show.additional_queries')) {
      $this->endQueryCapture();
    }
    $this->render_time = microtime(TRUE) - $start;

    return $this->display_handler->output;
  }

  /**
   * Render a specific field via the field ID and the row #
   *
   * Note: You might want to use views_plugin_style::render_fields as it
   * caches the output for you.
   *
   * @param string $field
   *   The id of the field to be rendered.
   *
   * @param int $row
   *   The row number in the $view->result which is used for the rendering.
   *
   * @return string
   *   The rendered output of the field.
   */
  public function renderField($field, $row) {
    if (isset($this->field[$field]) && isset($this->result[$row])) {
      return $this->field[$field]->advanced_render($this->result[$row]);
    }
  }

  /**
   * Execute the given display, with the given arguments.
   * To be called externally by whatever mechanism invokes the view,
   * such as a page callback, hook_block, etc.
   *
   * This function should NOT be used by anything external as this
   * returns data in the format specified by the display. It can also
   * have other side effects that are only intended for the 'proper'
   * use of the display, such as setting page titles and breadcrumbs.
   *
   * If you simply want to view the display, use View::preview() instead.
   */
  public function executeDisplay($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || $this->current_display != $this->chooseDisplay($display_id)) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    $this->preExecute($args);

    // Execute the view
    $output = $this->display_handler->execute();

    $this->postExecute();
    return $output;
  }

  /**
   * Preview the given display, with the given arguments.
   *
   * To be called externally, probably by an AJAX handler of some flavor.
   * Can also be called when views are embedded, as this guarantees
   * normalized output.
   *
   * This function does not do any access checks on the view. It is the
   * responsibility of the caller to check $view->access() or implement other
   * access logic. To render the view normally with access checks, use
   * views_embed_view() instead.
   */
  public function preview($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || ((!empty($display_id)) && $this->current_display != $display_id)) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    $this->preview = TRUE;
    $this->preExecute($args);
    // Preview the view.
    $output = $this->display_handler->preview();

    $this->postExecute();
    return $output;
  }

  /**
   * Run attachments and let the display do what it needs to do prior
   * to running.
   */
  public function preExecute($args = array()) {
    $this->old_view[] = views_get_current_view();
    views_set_current_view($this);
    $display_id = $this->current_display;

    // Prepare the view with the information we have, but only if we were
    // passed arguments, as they may have been set previously.
    if ($args) {
      $this->setArguments($args);
    }

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_view') as $module) {
      $function = $module . '_views_pre_view';
      $function($this, $display_id, $this->args);
    }

    // Allow hook_views_pre_view() to set the dom_id, then ensure it is set.
    $this->dom_id = !empty($this->dom_id) ? $this->dom_id : md5($this->storage->get('name') . REQUEST_TIME . rand());

    // Allow the display handler to set up for execution
    $this->display_handler->preExecute();
  }

  /**
   * Unset the current view, mostly.
   */
  public function postExecute() {
    // unset current view so we can be properly destructed later on.
    // Return the previous value in case we're an attachment.

    if ($this->old_view) {
      $old_view = array_pop($this->old_view);
    }

    views_set_current_view(isset($old_view) ? $old_view : FALSE);
  }

  /**
   * Run attachment displays for the view.
   */
  public function attachDisplays() {
    if (!empty($this->is_attachment)) {
      return;
    }

    if (!$this->display_handler->acceptAttachments()) {
      return;
    }

    $this->is_attachment = TRUE;
    // Give other displays an opportunity to attach to the view.
    foreach ($this->displayHandlers as $id => $display) {
      if (!empty($this->displayHandlers[$id])) {
        // Create a clone for the attachments to manipulate. 'static' refers to the current class name.
        $cloned_view = new static($this->storage);
        $this->displayHandlers[$id]->attachTo($cloned_view, $this->current_display);
      }
    }
    $this->is_attachment = FALSE;
  }

  /**
   * Called to get hook_menu() information from the view and the named display handler.
   *
   * @param $display_id
   *   A display id.
   * @param $callbacks
   *   A menu callback array passed from views_menu_alter().
   */
  public function executeHookMenu($display_id = NULL, &$callbacks = array()) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->setDisplay($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->executeHookMenu($callbacks);
    }
  }

  /**
   * Called to get hook_block information from the view and the
   * named display handler.
   */
  public function executeHookBlockList($display_id = NULL) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->setDisplay($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->executeHookBlockList();
    }
  }

  /**
   * Determine if the given user has access to the view. Note that
   * this sets the display handler if it hasn't been.
   */
  public function access($displays = NULL, $account = NULL) {
    // Noone should have access to disabled views.
    if (!$this->storage->isEnabled()) {
      return FALSE;
    }

    if (!isset($this->current_display)) {
      $this->initDisplay();
    }

    if (!$account) {
      $account = $GLOBALS['user'];
    }

    // We can't use choose_display() here because that function
    // calls this one.
    $displays = (array)$displays;
    foreach ($displays as $display_id) {
      if (!empty($this->displayHandlers[$display_id])) {
        if ($this->displayHandlers[$display_id]->access($account)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Sets the used response object of the view.
   *
   * @param Symfony\Component\HttpFoundation\Response $response
   *   The response object which should be set.
   */
  public function setResponse(Response $response) {
    $this->response = $response;
  }

  /**
   * Gets the response object used by the view.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object of the view.
   */
  public function getResponse() {
    if (!isset($this->response)) {
      $this->response = new Response();
    }
    return $this->response;
  }

  /**
   * Get the view's current title. This can change depending upon how it
   * was built.
   */
  public function getTitle() {
    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }

    // During building, we might find a title override. If so, use it.
    if (!empty($this->build_info['title'])) {
      $title = $this->build_info['title'];
    }
    else {
      $title = $this->display_handler->getOption('title');
    }

    // Allow substitutions from the first row.
    if ($this->initStyle()) {
      $title = $this->style_plugin->tokenize_value($title, 0);
    }
    return $title;
  }

  /**
   * Override the view's current title.
   *
   * The tokens in the title get's replaced before rendering.
   */
  public function setTitle($title) {
    $this->build_info['title'] = $title;
    return TRUE;
  }

  /**
   * Force the view to build a title.
   */
  public function buildTitle() {
    $this->initDisplay();

    if (empty($this->built)) {
      $this->initQuery();
    }

    $this->initHandlers();

    $this->_buildArguments();
  }

  /**
   * Get the URL for the current view.
   *
   * This URL will be adjusted for arguments.
   */
  public function getUrl($args = NULL, $path = NULL) {
    if (!empty($this->override_url)) {
      return $this->override_url;
    }

    if (!isset($path)) {
      $path = $this->getPath();
    }
    if (!isset($args)) {
      $args = $this->args;

      // Exclude arguments that were computed, not passed on the URL.
      $position = 0;
      if (!empty($this->argument)) {
        foreach ($this->argument as $argument_id => $argument) {
          if (!empty($argument->is_default) && !empty($argument->options['default_argument_skip_url'])) {
            unset($args[$position]);
          }
          $position++;
        }
      }
    }
    // Don't bother working if there's nothing to do:
    if (empty($path) || (empty($args) && strpos($path, '%') === FALSE)) {
      return $path;
    }

    $pieces = array();
    $argument_keys = isset($this->argument) ? array_keys($this->argument) : array();
    $id = current($argument_keys);
    foreach (explode('/', $path) as $piece) {
      if ($piece != '%') {
        $pieces[] = $piece;
      }
      else {
        if (empty($args)) {
          // Try to never put % in a url; use the wildcard instead.
          if ($id && !empty($this->argument[$id]->options['exception']['value'])) {
            $pieces[] = $this->argument[$id]->options['exception']['value'];
          }
          else {
            $pieces[] = '*'; // gotta put something if there just isn't one.
          }

        }
        else {
          $pieces[] = array_shift($args);
        }

        if ($id) {
          $id = next($argument_keys);
        }
      }
    }

    if (!empty($args)) {
      $pieces = array_merge($pieces, $args);
    }
    return implode('/', $pieces);
  }

  /**
   * Get the base path used for this view.
   */
  public function getPath() {
    if (!empty($this->override_path)) {
      return $this->override_path;
    }

    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }
    return $this->display_handler->getPath();
  }

  /**
   * Get the breadcrumb used for this view.
   *
   * @param $set
   *   If true, use drupal_set_breadcrumb() to install the breadcrumb.
   */
  public function getBreadcrumb($set = FALSE) {
    // Now that we've built the view, extract the breadcrumb.
    $base = TRUE;
    $breadcrumb = array();

    if (!empty($this->build_info['breadcrumb'])) {
      foreach ($this->build_info['breadcrumb'] as $path => $title) {
        // Check to see if the frontpage is in the breadcrumb trail; if it
        // is, we'll remove that from the actual breadcrumb later.
        if ($path == config('system.site')->get('page.front')) {
          $base = FALSE;
          $title = t('Home');
        }
        if ($title) {
          $breadcrumb[] = l($title, $path, array('html' => TRUE));
        }
      }

      if ($set) {
        if ($base) {
          $breadcrumb = array_merge(drupal_get_breadcrumb(), $breadcrumb);
        }
        drupal_set_breadcrumb($breadcrumb);
      }
    }
    return $breadcrumb;
  }

  /**
   * Set up query capturing.
   *
   * db_query() stores the queries that it runs in global $queries,
   * bit only if dev_query is set to true. In this case, we want
   * to temporarily override that setting if it's not and we
   * can do that without forcing a db rewrite by just manipulating
   * $conf. This is kind of evil but it works.
   */
  public function startQueryCapture() {
    global $conf, $queries;
    if (empty($conf['dev_query'])) {
      $this->fix_dev_query = TRUE;
      $conf['dev_query'] = TRUE;
    }

    // Record the last query key used; anything already run isn't
    // a query that we are interested in.
    $this->last_query_key = NULL;

    if (!empty($queries)) {
      $keys = array_keys($queries);
      $this->last_query_key = array_pop($keys);
    }
  }

  /**
   * Add the list of queries run during render to buildinfo.
   *
   * @see View::start_query_capture()
   */
  public function endQueryCapture() {
    global $conf, $queries;
    if (!empty($this->fix_dev_query)) {
      $conf['dev_query'] = FALSE;
    }

    // make a copy of the array so we can manipulate it with array_splice.
    $temp = $queries;

    // Scroll through the queries until we get to our last query key.
    // Unset anything in our temp array.
    if (isset($this->last_query_key)) {
      while (list($id, $query) = each($queries)) {
        if ($id == $this->last_query_key) {
          break;
        }

        unset($temp[$id]);
      }
    }

    $this->additional_queries = $temp;
  }

  /**
   * Overrides Drupal\entity\Entity::createDuplicate().
   *
   * Makes a copy of this view that has been sanitized of handlers, any runtime
   *  data, ID, and UUID.
   */
  public function createDuplicate() {
    $data = config('views.view.' . $this->storage->id())->get();

    // Reset the name and UUID.
    unset($data['name']);
    unset($data['uuid']);

    return entity_create('view', $data);
  }

  /**
   * Unset references so that a $view object may be properly garbage
   * collected.
   */
  public function destroy() {
    foreach (array_keys($this->displayHandlers) as $display_id) {
      if (isset($this->displayHandlers[$display_id])) {
        $this->displayHandlers[$display_id]->destroy();
        unset($this->displayHandlers[$display_id]);
      }
    }

    foreach ($this::viewsHandlerTypes() as $type => $info) {
      if (isset($this->$type)) {
        $handlers = &$this->$type;
        foreach ($handlers as $id => $item) {
          $handlers[$id]->destroy();
        }
        unset($handlers);
      }
    }

    if (isset($this->style_plugin)) {
      $this->style_plugin->destroy();
    }

    $keys = array('current_display', 'display_handler', 'displayHandlers', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'result', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'many_to_one_tables');
    foreach ($keys as $key) {
      unset($this->$key);
    }

    // These keys are checked by the next init, so instead of unsetting them,
    // just set the default values.
    $keys = array('items_per_page', 'offset', 'current_page');
    foreach ($keys as $key) {
      if (isset($this->$key)) {
        $this->$key = NULL;
      }
    }

    $this->built = $this->executed = FALSE;
    $this->build_info = array();
    $this->attachment_before = '';
    $this->attachment_after = '';
  }

  /**
   * Make sure the view is completely valid.
   *
   * @return
   *   TRUE if the view is valid; an array of error strings if it is not.
   */
  public function validate() {
    $this->initDisplay();

    $errors = array();
    $this->display_errors = NULL;

    $current_display = $this->current_display;
    foreach ($this->displayHandlers as $id => $display) {
      if (!empty($display)) {
        if (!empty($display->deleted)) {
          continue;
        }

        $result = $this->displayHandlers[$id]->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
          // Mark this display as having validation errors.
          $this->display_errors[$id] = TRUE;
        }
      }
    }

    $this->setDisplay($current_display);
    return $errors ? $errors : TRUE;
  }

  /**
   * Provide a list of views handler types used in a view, with some information
   * about them.
   *
   * @return array
   *   An array of associative arrays containing:
   *   - title: The title of the handler type.
   *   - ltitle: The lowercase title of the handler type.
   *   - stitle: A singular title of the handler type.
   *   - lstitle: A singular lowercase title of the handler type.
   *   - plural: Plural version of the handler type.
   *   - (optional) type: The actual internal used handler type. This key is
   *     just used for header,footer,empty to link to the internal type: area.
   */
  public static function viewsHandlerTypes() {
    static $retval = NULL;

    // Statically cache this so t() doesn't run a bajillion times.
    if (!isset($retval)) {
      $retval = array(
        'field' => array(
          'title' => t('Fields'), // title
          'ltitle' => t('fields'), // lowercase title for mid-sentence
          'stitle' => t('Field'), // singular title
          'lstitle' => t('field'), // singular lowercase title for mid sentence
          'plural' => 'fields',
        ),
        'argument' => array(
          'title' => t('Contextual filters'),
          'ltitle' => t('contextual filters'),
          'stitle' => t('Contextual filter'),
          'lstitle' => t('contextual filter'),
          'plural' => 'arguments',
        ),
        'sort' => array(
          'title' => t('Sort criteria'),
          'ltitle' => t('sort criteria'),
          'stitle' => t('Sort criterion'),
          'lstitle' => t('sort criterion'),
          'plural' => 'sorts',
        ),
        'filter' => array(
          'title' => t('Filter criteria'),
          'ltitle' => t('filter criteria'),
          'stitle' => t('Filter criterion'),
          'lstitle' => t('filter criterion'),
          'plural' => 'filters',
        ),
        'relationship' => array(
          'title' => t('Relationships'),
          'ltitle' => t('relationships'),
          'stitle' => t('Relationship'),
          'lstitle' => t('Relationship'),
          'plural' => 'relationships',
        ),
        'header' => array(
          'title' => t('Header'),
          'ltitle' => t('header'),
          'stitle' => t('Header'),
          'lstitle' => t('Header'),
          'plural' => 'header',
          'type' => 'area',
        ),
        'footer' => array(
          'title' => t('Footer'),
          'ltitle' => t('footer'),
          'stitle' => t('Footer'),
          'lstitle' => t('Footer'),
          'plural' => 'footer',
          'type' => 'area',
        ),
        'empty' => array(
          'title' => t('No results behavior'),
          'ltitle' => t('no results behavior'),
          'stitle' => t('No results behavior'),
          'lstitle' => t('No results behavior'),
          'plural' => 'empty',
          'type' => 'area',
        ),
      );
    }

    return $retval;
  }

  /**
   * Returns the valid types of plugins that can be used.
   *
   * @return array
   *   An array of plugin type strings.
   */
  public static function getPluginTypes() {
    return array(
      'access',
      'area',
      'argument',
      'argument_default',
      'argument_validator',
      'cache',
      'display_extender',
      'display',
      'exposed_form',
      'field',
      'filter',
      'join',
      'pager',
      'query',
      'relationship',
      'row',
      'sort',
      'style',
      'wizard',
    );
  }

  /**
   * Adds an instance of a handler to the view.
   *
   * Items may be fields, filters, sort criteria, or arguments.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being added.
   * @param string $table
   *   The name of the table this handler is from.
   * @param string $field
   *   The name of the field this handler is from.
   * @param array $options
   *   (optional) Extra options for this instance. Defaults to an empty array.
   * @param string $id
   *   (optional) A unique ID for this handler instance. Defaults to NULL, in
   *   which case one will be generated.
   *
   * @return string
   *   The unique ID for this handler instance.
   */
  public function addItem($display_id, $type, $table, $field, $options = array(), $id = NULL) {
    $types = $this::viewsHandlerTypes();
    $this->setDisplay($display_id);

    $fields = $this->displayHandlers[$display_id]->getOption($types[$type]['plural']);

    if (empty($id)) {
      $id = $this->generateItemId($field, $fields);
    }

    // If the desired type is not found, use the original value directly.
    $handler_type = !empty($types[$type]['type']) ? $types[$type]['type'] : $type;

    // @todo This variable is never used.
    $handler = views_get_handler($table, $field, $handler_type);

    $fields[$id] = array(
      'id' => $id,
      'table' => $table,
      'field' => $field,
    ) + $options;

    $this->displayHandlers[$display_id]->setOption($types[$type]['plural'], $fields);

    return $id;
  }

  /**
   * Generates a unique ID for an handler instance.
   *
   * These handler instances are typically fields, filters, sort criteria, or
   * arguments.
   *
   * @param string $requested_id
   *   The requested ID for the handler instance.
   * @param array $existing_items
   *   An array of existing handler instancess, keyed by their IDs.
   *
   * @return string
   *   A unique ID. This will be equal to $requested_id if no handler instance
   *   with that ID already exists. Otherwise, it will be appended with an
   *   integer to make it unique, e.g., "{$requested_id}_1",
   *   "{$requested_id}_2", etc.
   */
  public static function generateItemId($requested_id, $existing_items) {
    $count = 0;
    $id = $requested_id;
    while (!empty($existing_items[$id])) {
      $id = $requested_id . '_' . ++$count;
    }
    return $id;
  }

  /**
   * Gets an array of handler instances for the current display.
   *
   * @param string $type
   *   The type of handlers to retrieve.
   * @param string $display_id
   *   (optional) A specific display machine name to use. If NULL, the current
   *   display will be used.
   *
   * @return array
   *   An array of handler instances of a given type for this display.
   */
  public function getItems($type, $display_id = NULL) {
    $this->setDisplay($display_id);

    if (!isset($display_id)) {
      $display_id = $this->current_display;
    }

    // Get info about the types so we can get the right data.
    $types = $this::viewsHandlerTypes();
    return $this->displayHandlers[$display_id]->getOption($types[$type]['plural']);
  }

  /**
   * Gets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler to retrieve.
   * @param string $id
   *   The ID of the handler to retrieve.
   *
   * @return array|null
   *   Either the handler instance's configuration, or NULL if the handler is
   *   not used on the display.
   */
  public function getItem($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = $this::viewsHandlerTypes();
    // Initialize the display
    $this->setDisplay($display_id);

    // Get the existing configuration
    $fields = $this->displayHandlers[$display_id]->getOption($types[$type]['plural']);

    return isset($fields[$id]) ? $fields[$id] : NULL;
  }

  /**
   * Sets the build array used by the view.
   *
   * @param array $element
   */
  public function setElement(&$element) {
    $this->element =& $element;
  }

  /**
   * Sets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param array|null $item
   *   An array of configuration for a handler, or NULL to remove this instance.
   *
   * @see set_item_option()
   */
  public function setItem($display_id, $type, $id, $item) {
    // Get info about the types so we can get the right data.
    $types = $this::viewsHandlerTypes();
    // Initialize the display.
    $this->setDisplay($display_id);

    // Get the existing configuration.
    $fields = $this->displayHandlers[$display_id]->getOption($types[$type]['plural']);
    if (isset($item)) {
      $fields[$id] = $item;
    }
    else {
      unset($fields[$id]);
    }

    // Store.
    $this->displayHandlers[$display_id]->setOption($types[$type]['plural'], $fields);
  }

  /**
   * Sets an option on a handler instance.
   *
   * Use this only if you have just 1 or 2 options to set; if you have many,
   * consider getting the handler instance, adding the options and using
   * set_item() directly.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param string $option
   *   The configuration key for the value being set.
   * @param mixed $value
   *   The value being set.
   *
   * @see set_item()
   */
  public function setItemOption($display_id, $type, $id, $option, $value) {
    $item = $this->getItem($display_id, $type, $id);
    $item[$option] = $value;
    $this->setItem($display_id, $type, $id, $item);
  }

  /**
   * Creates and stores a new display.
   *
   * @param string $id
   *   The ID for the display being added.
   *
   * @return Drupal\views\Plugin\views\display\DisplayPluginBase
   *   A reference to the new handler object.
   */
  public function &newDisplay($id) {
    // Create a handler.
    $display = $this->storage->get('display');
    $manager = drupal_container()->get("plugin.manager.views.display");
    $this->displayHandlers[$id] = $manager->createInstance($display[$id]['display_plugin']);
    if (empty($this->displayHandlers[$id])) {
      // provide a 'default' handler as an emergency. This won't work well but
      // it will keep things from crashing.
      $this->displayHandlers[$id] = $manager->createInstance('default');
    }

    if (!empty($this->displayHandlers[$id])) {
      // Initialize the new display handler with data.
      $this->displayHandlers[$id]->init($this, $display[$id]);
      // If this is NOT the default display handler, let it know which is
      if ($id != 'default') {
        // @todo is the '&' still required in php5?
        $this->displayHandlers[$id]->default_display = &$this->displayHandlers['default'];
      }
    }
    $this->storage->set('display', $display);

    return $this->displayHandlers[$id];
  }

}
